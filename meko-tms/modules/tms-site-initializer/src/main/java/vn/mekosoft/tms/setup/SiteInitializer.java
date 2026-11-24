package vn.mekosoft.tms.setup;

import com.liferay.portal.kernel.model.Company;
import com.liferay.portal.kernel.model.Group;
import com.liferay.portal.kernel.model.Layout;
import com.liferay.portal.kernel.model.ResourceConstants;
import com.liferay.portal.kernel.model.ResourcePermission;
import com.liferay.portal.kernel.model.Role;
import com.liferay.portal.kernel.model.RoleConstants;
import com.liferay.portal.kernel.model.User;
import com.liferay.portal.kernel.service.CompanyLocalServiceUtil;
import com.liferay.portal.kernel.service.GroupLocalServiceUtil;
import com.liferay.portal.kernel.service.LayoutLocalServiceUtil;
import com.liferay.portal.kernel.service.LayoutSetLocalServiceUtil;
import com.liferay.portal.kernel.service.ResourcePermissionLocalServiceUtil;
import com.liferay.portal.kernel.service.RoleLocalServiceUtil;
import com.liferay.portal.kernel.service.ServiceContext;
import com.liferay.portal.kernel.service.UserLocalServiceUtil;
import com.liferay.portal.kernel.util.UnicodeProperties;
import com.liferay.portal.kernel.util.PrefsPropsUtil;

import java.util.Collections;
import java.util.HashMap;
import java.util.Locale;
import java.util.Map;

public class SiteInitializer {
    private static final String THEME_ID = "acceestms_WAR_acceestmstheme";

    public void activate() throws Exception {
        for (Company company : CompanyLocalServiceUtil.getCompanies()) {
            configure(company);
        }
    }

    private void configure(Company company) throws Exception {
        long companyId = company.getCompanyId();
        Group group = GroupLocalServiceUtil.fetchFriendlyURLGroup(companyId, "/tms");
        if (group == null) {
            System.out.println("[MEKO-TMS] Site /tms absent; run init_liferay.py before installing.");
            return;
        }
        LegacyNavigationMigration.restore(PrefsPropsUtil.getPreferences(companyId), group.getFriendlyURL());
        group.setNameMap(Collections.singletonMap(Locale.US, "ACCEES TMS"));
        GroupLocalServiceUtil.updateGroup(group);
        Role administrator = RoleLocalServiceUtil.getRole(companyId, RoleConstants.ADMINISTRATOR);
        User admin = UserLocalServiceUtil.getRoleUsers(administrator.getRoleId()).stream()
            .filter(User::isActive).findFirst().orElseThrow(() -> new IllegalStateException("No active Administrator"));
        ServiceContext context = new ServiceContext();
        context.setCompanyId(companyId);
        context.setUserId(admin.getUserId());
        context.setScopeGroupId(group.getGroupId());
        context.setAddGuestPermissions(false);
        context.setAddGroupPermissions(false);
        for (String name : TmsPagePolicy.SITE_ROLES) {
            Role role = RoleLocalServiceUtil.fetchRole(companyId, name);
            if (role == null) {
                role = RoleLocalServiceUtil.addRole("accees-tms-" + name.toLowerCase(Locale.ROOT).replace(' ', '-'),
                    admin.getUserId(), null, 0, name, Collections.singletonMap(Locale.US, name),
                    Collections.emptyMap(), RoleConstants.TYPE_SITE, null, context);
            }
            if (role.getType() != RoleConstants.TYPE_SITE) {
                throw new IllegalStateException(name + " must be a Site Role");
            }
        }
        configurePages(group, context);
        LayoutSetLocalServiceUtil.updateLookAndFeel(group.getGroupId(), false, THEME_ID, "01", "");
        System.out.println("[ACCEES-TMS] Theme and page permissions configured for groupId=" + group.getGroupId());
    }

    private void configurePages(Group group, ServiceContext context) throws Exception {
        ensurePage(group, context, "/home", "Trang chủ", 0, false);
        ensurePage(group, context, "/courses", "Khóa học", 0, false);
        ensurePage(group, context, "/verify", "Tra cứu chứng chỉ", 0, false);
        ensurePage(group, context, "/my-profile", "Hồ sơ học tập", 0, false);
        Layout adminPage = ensurePage(group, context, "/admin", "Quản lý Lớp & Kế hoạch", 0, true);
        ensurePage(group, context, "/admin/enrollments", "Xét duyệt & Import Excel", adminPage.getLayoutId(), true);
        ensurePage(group, context, "/admin/email-templates", "Mẫu Email Thông báo", adminPage.getLayoutId(), true);
        ensurePage(group, context, "/admin/course-plan", "Tạo Kế hoạch / Mở lớp", adminPage.getLayoutId(), true);
        ensurePage(group, context, "/admin/import-logs", "Nhật ký Sync & Email", adminPage.getLayoutId(), true);
        ensurePage(group, context, "/admin/email-rejected", "Mẫu Email Từ chối", adminPage.getLayoutId(), true);
    }

    private Layout ensurePage(Group group, ServiceContext context, String url, String title,
            long parentId, boolean adminSpace) throws Exception {
        Layout page = LayoutLocalServiceUtil.fetchLayoutByFriendlyURL(group.getGroupId(), false, url);
        if (page == null) {
            page = LayoutLocalServiceUtil.addLayout("meko-tms-" + url.substring(1).replace('/', '-'),
                context.getUserId(), group.getGroupId(), false, parentId, title, title, "",
                "portlet", false, url, context);
        }
        UnicodeProperties properties = page.getTypeSettingsProperties();
        page.setNameMap(Collections.singletonMap(Locale.US, title));
        page.setTitleMap(Collections.singletonMap(Locale.US, title + " | ACCEES-TMS"));
        page.setHidden(false);
        properties.setProperty("isAdminSpace", Boolean.toString(adminSpace));
        properties.setProperty("layout-template-id", "1_column");
        page.setTypeSettingsProperties(properties);
        LayoutLocalServiceUtil.updateLayout(page);
        LayoutLocalServiceUtil.updateLookAndFeel(group.getGroupId(), false, page.getLayoutId(), THEME_ID, "01", "");
        Map<Long, String[]> permissions = new HashMap<>();
        for (String name : TmsPagePolicy.rolesFor(url)) {
            permissions.put(RoleLocalServiceUtil.getRole(group.getCompanyId(), name).getRoleId(), new String[] {"VIEW"});
        }
        permissions.put(RoleLocalServiceUtil.getRole(group.getCompanyId(), RoleConstants.ADMINISTRATOR).getRoleId(), new String[] {"VIEW"});
        if (url.equals("/home") || url.equals("/courses") || url.equals("/verify")) {
            permissions.put(RoleLocalServiceUtil.getRole(group.getCompanyId(), RoleConstants.GUEST).getRoleId(), new String[] {"VIEW"});
        }
        for (ResourcePermission permission : ResourcePermissionLocalServiceUtil.getResourcePermissions(
                group.getCompanyId(), Layout.class.getName(), ResourceConstants.SCOPE_INDIVIDUAL,
                Long.toString(page.getPlid()))) {
            if (!permissions.containsKey(permission.getRoleId())) {
                permissions.put(permission.getRoleId(), new String[0]);
            }
        }
        ResourcePermissionLocalServiceUtil.setResourcePermissions(group.getCompanyId(), Layout.class.getName(),
            ResourceConstants.SCOPE_INDIVIDUAL, Long.toString(page.getPlid()), permissions);
        return page;
    }

}

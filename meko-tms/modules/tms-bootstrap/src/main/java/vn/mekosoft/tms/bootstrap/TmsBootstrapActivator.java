package vn.mekosoft.tms.bootstrap;

import com.liferay.portal.kernel.model.Group;
import com.liferay.portal.kernel.model.Layout;
import com.liferay.portal.kernel.model.User;
import com.liferay.portal.kernel.service.GroupLocalService;
import com.liferay.portal.kernel.service.LayoutLocalService;
import com.liferay.portal.kernel.service.ServiceContext;
import com.liferay.portal.kernel.service.UserLocalService;
import com.liferay.portal.kernel.util.PortalUtil;

import java.util.Collections;
import java.util.Locale;
import java.util.Map;

import org.osgi.framework.BundleActivator;
import org.osgi.framework.BundleContext;
import org.osgi.framework.ServiceReference;

/**
 * Creates the DevLocal TMS site and its public home page on first deployment.
 * Repeated deployments are safe because both records are looked up first.
 */
public class TmsBootstrapActivator implements BundleActivator {

    private static final String ADMIN_EMAIL = "test@liferay.com";
    private static final String HOME_FRIENDLY_URL = "/home";
    private static final String SITE_FRIENDLY_URL = "/tms";

    @Override
    public void start(BundleContext bundleContext) {
        Thread bootstrapThread = new Thread(
            () -> bootstrap(bundleContext), "meko-tms-bootstrap");

        bootstrapThread.setDaemon(true);
        bootstrapThread.start();
    }

    @Override
    public void stop(BundleContext bundleContext) {
    }

    private void bootstrap(BundleContext bundleContext) {
        for (int attempt = 1; attempt <= 60; attempt++) {
            try {
                GroupLocalService groupLocalService = getService(
                    bundleContext, GroupLocalService.class);
                LayoutLocalService layoutLocalService = getService(
                    bundleContext, LayoutLocalService.class);
                UserLocalService userLocalService = getService(
                    bundleContext, UserLocalService.class);

                if ((groupLocalService == null) ||
                    (layoutLocalService == null) ||
                    (userLocalService == null)) {

                    Thread.sleep(5000);
                    continue;
                }

                long companyId = PortalUtil.getDefaultCompanyId();
                Group group = groupLocalService.fetchFriendlyURLGroup(
                    companyId, SITE_FRIENDLY_URL);

                if (group == null) {
                    System.out.println(
                        "[MEKO TMS Bootstrap] Site /tms is not available yet; retrying.");
                    Thread.sleep(5000);
                    continue;
                }

                Layout existingLayout = layoutLocalService.fetchLayoutByFriendlyURL(
                    group.getGroupId(), false, HOME_FRIENDLY_URL);

                if (existingLayout != null) {
                    System.out.println(
                        "[MEKO TMS Bootstrap] /web/tms/home already exists.");
                    return;
                }

                User admin = userLocalService.getUserByEmailAddress(
                    companyId, ADMIN_EMAIL);
                Locale locale = Locale.US;
                Map<Locale, String> nameMap = Collections.singletonMap(
                    locale, "TMS Dashboard");
                Map<Locale, String> titleMap = Collections.singletonMap(
                    locale, "MEKOSOFT TMS - Training Management System");
                Map<Locale, String> descriptionMap = Collections.singletonMap(
                    locale, "Training Management System Portal");
                Map<Locale, String> emptyMap = Collections.singletonMap(locale, "");
                Map<Locale, String> friendlyURLMap = Collections.singletonMap(
                    locale, HOME_FRIENDLY_URL);

                ServiceContext serviceContext = new ServiceContext();
                serviceContext.setAddGroupPermissions(true);
                serviceContext.setAddGuestPermissions(true);
                serviceContext.setCompanyId(companyId);
                serviceContext.setScopeGroupId(group.getGroupId());
                serviceContext.setUserId(admin.getUserId());

                layoutLocalService.addLayout(
                    "MEKO-TMS-HOME", admin.getUserId(), group.getGroupId(),
                    false, 0, 0, 0, nameMap, titleMap, descriptionMap,
                    emptyMap, emptyMap, "portlet", "layout-template-id=1_column\n",
                    false, false, friendlyURLMap, 0, serviceContext);

                System.out.println(
                    "[MEKO TMS Bootstrap] Created /web/tms/home successfully.");
                return;
            }
            catch (InterruptedException interruptedException) {
                Thread.currentThread().interrupt();
                return;
            }
            catch (Exception exception) {
                System.err.println(
                    "[MEKO TMS Bootstrap] Attempt " + attempt + " failed: " +
                        exception.getMessage());

                try {
                    Thread.sleep(5000);
                }
                catch (InterruptedException interruptedException) {
                    Thread.currentThread().interrupt();
                    return;
                }
            }
        }

        System.err.println(
            "[MEKO TMS Bootstrap] Timed out creating /web/tms/home.");
    }

    private <T> T getService(BundleContext bundleContext, Class<T> type) {
        ServiceReference<T> serviceReference =
            bundleContext.getServiceReference(type);

        if (serviceReference == null) {
            return null;
        }

        return bundleContext.getService(serviceReference);
    }
}

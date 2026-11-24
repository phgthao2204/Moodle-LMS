<!DOCTYPE html>
<#include init />
<#assign
    current_page = layout.getFriendlyURL()
    portal_url = themeDisplay.getPathFriendlyURLPublic() + themeDisplay.getScopeGroup().getFriendlyURL()
    public_url = themeDisplay.getScopeGroup().getFriendlyURL()
    lms_url = themeDisplay.getThemeSetting("lms-url")?remove_ending("/")
    login_url = (sign_in_url!"") + ((sign_in_url!"")?contains("?")?then("&", "?")) + "redirect=" + (portal_url + "/home")?url("UTF-8")
    logout_url = themeDisplay.getURLSignOut() + (themeDisplay.getURLSignOut()?contains("?")?then("&", "?")) + "referer=" + public_url?url("UTF-8")
    registration_url = is_signed_in?then(portal_url + "/my-profile", login_url)
    demo_mode = getterUtil.getBoolean(themeDisplay.getThemeSetting("demo-mode"))
    normal_view = !themeDisplay.isStateMaximized() && !themeDisplay.isStatePopUp()
    demo_pages = {"/home":"home", "/courses":"home", "/student":"home", "/student/courses":"home", "/student/my-profile":"profile", "/my-profile":"profile", "/verify":"verify", "/admin":"admin", "/admin/enrollments":"enrollments", "/admin/course-plan":"plan", "/admin/import-logs":"logs", "/admin/email-templates":"email", "/admin/email-rejected":"email-rejected"}
    demo_page = demo_pages[current_page]!""
    valid_page = themeDisplay.getResponse().getStatus() lt 400
    show_demo = demo_mode && normal_view && valid_page && demo_page?has_content
    accessible_pages = []
/>
<#list nav_items as item>
    <#assign accessible_pages = accessible_pages + [item.getLayout().getFriendlyURL()] />
    <#list item.getChildren() as child><#assign accessible_pages = accessible_pages + [child.getLayout().getFriendlyURL()] /></#list>
</#list>
<#assign
    student_access = accessible_pages?seq_contains("/my-profile")
    admin_access = accessible_pages?seq_contains("/admin")
    admin_space = admin_access && (current_page == "/admin" || current_page?starts_with("/admin/"))
    student_space = is_signed_in && !admin_space
/>
<html class="${root_css_class}" dir="<@liferay.language key='lang.dir' />" lang="${w3c_language_id}">
<head>
    <title>${html_title}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <@liferay_util["include"] page=top_head_include />
    <link rel="stylesheet" href="${css_folder}/fonts.css?t=${themeDisplay.getTheme().getTimestamp()?c}" />
</head>
<body class="${css_class} accees-tms <#if admin_space>tms-admin<#elseif student_space>tms-student<#else>tms-public</#if>" data-theme-build="@ACCEES_BUILD_ID@">
<@liferay_ui["quick-access"] contentId=show_demo?then("#tms-demo-content", "#main-content") />
<@liferay_util["include"] page=body_top_include />
<@liferay.control_menu />
<div id="wrapper">
    <header class="tms-header" id="banner">
        <a class="tms-brand" href="<#if admin_space>${portal_url}/admin<#else>${public_url}</#if>" aria-label="ACCEES CTU — Trang chủ đào tạo">
            <img class="tms-accees-logo" src="${images_folder}/logo_accees.png" alt="ACCEES" width="132" height="36" />
            <img class="tms-ctu-logo" src="${images_folder}/logo_ctu.png" alt="Đại học Cần Thơ" width="36" height="36" />
            <span class="tms-brand-badge">TMS<#if admin_space> ADMIN<#elseif student_space> STUDENT</#if></span>
        </a>
        <#if admin_space><span class="tms-role-badge">Không gian Quản trị Đào tạo</span></#if>
        <#if !admin_space><#include "${full_templates_path}/navigation.ftl" /></#if>
        <div class="tms-account">
            <#if admin_space><a class="tms-link-button" href="${public_url}">Xem cổng Công khai</a>
            <#elseif admin_access><a class="tms-link-button tms-admin-button" href="${portal_url}/admin">Quản trị</a>
            </#if>
            <#if !admin_space><a data-senna-off="true" class="tms-link-button tms-lms-link" href="${lms_url}/<#if is_signed_in>my/</#if>">Vào học LMS</a></#if>
            <#if is_signed_in><@liferay.user_personal_bar /><a class="tms-logout" data-senna-off="true" href="${logout_url}">Đăng xuất</a>
            <#elseif show_sign_in><a class="tms-login" data-senna-off="true" href="${login_url}">Đăng nhập</a></#if>
        </div>
    </header>
    <div class="tms-shell">
        <#if admin_space>
            <#include "${full_templates_path}/sidebar.ftl" />
        </#if>
        <div class="tms-content" id="content">
            <#if show_demo>
                <div class="tms-demo-note">Dữ liệu minh họa để kiểm thử giao diện</div>
                <div class="tms-demo demo-${demo_page}" id="tms-demo-content" data-demo-page="${demo_page}" data-portal-url="${portal_url}" data-lms-url="${lms_url}">
                    <#include "${full_templates_path}/demo/${demo_page}.ftl" />
                </div>
            </#if>
            <div class="tms-widgets <#if show_demo>tms-demo-widgets</#if>">
                <#if selectable><@liferay_util["include"] page=content_include />
                <#else>
                    ${portletDisplay.recycle()}
                    ${portletDisplay.setTitle(the_title)}
                    <@liferay_theme["wrap-portlet"] page="portlet.ftl"><@liferay_util["include"] page=content_include /></@>
                </#if>
            </div>
        </div>
    </div>
    <#include "${full_templates_path}/footer.ftl" />
</div>
<@liferay_util["include"] page=body_bottom_include />
<@liferay_util["include"] page=bottom_include />
<#if show_demo><script src="${javascript_folder}/demo.js?t=${themeDisplay.getTheme().getTimestamp()?c}" defer></script></#if>
</body>
</html>

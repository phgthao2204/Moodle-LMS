<aside class="tms-sidebar">
    <nav aria-label="Quản trị Đào tạo">
        <#assign groups = [
            {"title":"Điều phối Đào tạo", "links":[{"path":"/admin", "label":"Quản lý Lớp & Kế hoạch"}, {"path":"/admin/course-plan", "label":"Tạo Kế hoạch / Mở lớp"}]},
            {"title":"Học viên & Ghi danh", "links":[{"path":"/admin/enrollments", "label":"Xét duyệt & Import Excel"}]},
            {"title":"Hệ thống & Thông báo", "links":[{"path":"/admin/import-logs", "label":"Nhật ký Sync & Email"}, {"path":"/admin/email-templates", "label":"Mẫu Email Thông báo"}, {"path":"/admin/email-rejected", "label":"Mẫu Email Từ chối"}]}
        ] />
        <#list groups as group>
            <p class="tms-sidebar-group">${group.title}</p>
            <#list group.links as link>
                <#if accessible_pages?seq_contains(link.path)>
                    <a href="${portal_url}${link.path}" class="<#if current_page == link.path>is-active</#if>" <#if current_page == link.path>aria-current="page"</#if>>${link.label}</a>
                </#if>
            </#list>
        </#list>
    </nav>
</aside>

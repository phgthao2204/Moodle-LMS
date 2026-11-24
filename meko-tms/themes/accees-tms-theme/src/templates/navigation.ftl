<nav class="tms-navigation" aria-label="Điều hướng chính">
    <#if student_access>
        <#assign labels = {"/home":"Trang chủ", "/courses":"Khóa học", "/my-profile":"Hồ sơ Học tập", "/verify":"Tra cứu Chứng chỉ"} />
    <#else>
        <#assign labels = {"/home":"Trang chủ", "/courses":"Khóa học", "/verify":"Tra cứu Chứng chỉ"} />
    </#if>
    <#list labels as url, label>
        <a href="${portal_url}${url}" class="<#if current_page == url>is-active</#if>" <#if current_page == url>aria-current="page"</#if>>${label}</a>
    </#list>
</nav>

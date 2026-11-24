# CHUẨN LẬP TRÌNH LIFERAY 7.4 GA132 & MOODLE 5.3 (`CODING PLAYBOOK`)

Tài liệu kỹ thuật bắt buộc cho Dev khi viết **MVC Portlet**, **React Portlet**, **Service Builder Finder**, và **Moodle External Web Service**.

---

## 1. Kiến trúc Không gian Giao diện TMS (`meko-portal-theme`) & Lựa chọn Dạng Portlet

Dùng **DUY NHẤT `1` Theme `meko-portal-theme.war`** cho toàn bộ TMS (đồng bộ Header trắng `58px`, logo `MEKOSOFT [TMS]`, màu `#0000B6` / `#39B500` / `#EA6400`, font `Be Vietnam Pro`, 0% Emoji với bên Moodle LMS) — **không tạo 2–3 theme riêng**. Sự khác nhau giữa Khách, Học viên và Quản trị chỉ đến từ **Layout Template + Phân quyền RBAC trên Menu**:

1. **Không gian 1 — Cổng Công khai & Người học (`/tms`, `/tms/courses`, `/tms/my-profile`, `/tms/verify`):**
   - **Layout:** Dùng Layout chuẩn **Top Navbar ngang 1 dòng duy nhất** (`white-space: nowrap`, không Left Sidebar).
   - **Phân quyền hiển thị theo đăng nhập:** Khách (`Guest`) chỉ thấy `Trang chủ | Khóa học | Tra cứu Chứng chỉ` + nút `Đăng nhập`; khi Học viên (`Student`) đăng nhập, Liferay RBAC tự bật thêm mục `Hồ sơ Học tập` + Avatar cá nhân.
2. **Không gian 2 — Không gian Quản trị Đào tạo (`/tms/admin/...`):**
   - **Vai trò:** Cán bộ Quản lý Khóa học (`Course Manager`), Cán bộ Ghi danh (`Enrollment Officer`), Quản trị viên (`System Admin`).
   - **Layout:** Vẫn dùng **cùng 1 Theme `meko-portal-theme`**, chỉ chọn Page Layout Template `meko-admin-sidebar-layout` (có **Left Sidebar `250px`**) riêng cho cây trang `/tms/admin` và khóa quyền View theo Role quản trị (chỉ chứa menu quản trị: `Quản lý Lớp & Kế hoạch`, `Xét duyệt & Import Excel`, `Mẫu Email Thông báo`).

| Dạng Portlet | Áp dụng cho màn hình nào? | Giao tiếp Backend |
| :--- | :--- | :--- |
| **MVC Portlet (JSP + Clay Taglib)** | Danh sách quản trị chuẩn, lọc, phân trang, CRUD đơn giản, nhật ký đồng bộ (`tms-course-admin-web` list, `TMSSyncLog`). | `MVCRenderCommand` + `MVCActionCommand` (Post-Redirect-Get) |
| **React Portlet (MVC Shell bọc React + TS)** | Form nhiều bước, Upload & Preview Excel `.xlsx`, Catalog + Ghi danh 1-Click, Hồ sơ học viên + Radar Chart (`tms-enrollment-admin-web`, `tms-profile-web`). | `view.jsp` truyền `LiferayBridgeContext` → React gọi `MVCResourceCommand` (JSON + `p_auth`) |

---

## 2. Khung Chuẩn MVC Portlet (Taglib Liferay + `DisplayContext`)

### 2.1. Cấu trúc thư mục
```text
modules/tms-course-admin-web/
├── bnd.bnd
├── build.gradle
└── src/main/
    ├── java/vn/mekosoft/tms/course/web/
    │   ├── constants/TMSCoursePortletKeys.java
    │   ├── display/context/CourseManagementDisplayContext.java
    │   └── portlet/
    │       ├── TMSCourseAdminPortlet.java
    │       └── action/ (ViewCoursesMVCRenderCommand, EditCourseMVCRenderCommand, SaveCourseMVCActionCommand, DeployToMoodleMVCActionCommand)
    └── resources/META-INF/resources/
        ├── init.jsp
        ├── view.jsp
        ├── course_actions.jsp
        └── edit_course.jsp
```

### 2.2. `init.jsp` Chuẩn Dùng Chung
```jsp
<%@ taglib uri="http://java.sun.com/jsp/jstl/core" prefix="c" %>
<%@ taglib uri="http://java.sun.com/portlet_2_0" prefix="portlet" %>
<%@ taglib uri="http://liferay.com/tld/aui" prefix="aui" %>
<%@ taglib uri="http://liferay.com/tld/clay" prefix="clay" %>
<%@ taglib uri="http://liferay.com/tld/frontend" prefix="liferay-frontend" %>
<%@ taglib uri="http://liferay.com/tld/security" prefix="liferay-security" %>
<%@ taglib uri="http://liferay.com/tld/theme" prefix="liferay-theme" %>
<%@ taglib uri="http://liferay.com/tld/ui" prefix="liferay-ui" %>

<%@ page import="com.liferay.portal.kernel.language.LanguageUtil" %>
<%@ page import="com.liferay.portal.kernel.util.HtmlUtil" %>
<%@ page import="com.liferay.portal.kernel.util.ParamUtil" %>
<%@ page import="com.liferay.portal.kernel.util.WebKeys" %>
<%@ page import="javax.portlet.PortletURL" %>

<liferay-frontend:defineObjects />
<liferay-theme:defineObjects />
<portlet:defineObjects />
```

### 2.3. Quy tắc `DisplayContext` & `view.jsp`
- **Không viết query DB trong JSP:** Mọi logic build `SearchContainer`, nút `+ Thêm mới` (`CreationMenu`), bộ lọc dropdown nằm trong `*DisplayContext extends SearchContainerManagementToolbarDisplayContext`.
- **Khung `view.jsp` chuẩn 3 khối:**
```jsp
<%@ include file="/init.jsp" %>
<%
CourseManagementDisplayContext displayContext =
    (CourseManagementDisplayContext) request.getAttribute("COURSE_DISPLAY_CONTEXT");
%>
<liferay-ui:success key="course-saved" message="Lưu thông tin khóa học thành công." />
<liferay-ui:error key="course-deploy-failed" message="Lỗi đồng bộ sang Moodle. Xem Nhật ký đồng bộ." />

<clay:management-toolbar managementToolbarDisplayContext="<%= displayContext %>" />

<clay:container-fluid cssClass="main-content-body mt-3">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <liferay-ui:search-container id="tmsCoursesSC" searchContainer="<%= displayContext.getSearchContainer() %>">
                <liferay-ui:search-container-row className="vn.mekosoft.tms.core.model.TMSCourse" keyProperty="courseId" modelVar="course">
                    <liferay-ui:search-container-column-text name="Mã lớp" property="courseCode" cssClass="font-weight-bold text-primary" />
                    <liferay-ui:search-container-column-text name="Tên khóa học" value="<%= HtmlUtil.escape(course.getCourseTitle()) %>" />
                    <liferay-ui:search-container-column-text name="Sĩ số" value="<%= course.getCurrentEnrollment() + \" / \" + course.getMaxEnrollment() %>" />
                    <liferay-ui:search-container-column-text name="Trạng thái">
                        <span class="badge badge-info"><%= HtmlUtil.escape(course.getStatus()) %></span>
                    </liferay-ui:search-container-column-text>
                    <liferay-ui:search-container-column-jsp align="right" path="/course_actions.jsp" />
                </liferay-ui:search-container-row>
                <liferay-ui:search-iterator markupView="lexicon" />
            </liferay-ui:search-container>
        </div>
    </div>
</clay:container-fluid>
```

---

## 3. Khung Chuẩn React Portlet (`MVC Shell bọc React + TypeScript`)

- Đóng gói chung thành **1 OSGi JAR duy nhất** (Vite build ra `META-INF/resources/dist/app.js` và `app.css`), khai báo trong `@Component` Portlet:
  - `"com.liferay.portlet.header-portlet-css=/dist/app.css"`
  - `"com.liferay.portlet.footer-portlet-javascript=/dist/app.js"`
- **Trong `view.jsp` truyền `LiferayBridgeContext`:**
```jsp
<%@ include file="/init.jsp" %>
<portlet:resourceURL id="/tms/enrollment/catalog" var="catalogURL" />
<portlet:resourceURL id="/tms/enrollment/import-excel" var="importExcelURL" />
<% String mountId = renderResponse.getNamespace() + "reactRoot"; %>

<div id="<%= mountId %>" class="meko-react-portlet-shell"></div>
<script>
window.MekoTmsApps && window.MekoTmsApps.mountEnrollmentApp({
    mountId: '<%= mountId %>',
    namespace: '<portlet:namespace />',
    companyId: <%= themeDisplay.getCompanyId() %>,
    groupId: <%= themeDisplay.getScopeGroupId() %>,
    userId: <%= themeDisplay.getUserId() %>,
    isSignedIn: <%= themeDisplay.isSignedIn() %>,
    userFullName: '<%= HtmlUtil.escapeJS(user.getFullName()) %>',
    userEmail: '<%= HtmlUtil.escapeJS(user.getEmailAddress()) %>',
    locale: '<%= themeDisplay.getLanguageId() %>',
    authToken: Liferay.authToken,
    endpoints: { catalog: '<%= catalogURL %>', importExcel: '<%= importExcelURL %>' }
});
</script>
```
- **Quy tắc gọi API từ React (`postLiferayResource`):** Luôn đính kèm `p_auth=${authToken}` và prefix mọi param/FormData key bằng `${namespace}${key}`.
- **Quy chuẩn JSON trả về từ mọi `MVCResourceCommand`:** `{ "success": true, "code": "OK", "message": "...", "data": { ... } }`.
- **Đồng bộ giao diện:** Không dùng CSS framework riêng lệch tông; sử dụng các class Clay/Bootstrap của `meko-portal-theme` (`.sheet`, `.card`, `.table`, `.btn-primary`) và `Liferay.Util.openToast(...)`.

---

## 4. Quy chuẩn Hàm `Finder` & Tầng Service Builder (`SB`)

1. **Cấm gọi chéo tầng:** Portlet/Controller **chỉ được `@Reference *LocalService`**. Tuyệt đối không gọi `*Persistence`, `*Util` (static) hay `DynamicQuery` trực tiếp từ Portlet.
2. **Quy chuẩn `<finder>` trong `service.xml`:**
   - Unique (`unique="true"`): Sinh cặp `findBy*` (ném Exception) và `fetchBy*` (trả `null`).
   - Collection: Luôn đặt `groupId` / khóa ngoại chọn lọc cao lên đầu (`groupId`, `status`) để sinh index DB tối ưu.
   - Không tạo `<finder>` với `LIKE` trong `service.xml`; dùng `DynamicQuery` trong `*LocalServiceImpl`.
3. **6 nhóm hàm chuẩn trong `*LocalServiceImpl.java`:**
   - **Nhóm 1 (Exact Lookup):** `fetchByCourseCode(code)` / `getByCourseCode(code)`.
   - **Nhóm 2 (Filter Finder + Phân trang):** `getByStatus(groupId, status, start, end)` & `countByStatus(groupId, status)`.
   - **Nhóm 3 (Dynamic Search cho `search-container`):** `searchCourses(groupId, keywords, status, start, end, obc)` & `countSearchCourses(...)` — dùng `RestrictionsFactoryUtil.ilike(...)` (bắt buộc cho PostgreSQL 17 tìm không phân biệt hoa/thường).
   - **Nhóm 4 (Write / Chuyển trạng thái `@Transactional`):** `addCourse(...)`, `startEnrolling(courseId)`, `markDeployedToMoodle(courseId, moodleId, shortname)`, `approveEnrollment(enrollmentId, approverUserId)`. Không để Portlet tự `setStatus()` rồi `update()`.
   - **Nhóm 5 (Custom SQL `*FinderImpl`):** Chỉ dùng khi cần `JOIN` nhiều bảng, bind tham số `?`, không nối chuỗi SQL.
   - **Nhóm 6 (Postgres 17 Hints):** Mọi trường dài (`lomPayloadJson`, `courseDescription`, `lastSyncError`, `requestPayload`, `responseBody`) khai báo `max-length = 2000000` trong `portlet-model-hints.xml`.

---

## 5. Quy chuẩn Moodle Plugin & External API Stub (`MEKO-LMS`)

1. **Đọc Custom Fields đúng chuẩn API:** Không query thẳng `mdl_customfield_data.charvalue` (vì kiểu `select` lưu index ở `intvalue`). Dùng `\core_course\customfield\course_handler::create()->get_instance_data($courseid)` để lấy đúng giá trị hiển thị.
2. **Tạo nhanh API Schema & Mock trước khi code DB (Task `MEKO-API-00`):**
   - Tạo class `classes/external/<function>.php` kế thừa `\core_external\external_api` với đầy đủ `execute_parameters()` và `execute_returns()`.
   - Đăng ký trong `db/services.php`. Moodle tự render schema tài liệu live tại `/lms/admin/webservice/documentation.php` ngay từ giờ đầu tiên để team Liferay đối chiếu và test với stub/mock.

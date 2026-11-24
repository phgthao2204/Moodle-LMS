# HƯỚNG DẪN BẮT ĐẦU NHANH CHO DEV (`MEKO-DLE`)

Tài liệu này cung cấp đúng các thông tin Dev cần biết để cài đặt môi trường local và bắt đầu lập trình cho hệ sinh thái **MEKO-LMS (Moodle 5.3)** và **MEKO-TMS (Liferay 7.4 GA132 CE)**.

---

## 1. Thứ tự Đọc Tài liệu (Dành cho Dev)

0. **`00_SYSTEM_OVERVIEW.md`** *(Đọc trước 3 phút)* — Kiến trúc tổng thể 2 bên, thống kê 4 Module LMS & 7 Module TMS, và 3 Luồng nghiệp vụ thực tế (Quản trị làm gì → Sinh viên thấy gì).
1. **`01_DEV_QUICKSTART.md`** *(File này)* — Domain, Port, DB, Repo `meko-dle`, Quy chuẩn đặt tên & UI.
2. **`02_DATA_FLOW_SB_AND_API.md`** — Luồng đồng bộ chi tiết từng trường dữ liệu thật Moodle ↔ Liferay, **4 Form nhập liệu**, đặc tả **Import/Export `.imscc`**, `service.xml` (**10 bảng đủ Audit**), 2 mẫu Email `.ftl` & bảng **20 APIs/Commands** đủ Input/Output.
3. **`03_CODING_PLAYBOOK.md`** — Khung code mẫu MVC Portlet (Taglib), React Portlet (`LiferayBridgeContext`), hàm `Finder` Service Builder & Moodle External API Stub.
4. **`04_DEV_TASKS_AND_MOCKUPS.md`** — Tổng quan Phase, bảng 14 Task chuẩn OpenProject & bản đồ 15 Mockup chuẩn chia theo Phân hệ & Vai trò.
5. **`05_GIT_DEPLOY_AND_CICD.md`** — Cấu trúc Repo `meko-dle`, quy trình Commit/Push/PR, CI/CD & Deploy Staging.

*(File `PM_PLAN.md` là lịch điều phối Sprint 1 tuần dành cho Lead/PM).*

---

## 2. Môi trường Dev Local (`meko.localhost`)

Domain `.localhost` (RFC 6761) được Chrome/Edge/Firefox tự động phân giải về `127.0.0.1` (**không cần sửa `hosts`, không cần quyền Admin** và tránh bị DNS-over-HTTPS đẩy ra domain thật `mekosoft.vn`). Nếu máy dev có IIS/XAMPP/Laragon chiếm port `80`, `setup.ps1` sẽ tự phát hiện và hướng dẫn đổi `HTTP_PORT=8090` trong `.env` (truy cập qua `http://meko.localhost:8090/`).

### Danh sách Container & URL Truy cập

| Container | Image | URL / Port | Vai trò |
| :--- | :--- | :--- | :--- |
| **`devlocal-gateway`** | `nginx:alpine` | `http://meko.localhost` (`80:80`) | Reverse Proxy định tuyến `/`, `/tms`, `/lms`, `/mail`, `/dev-info` |
| **`devlocal-postgres`** | `postgres:17-alpine` | Host `5433` → Container `5432` | DB dùng chung chứa 2 database riêng: **`lportal`** (Liferay) & **`accees_lms`** (Moodle) |
| **`devlocal-liferay`** | `liferay/portal:7.4.3.132-ga132` | `/`, `/tms` (Nội bộ `8080`, Gogo `11311`) | CMS tại `/` (kèm thông tin DevLocal) + Phân hệ TMS tại `/tms` |
| **`devlocal-moodle`** | `elestio/moodle:v5.3.0` | `/lms` (Nội bộ `80`) | Phân hệ LMS Moodle 5.3 + Theme `meko` + Dữ liệu thật 100% |
| **`devlocal-mailpit`** | `axllent/mailpit:latest` | `/mail` (SMTP nội bộ `1025`, Web `8025`) | Hộp thư giả lập bắt 100% email xác nhận ghi danh & cấp chứng chỉ khi dev test |

---

## 3. Quy hoạch Repo & Tên Module Chuẩn

### 3.1. Cấu trúc Monorepo GitHub: `meko-dle`
- **Repository chính:** `https://github.com/huyriddle/meko-dle.git` *(Nhánh mặc định `develop`, xem chi tiết trong `05_GIT_DEPLOY_AND_CICD.md`)*
  - `meko-devlocal/`: Bộ Docker Compose + Nginx + Postgres 17 + Script seed dữ liệu thật cho Dev kéo về chạy 1-click.
  - `meko-lms/`: Theme Moodle chuẩn hóa (`theme_meko`, profile `meko`) & cụm plugin (`local_meko_competencies`, `local_meko_imscp`, `local_meko_sync_api`, `repository_meko_dlrp`).
  - `meko-tms/`: Liferay Workspace 7.4 GA132 (`meko-portal-theme`, `tms-core`, `tms-integration`, `tms-async`, `tms-course-admin-web`, `tms-enrollment-admin-web`, `tms-profile-web`, `tms-rest`).
  - `meko-samples/`: Bộ sinh file `.imscc` mẫu, `.xlsx` mẫu, đề thi Aiken mẫu, JSON mock & script test (`pytest` / `Playwright`).
  - `docs/`: Bộ tài liệu kỹ thuật & 15 Mockups HTML chuẩn hóa (`index.html`).

### 3.2. Quy chuẩn Đặt tên Code (Liferay OSGi 7 tầng & Moodle Frankenstyle)

| Thành phần | Quy chuẩn đặt tên | Ví dụ chính thức |
| :--- | :--- | :--- |
| **Git Path (TMS)** | `modules/<subsystem>-<type>/` | `modules/tms-core/`, `modules/tms-course-admin-web/` |
| **Gradle Project** | `:modules:<module-name>` | `:modules:tms-core:tms-core-service` |
| **OSGi BSN** | `vn.mekosoft.tms.<subsystem>.<layer>` | `vn.mekosoft.tms.core.api`, `vn.mekosoft.tms.core.service` |
| **OSGi Bundle Name** | `MEKOSOFT TMS <Display Name>` | `MEKOSOFT TMS Core Service`, `MEKOSOFT TMS Course Admin Web` |
| **Java Package** | `vn.mekosoft.tms.<subsystem>.*` | `vn.mekosoft.tms.core.service.*`, `vn.mekosoft.tms.course.web.portlet.*` |
| **Portlet ID** | `vn_mekosoft_tms_<name>_portlet` | `vn_mekosoft_tms_course_admin_portlet` |
| **SB Namespace & Bảng** | `<namespace>MEKO_TMS</namespace>` | `tms_course_plan`, `tms_course`, `tms_enrollment`, `tms_student_certificate` |
| **Moodle Plugins** | `<type>_<name>` | `local_meko_competencies`, `local_meko_imscp`, `local_meko_sync_api`, `repository_meko_dlrp` |

---

## 4. Quy chuẩn Giao diện Bắt buộc (Cho cả LMS & TMS)
- **Bảng màu 100% Solid (Cấm tuyệt đối `linear-gradient` / `radial-gradient`):**
  - Primary Blue: `#0000B6` | Accent Green: `#39B500` | Accent Orange: `#EA6400`
- **Phông chữ duy nhất:** `Be Vietnam Pro` (Google Fonts / local).
- **Quy tắc 0% Emoji:** Cấm đưa emoji Unicode vào JSP, Mustache, React, CSS hoặc dữ liệu mẫu. Toàn bộ icon dùng SVG phẳng, Font Awesome 6 Free hoặc Liferay Clay Icons.
- **Khung lưới:** Container tối đa `1320px`, hỗ trợ Responsive Desktop (`1320px`), Tablet (`768px`), Mobile (`375px`).
- **0% Hardcode:** Tuyệt đối không ghi cứng IP, mật khẩu, API Token, tên khóa học hoặc URL môi trường vào code PHP/Java/JS.

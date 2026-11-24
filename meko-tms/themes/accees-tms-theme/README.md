# ACCEES TMS theme

Liferay Portal 7.4 GA132, nhận diện ACCEES + Đại học Cần Thơ, bố cục các mockup TMS `07`–`15`. Một WAR dùng cho cổng học viên và sidebar quản trị.

## Build và cài đặt

Yêu cầu JDK 17, Node.js 22 trở lên. Workspace dùng plugin `17.1.11`, Gradle `8.9`, target `portal-7.4-ga132`. Từ `meko-tms`:

```powershell
.\gradlew.bat clean build --no-daemon
.\themes\accees-tms-theme\deploy.ps1
```

Trên Linux/macOS:

```sh
./gradlew clean build --no-daemon
cp themes/accees-tms-theme/build/libs/accees-tms-theme.war ../meko-devlocal/liferay/deploy/
# Sau khi WAR được Liferay deploy xong:
cp modules/tms-site-initializer/build/libs/tms-site-initializer.jar ../meko-devlocal/liferay/deploy/
```

Artifact: `build/libs/accees-tms-theme.war`. Bộ khởi tạo OSGi gán theme cho site `/tms`, tạo các trang còn thiếu và thiết lập quyền. Site TMS phải tồn tại trước khi cài; bộ DevLocal hiện có tạo site này. Tài khoản thử nghiệm đã có không bị thay mật khẩu hoặc quyền xác minh email.

Root build giữ `com.liferay.workspace`, tự nhận các module có `bnd.bnd`. Theme là subproject tùy chỉnh trong cùng Workspace. Core API/service được build cùng theme; `compileJava` chạy `buildService` trước khi biên dịch. Generator được ghim `1.0.496` tương ứng GA132. Bộ sinh tạo nguồn và persistence test theo `service.xml`; các test tích hợp cần một bundle kiểm thử riêng, không được xem là đã chạy trong build hiện tại.

Build dùng Classic theme `5.0.105` tương ứng GA132 từ Maven Central. Sass được biên dịch rồi ghép vào CSS nền; tài nguyên font WOFF2 được lưu trong repo, có cả tập ký tự tiếng Việt và giấy phép OFL. Không cần Docker hoặc Python để build và không cần truy cập Google Fonts khi mở trang.

Để LMS local dùng cùng nhận diện, chọn profile ACCEES có sẵn (không sửa mã nguồn Moodle):

```sh
docker exec devlocal-moodle php /var/www/html/admin/cli/cfg.php --component=theme_meko --name=brand_profile --set=accees
docker exec devlocal-moodle php /var/www/html/admin/cli/purge_caches.php
```

## Trang và quyền

| URL sau `/web/tms` | Mockup | Quyền xem |
| --- | --- | --- |
| `/home`, `/courses` | 07 — danh mục, tìm kiếm và lọc khóa học | Guest và các vai trò TMS |
| `/my-profile` | 10 — học tập, huy hiệu, điểm | Các vai trò TMS đã đăng nhập |
| `/verify` | 15 — xác minh chứng chỉ mẫu | Guest và các vai trò TMS |
| `/admin` | 08 — lớp và kế hoạch | Course Manager, Enrollment Officer, Administrator |
| `/admin/enrollments` | 09 — duyệt ghi danh | Enrollment Officer, Administrator |
| `/admin/course-plan` | 13 — biểu mẫu kế hoạch/mở lớp | Course Manager, Administrator |
| `/admin/import-logs` | 14 — lỗi import và nhật ký | Course Manager, Enrollment Officer, Administrator |
| `/admin/email-templates` | 11 — xem mẫu email | Enrollment Officer, Administrator |
| `/admin/email-rejected` | 12 — xem mẫu email từ chối | Enrollment Officer, Administrator |

Gán các Site Role `TMS Student`, `Lecturer`, `Course Manager`, `Enrollment Officer` trong site TMS; quản trị hệ thống dùng Regular Role `Administrator`. Bộ khởi tạo tạo role còn thiếu, không tự gán role hoặc thêm người dùng. Sidebar lấy quyền xem trang từ navigation của Liferay, không chỉ kiểm tra Company Admin. Đây là quyền xem trang/theme; portlet nghiệp vụ vẫn phải kiểm tra quyền Action/Resource riêng khi tích hợp.

Đăng nhập từ TMS dùng tham số `redirect=/web/tms/home`; người có quyền vào quản trị qua nút Quản trị. Đăng xuất từ header TMS gửi `referer=/tms` qua cơ chế kiểm tra URL của Liferay. Không ghi giá trị mặc định đăng nhập/đăng xuất toàn Company. Migration chỉ reset cặp giá trị cũ `/web/tms/entry` + `/tms` nếu khớp cả hai; không đụng cấu hình tùy chỉnh khác. Các trang `/student/*` và `/entry` có sẵn từ bản demo cũ không bị xóa dữ liệu, nhưng không được tạo hoặc liên kết trong cài đặt mới. Gateway giữ nguyên bản `develop`: `/tms` chuyển đến `/web/tms`; không thêm alias hạ tầng trong PR theme.

Các trang mới dùng `layout-template-id=1_column`; initializer đóng gói OSGi bằng bnd, có import lifecycle `com.liferay.portal.kernel.module.framework`.

## Dữ liệu minh họa

`fixtures/pages.json` lưu snapshot nội dung và dữ liệu từ các mockup, tách khỏi logic theme. Các liên kết `.html` được đổi thành URL thật của site ở bước build. Tham số theme `lms-url` mặc định `/lms`, dùng để liên kết sang LMS cùng môi trường; chỉnh trong Look and Feel khi LMS có địa chỉ khác.

Tất cả liên kết sang LMS dùng `data-senna-off="true"` để tải tài liệu Moodle đầy đủ. Điều hướng SPA của Liferay không áp dụng cho Moodle vì sẽ làm mất stylesheet của trang đích.

`demo-mode` mặc định **false**: render nội dung và portlet thật của trang, không nạp `demo.js`. Chỉ bật trong Site Builder → Pages → Configure → Look and Feel → Theme Settings khi nghiệm thu mockup; tắt trước khi ghép portlet. Với site đã từng lưu giá trị true, cần bỏ chọn và lưu lại vì setting đã lưu có ưu tiên hơn mặc định theme. Các trang mẫu có nhãn minh họa; thao tác lưu/đồng bộ/gửi email chỉ hiện thông báo tại trình duyệt. Icon SVG được ánh xạ theo tên và chức năng; icon chưa có ánh xạ làm build báo lỗi để tránh hiển thị sai.

Mã chứng chỉ mẫu theo mockup: `MEKO-2026-00018`. Trang hồ sơ có nhân vật mẫu Nguyễn Văn An, không phải hồ sơ thật của tài khoản đăng nhập.

## Kiểm thử

`gradlew clean build` chạy kiểm tra WAR, logo, font, màu, 1320px, fixture và quy tắc không emoji/gradient. Kiểm thử local sau khi deploy:

```powershell
$env:LIFERAY_TEST_URL = 'http://meko.localhost'
npm --prefix themes/accees-tms-theme run test:live
```

Để kiểm tra thêm đăng nhập, quyền từng trang và đăng xuất, cung cấp các cặp biến `TMS_ADMIN_EMAIL/PASSWORD`, `TMS_STUDENT_EMAIL/PASSWORD`, `TMS_LECTURER_EMAIL/PASSWORD`, `TMS_COURSE_MANAGER_EMAIL/PASSWORD`, `TMS_ENROLLMENT_OFFICER_EMAIL/PASSWORD` của tài khoản local. Test bỏ qua role chưa cung cấp tài khoản. Đặt `TMS_DEMO_MODE=true` chỉ khi site kiểm thử đã chủ động bật demo. Không lưu mật khẩu vào repo.

CI `.github/workflows/ci-tms.yml` setup Node 22 + Java 17, chạy clean build toàn Workspace, lưu WAR và JAR của tất cả module cùng kết quả test. Trạng thái GitHub Actions chỉ xác nhận được sau khi push/PR.

## Nguồn nhận diện

- Logo ACCEES và tài trợ EU: tài nguyên hiện có trong `meko-lms/theme_meko/pix/`.
- Logo CTU: cổng dự án chính thức, `https://accees.ctu.edu.vn/documents/d/accees-project-2026/ctu-1-png`.
- Thông tin Erasmus+ CBHE `101179860`: [cổng ACCEES](https://accees.ctu.edu.vn/).

`_clay_variables.scss` giữ bảng màu, font và container; `_custom.scss` áp dụng cho thành phần theme. CSS fixture được scope trong `.tms-demo` để không ảnh hưởng control panel Liferay.

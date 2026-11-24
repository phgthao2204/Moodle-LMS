# Moodle-LMS

Source Moodle LMS và các thành phần tích hợp TMS trong hệ sinh thái MEKO-DLE, được lưu tại tài khoản phgthao2204 để trình bày dự án và tham khảo mã nguồn.

## Nguồn gốc và phạm vi

Bản source này dựa trên [huyriddle/meko-dle](https://github.com/huyriddle/meko-dle), snapshot từ commit `71d1907`, kèm plugin `local_hello_thao` và module `tms-bootstrap` có trong workspace. Đây là dự án có nhiều thành phần/đóng góp; repository này không tuyên bố toàn bộ hệ thống do chủ tài khoản tự viết. Các thông báo bản quyền trong source được giữ nguyên.

## Thành phần

- `meko-lms/`: theme Moodle, plugin năng lực, API đồng bộ, import/export IMS và plugin Hello Thao.
- `meko-tms/`: workspace Liferay cùng các module và theme TMS.
- `meko-devlocal/`: Docker Compose, PostgreSQL, Nginx, Mailpit và script khởi tạo.
- `meko-samples/`: dữ liệu mẫu, hướng dẫn API và kiểm thử Playwright.
- `docs/`: kiến trúc và các mockup HTML có thể xem trên web.

## Demo giao diện trên GitHub Pages

Repo có sẵn các mockup HTML. Đây là bản xem giao diện tĩnh; thao tác API, đăng nhập và dữ liệu Moodle cần môi trường backend chạy riêng.

1. Mở repository trên GitHub, chọn **Settings > Pages**.
2. Trong **Build and deployment**, chọn **Deploy from a branch**.
3. Chọn branch **main**, thư mục **/docs**, rồi **Save**.
4. Chờ deployment hoàn tất; dùng URL GitHub hiển thị tại trang Pages.

URL dự kiến sau khi bật Pages: https://phgthao2204.github.io/Moodle-LMS/

Xem nhanh màn hình LMS: https://phgthao2204.github.io/Moodle-LMS/mockups/01_moodle_lms_homepage.html

## Chạy Moodle thật trên máy

Cài và mở Docker Desktop trước. Sau đó chạy trong PowerShell:

```powershell
git clone https://github.com/phgthao2204/Moodle-LMS.git
cd Moodle-LMS/meko-devlocal
.\setup.ps1 -MoodleOnly
```

Mở http://meko.localhost/lms/ khi setup hoàn tất. Tài khoản demo mặc định: `admin` / `AdminMoodle2026!@#`.

Muốn khởi tạo thêm Liferay, dùng `.\setup.ps1` và đọc [hướng dẫn DevLocal](meko-devlocal/README.md). Các module TMS vẫn cần build/deploy theo tài liệu thành phần. Plugin `hello_thao` cần được cài riêng theo [README của plugin](meko-lms/local/hello_thao/README.md).

Các mật khẩu có sẵn là giá trị dành cho local demo. Không dùng cấu hình này trực tiếp cho server public. Bản đóng gói này chưa được xác minh bằng lần chạy Docker từ database rỗng; môi trường Docker ở máy chuẩn bị source chưa hoạt động.

## Tài liệu và kiểm thử

- [Cổng tài liệu và mockup](docs/index.html)
- [Hướng dẫn kiểm thử Playwright](meko-samples/tests/playwright/README.md)
- [Tài liệu gốc](docs/UPSTREAM_README.md)

Các OAuth secret/token cụ thể trong hai tài liệu mẫu import/export đã được thay bằng placeholder. Không bao gồm `.env`, cấu hình Moodle local, browser profile, database dump hoặc báo cáo kiểm thử local.

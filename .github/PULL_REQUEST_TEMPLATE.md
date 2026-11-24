## 1. Thông tin Task & Phạm vi Phiên bản
- **Mã Work Package OpenProject:** `#XXXX`
- **Phân hệ / Module:** [ ] `local_accees_imscp`  [ ] `local_accees_sync_api`  [ ] `DevLocal / Docs`
- **Mã Use Case:** `UC-LMS-XX`
- **Phiên bản API áp dụng:** [ ] `v1.0`  [ ] `v1.1`
- **Phạm vi đã thực hiện vs giữ interface cho v1.1:**
  - Đã chạy thật ở v1.0:
  - Giữ qua `LomMetadataInterface` / tham số cho v1.1 (nếu có):

## 2. Bộ Bàn giao `a - b - c` (Bắt buộc trước khi Khoa review)

### (a) Dữ liệu test đã dùng (Test Data & Setup)
- **Nguồn dữ liệu:** [ ] Dùng sẵn `seed_base.php`  [ ] Có bổ sung dữ liệu riêng
- **Thông tin tài khoản / khóa học / client test:**
  - `course_shortname`:
  - `username` / `role`:
  - `oauth2_client_id` (hoặc `accees_service` PAT khi debug):
- **File mẫu & Cách dựng lại (nếu có thêm dữ liệu riêng):**
  - Đường dẫn fixture trong `meko-samples/fixtures/uc-lms-xx/`:
  - Lệnh CLI / bước thao tác để Khoa tái lập trên máy sạch:

### (b) Cách gọi API & Bằng chứng Kiểm thử Pass (Evidence)
- **Lệnh `curl` (OAuth 2.0 Client Credentials Bearer Token) cho Happy Path `200/201`:**
  ```bash
  # Dán lệnh curl test thành công tại đây
  ```
- **Các case lỗi đã tự test (`400`, `401`, `403`, `404`, `409`, `422`):**
  - Case 1:
  - Case 2:
- **JSON Response thực tế chụp từ `http://meko.localhost/lms/api/rest/v2/...`:**
  ```json
  {}
  ```
- [ ] Đã kiểm tra endpoint mới hiển thị đúng schema tại `http://meko.localhost/lms/api/rest/v2/openapi.json`

### (c) Tác động Hệ thống & Lưu ý Tích hợp (System Notes)
- [ ] Có thêm Capability mới trong `db/access.php` (Cần chạy `admin/cli/upgrade.php`)
- [ ] Có thêm Bảng DB / Setting / Event Observer mới
- [ ] Đã kiểm tra: 0% Emoji, 0% CSS Gradient, không hardcode IP/token/`dev3`/`dev5`
- **Ghi chú tồn đọng / giả định (nếu có):**

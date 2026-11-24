# ACCEES LMS REST API v2 - Dữ liệu Mẫu & Bàn giao (`Fixtures`)

Thư mục này chứa dữ liệu mẫu và payload test (mục `a` & `b` trong bộ bàn giao PR `a - b - c`) cho từng API:
- `uc-lms-08/`: File `.imscc` mẫu, request upload, response `201` (Thiện)
- `uc-lms-09/`: Request export, expected manifest/metadata response (Thiện)
- `uc-lms-10/`: Query filters mẫu và response danh sách/chi tiết khóa học (Thảo)
- `uc-lms-11/`: Payload cập nhật khóa học + 5 core custom fields `accees_*` (Thảo)
- `uc-lms-12/`: Payload enrol `v1.0` (tự tạo user) & `v1.1` (`strict_sso=true`) (Yến)
- `uc-lms-13/`: Payload/query unenrol & response `200`/`404` (Yến)
- `uc-lms-14/`: Results `GET` response & mock Webhook payload (Yến)

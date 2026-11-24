# meko-samples

Thư mục dùng chung cho toàn bộ team Dev & QA để chạy song song không bị phụ thuộc chéo:

- `api-contracts/meko-dle-openapi.yaml`: OpenAPI 3.0.3 Spec đầy đủ cho các API của `MEKO-LMS` và `MEKO-TMS` (import thẳng vào Swagger Editor, Apidog hoặc Postman).
- `api-contracts/meko-dle.postman_collection.json`: Bộ Postman Collection dựng sẵn cho `http://meko.localhost` và Mock Server `http://127.0.0.1:8099`.
- `mock-server/mock_api.py`: Mock HTTP Server viết bằng Python built-in (không cần cài thêm package):
  ```bash
  python meko-samples/mock-server/mock_api.py
  # Mở http://127.0.0.1:8099/ để xem danh sách endpoints mock
  ```
- `excel/`: File Excel `.xlsx` mẫu 6 cột dùng test Import học viên (`MEKO-TMS-05`).
- `imscc/`: Gói `.imscc` mẫu & đề thi Aiken dùng test Export/Import (`MEKO-LMS-03`, `MEKO-LMS-04`).
- `tests/`: Bộ kiểm thử tự động `pytest` (API/Webhook/Mailpit) & `Playwright` (UI E2E).

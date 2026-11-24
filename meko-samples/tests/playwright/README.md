# MEKOSOFT LMS REST API v2 — Automated E2E Test Suite (Playwright)

Bộ công cụ kiểm thử tự động End-to-End (E2E) và Integration Testing cho **7 LMS REST API v2 (`UC-LMS-08` -> `UC-LMS-14`) + T0 Discovery** sử dụng **Playwright Test (`APIRequestContext`)**.

Bộ test được thiết kế theo nguyên tắc:
- **Linh động 100% theo môi trường:** Chạy được trên `devlocal` (`http://meko.localhost/lms`), Server Dev/Staging (`http://dev-lms.local/lms`), hoặc Production (`https://lms.example.com`) mà **không sửa bất kỳ dòng code nào**.
- **Chạy độc lập hoặc chuỗi liên hoàn:** Hỗ trợ chạy riêng từng API (`UC-08`..`UC-14`) hoặc chạy chuỗi E2E liên hoàn từ Import -> Read -> Update -> Enrol -> Results -> Unenrol -> Export.
- **Phân nhóm theo tiến độ triển khai:** Dùng tag `@deployed` để chạy nhóm API đã sẵn sàng trên server (UC-08, 09, 10, 11) và full suite 7/7 khi hoàn tất.
- **Báo cáo HTML trực quan:** Tự động xuất Playwright HTML Report sau mỗi lần chạy.

---

## 1. Cài đặt

Yêu cầu: Node.js >= 18 (Khuyến nghị Node.js LTS).

```bash
cd meko-samples/tests/playwright
npm install
```

---

## 2. Cấu hình Môi trường (`.env`)

Sao chép file `.env.example` thành `.env`:

```bash
cp .env.example .env
```

Chỉnh sửa các biến trong file `.env` theo môi trường bạn muốn kiểm thử:

```env
# 1. URL gốc LMS (hỗ trợ cả sub-directory như /lms lẫn domain HTTPS)
# Ví dụ:
#   DevLocal Docker       : http://meko.localhost/lms
#   Remote Dev / Staging  : http://dev-lms.local/lms
#   Production Domain     : https://lms.example.com
LMS_BASE_URL=http://meko.localhost/lms

# 2. OAuth 2.0 Client Credentials (tự động xin Bearer JWT tại POST /oauth2/token)
OAUTH_CLIENT_ID=accees-dev-client
OAUTH_CLIENT_SECRET=your_client_secret_here
OAUTH_SCOPES=local_accees_imscp:imscp:import local_accees_imscp:imscp:export local_accees_sync_api:course:read local_accees_sync_api:course:write local_accees_sync_api:enrol:manage local_accees_sync_api:result:read

# 3. Personal Access Token (PAT) debug (Tùy chọn: dùng khi test devlocal chưa có OAuth2 server)
DEBUG_PAT=

# 4. Phiên bản API ACCEES (1.0 hoặc 1.1)
ACCEES_API_VERSION=1.0

# 5. Bỏ qua lỗi SSL (Hữu ích khi chạy với chứng chỉ tự ký trên môi trường dev/staging)
IGNORE_HTTPS_ERRORS=true

# 6. ID Khóa học mẫu & Danh mục phục vụ kiểm thử
TEST_CATEGORY_ID=2
SAMPLE_COURSE_ID=2
```

> [!WARNING]
> File `.env` chứa secret/token đã được đưa vào `.gitignore`. **Tuyệt đối không commit file `.env` lên GitHub.**

---

## 3. Các Lệnh Chạy Kiểm Thử

### 3.1. Chạy theo nhóm triển khai
```bash
# Chỉ chạy các API đã deploy live trên server (UC-08, 09, 10, 11 + T0):
npm run test:deployed

# Chạy kịch bản E2E liên hoàn toàn bộ vòng đời khóa học:
npm run test:e2e

# Chạy toàn bộ test cases (Happy Path + Error/Negative + Security):
npm test
```

### 3.2. Chạy độc lập từng Use-Case
```bash
# T0 Discovery & OAuth 2.0 Auth
npm run test:t0

# UC-LMS-08: Import gói .imscc
npm run test:uc08

# UC-LMS-10: Xem danh sách, phân trang, lọc & chi tiết khóa học
npm run test:uc10

# UC-LMS-11: Cập nhật thông tin & LOM metadata khóa học
npm run test:uc11

# UC-LMS-12: Ghi danh học viên / giảng viên (Enrol)
npm run test:uc12

# UC-LMS-13: Hủy ghi danh học viên (Unenrol)
npm run test:uc13

# UC-LMS-14: Kết quả học tập & Webhook
npm run test:uc14

# UC-LMS-09: Xuất gói .imscc Common Cartridge
npm run test:uc09
```

---

## 4. Xem Báo Cáo Kiểm Thử (HTML Report)

Sau khi chạy xong, mở báo cáo HTML trực quan bằng lệnh:

```bash
npm run report
```

Báo cáo hiển thị chi tiết:
- Danh sách Test Cases Pass / Fail.
- Thời gian phản hồi của từng API endpoint.
- Chi tiết HTTP Request Header/Body và Response Payload khi có lỗi xảy ra.

---

## 5. Cấu trúc Thư mục

```text
meko-samples/tests/playwright/
├── .env.example              # Template cấu hình môi trường
├── .gitignore                # Bỏ qua node_modules, .env, test-results
├── package.json              # Khai báo dependencies & test scripts
├── playwright.config.ts      # Cấu hình Playwright Test & BaseURL
├── tsconfig.json             # Cấu hình TypeScript
├── src/
│   ├── config.ts             # Module nạp cấu hình linh động & resolve fixtures
│   └── auth-helper.ts        # Quản lý Bearer JWT qua OAuth2 Client Credentials
└── tests/
    ├── t0-discovery-oauth2.spec.ts     # T0: Discovery & Token
    ├── uc-lms-08-import.spec.ts        # UC-08: Import .imscc
    ├── uc-lms-09-export.spec.ts        # UC-09: Export .imscc
    ├── uc-lms-10-courses-read.spec.ts  # UC-10: Read Courses
    ├── uc-lms-11-courses-update.spec.ts# UC-11: Update Course
    ├── uc-lms-12-enrolments.spec.ts    # UC-12: Enrol
    ├── uc-lms-13-unenrol.spec.ts       # UC-13: Unenrol
    ├── uc-lms-14-results-webhook.spec.ts # UC-14: Results & Webhook
    └── e2e-workflow.spec.ts            # Full continuous E2E workflow
```


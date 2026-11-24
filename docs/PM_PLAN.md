# KẾ HOẠCH TRIỂN KHAI SPRINT 1 TUẦN (TỪ CHIỀU NAY T2 16/11 → T6 20/11/2026)

*(Dành cho PM/Tech Lead điều phối đội 6 nhân sự chạy ngay trong tuần này).*

---

## 1. Phân bổ 6 Nhân sự theo Lịch Làm việc Tuần Này

| Cặp ca | Lịch làm việc | Nhân sự & Nhiệm vụ trọng tâm trong 1 tuần |
| :--- | :--- | :--- |
| **Cặp B** *(Chính Liferay)* | **T2, T3, T4, T5** *(Nghỉ T6)* | • **Dev B1 (LR Core & Integration):** Dựng `tms-core` (Service Builder 10 bảng đủ Audit trên Postgres 17) + `MoodleRestClient`/`IdentityProvisioningService` + Queue LMB + Email Mailpit (`ENROLLMENT_APPROVED`/`REJECTED` + `TMSEmailLog`) + Webhook `tms-rest`.<br>• **Dev B2 (LR Theme & Portlets):** Dựng `meko-portal-theme.war` (1 Theme + 2 Layout Top & Left Sidebar) + Portlet Quản lý Lớp, Catalog 1-Click, Import Excel `.xlsx`, Hồ sơ PDF QR & `/tms/verify`. |
| **Cặp C** *(Mix: Infra + API + Deploy)* | **T2, T3, T5, T6** *(Nghỉ T4)* | • **Dev C1 (Infra & Staging):** Dựng `meko-devlocal` (`meko.localhost` 5 container gồm Mailpit), repo `huyriddle/meko-dle` + GitHub Actions, cấu hình RBAC 5 vai trò & Deploy Staging.<br>• **Dev C2 (API Bridge & Mix):** Chốt API Contract/JSON Mock + PHP Stub ngay T2 chiều (`MEKO-API-00`) để LR làm trước, chuẩn bị dữ liệu mẫu (`.xlsx`/Aiken), hỗ trợ glue tích hợp & cùng cặp A chạy QA Staging T6. |
| **Cặp A** *(Chính Moodle & Test)* | **T3, T4, T5, T6** *(Nghỉ T2)* | • **Dev A1 (Moodle WS & IMS CC):** Lập trình Plugin `local_meko_imscp` (Export/Import `.imscc`) + `local_meko_sync_api` (Courses, Enrol Bulk, Results, Webhook `HMAC-SHA256`).<br>• **Dev A2 (Moodle Theme/CBE & QA E2E):** Dọn nốt fallback cũ trong `theme_meko/lib.php` + `settings.php`, cài Khung năng lực + Tiles + CustomCert QR + Seed 12 khóa, viết & chạy Full Test E2E (`pytest` + `Playwright`) tới hết T6. |

---

## 2. Lộ trình Chốt Sản phẩm Theo Từng Ngày (16/11 → 20/11/2026)

| Thời điểm | Có mặt | Đầu việc chốt trong ngày | Sản phẩm bàn giao (Daily Deliverable) |
| :--- | :--- | :--- | :--- |
| **T2 Sáng (16/11)** | `B1, B2, C1, C2` *(A nghỉ)* | • Coi tổng quan kiến trúc, chốt Mockup & Flow (`index.html`).<br>• Setup `meko-devlocal` (5 container) & cấu trúc nhánh trên `huyriddle/meko-dle`. | Máy dev lên thông `/`, `/tms`, `/lms`, `/mail`; Repo có sẵn `main`, `develop`, `staging`. |
| **T2 Chiều (16/11)** | **B1, B2** *(LR)* + **C1, C2** *(Mix)* | • **C2:** Chốt API Contract + JSON Mock + PHP External Stub (`MEKO-API-00`).<br>• **B1:** Viết `service.xml` 10 bảng (đủ Audit) + chạy `buildService` trên Postgres 17 (`MEKO-TMS-02`).<br>• **B2:** Dựng khung `meko-portal-theme.war` (1 Theme + 2 Layout Top Menu & Left Sidebar).<br>• **C1:** Hoàn thiện seeder nền & cấu hình CI/CD `meko-dle`. | Mock API chạy HTTP 200; `tms-core` JAR sinh 10 bảng `tms_*`; Theme LR lên khung `/` và `/tms`; Mailpit chạy. |
| **T3 (17/11)** | **6/6 (Full Team)** | • **B1:** Code `MoodleRestClient`, `IdentityProvisioningService`, LMB Queue & Email `.ftl` qua Mailpit.<br>• **B2:** Dựng Portlet Quản lý Lớp (`MEKO-TMS-04`) + Catalog 1-Click & Import Excel (`MEKO-TMS-05`) chạy với Mock.<br>• **A1:** Code Plugin Export `.imscc` chuẩn IMS CC (`MEKO-LMS-03`).<br>• **A2:** Dọn fallback `theme_meko` (`MEKO-LMS-01`) + Cài DigComp, `format_tiles`, `mod_customcert` + Seed 12 khóa (`MEKO-LMS-02`).<br>• **C1 + C2:** Hỗ trợ cấu hình bridge, sample `.xlsx`/Aiken & review chéo. | `/lms` đủ 12 khóa thật + ảnh thật; Export được file `.imscc` mẫu; Portlets LR chạy mượt trên `tms-core` + Mock API + Mailpit. |
| **T4 (18/11)** | **B1, B2** *(LR)* + **A1, A2** *(Moodle/Test)* *(C nghỉ)* | • **B1 + B2 (LR):** Hoàn thiện Triển khai LMS, Import Excel `.xlsx`, Hồ sơ Học viên + PDF QR + `/tms/verify` & Webhook `tms-rest`.<br>• **A1 (Moodle):** Hoàn thiện WS `create_course`, `get_courses`, `enrol_users_bulk`, `get_learner_results` & Observer bắn Webhook HMAC.<br>• **A2 (Moodle & Test):** Hoàn tất Quiz `gradepass=7.0` + phôi CustomCert QR & viết kịch bản test E2E (`pytest` + `Playwright`). | Đủ bộ API Moodle thật & Portlets Liferay; Có sẵn bộ script test E2E chuẩn bị ghép nối. |
| **T5 (19/11)** | **6/6 (Full Team)**<br>**INTEGRATION GATE & STAGING** | • **Sáng–Đầu chiều:** Toàn đội chuyển từ Mock sang API thật, test thông cả `3` Flow trên `meko.localhost`.<br>• **15:30 — Code Freeze Liferay:** B1/B2 chốt dứt điểm code LR trước khi nghỉ T6.<br>• **16:30 (C1 + C2 + A1):** Cài RBAC 5 vai trò, gom gói `.zip` + `.jar/.war`, merge `develop` → `staging` để **Deploy Staging**. | Tag **`v1.0.0-RC1`** chạy được đủ 3 Flow trên cả DevLocal lẫn Staging. |
| **T6 (20/11)** | **A1, A2** *(Moodle/Test)* + **C1, C2** *(Mix)* *(B nghỉ)*<br>**QA STAGING & RISK BUFFER** | • **Sáng:** **A2 + A1 + C2** chạy bộ test E2E (`pytest` + `Playwright`) kiểm thử toàn diện trên **Staging**.<br>• **Chiều (Risk Buffer 0.5 ngày):** A1/A2/C1/C2 fix dứt điểm lỗi Moodle/cấu hình Staging phát sinh, re-test xác nhận Pass & chốt bàn giao **`v1.0.0`**. | Staging chạy ổn định 100% Pass cho cả 3 Flow; Đóng gói Release Tuần 1. |

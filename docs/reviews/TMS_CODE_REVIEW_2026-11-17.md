# Biên bản Review Code Phân hệ MEKO-TMS (`2026-11-17`)

- **Repository:** `https://github.com/huyriddle/meko-dle.git`
- **Nhánh tích hợp mục tiêu:** `develop` (HEAD tại thời điểm review: `c602024`)
- **Đối tượng review:**
  - **Trần Lê Việt Tiến** (`feat/tms-t0-workspace-theme-init`, `feat/tms-02-accees-tms-core`) — OpenProject `#4373`, `#4376`, `#4377`
  - **Trần Phước Tài** (`feat/tms-01-accees-tms-theme`, PR `#1`) — OpenProject `#4374`, `#4375`

---

## 1. Tổng hợp Cảnh báo Xung đột (Merge Conflicts)

Dù Tiến (`accees-tms-core`) và Tài (`accees-tms-theme`) làm 2 module khác nhau, cả 2 đều tự dựng lại bộ khung gốc của `meko-tms/` và cấu hình CI theo 2 hướng trái ngược nhau (vì `feat/tms-t0` chưa được merge vào `develop` trước khi Tài tách nhánh). Nếu gộp trực tiếp sẽ xung đột tại:

| Tệp dùng chung | Bên Tiến (`feat/tms-t0` / `tms-02`) | Bên Tài (`feat/tms-01`) | Rủi ro |
| :--- | :--- | :--- | :--- |
| `meko-tms/settings.gradle` | Giữ `apply plugin: "com.liferay.workspace"` | Xóa `com.liferay.workspace`, chỉ `include ":themes:accees-tms-theme", ":modules:tms-site-initializer"` | **Blocker:** Làm gãy toàn bộ Service Builder & OSGi Portlets |
| `meko-tms/build.gradle` | `com.liferay.gradle.plugins.workspace:17.1.11` | `plugins { id "base" }` chỉ build 2 folder của Tài | Conflict trực tiếp |
| `meko-tms/gradle/wrapper/*` | Gradle `8.9` | Gradle `8.14.3` | Conflict cả wrapper script & binary JAR |
| `.github/workflows/ci-tms.yml` | Java 17 + `-Dorg.gradle.java.home=$JAVA_HOME` | Java 17 + chỉ upload `accees-tms-theme.war` & `tms-site-initializer.jar` | Mất artifact kiểm thử các module khác |
| `.gitignore` | `!**/gradle/wrapper/*.jar` | `!meko-tms/gradle/wrapper/gradle-wrapper.jar` | Conflict cùng vị trí |
| `meko-tms/themes/accees-tms-theme/` | Tạo shell `README.md`, `package.json` (`classic`) | Code hoàn chỉnh + `README.md` + `package.json` (`adm-zip`/`sass`) | Conflict 2 tệp |
| `meko-tms/themes/meko-portal-theme/README.md` | Xóa | Xóa | Cả 2 cùng xóa |

---

## 2. Review chi tiết — Tiến (`feat/tms-t0-workspace-theme-init`, `feat/tms-02-accees-tms-core`)

### Điểm đạt
- Giữ đúng chuẩn Liferay Workspace (`com.liferay.gradle.plugins.workspace:17.1.11`), sinh đủ 10 entity `ACCEES_TMS` (`vn.mekosoft.accees.tms.core`), tách `tms-core-api` và `tms-core-service`.
- Đã cấu hình `portlet-model-hints.xml` (`2000000` -> `TEXT` trên PostgreSQL 17) và `RestrictionsFactoryUtil.ilike` trong `DynamicQuery`.
- Không vi phạm 0% Emoji / 0% Gradient; bổ sung `TMSEmailTemplateSeeder` và script tiện ích `meko-devlocal/gogo.py`.

### Các lỗi & thiếu sót cần khắc phục (`#4373`, `#4376`, `#4377` -> `Test failed`)
1. **Hardcode đường dẫn máy cá nhân (`meko-tms/gradle.properties`):**
   - Tồn tại `org.gradle.java.home=D:/Project_ThucChien_CT511/env_setup/jdk-21` và phải vá ngược vào `ci-tms.yml` (`-Dorg.gradle.java.home=$JAVA_HOME`). Yêu cầu xóa khỏi `gradle.properties`.
2. **Chưa mở PR và thiếu toàn bộ CRUD Tests:**
   - Commit `8dbd90d` ghi `"implement localservice methods, email seeder and crud tests"`, nhưng không có tệp test CRUD nào được commit. Hiện cũng chưa tạo PR vào `develop`.
3. **Lệch `service.xml` và `portlet-model-hints.xml` so với đặc tả `develop` mới nhất (`02_DATA_FLOW_SB_AND_API.md`):**
   - Thiếu `id-type="increment"` trên khóa chính các bảng; thiếu `db-name="is_active"` ở `TMSCompetencyFramework` và `TMSEmailTemplate`.
   - `TMSCoursePlan`: thiếu `institution`, `scheduleInfo`, `location`, `lecturerUserId`, `lecturerUsername`, finder `GroupId`.
   - `TMSCourse`: thiếu 15 trường (`courseDescription`, `lomIdentifier`, `imsPackageUrl`, `deliveryFormat`, `creditHours`, `institution`, `scheduleInfo`, `location`, `lecturerUserId`, `lecturerUsername`, `lecturerName`, `lecturerEmail`, `allowSelfEnroll`, `autoApprove`, `moodleCategoryId`), dùng `isFeatured` thay vì `featured` (`db-name="is_featured"`), thiếu 5 finder (`GroupId`, `PlanId`, `Featured`, `MoodleShortname`, `LecturerUserId`).
   - `TMSStudent`: còn dùng `actorUserId`/`actorUserName` thay vì `createdByUserId`/`createdByUserName`, `SsoUsername` chưa `unique="true"`.
   - `TMSEnrollment`: thiếu finder `CourseId`, `EnrollmentCode` chưa `unique="true"`.
   - `TMSStudentCertificate`: thiếu 6 trường (`badgeUniqueHash`, `badgeAssertionUrl`, `badgesJson`, `progressPercent`, `digitalSignatureHash`, `revokedByUserName`), thiếu finder `CourseStudent`, `EnrollmentId` chưa `unique="true"`.
   - `TMSSyncLog`: finder `EventId` trả `Collection` thay vì `TMSSyncLog`; `TMSEmailLog` thiếu `recipientUserId`.
   - `portlet-model-hints.xml`: tăng các cột URL lên `1000` và `courseTitle` lên `500`.
4. **Email template không dấu (`TMSEmailTemplateLocalServiceImpl.java`):**
   - `DEFAULT_APPROVED_BODY`, `DEFAULT_REJECTED_BODY` và subject viết tiếng Việt không dấu (`XAC NHAN GHI DANH THANH CONG`, `Kinh gui Hoc vien...`), sai chuẩn UTF-8 và mẫu trong `02_DATA_FLOW_SB_AND_API.md`.
5. **Race condition khi khởi động (`TMSEmailTemplateSeeder.java`):**
   - Dùng `new Thread(() -> Thread.sleep(3000)...)` trong `@Activate` mà không khai báo `@Reference(target = "(module.service.lifecycle=portal.initialized)")`.
6. **Thiếu logic & xử lý edge-case trong `*LocalServiceImpl`:**
   - `TMSEnrollmentLocalServiceImpl.updateSyncStatus`: không xóa `lastSyncError` khi `syncStatus` chuyển về `"SYNCED"`; `rejectEnrollment` chưa kiểm tra `rejectReason` rỗng; `approveEnrollment` chưa kiểm tra trạng thái `PENDING`/sĩ số; thiếu `createEnrollment`/`selfEnroll`, `markEmailSent`/`markEmailFailed`.
   - `TMSStudentCertificateLocalServiceImpl.upsertCertificateFromResult`: ghi đè thẳng `status = "ISSUED"` ngay cả khi chứng chỉ đã bị `"REVOKED"`.
   - `TMSStudentLocalServiceImpl.getOrCreateStudent`: chưa cập nhật `userId`/`ssoUsername` cho hồ sơ học viên tạo trước từ Excel.
   - `TMSCourseLocalServiceImpl` & `TMSCoursePlanLocalServiceImpl`: thiếu `addCourse`/`addCoursePlan`/`rejectPlan`/`closeCourse`/`incrementEnrollment`, thiếu `OrderByComparator` ở hàm search, và `getCoursesByPlanId` nên dùng finder thay cho `DynamicQuery`.
7. **Sửa file ngoài phạm vi ở `feat/tms-t0` / `feat/tms-02`:**
   - Đã đụng vào `themes/accees-tms-theme/` (`README.md`, `package.json`) và `init_liferay.py`; cần tách sạch khi rebase lên `develop`.

---

## 3. Review chi tiết — Tài (`feat/tms-01-accees-tms-theme`, PR `#1`)

### Điểm đạt
- Đạt chuẩn giao diện `#4374`: bảng màu Solid (`#0000B6` / `#39B500` / `#EA6400`), 0% Emoji, 0% Gradient, container `1320px`, nhúng font `Be Vietnam Pro` WOFF2 đầy đủ ký tự tiếng Việt (offline, kèm `OFL.txt`), logo ACCEES + CTU + `eu_cofunded_white.png` + thông tin Erasmus+ `101179860`.
- Đóng gói 1 WAR duy nhất (`accees-tms-theme.war`) cho 2 không gian (`/tms` top navbar và `/tms/admin` left sidebar `250px`).
- Toàn bộ liên kết sang `/lms/` có `data-senna-off="true"` tránh xung đột SPA của Liferay.
- Có sẵn bộ test `tests/build.test.mjs` và `scripts/verify-live.mjs` kiểm tra WAR, font, màu, emoji, gradient và route phân quyền.

### Các lỗi & blocker cần khắc phục (`#4374`, `#4375` -> `Test failed`)
1. **BLOCKER — Phá cấu trúc Liferay Workspace (`meko-tms/settings.gradle`, `meko-tms/build.gradle`, `ci-tms.yml`):**
   - Xóa `apply plugin: "com.liferay.workspace"` khỏi `settings.gradle` và đổi `build.gradle` sang `plugins { id "base" }` chỉ build 2 project của theme; đồng thời sửa `ci-tms.yml` chỉ upload `accees-tms-theme.war` + `tms-site-initializer.jar`.
   - Yêu cầu khôi phục Liferay Workspace chuẩn để build được đồng thời `accees-tms-core` và các OSGi Portlets sau này.
2. **Thiếu cấu hình Node trong CI & thiếu `Import-Package` OSGi trong `MANIFEST.MF`:**
   - `package.json` yêu cầu `"node": ">=22"` nhưng `ci-tms.yml` không khai báo bước `actions/setup-node`.
   - `meko-tms/modules/tms-site-initializer/MANIFEST.MF` viết tay thiếu `com.liferay.portal.kernel.module.framework` dù `OSGI-INF/setup.xml` tham chiếu `ModuleServiceLifecycle`. Khuyến nghị chuyển sang build chuẩn Workspace + `bnd.bnd`.
3. **Nhét toàn bộ Mockup HTML vào Theme với `demo-mode = true` mặc định:**
   - 9 màn hình mockup (`fixtures/pages.json` -> `templates/demo/*.ftl` + `demo.js`) được render trực tiếp trong `portal_normal.ftl` với `demo-mode = true` mặc định (biến cả 2 mẫu email thành trang `/admin/email-templates`, `/admin/email-rejected`). Cần tắt `demo-mode` mặc định hoặc tách rõ khi ghép Portlet thật.
   - `scripts/build.mjs` đang replace toàn bộ thẻ `<i ...></i>` bằng đúng 1 SVG hình tờ giấy giống nhau.
   - `SiteInitializer.ensurePage` chưa đặt `layout-template-id = "1_column"` (khi đặt portlet thật sẽ dính layout mặc định 2 cột của Liferay).
4. **Phân quyền RBAC & phạm vi ngoài task `MEKO-TMS-01`:**
   - `portal_normal.ftl` chỉ check `isCompanyAdmin` và `SiteInitializer` chỉ tạo `TMS Student` + `Administrator`, chưa hỗ trợ các vai trò nghiệp vụ (`Course Manager`, `Enrollment Officer`, `Lecturer`) trong RBAC 5 vai trò (`MEKO-TMS-07`).
   - `SiteInitializer` ghi đè `DEFAULT_LANDING_PAGE_PATH` và `DEFAULT_LOGOUT_PAGE_PATH` ở cấp toàn `Company` (ảnh hưởng cả site CMS gốc `/`) và tạo cây URL `/student/*` + `/entry` meta-refresh khác chuẩn `/tms`, `/tms/courses`, `/tms/my-profile`, `/tms/verify`.
   - Sửa `meko-devlocal/gateway/nginx.conf` ngoài phạm vi task `MEKO-TMS-01` (với hành vi `/tms` proxy thẳng nhưng `/tms/` và `/tms/...` lại redirect `302`).

---

## 4. Kế hoạch đồng bộ tiếp theo

1. **Trên `develop`:** Cập nhật `docs/02_DATA_FLOW_SB_AND_API.md`, `docs/02B_API_MASTER_SPEC.md`, `meko-samples/`, khung chuẩn `meko-tms/modules/accees-tms-core/` và biên bản review này.
2. **Tiến (`#4373`, `#4376`, `#4377`):** Pull/rebase từ `develop`, sửa các mục ở phần 2, bổ sung CRUD tests, rồi mở PR vào `develop`.
3. **Tài (`#4374`, `#4375`):** Rebase trên `develop` sau khi giữ nguyên Liferay Workspace, hoàn trả `nginx.conf`, sửa các mục ở phần 3 và cập nhật PR `#1`.

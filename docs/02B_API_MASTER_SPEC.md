# API SPEC TỔNG THỂ, QUY CHUẨN ĐẶT TÊN & BẢNG MAPPING THEO PHÂN HỆ (`MEKO-DLE`)

> **API Spec là gì? (Nói theo ngôn ngữ Dev):** Đây là bản thiết kế chốt trước **Endpoint, Request Schema, Response JSON và Error Code** của toàn bộ **`21` API & Portlet Commands** trong hệ thống `MEKO-DLE`. Team **MEKO-LMS (Moodle)**, **MEKO-TMS (Liferay)** và **QA** nhìn cùng 1 chuẩn này để code song song & chạy Mock Server trước khi ghép code. Nếu quá trình code cần thêm/sửa field, Dev chỉ cần mở PR cập nhật file này cùng với file OpenAPI trong `meko-samples/api-contracts/`.

---

## 1. Quy chuẩn Đặt tên & Định tuyến API Theo Phân hệ

| Phân hệ / Lớp | Giao thức & Base URL Local | Quy chuẩn đặt tên Endpoint / Function | Vị trí File / Class Code trong Repo | Cơ chế Bảo mật & Auth |
| :--- | :--- | :--- | :--- | :--- |
| **1. `MEKO-LMS`** *(Moodle External WS — `6` APIs)* | `POST http://meko.localhost/lms/webservice/rest/server.php` | `wsfunction = local_meko_<plugin>_<dong_tu_danh_tu>`<br>VD: `local_meko_imscp_create_course`, `local_meko_sync_get_courses` | `meko-lms/local/meko_<plugin>/classes/external/<ten_ham>.php` + khai báo `db/services.php` | Tham số `wstoken=<MOODLE_WS_TOKEN>` + quyền Capability tương ứng trên Moodle Service `MEKO_TMS_SYNC` |
| **2. `MEKO-TMS` REST** *(Liferay JAX-RS — `2` APIs)* | `http://meko.localhost/o/meko-tms/api/...` | `/<nhom_tai_nguyen>/<hanh_dong>` (`kebab-case`)<br>VD: `POST /webhooks/badge-awarded`, `GET /certificates/verify` | `meko-tms/modules/tms-rest/src/main/java/vn/mekosoft/tms/rest/application/...` | Webhook: Header `X-MEKO-Signature: sha256=<HMAC_SHA256>`<br>Verify QR: Public Guest (chỉ đọc field công khai) |
| **3. `MEKO-TMS` Portlet** *(`MVCAction` & `MVCResource` — `13` Commands)* | Liferay `actionURL` (`POST` + PRG Redirect) hoặc `resourceURL` (`GET`/`POST` JSON / Binary) | `mvc.command.name = /tms/<doi_tuong>/<hanh_dong>`<br>Class Action: `<Action>MVCActionCommand`<br>Class Resource: `<Action>MVCResourceCommand` | `meko-tms/modules/tms-*-web/src/main/java/vn/mekosoft/tms/*/web/portlet/action/` | Session Liferay + CSRF Token (`p_auth`) + `PermissionChecker` kiểm tra Role trên từng Command |

### Chuẩn JSON Response & Mã lỗi Thống nhất

Mọi API trả JSON lỗi theo đúng 1 cấu trúc duy nhất:

```json
{
  "status": "error",
  "code": "snake_case_error_code",
  "message": "Mô tả lỗi rõ ràng cho Dev / UI",
  "details": {}
}
```

| HTTP Code | Ý nghĩa chuẩn toàn hệ thống | Khi nào trả về |
| :---: | :--- | :--- |
| **`200`** | Thành công | Xử lý xong, trả dữ liệu JSON (hoặc stream file PDF với Download Cert) |
| **`400`** | Dữ liệu đầu vào sai / thiếu | Thiếu field bắt buộc, file `.xlsx`/`.imscc` sai định dạng, thiếu `rejectReason` |
| **`401`** | Chưa đăng nhập hoặc sai chữ ký | Hết session Liferay, sai `wstoken`, hoặc sai HMAC `X-MEKO-Signature` |
| **`403`** | Không đủ quyền (RBAC) | Sinh viên cố gọi API/Command của Cán bộ ĐT / Admin |
| **`404`** | Không tìm thấy tài nguyên | Không tìm thấy `courseId`, `planId`, `enrollmentId`, hoặc `qr_verify_code` |
| **`409`** | Trùng lặp / Sai trạng thái | Trùng `course_code`, hoặc bấm Duyệt đơn không còn ở trạng thái `PENDING` |

---

## 2. Bảng Master Mapping Theo Phân hệ (Task OP <-> Endpoint <-> Code <-> Đầu vào/ra)

### 2.1. Phân hệ `MEKO-LMS` (Moodle 5.3 LTS — `6` Web Services)

> Tất cả 6 API dưới đây đều gọi qua Gateway **`POST /lms/webservice/rest/server.php`** kèm `wstoken`, `moodlewsrestformat=json`, `wsfunction=<tên_hàm>`.

| STT | Mã Task OP | Phụ trách | Tên WS (`wsfunction`) | File PHP Code (`meko-lms/...`) | Yêu cầu & Nhiệm vụ | Input chính | Output chính & Error Codes |
| :---: | :--- | :---: | :--- | :--- | :--- | :--- | :--- |
| **1** | `MEKO-LMS-03` | `Dev A1` | `local_meko_imscp_export_course` | `local/meko_imscp/classes/external/export_course.php` | Đóng gói khóa học Moodle 5.3 thành file `.imscc` chuẩn (Tiles + Page/File + QTI 1.2 + LOM, không export `mdl_user`) | `courseid`, `include_questionbank`, `include_userdata=0` | `{ status, shortname, filename, file_size_bytes, download_url, imscc_sha256, stats }` \| Lỗi: `course_not_found`, `export_permission_denied`, `zip_build_failed` |
| **2** | `MEKO-LMS-04` | `Dev A1` | `local_meko_imscp_create_course` | `local/meko_imscp/classes/external/create_course.php` | Nhận gói `.imscc` (`URL`/`BASE64`), bung Tiles + Page/Resource + Quiz QTI (`gradepass=7.0`), gắn 5 Custom Fields & Competency (Idempotent theo `course_code`) | `course_code`, `course_title`, `category_idnumber`, `startdate`, `enddate`, `imscc_source_type`, `imscc_url`/`imscc_base64`, `lecturer_email`, `competency_idnumbers[]`, `customfields{}` | `{ status, moodle_course_id, shortname, course_url, idempotent_reused, imported_sections, imported_modules, linked_competencies }` \| Lỗi: `invalid_imscc_zip`, `missing_imsmanifest`, `category_not_found` |
| **3** | `MEKO-LMS-05` | `Dev A1` | `local_meko_sync_get_courses` | `local/meko_sync_api/classes/external/get_courses.php` | Trả danh sách khóa học khả dụng (`visible=1` & `accees_course_status=PUBLISHED`) kèm ảnh cover, giảng viên, customfields, competencies | `status`, `category_idnumber`, `modified_since`, `page`, `per_page` | `{ total, page, courses[{ moodle_course_id, shortname, fullname, course_image_url, customfields, teachers, competencies, quiz_gradepass }] }` |
| **4** | `MEKO-LMS-05` | `Dev A1` | `local_meko_sync_update_courses` | `local/meko_sync_api/classes/external/update_courses.php` | Cập nhật `visible`, `accees_course_status` (`PUBLISHED`/`CLOSED`) hoặc `fullname` từ Liferay sang Moodle | `moodle_course_id`, `visible`, `accees_course_status`, `fullname` | `{ status, moodle_course_id, updated_fields[] }` \| Lỗi: `course_not_found` |
| **5** | `MEKO-LMS-05` | `Dev A1` | `local_meko_sync_enrol_users_bulk` | `local/meko_sync_api/classes/external/enrol_users_bulk.php` | Tạo tài khoản Moodle nếu thiếu (`manual` sinh `temp_password` / `oidc` cho SSO) và Enrol tối đa `50` user/lần (`roleid=5`) | `courseid`, `auth_type (manual|oidc)`, `users[{ username, student_code, email, firstname, lastname, organization, roleid }]` | `{ status, enrolled_count, failed_count, results[{ username, email, moodle_userid, temp_password, result }], errors[] }` \| Lỗi: `batch_limit_exceeded` |
| **6** | `MEKO-LMS-05` | `Dev A1` | `local_meko_sync_get_learner_results` | `local/meko_sync_api/classes/external/get_learner_results.php` | Scheduler bên Liferay gọi định kỳ đối soát điểm Quiz, Competency (`proficient`) và Open Badges phòng rớt Webhook | `moodle_course_id`, `moodle_user_ids[]`, `since_timestamp` | `{ moodle_course_id, results[{ moodle_userid, email, course_completed, final_grade, max_grade, competencies[], badges[] }] }` |

*(Ghi chú thêm: Event Observer `\local_meko_sync_api\observer` tại `local/meko_sync_api/classes/observer.php` bắt sự kiện `\core\event\badge_awarded` & `\core\event\course_completed` và bắn thẳng sang API #7 của `MEKO-TMS`.)*

---

### 2.2. Phân hệ `MEKO-TMS` (Liferay 7.4 GA132 — `2` REST + `13` Portlet Commands = `15` Endpoints)

| STT | Mã Task OP | Phụ trách | Tên API / Command | Method & Endpoint / `mvc.command.name` | Module & Class Code (`meko-tms/modules/...`) | Yêu cầu & Nhiệm vụ | Input chính | Output chính & Quyền / Error |
| :---: | :--- | :---: | :--- | :--- | :--- | :--- | :--- | :--- |
| **7** | `MEKO-TMS-06` | `Dev B1` | `WebhookBadgeAwarded` | `POST /o/meko-tms/api/webhooks/badge-awarded` | `tms-rest`: `BadgeWebhookResource.java` | Nhận sự kiện cấp Badge từ Moodle, check chữ ký HMAC-SHA256, lưu `TMSStudentCertificate` + sinh `cert_serial_code`, `qr_verify_code` & PDF DL | Header `X-MEKO-Signature` + Body `{ event, moodle_course_id, course_shortname, moodle_userid, user_email, final_grade, badge{} }` | `200`: `{ status, cert_id, cert_serial_code, qr_verify_code }` \| `401`: `invalid_signature`, `400`: `enrollment_not_found` |
| **8** | `MEKO-TMS-06` | `Dev B1` | `VerifyCertificate` | `GET /o/meko-tms/api/certificates/verify` | `tms-rest`: `CertificateVerifyResource.java` | API công khai cho trang `/tms/verify` & quét mã QR trên PDF để tra cứu chứng chỉ hợp lệ / thu hồi | Query `?code=<qr_verify_code>` | `200`: `{ valid, status, cert_serial_code, student_code, student_name, course_code, course_title, final_grade, badge_name, issued_date, revoke_reason }` \| `404`: `NOT_FOUND` (Public Guest) |
| **9** | `MEKO-TMS-04` | `Dev B2` | `SaveCoursePlanAction` | `POST` Action: `/tms/plan/save` | `tms-course-admin-web`: `SaveCoursePlanMVCActionCommand.java` | Tạo mới / cập nhật Kế hoạch mở khóa (`TMSCoursePlan`, Form 1) | `planId`, `courseTitle`, `lomIdentifier`, `imsPackageUrl`, `deliveryFormat`, `creditHours`, `estimatedLearners`, `expectedStartDate`, `expectedEndDate`, `lecturerName`, `lecturerEmail`, `courseDescription` | Redirect PRG + `SessionMessages(planSaved)` \| Role: `Course Manager / Admin` |
| **10** | `MEKO-TMS-04` | `Dev B2` | `ApproveCoursePlanAction` | `POST` Action: `/tms/plan/approve` | `tms-course-admin-web`: `ApproveCoursePlanMVCActionCommand.java` | Duyệt (`APPROVED`) hoặc trả lại (`REJECTED`) kế hoạch mở khóa | `planId`, `actionType (APPROVE|REJECT)`, `approvalNotes` | Cập nhật cột duyệt (`approvedByUserId`, `approvalDate`) + Redirect PRG \| Role: `Admin / Lãnh đạo ĐT` |
| **11** | `MEKO-TMS-04` | `Dev B2` | `SaveCourseAction` | `POST` Action: `/tms/course/save` | `tms-course-admin-web`: `SaveCourseMVCActionCommand.java` | Tạo / sửa Lớp đào tạo (`TMSCourse`, Form 2) từ Plan đã duyệt & gắn `TMSCourseCompetency[]` | `courseId`, `planId`, `courseCode`, `courseTitle`, `academicYear`, `semester`, `startDate`, `endDate`, `maxEnrollment`, `thumbnailUrl`, `frameworkIds[]`, `status`, `isFeatured` | Redirect PRG + `SessionMessages(courseSaved)` \| `409`: trùng `courseCode` \| Role: `Course Manager` |
| **12** | `MEKO-TMS-04` | `Dev B2` | `DeployToMoodleAction` | `POST` Action: `/tms/course/deploy` | `tms-course-admin-web`: `DeployToMoodleMVCActionCommand.java` | Bấm nút **Triển khai LMS**: gọi `MoodleRestClient` (`API #2`), lưu `moodleCourseId`, bật `IN_PROGRESS` + ghi `TMSSyncLog` | `courseId` | Redirect PRG + `SessionMessages(courseDeployed)` (hoặc `SessionErrors` nếu Moodle lỗi) \| Role: `Course Manager` |
| **13** | `MEKO-TMS-05` | `Dev B2` | `GetCatalogResource` | `GET` Resource: `/tms/catalog/list` | `tms-enrollment-admin-web`: `GetCatalogMVCResourceCommand.java` | Trả JSON danh sách khóa học cho màn hình Catalog `/tms` (kèm cờ `isEnrolledByMe`) | Query `keyword`, `status`, `frameworkCode`, `page`, `pageSize` | `{ status: "ok", total, page, items[{ courseId, courseCode, courseTitle, thumbnailUrl, status, enrolledCount, maxEnrollment, isEnrolledByMe }] }` (Public Guest) |
| **14** | `MEKO-TMS-05` | `Dev B2` | `SelfEnrollResource` | `POST` Resource: `/tms/enroll/self` | `tms-enrollment-admin-web`: `SelfEnrollMVCResourceCommand.java` | Học viên bấm **Đăng ký ngay (1-Click)**: tạo `TMSEnrollment` (`SELF_ENROLL`, `PENDING`) | `courseId` *(user lấy từ `themeDisplay.getUserId()`)* | `{ status: "ok", enrollmentId, enrollmentCode, enrollmentStatus: "PENDING" }` \| `401` nếu Guest, `409` nếu đã đăng ký / hết chỗ |
| **15** | `MEKO-TMS-05` | `Dev B2` | `ImportExcelPreviewResource` | `POST` Multipart Resource: `/tms/enroll/excel-preview` | `tms-enrollment-admin-web`: `ImportExcelPreviewMVCResourceCommand.java` | Đọc file `.xlsx` 6 cột bằng Apache POI, validate trùng/lỗi email/mã SV trước khi lưu | `courseId`, multipart `file (.xlsx)` | `{ status: "ok", totalRows, validCount, errorCount, rows[{ rowNum, studentCode, fullName, email, phone, organization, valid, error }] }` \| Role: `Enrollment Officer` |
| **16** | `MEKO-TMS-05` | `Dev B2` | `ImportExcelConfirmResource` | `POST` Resource: `/tms/enroll/excel-confirm` | `tms-enrollment-admin-web`: `ImportExcelConfirmMVCResourceCommand.java` | Lưu các dòng hợp lệ vào `TMSStudent` + `TMSEnrollment` (`APPROVED`, `PENDING`), đẩy Queue LMB gọi `API #5` + gửi Mailpit | `courseId`, `rowsJson` | `{ status: "ok", imported, queuedBatchId, syncStatus: "PENDING" }` \| Role: `Enrollment Officer` |
| **17** | `MEKO-TMS-05` | `Dev B2` | `ApproveRejectEnrollmentResource` | `POST` Resource: `/tms/enroll/decide` | `tms-enrollment-admin-web`: `ApproveRejectEnrollmentMVCResourceCommand.java` | Duyệt (đẩy Queue LMB + gửi mail `ENROLLMENT_APPROVED`) hoặc Từ chối (lưu lý do + gửi mail `ENROLLMENT_REJECTED`) | `enrollmentIds[]`, `decision (APPROVE|REJECT)`, `rejectReason` *(bắt buộc khi `REJECT`)* | `{ status: "ok", processedCount, decision, emailQueued: true }` \| `400` nếu từ chối mà thiếu `rejectReason` \| Role: `Enrollment Officer` |
| **18** | `MEKO-TMS-05` | `Dev B2` | `ResendEmailResource` | `POST` Resource: `/tms/logs/email-resend` | `tms-enrollment-admin-web`: `ResendEmailMVCResourceCommand.java` | Kích hoạt gửi lại email bị lỗi hoặc theo yêu cầu của Cán bộ từ bảng `TMSEmailLog` | `emailLogId` | `{ status: "ok", oldEmailLogId, newEmailLogId, mailStatus: "SENT" }` \| `404` nếu không thấy `emailLogId` \| Role: `Enrollment Officer` |
| **19** | `MEKO-TMS-05` | `Dev B2` | `GetLogsResource` | `GET` Resource: `/tms/logs/list` | `tms-enrollment-admin-web`: `GetLogsMVCResourceCommand.java` | Trả lịch sử đồng bộ `TMSSyncLog` hoặc lịch sử email `TMSEmailLog` theo lớp/trạng thái | `logType (SYNC|EMAIL)`, `courseId`, `status`, `page`, `pageSize` | `{ status: "ok", total, items[] }` \| Role: `Enrollment Officer / Admin` |
| **20** | `MEKO-TMS-06` | `Dev B2` | `GetMyProfileResource` | `GET` Resource: `/tms/profile/summary` | `tms-profile-web`: `GetMyProfileMVCResourceCommand.java` | Trả tổng hợp hồ sơ học viên, lớp đã ghi danh, tiến độ năng lực Radar Chart & chứng chỉ đã cấp | *(Lấy từ `themeDisplay.getUserId()`)* | `{ status: "ok", student{}, enrollments[], competencyProgress[], certificates[] }` \| `401` nếu chưa login |
| **21** | `MEKO-TMS-06` | `Dev B2` | `DownloadCertificatePdfResource` | `GET` Resource: `/tms/profile/cert-pdf` | `tms-profile-web`: `DownloadCertificatePdfMVCResourceCommand.java` | Kiểm tra quyền sở hữu chứng chỉ và stream file PDF có QR Code từ Liferay Document Library | `certId` | Stream binary `Content-Type: application/pdf` \| `403` nếu tải cert của người khác, `404` nếu không có PDF |

---

## 3. Chi tiết Request / Response Mẫu Từng Endpoint

### 3.1. Nhóm 1: `6` Moodle External Web Services (`/lms/webservice/rest/server.php`)

Cách gọi HTTP REST chuẩn từ `MoodleRestClient` (Java) hoặc `curl` / `Postman`:

```bash
curl -X POST "http://meko.localhost/lms/webservice/rest/server.php" \
  -d "wstoken=YOUR_MOODLE_TOKEN" \
  -d "moodlewsrestformat=json" \
  -d "wsfunction=local_meko_sync_get_courses" \
  -d "status=PUBLISHED" -d "page=1" -d "per_page=50"
```

#### API #1 — `local_meko_imscp_export_course` (`MEKO-LMS-03`)
- **Request:** `{ "courseid": 8, "include_questionbank": 1, "include_userdata": 0 }`
- **Response `200 OK`:**
  ```json
  {
    "status": "success",
    "shortname": "NLS-01-IMS",
    "filename": "NLS-01-IMS-20261116.imscc",
    "file_size_bytes": 1842310,
    "download_url": "http://meko.localhost/lms/webservice/pluginfile.php/1/local_meko_imscp/export/NLS-01-IMS-20261116.imscc",
    "imscc_sha256": "e3b0c44298fc1c149afbf4c8996fb924",
    "stats": { "sections": 4, "pages": 6, "resources": 4, "quizzes": 1, "questions": 10 }
  }
  ```
- **Errors:** `course_not_found`, `export_permission_denied`, `zip_build_failed`.

#### API #2 — `local_meko_imscp_create_course` (`MEKO-LMS-04`)
- **Request:**
  ```json
  {
    "course_code": "MK-2026-01",
    "course_title": "Đóng gói Học liệu Chuẩn IMS CP",
    "summary": "Khóa học thực hành IMS CC & QTI XML",
    "category_idnumber": "DIGCOMP_STU",
    "startdate": 1795132800,
    "enddate": 1797724800,
    "imscc_source_type": "URL",
    "imscc_url": "http://devlocal-gateway/documents/ims/nls01-ims.imscc",
    "imscc_base64": "",
    "thumbnail_url": "http://devlocal-gateway/documents/covers/course_01.jpg",
    "lecturer_email": "dien.tran@mekosoft.vn",
    "lecturer_fullname": "PGS.TS. Trần Thanh Điền",
    "competency_idnumbers": ["SV_C1_1_N1", "SV_C1_3_N1"],
    "customfields": {
      "accees_course_status": "PUBLISHED",
      "accees_delivery_mode": "Blended Learning",
      "accees_duration": "3 Credits · 60 Hours",
      "accees_institution": "Mekosoft Training Hub",
      "accees_is_featured": true
    }
  }
  ```
- **Response `200 OK`:**
  ```json
  {
    "status": "success",
    "moodle_course_id": 21,
    "shortname": "MK-2026-01",
    "course_url": "http://meko.localhost/lms/course/view.php?id=21",
    "idempotent_reused": false,
    "imported_sections": 4,
    "imported_modules": { "pages": 6, "resources": 4, "quizzes": 1 },
    "linked_competencies": 2
  }
  ```
- **Errors:** `invalid_imscc_zip`, `missing_imsmanifest`, `category_not_found`, `lecturer_sync_failed`.

#### API #3 — `local_meko_sync_get_courses` (`MEKO-LMS-05`)
- **Request:** `{ "status": "PUBLISHED", "category_idnumber": "", "modified_since": 0, "page": 1, "per_page": 50 }`
- **Response `200 OK`:**
  ```json
  {
    "total": 1,
    "page": 1,
    "courses": [
      {
        "moodle_course_id": 8,
        "shortname": "NLS-01-IMS",
        "fullname": "Đóng gói Học liệu Chuẩn IMS CP & Nhúng Tài nguyên Số",
        "summary": "Khóa học hướng dẫn chuẩn đóng gói IMS CC, QTI XML và siêu dữ liệu LOM.",
        "category_idnumber": "DIGCOMP_STU",
        "category_name": "Năng lực số Sinh viên",
        "startdate": 1794441600,
        "enddate": 1797033600,
        "visible": 1,
        "course_image_url": "http://meko.localhost/lms/pluginfile.php/45/course/overviewfiles/nls01ims.png",
        "customfields": {
          "accees_course_status": "PUBLISHED",
          "accees_delivery_mode": "Blended Learning",
          "accees_duration": "3 Credits · 60 Hours",
          "accees_institution": "Mekosoft Training Hub",
          "accees_is_featured": true
        },
        "teachers": [{ "fullname": "PGS.TS. Trần Thanh Điền", "email": "dien.tran@mekosoft.vn" }],
        "competencies": [{ "moodle_competency_id": 14, "idnumber": "SV_C1_1_N1", "shortname": "C1.1 Search & Filter (Level N1)" }],
        "quiz_gradepass": 7.0
      }
    ]
  }
  ```

#### API #4 — `local_meko_sync_update_courses` (`MEKO-LMS-05`)
- **Request:** `{ "moodle_course_id": 21, "visible": 0, "accees_course_status": "CLOSED", "fullname": "" }`
- **Response `200 OK`:** `{ "status": "success", "moodle_course_id": 21, "updated_fields": ["visible", "accees_course_status"] }`

#### API #5 — `local_meko_sync_enrol_users_bulk` (`MEKO-LMS-05`)
- **Request:**
  ```json
  {
    "courseid": 21,
    "auth_type": "manual",
    "users": [
      {
        "username": "mk2026001",
        "student_code": "MK2026001",
        "email": "an.nguyen@mekosoft.vn",
        "firstname": "An",
        "lastname": "Nguyễn Văn",
        "organization": "Phòng Kỹ thuật Phần mềm",
        "roleid": 5
      }
    ]
  }
  ```
- **Response `200 OK`:**
  ```json
  {
    "status": "success",
    "enrolled_count": 1,
    "failed_count": 0,
    "results": [
      {
        "username": "mk2026001",
        "email": "an.nguyen@mekosoft.vn",
        "moodle_userid": 105,
        "auth_type": "manual",
        "temp_password": "Meko@2026#a9",
        "result": "created_and_enrolled"
      }
    ],
    "errors": []
  }
  ```

#### API #6 — `local_meko_sync_get_learner_results` (`MEKO-LMS-05`)
- **Request:** `{ "moodle_course_id": 21, "moodle_user_ids": [105], "since_timestamp": 0 }`
- **Response `200 OK`:**
  ```json
  {
    "moodle_course_id": 21,
    "results": [
      {
        "moodle_userid": 105,
        "email": "an.nguyen@mekosoft.vn",
        "course_completed": true,
        "final_grade": 8.5,
        "max_grade": 10.0,
        "competencies": [{ "idnumber": "SV_C1_1_N1", "proficient": true, "grade": 8.5 }],
        "badges": [{ "id": 12, "name": "Chứng nhận Hoàn thành Năng lực số C1.1", "issued_at": 1795000000 }]
      }
    ]
  }
  ```

---

### 3.2. Nhóm 2: `2` Liferay JAX-RS REST Endpoints (`tms-rest`)

#### API #7 — `POST /o/meko-tms/api/webhooks/badge-awarded` (`MEKO-TMS-06`)
- **Headers:** `Content-Type: application/json`, `X-MEKO-Signature: sha256=<hmac_sha256_hex>`
- **Request Body:**
  ```json
  {
    "event": "badge_awarded",
    "timestamp": 1795000000,
    "moodle_course_id": 21,
    "course_shortname": "MK-2026-01",
    "moodle_userid": 105,
    "user_email": "an.nguyen@mekosoft.vn",
    "final_grade": 8.5,
    "badge": {
      "id": 12,
      "name": "Chứng nhận Hoàn thành Năng lực số C1.1",
      "image_url": "http://meko.localhost/lms/badges/badge_c11.png",
      "issued_at": 1795000000
    }
  }
  ```
- **Responses:**
  - `200 OK`: `{ "status": "ok", "cert_id": 18, "cert_serial_code": "MEKO-2026-00018", "qr_verify_code": "8f4a29b7c1e04d11a89c" }`
  - `401 Unauthorized`: `{ "status": "error", "code": "invalid_signature", "message": "HMAC-SHA256 mismatch" }`
  - `400 Bad Request`: `{ "status": "error", "code": "enrollment_not_found", "message": "No matching TMSEnrollment found" }`

#### API #8 — `GET /o/meko-tms/api/certificates/verify?code={qr_verify_code}` (`MEKO-TMS-06`)
- **Response `200 OK` (Hợp lệ):**
  ```json
  {
    "valid": true,
    "status": "ISSUED",
    "cert_serial_code": "MEKO-2026-00018",
    "qr_verify_code": "8f4a29b7c1e04d11a89c",
    "student_code": "MK2026001",
    "student_name": "Nguyễn Văn An",
    "course_code": "MK-2026-01",
    "course_title": "Đóng gói Học liệu Chuẩn IMS CP",
    "final_grade": 8.5,
    "badge_name": "Chứng nhận Hoàn thành Năng lực số C1.1",
    "issued_date": "2026-11-16",
    "revoke_reason": ""
  }
  ```
- **Errors / Trạng thái đặc biệt:** `404`: `{ "valid": false, "status": "NOT_FOUND" }`, `200 (Thu hồi)`: `"valid": false, "status": "REVOKED", "revoke_reason": "..."`.

---

### 3.3. Nhóm 3: `13` Liferay Portlet MVC Commands (Form Action & React AJAX)

> **Lưu ý gọi từ Frontend React Portlet:** React luôn đọc `LiferayBridgeContext` (`resourceURL` + `p_auth`) do JSP truyền xuống, gọi `fetch(buildResourceUrl('/tms/...'))` để đảm bảo đúng Session & CSRF của Liferay.

#### Command #9 — `POST /tms/plan/save` (`SaveCoursePlanMVCActionCommand` — `MEKO-TMS-04`)
- **Form Params:** `planId` *(0 nếu tạo mới)*, `courseTitle*`, `lomIdentifier`, `imsPackageUrl*`, `deliveryFormat*`, `targetAudience`, `creditHours*`, `estimatedLearners*`, `expectedStartDate*`, `expectedEndDate*`, `lecturerName*`, `lecturerEmail*`, `courseDescription`.
- **Output:** PRG Redirect về danh sách Kế hoạch + `SessionMessages.add(request, "planSaved")`; lỗi validate đẩy vào `SessionErrors`.

#### Command #10 — `POST /tms/plan/approve` (`ApproveCoursePlanMVCActionCommand` — `MEKO-TMS-04`)
- **Form Params:** `planId*`, `actionType* (APPROVE | REJECT)`, `approvalNotes`.
- **Output:** Cập nhật `status` + audit cột duyệt (`approvedByUserId`, `approvalDate`), Redirect PRG.

#### Command #11 — `POST /tms/course/save` (`SaveCourseMVCActionCommand` — `MEKO-TMS-04`)
- **Form Params:** `courseId`, `planId*`, `courseCode*`, `courseTitle*`, `academicYear`, `semester`, `startDate*`, `endDate*`, `enrollmentStartDate`, `enrollmentEndDate`, `maxEnrollment*`, `thumbnailUrl*`, `frameworkIds[]*`, `status`, `isFeatured`.
- **Output:** Lưu `TMSCourse` + `TMSCourseCompetency[]`, Redirect PRG; nếu trùng `courseCode` báo lỗi `SessionErrors("duplicateCourseCode")`.

#### Command #12 — `POST /tms/course/deploy` (`DeployToMoodleMVCActionCommand` — `MEKO-TMS-04`)
- **Form Params:** `courseId*`.
- **Output:** Chuyển `moodleSyncStatus = DEPLOYING`, gọi `MoodleRestClient` (`API #2`), lưu `moodleCourseId`, `DEPLOYED`, `IN_PROGRESS`, ghi `TMSSyncLog`, Redirect PRG.

#### Command #13 — `GET /tms/catalog/list` (`GetCatalogMVCResourceCommand` — `MEKO-TMS-05`)
- **Query Params:** `keyword`, `status (ENROLLING|IN_PROGRESS|UPCOMING)`, `frameworkCode`, `page=1`, `pageSize=12`.
- **Response `200 OK`:**
  ```json
  {
    "status": "ok",
    "total": 12,
    "page": 1,
    "items": [
      {
        "courseId": 101,
        "courseCode": "NLS-01-IMS",
        "courseTitle": "Đóng gói Học liệu Chuẩn IMS CP",
        "thumbnailUrl": "http://meko.localhost/lms/pluginfile.php/45/course/overviewfiles/nls01ims.png",
        "deliveryFormat": "Blended Learning",
        "creditHours": 3,
        "startDate": "2026-11-20",
        "status": "IN_PROGRESS",
        "enrolledCount": 24,
        "maxEnrollment": 60,
        "isEnrolledByMe": false,
        "moodleCourseUrl": "http://meko.localhost/lms/course/view.php?id=8"
      }
    ]
  }
  ```

#### Command #14 — `POST /tms/enroll/self` (`SelfEnrollMVCResourceCommand` — `MEKO-TMS-05`)
- **Body JSON / Form:** `{ "courseId": 101 }`
- **Response `200 OK`:** `{ "status": "ok", "enrollmentId": 41, "enrollmentCode": "ENR-2026-0041", "enrollmentStatus": "PENDING" }`
- **Errors:** `401` (`login_required`), `409` (`already_enrolled` | `course_full`).

#### Command #15 — `POST /tms/enroll/excel-preview` (`ImportExcelPreviewMVCResourceCommand` — `MEKO-TMS-05`)
- **Multipart Form:** `courseId`, `file (.xlsx)` *(đủ 6 cột: `STT, student_code, full_name, email, phone, organization`)*.
- **Response `200 OK`:**
  ```json
  {
    "status": "ok",
    "totalRows": 20,
    "validCount": 19,
    "errorCount": 1,
    "rows": [
      { "rowNum": 2, "studentCode": "MK2026001", "fullName": "Nguyễn Văn An", "email": "an.nguyen@mekosoft.vn", "phone": "0901234567", "organization": "Phòng KT", "valid": true, "error": "" }
    ]
  }
  ```

#### Command #16 — `POST /tms/enroll/excel-confirm` (`ImportExcelConfirmMVCResourceCommand` — `MEKO-TMS-05`)
- **Body JSON:** `{ "courseId": 101, "rows": [ { "studentCode": "MK2026001", "fullName": "Nguyễn Văn An", "email": "an.nguyen@mekosoft.vn", "phone": "0901234567", "organization": "Phòng KT" } ] }`
- **Response `200 OK`:** `{ "status": "ok", "imported": 19, "queuedBatchId": "BATCH-20261116-01", "syncStatus": "PENDING" }`

#### Command #17 — `POST /tms/enroll/decide` (`ApproveRejectEnrollmentMVCResourceCommand` — `MEKO-TMS-05`)
- **Body JSON:** `{ "enrollmentIds": [41], "decision": "APPROVE", "rejectReason": "" }` *(hoặc `"decision": "REJECT"`, bắt buộc có `rejectReason`)*.
- **Response `200 OK`:** `{ "status": "ok", "processedCount": 1, "decision": "APPROVE", "emailTemplate": "ENROLLMENT_APPROVED" }`
- **Errors:** `400` (`reject_reason_required`), `403` (`forbidden`).

#### Command #18 — `POST /tms/logs/email-resend` (`ResendEmailMVCResourceCommand` — `MEKO-TMS-05`)
- **Body JSON / Form:** `{ "emailLogId": 88 }`
- **Response `200 OK`:** `{ "status": "ok", "oldEmailLogId": 88, "newEmailLogId": 99, "mailStatus": "SENT" }`

#### Command #19 — `GET /tms/logs/list` (`GetLogsMVCResourceCommand` — `MEKO-TMS-05`)
- **Query Params:** `logType=EMAIL` (`SYNC` | `EMAIL`), `courseId`, `status`, `page=1`, `pageSize=20`.
- **Response `200 OK`:** `{ "status": "ok", "logType": "EMAIL", "total": 20, "items": [ { "id": 88, "templateCode": "ENROLLMENT_APPROVED", "recipientEmail": "an.nguyen@mekosoft.vn", "status": "SENT", "sentDate": "2026-11-16T10:15:00Z", "errorMessage": "" } ] }`

#### Command #20 — `GET /tms/profile/summary` (`GetMyProfileMVCResourceCommand` — `MEKO-TMS-06`)
- **Response `200 OK`:**
  ```json
  {
    "status": "ok",
    "student": { "studentCode": "MK2026001", "fullName": "Nguyễn Văn An", "email": "an.nguyen@mekosoft.vn", "organization": "Phòng Kỹ thuật Phần mềm" },
    "enrollments": [{ "courseCode": "MK-2026-01", "courseTitle": "Đóng gói Học liệu Chuẩn IMS CP", "progressPercent": 100, "finalGrade": 8.5, "status": "PASSED" }],
    "competencyProgress": [{ "domainCode": "C1", "domainName": "Thông tin & Dữ liệu", "achievedCount": 3, "totalCount": 4 }],
    "certificates": [{ "certId": 18, "certSerialCode": "MEKO-2026-00018", "qrVerifyCode": "8f4a29b7c1e04d11a89c", "issuedDate": "2026-11-16", "status": "ISSUED" }]
  }
  ```

#### Command #21 — `GET /tms/profile/cert-pdf?certId={certId}` (`DownloadCertificatePdfMVCResourceCommand` — `MEKO-TMS-06`)
- **Response `200 OK`:** Binary stream `Content-Type: application/pdf`, `Content-Disposition: attachment; filename="MEKO-2026-00018.pdf"`.
- **Errors:** `403` (`not_certificate_owner`), `404` (`certificate_pdf_not_found`).

---

## 4. Bộ OpenAPI, Postman & Mock Server Chạy Thử Ngay (`meko-samples/`)

Dev có thể mở hoặc chạy ngay không cần chờ dựng full server:
- **OpenAPI 3.0.3 Spec (import Swagger UI / Apidog):** [`meko-samples/api-contracts/meko-dle-openapi.yaml`](../meko-samples/api-contracts/meko-dle-openapi.yaml)
- **Postman Collection v2.1 (đủ 21 requests mẫu):** [`meko-samples/api-contracts/meko-dle.postman_collection.json`](../meko-samples/api-contracts/meko-dle.postman_collection.json)
- **Mock Server Python (port `8099`, không cần `pip install`):**
  ```bash
  python meko-samples/mock-server/mock_api.py
  # Mở http://localhost:8099/ để xem danh sách endpoints mock HTTP 200
  ```

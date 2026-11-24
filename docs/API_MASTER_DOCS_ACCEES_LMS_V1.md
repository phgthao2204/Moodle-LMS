# ACCEES-LMS REST API v2 — Master Specification (`v1.0` & `v1.1`)

- **Dự án:** ACCEES-2026 (`accees-2026`) — Phân hệ Moodle LMS (`elestio/moodle:v5.3.0`)
- **Phạm vi Module:**
  1. `local_accees_imscp` (`MOD-LMS-04` / OP `#4165`) — `UC-LMS-08`, `UC-LMS-09`
  2. `local_accees_sync_api` (`MOD-LMS-05` / OP `#4166`) — `UC-LMS-10`, `UC-LMS-11`, `UC-LMS-12`, `UC-LMS-13`, `UC-LMS-14`
- **Chuẩn giao tiếp:** Moodle 5.3 Native Routing Engine (`r.php`) & REST API v2 (`/lms/api/rest/v2/<component>/...`)
- **OpenAPI Live Schema:** `GET /lms/api/rest/v2/openapi.json` | **Swagger UI:** `/lms/admin/swaggerui.php`

---

## 1. Quy ước Chung, Xác thực & Phân quyền

### 1.1 Base URL & Headers
- **Base URL (`devlocal`):** `http://meko.localhost/lms/api/rest/v2`
- **Headers bắt buộc:**
  - `Authorization: Bearer <access_token>`
  - `Content-Type: application/json` (trừ endpoint upload multipart `.imscc` dùng `multipart/form-data`)
  - `Accept: application/json`
  - `X-ACCEES-API-Version: 1.0` (tùy chọn; mặc định `1.0`. Dùng `1.1` để bật hành vi mở rộng như strict SSO hoặc full LOM schema)

### 1.2 Cơ chế Xác thực (OAuth 2.0 + Bearer Token)
1. **Chuẩn tích hợp chính thức (TMS, DLRP, Khoa QA/Recheck):**
   - Dùng **OAuth 2.0 Client Credentials** cấu hình tại Moodle (`Site administration > Server > OAuth 2 clients`).
   - Client được cấp `client_id` + `client_secret` và chỉ được gán đúng tập **Scopes** thuộc phạm vi nghiệp vụ.
   - Khi định hướng gắn **Keycloak SSO** sau này, toàn bộ 7 API giữ nguyên giao diện nhận `Authorization: Bearer <token>` + kiểm tra Scope/Capability ở Router.
2. **Tiện ích debug cho Dev trên `devlocal`:**
   - Khoa dựng sẵn tài khoản service `accees_service` (gán Role `accees_integration_service`) kèm **Personal Access Token (PAT)** mẫu và **OAuth2 Dev Client (`accees-dev-client`)** trong bước `T0`.
   - Khi nộp PR (mục `b`) và khi Khoa Recheck, bắt buộc pass qua **OAuth 2.0 Client Credentials**.

### 1.3 Bảng Scope & Capability Chuẩn

| Plugin | Scope (`#[\core\router\scope\scopeset]`) | Capability (`db/access.php`) | UC áp dụng | Phụ trách |
| :--- | :--- | :--- | :--- | :--- |
| `local_accees_imscp` | `local_accees_imscp:imscp:import` | `local/accees_imscp:import` | `UC-LMS-08` | Thiện |
| `local_accees_imscp` | `local_accees_imscp:imscp:export` | `local/accees_imscp:export` | `UC-LMS-09` | Thiện |
| `local_accees_sync_api` | `local_accees_sync_api:course:read` | `local/accees_sync_api:viewcourses` | `UC-LMS-10` | Thảo |
| `local_accees_sync_api` | `local_accees_sync_api:course:write` | `local/accees_sync_api:updatecourse` | `UC-LMS-11` | Thảo |
| `local_accees_sync_api` | `local_accees_sync_api:enrol:manage` | `local/accees_sync_api:enrol` | `UC-LMS-12` | Yến |
| `local_accees_sync_api` | `local_accees_sync_api:enrol:manage` | `local/accees_sync_api:unenrol` | `UC-LMS-13` | Yến |
| `local_accees_sync_api` | `local_accees_sync_api:result:read` | `local/accees_sync_api:viewresults` | `UC-LMS-14` | Yến |

---

## 2. Ma trận Phiên bản (`v1.0` vs `v1.1`) & `LomMetadataInterface`

### 2.1 Ma trận Khác biệt Giữa `v1.0` và `v1.1`

| Hạng mục | Phiên bản `1.0` (Sprint hiện tại) | Phiên bản `1.1` (Mở rộng / SSO hoàn chỉnh) |
| :--- | :--- | :--- |
| **URL Endpoint** | Cố định `/lms/api/rest/v2/<component>/...` | Giữ nguyên URL, không làm vỡ tích hợp |
| **UC-LMS-12 (Enrol)** | `strict_sso=false` (mặc định): nếu user chưa tồn tại trong Moodle, **tự động tạo user tối thiểu** rồi ghi danh; trả về `user_created: true/false` | Hỗ trợ `strict_sso=true` (bắt buộc user đã đồng bộ qua Keycloak/LDAP OIDC; nếu không tồn tại trả `404 USER_NOT_FOUND` hoặc `409`) |
| **LOM-ACCEES Metadata** | Dùng **`LomMetadataInterface` đầy đủ 9 nhóm** trong DTO/XML; tầng DB Moodle **lưu thật 5 Core Custom Fields** (`accees_*`) | Bật mapping lưu trữ các trường LOM mở rộng (từ chuẩn D1.2a 9 nhóm / 79 phần tử) mà không phải sửa chữ ký API |
| **UC-LMS-14 (Results)** | Pull `GET` (điểm, % hoàn thành, Open Badges) + Event Observer bắn Webhook ra URL cấu hình (kèm retry log cơ bản) | Nâng cấp dead-letter queue / HMAC signature verification / batch retry nâng cao cho Webhook |

### 2.2 Hợp đồng `LomMetadataInterface` (Chuẩn hóa LOM-ACCEES)

Tất cả API đụng tới siêu dữ liệu khóa học (`UC-08`, `UC-09`, `UC-10`, `UC-11`) đều đi qua interface `local_accees_sync_api\contract\lom_metadata_interface` (hoặc shared DTO dùng chung giữa 2 plugin).

**Cấu trúc JSON chuẩn 9 nhóm LOM-ACCEES (theo tài liệu `ACCEES_D1.2a`):**
```json
{
  "schema_version": "LOM-ACCEES-1.0",
  "general": {
    "identifier": "ACCEES-CS101",
    "title": "Nhap mon Khoa hoc Du lieu",
    "language": ["vi"],
    "description": "Mo ta khoa hoc...",
    "keyword": ["AI", "Data Science"]
  },
  "lifecycle": {
    "version": "1.0",
    "status": "published",
    "contribute": [{"role": "author", "entity": "Dai hoc Can Tho", "date": "2026-11-17"}]
  },
  "metametadata": {
    "metadata_schema": ["LOMv1.0", "ACCEES-LOMv1.0"]
  },
  "technical": {
    "format": ["application/zip", "text/html"],
    "duration_hours": 30,
    "delivery_mode": "blended"
  },
  "educational": {
    "learning_resource_type": ["course"],
    "intended_end_user_role": ["learner"],
    "typical_learning_time": "PT30H",
    "digcomp_level": "B1"
  },
  "rights": {
    "cost": "no",
    "copyright_and_other_restrictions": "yes",
    "license": "CC-BY-4.0"
  },
  "relation": [],
  "annotation": [],
  "classification": {
    "institution": "CTU",
    "is_featured": true,
    "competency_codes": ["DIGCOMP-1.1", "DIGCOMP-2.1"]
  }
}
```

**Bảng ánh xạ vào 5 Custom Fields lõi trên Moodle ở `v1.0`:**

| Nhóm / Trường trong `LomMetadataInterface` | Shortname Custom Field Moodle (`v1.0`) | Kiểu dữ liệu | Trạng thái ở `v1.0` |
| :--- | :--- | :--- | :--- |
| `lifecycle.status` | `accees_course_status` | Text / Select | Đọc & Ghi DB |
| `technical.delivery_mode` | `accees_delivery_mode` | Text / Select | Đọc & Ghi DB |
| `technical.duration_hours` | `accees_duration` | Text / Int | Đọc & Ghi DB |
| `classification.institution` | `accees_institution` | Text | Đọc & Ghi DB |
| `classification.is_featured` | `accees_is_featured` | Checkbox / Bool | Đọc & Ghi DB |
| Các trường còn lại trong 9 nhóm LOM-ACCEES | Lưu giữ cấu trúc trong `LomMetadataInterface` & `imsmanifest.xml` | Object/Array | Pass-through / Dự phòng cho `v1.1` |

---

## 3. Đặc tả Chi tiết 7 API (`UC-LMS-08` đến `UC-LMS-14`)

> **Quy ước Format Lỗi Chung (`4xx`/`5xx`):**
> ```json
> {
>   "error": {
>     "code": "ERROR_ENUM_CODE",
>     "message": "Mo ta loi ro rang khong chua stacktrace",
>     "details": {}
>   }
> }
> ```

---

### Module 1: `local_accees_imscp` (Phụ trách dev: Thiện)

#### 3.1 `UC-LMS-08` — Nạp gói khóa học `.imscc` chuẩn ACCEES-LOM
- **Endpoint:** `POST /lms/api/rest/v2/local_accees_imscp/packages/import`
- **Scope:** `local_accees_imscp:imscp:import` | **Capability:** `local/accees_imscp:import`
- **Mục đích:** Nhận gói `.imscc` (upload trực tiếp hoặc tải từ URL/storage), giải nén, kiểm tra `imsmanifest.xml`, đọc metadata LOM-ACCEES (5 core fields + 9 nhóm interface), tạo/nạp nội dung vào khóa học Moodle.
- **Request (`multipart/form-data` hoặc `application/json`):**
  - `package_file` *(file `.imscc`/`.zip`, bắt buộc nếu không có `package_url`)*
  - `package_url` *(string, optional)* — URL nội bộ DLRP/MinIO để LMS kéo file
  - `category_id` *(int, optional, default = category mặc định cấu hình plugin)*
  - `target_course_shortname` *(string, optional)* — dùng khi cập nhật vào khóa học có sẵn
  - `overwrite` *(bool, optional, default = `false`)*
- **Response `201 Created`:**
```json
{
  "course_id": 105,
  "shortname": "ACCEES-CS101",
  "fullname": "Nhap mon Khoa hoc Du lieu",
  "category_id": 2,
  "import_status": "completed",
  "modules_created": 8,
  "lom_summary": {
    "accees_course_status": "published",
    "accees_delivery_mode": "online",
    "accees_duration": "30",
    "accees_institution": "CTU",
    "accees_is_featured": true
  },
  "warnings": []
}
```
- **Mã lỗi:** `400 INVALID_PACKAGE` (thiếu `imsmanifest.xml`, sai định dạng zip), `401 UNAUTHORIZED`, `403 FORBIDDEN`, `409 COURSE_ALREADY_EXISTS` (khi `overwrite=false` và `shortname` trùng), `422 LOM_VALIDATION_FAILED`.

---

#### 3.2 `UC-LMS-09` — Xuất gói khóa học `.imscc` đính kèm ACCEES-LOM
- **Endpoint:** `POST /lms/api/rest/v2/local_accees_imscp/courses/{courseid}/export`
- **Scope:** `local_accees_imscp:imscp:export` | **Capability:** `local/accees_imscp:export`
- **Mục đích:** Đóng gói khóa học Moodle thành file `.imscc` theo chuẩn IMS Common Cartridge / IMSCP, tự động nhúng `imsmanifest.xml` chứa khối `<lom>` trích xuất từ `LomMetadataInterface` (5 core fields + thông tin khóa học Moodle), trả về URL tải hoặc stream/artifact metadata.
- **Path Params:** `courseid` *(int, required)*
- **Request Body (`application/json`):**
```json
{
  "include_userdata": false,
  "lom_override": {
    "lifecycle": { "version": "1.1" }
  },
  "delivery": "download_url"
}
```
- **Response `200 OK`:**
```json
{
  "course_id": 105,
  "shortname": "ACCEES-CS101",
  "filename": "ACCEES-CS101-v1.0.imscc",
  "size_bytes": 2458190,
  "checksum_sha256": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855",
  "download_url": "http://meko.localhost/lms/api/rest/v2/local_accees_imscp/exports/token-abc123",
  "expires_at": "2026-11-18T10:00:00+07:00",
  "lom_included": true
}
```
*(Ghi chú: Cần cung cấp thêm route tải file `GET /lms/api/rest/v2/local_accees_imscp/exports/{token}` có check capability hoặc trả binary trực tiếp nếu `delivery=binary`.)*
- **Mã lỗi:** `400 INVALID_PARAMETER`, `401 UNAUTHORIZED`, `403 FORBIDDEN`, `404 COURSE_NOT_FOUND`, `500 EXPORT_PACKAGING_FAILED`.

---

### Module 2: `local_accees_sync_api` (Phụ trách dev: Thảo & Yến)

#### 3.3 `UC-LMS-10` — Lấy danh sách / chi tiết khóa học & metadata LOM *(Dev: Thảo)*
- **Endpoints:**
  - `GET /lms/api/rest/v2/local_accees_sync_api/courses` (phân trang & lọc)
  - `GET /lms/api/rest/v2/local_accees_sync_api/courses/{courseid}` (chi tiết 1 khóa học)
- **Scope:** `local_accees_sync_api:course:read` | **Capability:** `local/accees_sync_api:viewcourses`
- **Query Params (cho danh sách):**
  - `page` *(int, default = 1)*, `per_page` *(int, default = 20, max = 100)*
  - `search` *(string, optional — tìm theo fullname/shortname)*
  - `category_id` *(int, optional)*
  - `institution` *(string, optional — lọc theo `accees_institution`)*
  - `delivery_mode` *(string, optional — lọc theo `accees_delivery_mode`)*
  - `status` *(string, optional — lọc theo `accees_course_status`)*
  - `is_featured` *(bool, optional)*
  - `updated_since` *(ISO-8601 timestamp, optional — phục vụ đồng bộ tăng trưởng)*
- **Response `200 OK` (Danh sách):**
```json
{
  "items": [
    {
      "id": 105,
      "shortname": "ACCEES-CS101",
      "fullname": "Nhap mon Khoa hoc Du lieu",
      "summary": "Mo ta ngan...",
      "category_id": 2,
      "visible": true,
      "startdate": 1794873600,
      "enddate": 1802649600,
      "timemodified": 1794900000,
      "custom_fields": {
        "accees_course_status": "published",
        "accees_delivery_mode": "online",
        "accees_duration": "30",
        "accees_institution": "CTU",
        "accees_is_featured": true
      },
      "lom": {
        "schema_version": "LOM-ACCEES-1.0",
        "general": { "identifier": "ACCEES-CS101", "title": "Nhap mon Khoa hoc Du lieu" }
      }
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 1,
    "total_pages": 1
  }
}
```
- **Mã lỗi:** `400 INVALID_QUERY_PARAM`, `401 UNAUTHORIZED`, `403 FORBIDDEN`, `404 COURSE_NOT_FOUND` (ở endpoint chi tiết).

---

#### 3.4 `UC-LMS-11` — Cập nhật thông tin & siêu dữ liệu LOM khóa học *(Dev: Thảo)*
*(Chuẩn hóa từ đặc tả gốc ghi nhầm "Tạo khóa học": khóa học gốc do `UC-08` nạp vào; `UC-11` cập nhật metadata, trạng thái, lịch học và các trường custom field chuẩn ACCEES-LOM).*
- **Endpoint:** `PATCH /lms/api/rest/v2/local_accees_sync_api/courses/{courseid}` *(hoặc `PUT` cùng handler partial-update)*
- **Scope:** `local_accees_sync_api:course:write` | **Capability:** `local/accees_sync_api:updatecourse`
- **Request Body (`application/json` — chỉ gửi các trường cần cập nhật):**
```json
{
  "fullname": "Nhap mon Khoa hoc Du lieu (Cap nhat)",
  "summary": "Cap nhat noi dung moi...",
  "visible": true,
  "startdate": 1794873600,
  "enddate": 1805000000,
  "custom_fields": {
    "accees_course_status": "published",
    "accees_delivery_mode": "blended",
    "accees_duration": "45",
    "accees_institution": "CTU",
    "accees_is_featured": true
  },
  "lom": {
    "educational": { "digcomp_level": "B2" }
  }
}
```
- **Response `200 OK`:**
```json
{
  "id": 105,
  "shortname": "ACCEES-CS101",
  "updated_fields": ["fullname", "custom_fields.accees_delivery_mode", "custom_fields.accees_duration"],
  "custom_fields": {
    "accees_course_status": "published",
    "accees_delivery_mode": "blended",
    "accees_duration": "45",
    "accees_institution": "CTU",
    "accees_is_featured": true
  },
  "timemodified": 1794902000
}
```
- **Mã lỗi:** `400 INVALID_PAYLOAD`, `401 UNAUTHORIZED`, `403 FORBIDDEN`, `404 COURSE_NOT_FOUND`, `422 INVALID_CUSTOMFIELD_VALUE`.

---

#### 3.5 `UC-LMS-12` — Ghi danh học viên / giảng viên vào khóa học (`Enrol`) *(Dev: Yến)*
- **Endpoint:** `POST /lms/api/rest/v2/local_accees_sync_api/courses/{courseid}/enrolments`
- **Scope:** `local_accees_sync_api:enrol:manage` | **Capability:** `local/accees_sync_api:enrol`
- **Hỗ trợ Đa phiên bản (`v1.0` vs `v1.1`):**
  - `strict_sso=false` *(Mặc định ở `v1.0`)*: Tìm user theo `username` hoặc `email`. Nếu chưa có trong Moodle và request có đủ `email`, `firstname`, `lastname` -> **tự động tạo user mới** (`auth = manual` hoặc cấu hình mặc định) rồi mới ghi danh (`manual enrol`).
  - `strict_sso=true` *(Hoặc khi header `X-ACCEES-API-Version: 1.1` bật chế độ SSO-only)*: Không tự tạo user; nếu user chưa tồn tại trong Moodle -> trả `404 USER_NOT_FOUND`.
  - **Idempotent:** Nếu user đã được ghi danh đúng role trong khóa học -> trả `200 OK` với `enrolment_status: "already_enrolled"`, không báo lỗi trùng DB.
- **Request Body (`application/json`):**
```json
{
  "user": {
    "username": "sv_ctu_001",
    "email": "sv_ctu_001@ctu.edu.vn",
    "firstname": "An",
    "lastname": "Nguyen Van",
    "idnumber": "B2100001"
  },
  "role": "student",
  "timestart": 1794873600,
  "timeend": 0,
  "strict_sso": false
}
```
- **Response `200 OK` / `201 Created`:**
```json
{
  "course_id": 105,
  "user_id": 42,
  "username": "sv_ctu_001",
  "role": "student",
  "user_created": true,
  "enrolment_status": "enrolled",
  "timestart": 1794873600,
  "timeend": 0
}
```
- **Mã lỗi:** `400 MISSING_USER_FIELDS`, `401 UNAUTHORIZED`, `403 FORBIDDEN`, `404 COURSE_NOT_FOUND`, `404 USER_NOT_FOUND` (khi `strict_sso=true`), `404 ROLE_NOT_FOUND`, `409 ENROLMENT_CONFLICT`.

---

#### 3.6 `UC-LMS-13` — Hủy ghi danh học viên khỏi khóa học (`Unenrol`) *(Dev: Yến)*
- **Endpoint:** `DELETE /lms/api/rest/v2/local_accees_sync_api/courses/{courseid}/enrolments` *(truyền `username` hoặc `userid` qua query/body JSON chuẩn REST)*
  *(Khuyến nghị nhận qua query params `?username=...` hoặc `?user_id=...` vì một số proxy/client cắt body của `DELETE`; có thể hỗ trợ đọc cả query lẫn body nếu cần.)*
- **Scope:** `local_accees_sync_api:enrol:manage` | **Capability:** `local/accees_sync_api:unenrol`
- **Hành vi:** Thực thi hủy ghi danh qua Moodle `enrol_manual_plugin::unenrol_user()`. Hỗ trợ idempotent hoặc cờ kiểm soát rõ ràng: nếu user không còn ghi danh trả `404 ENROLMENT_NOT_FOUND` (hoặc `200` nếu truyền `ignore_missing=true`).
- **Response `200 OK`:**
```json
{
  "course_id": 105,
  "user_id": 42,
  "username": "sv_ctu_001",
  "enrolment_status": "unenrolled"
}
```
- **Mã lỗi:** `400 MISSING_IDENTIFIER`, `401 UNAUTHORIZED`, `403 FORBIDDEN`, `404 COURSE_NOT_FOUND`, `404 USER_NOT_FOUND`, `404 ENROLMENT_NOT_FOUND`.

---

#### 3.7 `UC-LMS-14` — Lấy kết quả học tập, Tiến độ & Open Badges + Webhook *(Dev: Yến)*

##### Phần A — Pull API (`GET`)
- **Endpoint:** `GET /lms/api/rest/v2/local_accees_sync_api/courses/{courseid}/results`
- **Scope:** `local_accees_sync_api:result:read` | **Capability:** `local/accees_sync_api:viewresults`
- **Query Params:**
  - `username` *(string, optional — lọc theo 1 học viên)*
  - `user_id` *(int, optional)*
  - `page` *(int, default = 1)*, `per_page` *(int, default = 50)*
  - `completed_only` *(bool, optional)*
- **Response `200 OK`:**
```json
{
  "course_id": 105,
  "shortname": "ACCEES-CS101",
  "items": [
    {
      "user_id": 42,
      "username": "sv_ctu_001",
      "progress_percent": 100.0,
      "completed": true,
      "timecompleted": 1794950000,
      "final_grade": {
        "raw": 8.75,
        "max": 10.0,
        "percentage": 87.5
      },
      "badges": [
        {
          "badge_id": 3,
          "name": "ACCEES Data Literacy",
          "issued_at": 1794950010,
          "uniquehash": "9a1f2c3e4d5b",
          "assertion_url": "http://meko.localhost/lms/badges/assertion.php?b=9a1f2c3e4d5b"
        }
      ]
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 1,
    "total_pages": 1
  }
}
```
- **Mã lỗi:** `401 UNAUTHORIZED`, `403 FORBIDDEN`, `404 COURSE_NOT_FOUND`, `404 USER_NOT_FOUND` (nếu truyền `username`/`user_id` không tồn tại).

##### Phần B — Push Webhook (Event Observer trong `local_accees_sync_api`)
- **Trigger Event Moodle:** `\core\event\course_completed` và `\core\event\badge_awarded`.
- **Cấu hình Admin Setting (`settings.php`):**
  - `local_accees_sync_api/webhook_enabled` (`0/1`)
  - `local_accees_sync_api/webhook_url` (VD trên `devlocal`: `http://mock-webhook:8080/hooks/lms-result`)
  - `local_accees_sync_api/webhook_secret` (dùng ký header `X-ACCEES-Signature: sha256=...`)
- **Payload Push JSON (`POST` tới `webhook_url`):**
```json
{
  "event_id": "evt_20261117_0001",
  "event_type": "course_completed",
  "occurred_at": "2026-11-17T10:15:00+07:00",
  "course_id": 105,
  "shortname": "ACCEES-CS101",
  "user_id": 42,
  "username": "sv_ctu_001",
  "progress_percent": 100.0,
  "final_grade": 8.75,
  "badges": []
}
```
*(Lưu ý: Bắn bất đồng bộ qua Moodle Adhoc Task `\local_accees_sync_api\task\send_webhook_task` để không làm treo request học viên khi endpoint TMS bị chậm; ghi log kết quả thành công/thất bại).*

---

## 4. Quy chuẩn Cấu trúc File Bắt buộc cho Mỗi API trong Plugin

```text
meko-lms/local/accees_imscp/
|-- version.php
|-- lang/en/local_accees_imscp.php
|-- db/access.php
|-- classes/
|   |-- route/api/
|   |   |-- import_package.php          # UC-LMS-08 (Thien)
|   |   `-- export_package.php          # UC-LMS-09 (Thien)
|   `-- manager/
|       |-- import_manager.php
|       `-- export_manager.php
`-- cli/
    `-- seed_imscp.php

meko-lms/local/accees_sync_api/
|-- version.php
|-- settings.php
|-- lang/en/local_accees_sync_api.php
|-- db/access.php
|-- db/events.php
|-- classes/
|   |-- contract/
|   |   `-- lom_metadata_interface.php  # Khoa tao o T0
|   |-- route/api/
|   |   |-- courses_read.php            # UC-LMS-10 (Thao)
|   |   |-- courses_update.php          # UC-LMS-11 (Thao)
|   |   |-- enrolments_create.php       # UC-LMS-12 (Yen)
|   |   |-- enrolments_delete.php       # UC-LMS-13 (Yen)
|   |   `-- results_read.php            # UC-LMS-14 (Yen)
|   |-- manager/
|   |-- observer/
|   |   `-- completion_observer.php     # UC-LMS-14 Webhook trigger (Yen)
|   `-- task/
|       `-- send_webhook_task.php       # Adhoc task gui webhook (Yen)
`-- cli/
    `-- seed_base.php                   # Khoa tao o T0
```

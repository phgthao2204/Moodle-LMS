# Bo Ban Giao: [MOD-LMS-04][UC-LMS-08] API Import goi khoa hoc .imscc chuan ACCEES-LOM (v1.0)

- Endpoint: `POST /lms/api/rest/v2/local_accees_imscp/packages/import`
- Scope OAuth 2.0: `local_accees_imscp:imscp:import`
- Capability: `local/accees_imscp:import`
- Quy cach route: `cookies: false`, phan biet ro 401 UNAUTHORIZED va 403 FORBIDDEN.
- Phien ban: ACCEES-LOM v1.0 (Luu 5 core custom fields tu du lieu thuc, ho tro plugin setting default_category_id, tich hop truc tiep qua canonical lom_metadata_manager tren develop).

---

## 1. Danh muc Fixtures & File Mau

- `ACCEES-CS101.imscc`: File package IMS Common Cartridge chuan mau chua `imsmanifest.xml` (metadata 9 nhom LOM, 4 bai hoc HTML trong `webcontent/`).
- `invalid_no_manifest.imscc`: File archive thieu `imsmanifest.xml` dung de kiem thu ma loi `400 INVALID_PACKAGE`.
- `invalid_syntax.imscc`: File archive chua `imsmanifest.xml` sai cu phap XML dung de kiem thu ma loi `422 LOM_VALIDATION_FAILED`.
- `sample_import_request.json`: Payload JSON mau khi import qua `package_url`.
- `sample_response_201.json`: Mau payload phan hoi HTTP 201 Created thuc te.

---

## 2. Xac nhan Tren Schema OpenAPI Live (`/openapi.json`)

Endpoint da duoc Moodle 5.3 dang ky thanh cong tai `http://meko.localhost/lms/api/rest/v2/openapi.json`:

```json
{
  "/local_accees_imscp/packages/import": {
    "post": {
      "description": "Upload or fetch an IMS Common Cartridge archive, import course structure and store LOM metadata.\n\nRequired OAuth scopes:\n- all of:\n  - local_accees_imscp:imscp:import\n",
      "summary": "Import IMS Common Cartridge package",
      "tags": [
        "local_accees_imscp"
      ],
      "parameters": [],
      "responses": [],
      "security": [
        {
          "oauth2": [
            "local_accees_imscp:imscp:import"
          ]
        }
      ]
    }
  }
}
```

---

## 3. Ket Qua Kiem Thu Thuc Te (Test Evidence)

### 3.1 Happy Path 1: HTTP 201 Created (Upload .imscc multipart/form-data)

**Lenh curl:**
```bash
curl -i -X POST http://meko.localhost/lms/api/rest/v2/local_accees_imscp/packages/import \
  -H "Authorization: Bearer <access_token>" \
  -H "Accept: application/json" \
  -F "package_file=@ACCEES-CS101.imscc" \
  -F "overwrite=true"
```
*(Ghi chu: khong can truyen `category_id`, plugin se tu dong doc gia tri tu cau hinh `local_accees_imscp/default_category_id`).*

**Response HTTP 201 Created:**
```http
HTTP/1.1 201 Created
Server: nginx/1.31.8
Content-Type: application/json; charset=utf-8

{
    "course_id": 35,
    "shortname": "ACCEES-CS101",
    "fullname": "Nhap mon Khoa hoc Du lieu",
    "category_id": 1,
    "import_status": "completed",
    "modules_created": 4,
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

---

### 3.2 Happy Path 2: HTTP 201 Created (Tai qua URL tu xa - JSON Body)

**Lenh curl:**
```bash
curl -i -X POST http://meko.localhost/lms/api/rest/v2/local_accees_imscp/packages/import \
  -H "Authorization: Bearer <access_token>" \
  -H "Content-Type: application/json" \
  -d '{
    "package_url": "http://meko.localhost/samples/ACCEES-CS101.imscc",
    "category_id": 1,
    "overwrite": true
  }'
```

---

### 3.3 Error Path: HTTP 400 INVALID_PACKAGE (Thieu file va package_url)

**Lenh curl:**
```bash
curl -i -X POST http://meko.localhost/lms/api/rest/v2/local_accees_imscp/packages/import \
  -H "Authorization: Bearer <access_token>" \
  -H "Content-Type: application/json" \
  -d '{}'
```

**Response HTTP 400 Bad Request:**
```json
{
    "error": {
        "code": "INVALID_PACKAGE",
        "message": "Either package_file (uploaded .imscc/.zip archive) or package_url must be provided",
        "details": {
            "received_fields": []
        }
    }
}
```

---

### 3.4 Error Path: HTTP 401 UNAUTHORIZED (Thieu token hoac token khong hop le)

**Lenh curl (Khong truyen token Authorization):**
```bash
curl -i -X POST http://meko.localhost/lms/api/rest/v2/local_accees_imscp/packages/import \
  -H "Content-Type: application/json" \
  -d '{}'
```

**Response HTTP 401 Unauthorized:**
```http
HTTP/1.1 401 Unauthorized
Server: nginx/1.31.8
Date: Tue, 17 Nov 2026 10:14:03 GMT
Content-Type: application/json; charset=utf-8
Content-Length: 147
Connection: keep-alive
X-Powered-By: PHP/8.4.26
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: POST
Access-Control-Allow-Headers: Content-Type, api_key, Authorization

{
    "error": {
        "code": "UNAUTHORIZED",
        "message": "Missing or invalid Bearer authentication token",
        "details": []
    }
}
```

---

### 3.5 Error Path: HTTP 403 FORBIDDEN (Da xac thuc nhung thieu capability local/accees_imscp:import)

**Lenh curl (User xac thuc thanh cong nhung khong co quyen tren danh muc hoac he thong):**
```bash
curl -i -X POST http://meko.localhost/lms/api/rest/v2/local_accees_imscp/packages/import \
  -H "Authorization: Bearer <user_without_permission_token>" \
  -H "Content-Type: application/json" \
  -d '{}'
```

**Response HTTP 403 Forbidden:**
```http
HTTP/1.1 403 Forbidden
Content-Type: application/json; charset=utf-8

{
    "error": {
        "code": "FORBIDDEN",
        "message": "User does not possess the required capability local/accees_imscp:import",
        "details": {
            "capability": "local/accees_imscp:import",
            "category_id": 1
        }
    }
}
```

---

### 3.6 Error Path: HTTP 409 COURSE_ALREADY_EXISTS (Khoa hoc da ton tai va overwrite=false)

**Lenh curl:**
```bash
curl -i -X POST http://meko.localhost/lms/api/rest/v2/local_accees_imscp/packages/import \
  -H "Authorization: Bearer <access_token>" \
  -H "Accept: application/json" \
  -F "package_file=@ACCEES-CS101.imscc" \
  -F "category_id=1" \
  -F "overwrite=false"
```

**Response HTTP 409 Conflict:**
```json
{
    "error": {
        "code": "COURSE_ALREADY_EXISTS",
        "message": "Course with shortname 'ACCEES-CS101' already exists",
        "details": {
            "shortname": "ACCEES-CS101",
            "existing_course_id": 35
        }
    }
}
```

---

### 3.7 Error Path: HTTP 422 LOM_VALIDATION_FAILED (XML sai cu phap hoac loi xac thuc LOM)

Khi goi tin XML bi loi cu phap hoac metadata khong hop le theo chuan ACCEES, he thong bat `\DomainException` va tra ve ma loi `422 LOM_VALIDATION_FAILED` (khong bao gio gay loi 500 INTERNAL_ERROR).

**Lenh curl:**
```bash
curl -i -X POST http://meko.localhost/lms/api/rest/v2/local_accees_imscp/packages/import \
  -H "Authorization: Bearer <access_token>" \
  -H "Accept: application/json" \
  -F "package_file=@invalid_syntax.imscc"
```

**Response HTTP 422 Unprocessable Entity:**
```json
{
    "error": {
        "code": "LOM_VALIDATION_FAILED",
        "message": "imsmanifest.xml contains invalid XML structure",
        "details": {
            "xml_errors": [
                "Opening and ending tag mismatch: unclosed_tag line 1 and manifest",
                "Premature end of data in tag manifest line 1"
            ]
        }
    }
}
```

---

### 3.8 Kiem thu Thuc Te qua OAuth 2.0 Client Credentials Flow (`/lms/oauth2/token`)

Kiem thu truc tiep tren moi truong devlocal sau khi chay seeder T0 (`seed_base.php`) voi OAuth 2.0 client `accees-dev-client`:

**1. Lay Access Token qua Client Credentials Endpoint:**
```bash
curl -i -X POST http://meko.localhost/lms/oauth2/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=client_credentials&client_id=YOUR_CLIENT_ID&client_secret=YOUR_CLIENT_SECRET&scope=local_accees_imscp:imscp:import"
```

**Response tu Token Endpoint (HTTP 200 OK thuc te):**
```http
HTTP/1.1 200 OK
Server: nginx/1.31.8
Date: Tue, 17 Nov 2026 10:10:23 GMT
Content-Type: application/json; charset=UTF-8
Content-Length: 783
Connection: keep-alive
X-Powered-By: PHP/8.4.26
Cache-Control: no-store
Pragma: no-cache

{
    "token_type": "Bearer",
    "expires_in": 3600,
    "access_token": "YOUR_ACCESS_TOKEN"
}
```

**2. Goi API import bang Access Token OAuth 2.0:**
```bash
curl -i -X POST http://meko.localhost/lms/api/rest/v2/local_accees_imscp/packages/import \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..." \
  -F "package_file=@ACCEES-CS101.imscc;type=application/zip" \
  -F "target_course_shortname=ACCEES-CS101-OAUTH2" \
  -F "category_id=34" \
  -F "overwrite=true"
```

**Response HTTP 201 Created thuc te:**
```http
HTTP/1.1 201 Created
Server: nginx/1.31.8
Date: Tue, 17 Nov 2026 10:13:34 GMT
Content-Type: application/json; charset=utf-8
Content-Length: 431
Connection: keep-alive
X-Powered-By: PHP/8.4.26
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: POST
Access-Control-Allow-Headers: Content-Type, api_key, Authorization

{
    "course_id": 36,
    "shortname": "ACCEES-CS101-OAUTH2",
    "fullname": "Nhap mon Khoa hoc Du lieu",
    "category_id": 34,
    "import_status": "completed",
    "modules_created": 4,
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

**Ket luan:**
- Luong OAuth 2.0 Client Credentials hoat dong hoan toan chinh xac va thong suot.
- Toan bo tien trinh tao khoa hoc, tao cac page module ben trong va ap dung 5 truong custom field ACCEES-LOM qua `\local_accees_sync_api\manager\lom_metadata_manager::apply_lom()` thanh cong 100%, khong bi vuong mac ve quyen hay validation.

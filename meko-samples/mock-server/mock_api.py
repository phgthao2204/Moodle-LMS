#!/usr/bin/env python3
"""
MEKOSOFT DLE (MEKO-TMS <-> MEKO-LMS) Local Mock API Server (Zero external dependencies).
Serves realistic HTTP 200 / 401 / 404 JSON mock responses matching docs/02B_API_MASTER_SPEC.md
so Liferay (TMS) and Frontend/QA developers can test without waiting for live Moodle WS.

Usage:
    python meko-samples/mock-server/mock_api.py
    # Listens on http://127.0.0.1:8099
"""

from http.server import BaseHTTPRequestHandler, HTTPServer
import hashlib
import hmac
import json
import os
import urllib.parse

PORT = int(os.environ.get("MEKO_MOCK_PORT", "8099"))
WEBHOOK_SECRET = os.environ.get("MEKO_WEBHOOK_SECRET", "meko-dev-webhook-secret")


MOODLE_WS_RESPONSES = {
    "local_meko_imscp_export_course": {
        "status": "success",
        "shortname": "NLS-01-IMS",
        "filename": "NLS-01-IMS-20261116.imscc",
        "file_size_bytes": 1842310,
        "download_url": "http://meko.localhost/lms/webservice/pluginfile.php/1/local_meko_imscp/export/NLS-01-IMS-20261116.imscc",
        "imscc_sha256": "e3b0c44298fc1c149afbf4c8996fb924",
        "stats": {"sections": 4, "pages": 6, "resources": 4, "quizzes": 1, "questions": 10},
    },
    "local_meko_imscp_create_course": {
        "status": "success",
        "moodle_course_id": 21,
        "shortname": "MK-2026-01",
        "course_url": "http://meko.localhost/lms/course/view.php?id=21",
        "idempotent_reused": False,
        "imported_sections": 4,
        "imported_modules": {"pages": 6, "resources": 4, "quizzes": 1},
        "linked_competencies": 2,
    },
    "local_meko_sync_get_courses": {
        "total": 1,
        "page": 1,
        "courses": [
            {
                "moodle_course_id": 8,
                "shortname": "NLS-01-IMS",
                "fullname": "Dong goi Hoc lieu Chuan IMS CP & Nhung Tai nguyen So",
                "summary": "Khoa hoc huong dan chuan dong goi IMS CC, QTI XML va sieu du lieu LOM.",
                "category_idnumber": "DIGCOMP_STU",
                "category_name": "Nang luc so Sinh vien",
                "startdate": 1794441600,
                "enddate": 1797033600,
                "visible": 1,
                "course_image_url": "http://meko.localhost/lms/pluginfile.php/45/course/overviewfiles/nls01ims.png",
                "customfields": {
                    "accees_course_status": "PUBLISHED",
                    "accees_delivery_mode": "Blended Learning",
                    "accees_duration": "3 Credits - 60 Hours",
                    "accees_institution": "Mekosoft Training Hub",
                    "accees_is_featured": True,
                },
                "teachers": [{"fullname": "PGS.TS. Tran Thanh Dien", "email": "dien.tran@mekosoft.vn"}],
                "competencies": [{"moodle_competency_id": 14, "idnumber": "SV_C1_1_N1", "shortname": "C1.1 Search & Filter (Level N1)"}],
                "quiz_gradepass": 7.0,
            }
        ],
    },
    "local_meko_sync_update_courses": {
        "status": "success",
        "moodle_course_id": 21,
        "updated_fields": ["visible", "accees_course_status"],
    },
    "local_meko_sync_enrol_users_bulk": {
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
                "result": "created_and_enrolled",
            }
        ],
        "errors": [],
    },
    "local_meko_sync_get_learner_results": {
        "moodle_course_id": 21,
        "results": [
            {
                "moodle_userid": 105,
                "email": "an.nguyen@mekosoft.vn",
                "course_completed": True,
                "final_grade": 8.5,
                "max_grade": 10.0,
                "competencies": [{"idnumber": "SV_C1_1_N1", "proficient": True, "grade": 8.5}],
                "badges": [{"id": 12, "name": "Chung nhan Hoan thanh Nang luc so C1.1", "issued_at": 1795000000}],
            }
        ],
    },
}


class MockApiHandler(BaseHTTPRequestHandler):
    def _send_json(self, code: int, payload: dict) -> None:
        raw = json.dumps(payload, ensure_ascii=False, indent=2).encode("utf-8")
        self.send_response(code)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(raw)))
        self.end_headers()
        self.wfile.write(raw)

    def _read_body(self) -> bytes:
        length = int(self.headers.get("Content-Length", "0") or 0)
        return self.rfile.read(length) if length > 0 else b""

    def do_GET(self) -> None:
        parsed = urllib.parse.urlparse(self.path)
        qs = urllib.parse.parse_qs(parsed.query)
        path = parsed.path

        if path in ("/", "/health"):
            return self._send_json(200, {
                "service": "MEKO-DLE Mock API Server",
                "port": PORT,
                "moodle_ws_functions": list(MOODLE_WS_RESPONSES.keys()),
                "tms_rest_endpoints": [
                    "POST /o/meko-tms/api/webhooks/badge-awarded",
                    "GET /o/meko-tms/api/certificates/verify?code=8f4a29b7c1e04d11a89c",
                ],
                "tms_portlet_resources": [
                    "GET /tms/catalog/list",
                    "POST /tms/enroll/self",
                    "POST /tms/enroll/excel-preview",
                    "POST /tms/enroll/excel-confirm",
                    "POST /tms/enroll/decide",
                    "POST /tms/logs/email-resend",
                    "GET /tms/logs/list",
                    "GET /tms/profile/summary",
                ],
            })

        if path == "/o/meko-tms/api/certificates/verify":
            code = (qs.get("code") or [""])[0]
            if code == "8f4a29b7c1e04d11a89c":
                return self._send_json(200, {
                    "valid": True,
                    "status": "ISSUED",
                    "cert_serial_code": "MEKO-2026-00018",
                    "qr_verify_code": code,
                    "student_code": "MK2026001",
                    "student_name": "Nguyen Van An",
                    "course_code": "MK-2026-01",
                    "course_title": "Dong goi Hoc lieu Chuan IMS CP",
                    "final_grade": 8.5,
                    "badge_name": "Chung nhan Hoan thanh Nang luc so C1.1",
                    "issued_date": "2026-11-16",
                    "revoke_reason": "",
                })
            return self._send_json(404, {"valid": False, "status": "NOT_FOUND"})

        if path == "/tms/catalog/list":
            return self._send_json(200, {
                "status": "ok",
                "total": 1,
                "page": 1,
                "items": [{
                    "courseId": 101,
                    "courseCode": "NLS-01-IMS",
                    "courseTitle": "Dong goi Hoc lieu Chuan IMS CP",
                    "thumbnailUrl": "http://meko.localhost/lms/pluginfile.php/45/course/overviewfiles/nls01ims.png",
                    "deliveryFormat": "Blended Learning",
                    "creditHours": 3,
                    "startDate": "2026-11-20",
                    "status": "IN_PROGRESS",
                    "enrolledCount": 24,
                    "maxEnrollment": 60,
                    "isEnrolledByMe": False,
                    "moodleCourseUrl": "http://meko.localhost/lms/course/view.php?id=8",
                }],
            })

        if path == "/tms/logs/list":
            return self._send_json(200, {
                "status": "ok",
                "logType": (qs.get("logType") or ["EMAIL"])[0],
                "total": 1,
                "items": [{
                    "id": 88,
                    "templateCode": "ENROLLMENT_APPROVED",
                    "recipientEmail": "an.nguyen@mekosoft.vn",
                    "status": "SENT",
                    "sentDate": "2026-11-16T10:15:00Z",
                    "errorMessage": "",
                }],
            })

        if path == "/tms/profile/summary":
            return self._send_json(200, {
                "status": "ok",
                "student": {"studentCode": "MK2026001", "fullName": "Nguyen Van An", "email": "an.nguyen@mekosoft.vn", "organization": "Phong Ky thuat Phan mem"},
                "enrollments": [{"courseCode": "MK-2026-01", "courseTitle": "Dong goi Hoc lieu Chuan IMS CP", "progressPercent": 100, "finalGrade": 8.5, "status": "PASSED"}],
                "competencyProgress": [{"domainCode": "C1", "domainName": "Thong tin & Du lieu", "achievedCount": 3, "totalCount": 4}],
                "certificates": [{"certId": 18, "certSerialCode": "MEKO-2026-00018", "qrVerifyCode": "8f4a29b7c1e04d11a89c", "issuedDate": "2026-11-16", "status": "ISSUED"}],
            })

        return self._send_json(404, {"status": "error", "code": "not_found", "message": f"Unknown GET endpoint: {path}"})

    def do_POST(self) -> None:
        parsed = urllib.parse.urlparse(self.path)
        qs = urllib.parse.parse_qs(parsed.query)
        path = parsed.path
        body = self._read_body()

        if path == "/lms/webservice/rest/server.php":
            form = urllib.parse.parse_qs(body.decode("utf-8", errors="ignore"))
            wsfn = (qs.get("wsfunction") or form.get("wsfunction") or [""])[0]
            if wsfn in MOODLE_WS_RESPONSES:
                return self._send_json(200, MOODLE_WS_RESPONSES[wsfn])
            return self._send_json(400, {"status": "error", "code": "invalid_wsfunction", "message": f"Unknown wsfunction: {wsfn}"})

        if path == "/o/meko-tms/api/webhooks/badge-awarded":
            sig_header = self.headers.get("X-MEKO-Signature", "")
            expected = "sha256=" + hmac.new(WEBHOOK_SECRET.encode("utf-8"), body, hashlib.sha256).hexdigest()
            if sig_header and sig_header != expected:
                return self._send_json(401, {"status": "error", "code": "invalid_signature", "message": "HMAC-SHA256 mismatch"})
            return self._send_json(200, {"status": "ok", "cert_id": 18, "cert_serial_code": "MEKO-2026-00018", "qr_verify_code": "8f4a29b7c1e04d11a89c"})

        if path == "/tms/enroll/self":
            return self._send_json(200, {"status": "ok", "enrollmentId": 41, "enrollmentCode": "ENR-2026-0041", "enrollmentStatus": "PENDING"})

        if path == "/tms/enroll/excel-preview":
            return self._send_json(200, {
                "status": "ok",
                "totalRows": 2,
                "validCount": 2,
                "errorCount": 0,
                "rows": [
                    {"rowNum": 2, "studentCode": "MK2026001", "fullName": "Nguyen Van An", "email": "an.nguyen@mekosoft.vn", "phone": "0901234567", "organization": "Phong KT", "valid": True, "error": ""},
                    {"rowNum": 3, "studentCode": "MK2026002", "fullName": "Tran Thi Binh", "email": "binh.tran@mekosoft.vn", "phone": "0907654321", "organization": "Phong DT", "valid": True, "error": ""},
                ],
            })

        if path == "/tms/enroll/excel-confirm":
            return self._send_json(200, {"status": "ok", "imported": 2, "queuedBatchId": "BATCH-20261116-01", "syncStatus": "PENDING"})

        if path == "/tms/enroll/decide":
            data = json.loads(body.decode("utf-8") or "{}") if body else {}
            if data.get("decision") == "REJECT" and not data.get("rejectReason"):
                return self._send_json(400, {"status": "error", "code": "reject_reason_required", "message": "rejectReason is required when rejecting"})
            return self._send_json(200, {"status": "ok", "processedCount": len(data.get("enrollmentIds", [1])), "decision": data.get("decision", "APPROVE"), "emailTemplate": "ENROLLMENT_APPROVED" if data.get("decision", "APPROVE") == "APPROVE" else "ENROLLMENT_REJECTED"})

        if path == "/tms/logs/email-resend":
            return self._send_json(200, {"status": "ok", "oldEmailLogId": 88, "newEmailLogId": 99, "mailStatus": "SENT"})

        return self._send_json(404, {"status": "error", "code": "not_found", "message": f"Unknown POST endpoint: {path}"})


if __name__ == "__main__":
    server = HTTPServer(("127.0.0.1", PORT), MockApiHandler)
    print(f"[MEKO-DLE Mock API] Listening at http://127.0.0.1:{PORT} (HMAC secret='{WEBHOOK_SECRET}')")
    server.serve_forever()

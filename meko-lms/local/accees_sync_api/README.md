# local_accees_sync_api (`MOD-LMS-05`)

Plugin Moodle 5.3 REST API v2 đồng bộ khóa học, ghi danh và kết quả học tập:
- `UC-LMS-10`: `GET /lms/api/rest/v2/local_accees_sync_api/courses` (`classes/route/api/courses_read.php`) — Thảo
- `UC-LMS-11`: `PATCH /lms/api/rest/v2/local_accees_sync_api/courses/{courseid}` (`classes/route/api/courses_update.php`) — Thảo
- `UC-LMS-12`: `POST /lms/api/rest/v2/local_accees_sync_api/courses/{courseid}/enrolments` (`classes/route/api/enrolments_create.php`) — Yến
- `UC-LMS-13`: `DELETE /lms/api/rest/v2/local_accees_sync_api/courses/{courseid}/enrolments` (`classes/route/api/enrolments_delete.php`) — Yến
- `UC-LMS-14`: `GET /lms/api/rest/v2/local_accees_sync_api/courses/{courseid}/results` + Event Webhook (`classes/route/api/results_read.php`) — Yến

Xem đặc tả chuẩn tại `docs/API_MASTER_DOCS_ACCEES_LMS_V1.md` và nộp bộ `a-b-c` theo `.github/PULL_REQUEST_TEMPLATE.md`.

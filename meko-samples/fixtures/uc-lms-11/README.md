# UC-LMS-11 — Update course and LOM metadata (v1.0)

Owner — Thị Phương Thảo Trần. Reviewer/QA — Thanh Yến Khoa Nguyễn.

## (a) Test data and setup

Use the Moodle DevLocal stack configured for REST v2 and with the five ACCEES course custom fields seeded.
The smoke test creates a disposable hidden course and a five-minute admin PAT, then removes both.
Run from the repository root with the updated plugin deployed and the Moodle cache purged.

```powershell
docker cp meko-samples/fixtures/uc-lms-11/smoke.php devlocal-moodle:/tmp/uc11-smoke.php
docker exec -e ACCEES_API_BASE=http://gateway/lms -e ACCEES_HTTP_HOST=meko.localhost devlocal-moodle php /tmp/uc11-smoke.php
```

## (b) Requests and test evidence

`PATCH /lms/api/rest/v2/local_accees_sync_api/courses/{courseid}`

Use a disposable course ID in `COURSE_ID` and a Moodle PAT with scope `local_accees_sync_api:course:write`.

```bash
curl -i -X PATCH -H "Authorization: Bearer ${TOKEN}" -H 'Content-Type: application/json' \
  --data-binary @meko-samples/fixtures/uc-lms-11/request-200.json \
  "http://meko.localhost/lms/api/rest/v2/local_accees_sync_api/courses/${COURSE_ID}"
```

- [200](response-200.json) — Partial update via both custom_fields and lom, with fullname verified in the database.
- [422](response-422.json) — Rejected accees_is_featured set to the string "yes".
- [404](response-404.json) — Unknown course ID.
- 401 — Missing bearer token.

The original fixtures use PAT. After the T0 rebase, OAuth2 Client Credentials passed
PATCH 200/422/404 and GET list/detail 200 with version headers 1.0 and 1.1 on DevLocal.

For OAuth, set ACCEES_OAUTH_CLIENT_ID and ACCEES_OAUTH_CLIENT_SECRET in the environment.
The client must grant both course scopes. The script defaults to the token endpoint
at /oauth2/token under ACCEES_API_BASE. ACCEES_OAUTH_TOKEN_URL can override it.
Set MOODLE_CONFIG to the server's config.php path and ACCEES_API_BASE to the LMS root.
Set ACCEES_HTTP_HOST only when overriding Host. ACCEES_API_VERSION defaults to 1.0.

Newman 6.2.1 supplemental run: 4 requests, 10 assertions, 0 failures. PATCH returned
`accees_course_status=published` and `accees_delivery_mode=blended`. A subsequent GET
confirmed both persisted, and PATCH still returned 422 for invalid featured and 404 for a missing course.

## (c) System and integration notes

- Requires the existing course-context capability `local/accees_sync_api:updatecourse`.
- Persists supported v1.0 custom fields with Moodle's custom field API. Unmapped LOM data such as educational metadata is reserved for v1.1.
- Course and metadata writes share a Moodle transaction. No new database table or setting.

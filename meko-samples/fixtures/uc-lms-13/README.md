# UC-LMS-13 — Remove manual enrolment

## (a) Test data and setup

PR #8 is updated against `develop` at `3d49610`, replacing its original custom-router implementation.
Only the DELETE endpoint, shared native `enrol:manage` scope, language strings and these fixtures are included.
The scope identifiers and language strings match UC-LMS-12. No custom `route.php`, `authenticator.php` or DevLocal changes.

Deploy `local_accees_sync_api` to a Moodle 5.3 instance with its Composer dependencies installed,
run `admin/cli/upgrade.php --non-interactive`, then purge caches to discover the route and scope.
Run the shared `local/accees_sync_api/cli/seed_base.php` first. It provisions `accees-dev-client`,
`accees_student1`, `accees_service` and the existing `local/accees_sync_api:unenrol` capability.

The smoke test requests a real OAuth2 Client Credentials token from the seeded dev client using a temporary
client secret, creates a disposable course, and removes both the course and temporary secret afterward.
The native Client Credentials grant uses Moodle's system identity. A separate short-lived student PAT
tests the authenticated identity without the unenrol capability and is deleted afterward.

```powershell
docker cp meko-samples/fixtures/uc-lms-13/smoke.php devlocal-moodle:/tmp/uc13-smoke.php
docker exec -e ACCEES_API_BASE=http://127.0.0.1/lms -e ACCEES_HTTP_HOST=meko.localhost devlocal-moodle php /tmp/uc13-smoke.php
```

Override `MOODLE_CONFIG` for a different config path. `ACCEES_API_BASE` defaults to Moodle's wwwroot;
`ACCEES_HTTP_HOST` is optional. Run only on a development/test instance.

## (b) Requests and evidence

Get a token with the seeded client credentials, using scope `local_accees_sync_api:enrol:manage`:

```bash
curl --fail-with-body -X POST "$LMS_BASE/oauth2/token" \
  --data-urlencode 'grant_type=client_credentials' \
  --data-urlencode "client_id=$CLIENT_ID" \
  --data-urlencode "client_secret=$CLIENT_SECRET" \
  --data-urlencode 'scope=local_accees_sync_api:enrol:manage'

curl -i -X DELETE -H "Authorization: Bearer $TOKEN" \
  "$LMS_BASE/api/rest/v2/local_accees_sync_api/courses/$COURSE_ID/enrolments?username=accees_student1"

curl -i -X DELETE -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  --data '{"username":"accees_student1","ignore_missing":true}' \
  "$LMS_BASE/api/rest/v2/local_accees_sync_api/courses/$COURSE_ID/enrolments"
```

Use exactly one of `username` or positive integer `user_id`. Query identifiers take precedence over body identifiers;
`ignore_missing` in the query takes precedence over the body flag. Malformed JSON, invalid/absent identifiers or
both identifiers return `400 MISSING_IDENTIFIER`. JSON bodies must be objects.

Verified on DevLocal on 2026-11-18:

Actual successful response from a disposable smoke-test course (removed after the run):

```json
{"course_id":23,"user_id":11,"username":"accees_student1","enrolment_status":"unenrolled"}
```

| Case | HTTP / result |
| --- | --- |
| OAuth2 Client Credentials token | 200 |
| Missing authentication | 401 UNAUTHORIZED |
| Student PAT with correct scope, missing capability | 403 FORBIDDEN |
| Missing, ambiguous or invalid identifier; malformed JSON | 400 MISSING_IDENTIFIER |
| Missing course | 404 COURSE_NOT_FOUND |
| Missing user | 404 USER_NOT_FOUND |
| Query username/user_id, body username/user_id | 200 |
| Repeated DELETE; explicit ignore_missing=false | 404 ENROLMENT_NOT_FOUND |
| ignore_missing=true via query or JSON body | 200 |
| Query identifier overrides body identifier | 200 |
| OpenAPI DELETE path, parameters, 200 response and OAuth scope | 200 |
| Suspended enrolment in disabled manual instance | Removed |
| Existing self-enrolment | Preserved |

The smoke test asserts the entire success payload and verifies database removal of the manual enrolment.
Regression checks: UC-LMS-11 PATCH 200/422/404 and GET list/detail 200; PHP syntax across LMS and fixtures;
LMS emoji/legacy-domain checks; theme build and 6 tests; workspace Gradle `test`.

## (c) System and integration notes

Uses Moodle Native Router, `scopeset(new manage())`, PSR-7 requests and `payload_response`.
Authentication attributes are checked before the existing course-context unenrol capability.
Native middleware validates bearer tokens and scopes; its own authentication/scope failures retain Moodle's error format.
Unenrolment calls Moodle's manual plugin inside a delegated transaction, including inactive/suspended manual enrolments.
Other enrolment methods remain intact; `unenrolled` describes the manual enrolment only.
No new capability, table, setting, OAuth implementation or version-specific behaviour is introduced.

# UC-LMS-14: Learner results and signed webhooks

PR #9 is rebuilt from `origin/develop` at `2f8193b`, after PR #5 and PR #8 were merged.
The old UC-LMS-12/13 commits from the `4a408db` base are not replayed.
There are no changes to `meko-devlocal/*`, custom `route.php`, `authenticator.php`, or `settings.php`.
Existing webhook settings, capability definitions and language strings are retained.
The existing event registrations are enabled with `on_course_completed` and `on_badge_awarded` callbacks.

## Setup and pull API

Deploy the plugin to Moodle 5.3 with its Composer dependencies, run
`admin/cli/upgrade.php --non-interactive`, purge caches, then run
`local/accees_sync_api/cli/seed_base.php`. The existing seed already provisions the result scope for these clients.

```bash
curl --fail-with-body -X POST "$LMS_BASE/oauth2/token" \
  --data-urlencode 'grant_type=client_credentials' \
  --data-urlencode "client_id=$CLIENT_ID" \
  --data-urlencode "client_secret=$CLIENT_SECRET" \
  --data-urlencode 'scope=local_accees_sync_api:result:read'

curl --fail-with-body -H "Authorization: Bearer $TOKEN" \
  "$LMS_BASE/api/rest/v2/local_accees_sync_api/courses/$COURSE_ID/results"
```

For DevLocal, `LMS_BASE=http://meko.localhost/lms`; the token endpoint is `/lms/oauth2/token`.
Use the `accees-dev-client` credentials provisioned by `seed_base.php`.
The authenticated identity must have `local/accees_sync_api:viewresults` in the course context.
The native Client Credentials grant uses Moodle's system identity.

| Query parameter | Default | Constraint |
| --- | --- | --- |
| `page` | 1 | Integer, 1–1000000 |
| `per_page` | 50 | Integer, 1–100 |
| `completed_only` | false | Boolean |
| `username` | absent | Nonempty enrolled student username |
| `user_id` | absent | Positive enrolled student ID; mutually exclusive with username |

Responses include progress, completion time, final grade and issued badges.
`X-ACCEES-API-Version` accepts `1.0` and `1.1`, defaulting to `1.0`.
Optional query values injected as `null` by Moodle Router are treated as absent.

## Webhook delivery

Use the existing `webhook_enabled`, `webhook_url` and `webhook_secret` settings.
Course completion and course badge issuance queue an asynchronous task.
The receiver verifies `X-ACCEES-Signature: sha256=<HMAC-SHA256(raw body, secret)>`.
Non-2xx responses and transport errors propagate to Moodle's adhoc retry handling.
The event ID is preserved on retry; receivers should deduplicate by that ID.

## Reproducible integration test

Run only on a development/test instance. The test creates a temporary course, grades and badge,
uses a temporary secret on the seeded OAuth client, and creates a student PAT for the capability check.
It restores webhook settings and removes its course, badge, PAT, client secret and queued tasks in `finally`.
The receiver writes test payloads to `/tmp/uc14-webhook.json`; do not deploy it as a public endpoint.
The script permits the loopback receiver only in the CLI process's in-memory curl security settings.

```powershell
docker cp meko-samples/fixtures/uc-lms-14/smoke.php devlocal-moodle:/tmp/uc14-smoke.php
docker cp meko-samples/fixtures/uc-lms-14/receiver.php devlocal-moodle:/tmp/uc14-receiver.php
docker exec -d devlocal-moodle php -S 127.0.0.1:8099 /tmp/uc14-receiver.php
docker exec -e ACCEES_API_BASE=http://127.0.0.1/lms -e ACCEES_HTTP_HOST=meko.localhost devlocal-moodle php /tmp/uc14-smoke.php
```

Stop the temporary PHP receiver after testing. `MOODLE_CONFIG` can override the config path.
This change was verified on 2026-11-18 in an isolated QA container using the DevLocal Moodle runtime
and this branch's plugin, without overwriting the plugin mounted by the original workspace.

| Case | Verified result |
| --- | --- |
| OAuth2 Client Credentials with result scope | HTTP 200, access token issued |
| Missing authentication / invalid bearer | HTTP 401 |
| Correct scope, student PAT without capability | HTTP 403 FORBIDDEN |
| OAuth token with course scope only | HTTP 401 access_denied from native scope middleware |
| Missing course / learner | HTTP 404 COURSE_NOT_FOUND / USER_NOT_FOUND |
| Invalid pagination, boolean or ambiguous learner | HTTP 400 INVALID_FIELD |
| Unsupported API version | HTTP 400 UNSUPPORTED_API_VERSION |
| Pull results, username/user ID filters and pagination | HTTP 200, payload asserted |
| Grade fixture | raw 80, max 100, percentage 80 |
| Completed-only filter after completion | Completed learner, progress 100 |
| OpenAPI route and OAuth scope | Registered correctly |
| Course completion and badge events | Both queue webhook tasks |
| Real HTTP webhook push for both events | HTTP 204; payload and HMAC verified |
| Receiver returns HTTP 503 | Task throws for Moodle retry |
| Re-execution after failure | Successful delivery with unchanged event ID |
| Disabled webhooks | No new task queued |
| Pull API after badge issuance | Issued badge present |

Regression validation: UC-LMS-13 smoke suite and UC-LMS-11 PATCH/GET smoke suite passed;
PHP syntax checks passed for all 81 LMS/fixture PHP files; all 6 theme tests and workspace Gradle `test` passed.
Moodle PHPUnit is not installed in the DevLocal runtime; integration checks above use the real HTTP API and database.

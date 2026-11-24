# UC-LMS-10 fixtures

Fixture and verification evidence for the v1.0 course read API.

## Task 4399 batch and API version regression

The default LOM mapper reads custom fields and course competencies once per page.
Category names, module counts and overview files are also loaded in batches.
The existing `lom_metadata_interface` is unchanged. Mappers can optionally implement
`bulk_lom_metadata_interface`; legacy mappers continue receiving per-course calls.

Both course list and detail pass `X-ACCEES-API-Version` to the mapper. Omitted header
defaults to `1.0`; supported values are `1.0` and `1.1`. Unsupported values return
HTTP 400 `INVALID_QUERY_PARAM`. Both versions retain the nine LOM groups;
the API version is independent of `schema_version: LOM-ACCEES-1.0`.

On a seeded, disposable Moodle development instance:

```powershell
docker cp meko-samples/fixtures/uc-lms-10/batch-smoke.php devlocal-moodle:/tmp/uc10-batch-smoke.php
docker exec devlocal-moodle php /tmp/uc10-batch-smoke.php
```

This script creates 100 temporary database course records in a transaction and
rolls them back in `finally`. It compares query counts for page sizes 1 and 100,
list/detail payloads for both API versions, image/custom field metadata, shared
competencies, empty batches/pages, and version propagation to old and batch mappers.
It requires at least one existing course; competency checks use seeded competencies
when available. Do not run fixture creation on a production system.

Run `npm run test:uc10` in `meko-samples/tests/playwright` for HTTP regression,
including both API versions and invalid version handling.

## (a) Test data and setup

- Moodle DevLocal seed data from `meko-devlocal/moodle/seed_real_data.php`.
- Happy-path account: `admin` with capability `local/accees_sync_api:viewcourses`.
- Forbidden-path account: `student1`, granted the API scope but not the capability.
- API scope: `local_accees_sync_api:course:read`.
- Representative course: ID `12`, shortname `NLS-03-AI`.
- Tokens used during verification were temporary one-hour PATs and were deleted after the run.

The Moodle image must have the Composer dependencies from its root `composer.lock` installed. The DevLocal Apache alias also
requires the rewrite rule in `meko-devlocal/moodle/apache-lms.conf` for clean `/lms/api/rest/v2/...` URLs.

## (b) API calls and evidence

```bash
BASE_URL="http://meko.localhost/lms/api/rest/v2/local_accees_sync_api/courses"

curl -H "Authorization: Bearer ${TOKEN}" "${BASE_URL}?page=1&per_page=2"
curl -H "Authorization: Bearer ${TOKEN}" "${BASE_URL}/12"
curl -H "Authorization: Bearer ${TOKEN}" \
  "${BASE_URL}?institution=MEKOSOFT%20AI%20Center&is_featured=true"
```

Verified on 2026-11-17:

| Case | Expected | Fixture |
| --- | ---: | --- |
| Paginated list | 200 | `list-200.json` |
| Course detail | 200 | `detail-200.json` |
| `institution` + `is_featured` filters | 200 | `filter-200.json` |
| Invalid `per_page=101` | 400 | `invalid-query-400.json` |
| Missing credentials | 401 | `unauthorized-401.json` |
| Scope present but capability missing | 403 | `forbidden-403.json` |
| Unknown course ID | 404 | `not-found-404.json` |

Both routes are present in `/lms/api/rest/v2/openapi.json`:

- `/local_accees_sync_api/courses`
- `/local_accees_sync_api/courses/{courseid}`

## (c) System and integration notes

- No new database table, setting, or observer was added.
- The existing system capability `local/accees_sync_api:viewcourses` is enforced.
- The v1.0 LOM mapper reads the five ACCEES course custom fields and returns all nine LOM category blocks.
- LOM writes remain the responsibility of UC-LMS-11.
- The endpoint excludes the Moodle site course and orders results by `timemodified DESC, id ASC`.

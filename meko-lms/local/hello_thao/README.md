# Hello Thao API

Moodle REST API v2 plugin with a fixed Hello World response.

Install this directory as `local/hello_thao` in the Moodle application,
run the Moodle upgrade, and purge caches to discover the route.

## Request

```http
GET /api/rest/v2/local_hello_thao/hello
Accept: application/json
```

When Moodle is hosted under `/lms`, the URL is:

```text
http://meko.localhost/lms/api/rest/v2/local_hello_thao/hello
```

No path, query or body parameters are needed. Authentication follows the
Moodle REST API v2 router configuration on the target instance.

## Response

HTTP 200:

```json
{"developer":"Thảo","message":"Hello Thảo"}
```

`developer` is the developer name. `message` is the fixed greeting.

## Phase 2 and Phase 3

`GET /api/rest/v2/local_hello_thao/courses` returns
`{"developer":"Thảo","courses":[{"id":2,"fullname":"Toán 10","shortname":"T10"}]}`.
The course values come from Moodle, not from fixed sample data. An empty list is `[]`.

`GET /api/rest/v2/local_hello_thao/courses/{courseid}` accepts a required integer
course ID and returns `{"developer":"Thảo","course":{"id":2,"fullname":"Toán 10","shortname":"T10"}}`.
An unknown course returns HTTP 404 with
`{"error":{"code":"COURSE_NOT_FOUND","message":"Course not found."}}`.

These simple endpoints expose only visible courses and exclude the site front page.
Hidden courses are also treated as not found. No query or body is required.

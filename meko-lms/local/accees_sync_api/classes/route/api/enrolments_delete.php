<?php
namespace local_accees_sync_api\route\api;

use core\param;
use core\router\route;
use core\router\schema\parameters\path_parameter;
use core\router\schema\parameters\query_parameter;
use core\router\schema\response\payload_response;
use core\router\schema\response\response;
use core\router\scope\scopeset;
use local_accees_sync_api\route\scope\enrol\manage;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class enrolments_delete {
    #[route(
        path: '/courses/{courseid}/enrolments',
        method: ['DELETE'],
        pathtypes: [new path_parameter(name: 'courseid', type: param::INT)],
        queryparams: [
            new query_parameter(name: 'username', type: param::TEXT),
            new query_parameter(name: 'user_id', type: param::INT),
            new query_parameter(name: 'ignore_missing', type: param::BOOL),
        ],
        responses: [
            new response(statuscode: 200, description: 'Manual enrolment removed, or already absent with ignore_missing=true.'),
            new response(statuscode: 400, description: 'MISSING_IDENTIFIER'),
            new response(statuscode: 401, description: 'UNAUTHORIZED'),
            new response(statuscode: 403, description: 'FORBIDDEN'),
            new response(statuscode: 404, description: 'COURSE_NOT_FOUND, USER_NOT_FOUND or ENROLMENT_NOT_FOUND'),
        ],
        cookies: false,
        summary: 'Remove a manual course enrolment',
    )]
    #[scopeset(new manage())]
    public function delete(ServerRequestInterface $request, ResponseInterface $response, string $courseid): payload_response {
        global $CFG, $DB;

        if ($request->getAttribute('api_token_id') === null && $request->getAttribute('oauth_user_id') === null) {
            return $this->error($request, $response, 401, 'UNAUTHORIZED', 'Authentication is required.');
        }

        $id = filter_var($courseid, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id || $id === SITEID || !$DB->record_exists('course', ['id' => $id])) {
            return $this->error($request, $response, 404, 'COURSE_NOT_FOUND', 'Course not found.');
        }
        if (!has_capability('local/accees_sync_api:unenrol', \context_course::instance($id))) {
            return $this->error($request, $response, 403, 'FORBIDDEN', 'Missing course unenrolment capability.');
        }

        $rawbody = trim((string) $request->getBody());
        $body = $rawbody === '' ? new \stdClass() : json_decode($rawbody);
        if (!$body instanceof \stdClass) {
            return $this->error($request, $response, 400, 'MISSING_IDENTIFIER', 'Expected a JSON object with a user identifier.');
        }
        $query = array_filter($request->getQueryParams(), static fn(mixed $value): bool => $value !== null);
        $keys = ['user_id' => true, 'username' => true];
        $queryidentifier = array_intersect_key($query, $keys);
        $bodyidentifier = array_intersect_key((array) $body, $keys);
        try {
            $queryidentifier = $queryidentifier ? $this->normalise_identifier($queryidentifier) : [];
            $bodyidentifier = $bodyidentifier ? $this->normalise_identifier($bodyidentifier) : [];
            if (!$queryidentifier && !$bodyidentifier) {
                throw new \InvalidArgumentException('Provide exactly one of user_id or username.');
            }
            // Compare matching keys before any lookup, even if neither user exists.
            foreach ($queryidentifier as $key => $value) {
                if (array_key_exists($key, $bodyidentifier) && $value !== $bodyidentifier[$key]) {
                    throw new \InvalidArgumentException('Conflicting user identifiers in query and body.');
                }
            }
        } catch (\InvalidArgumentException $e) {
            return $this->error($request, $response, 400, 'MISSING_IDENTIFIER', $e->getMessage());
        }
        $target = $queryidentifier ?: $bodyidentifier;
        if (isset($target['user_id'])) {
            $conditions = ['id' => $target['user_id'], 'deleted' => 0];
        } else {
            $conditions = ['username' => $target['username'], 'mnethostid' => $CFG->mnet_localhost_id, 'deleted' => 0];
        }
        $user = $DB->get_record('user', $conditions, 'id, username');
        if ($queryidentifier && $bodyidentifier && array_key_first($queryidentifier) !== array_key_first($bodyidentifier)) {
            // Different identifier kinds are accepted only when both identify the same user.
            if (!$user || (isset($bodyidentifier['user_id']) && $bodyidentifier['user_id'] !== (int) $user->id)
                    || (isset($bodyidentifier['username']) && $bodyidentifier['username'] !== $user->username)) {
                return $this->error($request, $response, 400, 'MISSING_IDENTIFIER',
                    'Conflicting user identifiers in query and body.');
            }
        }
        if (!$user) {
            return $this->error($request, $response, 404, 'USER_NOT_FOUND', 'User not found.');
        }

        require_once($CFG->libdir . '/enrollib.php');
        $instances = $DB->get_records_sql(
            "SELECT e.* FROM {enrol} e
               JOIN {user_enrolments} ue ON ue.enrolid = e.id
              WHERE e.courseid = :courseid AND e.enrol = :enrol AND ue.userid = :userid",
            ['courseid' => $id, 'enrol' => 'manual', 'userid' => $user->id],
        );
        $ignoremissing = filter_var($query['ignore_missing'] ?? $body->ignore_missing ?? false, FILTER_VALIDATE_BOOLEAN);
        if (!$instances && !$ignoremissing) {
            return $this->error($request, $response, 404, 'ENROLMENT_NOT_FOUND', 'Manual enrolment not found.');
        }
        if ($instances) {
            $transaction = $DB->start_delegated_transaction();
            $plugin = enrol_get_plugin('manual');
            foreach ($instances as $instance) {
                $plugin->unenrol_user($instance, $user->id);
            }
            $transaction->allow_commit();
        }

        return new payload_response(
            payload: [
                'course_id' => $id,
                'user_id' => (int) $user->id,
                'username' => $user->username,
                'enrolment_status' => 'unenrolled',
            ],
            request: $request,
            response: $response,
        );
    }

    /** Validate one identifier source without silently ignoring ambiguous values. */
    private function normalise_identifier(array $identifier): array {
        if (count($identifier) !== 1) {
            throw new \InvalidArgumentException('Provide exactly one of user_id or username per source.');
        }
        if (array_key_exists('user_id', $identifier)) {
            $value = $identifier['user_id'];
            $id = is_int($value) || is_string($value)
                ? filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
            if (!$id) {
                throw new \InvalidArgumentException('user_id must be a positive integer.');
            }
            return ['user_id' => $id];
        }
        if (!is_string($identifier['username']) || trim($identifier['username']) === '') {
            throw new \InvalidArgumentException('username must be a non-empty string.');
        }
        return ['username' => trim($identifier['username'])];
    }

    private function error(
        ServerRequestInterface $request,
        ResponseInterface $response,
        int $status,
        string $code,
        string $message,
    ): payload_response {
        return new payload_response(
            payload: ['error' => ['code' => $code, 'message' => $message]],
            request: $request,
            response: $response->withStatus($status),
        );
    }
}

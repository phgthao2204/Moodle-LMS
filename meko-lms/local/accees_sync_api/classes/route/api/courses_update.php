<?php
namespace local_accees_sync_api\route\api;

use core\router\route;
use core\param;
use core\router\schema\parameters\path_parameter;
use core\router\schema\response\response;
use core\router\scope\scopeset;
use core\router\schema\response\payload_response;
use local_accees_sync_api\manager\course_update_manager;
use local_accees_sync_api\route\scope\course\write;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** Partial course update for UC-LMS-11. */
class courses_update {
    /** Update one course using the authenticated Moodle identity. */
    #[route(
        path: '/courses/{courseid}',
        method: ['PATCH'],
        pathtypes: [new path_parameter(name: 'courseid', type: param::INT)],
        cookies: false,
        title: 'Update course information, LOM metadata and competencies',
        summary: 'Partially update a course and link existing competency codes',
        responses: [
            new response(statuscode: 200, description: 'Course updated.'),
            new response(statuscode: 400, description: 'INVALID_PAYLOAD'),
            new response(statuscode: 401, description: 'UNAUTHORIZED'),
            new response(statuscode: 403, description: 'FORBIDDEN'),
            new response(statuscode: 404, description: 'COURSE_NOT_FOUND'),
            new response(statuscode: 422, description: 'INVALID_CUSTOMFIELD_VALUE'),
            new response(statuscode: 500, description: 'INTERNAL_SERVER_ERROR'),
        ],
    )]
    #[scopeset(new write())]
    public function update(ServerRequestInterface $request, ResponseInterface $response, string $courseid): payload_response {
        global $DB;

        $status = 200;
        $code = '';
        $message = '';
        $payload = [];
        try {
            $id = filter_var($courseid, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($request->getAttribute('api_token_id') === null && $request->getAttribute('oauth_user_id') === null) {
                [$status, $code, $message] = [401, 'UNAUTHORIZED', 'Authentication is required.'];
            } else if (!$id || $id === SITEID || !$DB->record_exists('course', ['id' => $id])) {
                [$status, $code, $message] = [404, 'COURSE_NOT_FOUND', 'Course not found.'];
            } else if (!has_capability('local/accees_sync_api:updatecourse', \context_course::instance($id))) {
                [$status, $code, $message] = [403, 'FORBIDDEN', 'Missing course update capability.'];
            } else {
                $body = json_decode((string) $request->getBody(), false, 512, JSON_THROW_ON_ERROR);
                if (!$body instanceof \stdClass) {
                    throw new \InvalidArgumentException('Expected a JSON object.');
                }
                $version = $request->getHeaderLine('X-ACCEES-API-Version') ?: '1.0';
                if (!in_array($version, ['1.0', '1.1'], true)) {
                    throw new \InvalidArgumentException('Unsupported ACCEES API version.');
                }
                $payload = (new course_update_manager())->update($id, $body, $version);
            }
        } catch (\JsonException | \InvalidArgumentException $e) {
            [$status, $code, $message] = [400, 'INVALID_PAYLOAD', $e->getMessage()];
        } catch (\DomainException $e) {
            [$status, $code, $message] = [422, 'INVALID_CUSTOMFIELD_VALUE', $e->getMessage()];
        } catch (\Throwable $e) {
            // Do not expose SQL, filesystem paths or stack traces in the API response.
            [$status, $code, $message] = [500, 'INTERNAL_SERVER_ERROR', 'An unexpected error occurred while updating the course.'];
        }
        if ($status !== 200) {
            $payload = ['error' => ['code' => $code, 'message' => $message]];
        }
        return new payload_response($payload, $request, $response->withStatus($status));
    }
}

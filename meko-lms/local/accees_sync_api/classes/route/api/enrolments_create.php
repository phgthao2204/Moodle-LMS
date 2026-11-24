<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// @package    local_accees_sync_api
// @copyright  2026 Du an ACCEES (Erasmus+ CBHE-101179860)
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

namespace local_accees_sync_api\route\api;

use core\param;
use core\router\route;
use core\router\schema\parameters\path_parameter;
use core\router\schema\response\payload_response;
use core\router\scope\scopeset;
use local_accees_sync_api\exception\api_exception;
use local_accees_sync_api\manager\enrolment_manager;
use local_accees_sync_api\route\scope\enrol\manage;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Course enrolment endpoint for UC-LMS-12.
 */
class enrolments_create {

    /**
     * Enrol a user into a course.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $courseid
     * @param enrolment_manager|null $manager
     * @return payload_response
     */
    #[route(
        path: '/courses/{courseid}/enrolments',
        method: ['POST'],
        pathtypes: [
            new path_parameter(name: 'courseid', type: param::INT),
        ],
        cookies: false,
    )]
    #[scopeset(new manage())]
    public function create(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $courseid,
        ?enrolment_manager $manager = null,
    ): payload_response {
        global $DB;

        $id = filter_var($courseid, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        // 1. Check authentication (OAuth 2.0 or API token).
        if ($request->getAttribute('api_token_id') === null
            && $request->getAttribute('oauth_user_id') === null
            && $request->getAttribute('user') === null) {
            return $this->error($request, $response, 401, 'UNAUTHORIZED', 'Authentication is required.');
        }

        // 2. Check course existence.
        if (!$id || (defined('SITEID') && $id === SITEID) || !$DB->record_exists('course', ['id' => $id])) {
            return $this->error($request, $response, 404, 'COURSE_NOT_FOUND', 'Course not found.');
        }

        // 3. Check enrolment capability on course context using Moodle's active global user.
        if (!has_capability('local/accees_sync_api:enrol', \context_course::instance($id))) {
            return $this->error($request, $response, 403, 'FORBIDDEN', 'Missing course enrolment capability.');
        }

        // 4. Parse request payload and process enrolment.
        try {
            $body = json_decode((string)$request->getBody(), false, 512, JSON_THROW_ON_ERROR);
            if (!$body instanceof \stdClass) {
                return $this->error($request, $response, 400, 'MISSING_USER_FIELDS', 'Expected a JSON object.');
            }

            $version = $request->getHeaderLine('X-ACCEES-API-Version') ?: '1.0';
            $enrolmanager = $manager ?? new enrolment_manager();
            $result = $enrolmanager->enrol($id, $body, $version);

            return new payload_response(
                payload: $result['payload'],
                request: $request,
                response: $response->withStatus($result['status']),
            );
        } catch (\JsonException $e) {
            return $this->error($request, $response, 400, 'MISSING_USER_FIELDS', 'Invalid JSON body: ' . $e->getMessage());
        } catch (api_exception $e) {
            return $this->error($request, $response, $e->get_http_status(), $e->get_error_code(), $e->getMessage());
        } catch (\Throwable $e) {
            return $this->error($request, $response, 500, 'INTERNAL_SERVER_ERROR', $e->getMessage());
        }
    }

    /**
     * Build standard error response payload.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param int $status
     * @param string $code
     * @param string $message
     * @return payload_response
     */
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


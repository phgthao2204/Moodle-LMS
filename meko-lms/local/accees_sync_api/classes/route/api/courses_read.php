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

use core\context\system;
use core\param;
use core\router\route;
use core\router\schema\parameters\path_parameter;
use core\router\schema\parameters\query_parameter;
use core\router\schema\parameters\header_object;
use core\router\schema\response\payload_response;
use core\router\schema\response\response;
use core\router\scope\scopeset;
use local_accees_sync_api\manager\course_manager;
use local_accees_sync_api\route\scope\course\read as course_read_scope;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * UC-LMS-10 course list and detail endpoints.
 */
class courses_read {

    /** @var string Required OAuth/API token scope. */
    private const REQUIRED_SCOPE = 'local_accees_sync_api:course:read';

    /**
     * List courses with filters and pagination.
     *
     * @param ServerRequestInterface $request Request.
     * @param ResponseInterface $response Response.
     * @param course_manager $manager Course read manager.
     * @return payload_response
     */
    #[route(
        path: '/courses',
        method: ['GET'],
        headerparams: [new header_object(name: 'X-ACCEES-API-Version', type: param::TEXT, default: '1.0')],
        queryparams: [
            new query_parameter(name: 'page', type: param::INT, default: 1),
            new query_parameter(name: 'per_page', type: param::INT, default: 20),
            new query_parameter(name: 'search', type: param::TEXT),
            new query_parameter(name: 'category_id', type: param::INT),
            new query_parameter(name: 'institution', type: param::TEXT),
            new query_parameter(name: 'delivery_mode', type: param::TEXT),
            new query_parameter(name: 'status', type: param::TEXT),
            new query_parameter(name: 'is_featured', type: param::BOOL),
            new query_parameter(name: 'updated_since', type: param::RAW_TRIMMED),
            new query_parameter(name: 'include_hidden', type: param::BOOL, default: false),
        ],
        cookies: false,
        title: 'List courses and LOM metadata',
        summary: 'List courses and LOM metadata; hidden courses are excluded by default',
        responses: [
            new response(statuscode: 200, description: 'Paginated courses and LOM metadata.'),
            new response(statuscode: 400, description: 'INVALID_QUERY_PARAM'),
            new response(statuscode: 401, description: 'UNAUTHORIZED'),
            new response(statuscode: 403, description: 'FORBIDDEN'),
            new response(statuscode: 404, description: 'API route not found.'),
        ],
    )]
    #[scopeset(new course_read_scope())]
    public function list(
        ServerRequestInterface $request,
        ResponseInterface $response,
        course_manager $manager,
    ): payload_response {
        if (!$this->is_authenticated($request)) {
            return $this->error($request, $response, 401, 'UNAUTHORIZED', 'Authentication is required.');
        }
        if (!$this->has_required_scope($request)) {
            return $this->error($request, $response, 403, 'FORBIDDEN', 'Missing required course read scope.');
        }
        if (!has_capability('local/accees_sync_api:viewcourses', system::instance())) {
            return $this->error($request, $response, 403, 'FORBIDDEN', 'Missing capability to view ACCEES courses.');
        }

        try {
            $version = $request->getHeaderLine('X-ACCEES-API-Version') ?: '1.0';
            $payload = $manager->get_courses($request->getQueryParams(), $version);
        } catch (\InvalidArgumentException $exception) {
            return $this->error($request, $response, 400, 'INVALID_QUERY_PARAM', $exception->getMessage());
        }

        return new payload_response(payload: $payload, request: $request, response: $response);
    }

    /**
     * Return a single course.
     *
     * @param ServerRequestInterface $request Request.
     * @param ResponseInterface $response Response.
     * @param int $courseid Course ID.
     * @param course_manager $manager Course read manager.
     * @return payload_response
     */
    #[route(
        path: '/courses/{courseid}',
        method: ['GET'],
        headerparams: [new header_object(name: 'X-ACCEES-API-Version', type: param::TEXT, default: '1.0')],
        pathtypes: [
            new path_parameter(name: 'courseid', type: param::INT),
        ],
        cookies: false,
        title: 'Read a course and its LOM metadata',
        summary: 'Read a course and its LOM metadata',
        responses: [
            new response(statuscode: 200, description: 'Course details and LOM metadata.'),
            new response(statuscode: 400, description: 'Invalid course ID parameter.'),
            new response(statuscode: 401, description: 'UNAUTHORIZED'),
            new response(statuscode: 403, description: 'FORBIDDEN'),
            new response(statuscode: 404, description: 'COURSE_NOT_FOUND'),
        ],
    )]
    #[scopeset(new course_read_scope())]
    public function detail(
        ServerRequestInterface $request,
        ResponseInterface $response,
        int $courseid,
        course_manager $manager,
    ): payload_response {
        if (!$this->is_authenticated($request)) {
            return $this->error($request, $response, 401, 'UNAUTHORIZED', 'Authentication is required.');
        }
        if (!$this->has_required_scope($request)) {
            return $this->error($request, $response, 403, 'FORBIDDEN', 'Missing required course read scope.');
        }
        if (!has_capability('local/accees_sync_api:viewcourses', system::instance())) {
            return $this->error($request, $response, 403, 'FORBIDDEN', 'Missing capability to view ACCEES courses.');
        }

        try {
            $version = $request->getHeaderLine('X-ACCEES-API-Version') ?: '1.0';
            $course = $manager->get_course($courseid, $version);
        } catch (\InvalidArgumentException $exception) {
            return $this->error($request, $response, 400, 'INVALID_QUERY_PARAM', $exception->getMessage());
        }
        if ($course === null) {
            return $this->error($request, $response, 404, 'COURSE_NOT_FOUND', 'Course not found.');
        }

        return new payload_response(payload: $course, request: $request, response: $response);
    }

    /**
     * Build a contract-compliant error payload.
     *
     * @param ServerRequestInterface $request Request.
     * @param ResponseInterface $response Response.
     * @param int $status HTTP status.
     * @param string $code Stable API error code.
     * @param string $message Human-readable message.
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

    /**
     * Check the scopes attached by Moodle's API authentication middleware.
     *
     * This works for personal API tokens and OAuth2 bearer tokens. It avoids a
     * hard dependency on the optional league/oauth2-server package in DevLocal.
     *
     * @param ServerRequestInterface $request Request.
     * @return bool
     */
    private function has_required_scope(ServerRequestInterface $request): bool {
        $scopes = $request->getAttribute(\core\router\scope\scopeset::GRANTED_SCOPES, []);
        return in_array(self::REQUIRED_SCOPE, $scopes, true);
    }

    /**
     * Determine whether API middleware authenticated the request.
     *
     * @param ServerRequestInterface $request Request.
     * @return bool
     */
    private function is_authenticated(ServerRequestInterface $request): bool {
        return $request->getAttribute('api_token_id') !== null
            || $request->getAttribute('oauth_user_id') !== null
            || $request->getAttribute('user') !== null;
    }
}

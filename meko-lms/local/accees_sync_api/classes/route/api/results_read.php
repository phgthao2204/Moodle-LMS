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
use core\router\schema\parameters\query_parameter;
use core\router\schema\response\payload_response;
use core\router\scope\scopeset;
use local_accees_sync_api\exception\api_exception;
use local_accees_sync_api\manager\results_manager;
use local_accees_sync_api\route\scope\result\read;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class results_read {
    #[route(
        path: '/courses/{courseid}/results',
        method: ['GET'],
        pathtypes: [new path_parameter(name: 'courseid', type: param::INT)],
        queryparams: [
            new query_parameter(name: 'page', type: param::RAW, default: 1),
            new query_parameter(name: 'per_page', type: param::RAW, default: 50),
            new query_parameter(name: 'completed_only', type: param::RAW, default: 'false'),
            new query_parameter(name: 'username', type: param::RAW_TRIMMED),
            new query_parameter(name: 'user_id', type: param::RAW),
        ],
        cookies: false,
    )]
    #[scopeset(new read())]
    public function read(
        ServerRequestInterface $request,
        ResponseInterface $response,
        int $courseid,
        results_manager $manager,
    ): payload_response {
        global $DB;

        if ($request->getAttribute('api_token_id') === null && $request->getAttribute('oauth_user_id') === null) {
            return $this->error($request, $response, 401, 'UNAUTHORIZED', 'Authentication is required.');
        }
        if ($courseid < 2 || !$DB->record_exists('course', ['id' => $courseid])) {
            return $this->error($request, $response, 404, 'COURSE_NOT_FOUND', 'Course not found.');
        }
        if (!has_capability('local/accees_sync_api:viewresults', \context_course::instance($courseid))) {
            return $this->error($request, $response, 403, 'FORBIDDEN', 'Missing capability to view course results.');
        }

        $version = trim($request->getHeaderLine('X-ACCEES-API-Version')) ?: '1.0';
        if (!in_array($version, ['1.0', '1.1'], true)) {
            return $this->error($request, $response, 400, 'UNSUPPORTED_API_VERSION', 'Supported API versions are 1.0 and 1.1.');
        }
        try {
            $payload = $manager->read($courseid, $this->read_filters($request->getQueryParams()));
        } catch (api_exception $exception) {
            return $this->error($request, $response, $exception->get_http_status(),
                $exception->get_error_code(), $exception->getMessage());
        }
        return new payload_response(payload: $payload, request: $request, response: $response);
    }

    private function read_filters(array $query): array {
        $filters = [
            'page' => $this->integer($query['page'] ?? 1, 'page', 1000000),
            'per_page' => $this->integer($query['per_page'] ?? 50, 'per_page', 100),
        ];
        $completed = $query['completed_only'] ?? false;
        if (!is_scalar($completed)) {
            throw new api_exception('INVALID_FIELD', 'completed_only must be a boolean.', 400);
        }
        $filters['completed_only'] = filter_var($completed, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($filters['completed_only'] === null) {
            throw new api_exception('INVALID_FIELD', 'completed_only must be a boolean.', 400);
        }
        if (isset($query['username'], $query['user_id'])) {
            throw new api_exception('INVALID_FIELD', 'Specify only one of username or user_id.', 400);
        }
        if (isset($query['username'])) {
            if (!is_string($query['username']) || trim($query['username']) === '') {
                throw new api_exception('INVALID_FIELD', 'username must not be empty.', 400);
            }
            $filters['username'] = trim($query['username']);
        }
        if (isset($query['user_id'])) {
            $filters['user_id'] = $this->integer($query['user_id'], 'user_id', PHP_INT_MAX);
        }
        return $filters;
    }

    private function integer(mixed $value, string $name, int $max): int {
        $parsed = is_string($value) || is_int($value) ? filter_var($value, FILTER_VALIDATE_INT) : false;
        if ($parsed === false || $parsed < 1 || $parsed > $max) {
            throw new api_exception('INVALID_FIELD', "{$name} must be an integer between 1 and {$max}.", 400);
        }
        return $parsed;
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

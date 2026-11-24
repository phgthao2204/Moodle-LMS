<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// @package    local_accees_imscp
// @copyright  2026 Du an ACCEES (Erasmus+ CBHE-101179860)
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

namespace local_accees_imscp\route\api;

use context_course;
use context_system;
use core\router\route;
use core\router\schema\response\payload_response;
use core\router\scope\scopeset;
use local_accees_imscp\exception\export_exception;
use local_accees_imscp\manager\export_manager;
use local_accees_imscp\route\scope\imscp\export as export_scope;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Route controller for UC-LMS-09: Export Moodle course into ACCEES-LOM .imscc package.
 *
 * Endpoints:
 * - POST /lms/api/rest/v2/local_accees_imscp/courses/{courseid}/export
 * - GET  /lms/api/rest/v2/local_accees_imscp/exports/{token}
 *
 * Scope: local_accees_imscp:imscp:export
 * Capability: local/accees_imscp:export
 */
class export_package {

    /**
     * Export a course into an .imscc package with ACCEES-LOM metadata.
     *
     * @param ResponseInterface $response
     * @param ServerRequestInterface $request
     * @param int|string $courseid Course ID from path.
     * @return ResponseInterface
     */
    #[route(
        path: '/courses/{courseid}/export',
        method: ['POST'],
        cookies: false,
        summary: 'Export Moodle course as ACCEES-LOM .imscc package',
        description: 'Packages course structure, activities, and ACCEES-LOM metadata into an .imscc archive.',
    )]
    #[scopeset(
        new export_scope(),
    )]
    public function export(
        ResponseInterface $response,
        ServerRequestInterface $request,
        int|string $courseid = 0,
    ): ResponseInterface|payload_response {
        global $DB;

        // 1. Authentication check: Request must be authenticated via Bearer token (API token or OAuth2).
        $isauthenticated = ($request->getAttribute('api_token_id') !== null) ||
                           ($request->getAttribute('oauth_user_id') !== null);

        if (!$isauthenticated && isloggedin() && !isguestuser()) {
            $isauthenticated = true;
        }

        if (!$isauthenticated) {
            return $this->format_error_response(
                request: $request,
                response: $response,
                statuscode: 401,
                errorcode: 'UNAUTHORIZED',
                message: 'Missing or invalid Bearer authentication token',
                details: [],
            );
        }

        // Resolve courseid from argument or request attribute.
        $rawcourseid = $courseid ?: $request->getAttribute('courseid');
        if (!is_numeric($rawcourseid) || (int) $rawcourseid <= 0) {
            return $this->format_error_response(
                request: $request,
                response: $response,
                statuscode: 400,
                errorcode: 'INVALID_PARAMETER',
                message: 'Invalid courseid parameter: must be a positive integer',
                details: ['courseid' => $rawcourseid],
            );
        }
        $courseid = (int) $rawcourseid;

        // Check course existence.
        $course = $DB->get_record('course', ['id' => $courseid]);
        if (!$course || (int) $course->id === (int) SITEID) {
            return $this->format_error_response(
                request: $request,
                response: $response,
                statuscode: 404,
                errorcode: 'COURSE_NOT_FOUND',
                message: "Course with id {$courseid} not found",
                details: ['courseid' => $courseid],
            );
        }

        // Capability check on course context: local/accees_imscp:export.
        $haspermission = false;
        try {
            $coursecontext = context_course::instance($courseid, IGNORE_MISSING);
            if ($coursecontext && has_capability('local/accees_imscp:export', $coursecontext)) {
                $haspermission = true;
            } elseif (has_capability('local/accees_imscp:export', context_system::instance())) {
                $haspermission = true;
            }
        } catch (Throwable) {
            $haspermission = false;
        }

        if (!$haspermission) {
            return $this->format_error_response(
                request: $request,
                response: $response,
                statuscode: 403,
                errorcode: 'FORBIDDEN',
                message: 'User does not possess the required capability local/accees_imscp:export for this course',
                details: [
                    'capability' => 'local/accees_imscp:export',
                    'course_id' => $courseid,
                ],
            );
        }

        // Parse JSON options.
        $options = [];
        $rawcontent = (string) $request->getBody();
        if (!empty($rawcontent)) {
            $decoded = json_decode($rawcontent, true);
            if (is_array($decoded)) {
                $options = $decoded;
            }
        }
        $parsedbody = (array) ($request->getParsedBody() ?? []);
        $options = array_merge($options, $parsedbody);

        $manager = new export_manager();
        try {
            $result = $manager->export_course($courseid, $options);

            // Handle binary delivery.
            if (($result['delivery'] ?? '') === 'binary') {
                $filepath = $result['filepath'];
                $body = \GuzzleHttp\Psr7\Utils::streamFor(fopen($filepath, 'rb'));
                return $response
                    ->withStatus(200)
                    ->withHeader('Content-Type', 'application/zip')
                    ->withHeader('Content-Disposition', 'attachment; filename="' . $result['filename'] . '"')
                    ->withHeader('Content-Length', (string) $result['size_bytes'])
                    ->withHeader('X-ACCEES-Checksum-SHA256', $result['checksum_sha256'])
                    ->withBody($body);
            }

            return new payload_response(
                payload: $result,
                request: $request,
                response: $response->withStatus(200),
            );
        } catch (export_exception $e) {
            return $this->format_error_response(
                request: $request,
                response: $response,
                statuscode: $e->httpstatus,
                errorcode: $e->errorcode,
                message: $e->getMessage(),
                details: $e->details,
            );
        } catch (Throwable $e) {
            return $this->format_error_response(
                request: $request,
                response: $response,
                statuscode: 500,
                errorcode: 'EXPORT_PACKAGING_FAILED',
                message: 'An unexpected error occurred during course packaging: ' . $e->getMessage(),
                details: [],
            );
        }
    }

    /**
     * Download an exported course artifact by its ephemeral token.
     *
     * @param ResponseInterface $response
     * @param ServerRequestInterface $request
     * @param string $token Ephemeral token from path.
     * @return ResponseInterface
     */
    #[route(
        path: '/exports/{token}',
        method: ['GET'],
        cookies: false,
        summary: 'Download exported .imscc package artifact',
        description: 'Downloads the pre-packaged .imscc file generated by the course export endpoint.',
    )]
    #[scopeset(
        new export_scope(),
    )]
    public function download(
        ResponseInterface $response,
        ServerRequestInterface $request,
        string $token = '',
    ): ResponseInterface|payload_response {
        // 1. Authentication check: Request must be authenticated via Bearer token (API token or OAuth2).
        $isauthenticated = ($request->getAttribute('api_token_id') !== null) ||
                           ($request->getAttribute('oauth_user_id') !== null);

        if (!$isauthenticated && isloggedin() && !isguestuser()) {
            $isauthenticated = true;
        }

        if (!$isauthenticated) {
            return $this->format_error_response(
                request: $request,
                response: $response,
                statuscode: 401,
                errorcode: 'UNAUTHORIZED',
                message: 'Missing or invalid Bearer authentication token',
                details: [],
            );
        }

        $rawtoken = $token ?: (string) $request->getAttribute('token', '');
        if (empty($rawtoken)) {
            return $this->format_error_response(
                request: $request,
                response: $response,
                statuscode: 400,
                errorcode: 'INVALID_PARAMETER',
                message: 'Missing export download token',
            );
        }

        $manager = new export_manager();
        try {
            $artifact = $manager->get_export_artifact($rawtoken);

            // 2. Capability check on the course context: local/accees_imscp:export.
            $courseid = (int) ($artifact['course_id'] ?? 0);
            $haspermission = false;
            try {
                $coursecontext = context_course::instance($courseid, IGNORE_MISSING);
                if ($coursecontext && has_capability('local/accees_imscp:export', $coursecontext)) {
                    $haspermission = true;
                } elseif (has_capability('local/accees_imscp:export', context_system::instance())) {
                    $haspermission = true;
                }
            } catch (Throwable) {
                $haspermission = false;
            }

            if (!$haspermission) {
                return $this->format_error_response(
                    request: $request,
                    response: $response,
                    statuscode: 403,
                    errorcode: 'FORBIDDEN',
                    message: 'User does not possess the required capability local/accees_imscp:export for this course',
                    details: [
                        'capability' => 'local/accees_imscp:export',
                        'course_id' => $courseid,
                    ],
                );
            }

            $filepath = $artifact['filepath'];
            $body = \GuzzleHttp\Psr7\Utils::streamFor(fopen($filepath, 'rb'));

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/zip')
                ->withHeader('Content-Disposition', 'attachment; filename="' . $artifact['filename'] . '"')
                ->withHeader('Content-Length', (string) $artifact['size_bytes'])
                ->withHeader('X-ACCEES-Checksum-SHA256', $artifact['checksum_sha256'])
                ->withBody($body);
        } catch (export_exception $e) {
            return $this->format_error_response(
                request: $request,
                response: $response,
                statuscode: $e->httpstatus,
                errorcode: $e->errorcode,
                message: $e->getMessage(),
                details: $e->details,
            );
        } catch (Throwable $e) {
            return $this->format_error_response(
                request: $request,
                response: $response,
                statuscode: 500,
                errorcode: 'EXPORT_PACKAGING_FAILED',
                message: 'Failed to stream export artifact: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Format standard JSON error payload response according to ACCEES-LMS specifications.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param int $statuscode
     * @param string $errorcode
     * @param string $message
     * @param array<string, mixed> $details
     * @return payload_response
     */
    protected function format_error_response(
        ServerRequestInterface $request,
        ResponseInterface $response,
        int $statuscode,
        string $errorcode,
        string $message,
        array $details = [],
    ): payload_response {
        return new payload_response(
            payload: [
                'error' => [
                    'code' => $errorcode,
                    'message' => $message,
                    'details' => $details,
                ],
            ],
            request: $request,
            response: $response->withStatus($statuscode),
        );
    }
}

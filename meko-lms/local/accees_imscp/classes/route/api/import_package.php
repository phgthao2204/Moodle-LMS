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

use context_coursecat;
use context_system;
use core\router\route;
use core\router\schema\response\payload_response;
use core\router\scope\scopeset;
use local_accees_imscp\exception\import_exception;
use local_accees_imscp\manager\import_manager;
use local_accees_imscp\route\scope\imscp\import as import_scope;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Throwable;

/**
 * Route controller for UC-LMS-08: Import IMS Common Cartridge (.imscc) package.
 *
 * Endpoint: POST /lms/api/rest/v2/local_accees_imscp/packages/import
 * Scope: local_accees_imscp:imscp:import
 * Capability: local/accees_imscp:import
 */
class import_package {

    /**
     * Import an ACCEES-LOM .imscc course package.
     *
     * @param ResponseInterface $response
     * @param ServerRequestInterface $request
     * @return payload_response
     */
    #[route(
        path: '/packages/import',
        method: ['POST'],
        cookies: false,
        summary: 'Import ACCEES-LOM course package (.imscc)',
        description: 'Upload or fetch an IMS Common Cartridge archive, import course structure and store LOM metadata.',
    )]
    #[scopeset(
        new import_scope(),
    )]
    public function import(
        ResponseInterface $response,
        ServerRequestInterface $request,
    ): payload_response {
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

        // Parse parameters from multipart form-data or JSON body.
        $parsedbody = (array) ($request->getParsedBody() ?? []);
        if (empty($parsedbody) && !empty($_POST)) {
            $parsedbody = $_POST;
        }
        $rawcontent = (string) $request->getBody();
        if (!empty($rawcontent)) {
            $decoded = json_decode($rawcontent, true);
            if (is_array($decoded)) {
                $parsedbody = array_merge($parsedbody, $decoded);
            }
        }

        // Resolve default category ID from plugin settings if not provided in request.
        $defaultcategory = (int) get_config('local_accees_imscp', 'default_category_id');
        if ($defaultcategory <= 0) {
            $defaultcategory = 1;
        }

        $categoryid = (isset($parsedbody['category_id']) && is_numeric($parsedbody['category_id']))
            ? (int) $parsedbody['category_id']
            : $defaultcategory;
        $targetshortname = !empty($parsedbody['target_course_shortname']) ? trim((string) $parsedbody['target_course_shortname']) : null;
        $overwrite = false;
        if (isset($parsedbody['overwrite'])) {
            $rawoverwrite = $parsedbody['overwrite'];
            $overwrite = ($rawoverwrite === true || $rawoverwrite === 'true' || $rawoverwrite === 1 || $rawoverwrite === '1');
        }

        // Capability check: user must have local/accees_imscp:import at category or system level.
        $haspermission = false;
        try {
            $catcontext = context_coursecat::instance($categoryid, IGNORE_MISSING);
            if ($catcontext && has_capability('local/accees_imscp:import', $catcontext)) {
                $haspermission = true;
            } elseif (has_capability('local/accees_imscp:import', context_system::instance())) {
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
                message: 'User does not possess the required capability local/accees_imscp:import',
                details: [
                    'capability' => 'local/accees_imscp:import',
                    'category_id' => $categoryid,
                ],
            );
        }

        $manager = new import_manager();

        // Check for uploaded file.
        $uploadedfiles = $request->getUploadedFiles();
        /** @var UploadedFileInterface|null $packagefile */
        $packagefile = $uploadedfiles['package_file'] ?? null;
        $packageurl = !empty($parsedbody['package_url']) ? trim((string) $parsedbody['package_url']) : null;

        if (!$packagefile && empty($packageurl)) {
            return $this->format_error_response(
                request: $request,
                response: $response,
                statuscode: 400,
                errorcode: 'INVALID_PACKAGE',
                message: 'Either package_file (uploaded .imscc/.zip archive) or package_url must be provided',
                details: ['received_fields' => array_keys($parsedbody)],
            );
        }

        try {
            if ($packagefile instanceof UploadedFileInterface) {
                if ($packagefile->getError() !== UPLOAD_ERR_OK) {
                    return $this->format_error_response(
                        request: $request,
                        response: $response,
                        statuscode: 400,
                        errorcode: 'INVALID_PACKAGE',
                        message: 'Uploaded package_file has upload error',
                        details: ['upload_error_code' => $packagefile->getError()],
                    );
                }

                $temppath = make_temp_directory('imscp_upload') . '/' . uniqid('upload_', true) . '.imscc';
                $packagefile->moveTo($temppath);

                try {
                    $result = $manager->import_package_from_file(
                        filepath: $temppath,
                        categoryid: $categoryid,
                        targetshortname: $targetshortname,
                        overwrite: $overwrite,
                    );
                } finally {
                    @unlink($temppath);
                }
            } else {
                $result = $manager->import_package_from_url(
                    packageurl: $packageurl,
                    categoryid: $categoryid,
                    targetshortname: $targetshortname,
                    overwrite: $overwrite,
                );
            }

            return new payload_response(
                payload: $result,
                request: $request,
                response: $response->withStatus(201),
            );
        } catch (import_exception $e) {
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
                errorcode: 'INTERNAL_ERROR',
                message: 'An unexpected error occurred during course package import: ' . $e->getMessage(),
                details: [],
            );
        }
    }

    /**
     * Format standard JSON error payload response according to ACCEES-LMS v1.0 specifications.
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

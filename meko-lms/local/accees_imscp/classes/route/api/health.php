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

use core\router\route;
use core\router\schema\response\payload_response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Reference healthcheck route for local_accees_imscp REST API v2.
 */
class health {

    /**
     * Return plugin readiness and supported API version info.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return payload_response
     */
    #[route(
        path: '/health',
        methods: ['GET'],
    )]
    public function status(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): payload_response {
        return new payload_response(
            payload: [
                'component' => 'local_accees_imscp',
                'api_versions' => ['1.0', '1.1'],
                'status' => 'ready',
            ],
            request: $request,
            response: $response,
        );
    }
}

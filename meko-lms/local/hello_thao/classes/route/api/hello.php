<?php
// This file is part of Moodle - http://moodle.org/
// SPDX-License-Identifier: GPL-3.0-or-later

namespace local_hello_thao\route\api;

use core\router\route;
use core\router\schema\response\payload_response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** Hello World endpoint for Thao. */
class hello {
    /** Return the developer name and a fixed greeting. */
    #[route(
        path: '/hello',
        method: ['GET'],
        cookies: false,
    )]
    public function get(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): payload_response {
        return new payload_response(
            payload: ['developer' => 'Thảo', 'message' => 'Hello Thảo'],
            request: $request,
            response: $response->withStatus(200),
        );
    }
}

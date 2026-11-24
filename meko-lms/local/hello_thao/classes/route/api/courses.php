<?php
// This file is part of Moodle - http://moodle.org/
// SPDX-License-Identifier: GPL-3.0-or-later

namespace local_hello_thao\route\api;

use core\param;
use core\router\route;
use core\router\schema\parameters\path_parameter;
use core\router\schema\response\payload_response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** Simple public course list and detail examples. */
class courses {
    /** Return visible courses, excluding the site front page. */
    #[route(path: '/courses', method: ['GET'], cookies: false)]
    public function list(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): payload_response {
        global $DB;

        $records = $DB->get_records_select('course', 'id <> :siteid AND visible = :visible',
            ['siteid' => SITEID, 'visible' => 1], 'id ASC', 'id, fullname, shortname');
        $courses = array_map(fn($course) => $this->format_course($course), array_values($records));

        return new payload_response(
            payload: ['developer' => 'Thảo', 'courses' => $courses],
            request: $request,
            response: $response,
        );
    }

    /** Return a visible course by its required integer ID. */
    #[route(
        path: '/courses/{courseid}',
        method: ['GET'],
        pathtypes: [new path_parameter(name: 'courseid', type: param::INT)],
        cookies: false,
    )]
    public function detail(
        ServerRequestInterface $request,
        ResponseInterface $response,
        int $courseid,
    ): payload_response {
        global $DB;

        $course = $courseid > 0 && $courseid !== SITEID
            ? $DB->get_record('course', ['id' => $courseid, 'visible' => 1], 'id, fullname, shortname')
            : false;
        if (!$course) {
            return new payload_response(
                payload: ['error' => ['code' => 'COURSE_NOT_FOUND', 'message' => 'Course not found.']],
                request: $request,
                response: $response->withStatus(404),
            );
        }

        return new payload_response(
            payload: ['developer' => 'Thảo', 'course' => $this->format_course($course)],
            request: $request,
            response: $response,
        );
    }

    /** Keep the response limited to the three documented fields. */
    private function format_course(\stdClass $course): array {
        return ['id' => (int)$course->id, 'fullname' => $course->fullname, 'shortname' => $course->shortname];
    }
}

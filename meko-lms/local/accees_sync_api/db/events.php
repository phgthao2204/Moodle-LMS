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

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\course_completed',
        'callback'  => '\local_accees_sync_api\observer\completion_observer::on_course_completed',
    ],
    [
        'eventname' => '\core\event\badge_awarded',
        'callback'  => '\local_accees_sync_api\observer\completion_observer::on_badge_awarded',
    ],
];

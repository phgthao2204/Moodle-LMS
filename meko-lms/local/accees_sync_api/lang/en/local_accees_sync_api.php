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

$string['pluginname'] = 'ACCEES LMS Sync API';
$string['accees_sync_api:viewcourses'] = 'Read courses and LOM metadata via REST API v2';
$string['accees_sync_api:updatecourse'] = 'Update course info and LOM custom fields via REST API v2';
$string['accees_sync_api:enrol'] = 'Enrol users into courses via REST API v2';
$string['accees_sync_api:unenrol'] = 'Unenrol users from courses via REST API v2';
$string['accees_sync_api:viewresults'] = 'Read learner progress, grades and Open Badges via REST API v2';

$string['setting_default_strict_sso'] = 'Strict SSO user lookup (v1.1 mode)';
$string['setting_default_strict_sso_desc'] = 'When enabled, enrol API rejects unknown users instead of auto-creating them.';
$string['setting_webhook_enabled'] = 'Enable completion webhook (UC-LMS-14)';
$string['setting_webhook_enabled_desc'] = 'Dispatch async webhook notifications on course completion or badge issuance.';
$string['setting_webhook_url'] = 'Webhook target URL';
$string['setting_webhook_url_desc'] = 'Destination HTTP endpoint receiving completion events (e.g. TMS callback URL).';
$string['scope_course_read_summary'] = 'Read ACCEES courses';
$string['scope_course_read_description'] = 'Read course lists, course details, custom fields and LOM-ACCEES metadata.';
$string['scope_course_write_summary'] = 'Update ACCEES courses';
$string['scope_course_write_description'] = 'Update course information and supported LOM-ACCEES metadata.';
$string['scope_enrol_manage_summary'] = 'Manage ACCEES course enrolments';
$string['scope_enrol_manage_description'] = 'Enrol and manage users in ACCEES courses via REST API v2.';
$string['setting_webhook_secret'] = 'Webhook HMAC secret';
$string['setting_webhook_secret_desc'] = 'Secret key used to sign payloads via X-ACCEES-Signature header.';

$string['scope_result_read_summary'] = 'Read ACCEES learner results';
$string['scope_result_read_description'] = 'Read learner progress, course grades and issued badges.';

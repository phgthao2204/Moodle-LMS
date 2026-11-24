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

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Skeleton CLI seeder for UC-LMS-08 and UC-LMS-09 fixtures.
cli_heading('local_accees_imscp - Test Data Seeder');
cli_writeln('Base data is provided by local/accees_sync_api/cli/seed_base.php.');
cli_writeln('Add IMSCP-specific fixture preparation logic here during UC-LMS-08 / UC-LMS-09.');

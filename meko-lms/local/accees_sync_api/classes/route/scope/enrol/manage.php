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

namespace local_accees_sync_api\route\scope\enrol;

/** OAuth scope for managing ACCEES enrolments. */
#[\core\router\scope\identifier_attribute('manage')]
#[\core\router\scope\summary_attribute('scope_enrol_manage_summary', 'local_accees_sync_api')]
#[\core\router\scope\description_attribute('scope_enrol_manage_description', 'local_accees_sync_api')]
class manage extends abstract_scope {
}


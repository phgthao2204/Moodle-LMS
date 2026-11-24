<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// @package    local_meko_competencies
// @copyright  2026 Dự án ACCEES (Erasmus+ CBHE-101179860)
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

defined('MOODLE_INTERNAL') || die();

/**
 * Post-install hook to automatically seed canonical ACCEES competency frameworks into Moodle DB.
 *
 * @return bool
 */
function xmldb_local_meko_competencies_install() {
    require_once(__DIR__ . '/../classes/framework_sync.php');
    \local_meko_competencies\framework_sync::sync_all();
    return true;
}

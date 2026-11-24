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

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'ACCEES IMSCP Package Sync';
$string['accees_imscp:import'] = 'Import ACCEES-LOM .imscc course packages via REST API v2';
$string['accees_imscp:export'] = 'Export ACCEES-LOM .imscc course packages via REST API v2';
$string['scope_imscp_import_summary'] = 'Import IMS Common Cartridge course packages';
$string['scope_imscp_import_desc'] = 'Allows uploading and importing .imscc course packages into Moodle.';
$string['scope_imscp_export_summary'] = 'Export IMS Common Cartridge course packages';
$string['scope_imscp_export_desc'] = 'Allows exporting Moodle courses as .imscc packages with ACCEES-LOM metadata.';
$string['setting_default_category_id'] = 'Default Course Category ID';
$string['setting_default_category_id_desc'] = 'The default course category ID used when importing packages if no category_id is specified in the request.';

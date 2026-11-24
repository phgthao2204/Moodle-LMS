<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Version information for local_accees_imscp.
 *
 * @package    local_accees_imscp
 * @copyright  2026 Du an ACCEES (Erasmus+ CBHE-101179860)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_accees_imscp';
$plugin->version   = 2026111700;
$plugin->requires  = 2024100700; // Moodle 4.5+ / 5.x Routing Engine & REST API v2.
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = 'v1.0.0-dev';
$plugin->dependencies = [
    'local_accees_sync_api' => 2026111700,
];

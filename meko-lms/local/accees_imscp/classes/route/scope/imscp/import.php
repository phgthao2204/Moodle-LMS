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

namespace local_accees_imscp\route\scope\imscp;

use core\router\scope\description_attribute;
use core\router\scope\identifier_attribute;
use core\router\scope\summary_attribute;

/**
 * Scope local_accees_imscp:imscp:import for importing IMSCP packages.
 */
#[identifier_attribute('import')]
#[summary_attribute('scope_imscp_import_summary', 'local_accees_imscp')]
#[description_attribute('scope_imscp_import_desc', 'local_accees_imscp')]
class import extends abstract_scope {
}

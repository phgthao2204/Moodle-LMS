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

use core\router\scope\identifier_attribute;

/**
 * Base abstract scope for local_accees_imscp IMSCP package resources.
 */
#[identifier_attribute('imscp')]
abstract class abstract_scope extends \core\router\scope\abstract_scope {
}

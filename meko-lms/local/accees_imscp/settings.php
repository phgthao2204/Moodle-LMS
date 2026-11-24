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

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_accees_imscp',
        get_string('pluginname', 'local_accees_imscp')
    );

    $settings->add(new admin_setting_configtext(
        'local_accees_imscp/default_category_id',
        get_string('setting_default_category_id', 'local_accees_imscp'),
        get_string('setting_default_category_id_desc', 'local_accees_imscp'),
        1,
        PARAM_INT
    ));

    $ADMIN->add('localplugins', $settings);
}

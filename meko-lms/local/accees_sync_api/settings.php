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

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_accees_sync_api',
        get_string('pluginname', 'local_accees_sync_api')
    );

    $settings->add(new admin_setting_configcheckbox(
        'local_accees_sync_api/default_strict_sso',
        get_string('setting_default_strict_sso', 'local_accees_sync_api'),
        get_string('setting_default_strict_sso_desc', 'local_accees_sync_api'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_accees_sync_api/webhook_enabled',
        get_string('setting_webhook_enabled', 'local_accees_sync_api'),
        get_string('setting_webhook_enabled_desc', 'local_accees_sync_api'),
        0
    ));

    $settings->add(new admin_setting_configtext(
        'local_accees_sync_api/webhook_url',
        get_string('setting_webhook_url', 'local_accees_sync_api'),
        get_string('setting_webhook_url_desc', 'local_accees_sync_api'),
        '',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_accees_sync_api/webhook_secret',
        get_string('setting_webhook_secret', 'local_accees_sync_api'),
        get_string('setting_webhook_secret_desc', 'local_accees_sync_api'),
        ''
    ));

    $ADMIN->add('localplugins', $settings);
}

<?php
// This file is part of Moodle - http://moodle.org/
//
// MEKOSOFT LMS Theme Settings

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings = new theme_boost_admin_settingspage_tabs('themesettingmeko', get_string('configtitle', 'theme_meko'));
    $page = new admin_settingpage('theme_meko_general', get_string('generalsettings', 'theme_meko'));

    // Thông tin phiên bản & Trạng thái Theme
    $versioninfo = html_writer::div(
        html_writer::tag('h5', 'MEKOSOFT LMS Theme — Phiên bản v1.7.1 (Build: 2026111301)', ['class' => 'fw-bold text-primary mb-1', 'style' => 'color: #0000B6 !important;']) .
        html_writer::tag('p', 'Giao diện chuẩn hóa nhận diện số doanh nghiệp và đại học MEKOSOFT. Kế thừa lõi theme_boost (Moodle 5.3 LTS, Bootstrap 5).', ['class' => 'text-muted mb-0']),
        'alert alert-info py-3 px-3 mb-4 rounded-3 border-0',
        ['style' => 'background-color: #EEF2FF; border-left: 4px solid #0000B6 !important;']
    );
    $page->add(new admin_setting_heading('theme_meko_info', '', $versioninfo));

    // 0. Bộ nhận diện thương hiệu (Brand Profile)
    $name = 'theme_meko/brand_profile';
    $title = 'Bộ nhận diện thương hiệu (Brand Profile)';
    $description = 'Chọn bộ nhận diện hiển thị trên toàn hệ thống: MEKO LMS (Mekosoft) hoặc ACCEES LMS (Dự án ACCEES / ĐH Cần Thơ)';
    $choices = [
        'meko' => 'MEKOSOFT LMS (Logo & Thông tin Mekosoft)',
        'accees' => 'ACCEES LMS (Logo & Thông tin Dự án ACCEES / ĐH Cần Thơ)',
    ];
    $setting = new admin_setting_configselect($name, $title, $description, 'meko', $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // 1. Logo
    $name = 'theme_meko/logo';
    $title = get_string('logo', 'theme_meko');
    $description = get_string('logodesc', 'theme_meko');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'logo');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // 2. Portal TMS Link
    $name = 'theme_meko/tms_url';
    $title = get_string('tms_url', 'theme_meko');
    $description = get_string('tms_urldesc', 'theme_meko');
    $default = 'http://meko.localhost/tms';
    $setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_URL);
    $page->add($setting);

    // 3. SSO Keycloak Link
    $name = 'theme_meko/sso_url';
    $title = get_string('sso_url', 'theme_meko');
    $description = get_string('sso_urldesc', 'theme_meko');
    $default = 'http://meko.localhost/';
    $setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_URL);
    $page->add($setting);

    // 4. Custom SCSS
    $name = 'theme_meko/scss';
    $title = get_string('rawscss', 'theme_meko');
    $description = get_string('rawscss_desc', 'theme_meko');
    $setting = new admin_setting_configtextarea($name, $title, $description, '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);
}

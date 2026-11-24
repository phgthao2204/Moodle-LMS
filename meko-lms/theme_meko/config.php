<?php
// This file is part of Moodle - http://moodle.org/
//
// MEKOSOFT LMS Theme - Child theme of theme_boost

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/theme/boost/lib.php');
require_once(__DIR__ . '/lib.php');

$THEME->name = 'meko';
$THEME->parents = ['boost'];
$THEME->sheets = ['login'];
$THEME->editor_sheets = [];

// SCSS compiler callback
$THEME->scss = function($theme) {
    return theme_meko_get_pre_scss($theme)
        . theme_boost_get_main_scss_content($theme)
        . theme_meko_get_extra_scss($theme);
};

// Layout configurations
$THEME->layouts = [
    // 1. Layout Trang chủ (Frontpage / Home)
    'frontpage' => [
        'file' => 'frontpage.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
        'options' => ['nonavbar' => true],
    ],
    // 2. Layout cơ bản có drawer menu (Boost standard)
    'base' => [
        'file' => 'drawers.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],
    'standard' => [
        'file' => 'drawers.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],
    // 3. Layout khóa học (Format Tiles & Course View)
    'course' => [
        'file' => 'course.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
        'options' => ['langmenu' => true],
    ],
    // Trang danh mục khóa học Moodle (/course/index.php - Có bộ lọc CBE và tìm kiếm)
    'coursecategory' => [
        'file' => 'coursecategory.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],
    // Bảng điều khiển người dùng (/my/)
    'mydashboard' => [
        'file' => 'mycourses.php',
        'regions' => [],
        'options' => ['langmenu' => true],
    ],
    // Khung năng lực số MEKO
    'competencies' => [
        'file' => 'competencies.php',
        'regions' => [],
        'options' => ['nonavbar' => true],
    ],
    // Khóa học của tôi (/my/courses.php)
    'mycourses' => [
        'file' => 'mycourses.php',
        'regions' => [],
        'options' => ['langmenu' => true],
    ],
    // 4. Layout đăng nhập tập trung SSO
    'login' => [
        'file' => 'login.php',
        'regions' => [],
        'options' => ['langmenu' => true],
    ],
    // 5. Layout quản trị hệ thống
    'admin' => [
        'file' => 'drawers.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],
];

// Cho phép load CSS/JS mở rộng
$THEME->enable_dock = false;
$THEME->yuicssmodules = [];
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->requiredblocks = '';
$THEME->addblockposition = BLOCK_ADDBLOCK_POSITION_FLATNAV;
$THEME->haseditswitch = true;

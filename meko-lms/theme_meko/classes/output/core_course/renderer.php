<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle 5.3 PSR-4 course renderer override.
// Allows $PAGE->get_renderer('core', 'course') to automatically autoload our custom renderer.

namespace theme_meko\output\core_course;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../core_course_renderer.php');

if (class_exists('\theme_meko\output\core_course_renderer') && !class_exists('\theme_meko\output\core_course\renderer')) {
    class renderer extends \theme_meko\output\core_course_renderer {
    }
}

if (class_exists('\theme_meko\output\core_course\renderer') && !class_exists('\theme_accees\output\core_course\renderer')) {
    class_alias('\theme_meko\output\core_course\renderer', '\theme_accees\output\core_course\renderer');
}

if (class_exists('\theme_meko\output\core_course\renderer') && !class_exists('\theme_accees_lms_theme\output\core_course\renderer')) {
    class_alias('\theme_meko\output\core_course\renderer', '\theme_accees_lms_theme\output\core_course\renderer');
}

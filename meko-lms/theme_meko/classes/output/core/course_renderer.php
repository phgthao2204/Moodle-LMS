<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle 4.x / 5.x PSR-4 course renderer override.
// Matches $PAGE->get_renderer('core', 'course') -> \theme_meko\output\core\course_renderer

namespace theme_meko\output\core;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../core_course_renderer.php');

if (class_exists('\theme_meko\output\core_course_renderer') && !class_exists('\theme_meko\output\core\course_renderer')) {
    class course_renderer extends \theme_meko\output\core_course_renderer {
    }
}

if (class_exists('\theme_meko\output\core\course_renderer') && !class_exists('\theme_accees\output\core\course_renderer')) {
    class_alias('\theme_meko\output\core\course_renderer', '\theme_accees\output\core\course_renderer');
}

if (class_exists('\theme_meko\output\core\course_renderer') && !class_exists('\theme_accees_lms_theme\output\core\course_renderer')) {
    class_alias('\theme_meko\output\core\course_renderer', '\theme_accees_lms_theme\output\core\course_renderer');
}

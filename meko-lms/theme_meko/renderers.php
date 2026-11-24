<?php
// This file is part of Moodle - http://moodle.org/
//
// Traditional Moodle renderer class definitions.
// Guarantees Moodle renderer discovery regardless of caching or autoload method.

defined('MOODLE_INTERNAL') || die();

// Only define course renderer when core_course_renderer is requested or parent is available,
// preventing eager class declaration overhead or collisions on admin / login pages.
if (class_exists('\core_course_renderer')) {
    require_once(__DIR__ . '/classes/output/core_course_renderer.php');

    if (class_exists('\theme_meko\output\core_course_renderer')) {
        if (!class_exists('theme_meko_core_course_renderer')) {
            class theme_meko_core_course_renderer extends \theme_meko\output\core_course_renderer {
            }
        }

        if (!class_exists('theme_meko_course_renderer')) {
            class theme_meko_course_renderer extends \theme_meko\output\core_course_renderer {
            }
        }

        if (!class_exists('theme_accees_core_course_renderer')) {
            class theme_accees_core_course_renderer extends \theme_meko\output\core_course_renderer {
            }
        }

        if (!class_exists('theme_accees_course_renderer')) {
            class theme_accees_course_renderer extends \theme_meko\output\core_course_renderer {
            }
        }
    }
}

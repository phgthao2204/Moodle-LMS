<?php
// This file is part of Moodle - http://moodle.org/
//
// Competency renderer for theme_meko (UC-LMS-02).
// Renders 3-tier Competency Tree and Rubric Matrix Comparative View.

namespace theme_meko\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;
use moodle_url;

class competency_renderer extends plugin_renderer_base {

    /**
     * Render Cây Năng Lực Số 3 tầng (Domain > Competency > Target Level)
     *
     * @param array $data
     * @return string HTML
     */
    public function render_competency_tree(array $data): string {
        $themename = !empty($this->page->theme->name) ? $this->page->theme->name : 'meko';
        try {
            return $this->render_from_template('theme_' . $themename . '/competency_frameworks', $data);
        } catch (\Throwable $e) {
            try {
                return $this->render_from_template('theme_meko/competency_frameworks', $data);
            } catch (\Throwable $e2) {
                return $this->render_from_template('local_meko_competencies/competency_frameworks', $data);
            }
        }
    }

    /**
     * Render Ma Trận Rubric đối chiếu ngang 4 Bậc N1..N4
     *
     * @param array $data
     * @return string HTML
     */
    public function render_matrix_view(array $data): string {
        $themename = !empty($this->page->theme->name) ? $this->page->theme->name : 'meko';
        try {
            return $this->render_from_template('theme_' . $themename . '/competency_matrix', $data);
        } catch (\Throwable $e) {
            try {
                return $this->render_from_template('theme_meko/competency_matrix', $data);
            } catch (\Throwable $e2) {
                return $this->render_from_template('local_meko_competencies/competency_matrix', $data);
            }
        }
    }

    /**
     * Render Hub trung tâm Khung Năng Lực Số
     *
     * @param string $framework_key 'students' | 'faculty'
     * @param string $view_mode 'tree' | 'matrix'
     * @return string HTML
     */
    public function render_frameworks_hub(string $framework_key = 'students', string $view_mode = 'tree'): string {
        global $CFG;

        if (!class_exists('\local_meko_competencies\helper')) {
            $helper_file = $CFG->dirroot . '/local/meko_competencies/classes/helper.php';
            if (file_exists($helper_file)) {
                require_once($helper_file);
            }
        }

        $is_faculty = ($framework_key === 'faculty');
        $lang = class_exists('\local_meko_competencies\helper') ? \local_meko_competencies\helper::resolve_lang('en') : 'en';
        $fallback_title = $is_faculty ? get_string('faculty_framework', 'local_meko_competencies') : get_string('students_framework', 'local_meko_competencies');

        if ($view_mode === 'matrix') {
            if (class_exists('\local_meko_competencies\helper')) {
                $data = \local_meko_competencies\helper::get_rubric_matrix_data($framework_key, $lang);
            } else {
                $data = [
                    'framework_key' => $framework_key,
                    'framework_title' => $fallback_title,
                    'is_faculty' => $is_faculty,
                    'is_students' => !$is_faculty,
                    'columns' => [],
                    'matrix_rows' => [],
                    'domains_count' => 0,
                    'competencies_count' => 0,
                    'levels_count' => 0,
                ];
            }

            $data['students_url'] = (new moodle_url('/local/meko_competencies/index.php', ['framework' => 'students', 'view' => 'matrix']))->out(false);
            $data['faculty_url'] = (new moodle_url('/local/meko_competencies/index.php', ['framework' => 'faculty', 'view' => 'matrix']))->out(false);
            $data['tree_view_url'] = (new moodle_url('/local/meko_competencies/index.php', ['framework' => $framework_key, 'view' => 'tree']))->out(false);
            $data['matrix_view_url'] = (new moodle_url('/local/meko_competencies/index.php', ['framework' => $framework_key, 'view' => 'matrix']))->out(false);
            $data['is_matrix_view'] = true;
            $data['is_tree_view'] = false;

            return $this->render_matrix_view($data);
        }

        // Tree view
        if (class_exists('\local_meko_competencies\helper')) {
            $data = \local_meko_competencies\helper::get_framework_data($framework_key, $lang);
        } else {
            $data = [
                'framework_key' => $framework_key,
                'framework_title' => $fallback_title,
                'is_faculty' => $is_faculty,
                'is_students' => !$is_faculty,
                'domains' => [],
                'domains_count' => 0,
                'competencies_count' => 0,
                'levels_count' => 0,
                'mapped_courses_count' => 0,
            ];
        }

        $data['students_url'] = (new moodle_url('/local/meko_competencies/index.php', ['framework' => 'students', 'view' => 'tree']))->out(false);
        $data['faculty_url'] = (new moodle_url('/local/meko_competencies/index.php', ['framework' => 'faculty', 'view' => 'tree']))->out(false);
        $data['tree_view_url'] = (new moodle_url('/local/meko_competencies/index.php', ['framework' => $framework_key, 'view' => 'tree']))->out(false);
        $data['matrix_view_url'] = (new moodle_url('/local/meko_competencies/index.php', ['framework' => $framework_key, 'view' => 'matrix']))->out(false);
        $data['is_tree_view'] = true;
        $data['is_matrix_view'] = false;

        return $this->render_competency_tree($data);
    }
}

if (class_exists('\theme_meko\output\competency_renderer') && !class_exists('\theme_accees_lms_theme\output\competency_renderer')) {
    class_alias('\theme_meko\output\competency_renderer', '\theme_accees_lms_theme\output\competency_renderer');
}

<?php
// This file is part of Moodle - http://moodle.org/
//
// ACCEES-LMS Competency Framework Explorer (UC-LMS-02)
// Route: /local/meko_competencies/index.php
// Public & Student/Faculty Competency Viewer (Tree Explorer & Rubric Matrix)

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/helper.php');

$framework_raw = optional_param('framework', '', PARAM_RAW_TRIMMED);
$focus_competency = optional_param('competency', '', PARAM_ALPHANUMEXT);
if (empty($framework_raw) && !empty($focus_competency) && (stripos($focus_competency, 'GV_') === 0 || stripos($focus_competency, 'FACULTY') !== false)) {
    $framework_raw = 'DIGCOMP_ACCEES_FACULTY';
}
$framework_param = !empty($framework_raw) ? $framework_raw : 'DIGCOMP_ACCEES_STUDENTS';
$res = \local_meko_competencies\helper::resolve_framework_idnumber($framework_param);
$framework_idnumber = $res['idnumber'];
$framework_key = $res['key'];
$is_faculty = $res['is_faculty'];

$view = optional_param('view', 'tree', PARAM_ALPHA);
if (!in_array($view, ['tree', 'matrix'], true)) {
    $view = 'tree';
}
$initial_domain = optional_param('domain', 'ALL', PARAM_ALPHANUMEXT);
$initial_level = strtoupper(optional_param('level', 'ALL', PARAM_ALPHANUMEXT));
$initial_query = optional_param('q', '', PARAM_TEXT);
$lang = \local_meko_competencies\helper::resolve_lang('en');

// Trang công khai cho phép cả khách vãng lai và người dùng đã đăng nhập xem (UC-LMS-02)
// Không gọi require_login để tránh ép chuyển hướng sang màn hình đăng nhập

$context = context_system::instance();
$PAGE->set_context($context);
$url_params = ['framework' => $framework_idnumber, 'view' => $view];
if (!empty($focus_competency)) {
    $url_params['competency'] = $focus_competency;
}
$PAGE->set_url(new moodle_url('/local/meko_competencies/index.php', $url_params));
$PAGE->set_pagelayout('standard');

// 1. Lấy tóm tắt 02 khung động hoàn toàn từ CSDL Moodle theo ID number (không đọc từ CSV)
$students_summary = \local_meko_competencies\helper::get_framework_summary('DIGCOMP_ACCEES_STUDENTS', $lang);
$faculty_summary = \local_meko_competencies\helper::get_framework_summary('DIGCOMP_ACCEES_FACULTY', $lang);

// 2. Lấy dữ liệu phân cấp 3 tầng hoặc ma trận
if ($view === 'matrix') {
    $data = \local_meko_competencies\helper::get_rubric_matrix_data($framework_idnumber, $lang);
} else {
    $data = \local_meko_competencies\helper::get_framework_data($framework_idnumber, $lang);
}

$default_title = ($is_faculty) 
    ? get_string('faculty_framework', 'local_meko_competencies') 
    : get_string('students_framework', 'local_meko_competencies');

$framework_title = !empty($data['framework_title']) ? $data['framework_title'] : $default_title;

$PAGE->set_title($framework_title);
$PAGE->set_heading($framework_title);
$data['framework_title'] = $framework_title;

$data['config'] = ['wwwroot' => $CFG->wwwroot];
$data['is_tree_view'] = ($view === 'tree');
$data['is_matrix_view'] = ($view === 'matrix');
$data['is_faculty'] = $is_faculty;
$data['is_students'] = !$is_faculty;
$data['framework_key'] = $framework_key;
$data['focus_competency'] = $focus_competency;
$data['has_focus_competency'] = !empty($focus_competency);
$data['initial_domain'] = $initial_domain ?: 'ALL';
$data['initial_level'] = $initial_level ?: 'ALL';
$data['initial_query'] = $initial_query;

// Nhãn và số lượng Năng lực được nạp động từ CSDL Moodle
$data['students_idnumber'] = 'DIGCOMP_ACCEES_STUDENTS';
$data['students_tab_label'] = $students_summary['tab_label'];
$data['students_competencies_count'] = $students_summary['competencies_count'];

$data['faculty_idnumber'] = 'DIGCOMP_ACCEES_FACULTY';
$data['faculty_tab_label'] = $faculty_summary['tab_label'];
$data['faculty_competencies_count'] = $faculty_summary['competencies_count'];

$data['tree_view_url'] = (new moodle_url('/local/meko_competencies/index.php', ['framework' => $framework_idnumber, 'view' => 'tree']))->out(false);
$data['matrix_view_url'] = (new moodle_url('/local/meko_competencies/index.php', ['framework' => $framework_idnumber, 'view' => 'matrix']))->out(false);
$data['students_url'] = (new moodle_url('/local/meko_competencies/index.php', ['framework' => 'DIGCOMP_ACCEES_STUDENTS', 'view' => $view]))->out(false);
$data['faculty_url'] = (new moodle_url('/local/meko_competencies/index.php', ['framework' => 'DIGCOMP_ACCEES_FACULTY', 'view' => $view]))->out(false);

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('local_meko_competencies');
if ($view === 'matrix') {
    echo $renderer->render_matrix_view($data);
} else {
    echo $renderer->render_competency_tree($data);
}

echo $OUTPUT->footer();

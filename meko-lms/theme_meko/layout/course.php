<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Course Detail Layout (Learning Hub & Solid Tiles View - UC-LMS-04) for theme_accees_lms_theme.
 *
 * @package    theme_accees_lms_theme
 * @copyright  2026 Mekosoft / Can Tho University (CTU)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/completionlib.php');
require_once(__DIR__ . '/../lib.php');

global $OUTPUT, $PAGE, $COURSE, $CFG, $SITE, $USER, $DB;

$themename = !empty($PAGE->theme->name) ? $PAGE->theme->name : 'meko';
$theme = $PAGE->theme ?? theme_config::load($themename);
$commoncontext = theme_accees_lms_theme_get_common_context($theme, $OUTPUT, $CFG, $SITE);

$course = $PAGE->course;
$coursecontext = context_course::instance($course->id);

// Secondary navigation (Course / Settings / Participants / Grades / Competencies).
$secondarynavigation = false;
$overflow = '';
if ($PAGE->has_secondary_navigation()) {
    $tablistnav = $PAGE->has_tablist_secondary_navigation();
    $moremenu = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
    $secondarynavigation = $moremenu->export_for_template($OUTPUT);
    $overflowdata = null;
    if (method_exists($PAGE->secondarynav, 'get_overflow_menu_data')) {
        $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
    } else if (method_exists($PAGE->secondarynav, 'get_overflow_sort_data')) {
        $overflowdata = $PAGE->secondarynav->get_overflow_sort_data();
    }
    if (!is_null($overflowdata)) {
        $overflow = $overflowdata->export_for_template($OUTPUT);
    }
}

// Category and Teacher metadata.
$categoryname = get_string('nav_courses', 'theme_' . $themename);
$categoryurl = (new moodle_url('/course/index.php'))->out(false);
if (!empty($course->category)) {
    try {
        $category = core_course_category::get($course->category, IGNORE_MISSING);
        if ($category) {
            $categoryname = html_entity_decode($category->get_formatted_name(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $categoryurl = (new moodle_url('/course/index.php', ['categoryid' => $category->id]))->out(false);
        }
    } catch (\Throwable $e) {
        // Keep default category label.
    }
}

$cardmeta = theme_accees_lms_theme_prepare_course_card($course, null, $categoryname);
$teachername = $cardmeta['teacher_name'];
$startdate = !empty($course->startdate) ? userdate($course->startdate, '%d/%m/%Y') : $cardmeta['startdate_formatted'];

$coursesummary = '';
if (!empty($course->summary)) {
    $coursesummary = format_text($course->summary, $course->summaryformat, ['context' => $coursecontext]);
}

$curlang = current_language();
$isvi = ($curlang === 'vi');
$isfr = ($curlang === 'fr');

// Course Format & Sections / Activities inspection.
$format = course_get_format($course);
$formatname = get_string('pluginname', 'format_' . $course->format);
$modinfo = get_fast_modinfo($course);
$completion = new completion_info($course);
$completionenabled = $completion->is_enabled();

$totaltracked = 0;
$completedtracked = 0;
$totalvisibleactivities = 0;

foreach ($modinfo->get_cms() as $cm) {
    if (!$cm->uservisible || $cm->is_stealth()) {
        continue;
    }
    $totalvisibleactivities++;
    if ($completionenabled && $cm->completion != COMPLETION_TRACKING_NONE && isloggedin() && !isguestuser()) {
        $totaltracked++;
        $cmcompletion = $completion->get_data($cm, true, $USER->id);
        if (in_array((int)$cmcompletion->completionstate, [
            COMPLETION_COMPLETE,
            COMPLETION_COMPLETE_PASS,
        ], true)) {
            $completedtracked++;
        }
    }
}

$progresspercent = 0;
if ($totaltracked > 0) {
    $progresspercent = (int)round(($completedtracked / $totaltracked) * 100);
} else if (!empty($cardmeta['progress'])) {
    $progresspercent = (int)$cardmeta['progress'];
}

// Build interactive Solid Tiles sections for course view.
$tilesections = [];
$isediting = $PAGE->user_is_editing();
$sectionparam = optional_param('section', 0, PARAM_INT);
$activesectionnumber = $sectionparam > 0 ? $sectionparam : null;

foreach ($modinfo->get_section_info_all() as $section) {
    if (!$section->uservisible && !$isediting) {
        continue;
    }

    $sectioncmids = $modinfo->sections[$section->section] ?? [];
    if (!$isediting && (int)$section->section === 0 && empty($sectioncmids) && trim(strip_tags((string)$section->summary)) === '') {
        continue;
    }

    $nativeDeleteConfirmationRequired = !empty($section->name) || !empty($section->summary);
    if (!$nativeDeleteConfirmationRequired && $section->uservisible) {
        foreach ($sectioncmids as $cmid) {
            if (empty($modinfo->cms[$cmid])) {
                continue;
            }
            $cm = $modinfo->cms[$cmid];
            if ($cm->is_visible_on_course_page() && $cm->is_of_type_that_can_display()) {
                $nativeDeleteConfirmationRequired = true;
                break;
            }
        }
    }

    $activities = [];
    $sectiontracked = 0;
    $sectioncompleted = 0;

    foreach ($sectioncmids as $cmid) {
        if (empty($modinfo->cms[$cmid])) {
            continue;
        }
        $cm = $modinfo->cms[$cmid];
        if (!$cm->uservisible || $cm->is_stealth()) {
            continue;
        }

        $istracked = $completionenabled && $cm->completion != COMPLETION_TRACKING_NONE && isloggedin() && !isguestuser();
        $iscomplete = false;
        if ($istracked) {
            $sectiontracked++;
            $cmcompletion = $completion->get_data($cm, true, $USER->id);
            $iscomplete = in_array((int)$cmcompletion->completionstate, [
                COMPLETION_COMPLETE,
                COMPLETION_COMPLETE_PASS,
            ], true);
            if ($iscomplete) {
                $sectioncompleted++;
            }
        }

        $description = trim(strip_tags($cm->get_formatted_content(['overflowdiv' => false, 'noclean' => true])));
        if ($description === '') {
            $description = $cm->get_module_type_name();
        } else {
            $description = shorten_text($description, 150);
        }

        $act_status = $iscomplete
            ? ($isvi ? 'Đã hoàn thành' : ($isfr ? 'Terminé' : 'Completed'))
            : ($istracked ? ($isvi ? 'Chưa hoàn thành' : ($isfr ? 'Non terminé' : 'Not completed')) : ($isvi ? 'Học liệu / Hoạt động' : ($isfr ? 'Ressource / Activité' : 'Resource / Activity')));

        $activities[] = [
            'id' => $cm->id,
            'name' => html_entity_decode($cm->get_formatted_name(), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'modulename' => $cm->get_module_type_name(),
            'description' => $description,
            'url' => $cm->url ? $cm->url->out(false) : (new moodle_url('/course/view.php', [
                'id' => $course->id,
                'section' => $section->section,
            ]))->out(false),
            'iconurl' => $cm->get_icon_url()->out(false),
            'istracked' => $istracked,
            'iscomplete' => $iscomplete,
            'statuslabel' => $act_status,
        ];
    }

    $activitycount = count($activities);
    if (!$isediting && (int)$section->section === 0 && $activitycount === 0) {
        continue;
    }

    $progressbase = $sectiontracked > 0 ? $sectiontracked : $activitycount;
    $sectionpercent = $progressbase > 0 ? (int)round(($sectioncompleted / $progressbase) * 100) : 0;
    $sectioncomplete = $sectiontracked > 0 && $sectioncompleted >= $sectiontracked;
    $sectioninprogress = !$sectioncomplete && ($sectioncompleted > 0 || $activitycount > 0);

    if ($activesectionnumber === null && !$sectioncomplete) {
        $activesectionnumber = (int)$section->section;
    }

    $sectionlabel = (int)$section->section === 0
        ? ($isvi ? 'GIỚI THIỆU' : ($isfr ? 'INTRODUCTION' : 'INTRODUCTION'))
        : (($isvi ? 'CHỦ ĐỀ ' : ($isfr ? 'THÈME ' : 'TOPIC ')) . str_pad((string)$section->section, 2, '0', STR_PAD_LEFT));

    $tile_status = $sectioncomplete
        ? ($isvi ? 'Hoàn thành' : ($isfr ? 'Terminé' : 'Completed'))
        : ($sectioncompleted > 0 ? ($isvi ? 'Đang học' : ($isfr ? 'En cours' : 'In Progress')) : ($isvi ? 'Sẵn sàng' : ($isfr ? 'Prêt' : 'Ready')));

    $tilesections[] = [
        'number' => (int)$section->section,
        'tilelabel' => $sectionlabel,
        'name' => html_entity_decode($format->get_section_name($section), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        'ishighlighted' => $format->is_section_current($section),
        'summary' => format_text($section->summary, $section->summaryformat, ['context' => $coursecontext]),
        'nativeDeleteConfirmationRequired' => $nativeDeleteConfirmationRequired,
        'url' => (new moodle_url('/course/view.php', ['id' => $course->id, 'section' => $section->section]))->out(false),
        'activitycount' => $activitycount,
        'completedcount' => $sectioncompleted,
        'trackedcount' => $sectiontracked,
        'progresspercent' => $sectionpercent,
        'iscomplete' => $sectioncomplete,
        'isinprogress' => $sectioninprogress,
        'statuslabel' => $tile_status,
        'hasactivities' => !empty($activities),
        'activities' => $activities,
    ];
}

if ($activesectionnumber === null || !in_array($activesectionnumber, array_column($tilesections, 'number'), true)) {
    if (!empty($tilesections)) {
        $activesectionnumber = $tilesections[0]['number'];
    }
}

$tilecount = count($tilesections);
foreach ($tilesections as $index => $tilesection) {
    $tilesections[$index]['isactive'] = ($tilesection['number'] === $activesectionnumber);
    $tilesections[$index]['prevnumber'] = $index > 0 ? $tilesections[$index - 1]['number'] : null;
    $tilesections[$index]['nextnumber'] = $index < ($tilecount - 1) ? $tilesections[$index + 1]['number'] : null;
    $tilesections[$index]['hasprev'] = $index > 0;
    $tilesections[$index]['hasnext'] = $index < ($tilecount - 1);
}

$istilesformat = !empty($tilesections);

// Linked CBE Competencies lookup (grouped by Framework + Parent Competency, zero internal idnumbers exposed).
$competency_groups = [];
$framework_names = [];
try {
    $sql = "SELECT comp.id,
                   comp.idnumber,
                   comp.shortname,
                   comp.parentid,
                   comp.path,
                   comp.competencyframeworkid,
                   fw.idnumber AS fw_idnumber,
                   fw.shortname AS fw_shortname,
                   parent.id AS parent_id,
                   parent.idnumber AS parent_idnumber,
                   parent.shortname AS parent_shortname
              FROM {competency_coursecomp} cc
              JOIN {competency} comp ON comp.id = cc.competencyid
              JOIN {competency_framework} fw ON fw.id = comp.competencyframeworkid
              LEFT JOIN {competency} parent ON parent.id = comp.parentid
             WHERE cc.courseid = :courseid
          ORDER BY fw.id ASC, comp.sortorder ASC, comp.id ASC";
    $rows = $DB->get_records_sql($sql, ['courseid' => $course->id]) ?: [];

    $domain_names_by_id = [];
    $fwids = [];
    foreach ($rows as $row) {
        $fwids[(int)$row->competencyframeworkid] = (int)$row->competencyframeworkid;
    }
    if (!empty($fwids)) {
        list($fwinsql, $fwinparams) = $DB->get_in_or_equal(array_values($fwids), SQL_PARAMS_NAMED);
        $root_domains = $DB->get_records_select(
            'competency',
            "competencyframeworkid $fwinsql AND parentid = 0",
            $fwinparams,
            '',
            'id, shortname'
        );
        foreach ($root_domains as $rdom) {
            $domain_names_by_id[(int)$rdom->id] = html_entity_decode(
                strip_tags(format_string($rdom->shortname, true, ['escape' => false])),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            );
        }
    }

    $cancheckprof = (class_exists('\core_competency\api') && isloggedin() && !isguestuser());

    foreach ($rows as $row) {
        $fw_id = (int)$row->competencyframeworkid;
        $fw_idnum_upper = strtoupper((string)($row->fw_idnumber ?? ''));
        $comp_idnum = (string)($row->idnumber ?? '');
        $is_faculty_fw = (strpos($fw_idnum_upper, 'FACULTY') !== false || strpos(strtoupper($comp_idnum), 'GV_') === 0);
        $fw_key = $is_faculty_fw ? 'faculty' : 'students';
        $framework_short = $is_faculty_fw
            ? ($isvi ? 'Giảng viên · DigCompEdu' : ($isfr ? 'Enseignants · DigCompEdu' : 'Faculty · DigCompEdu'))
            : ($isvi ? 'Sinh viên · DigComp 2.2' : ($isfr ? 'Étudiants · DigComp 2.2' : 'Students · DigComp 2.2'));

        $fw_full_label = !empty($row->fw_shortname)
            ? format_string($row->fw_shortname, true, ['escape' => false])
            : $framework_short;
        $framework_names[$fw_id] = $fw_full_label;

        $domain_name = '';
        if (empty((int)$row->parentid)) {
            $domain_name = $domain_names_by_id[(int)$row->id] ?? '';
        } else if (!empty($row->path)) {
            $path_parts = array_values(array_filter(array_map('intval', explode('/', (string)$row->path)), function($id) {
                return $id > 0;
            }));
            if (!empty($path_parts) && isset($domain_names_by_id[$path_parts[0]])) {
                $domain_name = $domain_names_by_id[$path_parts[0]];
            }
        }

        $comp_title = !empty($row->parent_shortname)
            ? html_entity_decode(strip_tags(format_string($row->parent_shortname, true, ['escape' => false])), ENT_QUOTES | ENT_HTML5, 'UTF-8')
            : html_entity_decode(strip_tags(format_string($row->shortname, true, ['escape' => false])), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $parsed_lvl = theme_accees_lms_theme_parse_level_info($comp_idnum, (string)$row->shortname, $curlang);
        $level_label = $parsed_lvl ? $parsed_lvl['label'] : get_string('all_levels', 'theme_' . $themename);
        $level_key = $parsed_lvl ? $parsed_lvl['key'] : 'default';
        $level_order = $parsed_lvl ? (int)$parsed_lvl['order'] : 999;

        $isproficient = false;
        $gradelabel = $isvi ? 'Chuẩn đầu ra' : ($isfr ? 'Compétence cible' : 'Target Outcome');
        if ($cancheckprof) {
            try {
                $usercomp = \core_competency\api::get_user_competency_in_course($course->id, $USER->id, (int)$row->id);
                $isproficient = (bool)$usercomp->get('proficiency');
                $gradelabel = $isproficient
                    ? ($isvi ? 'Đạt chuẩn CBE' : ($isfr ? 'Compétence acquise' : 'CBE Proficient'))
                    : ($isvi ? 'Đang tích lũy' : ($isfr ? 'En acquisition' : 'In Progress'));
            } catch (\Throwable $e) {
                $gradelabel = $isvi ? 'Chuẩn đầu ra' : ($isfr ? 'Compétence cible' : 'Target Outcome');
            }
        }
        $level_full_title = html_entity_decode(
            strip_tags(format_string($row->shortname, true, ['escape' => false])),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        ) . ' · ' . $gradelabel;

        $group_parent_id = !empty((int)$row->parentid) ? (int)$row->parentid : (int)$row->id;
        $group_key = $fw_id . '_' . $group_parent_id;

        if (!isset($competency_groups[$group_key])) {
            $competency_groups[$group_key] = [
                'framework_short' => $framework_short,
                'domain_name' => $domain_name,
                'competency_name' => $comp_title,
                'hasproficient' => false,
                'levels' => [],
            ];
        }
        if ($isproficient) {
            $competency_groups[$group_key]['hasproficient'] = true;
        }
        $competency_groups[$group_key]['levels'][] = [
            'id' => (int)$row->id,
            'label' => $level_label,
            'level_key' => $level_key,
            'order' => $level_order,
            'isproficient' => $isproficient,
            'statuslabel' => $level_full_title,
            'url' => (new moodle_url('/course/index.php', [
                'competencyid' => $comp_idnum,
                'framework' => $fw_key,
            ]))->out(false),
        ];
    }
} catch (\Throwable $e) {
    $competency_groups = [];
}

$competencies = [];
foreach ($competency_groups as $grp) {
    usort($grp['levels'], function($a, $b) {
        return ((int)$a['order']) <=> ((int)$b['order']);
    });
    $competencies[] = $grp;
}

if (count($framework_names) > 1) {
    $frameworkname = $isvi
        ? 'Sinh viên (DigComp 2.2) · Giảng viên (DigCompEdu)'
        : ($isfr ? 'Étudiants (DigComp 2.2) · Enseignants (DigCompEdu)' : 'Students (DigComp 2.2) · Faculty (DigCompEdu)');
} else if (count($framework_names) === 1) {
    $frameworkname = reset($framework_names);
} else {
    $frameworkname = '';
}

$ui = [
    'lbl_course_code' => $isvi ? 'Mã học phần:' : ($isfr ? 'Code cours :' : 'Course Code:'),
    'lbl_cbe' => $isvi ? 'Chuẩn CBE:' : ($isfr ? 'Compétence CBE :' : 'CBE Competency:'),
    'lbl_instructor' => $isvi ? 'Giảng viên:' : ($isfr ? 'Enseignant :' : 'Instructor:'),
    'lbl_startdate' => $isvi ? 'Khai giảng:' : ($isfr ? 'Début :' : 'Starts:'),
    'lbl_institution' => $isvi ? 'Đơn vị:' : ($isfr ? 'Établissement :' : 'Institution:'),
    'lbl_criteria' => $isvi ? 'Tiêu chí đạt:' : ($isfr ? 'Critères de validation :' : 'Passing Criteria:'),
    'lbl_progress_title' => $isvi ? 'Tiến độ hoàn thành khóa học' : ($isfr ? 'Progression du cours' : 'Course Completion Progress'),
    'lbl_completed_prefix' => $isvi ? 'Hoàn thành' : ($isfr ? 'Terminé' : 'Completed'),
    'lbl_activities_suffix' => $isvi ? 'hoạt động học tập' : ($isfr ? 'activités d\'apprentissage' : 'learning activities'),
    'lbl_tracked_auto' => $isvi ? 'Theo dõi tự động' : ($isfr ? 'Suivi automatisé' : 'Automated tracking'),
    'lbl_tracked_self' => $isvi ? 'Chế độ tự học' : ($isfr ? 'Auto-apprentissage' : 'Self-paced'),
    'lbl_progress_note' => $isvi
        ? 'Hoàn thành đầy đủ các học liệu, bài tập thực hành và bài kiểm tra trắc nghiệm cuối khóa để được cấp nhận chuẩn đầu ra năng lực số.'
        : ($isfr
            ? 'Complétez les ressources, exercices pratiques et le quiz final pour valider vos compétences numériques CBE.'
            : 'Complete all learning materials, practical assignments, and final quiz assessments to achieve official CBE digital competence certification.'),
    'lbl_cbe_outcomes' => $isvi ? 'Chuẩn đầu ra Năng lực số (CBE)' : ($isfr ? 'Référentiel de Compétences Numériques (CBE)' : 'Digital Competence Outcomes (CBE)'),
    'lbl_cbe_details' => $isvi ? 'Xem chi tiết trên Cây & Ma trận Năng lực số' : ($isfr ? 'Voir l\'arbre et la matrice des compétences' : 'View on Competency Tree & Rubric Matrix'),
    'lbl_cbe_search' => $isvi ? 'Tra cứu Cây & Ma trận Năng lực số' : ($isfr ? 'Explorer l\'arbre et la matrice des compétences' : 'Explore Competency Tree & Rubric Matrix'),
    'lbl_sections_title' => $isvi ? 'Cấu trúc học phần & Học liệu số' : ($isfr ? 'Structure du cours & Ressources numériques' : 'Course Structure & Learning Materials'),
    'lbl_topic_prefix' => $isvi ? 'CHỦ ĐỀ ' : ($isfr ? 'THÈME ' : 'TOPIC '),
    'lbl_general_tile' => $isvi ? 'GIỚI THIỆU' : 'INTRODUCTION',
    'lbl_ready' => $isvi ? 'Sẵn sàng' : ($isfr ? 'Prêt' : 'Ready'),
    'lbl_highlighted' => $isvi ? 'Đang làm nổi bật' : ($isfr ? 'En vedette' : 'Highlighted'),
    'lbl_delete_empty_section_confirm' => $isvi
        ? 'Bạn có chắc muốn xóa chủ đề trống này không? Thao tác này không thể hoàn tác.'
        : ($isfr
            ? 'Confirmez-vous la suppression de cette section vide ? Cette action est irréversible.'
            : 'Are you sure you want to delete this empty section? This action cannot be undone.'),
    'lbl_delete_empty_section_title' => $isvi ? 'Xóa chủ đề?' : ($isfr ? 'Supprimer la section ?' : 'Delete section?'),
    'lbl_delete_cancel' => $isvi ? 'Hủy' : ($isfr ? 'Annuler' : 'Cancel'),
    'lbl_delete_confirm' => $isvi ? 'Xóa chủ đề' : ($isfr ? 'Supprimer' : 'Delete section'),
    'lbl_sections_desc' => $isvi
        ? 'Chọn một ô chủ đề (Tile) bên dưới để mở danh sách bài giảng, học liệu số DLRP và bài kiểm tra đánh giá tương ứng.'
        : ($isfr
            ? 'Sélectionnez un thème ci-dessous pour afficher les cours, les ressources DLRP et les évaluations.'
            : 'Select a tile below to view lectures, DLRP digital resources, and corresponding assessments.'),
    'lbl_edit_sections_desc' => $isvi
        ? 'Các chủ đề được hiển thị dạng thẻ. Dùng công cụ Moodle ngay trong từng thẻ để quản lý nội dung và hoạt động.'
        : ($isfr
            ? 'Les thèmes sont affichés sous forme de cartes. Utilisez les commandes Moodle de chaque carte pour gérer son contenu et ses activités.'
            : 'Sections are displayed as cards. Use the Moodle controls inside each card to manage its content and activities.'),
    'lbl_prev_section' => $isvi ? 'Chủ đề trước' : ($isfr ? 'Thème précédent' : 'Previous topic'),
    'lbl_next_section' => $isvi ? 'Chủ đề tiếp' : ($isfr ? 'Thème suivant' : 'Next topic'),
    'lbl_empty_section' => $isvi
        ? 'Chủ đề này hiện chưa có hoạt động học tập nào được công bố.'
        : ($isfr ? 'Aucune activité n\'est publiée dans ce thème pour le moment.' : 'No learning activities are currently published in this section.'),
    'lbl_content_title' => $isvi ? 'Nội dung khóa học & Hoạt động' : ($isfr ? 'Contenu du cours & Activités' : 'Course Content & Activities'),
    'lbl_content_desc' => $isvi ? 'Danh sách các chủ đề, học liệu và hoạt động đánh giá của khóa học.' : ($isfr ? 'Liste des thèmes, ressources et évaluations du cours.' : 'List of course topics, resources, and evaluation activities.'),
];

$competencyreporturl = (new moodle_url('/local/meko_competencies/index.php'))->out(false);

$maincontent = $OUTPUT->main_content();

$templatecontext = array_merge($commoncontext, [
    'ui' => $ui,
    'courseid' => $course->id,
    'coursefullname' => html_entity_decode(format_string($course->fullname, true, ['escape' => false]), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
    'courseshortname' => html_entity_decode(format_string($course->shortname, true, ['escape' => false]), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
    'courseidnumber' => !empty($course->idnumber) ? s($course->idnumber) : html_entity_decode(format_string($course->shortname, true, ['escape' => false]), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
    'categoryname' => html_entity_decode($categoryname, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
    'categoryurl' => $categoryurl,
    'teachername' => $teachername,
    'startdate' => $startdate,
    'formatname' => $formatname,
    'delivery_mode' => $cardmeta['delivery_mode'],
    'duration' => $cardmeta['duration'],
    'institution' => $cardmeta['institution'],
    'domain_pill' => $cardmeta['domain_pill'],
    'cbe_badge_label' => $cardmeta['cbe_badge_label'],
    'competency_level_tooltip' => $cardmeta['competency_level_tooltip'],
    'target_competency_code' => $cardmeta['target_competency_code'],
    'competency_title' => $cardmeta['competency_title'],
    'passing_criteria' => $cardmeta['passing_criteria'],
    'coursesummary' => $coursesummary,
    'hascoursesummary' => trim(strip_tags($coursesummary)) !== '',
    'completionenabled' => $completionenabled,
    'progresspercent' => $progresspercent,
    'completedactivities' => $completedtracked,
    'totalactivities' => $totaltracked > 0 ? $totaltracked : $totalvisibleactivities,
    'hascompetencies' => !empty($competencies),
    'competencies' => $competencies,
    'frameworkname' => $frameworkname,
    'competencyreporturl' => $competencyreporturl,
    'istilesformat' => $istilesformat,
    'hastilesections' => !empty($tilesections),
    'tilesections' => $tilesections,
    'isediting' => $isediting,
    'secondarymoremenu' => $secondarynavigation ?: false,
    'overflow' => $overflow,
    'headeractions' => $PAGE->get_header_actions(),
    'hasheaderactions' => !empty($PAGE->get_header_actions()),
    'activityheader' => method_exists($OUTPUT, 'activity_header') ? $OUTPUT->activity_header() : '',
    'maincontent' => $maincontent,
]);

$bodyattributes = $OUTPUT->body_attributes(['accees-course-page']);

echo $OUTPUT->doctype();
?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <?php echo $OUTPUT->standard_head_html(); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body <?php echo $bodyattributes; ?>>
<?php echo $OUTPUT->standard_top_of_body_html(); ?>

<div id="page-wrapper" class="d-flex flex-column min-vh-100">
    <!-- Top Navbar -->
    <?php echo $OUTPUT->render_from_template('theme_' . $themename . '/navbar', $templatecontext); ?>

    <!-- Main Course Learning Hub -->
    <div id="page" class="drawers flex-grow-1">
        <?php echo $OUTPUT->render_from_template('theme_' . $themename . '/course_detail', $templatecontext); ?>
    </div>

    <!-- Footer -->
    <?php echo $OUTPUT->render_from_template('theme_' . $themename . '/footer', $templatecontext); ?>
</div>

<?php echo $OUTPUT->standard_end_of_body_html(); ?>
</body>
</html>

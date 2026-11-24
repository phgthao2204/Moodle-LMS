<?php
// This file is part of Moodle - http://moodle.org/
//
// Course renderer override for theme_meko.
// Adheres 100% to Moodle Core Course Category architecture (Zero Core Hack).
// Integrates Competency-Based Education (CBE) Target Level Mapping (UC-LMS-02).

namespace theme_meko\output;

defined('MOODLE_INTERNAL') || die();

global $CFG;

// Safely load Moodle core course renderer with fallback for split webroot containers
if (!class_exists('\core_course_renderer')) {
    $candidate_paths = [
        ($CFG->dirroot ?? '') . '/course/renderer.php',
        ($CFG->dirroot ?? '') . '/public/course/renderer.php',
        dirname(__DIR__, 4) . '/course/renderer.php',
        dirname(__DIR__, 5) . '/course/renderer.php',
        ($CFG->libdir ?? '') . '/../course/renderer.php',
        '/var/www/html/public/course/renderer.php',
        '/var/www/html/course/renderer.php',
    ];
    foreach ($candidate_paths as $path) {
        if (!empty($path) && file_exists($path)) {
            require_once($path);
            break;
        }
    }
}

use core_course_category;
use moodle_url;
use html_writer;

if (class_exists('\core_course_renderer') && !class_exists('\theme_accees_lms_theme\output\core_course_renderer')) {
    class core_course_renderer extends \core_course_renderer {

    /**
     * Override index_page (/course/index.php without categoryid parameter)
     * Directs to course_category to enable competency filtering at top level.
     *
     * @return string
     */
    public function index_page() {
        $top = core_course_category::top();
        return $this->course_category($top);
    }

    /**
     * Override category_page (/course/index.php?categoryid=X)
     *
     * @param int|stdClass $categoryid
     * @return string
     */
    public function category_page($categoryid) {
        $category = core_course_category::get($categoryid, MUST_EXIST);
        return $this->course_category($category);
    }

    /**
     * Override coursecat_page
     *
     * @param core_course_category|null $category
     * @return string
     */
    public function coursecat_page($category = null) {
        $category = $category ?: core_course_category::top();
        return $this->course_category($category);
    }

    /**
     * Override hàm render trang Danh mục khóa học (/course/index.php)
     * Hỗ trợ lọc đồng thời Lĩnh vực đào tạo + Bậc Năng Lực Số (?competencyid={lvl_idnumber})
     *
     * Method signature must match parent \core_course_renderer::course_category($category) exactly
     * without narrowing typehint to avoid PHP 8 LSP Fatal Error (exit 255).
     *
     * @param int|stdClass|core_course_category $category
     * @return string
     */
    public function course_category($category) {
        global $CFG, $DB;

        if (is_numeric($category)) {
            $category = core_course_category::get((int)$category, IGNORE_MISSING);
        } else if (!($category instanceof core_course_category) && is_object($category) && isset($category->id)) {
            $category = core_course_category::get((int)$category->id, IGNORE_MISSING);
        }
        if (!($category instanceof core_course_category)) {
            $category = core_course_category::top();
        }

        $curlang = current_language();
        $isvi = ($curlang === 'vi');
        $isfr = ($curlang === 'fr');
        $cat_en_map = [
            'Biên Soạn & Thiết Kế Học Liệu Số Đa Phương Tiện' => $isfr ? 'Conception de Ressources Numériques Multimédias' : 'Multimedia Digital Learning Resource Design',
            'Khai Thác & Phân Tích Dữ Liệu Học Tập (Analytics)' => $isfr ? 'Analytique des Données d\'Apprentissage' : 'Learning Data Mining & Analytics',
            'Đổi Mới Phương Pháp Dạy Học Tương Tác & Trực Tuyến' => $isfr ? 'Innovation Pédagogique Interactive & En Ligne' : 'Interactive & Online Pedagogical Innovation',
            'Bảo Mật Dữ Liệu & An Toàn Số Trong GDĐH' => $isfr ? 'Sécurité des Données & Cybersécurité en ES' : 'Data Protection & Digital Safety in Higher Ed',
            'Ứng Dụng Trí Tuệ Nhân Tạo (AI) Trong Giảng Dạy' => $isfr ? 'Intelligence Artificielle (IA) pour l\'Enseignement' : 'Artificial Intelligence (AI) in Teaching & Assessment',
            'Quản Lý & Chuyển Đổi Số Cơ Sở GDĐH' => $isfr ? 'Gouvernance & Transformation Numérique en ES' : 'Higher Education Digital Transformation & Governance',
        ];

        $categoryid = (int)$category->id;
$categoryname = ($categoryid > 0) ? html_entity_decode(format_string($category->name), ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
        if (!$isvi && isset($cat_en_map[$categoryname])) {
            $categoryname = $cat_en_map[$categoryname];
        }
        $categorydesc = ($categoryid > 0) ? format_text($category->description, $category->descriptionformat) : '';

        // 1. Kiểm tra tham số lọc, phân trang & sắp xếp
        $competency_param = optional_param('competencyid', '', PARAM_ALPHANUMEXT);
        $page = max(0, optional_param('page', 0, PARAM_INT));
        $perpage = 12;
        $sort = optional_param('sort', 'default', PARAM_ALPHA);
        if (!in_array($sort, ['default', 'fullname', 'startdate'], true)) {
            $sort = 'default';
        }
        $active_fw_param = optional_param('framework', '', PARAM_ALPHA);

        $competency_filter = null;
        $courses = [];

        // Preload danh mục từ DB để tra cứu nhanh path và tên
        $all_cats = [];
        try {
            $all_cats = $DB->get_records('course_categories', null, '', 'id, parent, path, name');
        } catch (\Throwable $e) {}

        if ($categoryid > 0 && empty($categoryname) && isset($all_cats[$categoryid])) {
$categoryname = html_entity_decode(format_string($all_cats[$categoryid]->name), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (!$isvi && isset($cat_en_map[$categoryname])) {
                $categoryname = $cat_en_map[$categoryname];
            }
        }

        // Lấy danh sách Top-level categories Moodle gốc
        $top_categories = [];
        try {
            $topcategory = core_course_category::top();
            $top_categories = $topcategory->get_children();
        } catch (\Throwable $t) {
            $top_categories = [];
        }

        // Helper class ensuring local_meko_competencies\helper is available
        if (!class_exists('\local_meko_competencies\helper')) {
            $helper_path = $CFG->dirroot . '/local/meko_competencies/classes/helper.php';
            if (file_exists($helper_path)) {
                require_once($helper_path);
            }
        }

        // 2. Xử lý Lọc theo Bậc Năng lực (Target Level / Competency / Domain)
        if (!empty($competency_param)) {
            $details = null;
            if (class_exists('\local_meko_competencies\helper')) {
                $lang = \local_meko_competencies\helper::resolve_lang('en');
                $details = \local_meko_competencies\helper::find_competency_details($competency_param, $lang, $active_fw_param);
            }

            $compid = 0;
            if ($details && !empty($details['competency_id'])) {
                $compid = (int)$details['competency_id'];
            }
            if ($compid === 0 && !empty($competency_param)) {
                try {
                    $comp_recs = $DB->get_records('competency', ['idnumber' => $competency_param], 'competencyframeworkid ASC, id ASC', 'id', 0, 1);
                    if (!empty($comp_recs)) {
                        $comp_rec = reset($comp_recs);
                        $compid = (int)$comp_rec->id;
                    }
                } catch (\Throwable $e) {}
            }

            $tier = $details ? (int)($details['tier'] ?? 3) : 3;
            $resolved_fw = ($details && !empty($details['framework_key']))
                ? $details['framework_key']
                : ((strpos($competency_param, 'GV_') === 0 || strpos($competency_param, 'FACULTY') !== false) ? 'faculty' : 'students');

            $sibling_levels = [];
            if ($details && !empty($details['sibling_levels'])) {
                if (!empty($details['parent_code'])) {
                    $sibling_levels[] = [
                        'idnumber' => $details['parent_code'],
                        'shortname' => $details['parent_name'] ?: $details['competency_name'],
                        'badge_label' => get_string('all_levels', 'theme_meko'),
                        'badge_class' => 'badge-solid-primary',
                        'level_key' => 'all',
                        'url' => $this->build_catalog_url([
                            'categoryid' => $categoryid,
                            'competencyid' => $details['parent_code'],
                            'framework' => $details['framework_key'],
                            'sort' => $sort,
                        ]),
                        'is_current' => ($tier === 2),
                    ];
                }
                foreach ($details['sibling_levels'] as $sib) {
                    $sibling_levels[] = [
                        'idnumber' => $sib['idnumber'],
                        'shortname' => $sib['shortname'],
                        'badge_label' => $sib['level_badge'],
                        'badge_class' => $sib['badge_class'] ?? 'badge-solid-primary',
                        'level_key' => $sib['level_key'] ?? 'default',
                        'url' => $this->build_catalog_url([
                            'categoryid' => $categoryid,
                            'competencyid' => $sib['idnumber'],
                            'framework' => $details['framework_key'],
                            'sort' => $sort,
                        ]),
                        'is_current' => !empty($sib['is_current']),
                    ];
                }
            }

            // Truy vấn từ {competency_coursecomp} và {course} (hỗ trợ cả Node con khi lọc theo Năng lực mẹ / Miền).
            $courses_found = [];
            if ($compid > 0) {
                try {
                    $path_pattern = '%/' . $compid . '/%';
                    $sql = "SELECT c.*
                              FROM {course} c
                             WHERE c.visible = 1
                               AND c.id <> :siteid
                               AND EXISTS (
                                   SELECT 1
                                     FROM {competency_coursecomp} cc
                                     JOIN {competency} comp ON comp.id = cc.competencyid
                                    WHERE cc.courseid = c.id
                                      AND (comp.id = :compid1
                                           OR comp.parentid = :compid2
                                           OR " . $DB->sql_like('comp.path', ':pathlike', false, false) . ")
                               )
                          ORDER BY c.sortorder ASC, c.fullname ASC";
                    $courses_found = $DB->get_records_sql($sql, [
                        'siteid' => SITEID,
                        'compid1' => $compid,
                        'compid2' => $compid,
                        'pathlike' => $path_pattern,
                    ]);
                } catch (\Throwable $e) {}
            }

            // Nếu $compid chưa xác định được trước đó, tìm trực tiếp theo idnumber của competency qua JOIN.
            if ($compid === 0 && empty($courses_found) && !empty($competency_param)) {
                try {
                    $sql = "SELECT c.*
                              FROM {course} c
                             WHERE c.visible = 1
                               AND c.id <> :siteid
                               AND EXISTS (
                                   SELECT 1
                                     FROM {competency_coursecomp} cc
                                     JOIN {competency} comp ON comp.id = cc.competencyid
                                    WHERE cc.courseid = c.id
                                      AND comp.idnumber = :compcode
                               )
                          ORDER BY c.sortorder ASC, c.fullname ASC";
                    $courses_found = $DB->get_records_sql($sql, ['compcode' => $competency_param, 'siteid' => SITEID]);
                } catch (\Throwable $e) {}
            }

            // Lọc theo Category nếu người dùng chọn cụ thể 1 Danh mục đào tạo
            if ($categoryid > 0 && !empty($courses_found)) {
                $filtered_courses = [];
                foreach ($courses_found as $c) {
                    if ((int)$c->category === (int)$categoryid) {
                        $filtered_courses[] = $c;
                    } else if (isset($all_cats[$c->category]) && strpos($all_cats[$c->category]->path . '/', '/' . $categoryid . '/') !== false) {
                        $filtered_courses[] = $c;
                    }
                }
                $courses = $filtered_courses;
            } else {
                $courses = array_values($courses_found);
            }

            if ($details) {
                $level_badge_display = ($tier === 3)
                    ? $details['level_badge']
                    : get_string('all_levels', 'theme_meko');
                $chip_label = ($tier === 3)
                    ? get_string('competence_chip_label', 'theme_meko')
                    : ($tier === 2
                        ? get_string('tier_competency', 'theme_meko')
                        : get_string('tier_domain', 'theme_meko'));
                $parent_url = ($tier === 3 && !empty($details['parent_code']))
                    ? $this->build_catalog_url([
                        'categoryid' => $categoryid,
                        'competencyid' => $details['parent_code'],
                        'framework' => $details['framework_key'],
                        'sort' => $sort,
                    ])
                    : '';
                $domain_url = ($tier > 1 && !empty($details['domain_code']))
                    ? $this->build_catalog_url([
                        'categoryid' => $categoryid,
                        'competencyid' => $details['domain_code'],
                        'framework' => $details['framework_key'],
                        'sort' => $sort,
                    ])
                    : '';

                $competency_filter = [
                    'competency_id' => $compid,
                    'tier' => $tier,
                    'is_tier_1' => ($tier === 1),
                    'is_tier_2' => ($tier === 2),
                    'is_tier_3' => ($tier === 3),
                    'chip_label' => $chip_label,
                    'competency_code' => $details['competency_code'],
                    'competency_name' => $details['competency_name'],
                    'competency_desc' => $details['competency_desc'],
                    'framework_key' => $details['framework_key'],
                    'framework_name' => $details['framework_name'],
                    'framework_short_label' => get_string(
                        $details['framework_key'] === 'faculty' ? 'teacher_tab' : 'student_tab',
                        'theme_meko'
                    ),
                    'framework_idnumber' => $details['framework_idnumber'],
                    'domain_code' => $details['domain_code'],
                    'domain_name' => $details['domain_name'],
                    'domain_url' => $domain_url,
                    'parent_code' => ($tier === 1) ? '' : $details['parent_code'],
                    'parent_name' => ($tier === 1) ? '' : $details['parent_name'],
                    'parent_url' => $parent_url,
                    'level_badge' => $level_badge_display,
                    'badge_class' => $details['badge_class'] ?? 'badge-solid-primary',
                    'level_key' => $details['level_key'] ?? 'default',
                    'sibling_levels' => $sibling_levels,
                    'has_siblings' => !empty($sibling_levels),
                    'all_courses_url' => $this->build_catalog_url(['sort' => $sort]),
                    'clear_competency_url' => $this->build_catalog_url([
                        'categoryid' => $categoryid,
                        'framework' => $active_fw_param,
                        'sort' => $sort,
                    ]),
                    'relax_category_url' => $this->build_catalog_url([
                        'competencyid' => $competency_param,
                        'framework' => $details['framework_key'],
                        'sort' => $sort,
                    ]),
                    'relax_competency_url' => $this->build_catalog_url([
                        'categoryid' => $categoryid,
                        'framework' => $active_fw_param,
                        'sort' => $sort,
                    ]),
                    'tree_view_url' => (new moodle_url('/local/meko_competencies/index.php', [
                        'framework' => $details['framework_key'],
                        'view' => 'tree',
                        'competency' => $competency_param,
                    ]))->out(false),
                ];
            }

            // Xây dựng cây Lĩnh vực đào tạo đếm số lượng khóa học CHÍNH XÁC theo competency đang lọc
            $categories_tree = [];
            $categories_tree[] = [
                'id' => 0,
                'name' => get_string('all_categories', 'theme_meko'),
                'course_count' => count($courses_found),
                'url' => $this->build_catalog_url([
                    'competencyid' => $competency_param,
                    'framework' => $resolved_fw,
                    'sort' => $sort,
                ]),
                'isactive' => ($categoryid === 0),
            ];

            foreach ($top_categories as $cat) {
                $cat_count = 0;
                foreach ($courses_found as $cf) {
                    if ((int)$cf->category === (int)$cat->id) {
                        $cat_count++;
                    } else if (isset($all_cats[$cf->category]) && strpos($all_cats[$cf->category]->path . '/', '/' . $cat->id . '/') !== false) {
                        $cat_count++;
                    }
                }
                $decoded_cat_name = html_entity_decode(format_string($cat->name), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if ($cat_count <= 0 && ((int)$cat->id === 1 || stripos($decoded_cat_name, 'Category 1') !== false || stripos($decoded_cat_name, 'Miscellaneous') !== false)) {
                    continue;
                }
                if (!$isvi && isset($cat_en_map[$decoded_cat_name])) {
                    $decoded_cat_name = $cat_en_map[$decoded_cat_name];
                }
                $is_cat_active = ((int)$cat->id === (int)$categoryid || (isset($all_cats[$categoryid]) && strpos($all_cats[$categoryid]->path . '/', '/' . $cat->id . '/') !== false));
                $categories_tree[] = [
                    'id' => $cat->id,
'name' => !empty($decoded_cat_name) ? $decoded_cat_name : format_string($cat->name, true, ['escape' => false]),
                    'course_count' => $cat_count,
                    'url' => $this->build_catalog_url([
                        'categoryid' => $cat->id,
                        'competencyid' => $competency_param,
                        'framework' => $resolved_fw,
                        'sort' => $sort,
                    ]),
                    'isactive' => $is_cat_active,
                ];
            }
        } else {
            // Không lọc theo competency: Lấy danh sách khóa học thông thường
            try {
                $courses = $category->get_courses(['recursive' => true]);
            } catch (\Throwable $e) {
                $courses = [];
            }

            // Fallback an toàn nếu guest hoặc permission chặn get_courses()
            if (empty($courses)) {
                if ($categoryid > 0) {
                    $cat_ids = [$categoryid];
                    foreach ($all_cats as $c_id => $c_obj) {
                        if (strpos($c_obj->path . '/', '/' . $categoryid . '/') !== false) {
                            $cat_ids[] = (int)$c_id;
                        }
                    }
                    $cat_ids = array_unique($cat_ids);
                    list($insql, $inparams) = $DB->get_in_or_equal($cat_ids, SQL_PARAMS_NAMED);
                    $inparams['siteid'] = SITEID;
                    $courses = $DB->get_records_select('course', "category $insql AND visible = 1 AND id <> :siteid", $inparams, 'sortorder ASC, fullname ASC');
                } else {
                    $courses = $DB->get_records_select('course', "visible = 1 AND id <> :siteid", ['siteid' => SITEID], 'sortorder ASC, fullname ASC');
                }
            }

            // Đếm số lượng khóa học chính xác trực tiếp từ cơ sở dữ liệu
            $counts_by_cat = [];
            try {
                $counts_by_cat = $DB->get_records_sql("
                    SELECT c.category, COUNT(c.id) AS cnt
                      FROM {course} c
                     WHERE c.visible = 1 AND c.id <> :siteid
                  GROUP BY c.category
                ", ['siteid' => SITEID]);
            } catch (\Throwable $e) {}

            $total_site_courses = 0;
            foreach ($counts_by_cat as $rec) {
                $total_site_courses += (int)$rec->cnt;
            }

            $categories_tree = [];
            $categories_tree[] = [
                'id' => 0,
                'name' => get_string('all_categories', 'theme_meko'),
                'course_count' => $total_site_courses,
                'url' => $this->build_catalog_url(['framework' => $active_fw_param, 'sort' => $sort]),
                'isactive' => ($categoryid === 0),
            ];

            foreach ($top_categories as $cat) {
                $cat_total = 0;
                foreach ($counts_by_cat as $cat_id => $rec) {
                    if ((int)$cat_id === (int)$cat->id) {
                        $cat_total += (int)$rec->cnt;
                    } else if (isset($all_cats[$cat_id]) && strpos($all_cats[$cat_id]->path . '/', '/' . $cat->id . '/') !== false) {
                        $cat_total += (int)$rec->cnt;
                    }
                }
                $decoded_cat_name = html_entity_decode(format_string($cat->name), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if ($cat_total <= 0 && ((int)$cat->id === 1 || stripos($decoded_cat_name, 'Category 1') !== false || stripos($decoded_cat_name, 'Miscellaneous') !== false)) {
                    continue;
                }
                if (!$isvi && isset($cat_en_map[$decoded_cat_name])) {
                    $decoded_cat_name = $cat_en_map[$decoded_cat_name];
                }
                $is_cat_active = ((int)$cat->id === (int)$categoryid || (isset($all_cats[$categoryid]) && strpos($all_cats[$categoryid]->path . '/', '/' . $cat->id . '/') !== false));
                $categories_tree[] = [
                    'id' => $cat->id,
'name' => !empty($decoded_cat_name) ? $decoded_cat_name : format_string($cat->name, true, ['escape' => false]),
                    'course_count' => $cat_total,
                    'url' => $this->build_catalog_url([
                        'categoryid' => $cat->id,
                        'framework' => $active_fw_param,
                        'sort' => $sort,
                    ]),
                    'isactive' => $is_cat_active,
                ];
            }
        }

        // Resolve active framework tab BEFORE pagination/sorting so all links preserve framework consistently
        $active_fw = $active_fw_param;
        if (empty($active_fw)) {
            if ($competency_filter && !empty($competency_filter['framework_key'])) {
                $active_fw = $competency_filter['framework_key'];
            } else if (!empty($competency_param) && (strpos($competency_param, 'GV_') === 0 || strpos($competency_param, 'FACULTY') !== false)) {
                $active_fw = 'faculty';
            } else {
                $active_fw = 'students';
            }
        }
        if (!in_array($active_fw, ['students', 'faculty'], true)) {
            $active_fw = 'students';
        }

        // 3. Sắp xếp & Phân trang chuẩn Moodle Core
        $courses_list = array_values($courses);
        if ($sort === 'fullname') {
            usort($courses_list, function($a, $b) {
                return strcasecmp((string)($a->fullname ?? ''), (string)($b->fullname ?? ''));
            });
        } else if ($sort === 'startdate') {
            usort($courses_list, function($a, $b) {
                $cmp = ((int)($b->startdate ?? 0)) <=> ((int)($a->startdate ?? 0));
                if ($cmp !== 0) {
                    return $cmp;
                }
                return strcasecmp((string)($a->fullname ?? ''), (string)($b->fullname ?? ''));
            });
        }

        $total_courses = count($courses_list);
        $offset = $page * $perpage;
        if ($total_courses > 0 && $offset >= $total_courses) {
            $page = 0;
            $offset = 0;
        }
        $paged_courses = array_slice($courses_list, $offset, $perpage);
        $showing_from = $total_courses > 0 ? ($offset + 1) : 0;
        $showing_to = $total_courses > 0 ? min($total_courses, $offset + count($paged_courses)) : 0;

        $paging_url_params = [];
        if ($categoryid > 0) {
            $paging_url_params['categoryid'] = $categoryid;
        }
        if (!empty($competency_param)) {
            $paging_url_params['competencyid'] = $competency_param;
        }
        if (!empty($active_fw_param)) {
            $paging_url_params['framework'] = $active_fw_param;
        }
        if ($sort !== 'default') {
            $paging_url_params['sort'] = $sort;
        }
        $paging_baseurl = new moodle_url('/course/index.php', $paging_url_params);
        $pagination_html = ($total_courses > $perpage)
            ? $this->output->paging_bar($total_courses, $page, $perpage, $paging_baseurl)
            : '';

        $courses_data = [];
        foreach ($paged_courses as $c) {
            $cat_name = '';
            if (!empty($c->category) && isset($all_cats[$c->category])) {
                $cat_name = format_string($all_cats[$c->category]->name, true, ['escape' => false]);
            } else if (!empty($categoryname)) {
                $cat_name = $categoryname;
            } else if (!empty($c->category)) {
                $course_cat = core_course_category::get($c->category, IGNORE_MISSING);
                if ($course_cat) {
                    $cat_name = format_string($course_cat->name, true, ['escape' => false]);
                }
            }

            $courses_data[] = $this->prepare_course_card_data($c, $competency_filter, $cat_name, $active_fw_param);
        }

        $sort_options = [
            [
                'value' => 'default',
                'label' => get_string('sort_default', 'theme_meko'),
                'url' => $this->build_catalog_url([
                    'categoryid' => $categoryid,
                    'competencyid' => $competency_param,
                    'framework' => $active_fw_param,
                ]),
                'selected' => ($sort === 'default'),
            ],
            [
                'value' => 'fullname',
                'label' => get_string('sort_fullname', 'theme_meko'),
                'url' => $this->build_catalog_url([
                    'categoryid' => $categoryid,
                    'competencyid' => $competency_param,
                    'framework' => $active_fw_param,
                    'sort' => 'fullname',
                ]),
                'selected' => ($sort === 'fullname'),
            ],
            [
                'value' => 'startdate',
                'label' => get_string('sort_startdate', 'theme_meko'),
                'url' => $this->build_catalog_url([
                    'categoryid' => $categoryid,
                    'competencyid' => $competency_param,
                    'framework' => $active_fw_param,
                    'sort' => 'startdate',
                ]),
                'selected' => ($sort === 'startdate'),
            ],
        ];

        // 4. Xử lý dữ liệu Active Filter Chips & Compound Empty State
        $has_active_filters = ($categoryid > 0 || !empty($competency_filter));
        $is_compound_filter = ($categoryid > 0 && !empty($competency_filter));
        $category_filter_data = null;
        if ($categoryid > 0) {
            $category_filter_data = [
                'category_id' => $categoryid,
                'category_name' => $categoryname,
                'clear_url' => $this->build_catalog_url([
                    'competencyid' => $competency_param,
                    'framework' => $active_fw_param,
                    'sort' => $sort,
                ]),
            ];
        }
        $compound_empty_desc = '';
        if ($is_compound_filter) {
            $comp_display_label = !empty($competency_filter['parent_name'])
                ? ($competency_filter['parent_name'] . ' · ' . $competency_filter['level_badge'])
                : $competency_filter['competency_name'];
            $compound_empty_desc = get_string('empty_compound_desc', 'theme_meko', [
                'category' => $categoryname,
                'competency' => $comp_display_label,
            ]);
        }

        // 5. Cấu trúc Cây Năng Lực cho Sidebar Widget & Quick Picker Modal (2 khung: Students + Faculty)
        $competency_sidebar = null;
        $competency_picker = null;

        if (class_exists('\local_meko_competencies\helper')) {
            $lang = \local_meko_competencies\helper::resolve_lang('en');
            $students_formatted = $this->format_competency_tree_for_catalog('students', $competency_param, $categoryid, $sort, $lang, ($active_fw === 'students'));
            $faculty_formatted = $this->format_competency_tree_for_catalog('faculty', $competency_param, $categoryid, $sort, $lang, ($active_fw === 'faculty'));

            $active_formatted = ($active_fw === 'faculty') ? $faculty_formatted : $students_formatted;

            if (!empty($active_formatted['domains'])) {
                $competency_sidebar = [
                    'active_framework' => $active_fw,
                    'is_students' => ($active_fw === 'students'),
                    'is_faculty' => ($active_fw === 'faculty'),
                    'students_tab_url' => $this->build_catalog_url([
                        'categoryid' => $categoryid,
                        'framework' => 'students',
                        'sort' => $sort,
                    ]),
                    'faculty_tab_url' => $this->build_catalog_url([
                        'categoryid' => $categoryid,
                        'framework' => 'faculty',
                        'sort' => $sort,
                    ]),
                    'view_all_framework_url' => (new moodle_url('/local/meko_competencies/index.php', ['framework' => $active_fw]))->out(false),
                    'framework_title' => $active_formatted['framework_title'],
                    'framework_idnumber' => $active_formatted['framework_idnumber'],
                    'competency_count' => $active_formatted['competency_count'],
                    'competency_count_badge' => $active_formatted['competency_count_badge'],
                    'domains' => $active_formatted['domains'],
                ];
            }

            if (!empty($students_formatted['domains']) || !empty($faculty_formatted['domains'])) {
                $competency_picker = [
                    'is_students_active' => ($active_fw === 'students'),
                    'is_faculty_active' => ($active_fw === 'faculty'),
                    'students_count_label' => $students_formatted['competency_count'],
                    'faculty_count_label' => $faculty_formatted['competency_count'],
                    'students_domains' => $students_formatted['domains'],
                    'faculty_domains' => $faculty_formatted['domains'],
                    'clear_competency_url' => $this->build_catalog_url(['categoryid' => $categoryid, 'sort' => $sort]),
                    'has_active_competency' => !empty($competency_param),
                ];
            }
        }

        $context = [
            'categoryid' => $categoryid,
            'categoryname' => $categoryname,
            'categorydesc' => $categorydesc,
            'has_active_filters' => $has_active_filters,
            'is_compound_filter' => $is_compound_filter,
            'compound_empty_desc' => $compound_empty_desc,
            'category_filter' => $category_filter_data,
            'clear_all_url' => $this->build_catalog_url(['sort' => $sort]),
            'categories_tree' => $categories_tree,
            'competency_filter' => $competency_filter,
            'competency_sidebar' => $competency_sidebar,
            'competency_picker' => $competency_picker,
            'courses' => $courses_data,
            'hascourses' => !empty($courses_data),
            'total_courses' => $total_courses,
            'page_courses_count' => count($courses_data),
            'is_paginated' => ($total_courses > $perpage),
            'showing_range_text' => ($total_courses > $perpage)
                ? get_string('showing_page_courses', 'theme_meko', ['from' => $showing_from, 'to' => $showing_to, 'total' => $total_courses])
                : '',
            'sort_options' => $sort_options,
            'pagination' => $pagination_html,
        ];

        $themename = !empty($this->page->theme->name) ? $this->page->theme->name : 'meko';
        $template = 'theme_' . $themename . '/core_course/coursecat';

        try {
            return $this->render_from_template($template, $context);
        } catch (\Throwable $e1) {
            try {
                return $this->render_from_template('theme_meko/core_course/coursecat', $context);
            } catch (\Throwable $e2) {
                try {
                    return $this->render_from_template('theme_accees/core_course/coursecat', $context);
                } catch (\Throwable $e3) {
                    if (method_exists(get_parent_class($this), 'course_category')) {
                        return parent::course_category($category);
                    }
                    return '';
                }
            }
        }
    }

    /**
     * Chuẩn hóa 14 trường thông tin trên Thẻ Khóa học (Course Card)
     * Tuân thủ đặc tả Trang 2-4: DacTaYeuCau-ACCEES-DRLM-DLE-V-13-10-2026.pdf
     *
     * @param \stdClass $c Đối tượng khóa học Moodle
     * @param array|null $competency_filter Bộ lọc năng lực đang active
     * @param string $cat_name Tên danh mục khóa học
     * @param string $framework_filter Khung năng lực đang chọn ('students'|'faculty'|'')
     * @return array
     */
    public function prepare_course_card_data($c, $competency_filter = null, $cat_name = '', $framework_filter = '') {
        require_once(__DIR__ . '/../../lib.php');
        return theme_accees_lms_theme_prepare_course_card($c, $competency_filter, $cat_name, $framework_filter);
    }

    /**
     * Trích xuất thông tin Khung Năng Lực CBE (Nhóm 2)
     * Tra ngược từ Node năng lực lên Tầng 1 (Root Domain) để lấy Domain Pill
     */
    protected function get_course_cbe_metadata($courseid, $competency_filter = null, $shortname = '', $framework_filter = '') {
        require_once(__DIR__ . '/../../lib.php');
        return theme_accees_lms_theme_get_course_cbe_metadata($courseid, $competency_filter, $shortname, $framework_filter);
    }

    /**
     * Build canonical /course/index.php filter URL preserving active state cleanly.
     *
     * @param array $params
     * @return string
     */
    private function build_catalog_url(array $params): string {
        $clean = [];
        if (!empty($params['categoryid']) && (int)$params['categoryid'] > 0) {
            $clean['categoryid'] = (int)$params['categoryid'];
        }
        if (!empty($params['competencyid'])) {
            $clean['competencyid'] = $params['competencyid'];
        }
        if (!empty($params['framework']) && in_array($params['framework'], ['students', 'faculty'], true)) {
            $clean['framework'] = $params['framework'];
        }
        if (!empty($params['sort']) && $params['sort'] !== 'default') {
            $clean['sort'] = $params['sort'];
        }
        if (!empty($params['page']) && (int)$params['page'] > 0) {
            $clean['page'] = (int)$params['page'];
        }
        return (new moodle_url('/course/index.php', $clean))->out(false);
    }

    /**
     * Format 3-tier competency tree data for Course Catalog Sidebar & Quick Picker Modal.
     * Ensures zero internal idnumber values are exposed as visible text labels.
     *
     * @param string $fw_key 'students'|'faculty'
     * @param string $competency_param Active competency idnumber
     * @param int $categoryid Active category id
     * @param string $sort Active sort option
     * @param string $lang Resolved language ('en')
     * @param bool $is_active_fw Whether this framework matches the active catalog tab
     * @return array
     */
    private function format_competency_tree_for_catalog(
        string $fw_key,
        string $competency_param,
        int $categoryid,
        string $sort,
        string $lang,
        bool $is_active_fw = true
    ): array {
        if (!class_exists('\local_meko_competencies\helper')) {
            return [];
        }

        $fw_data = \local_meko_competencies\helper::get_framework_data($fw_key, $lang);
        if (empty($fw_data['domains'])) {
            return [];
        }

        $domains_formatted = [];
        $any_domain_active = false;

        foreach ($fw_data['domains'] as $dom) {
            $is_current_dom = ($is_active_fw && !empty($competency_param) && $dom['idnumber'] === $competency_param);
            $domain_has_active = $is_current_dom;
            if ($is_current_dom) {
                $any_domain_active = true;
            }
            $comps_formatted = [];

            foreach ($dom['competencies'] as $cp) {
                $is_current_comp = ($is_active_fw && !empty($competency_param) && $cp['idnumber'] === $competency_param);
                $comp_has_active = $is_current_comp;
                if ($is_current_comp) {
                    $domain_has_active = true;
                    $any_domain_active = true;
                }
                $levels_formatted = [];

                foreach ($cp['levels'] as $lv) {
                    $is_current_lvl = ($is_active_fw && !empty($competency_param) && $lv['idnumber'] === $competency_param);
                    if ($is_current_lvl) {
                        $comp_has_active = true;
                        $domain_has_active = true;
                        $any_domain_active = true;
                    }
                    $levels_formatted[] = [
                        'idnumber' => $lv['idnumber'],
                        'shortname' => $lv['shortname'],
                        'level_badge' => $lv['level_badge'],
                        'badge_class' => $lv['badge_class'] ?? 'badge-solid-primary',
                        'level_key' => $lv['level_key'] ?? 'default',
                        'courses_count' => (int)($lv['courses_count'] ?? 0),
                        'has_courses' => !empty($lv['has_courses']),
                        'is_current' => $is_current_lvl,
                        'url' => $this->build_catalog_url([
                            'categoryid' => $categoryid,
                            'competencyid' => $lv['idnumber'],
                            'framework' => $fw_key,
                            'sort' => $sort,
                        ]),
                    ];
                }

                $comps_formatted[] = [
                    'idnumber' => $cp['idnumber'],
                    'shortname' => $cp['shortname'],
                    'comp_url' => $this->build_catalog_url([
                        'categoryid' => $categoryid,
                        'competencyid' => $cp['idnumber'],
                        'framework' => $fw_key,
                        'sort' => $sort,
                    ]),
                    'is_current_comp' => $is_current_comp,
                    'is_open' => $comp_has_active,
                    'levels' => $levels_formatted,
                ];
            }

            $domains_formatted[] = [
                'idnumber' => $dom['idnumber'],
                'shortname' => $dom['shortname'],
                'description' => $dom['description'] ?? '',
                'competencies_count' => count($comps_formatted),
                'is_open' => $domain_has_active,
                'competencies' => $comps_formatted,
            ];
        }

        if (!$any_domain_active && !empty($domains_formatted)) {
            $domains_formatted[0]['is_open'] = true;
        }

        $comp_count = (int)($fw_data['competencies_count'] ?? 0);
        return [
            'framework_key' => $fw_key,
            'framework_title' => $fw_data['framework_title'] ?? '',
            'framework_idnumber' => $fw_data['framework_idnumber'] ?? '',
            'competency_count' => $comp_count,
            'competency_count_badge' => get_string('competences_count_badge', 'theme_meko', $comp_count),
            'domains' => $domains_formatted,
        ];
    }

    /**
     * Trích xuất thông tin Trường tùy chỉnh Moodle Custom Fields API (Nhóm 3)
     */
    protected function get_course_custom_fields($courseid) {
        require_once(__DIR__ . '/../../lib.php');
        return theme_accees_lms_theme_get_course_custom_fields($courseid);
    }
}
}

if (class_exists('\theme_meko\output\core_course_renderer') && !class_exists('\theme_accees\output\core_course_renderer')) {
    class_alias('\theme_meko\output\core_course_renderer', '\theme_accees\output\core_course_renderer');
}

if (class_exists('\theme_meko\output\core_course_renderer') && !class_exists('\theme_accees_lms_theme\output\core_course_renderer')) {
    class_alias('\theme_meko\output\core_course_renderer', '\theme_accees_lms_theme\output\core_course_renderer');
}

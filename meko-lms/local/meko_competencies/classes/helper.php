<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// @package    local_meko_competencies
// @copyright  2026 Dự án ACCEES (Erasmus+ CBHE-101179860)
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

namespace local_meko_competencies;

defined('MOODLE_INTERNAL') || die();

use moodle_url;

class helper {

    /**
     * Resolve language strictly to English ('en').
     * Optional parameter is preserved for signature compatibility.
     *
     * @param string|null $lang
     * @return string 'en'
     */
    public static function resolve_lang(?string $lang = null): string {
        return 'en';
    }

    /**
     * Resolve normalized CSS/filter key for a proficiency level idnumber.
     *
     * @param string $idnum
     * @return string 'n1'|'n2'|'n3'|'n4'|'basic'|'intermediate'|'advanced'|'default'
     */
    public static function get_level_key(string $idnum): string {
        if (preg_match('/_N([1-4])$/i', $idnum, $m)) {
            return 'n' . $m[1];
        } else if (stripos($idnum, 'BASIC') !== false) {
            return 'basic';
        } else if (stripos($idnum, 'INTERMEDIATE') !== false) {
            return 'intermediate';
        } else if (stripos($idnum, 'ADVANCED') !== false) {
            return 'advanced';
        }
        return 'default';
    }

    /**
     * Get standardized English proficiency level badge label and CSS class.
     *
     * @param string $idnum
     * @param string $lang Preserved for signature compatibility (always English)
     * @return array [$lvl_badge, $badge_class]
     */
    public static function get_level_badge(string $idnum, string $lang = 'en'): array {
        $badge_class = 'badge-solid-primary';

        if (preg_match('/_N([1-4])$/', $idnum, $m)) {
            $lvl_badge = 'Level N' . $m[1];
            if ($m[1] == '1') {
                $badge_class = 'badge-solid-primary';
            }
            if ($m[1] == '2') {
                $badge_class = 'badge-solid-success';
            }
            if ($m[1] == '3') {
                $badge_class = 'badge-solid-warning';
            }
            if ($m[1] == '4') {
                $badge_class = 'badge-solid-slate';
            }
        } else if (strpos($idnum, 'BASIC') !== false) {
            $lvl_badge = 'Level Basic';
            $badge_class = 'badge-solid-primary';
        } else if (strpos($idnum, 'INTERMEDIATE') !== false) {
            $lvl_badge = 'Level Intermediate';
            $badge_class = 'badge-solid-success';
        } else if (strpos($idnum, 'ADVANCED') !== false) {
            $lvl_badge = 'Level Advanced';
            $badge_class = 'badge-solid-warning';
        } else {
            $lvl_badge = 'Target Level';
            $badge_class = 'badge-solid-primary';
        }

        return [$lvl_badge, $badge_class];
    }

    /**
     * Normalize framework parameter to canonical Moodle DB idnumber:
     * DIGCOMP_ACCEES_STUDENTS or DIGCOMP_ACCEES_FACULTY.
     *
     * @param string $param 'DIGCOMP_ACCEES_STUDENTS' | 'DIGCOMP_ACCEES_FACULTY' | 'students' | 'faculty'
     * @return array
     */
    public static function resolve_framework_idnumber(string $param): array {
        $p = strtoupper(trim($param));
        if ($p === 'FACULTY' || $p === 'DIGCOMP_ACCEES_FACULTY' || strpos($p, 'FACULTY') !== false) {
            return [
                'idnumber' => 'DIGCOMP_ACCEES_FACULTY',
                'key' => 'faculty',
                'is_faculty' => true,
                'is_students' => false,
            ];
        }
        return [
            'idnumber' => 'DIGCOMP_ACCEES_STUDENTS',
            'key' => 'students',
            'is_faculty' => false,
            'is_students' => true,
        ];
    }

    /**
     * Format an optional competency description string from Moodle DB.
     * Returns empty string when DB value is blank so empty purpose blocks are omitted cleanly.
     *
     * @param string|null $text
     * @param int|null $format
     * @return string
     */
    protected static function format_db_description(?string $text, ?int $format = null): string {
        if ($text === null || trim($text) === '') {
            return '';
        }
        return format_text($text, $format ?? FORMAT_HTML, ['filter' => false]);
    }

    /**
     * Get framework summary metrics strictly from Moodle DB.
     *
     * @param string $framework_param
     * @param string $lang
     * @return array
     */
    public static function get_framework_summary(string $framework_param, string $lang = 'en'): array {
        $lang = self::resolve_lang($lang);
        $res = self::resolve_framework_idnumber($framework_param);
        $data = self::get_framework_data($res['idnumber'], $lang);

        $count = (int)($data['competencies_count'] ?? 0);
        $unit = 'Competencies';

        $title = !empty($data['framework_title']) ? $data['framework_title'] : '';
        if (empty($title)) {
            $title = get_string($res['is_faculty'] ? 'faculty_framework' : 'students_framework', 'local_meko_competencies');
        }

        $default_name = $res['is_faculty'] ? 'Faculty Framework' : 'Students Framework';

        $tab_name = $default_name;
        if (!empty($title) && mb_strlen($title) <= 40) {
            $tab_name = $title;
        }

        return [
            'idnumber' => $res['idnumber'],
            'key' => $res['key'],
            'title' => $title,
            'shortname' => $tab_name,
            'competencies_count' => $count,
            'tab_label' => "{$tab_name} ({$count} {$unit})",
        ];
    }

    /**
     * Get 3-tier competency framework hierarchy (Domain > Competency > Level) exclusively from Moodle DB.
     *
     * @param string $framework_param 'DIGCOMP_ACCEES_STUDENTS' | 'DIGCOMP_ACCEES_FACULTY' | 'students' | 'faculty'
     * @param string $lang
     * @return array
     */
    public static function get_framework_data(string $framework_param = 'DIGCOMP_ACCEES_STUDENTS', string $lang = 'en'): array {
        $lang = self::resolve_lang($lang);
        $res = self::resolve_framework_idnumber($framework_param);
        $framework_idnumber = $res['idnumber'];
        $framework_key = $res['key'];
        $is_faculty = $res['is_faculty'];

        $db_data = self::load_from_database($framework_idnumber, $lang);
        if (!empty($db_data)) {
            $db_data['framework_key'] = $framework_key;
            $db_data['is_faculty'] = $is_faculty;
            $db_data['is_students'] = !$is_faculty;
            return $db_data;
        }

        $fallback_title = get_string($is_faculty ? 'faculty_framework' : 'students_framework', 'local_meko_competencies');
        return [
            'framework_key' => $framework_key,
            'is_faculty' => $is_faculty,
            'is_students' => !$is_faculty,
            'framework_idnumber' => $framework_idnumber,
            'framework_title' => $fallback_title,
            'framework_desc' => '',
            'domains' => [],
            'level_filters' => self::get_framework_columns($is_faculty, $lang),
            'domains_count' => 0,
            'competencies_count' => 0,
            'levels_count' => 0,
            'mapped_courses_count' => 0,
        ];
    }

    /**
     * Load competency framework hierarchy strictly from Moodle DB core_competency tables.
     *
     * @param string $framework_idnumber
     * @param string $lang
     * @return array|null
     */
    protected static function load_from_database(string $framework_idnumber, string $lang = 'en'): ?array {
        global $DB;

        if (!$DB) {
            return null;
        }

        $lang = self::resolve_lang($lang);
        $res = self::resolve_framework_idnumber($framework_idnumber);
        $is_fac = $res['is_faculty'];
        $siteid = defined('SITEID') ? (int)SITEID : 1;

        try {
            $framework = $DB->get_record('competency_framework', ['idnumber' => $framework_idnumber]);
            if (!$framework) {
                $alt_idnumber = (strpos($framework_idnumber, '_V1') !== false)
                    ? str_replace('_V1', '', $framework_idnumber)
                    : $framework_idnumber . '_V1';
                $framework = $DB->get_record('competency_framework', ['idnumber' => $alt_idnumber]);
            }
            if (!$framework) {
                return null;
            }

            $frameworkid = (int)$framework->id;
            $all_comps = $DB->get_records('competency', ['competencyframeworkid' => $frameworkid], 'sortorder ASC, id ASC');
            if (empty($all_comps)) {
                return null;
            }

            // Tier 1 (parentid = 0), Tier 2 (Domain children), Tier 3 (Competency children).
            $domains_map = [];
            $competencies_map = [];
            $levels_map = [];

            foreach ($all_comps as $c) {
                if (empty((int)$c->parentid)) {
                    $domains_map[$c->id] = $c;
                }
            }

            foreach ($all_comps as $c) {
                $pid = (int)$c->parentid;
                if (!empty($pid) && isset($domains_map[$pid])) {
                    $competencies_map[$c->id] = $c;
                }
            }

            foreach ($all_comps as $c) {
                $pid = (int)$c->parentid;
                if (!empty($pid) && isset($competencies_map[$pid])) {
                    $levels_map[$c->id] = $c;
                }
            }

            // Query distinct visible course counts per competency in a single DB query.
            $comp_counts_sql = "SELECT cc.competencyid, COUNT(DISTINCT cc.courseid) AS count
                                  FROM {competency_coursecomp} cc
                                  JOIN {competency} comp ON comp.id = cc.competencyid
                                  JOIN {course} c ON c.id = cc.courseid
                                 WHERE comp.competencyframeworkid = :frameworkid
                                   AND c.visible = 1
                                   AND c.id <> :siteid
                              GROUP BY cc.competencyid";
            $comp_course_counts = $DB->get_records_sql_menu($comp_counts_sql, [
                'frameworkid' => $frameworkid,
                'siteid' => $siteid,
            ]) ?: [];

            // Query total distinct visible courses linked anywhere inside this framework.
            $total_unique_sql = "SELECT COUNT(DISTINCT cc.courseid)
                                   FROM {competency_coursecomp} cc
                                   JOIN {competency} comp ON comp.id = cc.competencyid
                                   JOIN {course} c ON c.id = cc.courseid
                                  WHERE comp.competencyframeworkid = :frameworkid
                                    AND c.visible = 1
                                    AND c.id <> :siteid";
            $total_mapped_courses = (int)$DB->count_records_sql($total_unique_sql, [
                'frameworkid' => $frameworkid,
                'siteid' => $siteid,
            ]);

            // Build 3-tier hierarchy exclusively from DB columns.
            $domains_tree = [];
            $total_levels = 0;
            $dom_index = 0;

            foreach ($domains_map as $d_id => $dom) {
                $is_first_dom = ($dom_index === 0);
                $comp_list = [];
                $dom_total_courses = 0;

                $dom_shortname = format_string($dom->shortname, true, ['escape' => false]);
                $dom_desc = self::format_db_description($dom->description ?? '', $dom->descriptionformat ?? FORMAT_HTML);

                foreach ($competencies_map as $c_id => $comp) {
                    if ((int)$comp->parentid == (int)$d_id) {
                        $lvl_list = [];
                        $comp_total_courses = 0;

                        $comp_shortname = format_string($comp->shortname, true, ['escape' => false]);
                        $comp_desc = self::format_db_description($comp->description ?? '', $comp->descriptionformat ?? FORMAT_HTML);

                        foreach ($levels_map as $l_id => $lvl) {
                            if ((int)$lvl->parentid == (int)$c_id) {
                                $total_levels++;
                                $courses_count = (int)($comp_course_counts[$l_id] ?? 0);
                                $comp_total_courses += $courses_count;

                                $idnum = (string)$lvl->idnumber;
                                [$lvl_badge, $badge_class] = self::get_level_badge($idnum, $lang);

                                $lvl_shortname = format_string($lvl->shortname, true, ['escape' => false]);
                                $lvl_desc = self::format_db_description($lvl->description ?? '', $lvl->descriptionformat ?? FORMAT_HTML);

                                $lvl_list[] = [
                                    'id' => (int)$l_id,
                                    'idnumber' => $idnum,
                                    'shortname' => $lvl_shortname,
                                    'description' => $lvl_desc,
                                    'level_badge' => $lvl_badge,
                                    'badge_class' => $badge_class,
                                    'level_key' => self::get_level_key($idnum),
                                    'courses_count' => $courses_count,
                                    'courses_url' => (new moodle_url('/course/index.php', ['competencyid' => $idnum]))->out(false),
                                    'has_courses' => ($courses_count > 0),
                                ];
                            }
                        }

                        $dom_total_courses += $comp_total_courses;
                        $comp_list[] = [
                            'id' => (int)$c_id,
                            'idnumber' => (string)$comp->idnumber,
                            'shortname' => $comp_shortname,
                            'description' => $comp_desc,
                            'levels' => $lvl_list,
                            'levels_count' => count($lvl_list),
                            'courses_count' => $comp_total_courses,
                            'has_courses' => ($comp_total_courses > 0),
                            'is_default_open' => $is_first_dom,
                        ];
                    }
                }

                if (!$comp_list) {
                    continue;
                }
                $dom_index++;
                $domains_tree[] = [
                    'id' => (int)$d_id,
                    'idnumber' => (string)$dom->idnumber,
                    'shortname' => $dom_shortname,
                    'description' => $dom_desc,
                    'competencies' => $comp_list,
                    'competencies_count' => count($comp_list),
                    'courses_count' => $dom_total_courses,
                    'has_courses' => ($dom_total_courses > 0),
                    'is_default_open' => true,
                ];
            }

            $f_title = !empty($framework->shortname)
                ? format_string($framework->shortname, true, ['escape' => false])
                : get_string($is_fac ? 'faculty_framework' : 'students_framework', 'local_meko_competencies');

            $f_desc = self::format_db_description($framework->description ?? '', $framework->descriptionformat ?? FORMAT_HTML);

            return [
                'framework_idnumber' => $framework->idnumber ?: $framework_idnumber,
                'framework_title' => $f_title,
                'framework_desc' => $f_desc,
                'domains' => $domains_tree,
                'level_filters' => self::get_framework_columns($is_fac, $lang),
                'domains_count' => count($domains_tree),
                'competencies_count' => count($competencies_map),
                'levels_count' => $total_levels,
                'mapped_courses_count' => $total_mapped_courses,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Define proficiency level columns and filter options in English.
     *
     * @param bool $is_faculty
     * @param string $lang Preserved for signature compatibility (always English)
     * @return array
     */
    public static function get_framework_columns(bool $is_faculty, string $lang = 'en'): array {
        if ($is_faculty) {
            return [
                ['key' => 'BASIC', 'level_key' => 'basic', 'label' => 'Level Basic', 'badge_class' => 'badge-solid-primary', 'col_width' => '25%'],
                ['key' => 'INTERMEDIATE', 'level_key' => 'intermediate', 'label' => 'Level Intermediate', 'badge_class' => 'badge-solid-success', 'col_width' => '25%'],
                ['key' => 'ADVANCED', 'level_key' => 'advanced', 'label' => 'Level Advanced', 'badge_class' => 'badge-solid-warning', 'col_width' => '25%'],
            ];
        } else {
            return [
                ['key' => 'N1', 'level_key' => 'n1', 'label' => 'Level N1: Basic', 'badge_class' => 'badge-solid-primary', 'col_width' => '19%'],
                ['key' => 'N2', 'level_key' => 'n2', 'label' => 'Level N2: Intermediate', 'badge_class' => 'badge-solid-success', 'col_width' => '19%'],
                ['key' => 'N3', 'level_key' => 'n3', 'label' => 'Level N3: Advanced', 'badge_class' => 'badge-solid-warning', 'col_width' => '19%'],
                ['key' => 'N4', 'level_key' => 'n4', 'label' => 'Level N4: In-depth', 'badge_class' => 'badge-solid-slate', 'col_width' => '19%'],
            ];
        }
    }

    /**
     * Get horizontal Rubric Matrix comparison table data for a framework (exclusively from DB).
     *
     * @param string $framework_param
     * @param string $lang
     * @return array
     */
    public static function get_rubric_matrix_data(string $framework_param = 'DIGCOMP_ACCEES_STUDENTS', string $lang = 'en'): array {
        $lang = self::resolve_lang($lang);
        $res = self::resolve_framework_idnumber($framework_param);
        $framework_idnumber = $res['idnumber'];
        $framework_key = $res['key'];
        $is_faculty = $res['is_faculty'];

        $data = self::get_framework_data($framework_idnumber, $lang);

        $columns = self::get_framework_columns($is_faculty, $lang);
        $competency_col_width = $is_faculty ? '25%' : '24%';

        $matrix_rows = [];
        foreach ($data['domains'] as $dom) {
            foreach ($dom['competencies'] as $comp) {
                $cells = [];
                foreach ($columns as $col) {
                    $matched_lvl = null;
                    $suffix = '_' . $col['key'];
                    foreach ($comp['levels'] as $lvl) {
                        if (substr($lvl['idnumber'], -strlen($suffix)) === $suffix) {
                            $matched_lvl = $lvl;
                            break;
                        }
                    }

                    if ($matched_lvl) {
                        $courses_count = (int)($matched_lvl['courses_count'] ?? 0);
                        $cells[] = [
                            'has_level' => true,
                            'col_key' => $col['key'],
                            'level_key' => $matched_lvl['level_key'] ?? strtolower($col['key']),
                            'idnumber' => $matched_lvl['idnumber'],
                            'badge' => $matched_lvl['level_badge'],
                            'badge_class' => $matched_lvl['badge_class'],
                            'description' => $matched_lvl['description'],
                            'courses_count' => $courses_count,
                            'courses_url' => $matched_lvl['courses_url'],
                            'has_courses' => ($courses_count > 0),
                        ];
                    } else {
                        $cells[] = [
                            'has_level' => false,
                            'col_key' => $col['key'],
                            'level_key' => strtolower($col['key']),
                        ];
                    }
                }

                $matrix_rows[] = [
                    'domain_name' => $dom['shortname'],
                    'domain_code' => $dom['idnumber'],
                    'competency_code' => $comp['idnumber'],
                    'competency_name' => $comp['shortname'],
                    'cells' => $cells,
                ];
            }
        }

        $matrix_title = !empty($data['framework_title']) ? $data['framework_title'] : '';
        if (empty($matrix_title)) {
            $matrix_title = get_string($is_faculty ? 'faculty_framework' : 'students_framework', 'local_meko_competencies');
        }

        return [
            'framework_idnumber' => $framework_idnumber,
            'framework_key' => $framework_key,
            'framework_title' => $matrix_title,
            'framework_desc' => $data['framework_desc'] ?? '',
            'matrix_subtitle' => get_string('matrix_subtitle', 'local_meko_competencies'),
            'is_faculty' => $is_faculty,
            'is_students' => !$is_faculty,
            'competency_col_width' => $competency_col_width,
            'default_level_col_width' => !empty($columns[0]['col_width']) ? $columns[0]['col_width'] : ($is_faculty ? '25%' : '19%'),
            'columns' => $columns,
            'level_filters' => $columns,
            'domains' => $data['domains'] ?? [],
            'matrix_rows' => $matrix_rows,
            'domains_count' => $data['domains_count'],
            'competencies_count' => $data['competencies_count'],
            'levels_count' => $data['levels_count'],
            'mapped_courses_count' => $data['mapped_courses_count'] ?? 0,
        ];
    }

    /**
     * Find details of a Level, Competency, or Domain exclusively from Moodle DB ({competency} & {competency_framework}).
     * Resolves 3-tier ancestry (Domain > Competency > Target Level) and sibling levels.
     *
     * @param string|int $competency_param
     * @param string $lang
     * @param string $framework_param Optional 'students'|'faculty'|framework idnumber to disambiguate duplicate codes
     * @return array|null
     */
    public static function find_competency_details($competency_param, string $lang = 'en', string $framework_param = ''): ?array {
        if (empty($competency_param)) {
            return null;
        }

        global $DB;
        if (!$DB) {
            return null;
        }

        $lang = self::resolve_lang($lang);
        $param_str = trim((string)$competency_param);

        try {
            $comp = null;
            if (is_numeric($param_str)) {
                $comp = $DB->get_record('competency', ['id' => (int)$param_str]);
            } else {
                if (trim($framework_param) !== '') {
                    $fw_res = self::resolve_framework_idnumber($framework_param);
                    $fw_idnum = $fw_res['idnumber'];
                    $fw_alt = (strpos($fw_idnum, '_V1') !== false) ? str_replace('_V1', '', $fw_idnum) : ($fw_idnum . '_V1');
                    $scoped = $DB->get_records_sql(
                        "SELECT comp.*
                           FROM {competency} comp
                           JOIN {competency_framework} fw ON fw.id = comp.competencyframeworkid
                          WHERE comp.idnumber = :idnum
                            AND (fw.idnumber = :fw1 OR fw.idnumber = :fw2)
                       ORDER BY comp.id ASC",
                        ['idnum' => $param_str, 'fw1' => $fw_idnum, 'fw2' => $fw_alt],
                        0,
                        1
                    );
                    if (!empty($scoped)) {
                        $comp = reset($scoped);
                    }
                }
                if (!$comp) {
                    // Bounded query avoids Moodle debug warnings if domain idnumber exists across both frameworks.
                    $matches = $DB->get_records('competency', ['idnumber' => $param_str], 'competencyframeworkid ASC, id ASC', '*', 0, 1);
                    if (!empty($matches)) {
                        $comp = reset($matches);
                    }
                }
            }

            if (!$comp) {
                return null;
            }

            $compid = (int)$comp->id;
            $framework = $DB->get_record('competency_framework', ['id' => $comp->competencyframeworkid]);
            $fw_idnum = $framework ? (string)$framework->idnumber : '';
            $fw_key = (strpos($fw_idnum, 'FACULTY') !== false || strpos((string)$comp->idnumber, 'GV_') === 0) ? 'faculty' : 'students';
            $fw_name = ($framework && !empty($framework->shortname))
                ? format_string($framework->shortname, true, ['escape' => false])
                : get_string($fw_key === 'faculty' ? 'faculty_framework' : 'students_framework', 'local_meko_competencies');

            $parent_name = '';
            $parent_code = '';
            $domain_name = '';
            $domain_code = '';
            $sibling_levels = [];
            $tier = 1;

            $parentid = (int)$comp->parentid;
            if (!empty($parentid)) {
                $parent_comp = $DB->get_record('competency', ['id' => $parentid]);
                if ($parent_comp) {
                    $grandparentid = (int)$parent_comp->parentid;
                    if (!empty($grandparentid)) {
                        // Tier 3 (Target Level): parent is Competency, grandparent is Domain.
                        $tier = 3;
                        $parent_code = (string)$parent_comp->idnumber;
                        $parent_name = format_string($parent_comp->shortname, true, ['escape' => false]);

                        $grandparent = $DB->get_record('competency', ['id' => $grandparentid]);
                        if ($grandparent) {
                            $domain_code = (string)$grandparent->idnumber;
                            $domain_name = format_string($grandparent->shortname, true, ['escape' => false]);
                        }

                        $siblings = $DB->get_records('competency', ['parentid' => $parentid], 'sortorder ASC, id ASC');
                        foreach ($siblings as $sib) {
                            $sib_idnum = (string)$sib->idnumber;
                            [$sib_badge, $sib_class] = self::get_level_badge($sib_idnum, $lang);
                            $sibling_levels[] = [
                                'id' => (int)$sib->id,
                                'idnumber' => $sib_idnum,
                                'shortname' => format_string($sib->shortname, true, ['escape' => false]),
                                'level_badge' => $sib_badge,
                                'badge_class' => $sib_class,
                                'level_key' => self::get_level_key($sib_idnum),
                                'is_current' => ((int)$sib->id === $compid || $sib_idnum === $param_str),
                            ];
                        }
                    } else {
                        // Tier 2 (Competency): parent is Domain, children are Target Levels.
                        $tier = 2;
                        $domain_code = (string)$parent_comp->idnumber;
                        $domain_name = format_string($parent_comp->shortname, true, ['escape' => false]);
                        $parent_code = (string)$comp->idnumber;
                        $parent_name = format_string($comp->shortname, true, ['escape' => false]);

                        $children = $DB->get_records('competency', ['parentid' => $compid], 'sortorder ASC, id ASC');
                        foreach ($children as $child) {
                            $child_idnum = (string)$child->idnumber;
                            [$c_badge, $c_class] = self::get_level_badge($child_idnum, $lang);
                            $sibling_levels[] = [
                                'id' => (int)$child->id,
                                'idnumber' => $child_idnum,
                                'shortname' => format_string($child->shortname, true, ['escape' => false]),
                                'level_badge' => $c_badge,
                                'badge_class' => $c_class,
                                'level_key' => self::get_level_key($child_idnum),
                                'is_current' => false,
                            ];
                        }
                    }
                }
            } else {
                // Tier 1 (Domain)
                $tier = 1;
                $domain_code = (string)$comp->idnumber;
                $domain_name = format_string($comp->shortname, true, ['escape' => false]);
            }

            if ($tier === 3) {
                [$level_badge, $badge_class] = self::get_level_badge((string)$comp->idnumber, $lang);
                $level_key = self::get_level_key((string)$comp->idnumber);
            } else {
                $level_badge = 'All Levels';
                $badge_class = 'badge-solid-primary';
                $level_key = 'default';
            }

            $comp_name = format_string($comp->shortname, true, ['escape' => false]);
            $comp_desc = self::format_db_description($comp->description ?? '', $comp->descriptionformat ?? FORMAT_HTML);

            return [
                'competency_id' => $compid,
                'tier' => $tier,
                'is_level_filter' => ($tier === 3),
                'competency_code' => (string)$comp->idnumber,
                'competency_name' => $comp_name,
                'competency_desc' => $comp_desc,
                'framework_key' => $fw_key,
                'framework_name' => $fw_name,
                'framework_idnumber' => $fw_idnum ?: ($fw_key === 'faculty' ? 'DIGCOMP_ACCEES_FACULTY' : 'DIGCOMP_ACCEES_STUDENTS'),
                'domain_code' => $domain_code,
                'domain_name' => $domain_name,
                'parent_code' => $parent_code,
                'parent_name' => $parent_name,
                'level_badge' => $level_badge,
                'badge_class' => $badge_class,
                'level_key' => $level_key,
                'sibling_levels' => $sibling_levels,
                'has_siblings' => !empty($sibling_levels),
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }
}

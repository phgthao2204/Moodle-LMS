<?php

/**
 * Return Moodle's profile menu without the language submenu.
 *
 * @param \core\output\renderer_base $output Moodle renderer.
 * @return array Profile menu template data.
 */
function theme_meko_profile_menu($output): array {
    global $PAGE;

    $menu = (new \core\navigation\output\primary($PAGE))->get_user_menu($output);
    $languageids = [];
    foreach ($menu['submenus'] ?? [] as $submenu) {
        if ($submenu->title === get_string('languageselector')) {
            $languageids[] = $submenu->id;
        }
    }
    if ($languageids) {
        $menu['items'] = array_values(array_filter($menu['items'], static function ($item) use ($languageids) {
            return !in_array($item->submenuid ?? null, $languageids, true);
        }));
        $menu['submenus'] = array_values(array_filter($menu['submenus'], static function ($submenu) use ($languageids) {
            return !in_array($submenu->id, $languageids, true);
        }));
    }
    return $menu;
}
// This file is part of Moodle - http://moodle.org/
//
// MEKOSOFT LMS Theme Library Callbacks

defined('MOODLE_INTERNAL') || die();

/**
 * Hook to inject Google Font (Be Vietnam Pro) into head
 */
function theme_meko_before_http_headers() {
    global $PAGE;
    $PAGE->requires->css(new moodle_url('https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap'));
}

/**
 * Return Moodle's standard head with the blocking compatibility bundle marked
 * as a high-priority fetch. Execution order is deliberately left unchanged.
 *
 * @return string
 */
function theme_meko_optimised_head_html(): string {
    global $OUTPUT;

    $html = $OUTPUT->standard_head_html();
    return preg_replace(
        '~<script(?![^>]*fetchpriority)(?=[^>]*polyfills/polyfill\.js)([^>]*)>~i',
        '<script fetchpriority="high"$1>',
        $html
    );
}

/**
 * Preload Moodle's compatibility bundle using the active JavaScript revision.
 * This starts the request in the head while Moodle keeps executing the script
 * in its original, core-controlled position at the end of the document.
 *
 * @return string
 */
function theme_meko_polyfill_preload(): string {
    global $CFG;

    if (empty($CFG->jsrev)) {
        return '';
    }
    $url = new moodle_url('/lib/javascript.php/' . $CFG->jsrev . '/lib/polyfills/polyfill.js');
    return html_writer::empty_tag('link', [
        'rel' => 'preload',
        'as' => 'script',
        'href' => $url->out(false),
        'fetchpriority' => 'high',
    ]);
}
/**
 * Returns brand variables which must be defined before Boost is compiled.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_meko_get_pre_scss($theme): string {
    global $CFG;

    $scss = '';
    $presetfile = $CFG->dirroot . '/theme/meko/scss/preset/default.scss';
    if (file_exists($presetfile)) {
        $scss .= file_get_contents($presetfile);
    }

    // Pre SCSS from theme settings
    if (!empty($theme->settings->scsspre)) {
        $scss .= "\n" . $theme->settings->scsspre;
    }

    return $scss;
}

/**
 * Returns theme overrides appended after Boost SCSS.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_meko_get_extra_scss($theme): string {
    $scss = theme_boost_get_extra_scss($theme);

    // Global theme overrides. The file is supplied as a string to Moodle's
    // SCSS compiler, so resolve the local partials here instead of relying on
    // Sass to infer an import path for an in-memory stylesheet.
    $files = [
        __DIR__ . '/scss/_variables.scss',
        __DIR__ . '/scss/_base.scss',
        __DIR__ . '/scss/_components.scss',
        __DIR__ . '/scss/core/_navbar.scss',
        __DIR__ . '/scss/core/_footer.scss',
        __DIR__ . '/scss/post.scss',
        __DIR__ . '/scss/components/_course_card.scss',
        __DIR__ . '/scss/pages/_frontpage.scss',
        __DIR__ . '/scss/pages/_catalog.scss',
        __DIR__ . '/scss/pages/_competencies.scss',
        __DIR__ . '/scss/pages/_competencies_tree_explorer.scss',
        __DIR__ . '/scss/pages/_course_detail.scss',
        __DIR__ . '/scss/pages/_mycourses.scss',
        __DIR__ . '/scss/pages/_enrol.scss',
        __DIR__ . '/scss/pages/_login.scss',
        __DIR__ . '/scss/pages/_course_admin.scss',
        __DIR__ . '/scss/pages/_quiz.scss',
    ];
    foreach ($files as $file) {
        if (is_readable($file)) {
            $content = file_get_contents($file);
            // Strip relative @import directives if any to avoid compiler errors on in-memory string
            $content = preg_replace('/^\s*@import\s+["\'][^"\']+["\'];\s*$/m', '', $content);
            $scss .= "\n" . $content;
        }
    }
    if (!empty($theme->settings->scss)) {
        $scss .= "\n" . $theme->settings->scss;
    }
    return $scss;
}

/**
 * Compiles full main SCSS for theme_meko (Boost core + MEKO presets + partials)
 *
 * @param theme_config $theme
 * @return string
 */
function theme_meko_get_main_scss_content($theme): string {
    $boostconfig = theme_config::load('boost');
    return theme_meko_get_pre_scss($theme)
        . "\n" . theme_boost_get_main_scss_content($boostconfig)
        . "\n" . theme_meko_get_extra_scss($theme);
}

/**
 * Shared helper to build standard navbar context for MEKO theme templates.
 *
 * @param string $activepage Identifier of active page ('home', 'catalog', 'mycourses', 'competencies')
 * @return array
 */
function theme_meko_header_logo($theme, $output): moodle_url {
    $logo = $theme->setting_file_url('logo', 'logo') ?: $output->get_logo_url();
    $brand = theme_accees_lms_theme_get_brand_profile($theme);
    return new moodle_url($logo ?: $brand['logo_url']);
}

/**
 * Resolve the configured TMS or SSO portal URL for Meko.
 */
function theme_meko_service_url($theme, string $service): string {
    global $CFG;
    $url = trim($theme->settings->{$service . '_url'} ?? '');
    if ($url === '' || strpos($url, 'mekosoft.local') !== false || strpos($url, 'accees.vn') !== false) {
        $portalBase = preg_replace('~/lms/?$~', '', rtrim((string)($CFG->wwwroot ?? 'http://meko.localhost/lms'), '/'));
        return $service === 'tms' ? ($portalBase . '/tms') : ($portalBase . '/');
    }
    return $url;
}

/**
 * Build the navbar context for the Meko layout.
 */
function theme_meko_get_navbar_context(string $activepage = ''): array {
    global $PAGE, $OUTPUT, $USER;

    if (empty($activepage)) {
        $currpath = '';
        try {
            if (!empty($PAGE) && method_exists($PAGE, 'has_set_url') && $PAGE->has_set_url()) {
                $currpath = $PAGE->url->get_path();
            }
        } catch (\Throwable $e) {}

        $scriptname = $_SERVER['SCRIPT_NAME'] ?? '';
        $requesturi = $_SERVER['REQUEST_URI'] ?? '';
        $fulluri = (!empty($currpath)) ? $currpath : (!empty($scriptname) ? $scriptname : $requesturi);
        $pagelayout = '';
        try {
            if (!empty($PAGE)) {
                $pagelayout = (string)$PAGE->pagelayout;
            }
        } catch (\Throwable $e) {}

        if ($pagelayout === 'mycourses' || $pagelayout === 'mydashboard' || strpos($fulluri, '/my') !== false || strpos($requesturi, '/my') !== false) {
            $activepage = 'mycourses';
        } else if ($pagelayout === 'coursecategory' || $pagelayout === 'course' || $pagelayout === 'incourse' || strpos($fulluri, '/course/') !== false || strpos($requesturi, '/course/') !== false) {
            $activepage = 'catalog';
        } else if (strpos($fulluri, 'competencies') !== false || strpos($requesturi, 'competencies') !== false) {
            $activepage = 'competencies';
        } else {
            $activepage = 'home';
        }
    }

    $isloggedin = isloggedin() && !isguestuser();
    $currentlang = current_language();
    $languageurl = static function(string $lang) use ($PAGE): string {
        $url = new moodle_url($PAGE->url);
        $url->param('lang', $lang);
        return $url->out(false);
    };

    $userinitials = '';
    if ($isloggedin) {
        $userinitials = core_text::strtoupper(
            core_text::substr($USER->firstname, 0, 1) . core_text::substr($USER->lastname, 0, 1)
        );
    }

    $tmsurl = theme_meko_service_url($PAGE->theme, 'tms');
    $homeurl = (new moodle_url('/', ['redirect' => 0]))->out();
    $catalogurl = (new moodle_url('/course/index.php'))->out();
    $mycoursesurl = (new moodle_url('/my/courses.php'))->out();
    $competenciesurl = (new moodle_url('/theme/meko/competencies.php'))->out();

    $mainmenu = [
        [
            'label' => get_string('nav_home', 'theme_meko'),
            'url' => $homeurl,
            'active' => $activepage === 'home',
        ],
    ];
    if ($isloggedin) {
        $mainmenu[] = [
            'label' => get_string('nav_mycourses', 'theme_meko'),
            'url' => $mycoursesurl,
            'active' => $activepage === 'mycourses',
        ];
    }
    $mainmenu[] = [
        'label' => get_string('nav_catalog', 'theme_meko'),
        'url' => $catalogurl,
        'active' => $activepage === 'catalog',
    ];
    $mainmenu[] = [
        'label' => get_string('nav_competencies', 'theme_meko'),
        'url' => $competenciesurl,
        'active' => $activepage === 'competencies',
    ];
    if ($isloggedin && is_siteadmin($USER)) {
        $mainmenu[] = [
            'label' => get_string('nav_admin', 'theme_meko'),
            'url' => (new moodle_url('/admin/search.php'))->out(),
            'active' => false,
        ];
    }

    return [
        'logourl' => theme_meko_header_logo($PAGE->theme, $OUTPUT),
        'logowhiteurl' => $OUTPUT->image_url('logo_white', 'theme_meko'),
        'isloggedin' => $isloggedin,
        'userfullname' => fullname($USER),
        'userinitials' => $userinitials,
        'userprofileurl' => (new moodle_url('/user/profile.php', ['id' => $USER->id]))->out(),
        'loginurl' => (new moodle_url('/login/index.php'))->out(),
        'logouturl' => (new moodle_url('/login/logout.php', ['sesskey' => sesskey()]))->out(),
        'homeurl' => $homeurl,
        'catalogurl' => $catalogurl,
        'mycoursesurl' => $mycoursesurl,
        'competenciesurl' => $competenciesurl,
        'tmsurl' => $tmsurl,
        'hastmsurl' => !empty($tmsurl),
        'langviurl' => $languageurl('vi'),
        'langenurl' => $languageurl('en'),
        'langfrurl' => $languageurl('fr'),
        'langvi' => str_starts_with($currentlang, 'vi'),
        'langen' => str_starts_with($currentlang, 'en'),
        'langfr' => str_starts_with($currentlang, 'fr'),
        'mainmenu' => $mainmenu,
        'nav_active_home' => $activepage === 'home',
        'nav_active_courses' => $activepage === 'catalog',
        'nav_active_mycourses' => $activepage === 'mycourses',
        'nav_active_competencies' => $activepage === 'competencies',
    ];
}

/**
 * Serves theme files (logos, backgrounds)
 */
function theme_meko_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    if ($context->contextlevel == CONTEXT_SYSTEM && in_array($filearea, ['logo', 'logowhite', 'favicon'])) {
        $theme = theme_config::load('meko');
        return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
    }
    send_file_not_found();
}

/**
 * Build the MEKO course catalogue template context and render it.
 *
 * @param stdClass|null $category Current category, when a category is selected.
 * @return string
 */
function theme_meko_render_course_catalog($category = null) {
    global $PAGE, $OUTPUT, $USER, $DB, $CFG, $SITE;

    require_once($CFG->dirroot . '/course/lib.php');
    $templatecontext = [
        'sitename' => format_string($SITE->fullname ?? get_site()->fullname),
        'output' => $OUTPUT,
        'logourl' => theme_meko_header_logo($PAGE->theme, $OUTPUT),
        'isloggedin' => isloggedin() && !isguestuser(),
        'userfullname' => fullname($USER),
        'userprofileurl' => (new moodle_url('/user/profile.php', ['id' => $USER->id]))->out(),
        'loginurl' => (new moodle_url('/login/index.php'))->out(),
        'logouturl' => (new moodle_url('/login/logout.php', ['sesskey' => sesskey()]))->out(),
        'homeurl' => (new moodle_url('/', ['redirect' => 0]))->out(),
        'catalogurl' => (new moodle_url('/course/index.php'))->out(),
        'courseviewbase' => (new moodle_url('/course/view.php'))->out(false),
        'mycoursesurl' => (new moodle_url('/my/courses.php'))->out(),
        'competenciesurl' => (new moodle_url('/theme/meko/competencies.php'))->out(),
        'courses' => [],
    ];

    $records = $DB->get_records_sql(
        "SELECT c.id, c.fullname, c.shortname, c.summary, c.category, c.startdate, c.enddate,
                c.idnumber, cat.name AS categoryname
           FROM {course} c
           LEFT JOIN {course_categories} cat ON cat.id = c.category
          WHERE c.id != :siteid AND c.visible = 1
       ORDER BY c.sortorder ASC",
        ['siteid' => SITEID]
    );
    $catalogcourses = [];
    $publiccourses = \core_course_category::get(0)->get_courses([
        'recursive' => true,
        'coursecontacts' => true,
    ]);
    $coursecompetencymap = [];
    $competencyrecords = $DB->get_records_sql(
        "SELECT CONCAT(coursecomp.courseid, '-', comp.id) AS recordkey,
                coursecomp.courseid, comp.idnumber, comp.shortname
           FROM {competency_coursecomp} coursecomp
           JOIN {competency} comp ON comp.id = coursecomp.competencyid
           JOIN {course} c ON c.id = coursecomp.courseid
          WHERE c.visible = 1 AND c.id != :siteid
       ORDER BY comp.sortorder ASC",
        ['siteid' => SITEID]
    );
    foreach ($competencyrecords as $competencyrecord) {
        if (empty($competencyrecord->idnumber)) {
            continue;
        }
        $coursecompetencymap[$competencyrecord->courseid][] = $competencyrecord->idnumber;
        $competencymap[$competencyrecord->idnumber] = format_string($competencyrecord->shortname);
    }
    foreach ($records as $course) {
        $coursecontext = context_course::instance($course->id);
        $competencyids = $coursecompetencymap[$course->id] ?? [];
        $contacts = isset($publiccourses[$course->id]) ? $publiccourses[$course->id]->get_course_contacts() : [];
        $templatecontext['courses'][] = [
            'id' => (int)$course->id,
            'code' => $course->idnumber ?: $course->shortname,
            'fullname' => format_string($course->fullname, true, ['context' => $coursecontext]),
            'summary' => shorten_text(clean_text(strip_tags($course->summary)), 180),
            'categoryid' => (int)$course->category,
            'competencyids' => implode(',', $competencyids),
            'categoryname' => $course->categoryname ? format_string($course->categoryname) : get_string('uncategorised'),
            'viewurl' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
            'startdate' => $course->startdate ? userdate($course->startdate, get_string('strftimedate', 'langconfig')) : '',
            'visible' => true,
        ];
        $catalogcourses[] = [
            'id' => (int)$course->id,
            'code' => $course->idnumber ?: $course->shortname,
            'title' => format_string($course->fullname, true, ['context' => $coursecontext]),
            'category_id' => (int)$course->category,
            'category_name' => $course->categoryname ? format_string($course->categoryname) : get_string('uncategorised'),
            'competencies' => $competencyids,
            'teacher' => s(implode(', ', array_column($contacts, 'username'))),
            'startdate' => $course->startdate ? userdate($course->startdate, get_string('strftimedate', 'langconfig')) : '',
            'summary' => shorten_text(clean_text(strip_tags($course->summary)), 180),
        ];
    }
    $templatecontext['coursejson'] = json_encode($catalogcourses, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    $templatecontext['coursecount'] = count($templatecontext['courses']);
    $templatecontext['competencies'] = [];
    foreach ($competencymap ?? [] as $idnumber => $competencyname) {
        $templatecontext['competencies'][] = [
            'idnumber' => $idnumber,
            'name' => $competencyname,
        ];
    }
    $templatecontext['hascompetencies'] = !empty($templatecontext['competencies']);

    $templatecontext['categories'] = [];
    foreach ($DB->get_records('course_categories', ['visible' => 1], 'sortorder ASC', 'id,name') as $coursecategory) {
        $templatecontext['categories'][] = [
            'id' => (int)$coursecategory->id,
            'name' => format_string($coursecategory->name),
        ];
    }
    $templatecontext['categoryjson'] = json_encode($templatecontext['categories'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    $templatecontext = array_merge(theme_meko_get_navbar_context('catalog'), $templatecontext);
    return $OUTPUT->render_from_template('theme_meko/catalog', $templatecontext);
}

// ------------------------------------------------------------------------------
// ACCEES CORE HELPERS & METADATA PROVIDERS (Merged from theme_accees)
// ------------------------------------------------------------------------------
function theme_accees_lms_theme_get_frontpage_competencies() {
    return [
        'student_domains' => [
            [
                'code' => 'DIGCOMP-STU-1',
                'title' => get_string('stu_d1_title', 'theme_meko'),
                'desc' => get_string('stu_d1_desc', 'theme_meko'),
                'badge_label' => get_string('stu_d1_code', 'theme_meko'),
                'meta_label' => get_string('stu_d1_meta', 'theme_meko'),
                'icon' => 'fa-solid fa-database',
                'icon_bg' => '#EBF0FF',
                'icon_color' => '#0000B6',
                'border_color' => '#0000B6',
                'cat_url' => '/course/index.php?categoryid=7',
            ],
            [
                'code' => 'DIGCOMP-STU-2',
                'title' => get_string('stu_d2_title', 'theme_meko'),
                'desc' => get_string('stu_d2_desc', 'theme_meko'),
                'badge_label' => get_string('stu_d2_code', 'theme_meko'),
                'meta_label' => get_string('stu_d2_meta', 'theme_meko'),
                'icon' => 'fa-solid fa-comments',
                'icon_bg' => '#EBF0FF',
                'icon_color' => '#0000B6',
                'border_color' => '#0000B6',
                'cat_url' => '/course/index.php?categoryid=7',
            ],
            [
                'code' => 'DIGCOMP-STU-3',
                'title' => get_string('stu_d3_title', 'theme_meko'),
                'desc' => get_string('stu_d3_desc', 'theme_meko'),
                'badge_label' => get_string('stu_d3_code', 'theme_meko'),
                'meta_label' => get_string('stu_d3_meta', 'theme_meko'),
                'icon' => 'fa-solid fa-pen-nib',
                'icon_bg' => '#EBF0FF',
                'icon_color' => '#0000B6',
                'border_color' => '#0000B6',
                'cat_url' => '/course/index.php?categoryid=7',
            ],
            [
                'code' => 'DIGCOMP-STU-4',
                'title' => get_string('stu_d4_title', 'theme_meko'),
                'desc' => get_string('stu_d4_desc', 'theme_meko'),
                'badge_label' => get_string('stu_d4_code', 'theme_meko'),
                'meta_label' => get_string('stu_d4_meta', 'theme_meko'),
                'icon' => 'fa-solid fa-shield-halved',
                'icon_bg' => '#FFF5EB',
                'icon_color' => '#EA6400',
                'border_color' => '#EA6400',
                'cat_url' => '/course/index.php?categoryid=7',
            ],
            [
                'code' => 'DIGCOMP-STU-5',
                'title' => get_string('stu_d5_title', 'theme_meko'),
                'desc' => get_string('stu_d5_desc', 'theme_meko'),
                'badge_label' => get_string('stu_d5_code', 'theme_meko'),
                'meta_label' => get_string('stu_d5_meta', 'theme_meko'),
                'icon' => 'fa-solid fa-screwdriver-wrench',
                'icon_bg' => '#F0FDF0',
                'icon_color' => '#39B500',
                'border_color' => '#39B500',
                'cat_url' => '/course/index.php?categoryid=7',
            ],
        ],
        'teacher_domains' => [
            [
                'code' => 'DIGCOMP-FAC-1',
                'title' => get_string('fac_d1_title', 'theme_meko'),
                'desc' => get_string('fac_d1_desc', 'theme_meko'),
                'badge_label' => get_string('fac_d1_code', 'theme_meko'),
                'meta_label' => get_string('fac_d1_meta', 'theme_meko'),
                'icon' => 'fa-solid fa-briefcase',
                'icon_bg' => '#EBF0FF',
                'icon_color' => '#0000B6',
                'border_color' => '#0000B6',
                'cat_url' => '/course/index.php?categoryid=8',
            ],
            [
                'code' => 'DIGCOMP-FAC-2',
                'title' => get_string('fac_d2_title', 'theme_meko'),
                'desc' => get_string('fac_d2_desc', 'theme_meko'),
                'badge_label' => get_string('fac_d2_code', 'theme_meko'),
                'meta_label' => get_string('fac_d2_meta', 'theme_meko'),
                'icon' => 'fa-solid fa-folder-open',
                'icon_bg' => '#EBF0FF',
                'icon_color' => '#0000B6',
                'border_color' => '#0000B6',
                'cat_url' => '/course/index.php?categoryid=8',
            ],
            [
                'code' => 'DIGCOMP-FAC-3',
                'title' => get_string('fac_d3_title', 'theme_meko'),
                'desc' => get_string('fac_d3_desc', 'theme_meko'),
                'badge_label' => get_string('fac_d3_code', 'theme_meko'),
                'meta_label' => get_string('fac_d3_meta', 'theme_meko'),
                'icon' => 'fa-solid fa-chalkboard-user',
                'icon_bg' => '#EBF0FF',
                'icon_color' => '#0000B6',
                'border_color' => '#0000B6',
                'cat_url' => '/course/index.php?categoryid=8',
            ],
            [
                'code' => 'DIGCOMP-FAC-4',
                'title' => get_string('fac_d4_title', 'theme_meko'),
                'desc' => get_string('fac_d4_desc', 'theme_meko'),
                'badge_label' => get_string('fac_d4_code', 'theme_meko'),
                'meta_label' => get_string('fac_d4_meta', 'theme_meko'),
                'icon' => 'fa-solid fa-chart-line',
                'icon_bg' => '#F0FDF0',
                'icon_color' => '#39B500',
                'border_color' => '#39B500',
                'cat_url' => '/course/index.php?categoryid=8',
            ],
            [
                'code' => 'DIGCOMP-FAC-5',
                'title' => get_string('fac_d5_title', 'theme_meko'),
                'desc' => get_string('fac_d5_desc', 'theme_meko'),
                'badge_label' => get_string('fac_d5_code', 'theme_meko'),
                'meta_label' => get_string('fac_d5_meta', 'theme_meko'),
                'icon' => 'fa-solid fa-universal-access',
                'icon_bg' => '#FFF5EB',
                'icon_color' => '#EA6400',
                'border_color' => '#EA6400',
                'cat_url' => '/course/index.php?categoryid=8',
            ],
            [
                'code' => 'DIGCOMP-FAC-6',
                'title' => get_string('fac_d6_title', 'theme_meko'),
                'desc' => get_string('fac_d6_desc', 'theme_meko'),
                'badge_label' => get_string('fac_d6_code', 'theme_meko'),
                'meta_label' => get_string('fac_d6_meta', 'theme_meko'),
                'icon' => 'fa-solid fa-laptop-code',
                'icon_bg' => '#EBF0FF',
                'icon_color' => '#0000B6',
                'border_color' => '#0000B6',
                'cat_url' => '/course/index.php?categoryid=8',
            ]
        ]
    ];
}

/**
 * Trả về thông tin thương hiệu động theo Profile ('accees' hoặc 'meko').
 *
 * Cho phép phân tách linh hoạt giữa:
 * - Profile 'accees': Dành cho môi trường Dự án ACCEES (DEV3, Staging, Production - Đại học Cần Thơ CTU).
 * - Profile 'meko': Dành cho môi trường nội bộ Dev Team (DEV5, Local Docker - Mekosoft).
 *
 * @param theme_config|null $theme
 * @return array
 */
function theme_accees_lms_theme_get_brand_profile($theme = null) {
    global $CFG;

    $themename = !empty($theme->name) ? $theme->name : 'meko';

    // Mặc định cho theme_meko là 'meko', các theme khác là 'accees'
    $profile = ($themename === 'meko') ? 'meko' : 'accees';

    $dbprofile = get_config('theme_' . $themename, 'brand_profile');
    if (!empty($dbprofile)) {
        $profile = strtolower(trim($dbprofile));
    } else if (!empty($theme->settings->brand_profile)) {
        $profile = strtolower(trim($theme->settings->brand_profile));
    } else if (!empty($CFG->brand_profile)) {
        $profile = strtolower(trim($CFG->brand_profile));
    } else if (!empty($CFG->accees_brand_profile)) {
        $profile = strtolower(trim($CFG->accees_brand_profile));
    }

    $curlang = current_language();
    $isvi = ($curlang === 'vi');
    $isfr = ($curlang === 'fr');

    if ($profile === 'meko') {
        return [
            'profile' => 'meko',
            'is_meko' => true,
            'is_accees' => false,
            'brand_name' => 'MEKO',
            'brand_badge_text' => 'MEKO LMS',
            'hero_badge' => $isvi ? 'DỰ ÁN MEKO · MEKOSOFT' : ($isfr ? 'PROJET MEKO · MEKOSOFT' : 'MEKO PROJECT · MEKOSOFT'),
            'hero_title' => $isvi ? 'Hệ Thống Quản Lý Học Tập Năng Lực Số (MEKO-LMS)' : ($isfr ? 'Système de Gestion de l\'Apprentissage (MEKO-LMS)' : 'Digital Competence Learning Management System (MEKO-LMS)'),
            'hero_subtitle' => $isvi
                ? 'Nền tảng đào tạo và đánh giá theo chuẩn Khung năng lực số Sinh viên (DigComp 2.2) và Giảng viên (DigCompEdu) thuộc Hệ sinh thái Công nghệ Giáo dục Meko.'
                : ($isfr
                    ? 'Plateforme de formation et d’évaluation alignée sur les référentiels DigComp 2.2 et DigCompEdu au sein de l’écosystème Meko.'
                    : 'Digital competence training and assessment platform aligned with DigComp 2.2 and DigCompEdu Frameworks under the Meko EdTech Ecosystem.'),
            'login_title' => $isvi ? 'Đăng nhập MEKO-LMS' : ($isfr ? 'Connexion à MEKO-LMS' : 'Sign In to MEKO-LMS'),
            'login_project_title' => $isvi ? 'DỰ ÁN PHÁT TRIỂN NĂNG LỰC SỐ MEKO (MEKOSOFT)' : ($isfr ? 'PROJET COMPÉTENCES NUMÉRIQUES MEKO (MEKOSOFT)' : 'MEKO DIGITAL COMPETENCE PROJECT (MEKOSOFT)'),
            'institution_name' => 'Mekosoft',
            'coord_note' => $isvi
                ? 'Hệ thống Quản lý Học tập MEKO-LMS (Moodle 5.3 LTS) • Đơn vị điều phối: Mekosoft'
                : ($isfr
                    ? 'Système de Gestion de l\'Apprentissage MEKO-LMS • Coordinateur : Mekosoft'
                    : 'Learning Management System MEKO-LMS • Coordinator: Mekosoft'),
            'support_email' => 'support@mekosoft.vn',
            'support_phone' => '(+84) 28 3822 8888',
            'portal_url' => 'https://mekosoft.vn',
            'portal_name' => 'Cổng Mekosoft (mekosoft.vn)',
            'logo_url' => $CFG->wwwroot . '/theme/' . $themename . '/pix/logo_meko.svg',
            'logo_white_url' => $CFG->wwwroot . '/theme/' . $themename . '/pix/logo_meko_white.svg',
            'favicon_ico' => $CFG->wwwroot . '/theme/' . $themename . '/pix/favicon_meko.ico',
            'favicon_png' => $CFG->wwwroot . '/theme/' . $themename . '/pix/favicon_meko.png',
            'is_eu_funded' => false,
            'footer_desc' => $isvi
                ? 'Hệ thống Quản lý Học tập Năng lực số MEKO-LMS được phát triển bởi Công ty Cổ phần Công nghệ Mekosoft, phục vụ đào tạo và phát triển năng lực số doanh nghiệp và đại học.'
                : 'MEKO-LMS is developed by Mekosoft Corporation, an EdTech platform for digital competence training and assessment.',
            'copyright' => $isvi ? '© 2026 Dự án Meko. Mọi quyền được bảo lưu.' : ($isfr ? '© 2026 Projet Meko. Tous droits réservés.' : '© 2026 Meko Project. All rights reserved.'),
        ];
    }

    // Default ACCEES profile
    return [
        'profile' => 'accees',
        'is_meko' => false,
        'is_accees' => true,
        'brand_name' => 'ACCEES',
        'brand_badge_text' => 'ACCEES LMS',
        'hero_badge' => $isvi ? 'DỰ ÁN ACCEES · ĐẠI HỌC CẦN THƠ (CTU)' : ($isfr ? 'PROJET ACCEES · UNIVERSITÉ DE CAN THO (CTU)' : 'ACCEES PROJECT · CAN THO UNIVERSITY (CTU)'),
        'hero_title' => $isvi ? 'Hệ Thống Quản Lý Học Tập Năng Lực Số (ACCEES-LMS)' : ($isfr ? 'Système de Gestion de l’Apprentissage des Compétences Numériques (ACCEES-LMS)' : 'Digital Competence Learning Management System (ACCEES-LMS)'),
        'hero_subtitle' => $isvi
            ? 'Nền tảng đào tạo và đánh giá theo chuẩn Khung năng lực số Sinh viên (DigComp 2.2) và Giảng viên (DigCompEdu) thuộc Hệ sinh thái Giáo dục Đại học số ACCEES.'
            : ($isfr
                ? 'Plateforme de formation et d’évaluation alignée sur les référentiels DigComp 2.2 et DigCompEdu au sein de l’écosystème ACCEES.'
                : 'Digital competence training and assessment platform aligned with DigComp 2.2 and DigCompEdu Frameworks under the ACCEES Ecosystem.'),
        'login_title' => $isvi ? 'Đăng nhập ACCEES-LMS' : ($isfr ? 'Connexion à ACCEES-LMS' : 'Sign In to ACCEES-LMS'),
        'login_project_title' => $isvi ? 'DỰ ÁN ACCEES (ERASMUS+ CBHE)' : ($isfr ? 'PROJET ACCEES (ERASMUS+ CBHE)' : 'ACCEES PROJECT (ERASMUS+ CBHE)'),
        'institution_name' => $isvi ? 'Đại học Cần Thơ (CTU)' : ($isfr ? 'Université de Can Tho (CTU)' : 'Can Tho University (CTU)'),
        'coord_note' => $isvi
            ? 'Hệ thống Quản lý Học tập ACCEES-LMS (Moodle 5.3 LTS) • Đơn vị điều phối: Đại học Cần Thơ (CTU)'
            : ($isfr
                ? 'Système de Gestion de l\'Apprentissage ACCEES-LMS (Moodle 5.3 LTS) • Coordinateur : Université de Can Tho (CTU)'
                : 'Learning Management System ACCEES-LMS (Moodle 5.3 LTS) • Coordinator: Can Tho University (CTU)'),
        'support_email' => 'support@accees.vn',
        'support_phone' => '(+84) 292 3832 663',
        'portal_url' => 'https://accees.ctu.edu.vn',
        'portal_name' => 'Cổng Dự án ACCEES (CTU)',
        'logo_url' => $CFG->wwwroot . '/theme/' . $themename . '/pix/logo.png',
        'logo_white_url' => $CFG->wwwroot . '/theme/' . $themename . '/pix/logo_white.png',
        'favicon_ico' => $CFG->wwwroot . '/theme/' . $themename . '/pix/favicon.ico',
        'favicon_png' => $CFG->wwwroot . '/theme/' . $themename . '/pix/favicon.png',
        'is_eu_funded' => true,
        'footer_desc' => get_string('footer_default_desc', 'theme_meko'),
        'copyright' => $isvi ? '© 2026 Dự án ACCEES Erasmus+. Mọi quyền được bảo lưu.' : ($isfr ? '© 2026 Projet ACCEES Erasmus+. Tous droits réservés.' : '© 2026 ACCEES Erasmus+ Project. All rights reserved.'),
    ];
}

/**
 * Returns shared Mustache context variables for all ACCEES layouts (Header, Footer, Ecosystem links).
 *
 * @param theme_config $theme
 * @param renderer_base $OUTPUT
 * @param stdClass $CFG
 * @param stdClass $SITE
 * @return array
 */
function theme_accees_lms_theme_get_common_context($theme, $OUTPUT, $CFG, $SITE) {
    $themename = $theme->name ?? 'meko';
    $brand = theme_accees_lms_theme_get_brand_profile($theme);
    $logourl = $theme->name === 'meko' ? theme_meko_header_logo($theme, $OUTPUT) : $theme->setting_file_url('logo', 'logo');
    if (empty($logourl)) {
        $logourl = !empty($brand['logo_url']) ? $brand['logo_url'] : ($CFG->wwwroot . '/theme/accees_lms_theme/pix/logo.png');
    } else if (strpos($logourl, '//') === 0) {
        $logourl = 'https:' . $logourl;
    }

    $logowhiteurl = !empty($brand['logo_white_url']) ? $brand['logo_white_url'] : ($CFG->wwwroot . '/theme/accees_lms_theme/pix/logo_white.png');
    $eulogowhiteurl = $CFG->wwwroot . '/theme/accees_lms_theme/pix/eu_cofunded_white.png';

    $curlang = current_language();
    $isvi = ($curlang === 'vi');
    $isen = ($curlang === 'en' || (!$isvi && $curlang !== 'fr'));
    $isfr = ($curlang === 'fr');
    $custommenuitems = $CFG->custommenuitems ?? '';
    if (!empty($CFG->navfilter) && !empty($CFG->stringfilters)) {
        $custommenuitems = \filter_manager::instance()->filter_string($custommenuitems, \context_system::instance());
    }
    $custommenu = new \core\output\custom_menu($custommenuitems, $curlang);
    $custommenuhtml = '';
    foreach ($custommenu->get_children() as $item) {
        $custommenuhtml .= $OUTPUT->render_from_template('core/custom_menu_item', $item->export_for_template($OUTPUT));
    }

    $herotitle = !empty($brand['hero_title']) ? $brand['hero_title'] : get_string('hero_default_title', 'theme_meko');
    if (!empty($theme->settings->herotitle) && $isvi) {
        $herotitle = format_string($theme->settings->herotitle, true);
    }

    $herosubtitle = !empty($brand['hero_subtitle']) ? $brand['hero_subtitle'] : get_string('hero_default_subtitle', 'theme_meko');
    if (!empty($theme->settings->herosubtitle) && $isvi) {
        $herosubtitle = strip_tags(format_text($theme->settings->herosubtitle, FORMAT_HTML));
    }

    $footerdesc = !empty($brand['footer_desc']) ? $brand['footer_desc'] : get_string('footer_default_desc', 'theme_meko');
    if (!empty($theme->settings->footerdescription) && $isvi) {
        $footerdesc = format_string($theme->settings->footerdescription, true);
    }

    global $PAGE;
    $currpath = '';
    try {
        if (!empty($PAGE) && method_exists($PAGE, 'has_set_url') && $PAGE->has_set_url()) {
            $currpath = $PAGE->url->get_path();
        }
    } catch (\Throwable $e) {}

    $scriptname = $_SERVER['SCRIPT_NAME'] ?? '';
    $requesturi = $_SERVER['REQUEST_URI'] ?? '';
    $fulluri = (!empty($currpath)) ? $currpath : (!empty($scriptname) ? $scriptname : $requesturi);

    $pagelayout = '';
    try {
        if (!empty($PAGE)) {
            $pagelayout = (string)$PAGE->pagelayout;
        }
    } catch (\Throwable $e) {}

    $is_mycourses = (
        $pagelayout === 'mycourses' ||
        $pagelayout === 'mydashboard' ||
        strpos($fulluri, '/my') !== false ||
        strpos($requesturi, '/my') !== false
    );

    $is_courses = (
        !$is_mycourses && (
            $pagelayout === 'coursecategory' ||
            $pagelayout === 'course' ||
            $pagelayout === 'incourse' ||
            strpos($fulluri, '/course/') !== false ||
            strpos($requesturi, '/course/') !== false ||
            strpos($fulluri, '/enrol/') !== false ||
            strpos($requesturi, '/enrol/') !== false
        )
    );

    $is_competencies = (
        !$is_mycourses && !$is_courses && (
            strpos($fulluri, 'accees_competencies') !== false ||
            strpos($requesturi, 'accees_competencies') !== false ||
            strpos($fulluri, '/competencies') !== false ||
            strpos($requesturi, '/competencies') !== false
        )
    );

    $is_home = (
        !$is_mycourses &&
        !$is_courses &&
        !$is_competencies &&
        (
            $pagelayout === 'frontpage' ||
            $fulluri === '/' ||
            $fulluri === '/index.php' ||
            strpos($requesturi, '/?redirect=0') !== false ||
            strpos($fulluri, '/?redirect=0') !== false ||
            preg_match('#^/(index\.php)?(\?.*)?$#', $requesturi)
        )
    );

    return [
        'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), 'escape' => false]),
        'brand' => $brand,
        'hero_badge' => $brand['hero_badge'],
        'brand_name' => $brand['brand_name'],
        'brand_badge_text' => $brand['brand_badge_text'],
        'is_eu_funded' => !empty($brand['is_eu_funded']),
        'support_email' => $brand['support_email'],
        'support_phone' => $brand['support_phone'],
        'portal_url' => $brand['portal_url'],
        'portal_name' => $brand['portal_name'],
        'footer_coord_note' => $brand['coord_note'],
        'footer_copyright' => $brand['copyright'],
        'output' => $OUTPUT,
        'config' => [
            'wwwroot' => $CFG->wwwroot,
        ],
        'isloggedin' => isloggedin() && !isguestuser(),
        'is_site_admin' => is_siteadmin(),
        'nav_active_home' => $is_home,
        'nav_active_courses' => $is_courses,
        'nav_active_competencies' => $is_competencies,
        'nav_active_mycourses' => $is_mycourses,
        'usermenu' => theme_meko_profile_menu($OUTPUT),
        'standard_head_html' => $OUTPUT->standard_head_html(),
        'page_title' => $OUTPUT->page_title(),
        'logo_url' => $logourl,
        'logo_white_url' => $logowhiteurl,
        'eu_logo_white_url' => $eulogowhiteurl,
        'lang_is_vi' => $isvi,
        'meko_custom_menu' => $custommenuhtml,
        'lang_is_en' => $isen,
        'lang_is_fr' => $isfr,
        'herotitle' => $herotitle,
        'herosubtitle' => $herosubtitle,
        'footer_desc' => $footerdesc,
        'sso_account_url' => $themename === 'meko' ? theme_meko_service_url($theme, 'sso') : (!empty($theme->settings->ssourl)
            ? $theme->settings->ssourl
            : theme_meko_service_url($theme, 'sso')),
        'dlrp_url' => !empty($theme->settings->dlrpurl)
            ? $theme->settings->dlrpurl
            : 'http://meko.localhost/dlrp',
        'tms_url' => $themename === 'meko' ? theme_meko_service_url($theme, 'tms') : (!empty($theme->settings->tmsurl)
            ? $theme->settings->tmsurl
            : theme_meko_service_url($theme, 'tms')),
        'theme_version_badge' => ($themename === 'meko')
            ? 'MEKOSOFT LMS Theme v1.7.1 (Build: 2026111301)'
            : 'ACCEES LMS Theme v1.7.1 (Build: 2026111301)',
    ];
}

/**
 * Queries visible Moodle courses filtered by admin-configured Category IDs for the Frontpage.
 *
 * @param theme_config $theme
 * @return array Contains 'has_featured_courses', 'course_categories', 'featured_courses'
 */
function theme_accees_lms_theme_get_frontpage_courses($theme) {
    global $CFG, $DB, $OUTPUT;

    $categoriesdata = [];
    $coursesdata = [];

    // Enforce 6 courses for frontpage to ensure a perfectly balanced 3-column x 2-row grid without dangling cards.
    $limit = 6;

    $rawcatids = !empty($theme->settings->frontpagecategories) ? trim($theme->settings->frontpagecategories) : '';
    $catids = [];
    if ($rawcatids !== '') {
        foreach (explode(',', $rawcatids) as $part) {
            $cid = (int)trim($part);
            if ($cid > 0) {
                $catids[] = $cid;
            }
        }
    }

    // Fetch categories from mdl_course_categories.
    if (!empty($catids)) {
        list($insql, $inparams) = $DB->get_in_or_equal($catids, SQL_PARAMS_NAMED);
        $categories = $DB->get_records_select('course_categories', "id $insql AND visible = 1", $inparams, 'sortorder ASC');
    } else {
        $categories = $DB->get_records_select('course_categories', 'visible = 1', [], 'sortorder ASC', '*', 0, 12);
    }

    // Compute accurate visible course counts per category.
    $countsbycat = [];
    try {
        $countsbycat = $DB->get_records_sql("
            SELECT c.category, COUNT(c.id) AS cnt
              FROM {course} c
             WHERE c.visible = 1 AND c.id <> :siteid
          GROUP BY c.category
        ", ['siteid' => SITEID]);
    } catch (\Throwable $e) {
        $countsbycat = [];
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

    foreach ($categories as $cat) {
        $realcount = isset($countsbycat[$cat->id]) ? (int)$countsbycat[$cat->id]->cnt : (int)$cat->coursecount;
        $decodedname = html_entity_decode(format_string($cat->name, true), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Skip empty default Moodle Category 1 / Miscellaneous with 0 courses.
        if ($realcount <= 0 && ((int)$cat->id === 1 || stripos($decodedname, 'Category 1') !== false || stripos($decodedname, 'Miscellaneous') !== false)) {
            continue;
        }
        if ($realcount <= 0) {
            continue;
        }

        if (!$isvi && isset($cat_en_map[$decodedname])) {
            $decodedname = $cat_en_map[$decodedname];
        }

        $categoriesdata[] = [
            'id' => $cat->id,
            'name' => $decodedname,
            'coursecount' => $realcount,
            'url' => (new moodle_url('/course/index.php', ['categoryid' => $cat->id]))->out(false),
        ];
    }

    // Fetch courses belonging to the selected categories (or all visible courses if none filtered).
    if (!empty($catids)) {
        list($cinsql, $cinparams) = $DB->get_in_or_equal($catids, SQL_PARAMS_NAMED);
        $cinparams['siteid'] = SITEID;
        $courses = $DB->get_records_select(
            'course',
            "category $cinsql AND visible = 1 AND id <> :siteid",
            $cinparams,
            'sortorder ASC, timemodified DESC',
            '*',
            0,
            $limit
        );
    } else {
        $courses = $DB->get_records_select(
            'course',
            'visible = 1 AND id <> :siteid',
            ['siteid' => SITEID],
            'sortorder ASC, timemodified DESC',
            '*',
            0,
            $limit
        );
    }

    foreach ($courses as $course) {
        $catname = isset($categories[$course->category])
            ? html_entity_decode(format_string($categories[$course->category]->name, true), ENT_QUOTES | ENT_HTML5, 'UTF-8')
            : get_string('nav_courses', 'theme_meko');

        $carddata = theme_accees_lms_theme_prepare_course_card($course, null, $catname);
        $carddata['categoryid'] = $course->category;

        $coursesdata[] = $carddata;
    }

    return [
        'has_course_categories' => !empty($categoriesdata),
        'course_categories' => $categoriesdata,
        'has_featured_courses' => !empty($coursesdata),
        'featured_courses' => $coursesdata,
    ];
}

/**
 * Trích xuất và chuẩn bị dữ liệu chuẩn 14 trường thông tin cho component course_card.mustache.
 * Dùng chung cho cả Frontpage (lib.php), Course Catalog (core_course_renderer.php) và My Courses.
 *
 * @param object $c Khóa học Moodle record
 * @param array|null $competency_filter Bộ lọc năng lực nếu có
 * @param string $cat_name Tên danh mục khóa học
 * @param string $framework_filter Khung năng lực đang chọn ('students'|'faculty'|'')
 * @return array
 */
function theme_accees_lms_theme_prepare_course_card($c, $competency_filter = null, $cat_name = '', $framework_filter = '') {
    global $DB, $USER;

    $curlang = current_language();
    $isvi = ($curlang === 'vi');
    $isfr = ($curlang === 'fr');

    // Default localized instructor map per demo course shortname so cards show realistic academic faculty.
    $defaultinstructors = [
        'NLS-01-IMS' => $isvi ? 'PGS.TS. Trần Thanh Điền' : 'Assoc. Prof. Tran Thanh Dien',
        'NLS-01-OER' => $isvi ? 'TS. Nguyễn Thái Nghe' : 'Dr. Nguyen Thai Nghe',
        'NLS-01-H5P' => $isvi ? 'TS. Lâm Nhựt Khang' : 'Dr. Lam Nhut Khang',
        'NLS-01-VIDEO' => $isvi ? 'ThS. Phan Quốc Huy' : 'MSc. Phan Quoc Huy',
        'NLS-01-PODCAST' => $isvi ? 'TS. Bùi Võ Quốc Bảo' : 'Dr. Bui Vo Quoc Bao',
        'NLS-01-ACC' => $isvi ? 'PGS.TS. Đỗ Thanh Nghị' : 'Assoc. Prof. Do Thanh Nghi',
        'NLS-02-DATA' => $isvi ? 'PGS.TS. Phạm Nguyên Khang' : 'Assoc. Prof. Pham Nguyen Khang',
        'NLS-02-LOGS' => $isvi ? 'TS. Trần Công Án' : 'Dr. Tran Cong An',
        'NLS-02-DASH' => $isvi ? 'TS. Trương Quốc Định' : 'Dr. Truong Quoc Dinh',
        'NLS-02-INTERV' => $isvi ? 'TS. Ngô Bá Hùng' : 'Dr. Ngo Ba Hung',
        'NLS-03-AI' => $isvi ? 'PGS.TS. Nguyễn Hữu Hòa' : 'Assoc. Prof. Nguyen Huu Hoa',
        'NLS-04-SEC' => $isvi ? 'TS. Lê Văn Lâm' : 'Dr. Le Van Lam',
    ];

    // 1. NHÓM 1: 06 TRƯỜNG LÕI MOODLE CORE
    $teachers = [];
    if (function_exists('get_course_contacts')) {
        $contacts = get_course_contacts($c->id);
        foreach ($contacts as $contact) {
            if (is_object($contact)) {
                if (isset($contact->user)) {
                    $teachers[] = fullname($contact->user);
                } else if (isset($contact->name)) {
                    $teachers[] = $contact->name;
                } else if (isset($contact->username)) {
                    $teachers[] = $contact->username;
                }
            } else if (is_array($contact)) {
                $teachers[] = $contact['name'] ?? $contact['username'] ?? '';
            }
        }
    }
    $fallbackteacher = $defaultinstructors[$c->shortname ?? ''] ?? ($isvi ? 'Hội đồng Giảng viên CTU' : ($isfr ? 'Faculté ACCEES CTU' : 'ACCEES CTU Faculty'));
    $has_specific_teacher = (!empty($teachers) && $teachers[0] !== 'TS. Nguyễn Văn A' && $teachers[0] !== 'Nguyen Van A');
    $teacher_name = $has_specific_teacher ? implode(', ', $teachers) : $fallbackteacher;

    // Extract teacher initials (e.g., "PGS.TS. Trần Thanh Điền" -> "TĐ")
    $teacher_initials = 'GV';
    $clean_name = preg_replace('/^(PGS\.TS\.|TS\.|ThS\.|GS\.|Assoc\. Prof\.|Dr\.|MSc\.|Prof\.)\s*/ui', '', trim($teacher_name));
    $name_parts = preg_split('/\s+/u', trim($clean_name));
    if (count($name_parts) >= 2) {
        $first = mb_substr($name_parts[0], 0, 1, 'UTF-8');
        $last = mb_substr(end($name_parts), 0, 1, 'UTF-8');
        $teacher_initials = mb_strtoupper($first . $last, 'UTF-8');
    } else if (!empty($clean_name)) {
        $teacher_initials = mb_strtoupper(mb_substr($clean_name, 0, 2, 'UTF-8'), 'UTF-8');
    }
    $brand = theme_accees_lms_theme_get_brand_profile();
    $teacher_institution = $brand['institution_name'];

    // Course thumbnail extraction with local fallback
    global $OUTPUT;
    $course_image = '';
    try {
        $fs = get_file_storage();
        $context = \context_course::instance($c->id, IGNORE_MISSING);
        if ($context) {
            $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0, 'filename', false);
            if (!empty($files)) {
                $file = reset($files);
                $course_image = \moodle_url::make_pluginfile_url(
                    $file->get_contextid(), $file->get_component(), $file->get_filearea(), null, $file->get_filepath(), $file->get_filename()
                )->out(false);
            }
        }
    } catch (\Throwable $e) {}

    if (empty($course_image)) {
        $slug = strtolower(str_replace(['-', '_'], '', $c->shortname ?? ''));
        $known_slugs = [
            'nls01ims', 'nls01oer', 'nls01h5p', 'nls01video', 'nls01podcast', 'nls01acc',
            'nls02data', 'nls02logs', 'nls02dash', 'nls02interv', 'nls03ai', 'nls04sec'
        ];
        if (in_array($slug, $known_slugs)) {
            $course_image = $OUTPUT->image_url('courses/' . $slug, 'theme_meko')->out(false);
        } else {
            $course_image = $OUTPUT->image_url('courses/default', 'theme_meko')->out(false);
        }
    }

    // Localized course titles & summaries when viewing in English/French.
    $en_titles = [
        'NLS-01-IMS' => 'IMS CP Learning Content Packaging & DLRP Embedding',
        'NLS-01-OER' => 'Editing & Publishing Multimedia Open Educational Resources (OER)',
        'NLS-01-H5P' => 'Designing Advanced Interactive Learning Activities with H5P on Moodle',
        'NLS-01-VIDEO' => 'Producing Micro-learning Modules & Interactive Digital Videos',
        'NLS-01-PODCAST' => 'Building Educational Podcast Channels & Digital Audio Resources',
        'NLS-01-ACC' => 'Universal Design for Accessible Digital Learning Materials (WCAG 2.1)',
        'NLS-02-DATA' => 'Learning Data Analytics & Academic Performance Forecasting',
        'NLS-02-LOGS' => 'Leveraging Moodle Event Logs & xAPI in Higher Education',
        'NLS-02-DASH' => 'Building Real-Time Learning Analytics Dashboards with PowerBI',
        'NLS-02-INTERV' => 'Student Retention Early Warning & Pedagogical Intervention Systems',
        'NLS-03-AI' => 'Generative AI for Quiz Authoring & Automated Competency Assessment',
        'NLS-04-SEC' => 'Information Security, Personal Data Protection & Cyber Resilience in HE',
    ];
    $en_summaries = [
        'NLS-01-IMS' => 'Comprehensive training on IMS Content Packaging (IMS CP), SCORM standards, and embedding open digital resources directly from ACCEES-DLRP into Moodle LMS.',
        'NLS-01-OER' => 'Practical workflow for selecting, adapting, licensing (Creative Commons), and publishing multimedia Open Educational Resources (OER) aligned with digital pedagogy.',
        'NLS-01-H5P' => 'Hands-on authoring of interactive videos, drag-and-drop exercises, branching scenarios, and interactive flashcards natively inside Moodle LMS.',
        'NLS-01-VIDEO' => 'Micro-learning instructional design, recording, and editing concise interactive video lectures tailored for modern university students.',
        'NLS-01-PODCAST' => 'Educational audio scripting, recording, noise reduction, and publishing podcast channels for self-directed and distance learning.',
        'NLS-01-ACC' => 'Standards for designing accessible lectures, PDF documents, and captioned videos supporting diverse learners under WCAG 2.1 AA guidelines.',
        'NLS-02-DATA' => 'Foundations of Learning Analytics, collecting learner interaction telemetry from Moodle, and applying predictive models for academic outcomes.',
        'NLS-02-LOGS' => 'Extracting Moodle Event Logs and standardizing learning activity streams with xAPI (Experience API) for research and quality assurance.',
        'NLS-02-DASH' => 'Connecting Moodle analytics data to PowerBI and Metabase to visualize course completion rates and real-time learner engagement.',
        'NLS-02-INTERV' => 'Configuring early warning indicators to identify at-risk students on LMS and triggering timely pedagogical interventions.',
        'NLS-03-AI' => 'Applying Prompt Engineering and LLMs to generate competency-aligned question banks, automated grading rubrics, and personalized feedback.',
        'NLS-04-SEC' => 'Recognizing phishing threats, securing academic digital identities, preventing malware, and complying with personal data protection regulations.',
    ];

    $fullname_display = html_entity_decode(format_string($c->fullname), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if (!$isvi && isset($en_titles[$c->shortname ?? ''])) {
        $fullname_display = html_entity_decode($en_titles[$c->shortname], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    // Tóm tắt mô tả (summary) cắt gọn 2-3 dòng (145 ký tự), đồng thời giữ bản đầy đủ cho tooltip
    $summary_full = '';
    if (!$isvi && isset($en_summaries[$c->shortname ?? ''])) {
        $summary_full = html_entity_decode($en_summaries[$c->shortname], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    } else if (!empty($c->summary)) {
        $summary_full = html_entity_decode(clean_param(strip_tags($c->summary), PARAM_TEXT), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    if (empty($summary_full)) {
        $summary_full = $isvi
            ? 'Khóa học bồi dưỡng năng lực số chuẩn ACCEES trang bị kiến thức và kỹ năng thực hành e-learning hiện đại...'
            : ($isfr
                ? 'Formation certifiante aux compétences numériques ACCEES pour l\'enseignement supérieur moderne...'
                : 'Standardized ACCEES digital competence training equipping learners with modern e-learning skills...');
    }
    $summary = $summary_full;
    if (mb_strlen($summary, 'UTF-8') > 145) {
        $summary = mb_substr($summary, 0, 142, 'UTF-8') . '...';
    }

    // Ngày khai giảng phân bổ đa dạng thực tế cho các khóa demo
    $startdates_map = [
        'NLS-01-IMS' => '12/11/2026',
        'NLS-01-OER' => '15/11/2026',
        'NLS-01-H5P' => '18/11/2026',
        'NLS-01-VIDEO' => '20/11/2026',
        'NLS-01-PODCAST' => '25/11/2026',
        'NLS-01-ACC' => '28/11/2026',
        'NLS-02-DATA' => '01/12/2026',
        'NLS-02-LOGS' => '05/12/2026',
        'NLS-02-DASH' => '08/12/2026',
        'NLS-02-INTERV' => '10/12/2026',
        'NLS-03-AI' => '15/12/2026',
        'NLS-04-SEC' => '20/12/2026',
    ];
    $startdate_formatted = $startdates_map[$c->shortname ?? ''] ?? (!empty($c->startdate) ? userdate($c->startdate, '%d/%m/%Y') : '15/11/2026');

    // Trạng thái ghi danh & tiến độ % của học viên
    $is_enrolled = false;
    $progress = 0;
    $lastaccess_formatted = $isvi ? '2 giờ trước' : ($isfr ? 'Il y a 2 heures' : '2 hours ago');

    if (!empty($USER->id) && !isguestuser()) {
        try {
            $context = \context_course::instance($c->id, IGNORE_MISSING);
            if ($context && is_enrolled($context, $USER->id)) {
                $is_enrolled = true;
                if (class_exists('\core_completion\progress')) {
                    $progress = (int)\core_completion\progress::get_course_progress_percentage($c, $USER->id);
                }
                $lastaccess = $DB->get_field('user_lastaccess', 'timeaccess', ['userid' => $USER->id, 'courseid' => $c->id]);
                if ($lastaccess) {
                    $ago = $isvi ? ' trước' : ($isfr ? '' : ' ago');
                    $lastaccess_formatted = format_time(time() - $lastaccess) . $ago;
                }
            }
        } catch (\Throwable $e) {}
    }

    // 2. NHÓM 2: 03 TRƯỜNG KHUNG NĂNG LỰC CBE
    $cbe_info = theme_accees_lms_theme_get_course_cbe_metadata($c->id, $competency_filter, $c->shortname ?? '', $framework_filter);

    // 3. NHÓM 3: 05 TRƯỜNG MỞ RỘNG (Moodle Custom Fields API)
    $cf_info = theme_accees_lms_theme_get_course_custom_fields($c->id, $c->shortname ?? '');

    return [
        // Nhóm 1: Core
        'id' => $c->id,
        'fullname' => $fullname_display,
        'shortname' => $c->shortname,
        'summary' => $summary,
        'summary_full' => $summary_full,
        'startdate_formatted' => $startdate_formatted,
        'teacher_name' => $teacher_name,
        'teacher_initials' => $teacher_initials,
        'teacher_institution' => $teacher_institution,
        'course_image' => $course_image,
        'categoryname' => html_entity_decode($cat_name, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        'viewurl' => (new moodle_url('/course/view.php', ['id' => $c->id]))->out(false),
        'is_enrolled' => $is_enrolled,
        'progress' => $progress,
        'lastaccess_formatted' => $lastaccess_formatted,

        // Nhóm 2: CBE
        'domain_pill' => $cbe_info['domain_pill'],
        'target_competency_code' => $cbe_info['target_competency_code'],
        'competency_level' => $cbe_info['competency_level'],
        'competency_level_name' => $cbe_info['competency_level_name'],
        'competency_level_tooltip' => $cbe_info['competency_level_tooltip'],
        'competency_title' => $cbe_info['competency_title'],
        'competency_tooltip' => $cbe_info['competency_tooltip'],
        'cbe_badge_label' => $cbe_info['cbe_badge_label'],
        'passing_criteria' => $cbe_info['passing_criteria'],
        'competency_tag' => $cbe_info['target_competency_code'],

        // Nhóm 3: Custom Fields
        'course_status' => $cf_info['course_status'],
        'delivery_mode' => $cf_info['delivery_mode'],
        'duration' => $cf_info['duration'],
        'institution' => $cf_info['institution'],
        'is_featured' => $cf_info['is_featured'],
        'is_core' => $cf_info['is_featured'],
        'core_badge_label' => $isvi ? 'Cốt lõi' : ($isfr ? 'Tronc commun' : 'Core Module'),

        // Localized UI Labels for course_card.mustache
        'label_featured_title' => $isvi ? 'Học phần cốt lõi trong khung năng lực số' : ($isfr ? 'Module obligatoire du référentiel' : 'Core competency framework module'),
        'label_outcome' => $isvi ? 'Chuẩn đầu ra:' : ($isfr ? 'Compétence cible :' : 'Target Outcome:'),
        'label_instructor' => $isvi ? 'Giảng viên:' : ($isfr ? 'Enseignant :' : 'Instructor:'),
        'label_institution' => $isvi ? 'Đơn vị:' : ($isfr ? 'Institution :' : 'Institution:'),
        'label_lastaccess' => $isvi ? 'Truy cập cuối:' : ($isfr ? 'Dernier accès :' : 'Last access:'),
        'label_duration' => $isvi ? 'Thời lượng:' : ($isfr ? 'Durée :' : 'Duration:'),
        'label_startdate' => $isvi ? 'Khai giảng:' : ($isfr ? 'Début :' : 'Starts:'),
        'label_delivery_mode' => $isvi ? 'Hình thức:' : ($isfr ? 'Format :' : 'Mode:'),
        'label_progress' => $isvi ? 'Tiến độ học tập' : ($isfr ? 'Progression' : 'Learning progress'),
        'label_enter_course' => $isvi ? 'Vào học' : ($isfr ? 'Continuer' : 'Continue Learning'),
        'label_enroll_now' => $isvi ? 'Ghi danh ngay' : ($isfr ? 'S\'inscrire' : 'Enroll Now'),
    ];
}

/**
 * Parse standardized proficiency level metadata dynamically from a Moodle competency record.
 * Supports both Student (N1..N4) and Faculty (Basic, Intermediate, Advanced) frameworks.
 *
 * @param string $idnumber Competency idnumber (e.g. SV_C3_1_N1, GV_A_1_BASIC)
 * @param string $shortname Optional competency shortname fallback
 * @param string|null $lang Moodle language code override
 * @return array|null Normalized level metadata or null if node is not a level leaf
 */
function theme_accees_lms_theme_parse_level_info(string $idnumber, string $shortname = '', ?string $lang = null): ?array {
    $lang = $lang ?: current_language();
    $isvi = ($lang === 'vi');
    $isfr = ($lang === 'fr');
    $idtrim = trim($idnumber);

    if (preg_match('/_N([1-4])$/i', $idtrim, $m) || preg_match('/\bLevel\s*N([1-4])\b/i', $shortname, $m)) {
        $n = (int)$m[1];
        $classes = [
            1 => 'badge-solid-primary',
            2 => 'badge-solid-success',
            3 => 'badge-solid-warning',
            4 => 'badge-solid-slate',
        ];
        return [
            'code' => 'N' . $n,
            'short' => 'N' . $n,
            'short_compact' => 'N' . $n,
            'key' => 'n' . $n,
            'order' => $n,
            'family' => 'students',
            'label' => $isvi ? "Bậc N{$n}" : ($isfr ? "Niveau N{$n}" : "Level N{$n}"),
            'badge_class' => $classes[$n] ?? 'badge-solid-primary',
        ];
    }

    if (stripos($idtrim, 'BASIC') !== false || preg_match('/\[\s*Level\s+Basic\b/i', $shortname)) {
        $short = $isvi ? 'Cơ bản' : ($isfr ? 'Basique' : 'Basic');
        return [
            'code' => 'Basic',
            'short' => $short,
            'short_compact' => $short,
            'key' => 'basic',
            'order' => 10,
            'family' => 'faculty',
            'label' => $isvi ? 'Bậc Cơ bản' : ($isfr ? 'Niveau Basique' : 'Level Basic'),
            'badge_class' => 'badge-solid-primary',
        ];
    }

    if (stripos($idtrim, 'INTERMEDIATE') !== false || preg_match('/\[\s*Level\s+Intermediate\b/i', $shortname)) {
        $short = $isvi ? 'Trung cấp' : ($isfr ? 'Intermédiaire' : 'Intermediate');
        $compact = $isvi ? 'Trung cấp' : 'Interm.';
        return [
            'code' => 'Intermediate',
            'short' => $short,
            'short_compact' => $compact,
            'key' => 'intermediate',
            'order' => 20,
            'family' => 'faculty',
            'label' => $isvi ? 'Bậc Trung cấp' : ($isfr ? 'Niveau Intermédiaire' : 'Level Intermediate'),
            'badge_class' => 'badge-solid-success',
        ];
    }

    if (stripos($idtrim, 'ADVANCED') !== false || preg_match('/\[\s*Level\s+Advanced\b/i', $shortname)) {
        $short = $isvi ? 'Nâng cao' : ($isfr ? 'Avancé' : 'Advanced');
        return [
            'code' => 'Advanced',
            'short' => $short,
            'short_compact' => $short,
            'key' => 'advanced',
            'order' => 30,
            'family' => 'faculty',
            'label' => $isvi ? 'Bậc Nâng cao' : ($isfr ? 'Niveau Avancé' : 'Level Advanced'),
            'badge_class' => 'badge-solid-warning',
        ];
    }

    return null;
}

/**
 * Format 1 or more parsed proficiency levels into a concise card badge label and full tooltip.
 *
 * @param array $level_infos Array of outputs from theme_accees_lms_theme_parse_level_info()
 * @param string|null $lang Moodle language code override
 * @return array ['level' => string, 'level_name' => string, 'level_tooltip' => string]
 */
function theme_accees_lms_theme_format_levels_badge(array $level_infos, ?string $lang = null): array {
    $lang = $lang ?: current_language();
    $isvi = ($lang === 'vi');
    $isfr = ($lang === 'fr');

    $unique = [];
    foreach ($level_infos as $info) {
        if (!empty($info) && !empty($info['key'])) {
            $unique[$info['key']] = $info;
        }
    }

    if (empty($unique)) {
        return [
            'level' => '',
            'level_name' => '',
            'level_tooltip' => '',
        ];
    }

    uasort($unique, function($a, $b) {
        return ((int)$a['order']) <=> ((int)$b['order']);
    });
    $ordered = array_values($unique);

    if (count($ordered) === 1) {
        $only = $ordered[0];
        return [
            'level' => $only['code'],
            'level_name' => $only['label'],
            'level_tooltip' => $only['label'],
        ];
    }

    $full_labels = array_map(function($u) {
        return $u['label'];
    }, $ordered);
    $tooltip = implode(', ', $full_labels);

    $all_students = true;
    foreach ($ordered as $u) {
        if ($u['family'] !== 'students') {
            $all_students = false;
            break;
        }
    }

    if ($all_students) {
        $joined = implode(' · ', array_map(function($u) {
            return $u['short'];
        }, $ordered));
        return [
            'level' => $joined,
            'level_name' => $isvi ? "Bậc {$joined}" : ($isfr ? "Niveaux {$joined}" : "Level {$joined}"),
            'level_tooltip' => $tooltip,
        ];
    }

    $use_compact = (count($ordered) >= 3);
    $joined = implode(' · ', array_map(function($u) use ($use_compact) {
        return $use_compact ? $u['short_compact'] : $u['short'];
    }, $ordered));
    $codes = implode(' · ', array_map(function($u) {
        return $u['code'];
    }, $ordered));

    return [
        'level' => $codes,
        'level_name' => $joined,
        'level_tooltip' => $tooltip,
    ];
}

/**
 * Format a clean human-readable Competency Domain pill label without duplicate prefixes.
 *
 * @param string $domain_code
 * @param string $domain_name
 * @param string|null $lang
 * @return string
 */
function theme_accees_lms_theme_format_domain_pill(string $domain_code, string $domain_name, ?string $lang = null): string {
    $lang = $lang ?: current_language();
    $isvi = ($lang === 'vi');
    $domprefix = $isvi ? 'MIỀN ' : 'DOMAIN ';

    $raw = html_entity_decode(strip_tags($domain_name), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $clean = preg_replace('/^\s*[A-Z0-9]+[\.\:\-]\s*/u', '', trim($raw));
    if ($clean === '') {
        $clean = trim($raw);
    }

    $code = trim($domain_code);
    if ($code !== '' && $clean !== '') {
        return $domprefix . $code . ' · ' . mb_strtoupper($clean, 'UTF-8');
    }
    if ($clean !== '') {
        return $domprefix . mb_strtoupper($clean, 'UTF-8');
    }
    return $code !== '' ? ($domprefix . $code) : '';
}

/**
 * Trích xuất thông tin Khung Năng Lực CBE (Nhóm 2) hoàn toàn từ Moodle DB và ngữ cảnh bộ lọc active.
 *
 * Nguyên tắc hiển thị theo bộ lọc (Phương án 2):
 * - Khi lọc theo 1 Bậc mục tiêu cụ thể (Tier 3, VD SV_C3_1_N1 / SV_C3_1_N2): hiển thị đúng 1 bậc đang lọc.
 * - Khi lọc theo Năng lực cha (Tier 2), Miền (Tier 1), Khung (students/faculty), hoặc không lọc:
 *   gom toàn bộ các bậc distinct của khóa học trong phạm vi bộ lọc (VD Level N1 · N2, Basic · Intermediate).
 */
function theme_accees_lms_theme_get_course_cbe_metadata($courseid, $competency_filter = null, $shortname = '', $framework_filter = '') {
    global $DB;

    $curlang = current_language();
    $isvi = ($curlang === 'vi');
    $isfr = ($curlang === 'fr');

    $domain_pill = '';
    $target_code = '';
    $resolved_comp_title = '';
    $pass_scores = ['8.0', '7.5', '8.5', '7.0', '8.0'];
    $pscore = $pass_scores[((int)$courseid) % count($pass_scores)];
    $passing_criteria = $isvi
        ? "Quiz Cuối Khóa: Đạt từ {$pscore}/10"
        : ($isfr ? "Quiz final : Note requise {$pscore}/10" : "Final Quiz: Grade to pass {$pscore}/10");

    $comps = [];
    try {
        $sql = "SELECT comp.id,
                       comp.idnumber,
                       comp.shortname,
                       comp.parentid,
                       comp.path,
                       comp.competencyframeworkid,
                       fw.idnumber AS fw_idnumber,
                       parent.idnumber AS parent_idnumber,
                       parent.shortname AS parent_shortname
                  FROM {competency_coursecomp} cc
                  JOIN {competency} comp ON comp.id = cc.competencyid
                  JOIN {competency_framework} fw ON fw.id = comp.competencyframeworkid
                  LEFT JOIN {competency} parent ON parent.id = comp.parentid
                 WHERE cc.courseid = :courseid
              ORDER BY cc.id ASC, comp.sortorder ASC, comp.id ASC";
        $comps = $DB->get_records_sql($sql, ['courseid' => $courseid]) ?: [];
    } catch (\Throwable $e) {
        $comps = [];
    }

    // Helper closure to resolve a competency record's Tier 1 root domain pill cleanly from Moodle DB.
    $resolve_domain_from_row = function($row) use ($DB, $curlang) {
        if (empty($row)) {
            return '';
        }
        $root_comp = null;
        if (empty((int)$row->parentid)) {
            $root_comp = $row;
        } else if (!empty($row->path)) {
            $id_parts = array_values(array_filter(array_map('intval', explode('/', (string)$row->path)), function($id) {
                return $id > 0;
            }));
            if (!empty($id_parts)) {
                $root_id = (int)$id_parts[0];
                try {
                    $root_comp = $DB->get_record('competency', ['id' => $root_id], 'id, idnumber, shortname');
                } catch (\Throwable $e) {
                    $root_comp = null;
                }
            }
        }
        if ($root_comp && !empty($root_comp->shortname)) {
            $raw_root = format_string($root_comp->shortname, true, ['escape' => false]);
            return theme_accees_lms_theme_format_domain_pill((string)($root_comp->idnumber ?? ''), $raw_root, $curlang);
        }
        return '';
    };

    // Helper closure to classify whether a competency row belongs to 'faculty' or 'students'.
    $is_faculty_row = function($row): bool {
        $fw_idnum = strtoupper((string)($row->fw_idnumber ?? ''));
        $idnum = strtoupper((string)($row->idnumber ?? ''));
        return (strpos($fw_idnum, 'FACULTY') !== false || strpos($idnum, 'GV_') === 0);
    };

    // Helper closure to extract human-readable parent competency title from DB row.
    $resolve_title_from_row = function($row): string {
        if (empty($row)) {
            return '';
        }
        if (!empty($row->parent_shortname)) {
            return html_entity_decode(strip_tags(format_string($row->parent_shortname, true, ['escape' => false])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        if (!empty($row->shortname)) {
            return html_entity_decode(strip_tags(format_string($row->shortname, true, ['escape' => false])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return '';
    };

    try {
        $grade_item = $DB->get_record_sql("
            SELECT gradepass
              FROM {grade_items}
             WHERE courseid = :courseid
               AND itemmodule = 'quiz'
               AND gradepass > 0
          ORDER BY id ASC", ['courseid' => $courseid], IGNORE_MULTIPLE);
        if ($grade_item && $grade_item->gradepass > 0) {
            $gp = number_format($grade_item->gradepass, 1);
            $passing_criteria = $isvi
                ? "Quiz Cuối Khóa: Đạt từ {$gp}/10"
                : ($isfr ? "Quiz final : Note requise {$gp}/10" : "Final Quiz: Grade to pass {$gp}/10");
        }
    } catch (\Throwable $e) {}

    $level_infos = [];
    $chosen = null;

    if ($competency_filter && !empty($competency_filter['competency_code'])) {
        $target_filter_code = (string)$competency_filter['competency_code'];
        $filter_tier = (int)($competency_filter['tier'] ?? 3);
        $filter_comp_id = (int)($competency_filter['competency_id'] ?? 0);

        // Verify strictly against the course's actual {competency_coursecomp} rows so unmapped courses never inherit fake badges.
        $scoped_comps = [];
        foreach ($comps as $row) {
            $matches = false;
            if ($filter_tier === 3) {
                $matches = ($filter_comp_id > 0 && (int)$row->id === $filter_comp_id)
                    || ($target_filter_code !== '' && strcasecmp((string)$row->idnumber, $target_filter_code) === 0);
            } else if ($filter_tier === 2) {
                $matches = ($filter_comp_id > 0 && ((int)$row->parentid === $filter_comp_id || (int)$row->id === $filter_comp_id))
                    || ($target_filter_code !== '' && (strcasecmp((string)$row->parent_idnumber, $target_filter_code) === 0 || strcasecmp((string)$row->idnumber, $target_filter_code) === 0));
            } else {
                $norm_path = '/' . trim((string)$row->path, '/') . '/';
                $matches = ($filter_comp_id > 0 && ((int)$row->id === $filter_comp_id || (int)$row->parentid === $filter_comp_id || strpos($norm_path, '/' . $filter_comp_id . '/') !== false))
                    || ($target_filter_code !== '' && strcasecmp((string)$row->idnumber, $target_filter_code) === 0);
            }
            if ($matches) {
                $scoped_comps[] = $row;
            }
        }

        if (!empty($scoped_comps)) {
            $chosen = reset($scoped_comps);
            $target_code = ($filter_tier === 3) ? (string)$chosen->idnumber : $target_filter_code;

            if (!empty($competency_filter['domain_code']) || !empty($competency_filter['domain_name'])) {
                $domain_pill = theme_accees_lms_theme_format_domain_pill(
                    (string)($competency_filter['domain_code'] ?? ''),
                    (string)($competency_filter['domain_name'] ?? ''),
                    $curlang
                );
            }
            if ($domain_pill === '') {
                $domain_pill = $resolve_domain_from_row($chosen);
            }

            if ($filter_tier === 3) {
                // Filtering by a specific Target Level (Tier 3): display ONLY that matched level.
                $parsed_filter_lvl = theme_accees_lms_theme_parse_level_info(
                    (string)$chosen->idnumber,
                    (string)$chosen->shortname,
                    $curlang
                );
                if ($parsed_filter_lvl !== null) {
                    $level_infos = [$parsed_filter_lvl];
                }
                if (!empty($competency_filter['parent_name'])) {
                    $resolved_comp_title = trim((string)$competency_filter['parent_name']);
                } else {
                    $resolved_comp_title = $resolve_title_from_row($chosen);
                }
            } else {
                // Filtering by Tier 2 Parent Competency or Tier 1 Domain: aggregate all matching course levels in subtree.
                foreach ($scoped_comps as $row) {
                    $parsed = theme_accees_lms_theme_parse_level_info((string)$row->idnumber, (string)$row->shortname, $curlang);
                    if ($parsed !== null) {
                        $level_infos[] = $parsed;
                    }
                }
                if ($filter_tier === 2 && !empty($competency_filter['parent_name'])) {
                    $resolved_comp_title = trim((string)$competency_filter['parent_name']);
                } else {
                    $resolved_comp_title = $resolve_title_from_row($chosen);
                }
            }
        }
    } else if (!empty($comps)) {
        // No specific competency filter: scope strictly by active framework when explicitly requested ('students'|'faculty'),
        // or show mapped competencies cleanly when no framework filter is set (''). Never fall back across frameworks.
        $scoped_comps = [];
        if ($framework_filter === 'faculty') {
            foreach ($comps as $row) {
                if ($is_faculty_row($row)) {
                    $scoped_comps[] = $row;
                }
            }
        } else if ($framework_filter === 'students') {
            foreach ($comps as $row) {
                if (!$is_faculty_row($row)) {
                    $scoped_comps[] = $row;
                }
            }
        } else {
            foreach ($comps as $row) {
                if (!$is_faculty_row($row)) {
                    $scoped_comps[] = $row;
                }
            }
            if (empty($scoped_comps)) {
                $scoped_comps = array_values($comps);
            }
        }

        if (!empty($scoped_comps)) {
            $chosen = reset($scoped_comps);
            $target_code = (string)$chosen->idnumber;
            $domain_pill = $resolve_domain_from_row($chosen);
            $resolved_comp_title = $resolve_title_from_row($chosen);

            foreach ($scoped_comps as $row) {
                $parsed = theme_accees_lms_theme_parse_level_info((string)$row->idnumber, (string)$row->shortname, $curlang);
                if ($parsed !== null) {
                    $level_infos[] = $parsed;
                }
            }
        }
    }

    $level_formatted = theme_accees_lms_theme_format_levels_badge($level_infos, $curlang);

    $competency_tooltip = '';
    if ($resolved_comp_title !== '' && $level_formatted['level_tooltip'] !== '') {
        $competency_tooltip = "{$resolved_comp_title} ({$level_formatted['level_tooltip']})";
    } else if ($resolved_comp_title !== '') {
        $competency_tooltip = $resolved_comp_title;
    }

    return [
        'domain_pill' => $domain_pill,
        'target_competency_code' => $target_code,
        'competency_level' => $level_formatted['level'],
        'competency_level_name' => $level_formatted['level_name'],
        'competency_level_tooltip' => $level_formatted['level_tooltip'],
        'competency_title' => $resolved_comp_title,
        'competency_tooltip' => $competency_tooltip,
        'cbe_badge_label' => $level_formatted['level_name'],
        'passing_criteria' => $passing_criteria,
    ];
}

/**
 * Trích xuất thông tin Trường tùy chỉnh Moodle Custom Fields API (Nhóm 3)
 */
function theme_accees_lms_theme_get_course_custom_fields($courseid, $shortname = '') {
    global $DB;
    $curlang = current_language();
    $isvi = ($curlang === 'vi');
    $isfr = ($curlang === 'fr');

    $idx = (int)$courseid;
    $statuses = $isvi
        ? ['Đang mở ghi danh', 'Đang mở ghi danh', 'Sắp khai giảng', 'Đang diễn ra']
        : ($isfr ? ['Inscription ouverte', 'Inscription ouverte', 'Bientôt disponible', 'En cours'] : ['Open for Enrollment', 'Open for Enrollment', 'Upcoming', 'Ongoing']);
    $modes = $isvi
        ? ['Trực tuyến', 'Kết hợp (Blended)', 'Tự học có hướng dẫn', 'Trực tuyến']
        : ($isfr ? ['En ligne', 'Hybride (Blended)', 'Auto-formation guidée', 'En ligne'] : ['Online', 'Blended Learning', 'Self-paced', 'Online']);

    $duration_map = [
        'NLS-01-IMS' => $isvi ? '3 Tín chỉ (60 giờ)' : '3 Credits · 60 Hours',
        'NLS-01-OER' => $isvi ? '2 Tín chỉ (45 giờ)' : '2 Credits · 45 Hours',
        'NLS-01-H5P' => $isvi ? '3 Tín chỉ (60 giờ)' : '3 Credits · 60 Hours',
        'NLS-01-VIDEO' => $isvi ? '2 Tín chỉ (30 giờ)' : '2 Credits · 30 Hours',
        'NLS-01-PODCAST' => $isvi ? '2 Tín chỉ (30 giờ)' : '2 Credits · 30 Hours',
        'NLS-01-ACC' => $isvi ? '3 Tín chỉ (60 giờ)' : '3 Credits · 60 Hours',
        'NLS-02-DATA' => $isvi ? '3 Tín chỉ (60 giờ)' : '3 Credits · 60 Hours',
        'NLS-02-LOGS' => $isvi ? '2 Tín chỉ (45 giờ)' : '2 Credits · 45 Hours',
        'NLS-02-DASH' => $isvi ? '3 Tín chỉ (60 giờ)' : '3 Credits · 60 Hours',
        'NLS-02-INTERV' => $isvi ? '2 Tín chỉ (45 giờ)' : '2 Credits · 45 Hours',
        'NLS-03-AI' => $isvi ? '3 Tín chỉ (60 giờ)' : '3 Credits · 60 Hours',
        'NLS-04-SEC' => $isvi ? '2 Tín chỉ (45 giờ)' : '2 Credits · 45 Hours',
    ];
    $standard_duration = $duration_map[$shortname] ?? ($isvi ? '3 Tín chỉ (60 giờ)' : '3 Credits · 60 Hours');

    $institutions = $isvi
        ? ['ĐH Cần Thơ', 'Khoa CNTT&TT - CTU', 'Trường Bách Khoa - CTU', 'DA ACCEES', 'ĐH Cần Thơ']
        : ($isfr ? ['Univ. Can Tho', 'Faculté TIC - CTU', 'Polytech CTU', 'Consortium ACCEES', 'Univ. Can Tho'] : ['Can Tho Univ.', 'College of ICT - CTU', 'Engineering - CTU', 'ACCEES Project', 'Can Tho Univ.']);

    $defaults = [
        'course_status' => $statuses[$idx % count($statuses)],
        'delivery_mode' => $modes[$idx % count($modes)],
        'duration' => $standard_duration,
        'institution' => $institutions[$idx % count($institutions)],
        'is_featured' => ($idx % 3 !== 0),
    ];

    try {
        $sql = "SELECT f.shortname, d.value, d.charvalue, d.intvalue
                  FROM {customfield_data} d
                  JOIN {customfield_field} f ON f.id = d.fieldid
                 WHERE d.instanceid = :courseid";
        $records = $DB->get_records_sql($sql, ['courseid' => $courseid]);
        foreach ($records as $r) {
            if ($r->shortname === 'accees_course_status' && !empty($r->charvalue)) {
                $defaults['course_status'] = $r->charvalue;
            } else if ($r->shortname === 'accees_delivery_mode' && !empty($r->charvalue)) {
                $defaults['delivery_mode'] = $r->charvalue;
            } else if ($r->shortname === 'accees_duration' && !empty($r->charvalue)) {
                $defaults['duration'] = $r->charvalue;
            } else if ($r->shortname === 'accees_institution' && !empty($r->charvalue)) {
                $defaults['institution'] = $r->charvalue;
            } else if ($r->shortname === 'accees_is_featured') {
                $defaults['is_featured'] = !empty($r->intvalue);
            }
        }
    } catch (\Throwable $e) {}

    return $defaults;
}


// ==============================================================================
// BACKWARD & COMPONENT COMPATIBILITY ALIASES (theme_meko <-> theme_accees_lms_theme)
// Allows templates and layouts to run seamlessly under both 'meko' and 'accees_lms_theme'
// ==============================================================================
if (!function_exists('theme_accees_lms_theme_get_common_context')) {
    function theme_accees_lms_theme_get_common_context($theme, $output, $cfg, $site) {
        return theme_meko_get_navbar_context();
    }
}
if (!function_exists('theme_accees_lms_theme_get_main_scss_content')) {
    function theme_accees_lms_theme_get_main_scss_content($theme) {
        return theme_meko_get_main_scss_content($theme);
    }
}
if (!function_exists('theme_accees_lms_theme_get_extra_scss')) {
    function theme_accees_lms_theme_get_extra_scss($theme) {
        return theme_meko_get_extra_scss($theme);
    }
}
if (!function_exists('theme_accees_lms_theme_get_pre_scss')) {
    function theme_accees_lms_theme_get_pre_scss($theme) {
        return theme_meko_get_pre_scss($theme);
    }
}
if (!function_exists('theme_meko_prepare_course_card')) {
    function theme_meko_prepare_course_card($c, $competency_filter = null, $cat_name = '', $framework_filter = '') {
        return theme_accees_lms_theme_prepare_course_card($c, $competency_filter, $cat_name, $framework_filter);
    }
}
if (!function_exists('theme_meko_get_brand_profile')) {
    function theme_meko_get_brand_profile() {
        return theme_accees_lms_theme_get_brand_profile();
    }
}
if (!function_exists('theme_meko_get_course_cbe_metadata')) {
    function theme_meko_get_course_cbe_metadata($courseid, $competency_filter = null, $shortname = '', $framework_filter = '') {
        return theme_accees_lms_theme_get_course_cbe_metadata($courseid, $competency_filter, $shortname, $framework_filter);
    }
}

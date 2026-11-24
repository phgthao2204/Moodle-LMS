<?php
// This file is part of Moodle - http://moodle.org/
//
// MEKOSOFT LMS digital competencies framework page.

defined('MOODLE_INTERNAL') || die();

global $CFG, $OUTPUT, $USER;

$competenciesurl = (new moodle_url('/theme/meko/competencies.php'))->out(false);
$frameworks = [
    'faculty' => [
        'key' => 'faculty',
        'title' => 'Faculty',
        'idnumber' => 'DIGCOMP_ACCEES_FACULTY',
        'competencyCount' => 0,
        'domains' => [],
    ],
    'students' => [
        'key' => 'students',
        'title' => 'Students',
        'idnumber' => 'DIGCOMP_ACCEES_STUDENTS',
        'competencyCount' => 0,
        'domains' => [],
    ],
];

$helperfile = $CFG->dirroot . '/local/meko_competencies/classes/helper.php';
if (!class_exists('\local_meko_competencies\helper') && is_readable($helperfile)) {
    require_once($helperfile);
}

if (class_exists('\local_meko_competencies\helper')) {
    foreach (['faculty', 'students'] as $frameworkkey) {
        $frameworkdata = \local_meko_competencies\helper::get_framework_data($frameworkkey, 'en');
        $levelgroups = ($frameworkkey === 'faculty')
            ? [
                'basic' => ['basic'],
                'intermediate' => ['intermediate'],
                'advanced' => ['advanced'],
            ]
            : [
                'basic' => ['n1'],
                'intermediate' => ['n2'],
                'advanced' => ['n3', 'n4'],
            ];
        $domains = [];

        foreach (($frameworkdata['domains'] ?? []) as $domain) {
            $competencies = [];
            foreach (($domain['competencies'] ?? []) as $competency) {
                $sourcelevels = [];
                foreach (($competency['levels'] ?? []) as $level) {
                    $sourcelevels[$level['level_key'] ?? 'default'] = $level;
                }

                $levels = [];
                foreach ($levelgroups as $levelkey => $sourcekeys) {
                    $sourcerecords = [];
                    $requirements = [];
                    $examples = [];
                    $coursescount = 0;
                    foreach ($sourcekeys as $sourcekey) {
                        if (empty($sourcelevels[$sourcekey])) {
                            continue;
                        }
                        $sourcelevel = $sourcelevels[$sourcekey];
                        $rawdescription = $sourcelevel['description'] ?? '';
                        $description = html_to_text($rawdescription);
                        $parts = preg_split('/Practical\s+Examples\s*:/i', $description, 2);
                        $rawparts = preg_split('/Practical\s+Examples\s*:/i', $rawdescription, 2);
                        $requirement = trim(preg_replace(
                            '/^Learning Outcome(?:\s*\([^)]*\))?\s*:\s*/i',
                            '',
                            trim($parts[0] ?? '')
                        ));
                        $exampleitems = [];
                        if (!empty($rawparts[1]) && preg_match_all('/<li\b[^>]*>(.*?)<\/li>/is', $rawparts[1], $listitems)) {
                            foreach ($listitems[1] as $listitem) {
                                $listitem = trim(preg_replace('/\s+/u', ' ', html_to_text($listitem)));
                                if ($listitem !== '') {
                                    $exampleitems[] = $listitem;
                                }
                            }
                        } else {
                            $example = trim($parts[1] ?? '');
                            if ($example !== '') {
                                $exampleitems[] = $example;
                            }
                        }
                        $sourcerecords[] = [
                            'idnumber' => $sourcelevel['idnumber'] ?? '',
                            'label' => $sourcelevel['level_badge'] ?? '',
                        ];
                        $coursescount += (int)($sourcelevel['courses_count'] ?? 0);
                        if ($requirement !== '') {
                            $requirements[] = (count($sourcekeys) > 1 && !empty($sourcelevel['level_badge'])
                                ? $sourcelevel['level_badge'] . ': '
                                : '') . $requirement;
                        }
                        foreach ($exampleitems as $exampleitem) {
                            $examples[] = (count($sourcekeys) > 1 && !empty($sourcelevel['level_badge'])
                                ? $sourcelevel['level_badge'] . ': '
                                : '') . trim($exampleitem);
                        }
                    }

                    $filteridnumber = (count($sourcerecords) === 1)
                        ? $sourcerecords[0]['idnumber']
                        : (string)($competency['idnumber'] ?? '');
                    $levels[$levelkey] = [
                        'key' => $levelkey,
                        'label' => ucfirst($levelkey),
                        'coursesCount' => $coursescount,
                        'sources' => $sourcerecords,
                        'requirement' => implode("\n\n", $requirements),
                        'examples' => implode("\n\n", $examples),
                        'catalogUrl' => (new moodle_url('/course/index.php', [
                            'competencyid' => $filteridnumber,
                            'framework' => $frameworkkey,
                        ]))->out(false),
                    ];
                }

                $competencies[] = [
                    'id' => (string)($competency['idnumber'] ?? ''),
                    'title' => html_to_text($competency['shortname'] ?? ''),
                    'description' => html_to_text($competency['description'] ?? ''),
                    'levels' => $levels,
                ];
            }

            $domains[] = [
                'id' => (string)($domain['idnumber'] ?? ''),
                'title' => html_to_text($domain['shortname'] ?? ''),
                'description' => html_to_text($domain['description'] ?? ''),
                'competencies' => $competencies,
            ];
        }

        $frameworks[$frameworkkey]['title'] = $frameworkdata['framework_title'] ?? $frameworks[$frameworkkey]['title'];
        $frameworks[$frameworkkey]['idnumber'] = $frameworkdata['framework_idnumber'] ?? $frameworks[$frameworkkey]['idnumber'];
        $frameworks[$frameworkkey]['domains'] = $domains;
        $frameworks[$frameworkkey]['competencyCount'] = array_sum(array_map(
            static function(array $domain): int {
                return count($domain['competencies']);
            },
            $domains
        ));
    }
}

$frameworkjson = json_encode(
    $frameworks,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
);
$context = [
    'logourl' => $OUTPUT->image_url('logo', 'theme_meko'),
    'logowhiteurl' => $OUTPUT->image_url('logo_white', 'theme_meko'),
    'homeurl' => (new moodle_url('/'))->out(false),
    'catalogurl' => (new moodle_url('/course/index.php'))->out(false),
    'mycoursesurl' => (new moodle_url('/my/courses.php'))->out(false),
    'competenciesurl' => $competenciesurl,
    'frameworkadminurl' => (new moodle_url('/admin/tool/lp/competencyframeworks.php'))->out(false),
    'loginurl' => get_login_url(),
    'isloggedin' => isloggedin() && !isguestuser(),
    'userfullname' => fullname($USER),
    'logouturl' => (new moodle_url('/login/logout.php', ['sesskey' => sesskey()]))->out(false),
    'competency_tree_json' => $frameworkjson,
    'competency_tree_js_url' => (new moodle_url('/theme/meko/js/competencies-tree-explorer.js', [
        'v' => filemtime($CFG->dirroot . '/theme/meko/js/competencies-tree-explorer.js'),
    ]))->out(false),
];
$context = array_merge(theme_meko_get_navbar_context('competencies'), $context);
echo $OUTPUT->doctype();
?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <?php echo theme_meko_polyfill_preload(); ?>
    <?php echo theme_meko_optimised_head_html(); ?>
</head>
<body <?php echo $OUTPUT->body_attributes(); ?>>
<?php
echo $OUTPUT->standard_top_of_body_html();
echo $OUTPUT->render_from_template('theme_meko/competencies_tree_explorer', $context);
?>
<div class="d-none">
    <?php echo $OUTPUT->main_content(); ?>
</div>
<?php echo $OUTPUT->standard_end_of_body_html(); ?>
</body>
</html>

<?php
// This file is part of Moodle - http://moodle.org/
//
// Plugin renderer for local_meko_competencies.
// Provides native fallback rendering regardless of active theme.

namespace local_meko_competencies\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;
use moodle_url;

class renderer extends plugin_renderer_base {

    /**
     * Render Cây Năng Lực Số 3 tầng (Domain > Competency > Target Level)
     *
     * @param array $data
     * @return string HTML
     */
    public function render_competency_tree(array $data): string {
        global $CFG;

        $frameworkkey = $data['framework_key'] ?? (!empty($data['is_faculty']) ? 'faculty' : 'students');
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

        foreach (($data['domains'] ?? []) as $domain) {
            $competencies = [];
            foreach (($domain['competencies'] ?? []) as $competency) {
                $sourcelevels = [];
                foreach (($competency['levels'] ?? []) as $level) {
                    $sourcelevels[$level['level_key'] ?? 'default'] = $level;
                }

                $levels = [];
                foreach ($levelgroups as $levelkey => $sourcekeys) {
                    $sources = [];
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

                        $sources[] = [
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
                                : '') . $exampleitem;
                        }
                    }

                    $filteridnumber = (count($sources) === 1)
                        ? $sources[0]['idnumber']
                        : (string)($competency['idnumber'] ?? '');
                    $levels[$levelkey] = [
                        'key' => $levelkey,
                        'label' => ucfirst($levelkey),
                        'coursesCount' => $coursescount,
                        'requirement' => implode("\n\n", $requirements),
                        'examples' => implode("\n", $examples),
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

        $frameworks = [
            $frameworkkey => [
                'key' => $frameworkkey,
                'title' => $data['framework_title'] ?? '',
                'idnumber' => $data['framework_idnumber'] ?? '',
                'competencyCount' => (int)($data['competencies_count'] ?? 0),
                'domains' => $domains,
            ],
        ];
        $data['competency_tree_json'] = json_encode(
            $frameworks,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
        );
        $themename = $this->page->theme->name ?? 'meko';
        $scriptpath = $CFG->dirroot . '/theme/' . $themename . '/js/competencies-tree-explorer.js';
        $data['competency_tree_js_url'] = (new moodle_url(
            '/theme/' . $themename . '/js/competencies-tree-explorer.js',
            ['v' => filemtime($scriptpath)]
        ))->out(false);

        return $this->render_from_template('local_meko_competencies/competency_tree_explorer', $data);
    }

    /**
     * Render Ma Trận Rubric đối chiếu ngang 4 Bậc N1..N4
     *
     * @param array $data
     * @return string HTML
     */
    public function render_matrix_view(array $data): string {
        return $this->render_from_template('local_meko_competencies/competency_matrix', $data);
    }
}

<?php
// This file is part of Moodle - http://moodle.org/
//
// MEKOSOFT LMS digital competencies framework page.

require('../../config.php');

$PAGE->set_url(new moodle_url('/theme/meko/competencies.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('competencies');
$PAGE->set_title(get_string('competenciespage', 'theme_meko'));
$PAGE->set_heading(get_string('competenciespage', 'theme_meko'));

echo $OUTPUT->header();
echo $OUTPUT->footer();

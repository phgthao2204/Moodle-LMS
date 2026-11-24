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
 * Frontpage layout for theme_accees_lms_theme.
 *
 * @package    theme_accees_lms_theme
 * @copyright  2026 Mekosoft / Can Tho University (CTU)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $OUTPUT, $PAGE, $USER, $CFG, $SITE;

require_once(__DIR__ . '/../lib.php');

$themename = !empty($PAGE->theme->name) ? $PAGE->theme->name : 'meko';
$theme = $PAGE->theme ?? theme_config::load($themename);

$commoncontext = theme_accees_lms_theme_get_common_context($theme, $OUTPUT, $CFG, $SITE);
$coursescontext = theme_accees_lms_theme_get_frontpage_courses($theme);
$competenciescontext = theme_accees_lms_theme_get_frontpage_competencies();

$templatecontext = array_merge($commoncontext, $coursescontext, $competenciescontext);

$favicourl = $CFG->wwwroot . '/theme/' . $themename . '/pix/favicon.ico?v=20261026';
$favpngurl = $CFG->wwwroot . '/theme/' . $themename . '/pix/favicon.png?v=20261026';

echo $OUTPUT->doctype();
?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <link rel="icon" type="image/x-icon" href="<?php echo $favicourl; ?>" />
    <link rel="shortcut icon" type="image/x-icon" href="<?php echo $favicourl; ?>" />
    <link rel="apple-touch-icon" href="<?php echo $favpngurl; ?>" />
    <?php echo $OUTPUT->standard_head_html(); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body <?php echo $OUTPUT->body_attributes(); ?>>
<?php echo $OUTPUT->standard_top_of_body_html(); ?>

<div id="page-wrapper">
    <!-- Clean Top Navbar -->
    <?php echo $OUTPUT->render_from_template('theme_' . $themename . '/navbar', $templatecontext); ?>

    <!-- Main Frontpage Content -->
    <div id="page" class="container-fluid p-0" style="margin-top: 60px;">
        <?php echo $OUTPUT->render_from_template('theme_' . $themename . '/frontpage', $templatecontext); ?>
    </div>

    <!-- LMS-Specific Footer -->
    <?php echo $OUTPUT->render_from_template('theme_' . $themename . '/footer', $templatecontext); ?>
</div>

<?php echo $OUTPUT->standard_end_of_body_html(); ?>
</body>
</html>

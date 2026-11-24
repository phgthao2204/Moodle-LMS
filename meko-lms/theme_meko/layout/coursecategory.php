<?php
// This file is part of Moodle - http://moodle.org/
//
// MEKOSOFT LMS - Course catalogue page layout.

defined('MOODLE_INTERNAL') || die();

global $OUTPUT, $PAGE, $CFG, $SITE;

require_once(__DIR__ . '/../lib.php');

$themename = !empty($PAGE->theme->name) ? $PAGE->theme->name : 'meko';
$theme = $PAGE->theme ?? theme_config::load($themename);
$commoncontext = theme_accees_lms_theme_get_common_context($theme, $OUTPUT, $CFG, $SITE);

echo $OUTPUT->doctype();
?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <?php echo $OUTPUT->standard_head_html(); ?>
</head>
<body <?php echo $OUTPUT->body_attributes(); ?>>
<?php
echo $OUTPUT->standard_top_of_body_html();
?>
<div id="page-wrapper">
    <?php echo $OUTPUT->render_from_template('theme_' . $themename . '/navbar', $commoncontext); ?>
    <div id="page" class="container-fluid p-0" style="margin-top: 60px;">
    <?php echo $OUTPUT->main_content(); ?>
    </div>
    <?php echo $OUTPUT->render_from_template('theme_' . $themename . '/footer', $commoncontext); ?>
</div>
<?php
echo $OUTPUT->standard_end_of_body_html();
?>
</body>
</html>

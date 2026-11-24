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
 * Clean & Streamlined Direct Login layout for theme_accees_lms_theme (UC-LMS-01).
 *
 * Tinh gọn toàn diện trang Đăng nhập theo yêu cầu người dùng:
 * - Tắt bỏ hoàn toàn các khối SSO / Keycloak chưa kích hoạt.
 * - Đưa form Đăng nhập Moodle trực tiếp lên vị trí trung tâm, trực quan, dễ thao tác.
 * - Bổ sung background học thuật sang trọng, độ tương phản cao.
 *
 * @package    theme_accees_lms_theme
 * @copyright  2026 Mekosoft / Can Tho University (CTU)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

global $OUTPUT, $PAGE, $CFG, $SITE;

$themename = !empty($PAGE->theme->name) ? $PAGE->theme->name : 'meko';
$theme = $PAGE->theme ?? theme_config::load($themename);
$commoncontext = theme_accees_lms_theme_get_common_context($theme, $OUTPUT, $CFG, $SITE);
$brand = theme_accees_lms_theme_get_brand_profile($theme);
$currentlang = current_language();
$isvi = ($currentlang === 'vi');
$isfr = ($currentlang === 'fr');

$bgurl = $OUTPUT->image_url('login_bg', 'theme_' . $themename);

$ui = [
    'brand_badge' => $brand['brand_badge_text'],
    'project_title' => $brand['login_project_title'],
    'back_home' => $isvi ? 'Quay lại Trang chủ' : ($isfr ? 'Retour à l\'accueil' : 'Back to Home'),
    'login_title' => $brand['login_title'],
    'login_subtitle' => $isvi
        ? 'Nhập thông tin tài khoản để truy cập hệ thống học tập'
        : ($isfr
            ? 'Entrez vos identifiants pour accéder à la plateforme'
            : 'Enter your credentials to access the learning platform'),
    'support_text' => $isvi
        ? 'Hỗ trợ kỹ thuật: <a href="mailto:' . $brand['support_email'] . '">' . $brand['support_email'] . '</a>'
        : ($isfr
            ? 'Support technique : <a href="mailto:' . $brand['support_email'] . '">' . $brand['support_email'] . '</a>'
            : 'Technical support: <a href="mailto:' . $brand['support_email'] . '">' . $brand['support_email'] . '</a>'),
];

echo $OUTPUT->doctype();
?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <?php echo $OUTPUT->standard_head_html(); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        html {
            height: 100% !important;
            overflow: hidden !important;
        }
        body.accees-clean-login-page {
            background-color: #081028 !important;
            background-image: 
                radial-gradient(circle at 50% 12%, rgba(0, 0, 182, 0.28) 0%, transparent 60%),
                linear-gradient(to right, rgba(255, 255, 255, 0.035) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.035) 1px, transparent 1px) !important;
            background-size: 100% 100%, 32px 32px, 32px 32px !important;
            background-position: center top, center center, center center !important;
            height: 100vh !important;
            height: 100dvh !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
        }
        .accees-login-shell {
            height: 100vh !important;
            height: 100dvh !important;
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden !important;
        }
    </style>
</head>
<body <?php echo $OUTPUT->body_attributes(['pagelayout-login', 'accees-clean-login-page']); ?>>
<?php echo $OUTPUT->standard_top_of_body_html(); ?>

<div id="page-wrapper" class="accees-login-shell">

    <!-- 1. Top Navigation Strip -->
    <div class="top-strip">
        <a href="<?php echo $CFG->wwwroot; ?>/" class="login-back-btn" id="login-back-btn">
            <i class="fa-solid fa-arrow-left me-1"></i> <?php echo $ui['back_home']; ?>
        </a>
        <div class="project-tag d-none d-md-flex align-items-center">
            <span class="badge-meko"><?php echo $ui['brand_badge']; ?></span>
            <span><?php echo $ui['project_title']; ?></span>
        </div>
        <?php if (!empty($CFG->langmenu)): ?>
        <div class="lang-switcher">
            <a href="<?php echo new moodle_url($PAGE->url, ['lang' => 'vi']); ?>" class="lang-btn <?php echo ($currentlang === 'vi') ? 'active' : ''; ?>" title="Tiếng Việt">VN</a>
            <a href="<?php echo new moodle_url($PAGE->url, ['lang' => 'en']); ?>" class="lang-btn <?php echo ($currentlang === 'en') ? 'active' : ''; ?>" title="English">EN</a>
            <a href="<?php echo new moodle_url($PAGE->url, ['lang' => 'fr']); ?>" class="lang-btn <?php echo ($currentlang === 'fr') ? 'active' : ''; ?>" title="Français">FR</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- 2. Main Login Card Area -->
    <div class="login-wrapper">
        <div class="login-card-container">

            <!-- Card Header -->
            <div class="login-header">
                <a href="<?php echo $CFG->wwwroot; ?>/" class="brand-logo-link" title="ACCEES LMS">
                    <img src="<?php echo s($commoncontext['logo_url']); ?>" alt="ACCEES LMS" class="brand-logo-img" style="height:34px;width:auto;">
                    <span class="brand-badge">LMS</span>
                </a>
                <h1 class="login-heading"><?php echo $ui['login_title']; ?></h1>
                <p class="login-subheading"><?php echo $ui['login_subtitle']; ?></p>
            </div>

            <!-- Card Body: Direct Native Moodle Login Form (0% SSO Clutter) -->
            <div class="login-body">
                <?php echo $OUTPUT->main_content(); ?>
            </div>

            <!-- Card Footer -->
            <div class="login-footer">
                <p class="support-info mb-1"><?php echo $ui['support_text']; ?></p>
                <p class="copyright mb-0">
                    <?php echo get_string('footer_copyright', 'theme_' . $themename); ?>
                </p>
            </div>

        </div>
    </div>

</div>

<?php echo $OUTPUT->standard_end_of_body_html(); ?>
</body>
</html>

<?php
// This file is part of Moodle - http://moodle.org/

/**
 * 1-Click Seed Data CLI for Local Dev Environment.
 * Creates 12 standard MEKO courses with 16:9 images, categories, test accounts, enrolments,
 * activates theme_meko and enables Theme Designer Mode.
 *
 * Usage inside Moodle container:
 * php /path/to/theme/meko/cli/seed_dev_data.php
 *
 * @package   theme_meko
 * @copyright 2026 Mekosoft
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

// Safely locate config.php
$config_candidates = [
    __DIR__ . '/../../../../config.php',
    __DIR__ . '/../../../config.php',
    '/var/www/html/config.php',
    '/var/www/moodle/public/config.php',
];
$found_config = false;
foreach ($config_candidates as $candidate) {
    if (file_exists($candidate)) {
        require_once($candidate);
        $found_config = true;
        break;
    }
}
if (!$found_config) {
    echo "[ERROR] Cannot find Moodle config.php!\n";
    exit(1);
}

require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->libdir . '/enrollib.php');

global $DB, $CFG;

echo "======================================================================\n";
echo "  MEKOSOFT LMS - 1-Click Local Data Generator\n";
echo "======================================================================\n";

// 1. Activate theme_meko & Theme Designer Mode (No CSS cache during dev)
echo "[1/4] Activating theme_meko & Theme Designer Mode...\n";
set_config('theme', 'meko');
set_config('themedesignermode', 1);
set_config('supportname', 'Ban Quan Tri MEKOSOFT');
set_config('supportemail', 'support@mekosoft.vn');
set_config('supportpage', 'http://localhost:8088');

// Ensure Site Home settings are fully configured (avoids "Installation: New settings" prompt)
$sitecourse = $DB->get_record('course', ['id' => 1]);
if ($sitecourse) {
    $sitecourse->fullname = 'MEKOSOFT LMS - Digital Learning & Competencies';
    $sitecourse->shortname = 'MEKO-LMS';
    $sitecourse->summary = 'He thong Quan ly Hoc tap va Danh gia Khung Nang luc So MEKOSOFT LMS';
    $DB->update_record('course', $sitecourse);
}
echo "  -> Theme set to 'meko' with Theme Designer Mode ON and Site Settings configured\n";

// 2. Create Categories & Courses
echo "[2/4] Creating 12 standard MEKO courses with 16:9 covers...\n";
$categories = [
    'cat-nls-01' => [
        'name' => 'Kỹ năng số cơ bản (NLS-01)',
        'courses' => [
            [
                'fullname' => 'Kỹ thuật đóng gói học liệu IMS CP và nhúng DLRP',
                'shortname' => 'NLS-01-IMS',
                'summary' => 'Hướng dẫn chuẩn hóa cấu trúc bài giảng IMS Content Package và tích hợp kho học liệu số tập trung DLRP.',
                'image' => 'nls01ims.jpg',
            ],
            [
                'fullname' => 'Biên tập và xuất bản tài nguyên giáo dục mở OER đa phương tiện',
                'shortname' => 'NLS-01-OER',
                'summary' => 'Quy trình khai thác, cấp phép Creative Commons và xuất bản tài nguyên giáo dục mở.',
                'image' => 'nls01oer.jpg',
            ],
            [
                'fullname' => 'Thiết kế hoạt động tương tác nâng cao với H5P trên Moodle',
                'shortname' => 'NLS-01-H5P',
                'summary' => 'Xây dựng bài giảng tương tác video, flashcard, branching scenario và bài tập kéo thả đa dạng.',
                'image' => 'nls01h5p.jpg',
            ],
            [
                'fullname' => 'Sản xuất bài giảng Micro-learning và Video tương tác số',
                'shortname' => 'NLS-01-VIDEO',
                'summary' => 'Kỹ thuật quay dựng, biên tập clip học tập ngắn và nhúng câu hỏi kiểm tra trực tiếp trên video.',
                'image' => 'nls01video.jpg',
            ],
            [
                'fullname' => 'Xây dựng kênh Podcast giáo dục và học liệu âm thanh số',
                'shortname' => 'NLS-01-PODCAST',
                'summary' => 'Biên tập âm thanh chuyên nghiệp, xây dựng chuỗi bài giảng phát thanh số cho người học di động.',
                'image' => 'nls01podcast.jpg',
            ],
            [
                'fullname' => 'Thiết kế học liệu số tiếp cận phổ quát (Accessibility in EdTech)',
                'shortname' => 'NLS-01-ACC',
                'summary' => 'Tuân thủ tiêu chuẩn tiếp cận WCAG 2.1, thiết kế nội dung hòa nhập cho mọi đối tượng người học.',
                'image' => 'nls01acc.jpg',
            ],
        ],
    ],
    'cat-nls-02' => [
        'name' => 'Phân tích dữ liệu giáo dục (NLS-02)',
        'courses' => [
            [
                'fullname' => 'Phân tích dữ liệu học tập và dự báo kết quả đào tạo',
                'shortname' => 'NLS-02-DATA',
                'summary' => 'Ứng dụng thống kê học tập Learning Analytics để đánh giá mức độ tương tác và dự báo điểm số.',
                'image' => 'nls02data.jpg',
            ],
            [
                'fullname' => 'Khai thác Moodle Event Logs và xAPI trong giáo dục đại học',
                'shortname' => 'NLS-02-LOGS',
                'summary' => 'Trích xuất log sự kiện Moodle và tích hợp chuẩn xAPI/LRS để theo dõi hành vi học viên.',
                'image' => 'nls02logs.jpg',
            ],
            [
                'fullname' => 'Xây dựng Dashboard trực quan hóa dữ liệu học tập với PowerBI',
                'shortname' => 'NLS-02-DASH',
                'summary' => 'Kết nối CSDL Moodle với PowerBI để tạo bảng điều khiển theo dõi tiến độ đào tạo thời gian thực.',
                'image' => 'nls02dash.jpg',
            ],
            [
                'fullname' => 'Hệ thống cảnh báo sớm sinh viên có nguy cơ bỏ cuộc (Early Warning System)',
                'shortname' => 'NLS-02-INTERV',
                'summary' => 'Thiết lập thuật toán và quy trình cảnh báo sớm can thiệp hỗ trợ sinh viên học chậm.',
                'image' => 'nls02interv.jpg',
            ],
        ],
    ],
    'cat-nls-03' => [
        'name' => 'Trí tuệ nhân tạo & Đổi mới sáng tạo (NLS-03)',
        'courses' => [
            [
                'fullname' => 'Ứng dụng Generative AI trong thiết kế câu hỏi trắc nghiệm và đánh giá tự động',
                'shortname' => 'NLS-03-AI',
                'summary' => 'Sử dụng mô hình ngôn ngữ lớn để soạn thảo ngân hàng câu hỏi, rubric chấm điểm và phản hồi tự động.',
                'image' => 'nls03ai.jpg',
            ],
        ],
    ],
    'cat-nls-04' => [
        'name' => 'An toàn thông tin & Quản trị số (NLS-04)',
        'courses' => [
            [
                'fullname' => 'An toàn thông tin, bảo vệ dữ liệu cá nhân và phòng chống tấn công số trong GDĐH',
                'shortname' => 'NLS-04-SEC',
                'summary' => 'Nguyên tắc bảo vệ dữ liệu sinh viên, phòng ngừa lừa đảo mạng và bảo mật tài khoản LMS.',
                'image' => 'nls04sec.jpg',
            ],
        ],
    ],
];

$fs = get_file_storage();
$filesystem = $fs->get_file_system();
$pix_dir = __DIR__ . '/../pix/courses';

foreach ($categories as $catidnumber => $catinfo) {
    $cat = $DB->get_record('course_categories', ['idnumber' => $catidnumber]);
    if (!$cat) {
        $cat = core_course_category::create([
            'name' => $catinfo['name'],
            'idnumber' => $catidnumber,
            'description' => $catinfo['name'],
            'descriptionformat' => FORMAT_HTML,
        ]);
        echo "  [+] Danh mục: {$catinfo['name']}\n";
    }

    foreach ($catinfo['courses'] as $cdata) {
        $course = $DB->get_record('course', ['shortname' => $cdata['shortname']]);
        if (!$course) {
            $course = create_course((object) [
                'category' => $cat->id,
                'fullname' => $cdata['fullname'],
                'shortname' => $cdata['shortname'],
                'summary' => '<p>' . s($cdata['summary']) . '</p>',
                'summaryformat' => FORMAT_HTML,
                'format' => 'topics',
                'visible' => 1,
                'startdate' => time(),
                'enddate' => 0,
                'numsections' => 4,
            ]);
            echo "  [+] Khóa học: {$cdata['shortname']} - {$cdata['fullname']}\n";
        }

        $img_path = $pix_dir . '/' . $cdata['image'];
        if (!is_readable($img_path)) {
            continue;
        }

        $context = context_course::instance($course->id);
        $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0, 'id', false);
        if (!$files) {
            $fs->create_file_from_pathname([
                'contextid' => $context->id,
                'component' => 'course',
                'filearea' => 'overviewfiles',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => $cdata['image'],
            ], $img_path);
            continue;
        }

        $contenthash = sha1_file($img_path);
        foreach ($files as $file) {
            if ($file->get_contenthash() === $contenthash
                    && !$filesystem->is_file_readable_locally_by_storedfile($file, true)) {
                $filesystem->add_file_from_path($img_path, $contenthash);
            }
        }
    }
}

// 3. Create or Reset Test Accounts
echo "[3/4] Creating Test Accounts (admin, pqhuy, teacher1, student1)...\n";
$accounts = [
    [
        'username' => 'admin',
        'password' => 'AdminMoodle2026!@#',
        'firstname' => 'Quản trị viên',
        'lastname' => 'Mekosoft',
        'email' => 'admin@mekosoft.vn',
        'role' => 'admin',
    ],
    [
        'username' => 'pqhuy',
        'password' => 'PqHuy2026!@#',
        'firstname' => 'Quốc Huy',
        'lastname' => 'Phan',
        'email' => 'pqhuy@mekosoft.vn',
        'role' => 'manager',
    ],
    [
        'username' => 'teacher1',
        'password' => 'Teacher2026!@#',
        'firstname' => 'Văn Giảng',
        'lastname' => 'Nguyễn',
        'email' => 'teacher1@mekosoft.vn',
        'role' => 'editingteacher',
    ],
    [
        'username' => 'student1',
        'password' => 'Student2026!@#',
        'firstname' => 'Thị Học',
        'lastname' => 'Trần',
        'email' => 'student1@mekosoft.vn',
        'role' => 'student',
    ],
];

foreach ($accounts as $acc) {
    $u = $DB->get_record('user', ['username' => $acc['username']]);
    if (!$u) {
        $newuser = (object) [
            'username' => $acc['username'],
            'password' => hash_internal_user_password($acc['password']),
            'firstname' => $acc['firstname'],
            'lastname' => $acc['lastname'],
            'email' => $acc['email'],
            'auth' => 'manual',
            'confirmed' => 1,
            'mnethostid' => $CFG->mnet_localhost_id,
        ];
        $uid = user_create_user($newuser);
        $u = $DB->get_record('user', ['id' => $uid]);
        echo "  [+] Tạo tài khoản: {$acc['username']} ({$acc['password']})\n";
    } else {
        $DB->set_field('user', 'password', hash_internal_user_password($acc['password']), ['id' => $u->id]);
        echo "  [*] Cập nhật mật khẩu: {$acc['username']} -> {$acc['password']}\n";
    }

    if ($acc['role'] === 'admin') {
        $siteadmins = explode(',', get_config('core', 'siteadmins'));
        if (!in_array($u->id, $siteadmins)) {
            $siteadmins[] = $u->id;
            set_config('siteadmins', implode(',', array_filter($siteadmins)));
        }
    }
}

// 4. Enrol Teacher and Student
echo "[4/4] Enrolling Teacher1 and Student1 into demo courses...\n";
$teacher = $DB->get_record('user', ['username' => 'teacher1']);
$student = $DB->get_record('user', ['username' => 'student1']);
$teacher_role = $DB->get_record('role', ['shortname' => 'editingteacher']);
$student_role = $DB->get_record('role', ['shortname' => 'student']);

$manualplugin = enrol_get_plugin('manual');

$all_courses = $DB->get_records_select('course', 'id > 1', null, 'id ASC');
$teacher_course_codes = ['NLS-01-IMS', 'NLS-01-H5P', 'NLS-02-DATA', 'NLS-03-AI'];
$student_course_codes = ['NLS-01-IMS', 'NLS-01-OER', 'NLS-01-H5P', 'NLS-01-ACC', 'NLS-03-AI', 'NLS-04-SEC'];

foreach ($all_courses as $c) {
    $instances = enrol_get_instances($c->id, false);
    $manual_instance = null;
    foreach ($instances as $inst) {
        if ($inst->enrol === 'manual') {
            $manual_instance = $inst;
            break;
        }
    }
    if (!$manual_instance) {
        $instanceid = $manualplugin->add_instance($c);
        $manual_instance = $DB->get_record('enrol', ['id' => $instanceid]);
    }

    if (in_array($c->shortname, $teacher_course_codes) && $teacher && $teacher_role) {
        $manualplugin->enrol_user($manual_instance, $teacher->id, $teacher_role->id);
    }
    if (in_array($c->shortname, $student_course_codes) && $student && $student_role) {
        $manualplugin->enrol_user($manual_instance, $student->id, $student_role->id);
    }
}

// Purge cache
purge_all_caches();

echo "======================================================================\n";
echo "  HOÀN TẤT BƠM DỮ LIỆU & CÀI THEME CHO LOCAL DEV!\n";
echo "  Tài khoản kiểm thử:\n";
echo "    - Admin       : admin    / AdminMoodle2026!@#\n";
echo "    - Tech Lead   : pqhuy    / PqHuy2026!@#\n";
echo "    - Giảng viên : teacher1 / Teacher2026!@# (4 khóa học)\n";
echo "    - Sinh viên   : student1 / Student2026!@# (6 khóa học)\n";
echo "======================================================================\n";


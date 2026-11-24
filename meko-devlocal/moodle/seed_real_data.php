<?php
// ==============================================================================
// MEKOSOFT DEVLOCAL - 100% REAL DATA SEEDER FOR MOODLE 5.3
// Populates:
//   - Theme (theme_accees_lms_theme) + Brand Profile ('meko')
//   - 2 Competency Frameworks (DigComp 2.2 & DigCompEdu via local_accees_competencies)
//   - 5 Real Course Custom Fields (core_customfield API) + Data per course
//   - 4 Categories + 12 Courses with real startdates, 16:9 covers, & CBE mappings
//   - Real Teachers, Tech Lead, Students + Enrolments + Real Course Activities
// ==============================================================================

define('CLI_SCRIPT', true);

$config_candidates = [
    '/var/www/html/config.php',
    '/var/www/html/public/config.php',
    __DIR__ . '/../../../../config.php',
];
$loaded = false;
foreach ($config_candidates as $candidate) {
    if (file_exists($candidate)) {
        require_once($candidate);
        $loaded = true;
        break;
    }
}
if (!$loaded) {
    fwrite(STDERR, "[ERROR] Cannot locate Moodle config.php\n");
    exit(1);
}

require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/completionlib.php');

global $DB, $CFG;

\core\session\manager::set_user(get_admin());

$brandProfile = getenv('MOODLE_BRAND_PROFILE') ?: 'meko';
$domain = getenv('DOMAIN') ?: 'meko.localhost';
$wwwroot = "http://{$domain}/lms";

echo "======================================================================\n";
echo "  MEKOSOFT DEVLOCAL - MOODLE REAL DATA SEEDER\n";
echo "  Brand Profile: {$brandProfile} | URL: {$wwwroot}\n";
echo "======================================================================\n";

// ------------------------------------------------------------------------------
// [1/6] Configure Theme, Brand Profile & Site Settings
// ------------------------------------------------------------------------------
echo "[1/6] Configuring theme_meko (brand_profile={$brandProfile})...\n";
set_config('theme', 'meko');
set_config('themedesignermode', 1);
set_config('enablemycourses', 1);
set_config('enablecompetencies', 1);
set_config('enablecompletion', 1);
set_config('langmenu', 1);
set_config('brand_profile', $brandProfile, 'theme_meko');
set_config('tms_url', "http://{$domain}/tms", 'theme_meko');
set_config('sso_url', "http://{$domain}/", 'theme_meko');
set_config('tmsurl', "http://{$domain}/tms", 'theme_meko');
set_config('ssourl', "http://{$domain}/", 'theme_meko');
set_config('supportname', 'Ban Quan Tri MEKOSOFT');
set_config('supportemail', 'support@mekosoft.vn');
set_config('supportpage', $wwwroot);

$sitecourse = $DB->get_record('course', ['id' => SITEID]);
if ($sitecourse) {
    $sitecourse->fullname = 'MEKOSOFT LMS - Digital Learning & Competencies';
    $sitecourse->shortname = 'MEKO-LMS';
    $sitecourse->summary = 'He thong Quan ly Hoc tap va Danh gia Khung Nang luc So (DevLocal)';
    $DB->update_record('course', $sitecourse);
}

// ------------------------------------------------------------------------------
// [2/6] Sync Canonical Digital Competency Frameworks (DigComp 2.2 & DigCompEdu)
// ------------------------------------------------------------------------------
echo "[2/6] Syncing Digital Competency Frameworks...\n";
$syncFile = $CFG->dirroot . '/local/meko_competencies/classes/framework_sync.php';
if (file_exists($syncFile)) {
    require_once($syncFile);
    \local_meko_competencies\framework_sync::sync_all();
    echo "  -> Synced DIGCOMP_ACCEES_STUDENTS & DIGCOMP_ACCEES_FACULTY.\n";
} else {
    echo "  [WARN] framework_sync.php not found at {$syncFile}\n";
}

// ------------------------------------------------------------------------------
// [3/6] Create 5 Real Course Custom Fields via Moodle Custom Field API
// ------------------------------------------------------------------------------
echo "[3/6] Setting up 5 Course Custom Fields...\n";
$cfCategoryName = 'ACCEES / MEKO Course Metadata';
$cfCat = $DB->get_record('customfield_category', [
    'component' => 'core_course',
    'area' => 'course',
    'name' => $cfCategoryName,
]);
if (!$cfCat) {
    $catHandler = \core_customfield\handler::get_handler('core_course', 'course');
    $catId = $catHandler->create_category($cfCategoryName);
    $cfCat = $DB->get_record('customfield_category', ['id' => $catId], '*', MUST_EXIST);
}

$customFieldsDef = [
    ['shortname' => 'accees_course_status', 'name' => 'Trang thai khoa hoc', 'type' => 'text', 'configdata' => ['required' => 0, 'uniquevalues' => 0, 'defaultvalue' => '', 'displaysize' => 40, 'maxlength' => 120, 'ispassword' => 0, 'link' => '']],
    ['shortname' => 'accees_delivery_mode', 'name' => 'Hinh thuc dao tao', 'type' => 'text', 'configdata' => ['required' => 0, 'uniquevalues' => 0, 'defaultvalue' => '', 'displaysize' => 40, 'maxlength' => 120, 'ispassword' => 0, 'link' => '']],
    ['shortname' => 'accees_duration', 'name' => 'Thoi luong / Tin chi', 'type' => 'text', 'configdata' => ['required' => 0, 'uniquevalues' => 0, 'defaultvalue' => '', 'displaysize' => 40, 'maxlength' => 120, 'ispassword' => 0, 'link' => '']],
    ['shortname' => 'accees_institution', 'name' => 'Don vi phu trach', 'type' => 'text', 'configdata' => ['required' => 0, 'uniquevalues' => 0, 'defaultvalue' => '', 'displaysize' => 40, 'maxlength' => 120, 'ispassword' => 0, 'link' => '']],
    ['shortname' => 'accees_is_featured', 'name' => 'Hoc phan cot loi', 'type' => 'checkbox', 'configdata' => ['required' => 0, 'uniquevalues' => 0, 'checkbydefault' => 0]],
];

$fieldRecords = [];
$sortOrder = 1;
foreach ($customFieldsDef as $def) {
    $field = $DB->get_record('customfield_field', ['shortname' => $def['shortname'], 'categoryid' => $cfCat->id]);
    if (!$field) {
        $field = $DB->get_record('customfield_field', ['shortname' => $def['shortname']]);
    }
    if (!$field) {
        $catCtrl = \core_customfield\category_controller::create($cfCat->id);
        $fieldCtrl = \core_customfield\field_controller::create(0, (object)[
            'shortname' => $def['shortname'],
            'name' => $def['name'],
            'type' => $def['type'],
            'sortorder' => $sortOrder,
            'description' => '',
            'descriptionformat' => FORMAT_HTML,
            'configdata' => json_encode($def['configdata']),
        ], $catCtrl);
        $fieldCtrl->save();
        $field = $DB->get_record('customfield_field', ['shortname' => $def['shortname']], '*', MUST_EXIST);
        echo "  [+] Created custom field: {$def['shortname']} ({$def['type']})\n";
    }
    $fieldRecords[$def['shortname']] = $field;
    $sortOrder++;
}

function upsert_course_customfield($field, int $courseid, $value): void {
    global $DB;
    $context = \context_course::instance($courseid);
    $now = time();
    $existing = $DB->get_record('customfield_data', ['fieldid' => $field->id, 'instanceid' => $courseid]);
    $rec = $existing ?: new stdClass();
    $rec->fieldid = $field->id;
    $rec->instanceid = $courseid;
    $rec->contextid = $context->id;
    $rec->valueformat = 0;
    $rec->timemodified = $now;

    if ($field->type === 'checkbox') {
        $intval = !empty($value) ? 1 : 0;
        $rec->intvalue = $intval;
        $rec->value = (string)$intval;
        $rec->charvalue = null;
    } else {
        $strval = (string)$value;
        $rec->charvalue = $strval;
        $rec->value = $strval;
        $rec->intvalue = null;
    }

    if ($existing) {
        $DB->update_record('customfield_data', $rec);
    } else {
        $rec->timecreated = $now;
        $DB->insert_record('customfield_data', $rec);
    }
}

// ------------------------------------------------------------------------------
// [4/6] Create Real Users (Admin, Tech Lead, 4 Lecturers, 2 Students)
// ------------------------------------------------------------------------------
echo "[4/6] Creating real user accounts...\n";
$accounts = [
    ['username' => 'admin',    'password' => 'AdminMoodle2026!@#', 'firstname' => 'Quan tri vien', 'lastname' => 'Mekosoft',       'email' => 'admin@mekosoft.vn',    'role' => 'admin'],
    ['username' => 'pqhuy',    'password' => 'PqHuy2026!@#',       'firstname' => 'Quoc Huy',      'lastname' => 'Phan',           'email' => 'pqhuy@mekosoft.vn',    'role' => 'manager'],
    ['username' => 'teacher1', 'password' => 'Teacher2026!@#',     'firstname' => 'Thanh Dien',    'lastname' => 'PGS.TS. Tran',   'email' => 'teacher1@mekosoft.vn', 'role' => 'editingteacher'],
    ['username' => 'teacher2', 'password' => 'Teacher2026!@#',     'firstname' => 'Thai Nghe',     'lastname' => 'TS. Nguyen',     'email' => 'teacher2@mekosoft.vn', 'role' => 'editingteacher'],
    ['username' => 'teacher3', 'password' => 'Teacher2026!@#',     'firstname' => 'Nguyen Khang',  'lastname' => 'PGS.TS. Pham',   'email' => 'teacher3@mekosoft.vn', 'role' => 'editingteacher'],
    ['username' => 'teacher4', 'password' => 'Teacher2026!@#',     'firstname' => 'Huu Hoa',       'lastname' => 'PGS.TS. Nguyen', 'email' => 'teacher4@mekosoft.vn', 'role' => 'editingteacher'],
    ['username' => 'student1', 'password' => 'Student2026!@#',     'firstname' => 'Minh Anh',      'lastname' => 'Nguyen',         'email' => 'student1@mekosoft.vn', 'role' => 'student'],
    ['username' => 'b2204927', 'password' => 'Student2026!@#',     'firstname' => 'Hoang Nam',     'lastname' => 'Le',             'email' => 'b2204927@mekosoft.vn', 'role' => 'student'],
];

$usersByUsername = [];
foreach ($accounts as $acc) {
    $u = $DB->get_record('user', ['username' => $acc['username'], 'deleted' => 0]);
    if (!$u) {
        $uid = user_create_user((object)[
            'username' => $acc['username'],
            'password' => hash_internal_user_password($acc['password']),
            'firstname' => $acc['firstname'],
            'lastname' => $acc['lastname'],
            'email' => $acc['email'],
            'auth' => 'manual',
            'confirmed' => 1,
            'mnethostid' => $CFG->mnet_localhost_id,
            'city' => 'Can Tho',
            'country' => 'VN',
        ]);
        $u = $DB->get_record('user', ['id' => $uid], '*', MUST_EXIST);
        echo "  [+] Created user: {$acc['username']}\n";
    } else {
        $u->firstname = $acc['firstname'];
        $u->lastname = $acc['lastname'];
        $u->email = $acc['email'];
        $u->password = hash_internal_user_password($acc['password']);
        $u->confirmed = 1;
        $DB->update_record('user', $u);
    }
    if ($acc['role'] === 'admin') {
        $siteadmins = array_filter(explode(',', get_config('core', 'siteadmins')));
        if (!in_array((string)$u->id, $siteadmins, true)) {
            $siteadmins[] = (string)$u->id;
            set_config('siteadmins', implode(',', $siteadmins));
        }
    }
    $usersByUsername[$acc['username']] = $u;
}

// ------------------------------------------------------------------------------
// [5/6] Create 4 Categories + 12 Real Courses + Custom Fields + Competencies
// ------------------------------------------------------------------------------
echo "[5/6] Seeding 4 categories, 12 courses, real custom fields & competencies...\n";
$categoriesData = [
    'cat-nls-01' => [
        'name' => 'Biên Soạn & Thiết Kế Học Liệu Số Đa Phương Tiện',
        'courses' => [
            [
                'shortname' => 'NLS-01-IMS',
                'fullname' => 'Kỹ thuật đóng gói học liệu IMS CP và nhúng DLRP',
                'summary' => 'Hướng dẫn chuẩn hóa cấu trúc bài giảng IMS Content Package, SCORM và tích hợp kho học liệu số tập trung DLRP.',
                'image' => 'nls01ims.jpg',
                'startdate' => strtotime('2026-11-12 08:00:00'),
                'teacher' => 'teacher1',
                'status' => 'Đang mở ghi danh',
                'mode' => 'Kết hợp (Blended)',
                'duration' => '3 Tín chỉ (60 giờ)',
                'institution' => 'MEKOSOFT Academy',
                'featured' => 1,
                'comps' => ['SV_C1_3_N2', 'GV_2_2_INTERMEDIATE'],
            ],
            [
                'shortname' => 'NLS-01-OER',
                'fullname' => 'Biên tập và xuất bản tài nguyên giáo dục mở OER đa phương tiện',
                'summary' => 'Quy trình khai thác, cấp phép Creative Commons và xuất bản tài nguyên giáo dục mở chuẩn quốc tế.',
                'image' => 'nls01oer.jpg',
                'startdate' => strtotime('2026-11-15 08:00:00'),
                'teacher' => 'teacher2',
                'status' => 'Đang mở ghi danh',
                'mode' => 'Trực tuyến',
                'duration' => '2 Tín chỉ (45 giờ)',
                'institution' => 'Khoa CNTT&TT - CTU',
                'featured' => 1,
                'comps' => ['SV_C3_2_N2', 'GV_2_1_BASIC'],
            ],
            [
                'shortname' => 'NLS-01-H5P',
                'fullname' => 'Thiết kế hoạt động tương tác nâng cao với H5P trên Moodle',
                'summary' => 'Xây dựng bài giảng tương tác video, flashcard, branching scenario và bài tập kéo thả đa dạng.',
                'image' => 'nls01h5p.jpg',
                'startdate' => strtotime('2026-11-18 08:00:00'),
                'teacher' => 'teacher1',
                'status' => 'Đang diễn ra',
                'mode' => 'Kết hợp (Blended)',
                'duration' => '3 Tín chỉ (60 giờ)',
                'institution' => 'MEKOSOFT Academy',
                'featured' => 1,
                'comps' => ['SV_C3_1_N3', 'GV_3_1_INTERMEDIATE'],
            ],
            [
                'shortname' => 'NLS-01-VIDEO',
                'fullname' => 'Sản xuất bài giảng Micro-learning và Video tương tác số',
                'summary' => 'Kỹ thuật quay dựng, biên tập clip học tập ngắn và nhúng câu hỏi kiểm tra trực tiếp trên video.',
                'image' => 'nls01video.jpg',
                'startdate' => strtotime('2026-11-20 08:00:00'),
                'teacher' => 'teacher2',
                'status' => 'Sắp khai giảng',
                'mode' => 'Tự học có hướng dẫn',
                'duration' => '2 Tín chỉ (30 giờ)',
                'institution' => 'MEKOSOFT Studio',
                'featured' => 0,
                'comps' => ['SV_C3_1_N2', 'GV_2_2_BASIC'],
            ],
            [
                'shortname' => 'NLS-01-PODCAST',
                'fullname' => 'Xây dựng kênh Podcast giáo dục và học liệu âm thanh số',
                'summary' => 'Biên tập âm thanh chuyên nghiệp, xây dựng chuỗi bài giảng phát thanh số cho người học di động.',
                'image' => 'nls01podcast.jpg',
                'startdate' => strtotime('2026-11-25 08:00:00'),
                'teacher' => 'teacher2',
                'status' => 'Sắp khai giảng',
                'mode' => 'Trực tuyến',
                'duration' => '2 Tín chỉ (30 giờ)',
                'institution' => 'Trung tâm Học liệu Số',
                'featured' => 0,
                'comps' => ['SV_C2_4_N2', 'GV_2_3_BASIC'],
            ],
            [
                'shortname' => 'NLS-01-ACC',
                'fullname' => 'Thiết kế học liệu số tiếp cận phổ quát (Accessibility WCAG 2.1)',
                'summary' => 'Tuân thủ tiêu chuẩn tiếp cận WCAG 2.1, thiết kế nội dung hòa nhập cho mọi đối tượng người học.',
                'image' => 'nls01acc.jpg',
                'startdate' => strtotime('2026-11-28 08:00:00'),
                'teacher' => 'teacher1',
                'status' => 'Đang mở ghi danh',
                'mode' => 'Trực tuyến',
                'duration' => '3 Tín chỉ (60 giờ)',
                'institution' => 'Dự án ACCEES',
                'featured' => 1,
                'comps' => ['SV_C3_3_N3', 'GV_5_1_INTERMEDIATE'],
            ],
        ],
    ],
    'cat-nls-02' => [
        'name' => 'Khai Thác & Phân Tích Dữ Liệu Học Tập (Analytics)',
        'courses' => [
            [
                'shortname' => 'NLS-02-DATA',
                'fullname' => 'Phân tích dữ liệu học tập và dự báo kết quả đào tạo',
                'summary' => 'Ứng dụng Learning Analytics để thu thập telemetry từ LMS, đánh giá tương tác và dự báo kết quả.',
                'image' => 'nls02data.jpg',
                'startdate' => strtotime('2026-12-01 08:00:00'),
                'teacher' => 'teacher3',
                'status' => 'Đang mở ghi danh',
                'mode' => 'Kết hợp (Blended)',
                'duration' => '3 Tín chỉ (60 giờ)',
                'institution' => 'MEKOSOFT Data Lab',
                'featured' => 1,
                'comps' => ['SV_C1_2_N3', 'GV_4_2_ADVANCED'],
            ],
            [
                'shortname' => 'NLS-02-LOGS',
                'fullname' => 'Khai thác Moodle Event Logs và xAPI trong giáo dục đại học',
                'summary' => 'Trích xuất log sự kiện Moodle và tích hợp chuẩn xAPI/LRS để theo dõi hành vi học viên.',
                'image' => 'nls02logs.jpg',
                'startdate' => strtotime('2026-12-05 08:00:00'),
                'teacher' => 'teacher3',
                'status' => 'Đang diễn ra',
                'mode' => 'Trực tuyến',
                'duration' => '2 Tín chỉ (45 giờ)',
                'institution' => 'Khoa CNTT&TT - CTU',
                'featured' => 0,
                'comps' => ['SV_C1_3_N3', 'GV_4_1_INTERMEDIATE'],
            ],
            [
                'shortname' => 'NLS-02-DASH',
                'fullname' => 'Xây dựng Dashboard trực quan hóa dữ liệu học tập với PowerBI',
                'summary' => 'Kết nối CSDL Moodle với PowerBI để tạo bảng điều khiển theo dõi tiến độ đào tạo thời gian thực.',
                'image' => 'nls02dash.jpg',
                'startdate' => strtotime('2026-12-08 08:00:00'),
                'teacher' => 'teacher3',
                'status' => 'Đang mở ghi danh',
                'mode' => 'Kết hợp (Blended)',
                'duration' => '3 Tín chỉ (60 giờ)',
                'institution' => 'MEKOSOFT Data Lab',
                'featured' => 1,
                'comps' => ['SV_C1_1_N3', 'GV_4_2_INTERMEDIATE'],
            ],
            [
                'shortname' => 'NLS-02-INTERV',
                'fullname' => 'Hệ thống cảnh báo sớm sinh viên có nguy cơ bỏ cuộc',
                'summary' => 'Thiết lập chỉ số cảnh báo sớm và quy trình can thiệp sư phạm kịp thời cho người học.',
                'image' => 'nls02interv.jpg',
                'startdate' => strtotime('2026-12-10 08:00:00'),
                'teacher' => 'teacher3',
                'status' => 'Sắp khai giảng',
                'mode' => 'Tự học có hướng dẫn',
                'duration' => '2 Tín chỉ (45 giờ)',
                'institution' => 'Phòng Đào tạo',
                'featured' => 0,
                'comps' => ['SV_C5_2_N3', 'GV_5_3_ADVANCED'],
            ],
        ],
    ],
    'cat-nls-03' => [
        'name' => 'Ứng Dụng Trí Tuệ Nhân Tạo (AI) Trong Giảng Dạy',
        'courses' => [
            [
                'shortname' => 'NLS-03-AI',
                'fullname' => 'Ứng dụng Generative AI trong thiết kế câu hỏi và đánh giá tự động',
                'summary' => 'Sử dụng mô hình ngôn ngữ lớn để soạn thảo ngân hàng câu hỏi, rubric chấm điểm và phản hồi tự động.',
                'image' => 'nls03ai.jpg',
                'startdate' => strtotime('2026-12-15 08:00:00'),
                'teacher' => 'teacher4',
                'status' => 'Đang mở ghi danh',
                'mode' => 'Kết hợp (Blended)',
                'duration' => '3 Tín chỉ (60 giờ)',
                'institution' => 'MEKOSOFT AI Center',
                'featured' => 1,
                'comps' => ['SV_C5_3_N4', 'GV_3_3_ADVANCED'],
            ],
        ],
    ],
    'cat-nls-04' => [
        'name' => 'Bảo Mật Dữ Liệu & An Toàn Số Trong GDĐH',
        'courses' => [
            [
                'shortname' => 'NLS-04-SEC',
                'fullname' => 'An toàn thông tin, bảo vệ dữ liệu cá nhân và phòng chống tấn công số',
                'summary' => 'Nguyên tắc bảo vệ dữ liệu sinh viên, phòng ngừa lừa đảo mạng và bảo mật danh tính số trên LMS.',
                'image' => 'nls04sec.jpg',
                'startdate' => strtotime('2026-12-20 08:00:00'),
                'teacher' => 'teacher4',
                'status' => 'Đang mở ghi danh',
                'mode' => 'Trực tuyến',
                'duration' => '2 Tín chỉ (45 giờ)',
                'institution' => 'MEKOSOFT Security',
                'featured' => 1,
                'comps' => ['SV_C4_2_N3', 'GV_2_3_INTERMEDIATE'],
            ],
        ],
    ],
];

$fs = get_file_storage();
$pixDir = $CFG->dirroot . '/theme/accees_lms_theme/pix/courses';
$teacherRole = $DB->get_record('role', ['shortname' => 'editingteacher'], '*', MUST_EXIST);
$studentRole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
$manualEnrol = enrol_get_plugin('manual');

$seededCourses = [];
foreach ($categoriesData as $catIdNum => $catInfo) {
    $cat = $DB->get_record('course_categories', ['idnumber' => $catIdNum]);
    if (!$cat) {
        $cat = core_course_category::create([
            'name' => $catInfo['name'],
            'idnumber' => $catIdNum,
            'description' => '<p>' . s($catInfo['name']) . '</p>',
            'descriptionformat' => FORMAT_HTML,
        ]);
        echo "  [+] Category: {$catInfo['name']}\n";
    }

    foreach ($catInfo['courses'] as $cData) {
        $course = $DB->get_record('course', ['shortname' => $cData['shortname']]);
        if (!$course) {
            $course = create_course((object)[
                'category' => $cat->id,
                'fullname' => $cData['fullname'],
                'shortname' => $cData['shortname'],
                'summary' => '<p>' . s($cData['summary']) . '</p>',
                'summaryformat' => FORMAT_HTML,
                'format' => 'topics',
                'visible' => 1,
                'enablecompletion' => 1,
                'startdate' => $cData['startdate'],
                'enddate' => 0,
                'numsections' => 3,
            ]);
            echo "  [+] Course: {$cData['shortname']}\n";
        } else {
            $course->startdate = $cData['startdate'];
            $course->enablecompletion = 1;
            $DB->update_record('course', $course);
        }
        $seededCourses[$cData['shortname']] = $course;

        // Upload real 16:9 cover into Moodle file storage (overviewfiles)
        $imgPath = $pixDir . '/' . $cData['image'];
        $courseCtx = context_course::instance($course->id);
        if (is_readable($imgPath)) {
            $existingFiles = $fs->get_area_files($courseCtx->id, 'course', 'overviewfiles', 0, 'id', false);
            if (!$existingFiles) {
                $fs->create_file_from_pathname([
                    'contextid' => $courseCtx->id,
                    'component' => 'course',
                    'filearea' => 'overviewfiles',
                    'itemid' => 0,
                    'filepath' => '/',
                    'filename' => $cData['image'],
                ], $imgPath);
            }
        }

        // Write 5 real custom fields into mdl_customfield_data
        upsert_course_customfield($fieldRecords['accees_course_status'], $course->id, $cData['status']);
        upsert_course_customfield($fieldRecords['accees_delivery_mode'], $course->id, $cData['mode']);
        upsert_course_customfield($fieldRecords['accees_duration'], $course->id, $cData['duration']);
        upsert_course_customfield($fieldRecords['accees_institution'], $course->id, $cData['institution']);
        upsert_course_customfield($fieldRecords['accees_is_featured'], $course->id, $cData['featured']);

        // Link real competencies (mdl_competency_coursecomp)
        foreach ($cData['comps'] as $compIdNum) {
            $compRec = $DB->get_record('competency', ['idnumber' => $compIdNum]);
            if ($compRec) {
                try {
                    \core_competency\api::add_competency_to_course($course->id, $compRec->id);
                } catch (\Throwable $e) {
                    // Already linked or rule exists
                }
            }
        }

        // Ensure manual enrolment instance & enrol assigned real teacher
        $manualInst = null;
        foreach (enrol_get_instances($course->id, false) as $inst) {
            if ($inst->enrol === 'manual') {
                $manualInst = $inst;
                break;
            }
        }
        if (!$manualInst) {
            $instId = $manualEnrol->add_instance($course);
            $manualInst = $DB->get_record('enrol', ['id' => $instId], '*', MUST_EXIST);
        }

        $tUser = $usersByUsername[$cData['teacher']] ?? null;
        if ($tUser) {
            $manualEnrol->enrol_user($manualInst, $tUser->id, $teacherRole->id);
        }
    }
}

// Enrol students into sample courses
$student1Courses = ['NLS-01-IMS', 'NLS-01-OER', 'NLS-01-H5P', 'NLS-01-ACC', 'NLS-02-DATA', 'NLS-03-AI', 'NLS-04-SEC'];
$student2Courses = ['NLS-01-IMS', 'NLS-02-DATA', 'NLS-02-DASH', 'NLS-03-AI'];
foreach ($student1Courses as $code) {
    if (isset($seededCourses[$code])) {
        $inst = $DB->get_record('enrol', ['courseid' => $seededCourses[$code]->id, 'enrol' => 'manual']);
        if ($inst) {
            $manualEnrol->enrol_user($inst, $usersByUsername['student1']->id, $studentRole->id);
        }
    }
}
foreach ($student2Courses as $code) {
    if (isset($seededCourses[$code])) {
        $inst = $DB->get_record('enrol', ['courseid' => $seededCourses[$code]->id, 'enrol' => 'manual']);
        if ($inst) {
            $manualEnrol->enrol_user($inst, $usersByUsername['b2204927']->id, $studentRole->id);
        }
    }
}

// ------------------------------------------------------------------------------
// [6/6] Populate Real Learning Activities on Flagship Courses (if empty)
// ------------------------------------------------------------------------------
echo "[6/6] Populating real activities (Forum, Page, Resource, Assign, Quiz) on flagship courses...\n";
$genLib = $CFG->libdir . '/testing/generator/lib.php';
if (file_exists($genLib)) {
    require_once($genLib);
    $generator = testing_util::get_data_generator();
    $forumgen = $generator->get_plugin_generator('mod_forum');
    $pagegen = $generator->get_plugin_generator('mod_page');
    $assigngen = $generator->get_plugin_generator('mod_assign');
    $urlgen = $generator->get_plugin_generator('mod_url');

    foreach (['NLS-01-IMS', 'NLS-02-DATA', 'NLS-03-AI'] as $code) {
        if (!isset($seededCourses[$code])) {
            continue;
        }
        $course = $seededCourses[$code];
        $existingCms = $DB->count_records('course_modules', ['course' => $course->id]);
        if ($existingCms > 0) {
            continue;
        }

        // Section names
        $secTitles = [
            0 => 'Giới thiệu & Đề cương học phần',
            1 => 'Chủ đề 1: Kiến thức nền tảng & Chuẩn kỹ thuật',
            2 => 'Chủ đề 2: Thực hành triển khai & Tình huống',
            3 => 'Chủ đề 3: Đánh giá Năng lực số (CBE)',
        ];
        foreach ($secTitles as $secNum => $secTitle) {
            $sec = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $secNum]);
            if ($sec) {
                $sec->name = $secTitle;
                $sec->summary = "<p>Nội dung chi tiết thuộc {$secTitle}</p>";
                $DB->update_record('course_sections', $sec);
            }
        }

        $forumgen->create_instance([
            'course' => $course->id,
            'section' => 0,
            'name' => 'Diễn đàn thông báo & Thảo luận học phần',
            'intro' => '<p>Kênh trao đổi trực tiếp giữa giảng viên và học viên trong suốt khóa học.</p>',
            'introformat' => FORMAT_HTML,
        ]);

        $pagegen->create_instance([
            'course' => $course->id,
            'section' => 1,
            'name' => "Bài giảng 1: Tổng quan & Chuẩn đầu ra ({$code})",
            'content' => "<h3>Mục tiêu học tập</h3><p>Khóa học <strong>{$course->fullname}</strong> được thiết kế theo chuẩn Competency-Based Education (CBE) gắn với Khung năng lực số DigComp 2.2 / DigCompEdu.</p>",
            'contentformat' => FORMAT_HTML,
        ]);

        $urlgen->create_instance([
            'course' => $course->id,
            'section' => 1,
            'name' => 'Tài liệu tham khảo trên Liferay TMS',
            'externalurl' => "http://{$domain}/tms",
            'intro' => '<p>Liên kết phân hệ TMS nội bộ để đối chiếu kế hoạch đào tạo.</p>',
            'introformat' => FORMAT_HTML,
        ]);

        $assigngen->create_instance([
            'course' => $course->id,
            'section' => 2,
            'name' => "Bài tập thực hành: Hồ sơ năng lực số ({$code})",
            'intro' => '<p>Sinh viên hoàn thành bài tập thực hành và nộp file báo cáo PDF/ZIP trực tiếp tại đây.</p>',
            'introformat' => FORMAT_HTML,
        ]);

        echo "  [+] Added real activities to {$code}\n";
    }
}

purge_all_caches();

echo "======================================================================\n";
echo "  MOODLE REAL DATA SEEDING COMPLETED!\n";
echo "  URL        : {$wwwroot}\n";
echo "  Admin      : admin    / AdminMoodle2026!@#\n";
echo "  Tech Lead  : pqhuy    / PqHuy2026!@#\n";
echo "  Lecturers  : teacher1..teacher4 / Teacher2026!@#\n";
echo "  Students   : student1, b2204927 / Student2026!@#\n";
echo "======================================================================\n";

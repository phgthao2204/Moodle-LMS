<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Creates the demo categories and courses used by the public landing page.
 *
 * @package   theme_meko
 * @copyright 2026 Mekosoft
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

$definitions = [
    'meko-free' => [
        'name' => 'Khóa học miễn phí',
        'courses' => [
            ['Nhập môn chuyển đổi số', 'FREE-DIGITAL', 'Hiểu các khái niệm nền tảng và cách bắt đầu hành trình chuyển đổi số.'],
            ['Kỹ năng làm việc hiệu quả', 'FREE-WORK', 'Xây dựng thói quen, quản lý thời gian và nâng cao hiệu suất cá nhân.'],
            ['Cơ bản về an toàn thông tin', 'FREE-SECURITY', 'Nhận diện rủi ro phổ biến và bảo vệ dữ liệu trong công việc hằng ngày.'],
            ['Giao tiếp trong môi trường số', 'FREE-COMM', 'Rèn luyện kỹ năng giao tiếp rõ ràng qua email, chat và họp trực tuyến.'],
            ['Làm quen với Moodle LMS', 'FREE-MOODLE', 'Khám phá cách học, làm bài kiểm tra và theo dõi tiến độ trên Moodle.'],
        ],
    ],
    'meko-latest' => [
        'name' => 'Khóa học mới nhất',
        'courses' => [
            ['Ứng dụng AI trong công việc', 'NEW-AI', 'Sử dụng AI có trách nhiệm để nghiên cứu, sáng tạo và tự động hóa tác vụ.'],
            ['Quản lý dự án Agile', 'NEW-AGILE', 'Nắm vững Scrum, lập kế hoạch sprint và phối hợp nhóm hiệu quả.'],
            ['Phân tích dữ liệu cho người mới', 'NEW-DATA', 'Đọc hiểu dữ liệu, xây dựng báo cáo và đưa ra quyết định có cơ sở.'],
            ['Thiết kế trải nghiệm học trực tuyến', 'NEW-LXD', 'Thiết kế lộ trình học hấp dẫn, dễ tiếp cận và đo lường được.'],
            ['Kỹ năng lãnh đạo đội nhóm', 'NEW-LEAD', 'Phát triển năng lực giao việc, phản hồi và tạo động lực cho đội ngũ.'],
        ],
    ],
];

foreach ($definitions as $idnumber => $definition) {
    $categoryrecord = $DB->get_record('course_categories', ['idnumber' => $idnumber]);
    if ($categoryrecord) {
        $category = core_course_category::get($categoryrecord->id);
    } else {
        $category = core_course_category::create([
            'name' => $definition['name'],
            'idnumber' => $idnumber,
            'description' => $definition['name'] . ' trên Meko LMS.',
            'descriptionformat' => FORMAT_HTML,
        ]);
        mtrace('Created category: ' . $definition['name']);
    }

    foreach ($definition['courses'] as [$fullname, $shortname, $summary]) {
        if ($DB->record_exists('course', ['shortname' => $shortname])) {
            mtrace('Course already exists: ' . $shortname);
            continue;
        }

        create_course((object) [
            'category' => $category->id,
            'fullname' => $fullname,
            'shortname' => $shortname,
            'summary' => '<p>' . s($summary) . '</p>',
            'summaryformat' => FORMAT_HTML,
            'format' => 'topics',
            'visible' => 1,
            'startdate' => time(),
            'enddate' => 0,
            'numsections' => 5,
        ]);
        mtrace('Created course: ' . $fullname);
    }
}

mtrace('Homepage seed completed.');

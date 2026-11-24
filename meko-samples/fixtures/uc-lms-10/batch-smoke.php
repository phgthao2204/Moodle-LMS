<?php
// Run only on a disposable Moodle development instance. All fixtures are rolled back.
define('CLI_SCRIPT', true);
require(getenv('MOODLE_CONFIG') ?: '/var/www/html/config.php');
\core\session\manager::set_user(get_admin());

use local_accees_sync_api\contract\lom_metadata_interface;
use local_accees_sync_api\manager\course_manager;
use local_accees_sync_api\manager\lom_metadata_manager;

function uc10_check(string $name, bool $passed): void {
    echo json_encode(['check' => $name, 'passed' => $passed]) . PHP_EOL;
    if (!$passed) {
        throw new RuntimeException($name);
    }
}

$transaction = $DB->start_delegated_transaction();
$rollback = new RuntimeException('Roll back UC10 QA fixtures');
try {
    $base = $DB->get_record_sql('SELECT * FROM {course} WHERE id <> :siteid ORDER BY id', ['siteid' => SITEID], IGNORE_MULTIPLE);
    if (!$base) {
        throw new RuntimeException('Seed at least one course before running this check.');
    }
    $tag = 'UC10-BATCH-' . bin2hex(random_bytes(5));
    $courses = [];
    for ($index = 0; $index < 100; $index++) {
        $course = clone $base;
        unset($course->id);
        $course->shortname = $tag . '-' . $index;
        $course->fullname = $course->shortname;
        $course->idnumber = '';
        $course->visible = 1;
        $course->id = $DB->insert_record('course', $course);
        $courses[$course->id] = $course;
    }
    $ids = array_keys($courses);
    // Populate overview and custom fields on one course to exercise non-empty batch metadata.
    $context = \context_course::instance($ids[0]);
    get_file_storage()->create_file_from_string([
        'contextid' => $context->id, 'component' => 'course', 'filearea' => 'overviewfiles',
        'itemid' => 0, 'filepath' => '/', 'filename' => 'batch.png', 'mimetype' => 'image/png',
    ], base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
    $sourcefields = $DB->get_records_sql(
        "SELECT d.* FROM {customfield_data} d JOIN {customfield_field} f ON f.id = d.fieldid
          WHERE d.instanceid = :courseid AND f.shortname LIKE :prefix",
        ['courseid' => $base->id, 'prefix' => 'accees_%'],
    );
    foreach ($sourcefields as $field) {
        unset($field->id);
        $field->instanceid = $ids[0];
        $field->contextid = $context->id;
        $DB->insert_record('customfield_data', $field);
    }
    // Attach the same competency to two courses to catch duplicate result-key bugs.
    $competency = $DB->get_record_sql('SELECT * FROM {competency} ORDER BY id', [], IGNORE_MULTIPLE);
    if ($competency) {
        foreach (array_slice($ids, 0, 2) as $id) {
            $DB->insert_record('competency_coursecomp', (object)[
                'courseid' => $id, 'competencyid' => $competency->id,
                'sortorder' => 0, 'ruleoutcome' => 0, 'timecreated' => time(),
                'timemodified' => time(), 'usermodified' => get_admin()->id,
            ]);
        }
    }
    $manager = new course_manager();
    $detail = $manager->get_course($ids[0]);
    uc10_check('overview image populated', is_string($detail['course_image']) && $detail['course_image'] !== '');
    if ($sourcefields) {
        uc10_check('custom fields populated', count(array_filter($detail['custom_fields'], static fn($value) => $value !== null)) > 1);
    }
    $manager->get_courses(['search' => $tag, 'per_page' => 100]); // Warm core/file API initialization.
    $before = $DB->perf_get_reads();
    $one = $manager->get_courses(['search' => $tag, 'per_page' => 1]);
    $onereads = $DB->perf_get_reads() - $before;
    $before = $DB->perf_get_reads();
    $hundred = $manager->get_courses(['search' => $tag, 'per_page' => 100]);
    $hundredreads = $DB->perf_get_reads() - $before;
    echo json_encode(['courses_1_queries' => $onereads, 'courses_100_queries' => $hundredreads]) . PHP_EOL;
    uc10_check('100 courses returned', count($hundred['items']) === 100);
    uc10_check('query count does not grow with page size', $hundredreads === $onereads && $hundredreads <= 8);
    foreach (['1.0', '1.1'] as $version) {
        $page = $manager->get_courses(['search' => $tag, 'per_page' => 100], $version);
        $equal = true;
        foreach ($page['items'] as $item) {
            $equal = $equal && $item === $manager->get_course($item['id'], $version);
            foreach (lom_metadata_interface::LOM_CATEGORIES as $group) {
                if (!array_key_exists($group, $item['lom'])) {
                    throw new RuntimeException('Missing LOM group: ' . $group);
                }
            }
        }
        uc10_check('100 list/detail payloads match for ' . $version, $equal);
    }
    $loms = (new lom_metadata_manager())->export_lom_batch($courses, '1.1');
    if ($competency) {
        uc10_check('shared competency retained for both courses',
            $loms[$ids[0]]['classification']['competency_codes'] === [$competency->idnumber]
            && $loms[$ids[1]]['classification']['competency_codes'] === [$competency->idnumber]);
    }
    uc10_check('missing competency returns empty array', $loms[$ids[99]]['classification']['competency_codes'] === []);
    uc10_check('custom fields do not leak between courses', $loms[$ids[99]]['classification']['institution'] === null);
    uc10_check('empty batch', (new lom_metadata_manager())->export_lom_batch([], '1.1') === []);
    uc10_check('empty page', $manager->get_courses(['search' => $tag, 'page' => 101])['items'] === []);

    // Verify backwards compatibility and version propagation for existing single-course mappers.
    $spy = new class implements lom_metadata_interface {
        public array $versions = [];
        public function export_lom(int $courseid, string $apiversion = '1.0'): array {
            $this->versions[] = $apiversion;
            return array_fill_keys(self::LOM_CATEGORIES, []);
        }
        public function apply_lom(int $courseid, array $lom, string $apiversion = '1.0'): array {
            return [];
        }
    };
    $legacy = new course_manager($spy);
    $legacy->get_courses(['search' => $tag, 'per_page' => 1], '1.1');
    $legacy->get_course($ids[0], '1.1');
    uc10_check('legacy mapper receives version', $spy->versions === ['1.1', '1.1']);
    $bulkspy = new class extends lom_metadata_manager {
        public array $versions = [];
        protected function format_lom(\stdClass $course, array $fields, array $competencies, string $apiversion): array {
            $this->versions[] = $apiversion;
            return parent::format_lom($course, $fields, $competencies, $apiversion);
        }
    };
    $bulkmanager = new course_manager($bulkspy);
    $bulkmanager->get_courses(['search' => $tag, 'per_page' => 1], '1.1');
    $bulkmanager->get_course($ids[0], '1.1');
    uc10_check('batch mapper receives version', $bulkspy->versions === ['1.1', '1.1']);
    foreach (['list', 'detail'] as $operation) {
        $rejected = false;
        try {
            $operation === 'list' ? $manager->get_courses([], '2.0') : $manager->get_course($ids[0], '2.0');
        } catch (InvalidArgumentException $e) {
            $rejected = true;
        }
        uc10_check('reject unsupported version ' . $operation, $rejected);
    }
} finally {
    try {
        $transaction->rollback($rollback);
    } catch (RuntimeException $e) {
        if ($e !== $rollback) {
            throw $e;
        }
    }
    echo "QA fixtures rolled back\n";
}

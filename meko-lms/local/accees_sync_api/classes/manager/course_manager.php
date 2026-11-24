<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// @package    local_accees_sync_api
// @copyright  2026 Du an ACCEES (Erasmus+ CBHE-101179860)
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

namespace local_accees_sync_api\manager;

use local_accees_sync_api\contract\lom_metadata_interface;
use local_accees_sync_api\contract\bulk_lom_metadata_interface;

/**
 * Read service for the UC-LMS-10 course endpoints.
 */
class course_manager {

    /** @var int Maximum supported page size. */
    private const MAX_PER_PAGE = 100;

    /** @var lom_metadata_interface LOM mapper. */
    private readonly lom_metadata_interface $lommanager;

    /**
     * Constructor.
     *
     * @param lom_metadata_interface|null $lommanager Optional mapper for tests.
     */
    public function __construct(?lom_metadata_interface $lommanager = null) {
        $this->lommanager = $lommanager ?? new lom_metadata_manager();
    }

    /**
     * Return paginated courses matching the supplied filters.
     *
     * @param array<string, mixed> $filters Validated query parameters.
     * @param string $apiversion Target API version.
     * @return array<string, mixed>
     */
    public function get_courses(array $filters, string $apiversion = '1.0'): array {
        global $DB, $SITE;

        $this->validate_version($apiversion);

        $page = (int) ($filters['page'] ?? 1);
        $perpage = (int) ($filters['per_page'] ?? 20);
        if ($page < 1) {
            throw new \InvalidArgumentException('page must be greater than or equal to 1.');
        }
        if ($perpage < 1 || $perpage > self::MAX_PER_PAGE) {
            throw new \InvalidArgumentException('per_page must be between 1 and 100.');
        }

        $where = ['c.id <> :siteid'];
        $params = ['siteid' => (int) $SITE->id];
        if (!filter_var($filters['include_hidden'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $where[] = 'c.visible = 1';
        }
        $this->add_standard_filters($where, $params, $filters);
        $this->add_custom_field_filter(
            $where,
            $params,
            lom_metadata_interface::FIELD_INSTITUTION,
            $filters['institution'] ?? null,
            'institution'
        );
        $this->add_custom_field_filter(
            $where,
            $params,
            lom_metadata_interface::FIELD_DELIVERY_MODE,
            $filters['delivery_mode'] ?? null,
            'deliverymode'
        );
        $this->add_custom_field_filter(
            $where,
            $params,
            lom_metadata_interface::FIELD_COURSE_STATUS,
            $filters['status'] ?? null,
            'status'
        );
        $this->add_featured_filter($where, $params, $filters['is_featured'] ?? null);

        $wheresql = implode(' AND ', $where);
        $total = (int) $DB->count_records_sql("SELECT COUNT(1) FROM {course} c WHERE {$wheresql}", $params);
        $sql = "SELECT c.id, c.shortname, c.fullname, c.summary, c.category,
                       c.visible, c.startdate, c.enddate, c.timemodified, c.lang
                  FROM {course} c
                 WHERE {$wheresql}
              ORDER BY c.timemodified DESC, c.id ASC";
        $offset = ($page - 1) * $perpage;
        $records = $DB->get_records_sql($sql, $params, $offset, $perpage);

        $metadata = $this->load_page_metadata($records);
        $loms = $this->lommanager instanceof bulk_lom_metadata_interface
            ? $this->lommanager->export_lom_batch($records, $apiversion) : [];

        $items = [];
        foreach ($records as $course) {
            $items[] = $this->format_course($course, $apiversion, $metadata[$course->id], $loms[$course->id] ?? null);
        }

        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $perpage,
                'total' => $total,
                'total_pages' => $total === 0 ? 0 : (int) ceil($total / $perpage),
            ],
        ];
    }

    /**
     * Return one course or null when it does not exist.
     *
     * @param int $courseid Moodle course ID.
     * @param string $apiversion Target API version.
     * @return array<string, mixed>|null
     */
    public function get_course(int $courseid, string $apiversion = '1.0'): ?array {
        global $DB, $SITE;

        $this->validate_version($apiversion);

        if ($courseid === (int) $SITE->id) {
            return null;
        }
        $course = $DB->get_record(
            'course',
            ['id' => $courseid],
            'id, shortname, fullname, summary, category, visible, startdate, enddate, timemodified, lang'
        );
        return $course ? $this->format_course($course, $apiversion) : null;
    }

    /** Reject unsupported versions before querying or invoking an injected mapper. */
    private function validate_version(string $apiversion): void {
        if (!in_array($apiversion, ['1.0', '1.1'], true)) {
            throw new \InvalidArgumentException('Unsupported X-ACCEES-API-Version.');
        }
    }

    /** Load category names, module counts and overview images once per page. */
    private function load_page_metadata(array $courses): array {
        global $DB, $CFG;

        if (!$courses) {
            return [];
        }
        $ids = array_map(static fn(\stdClass $course): int => (int) $course->id, $courses);
        $categoryids = array_unique(array_map(static fn(\stdClass $course): int => (int) $course->category, $courses));
        $categories = $DB->get_records_list('course_categories', 'id', $categoryids, '', 'id, name');
        [$insql, $params] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'pagecourse');
        $counts = $DB->get_records_sql(
            "SELECT course, COUNT(1) AS total FROM {course_modules}
              WHERE course {$insql} AND deletioninprogress = 0 GROUP BY course", $params,
        );
        $metadata = [];
        foreach ($courses as $course) {
            $metadata[$course->id] = [
                'category_name' => (string) ($categories[$course->category]->name ?? ''),
                'modules_count' => (int) ($counts[$course->id]->total ?? 0),
                'course_image' => null,
            ];
        }
        if (empty($CFG->courseoverviewfileslimit)) {
            return $metadata;
        }
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->libdir . '/filelib.php');
        $acceptedtypes = course_overviewfiles_options(0)['accepted_types'];
        $params['contextlevel'] = CONTEXT_COURSE;
        $params['component'] = 'course';
        $params['filearea'] = 'overviewfiles';
        $params['directory'] = '.';
        $files = $DB->get_records_sql(
            "SELECT f.*, ctx.instanceid AS courseid
               FROM {files} f JOIN {context} ctx ON ctx.id = f.contextid
              WHERE ctx.instanceid {$insql} AND ctx.contextlevel = :contextlevel
                AND f.component = :component AND f.filearea = :filearea AND f.filename <> :directory
           ORDER BY ctx.instanceid, f.filename, f.id", $params,
        );
        $seen = [];
        $storage = get_file_storage();
        foreach ($files as $record) {
            $id = (int) $record->courseid;
            if ($acceptedtypes !== '*' && !file_extension_in_typegroup($record->filename, $acceptedtypes)) {
                continue;
            }
            $seen[$id] = ($seen[$id] ?? 0) + 1;
            if ($seen[$id] > $CFG->courseoverviewfileslimit || $metadata[$id]['course_image'] !== null) {
                continue;
            }
            $file = $storage->get_file_instance($record);
            if ($file->is_valid_image()) {
                $metadata[$id]['course_image'] = \moodle_url::make_pluginfile_url(
                    $file->get_contextid(), $file->get_component(), $file->get_filearea(),
                    null, $file->get_filepath(), $file->get_filename(),
                )->out(false);
            }
        }
        return $metadata;
    }

    /**
     * Add filters backed by core course columns.
     *
     * @param array<int, string> $where SQL predicates.
     * @param array<string, mixed> $params SQL parameters.
     * @param array<string, mixed> $filters Request filters.
     */
    private function add_standard_filters(array &$where, array &$params, array $filters): void {
        global $DB;

        if (!empty($filters['search'])) {
            $like = $DB->sql_like('c.fullname', ':searchfullname', false, false);
            $shortlike = $DB->sql_like('c.shortname', ':searchshortname', false, false);
            $where[] = "({$like} OR {$shortlike})";
            $search = '%' . $DB->sql_like_escape(trim((string) $filters['search'])) . '%';
            $params['searchfullname'] = $search;
            $params['searchshortname'] = $search;
        }
        if (!empty($filters['category_id'])) {
            $where[] = 'c.category = :categoryid';
            $params['categoryid'] = (int) $filters['category_id'];
        }
        if (!empty($filters['updated_since'])) {
            $rawupdated = (string) $filters['updated_since'];
            if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/', $rawupdated)) {
                throw new \InvalidArgumentException('updated_since must be a valid ISO-8601 timestamp.');
            }
            try {
                $updated = new \DateTimeImmutable($rawupdated);
            } catch (\Throwable $exception) {
                throw new \InvalidArgumentException('updated_since must be a valid ISO-8601 timestamp.', 0, $exception);
            }
            $where[] = 'c.timemodified >= :updatedsince';
            $params['updatedsince'] = $updated->getTimestamp();
        }
    }

    /**
     * Add a string custom-field filter.
     *
     * @param array<int, string> $where SQL predicates.
     * @param array<string, mixed> $params SQL parameters.
     * @param string $shortname Custom field shortname.
     * @param mixed $value Filter value.
     * @param string $paramprefix Unique parameter prefix.
     */
    private function add_custom_field_filter(
        array &$where,
        array &$params,
        string $shortname,
        mixed $value,
        string $paramprefix,
    ): void {
        if ($value === null || $value === '') {
            return;
        }
        $where[] = "EXISTS (
            SELECT 1
              FROM {customfield_data} {$paramprefix}d
              JOIN {customfield_field} {$paramprefix}f ON {$paramprefix}f.id = {$paramprefix}d.fieldid
             WHERE {$paramprefix}d.instanceid = c.id
               AND {$paramprefix}f.shortname = :{$paramprefix}field
               AND ({$paramprefix}d.charvalue = :{$paramprefix}value
                    OR {$paramprefix}d.value = :{$paramprefix}value2)
        )";
        $params["{$paramprefix}field"] = $shortname;
        $params["{$paramprefix}value"] = (string) $value;
        $params["{$paramprefix}value2"] = (string) $value;
    }

    /**
     * Add the boolean featured custom-field filter.
     *
     * @param array<int, string> $where SQL predicates.
     * @param array<string, mixed> $params SQL parameters.
     * @param mixed $value Filter value.
     */
    private function add_featured_filter(array &$where, array &$params, mixed $value): void {
        if ($value === null || $value === '') {
            return;
        }
        // Missing metadata is returned as false, so include it in the false filter.
        $predicate = $value ? 'EXISTS' : 'NOT EXISTS';
        $where[] = "{$predicate} (
            SELECT 1
              FROM {customfield_data} featuredd
              JOIN {customfield_field} featuredf ON featuredf.id = featuredd.fieldid
             WHERE featuredd.instanceid = c.id
               AND featuredf.shortname = :featuredfield
               AND (featuredd.intvalue = :featuredint
                    OR featuredd.value = :featuredvalue
                    OR featuredd.charvalue = :featuredchar)
        )";
        $params['featuredfield'] = lom_metadata_interface::FIELD_IS_FEATURED;
        $params['featuredint'] = 1;
        $params['featuredvalue'] = '1';
        $params['featuredchar'] = '1';
    }

    /**
     * Format a Moodle course for the public API contract.
     *
     * @param \stdClass $course Course record.
     * @param string $apiversion Target API version.
     * @param array|null $metadata Preloaded page metadata.
     * @param array|null $lom Preloaded LOM payload.
     * @return array<string, mixed>
     */
    private function format_course(
        \stdClass $course,
        string $apiversion = '1.0',
        ?array $metadata = null,
        ?array $lom = null,
    ): array {
        global $CFG, $DB;

        if ($metadata === null) {
            require_once($CFG->dirroot . '/course/classes/list_element.php');
            $image = null;
            $element = new \core_course_list_element($course);
            foreach ($element->get_course_overviewfiles() as $file) {
                if (!$file->is_valid_image()) {
                    continue;
                }
                $image = \moodle_url::make_pluginfile_url(
                    $file->get_contextid(), $file->get_component(), $file->get_filearea(),
                    null, $file->get_filepath(), $file->get_filename(),
                )->out(false);
                break;
            }
            $metadata = [
                'course_image' => $image,
                'category_name' => (string) $DB->get_field('course_categories', 'name', ['id' => $course->category]),
                'modules_count' => (int) $DB->count_records('course_modules', ['course' => $course->id, 'deletioninprogress' => 0]),
            ];
        }
        $lom ??= $this->lommanager->export_lom((int) $course->id, $apiversion);
        $lifecycle = $lom['lifecycle'] ?? [];
        $technical = $lom['technical'] ?? [];
        $classification = $lom['classification'] ?? [];

        return [
            'id' => (int) $course->id,
            'shortname' => (string) $course->shortname,
            'fullname' => (string) $course->fullname,
            'summary' => (string) $course->summary,
            'category_id' => (int) $course->category,
            'category_name' => $metadata['category_name'],
            'course_image' => $metadata['course_image'],
            'lang' => (string) ($course->lang ?: current_language()),
            'view_url' => (new \moodle_url('/course/view.php', ['id' => (int) $course->id]))->out(false),
            'modules_count' => $metadata['modules_count'],
            'visible' => (bool) $course->visible,
            'startdate' => (int) $course->startdate,
            'enddate' => (int) $course->enddate,
            'timemodified' => (int) $course->timemodified,
            'custom_fields' => [
                lom_metadata_interface::FIELD_COURSE_STATUS =>
                    $lifecycle['status'] ?? null,
                lom_metadata_interface::FIELD_DELIVERY_MODE =>
                    $technical['delivery_mode'] ?? null,
                lom_metadata_interface::FIELD_DURATION =>
                    $technical['duration_hours'] ?? null,
                lom_metadata_interface::FIELD_INSTITUTION =>
                    $classification['institution'] ?? null,
                lom_metadata_interface::FIELD_IS_FEATURED =>
                    $classification['is_featured'] ?? false,
            ],
            'lom' => $lom,
        ];
    }
}

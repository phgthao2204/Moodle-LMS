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

use core\exception\coding_exception;
use core_course\customfield\course_handler;
use local_accees_sync_api\contract\lom_metadata_interface;
use local_accees_sync_api\contract\bulk_lom_metadata_interface;

/**
 * Canonical LOM-ACCEES metadata mapper for ACCEES-LMS REST API v2.
 *
 * Bridges the 9-category LOM-ACCEES payload structure with the 5 core Moodle
 * course custom fields in v1.0 while keeping v1.1 version compatibility.
 */
class lom_metadata_manager implements bulk_lom_metadata_interface {

    /** @var string[] Supported ACCEES API versions. */
    private const SUPPORTED_API_VERSIONS = ['1.0', '1.1'];

    /**
     * Read course metadata as a 9-category LOM-ACCEES associative structure.
     *
     * @param int $courseid Moodle course ID.
     * @param string $apiversion Target API version ('1.0' or '1.1').
     * @return array<string, mixed>
     * @throws coding_exception When an unsupported API version is requested.
     */
    public function export_lom(int $courseid, string $apiversion = '1.0'): array {
        global $DB;

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        return $this->export_lom_batch([$course], $apiversion)[$courseid];
    }

    /** @inheritDoc */
    public function export_lom_batch(array $courses, string $apiversion = '1.0'): array {
        global $DB;

        $this->assert_supported_version($apiversion);
        if (!$courses) {
            return [];
        }
        $ids = array_map(static fn(\stdClass $course): int => (int) $course->id, $courses);
        $fields = $this->get_custom_fields_batch($ids);
        [$insql, $params] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'lomcourse');
        $competencies = $DB->get_records_sql(
            "SELECT cc.id, cc.courseid, c.idnumber, f.idnumber AS frameworkcode
               FROM {competency_coursecomp} cc
               JOIN {competency} c ON c.id = cc.competencyid
               JOIN {competency_framework} f ON f.id = c.competencyframeworkid
              WHERE cc.courseid {$insql}
           ORDER BY cc.courseid ASC, cc.sortorder ASC, c.id ASC",
            $params,
        );
        $bycourse = [];
        foreach ($competencies as $competency) {
            $bycourse[$competency->courseid][] = $competency;
        }
        $result = [];
        foreach ($courses as $course) {
            $id = (int) $course->id;
            $result[$id] = $this->format_lom($course, $fields[$id] ?? [], $bycourse[$id] ?? [], $apiversion);
        }
        return $result;
    }

    /** Build the nine groups without any per-course database queries. */
    protected function format_lom(\stdClass $course, array $fields, array $competencies, string $apiversion): array {

        $status = $fields[self::FIELD_COURSE_STATUS] ?? null;
        $deliverymode = $fields[self::FIELD_DELIVERY_MODE] ?? null;
        $duration = $fields[self::FIELD_DURATION] ?? null;
        $institution = $fields[self::FIELD_INSTITUTION] ?? null;
        $isfeatured = (bool) ($fields[self::FIELD_IS_FEATURED] ?? false);

        $contribute = [];
        if ($institution !== null && $institution !== '') {
            $contribute[] = [
                'role' => 'author',
                'entity' => (string) $institution,
                'date' => date('Y-m-d', (int) ($course->timemodified ?: time())),
            ];
        }

        $educational = [
            'learning_resource_type' => ['course'],
            'intended_end_user_role' => ['learner'],
        ];
        if ($duration !== null && is_numeric($duration)) {
            $educational['typical_learning_time'] = 'PT' . $duration . 'H';
        }

        $codes = [];
        $levels = [];
        foreach ($competencies as $competency) {
            if (trim($competency->idnumber) !== '') {
                $codes[] = (string) $competency->idnumber;
            }
            // Read explicit level tokens only; a framework name alone does not imply a level.
            foreach ([$competency->idnumber, $competency->frameworkcode] as $code) {
                if (preg_match('/(?:^|[_.:\\s-])(A[12]|B[12]|C[12]|N[1-4]|BASIC|INTERMEDIATE|ADVANCED)$/i', $code, $match)) {
                    $levels[] = strtoupper($match[1]);
                    break;
                }
            }
        }
        $levels = array_values(array_unique($levels));
        if ($levels) {
            $educational['digcomp_level'] = implode(', ', $levels);
        }

        return [
            'schema_version' => 'LOM-ACCEES-1.0',
            'general' => [
                'identifier' => (string) $course->shortname,
                'title' => (string) $course->fullname,
                'language' => [(string) ($course->lang ?: current_language())],
                'description' => trim(strip_tags((string) $course->summary)),
                'keyword' => [],
            ],
            'lifecycle' => [
                'version' => '1.0',
                'status' => $status,
                'contribute' => $contribute,
            ],
            'metametadata' => [
                'metadata_schema' => ['LOMv1.0', 'ACCEES-LOMv1.0'],
            ],
            'technical' => [
                'format' => ['application/zip', 'text/html'],
                'duration_hours' => $duration,
                'delivery_mode' => $deliverymode,
            ],
            'educational' => $educational,
            'rights' => [
                'cost' => 'no',
                'copyright_and_other_restrictions' => 'yes',
                'license' => 'CC-BY-4.0',
            ],
            'relation' => [],
            'annotation' => [],
            'classification' => [
                'institution' => $institution,
                'is_featured' => $isfeatured,
                'competency_codes' => array_values(array_unique($codes)),
            ],
        ];
    }

    /**
     * Persist supported LOM-ACCEES fields into Moodle course custom fields.
     *
     * Supports partial updates: omitted fields remain untouched in Moodle DB.
     *
     * @param int $courseid Moodle course ID.
     * @param array<string, mixed> $lom Normalized LOM-ACCEES payload or custom_fields map.
     * @param string $apiversion Target API version ('1.0' or '1.1').
     * @return array<string, mixed> Effective stored custom field values.
     * @throws coding_exception When an unsupported API version is requested.
     * @throws \DomainException When input values or custom field definitions are invalid.
     */
    public function apply_lom(int $courseid, array $lom, string $apiversion = '1.0'): array {
        $this->assert_supported_version($apiversion);

        $mapping = [
            self::FIELD_COURSE_STATUS => ['lifecycle', 'status'],
            self::FIELD_DELIVERY_MODE => ['technical', 'delivery_mode'],
            self::FIELD_DURATION => ['technical', 'duration_hours'],
            self::FIELD_INSTITUTION => ['classification', 'institution'],
            self::FIELD_IS_FEATURED => ['classification', 'is_featured'],
        ];

        $rawcustomfields = $lom['custom_fields'] ?? [];
        if ($rawcustomfields instanceof \stdClass) {
            $rawcustomfields = (array) $rawcustomfields;
        }
        if (!is_array($rawcustomfields)) {
            throw new \DomainException('custom_fields must be an object or associative array.');
        }

        $values = [];
        foreach ($rawcustomfields as $key => $val) {
            if (!array_key_exists($key, $mapping)) {
                throw new \DomainException("Unsupported custom field: {$key}.");
            }
            $values[$key] = $val;
        }

        // Also accept flat top-level aliases if passed directly in $lom.
        foreach (array_keys($mapping) as $shortname) {
            if (array_key_exists($shortname, $lom) && !array_key_exists($shortname, $values)) {
                $values[$shortname] = $lom[$shortname];
            }
        }

        foreach ($mapping as $name => [$group, $key]) {
            if (!array_key_exists($group, $lom)) {
                continue;
            }
            $block = $lom[$group];
            if ($block instanceof \stdClass) {
                $block = (array) $block;
            }
            if (!is_array($block)) {
                throw new \DomainException("Invalid LOM block: {$group}.");
            }
            // Support legacy technical.duration alias in addition to technical.duration_hours.
            $matchedkey = array_key_exists($key, $block)
                ? $key
                : ($name === self::FIELD_DURATION && array_key_exists('duration', $block) ? 'duration' : null);

            if ($matchedkey !== null) {
                $blockval = $block[$matchedkey];
                if (array_key_exists($name, $values) && $values[$name] !== $blockval) {
                    throw new \DomainException("Conflicting values for {$name}.");
                }
                $values[$name] = $blockval;
            }
        }

        if (!empty($values)) {
            $handler = course_handler::create();
            $editable = [];
            foreach ($handler->get_editable_fields($courseid) as $field) {
                $editable[$field->get('shortname')] = true;
            }

            $before = $this->get_custom_fields($courseid);
            $data = ['id' => $courseid];

            foreach ($values as $name => $value) {
                if (empty($editable[$name])) {
                    throw new \DomainException("Custom field is missing or not editable: {$name}.");
                }

                if ($name === self::FIELD_IS_FEATURED) {
                    if (!is_bool($value) && $value !== 0 && $value !== 1) {
                        throw new \DomainException("Invalid boolean value for {$name}.");
                    }
                    $normalized = (bool) $value;
                    $saveval = $normalized ? 1 : 0;
                } else if ($name === self::FIELD_DURATION) {
                    if (is_int($value) || is_float($value)) {
                        $value = (string) $value;
                    }
                    if (!is_string($value) || preg_match('/^\d+(?:\.\d+)?$/D', trim($value)) !== 1) {
                        throw new \DomainException("Invalid numeric value for {$name}.");
                    }
                    $normalized = trim($value);
                    $saveval = $normalized;
                } else {
                    if (!is_string($value) || trim($value) === '' || \core_text::strlen(trim($value)) > 255) {
                        throw new \DomainException("Invalid string value for {$name}.");
                    }
                    $normalized = trim($value);
                    $saveval = $normalized;
                }

                if ($normalized !== ($before[$name] ?? null)) {
                    $data['customfield_' . $name] = $saveval;
                }
            }

            if (count($data) > 1) {
                foreach ($handler->get_instance_data($courseid) as $controller) {
                    $formkey = $controller->get_form_element_name();
                    if (array_key_exists($formkey, $data) && $controller->instance_form_validation($data, [])) {
                        throw new \DomainException("Custom field validation failed: {$formkey}.");
                    }
                }
                $handler->instance_form_save((object) $data);
            }
        }

        $effective = $this->get_custom_fields($courseid);
        return [
            self::FIELD_COURSE_STATUS => $effective[self::FIELD_COURSE_STATUS] ?? null,
            self::FIELD_DELIVERY_MODE => $effective[self::FIELD_DELIVERY_MODE] ?? null,
            self::FIELD_DURATION => $effective[self::FIELD_DURATION] ?? null,
            self::FIELD_INSTITUTION => $effective[self::FIELD_INSTITUTION] ?? null,
            self::FIELD_IS_FEATURED => (bool) ($effective[self::FIELD_IS_FEATURED] ?? false),
        ];
    }

    /**
     * Fetch the five ACCEES core custom fields stored for a course.
     *
     * @param int $courseid Moodle course ID.
     * @return array<string, mixed>
     */
    public function get_custom_fields(int $courseid): array {
        return $this->get_custom_fields_batch([$courseid])[$courseid] ?? [];
    }

    /** Read core custom fields once for all courses in a page. */
    private function get_custom_fields_batch(array $courseids): array {
        global $DB;

        $supported = [
            self::FIELD_COURSE_STATUS,
            self::FIELD_DELIVERY_MODE,
            self::FIELD_DURATION,
            self::FIELD_INSTITUTION,
            self::FIELD_IS_FEATURED,
        ];
        [$insql, $params] = $DB->get_in_or_equal($supported, SQL_PARAMS_NAMED, 'lomfield');
        [$coursesql, $courseparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'fieldcourse');
        $params += $courseparams;
        $sql = "SELECT d.id, d.instanceid, f.shortname, d.value, d.charvalue, d.intvalue
                  FROM {customfield_data} d
                  JOIN {customfield_field} f ON f.id = d.fieldid
                 WHERE d.instanceid {$coursesql}
                   AND f.shortname {$insql}";

        $result = [];
        foreach ($DB->get_records_sql($sql, $params) as $record) {
            if ($record->shortname === self::FIELD_IS_FEATURED) {
                $result[$record->instanceid][$record->shortname] =
                    (bool) ($record->intvalue ?? $record->value ?? $record->charvalue);
                continue;
            }
            $raw = $record->charvalue ?? $record->value ?? $record->intvalue;
            $result[$record->instanceid][$record->shortname] = ($raw === null || $raw === '') ? null : (string) $raw;
        }

        return $result;
    }

    /**
     * Validate API version parameter.
     *
     * @param string $apiversion Target API version.
     * @throws coding_exception When API version is unsupported.
     */
    private function assert_supported_version(string $apiversion): void {
        if (!in_array($apiversion, self::SUPPORTED_API_VERSIONS, true)) {
            throw new coding_exception("Unsupported LOM-ACCEES API version: {$apiversion}");
        }
    }
}

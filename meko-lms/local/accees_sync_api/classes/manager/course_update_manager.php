<?php
namespace local_accees_sync_api\manager;

/** Transactional partial update of course information and metadata. */
class course_update_manager {
    /** Apply a patch and return the persisted values. */
    public function update(int $id, \stdClass $body, string $version = '1.0'): array {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');

        $data = (object) ['id' => $id];
        $changed = [];
        $mapper = new lom_metadata_manager();
        $transaction = $DB->start_delegated_transaction();
        try {
            $course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
            foreach ((array) $body as $name => $value) {
                $valid = match ($name) {
                    'fullname' => is_string($value) && trim($value) !== '' && \core_text::strlen($value) <= 254,
                    'summary' => is_string($value),
                    'visible' => is_bool($value),
                    'startdate', 'enddate' => is_int($value) && $value >= 0,
                    'custom_fields', 'lom' => $value instanceof \stdClass,
                    'competency_codes' => is_array($value) && array_is_list($value),
                    default => false,
                };
                if (!$valid) {
                    throw new \InvalidArgumentException("Invalid field: {$name}.");
                }
                if (in_array($name, ['lom', 'custom_fields', 'competency_codes'], true)) {
                    continue;
                }
                $value = $name === 'summary' ? clean_text($value, (int) $course->summaryformat) : $value;
                if ($value !== match ($name) {
                    'visible' => (bool) $course->$name,
                    'startdate', 'enddate' => (int) $course->$name,
                    default => (string) $course->$name,
                }) {
                    $data->$name = $value;
                    $changed[] = $name;
                }
            }
            $start = $data->startdate ?? (int) $course->startdate;
            $end = $data->enddate ?? (int) $course->enddate;
            if ($end && $end < $start) {
                throw new \InvalidArgumentException('enddate must not precede startdate.');
            }
            $codes = $this->get_competency_codes($body);
            $competencies = [];
            foreach ($codes as $code) {
                // idnumber is unique within a framework, not necessarily across frameworks.
                $matches = $DB->get_records('competency', ['idnumber' => $code], 'id ASC', 'id', 0, 2);
                if (count($matches) !== 1) {
                    throw new \InvalidArgumentException('Unknown or ambiguous competency code: ' . $code . '.');
                }
                $competencies[] = (int) reset($matches)->id;
            }
            $before = $mapper->get_custom_fields($id);
            $lom = (array) ($body->lom ?? []);
            $lom['custom_fields'] = (array) ($body->custom_fields ?? []);
            $fields = $mapper->apply_lom($id, $lom, $version);
            foreach ($fields as $name => $value) {
                $previous = $before[$name] ?? ($name === $mapper::FIELD_IS_FEATURED ? false : null);
                if ($value !== $previous) {
                    $changed[] = "custom_fields.{$name}";
                }
            }
            $competencieschanged = false;
            foreach ($competencies as $competencyid) {
                if (!$DB->record_exists('competency_coursecomp', ['courseid' => $id, 'competencyid' => $competencyid])) {
                    if (!\core_competency\api::add_competency_to_course($id, $competencyid)) {
                        throw new \RuntimeException('Could not link the course competency.');
                    }
                    $competencieschanged = true;
                }
            }
            if ($competencieschanged) {
                $changed[] = property_exists($body, 'competency_codes')
                    ? 'competency_codes' : 'lom.classification.competency_codes';
            }
            if ($changed) {
                $data->startdate = $start;
                $data->enddate = $end;
                update_course($data);
            }
            $result = [
                'id' => $id,
                'shortname' => $course->shortname,
                'updated_fields' => $changed,
                'custom_fields' => $fields,
                'timemodified' => (int) $DB->get_field('course', 'timemodified', ['id' => $id], MUST_EXIST),
            ];
            $transaction->allow_commit();
            return $result;
        } catch (\Throwable $e) {
            $transaction->rollback($e);
            throw $e;
        }
    }

    /** Read an additive list of existing competency idnumbers from either supported location. */
    private function get_competency_codes(\stdClass $body): array {
        $top = property_exists($body, 'competency_codes');
        $classification = $body->lom->classification ?? null;
        if ($classification !== null && !$classification instanceof \stdClass) {
            throw new \InvalidArgumentException('lom.classification must be an object.');
        }
        $nested = $classification instanceof \stdClass && property_exists($classification, 'competency_codes');
        $normalise = static function (mixed $value): array {
            if (!is_array($value) || !array_is_list($value)) {
                throw new \InvalidArgumentException('competency_codes must be an array of idnumber strings.');
            }
            foreach ($value as $code) {
                if (!is_string($code) || trim($code) === '') {
                    throw new \InvalidArgumentException('Each competency code must be a non-empty string.');
                }
            }
            return array_values(array_unique($value));
        };
        $codes = $top ? $normalise($body->competency_codes) : [];
        if ($nested) {
            $nestedcodes = $normalise($classification->competency_codes);
            if ($top && (array_diff($codes, $nestedcodes) || array_diff($nestedcodes, $codes))) {
                throw new \InvalidArgumentException('Conflicting competency_codes values.');
            }
            $codes = $nestedcodes;
        }
        return $codes;
    }
}

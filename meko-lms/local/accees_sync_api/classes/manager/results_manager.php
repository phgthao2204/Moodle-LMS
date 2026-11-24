<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_accees_sync_api\manager;

use local_accees_sync_api\exception\api_exception;

defined('MOODLE_INTERNAL') || die();

class results_manager {
    /**
     * Read paginated results for active student enrolments in a course.
     *
     * @param int $courseid
     * @param array $filters
     * @return array
     */
    public function read(int $courseid, array $filters): array {
        global $DB;

        if ($courseid < 2) {
            throw new api_exception('COURSE_NOT_FOUND', 'Course was not found.', 404);
        }
        $course = $DB->get_record('course', ['id' => $courseid], '*', IGNORE_MISSING);
        if (!$course) {
            throw new api_exception('COURSE_NOT_FOUND', 'Course was not found.', 404);
        }

        $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student'], IGNORE_MISSING);
        if (!$studentroleid) {
            throw new api_exception('STUDENT_ROLE_NOT_FOUND', 'The Moodle student role is not configured.', 500);
        }

        $this->validate_user_filter($courseid, $filters);
        $params = [];
        $where = $this->get_learner_where($courseid, $filters, $params);
        $total = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT u.id)
               FROM {user} u
               JOIN {user_enrolments} ue ON ue.userid = u.id AND ue.status = :active
               JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = :courseid AND e.status = :enabled
               JOIN {role_assignments} ra ON ra.userid = u.id AND ra.contextid = :contextid AND ra.roleid = :studentrole
              WHERE u.deleted = 0 AND $where",
            $params + [
                'active' => ENROL_USER_ACTIVE,
                'enabled' => ENROL_INSTANCE_ENABLED,
                'contextid' => \context_course::instance($courseid)->id,
                'studentrole' => $studentroleid,
            ]
        );

        $users = $DB->get_records_sql(
            "SELECT DISTINCT u.id, u.username
               FROM {user} u
               JOIN {user_enrolments} ue ON ue.userid = u.id AND ue.status = :active
               JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = :courseid AND e.status = :enabled
               JOIN {role_assignments} ra ON ra.userid = u.id AND ra.contextid = :contextid AND ra.roleid = :studentrole
              WHERE u.deleted = 0 AND $where
           ORDER BY u.id ASC",
            $params + [
                'active' => ENROL_USER_ACTIVE,
                'enabled' => ENROL_INSTANCE_ENABLED,
                'contextid' => \context_course::instance($courseid)->id,
                'studentrole' => $studentroleid,
            ],
            ($filters['page'] - 1) * $filters['per_page'],
            $filters['per_page']
        );

        $items = [];
        foreach ($users as $user) {
            $items[] = $this->build_item($course, $user);
        }

        return [
            'course_id' => (int)$course->id,
            'shortname' => $course->shortname,
            'items' => $items,
            'pagination' => [
                'page' => $filters['page'],
                'per_page' => $filters['per_page'],
                'total' => (int)$total,
                'total_pages' => $total === 0 ? 0 : (int)ceil($total / $filters['per_page']),
            ],
        ];
    }

    /**
     * Get one learner's current result snapshot for a webhook.
     *
     * @param int $courseid
     * @param int $userid
     * @return array
     */
    public function read_for_user(int $courseid, int $userid): array {
        global $DB;

        $course = $DB->get_record('course', ['id' => $courseid], '*', IGNORE_MISSING);
        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], 'id,username', IGNORE_MISSING);
        if (!$course || !$user) {
            throw new api_exception('USER_NOT_FOUND', 'The course or user was not found.', 404);
        }
        return $this->build_item($course, $user);
    }

    /**
     * @param \stdClass $course
     * @param \stdClass $user
     * @return array
     */
    private function build_item(\stdClass $course, \stdClass $user): array {
        global $CFG, $DB;

        require_once($CFG->libdir . '/completionlib.php');

        $completion = $DB->get_record('course_completions', [
            'course' => $course->id,
            'userid' => $user->id,
        ], 'timecompleted', IGNORE_MISSING);
        $timecompleted = $completion ? (int)$completion->timecompleted : 0;
        $completed = $timecompleted > 0;

        $completioninfo = new \completion_info($course);
        $eligiblemodules = 0;
        $completedmodules = 0;
        if ($completioninfo->is_enabled()) {
            $modinfo = get_fast_modinfo($course);
            foreach ($modinfo->get_cms() as $cm) {
                if (!$completioninfo->is_enabled($cm)) {
                    continue;
                }
                $eligiblemodules++;
                $data = $completioninfo->get_data($cm, false, (int)$user->id);
                if (!empty($data->completionstate)) {
                    $completedmodules++;
                }
            }
        }
        $progress = $completed ? 100.0 : ($eligiblemodules > 0
            ? round(($completedmodules / $eligiblemodules) * 100, 1)
            : 0.0);

        $gradeitem = $DB->get_record('grade_items', [
            'courseid' => $course->id,
            'itemtype' => 'course',
        ], 'id,grademax', IGNORE_MISSING);
        $grade = $gradeitem ? $DB->get_record('grade_grades', [
            'itemid' => $gradeitem->id,
            'userid' => $user->id,
        ], 'finalgrade', IGNORE_MISSING) : false;
        $rawgrade = $grade && $grade->finalgrade !== null ? (float)$grade->finalgrade : null;
        $maxgrade = $gradeitem ? (float)$gradeitem->grademax : 0.0;
        $percentage = $rawgrade !== null && $maxgrade > 0
            ? round(($rawgrade / $maxgrade) * 100, 1)
            : null;

        $badges = $DB->get_records_sql(
            'SELECT b.id AS badge_id, b.name, bi.dateissued, bi.uniquehash
               FROM {badge_issued} bi
               JOIN {badge} b ON b.id = bi.badgeid
              WHERE bi.userid = :userid AND (b.courseid = :courseid OR b.courseid = 0)
           ORDER BY bi.dateissued ASC, bi.id ASC',
            ['userid' => $user->id, 'courseid' => $course->id]
        );
        $badgeitems = [];
        foreach ($badges as $badge) {
            $badgeitems[] = [
                'badge_id' => (int)$badge->badge_id,
                'name' => $badge->name,
                'issued_at' => (int)$badge->dateissued,
                'uniquehash' => $badge->uniquehash,
                'assertion_url' => (new \moodle_url('/badges/assertion.php', ['b' => $badge->uniquehash]))->out(false),
            ];
        }

        return [
            'user_id' => (int)$user->id,
            'username' => $user->username,
            'progress_percent' => $progress,
            'completed' => $completed,
            'timecompleted' => $timecompleted,
            'final_grade' => [
                'raw' => $rawgrade,
                'max' => $maxgrade,
                'percentage' => $percentage,
            ],
            'badges' => $badgeitems,
        ];
    }

    /**
     * Validate an optional user filter against active student enrolments.
     *
     * @param int $courseid
     * @param array $filters
     * @return void
     */
    private function validate_user_filter(int $courseid, array $filters): void {
        global $DB;

        if (!isset($filters['user_id']) && !isset($filters['username'])) {
            return;
        }
        $field = isset($filters['user_id']) ? 'u.id = :filteruserid' : 'LOWER(u.username) = :filterusername';
        $params = isset($filters['user_id'])
            ? ['filteruserid' => $filters['user_id']]
            : ['filterusername' => \core_text::strtolower($filters['username'])];
        $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student'], IGNORE_MISSING);
        if (!$studentroleid) {
            throw new api_exception('STUDENT_ROLE_NOT_FOUND', 'The Moodle student role is not configured.', 500);
        }
        $exists = $DB->record_exists_sql(
            "SELECT 1
               FROM {user} u
               JOIN {user_enrolments} ue ON ue.userid = u.id AND ue.status = :active
               JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = :courseid AND e.status = :enabled
               JOIN {role_assignments} ra ON ra.userid = u.id AND ra.contextid = :contextid AND ra.roleid = :studentrole
              WHERE u.deleted = 0 AND $field",
            $params + [
                'active' => ENROL_USER_ACTIVE,
                'courseid' => $courseid,
                'enabled' => ENROL_INSTANCE_ENABLED,
                'contextid' => \context_course::instance($courseid)->id,
                'studentrole' => $studentroleid,
            ]
        );
        if (!$exists) {
            throw new api_exception('USER_NOT_FOUND', 'The learner was not found in this course.', 404);
        }
    }

    /**
     * Build the optional learner and completion filter SQL.
     *
     * @param int $courseid
     * @param array $filters
     * @param array $params
     * @return string
     */
    private function get_learner_where(int $courseid, array $filters, array &$params): string {
        $params = ['courseid' => $courseid];
        $conditions = [];
        if (isset($filters['user_id'])) {
            $conditions[] = 'u.id = :filteruserid';
            $params['filteruserid'] = $filters['user_id'];
        } else if (isset($filters['username'])) {
            $conditions[] = 'LOWER(u.username) = :filterusername';
            $params['filterusername'] = \core_text::strtolower($filters['username']);
        }
        if ($filters['completed_only']) {
            $conditions[] = 'EXISTS (SELECT 1 FROM {course_completions} cc WHERE cc.course = :completioncourse AND cc.userid = u.id AND cc.timecompleted > 0)';
            $params['completioncourse'] = $courseid;
        }
        return $conditions ? implode(' AND ', $conditions) : '1 = 1';
    }
}

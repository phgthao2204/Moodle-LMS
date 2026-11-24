<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License, either version 3 of the License,
// or (at your option) any later version.

namespace local_accees_sync_api\observer;

defined('MOODLE_INTERNAL') || die();

class completion_observer {
    /**
     * Queue a webhook when a learner completes a course.
     *
     * @param \core\event\course_completed $event
     * @return void
     */
    public static function on_course_completed(\core\event\course_completed $event): void {
        self::queue_event('course_completed', (int)$event->courseid, (int)$event->relateduserid, (int)$event->timecreated);
    }

    /**
     * Queue a webhook when a learner earns a course badge.
     *
     * @param \core\event\badge_awarded $event
     * @return void
     */
    public static function on_badge_awarded(\core\event\badge_awarded $event): void {
        global $DB;

        $badge = $DB->get_record('badge', ['id' => $event->objectid], 'courseid', IGNORE_MISSING);
        $courseid = $badge ? (int)$badge->courseid : (int)$event->courseid;
        if ($courseid < 2) {
            return;
        }
        self::queue_event('badge_awarded', $courseid, (int)$event->relateduserid, (int)$event->timecreated);
    }

    /**
     * Queue the asynchronous task when webhook delivery is enabled.
     *
     * @param string $eventtype
     * @param int $courseid
     * @param int $userid
     * @param int $timecreated
     * @return void
     */
    private static function queue_event(string $eventtype, int $courseid, int $userid, int $timecreated): void {
        if (empty(get_config('local_accees_sync_api', 'webhook_enabled'))) {
            return;
        }

        $task = new \local_accees_sync_api\task\send_webhook_task();
        $task->set_custom_data([
            'event_id' => 'evt_' . bin2hex(random_bytes(12)),
            'event_type' => $eventtype,
            'course_id' => $courseid,
            'user_id' => $userid,
            'occurred_at' => date('c', $timecreated),
        ]);
        if (!\core\task\manager::queue_adhoc_task($task)) {
            error_log('ACCEES result webhook task could not be queued for ' . $eventtype . '.');
        }
    }
}

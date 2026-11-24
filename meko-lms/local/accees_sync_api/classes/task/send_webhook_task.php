<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License, either version 3 of the License,
// or (at your option) any later version.

namespace local_accees_sync_api\task;

use local_accees_sync_api\manager\results_manager;

defined('MOODLE_INTERNAL') || die();

class send_webhook_task extends \core\task\adhoc_task {
    /**
     * Send the signed learner result webhook.
     *
     * @return void
     */
    public function execute(): void {
        global $DB;

        if (empty(get_config('local_accees_sync_api', 'webhook_enabled'))) {
            return;
        }

        $url = trim((string)get_config('local_accees_sync_api', 'webhook_url'));
        $secret = (string)get_config('local_accees_sync_api', 'webhook_secret');
        if ($url === '' || $secret === '') {
            error_log('ACCEES result webhook is enabled but URL or signing secret is missing.');
            throw new \moodle_exception('invalidaccess', 'error', '', 'Webhook URL or secret is not configured.');
        }

        $data = $this->get_custom_data();
        foreach (['event_id', 'event_type', 'course_id', 'user_id', 'occurred_at'] as $required) {
            if (!isset($data->{$required})) {
                throw new \moodle_exception('invalidrecord', 'error', '', 'Webhook task is missing ' . $required . '.');
            }
        }

        $course = $DB->get_record('course', ['id' => (int)$data->course_id], 'id,shortname', MUST_EXIST);
        $result = (new results_manager())->read_for_user((int)$course->id, (int)$data->user_id);
        $payload = [
            'event_id' => (string)$data->event_id,
            'event_type' => (string)$data->event_type,
            'occurred_at' => (string)$data->occurred_at,
            'course_id' => (int)$course->id,
            'shortname' => $course->shortname,
            'user_id' => (int)$result['user_id'],
            'username' => $result['username'],
            'progress_percent' => $result['progress_percent'],
            'final_grade' => $result['final_grade']['raw'],
            'badges' => $result['badges'],
        ];
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $signature = 'sha256=' . hash_hmac('sha256', $body, $secret);

        $client = new \core\http_client();
        $response = $client->post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-ACCEES-Signature' => $signature,
            ],
            'body' => $body,
            'connect_timeout' => 5,
            'timeout' => 15,
            'http_errors' => false,
        ]);

        $httpstatus = $response->getStatusCode();
        if ($httpstatus < 200 || $httpstatus >= 300) {
            error_log('ACCEES result webhook delivery failed with HTTP status ' . $httpstatus . '.');
            throw new \RuntimeException('Webhook delivery failed.');
        }

        error_log('ACCEES result webhook delivered: ' . $data->event_id . ' (HTTP ' . $httpstatus . ').');
    }
}

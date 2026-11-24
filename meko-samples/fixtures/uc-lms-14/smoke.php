<?php
define('CLI_SCRIPT', true);
require(getenv('MOODLE_CONFIG') ?: '/var/www/html/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->libdir . '/gradelib.php');

\core\session\manager::set_user(get_admin());
$base = rtrim(getenv('ACCEES_API_BASE') ?: $CFG->wwwroot, '/');
$host = getenv('ACCEES_HTTP_HOST');
$headers = $host ? ["Host: {$host}"] : [];
$failed = 0;

function uc14_check(string $name, bool $passed): void {
    global $failed;
    $failed += $passed ? 0 : 1;
    echo ($passed ? 'PASS ' : 'FAIL ') . $name . "\n";
}

function uc14_request(string $url, array $headers, ?array $form = null): array {
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ]);
    if ($form !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($form));
    }
    $raw = curl_exec($curl);
    if ($raw === false) {
        throw new \RuntimeException(curl_error($curl));
    }
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    return [$status, json_decode($raw, true)];
}

$clientmanager = \core\di::get(\core\oauth2\server\client_manager::class);
$client = $DB->get_record('oauth2_server_clients', ['name' => 'accees-dev-client'], '*', MUST_EXIST);
$secret = $clientmanager->create_secret((int)$client->id);
$course = null;
$limitedtoken = null;
$badgetest = null;
$oldsettings = [];
foreach (['webhook_enabled', 'webhook_url', 'webhook_secret'] as $key) {
    $oldsettings[$key] = get_config('local_accees_sync_api', $key);
}
try {
    [$status, $auth] = uc14_request("{$base}/oauth2/token", $headers, [
        'grant_type' => 'client_credentials',
        'client_id' => $client->clientidentifier,
        'client_secret' => $secret,
        'scope' => 'local_accees_sync_api:result:read',
    ]);
    if ($status !== 200 || empty($auth['access_token'])) {
        throw new \RuntimeException('OAuth Client Credentials failed: HTTP ' . $status . ' ' . json_encode($auth));
    }
    uc14_check('OAuth Client Credentials HTTP 200', true);
    $authheaders = [...$headers, 'Authorization: Bearer ' . $auth['access_token']];
    $course = create_course((object)[
        'shortname' => 'UC14-QA-' . bin2hex(random_bytes(4)),
        'fullname' => 'UC-LMS-14 smoke course',
        'category' => $DB->get_field_sql('SELECT MIN(id) FROM {course_categories}'),
        'enablecompletion' => 1,
    ]);
    $student = $DB->get_record('user', ['username' => 'accees_student1'], '*', MUST_EXIST);
    $plugin = enrol_get_plugin('manual');
    $instanceid = $DB->get_field('enrol', 'id', ['courseid' => $course->id, 'enrol' => 'manual']);
    $instance = $DB->get_record('enrol', ['id' => $instanceid ?: $plugin->add_instance($course)], '*', MUST_EXIST);
    $plugin->enrol_user($instance, $student->id, $DB->get_field('role', 'id', ['shortname' => 'student']));
    $gradeitem = new \grade_item((object)[
        'courseid' => $course->id, 'itemtype' => 'course', 'grademax' => 100, 'grademin' => 0,
    ], false);
    $gradeitem->insert();
    $grade = new \grade_grade((object)[
        'itemid' => $gradeitem->id, 'userid' => $student->id, 'finalgrade' => 80,
    ], false);
    $grade->insert();
    $patsecret = bin2hex(random_bytes(32));
    $limitedtoken = (new \core\api\repository\api_token_repository())->create_token(
        'UC-LMS-14 capability check', $patsecret, (int)$student->id,
        ['local_accees_sync_api:result:read'], null, time() + 300,
    );
    $pat = 'pat_' . rtrim(base64_encode($limitedtoken->get_id() . '/' . $patsecret), '=');
    $api = "{$base}/api/rest/v2/local_accees_sync_api/courses";
    $target = "{$api}/{$course->id}/results";
    $missingcourse = (int)$DB->get_field_sql('SELECT MAX(id) FROM {course}') + 1;
    $cases = [
        ['missing authentication', $target, $headers, 401, 'UNAUTHORIZED'],
        ['missing capability', $target, [...$headers, "Authorization: Bearer {$pat}"], 403, 'FORBIDDEN'],
        ['invalid bearer', $target, [...$headers, 'Authorization: Bearer invalid'], 401, null],
        ['missing course', "{$api}/{$missingcourse}/results", $authheaders, 404, 'COURSE_NOT_FOUND'],
        ['invalid page', "{$target}?page=0", $authheaders, 400, 'INVALID_FIELD'],
        ['invalid page size', "{$target}?per_page=101", $authheaders, 400, 'INVALID_FIELD'],
        ['invalid completion filter', "{$target}?completed_only=invalid", $authheaders, 400, 'INVALID_FIELD'],
        ['ambiguous learner', "{$target}?user_id={$student->id}&username={$student->username}", $authheaders,
            400, 'INVALID_FIELD'],
        ['missing learner', "{$target}?username=uc14-nonexistent", $authheaders, 404, 'USER_NOT_FOUND'],
        ['unsupported version', $target, [...$authheaders, 'X-ACCEES-API-Version: 2.0'],
            400, 'UNSUPPORTED_API_VERSION'],
    ];
    foreach ($cases as [$name, $url, $requestheaders, $expected, $error]) {
        [$status, $json] = uc14_request($url, $requestheaders);
        uc14_check("{$name}: HTTP {$status}", $status === $expected
            && ($error === null || ($json['error']['code'] ?? null) === $error));
        if ($status !== $expected) {
            echo json_encode($json) . "\n";
        }
    }
    [$status, $json] = uc14_request($target, $authheaders);
    echo 'Results: HTTP ' . $status . ' ' . json_encode($json) . "\n";
    $expecteditem = [
        'user_id' => (int)$student->id, 'username' => $student->username,
        'progress_percent' => 0, 'completed' => false, 'timecompleted' => 0,
        'final_grade' => ['raw' => 80, 'max' => 100, 'percentage' => 80], 'badges' => [],
    ];
    uc14_check('results payload HTTP 200', $status === 200 && ($json['items'] ?? []) == [$expecteditem]
        && $json['course_id'] === (int)$course->id
        && $json['pagination'] === ['page' => 1, 'per_page' => 50, 'total' => 1, 'total_pages' => 1]);
    foreach (["user_id={$student->id}", "username={$student->username}", 'page=2', 'completed_only=true'] as $query) {
        [$status, $json] = uc14_request("{$target}?{$query}", $authheaders);
        $count = str_starts_with($query, 'page=') || $query === 'completed_only=true' ? 0 : 1;
        uc14_check("filter {$query}", $status === 200 && count($json['items'] ?? []) === $count);
    }
    $completion = (object)[
        'course' => $course->id, 'userid' => $student->id, 'timeenrolled' => time(),
        'timestarted' => time(), 'timecompleted' => time(),
    ];
    $completion->id = $DB->insert_record('course_completions', $completion);
    [$status, $json] = uc14_request("{$target}?completed_only=true", $authheaders);
    uc14_check('completed result', $status === 200 && count($json['items'] ?? []) === 1
        && $json['items'][0]['completed'] === true && (float)$json['items'][0]['progress_percent'] === 100.0);

    [$status, $schema] = uc14_request("{$base}/api/rest/v2/openapi.json", $headers);
    $operation = $schema['paths']['/local_accees_sync_api/courses/{courseid}/results']['get'] ?? [];
    uc14_check('OpenAPI route and result scope', $status === 200
        && ($operation['security'][0]['oauth2'] ?? []) === ['local_accees_sync_api:result:read']);
    [$status, $wrongscope] = uc14_request("{$base}/oauth2/token", $headers, [
        'grant_type' => 'client_credentials', 'client_id' => $client->clientidentifier,
        'client_secret' => $secret, 'scope' => 'local_accees_sync_api:course:read',
    ]);
    [$status, $json] = uc14_request($target, [...$headers, 'Authorization: Bearer ' . $wrongscope['access_token']]);
    uc14_check("wrong OAuth scope rejected by native middleware HTTP {$status}", $status === 401
        && ($json['error'] ?? null) === 'access_denied');

    set_config('webhook_enabled', 1, 'local_accees_sync_api');
    set_config('webhook_url', 'http://127.0.0.1:8099', 'local_accees_sync_api');
    set_config('webhook_secret', 'uc14-temporary-test-secret', 'local_accees_sync_api');
    $CFG->curlsecurityblockedhosts = '';
    $CFG->curlsecurityallowedport = '8099';
    $event = \core\event\course_completed::create_from_completion($completion);
    $event->trigger();
    $tasks = $DB->get_records('task_adhoc', ['component' => 'local_accees_sync_api']);
    $taskrecord = null;
    foreach ($tasks as $record) {
        if ((json_decode($record->customdata)->course_id ?? null) === (int)$course->id) {
            $taskrecord = $record;
        }
    }
    uc14_check('completion event queues task', $taskrecord !== null);
    if ($taskrecord === null) {
        throw new \RuntimeException('Completion observer did not queue webhook.');
    }
    $task = new \local_accees_sync_api\task\send_webhook_task();
    $task->set_custom_data(json_decode($taskrecord->customdata));
    $task->execute();
    $received = json_decode(file_get_contents('/tmp/uc14-webhook.json'), true);
    $payload = json_decode($received['body'], true);
    uc14_check('webhook HTTP delivery and HMAC', hash_equals(
        'sha256=' . hash_hmac('sha256', $received['body'], 'uc14-temporary-test-secret'), $received['signature'])
        && $payload['event_type'] === 'course_completed' && $payload['course_id'] === (int)$course->id
        && $payload['user_id'] === (int)$student->id && $payload['progress_percent'] === 100);
    touch('/tmp/uc14-reject-webhook');
    $rejected = false;
    try {
        $task->execute();
    } catch (\RuntimeException $exception) {
        $rejected = true;
    } finally {
        unlink('/tmp/uc14-reject-webhook');
    }
    uc14_check('HTTP 503 propagates for adhoc retry', $rejected);
    $task->execute();
    $retried = json_decode(file_get_contents('/tmp/uc14-webhook.json'), true);
    uc14_check('retry preserves event id', json_decode($retried['body'], true)['event_id'] === $payload['event_id']);

    $badgetest = $DB->insert_record('badge', (object)[
        'name' => 'UC14 QA badge', 'usercreated' => get_admin()->id, 'usermodified' => get_admin()->id,
        'issuername' => 'UC14 QA', 'issuerurl' => $CFG->wwwroot, 'type' => 2, 'courseid' => $course->id,
        'message' => '', 'messagesubject' => '',
    ]);
    $issuedid = $DB->insert_record('badge_issued', (object)[
        'badgeid' => $badgetest, 'userid' => $student->id, 'uniquehash' => bin2hex(random_bytes(20)),
        'dateissued' => time(),
    ]);
    \core\event\badge_awarded::create([
        'objectid' => $badgetest, 'relateduserid' => $student->id,
        'context' => \context_course::instance($course->id),
        'other' => ['badgeissuedid' => $issuedid],
    ])->trigger();
    $badgetask = null;
    foreach ($DB->get_records('task_adhoc', ['component' => 'local_accees_sync_api']) as $record) {
        $data = json_decode($record->customdata);
        if (($data->course_id ?? null) === (int)$course->id && $data->event_type === 'badge_awarded') {
            $badgetask = $data;
        }
    }
    uc14_check('badge event queues task', $badgetask !== null);
    if ($badgetask !== null) {
        $task->set_custom_data($badgetask);
        $task->execute();
        $received = json_decode(file_get_contents('/tmp/uc14-webhook.json'), true);
        $payload = json_decode($received['body'], true);
        uc14_check('badge webhook payload and HMAC', $payload['event_type'] === 'badge_awarded'
            && $payload['badges'][0]['badge_id'] === (int)$badgetest
            && hash_equals('sha256=' . hash_hmac('sha256', $received['body'], 'uc14-temporary-test-secret'),
                $received['signature']));
        [$status, $json] = uc14_request($target, $authheaders);
        uc14_check('pull API includes issued badge', $status === 200
            && $json['items'][0]['badges'][0]['badge_id'] === (int)$badgetest);
    }
    set_config('webhook_enabled', 0, 'local_accees_sync_api');
    $before = $DB->count_records('task_adhoc', ['component' => 'local_accees_sync_api']);
    \local_accees_sync_api\observer\completion_observer::on_course_completed($event);
    uc14_check('disabled webhook does not queue', $before === $DB->count_records('task_adhoc',
        ['component' => 'local_accees_sync_api']));
} finally {
    foreach ($oldsettings as $key => $value) {
        $value === false ? unset_config($key, 'local_accees_sync_api') : set_config($key, $value, 'local_accees_sync_api');
    }
    foreach ($clientmanager->get_secrets((int)$client->id) as $entry) {
        if (password_verify($secret, $entry->secret)) {
            $clientmanager->revoke_secret((int)$entry->id);
        }
    }
    if ($limitedtoken !== null) {
        $DB->delete_records('rest_api_tokens', ['id' => $limitedtoken->get_id()]);
    }
    if ($course !== null) {
        if ($badgetest !== null) {
            $DB->delete_records('badge_issued', ['badgeid' => $badgetest]);
            $DB->delete_records('badge', ['id' => $badgetest]);
        }
        foreach ($DB->get_records('task_adhoc', ['component' => 'local_accees_sync_api']) as $record) {
            if ((json_decode($record->customdata)->course_id ?? null) === (int)$course->id) {
                $DB->delete_records('task_adhoc', ['id' => $record->id]);
            }
        }
        delete_course($course, false);
    }
}
exit($failed ? 1 : 0);

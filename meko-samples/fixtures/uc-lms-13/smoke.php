<?php
define('CLI_SCRIPT', true);
require(getenv('MOODLE_CONFIG') ?: '/var/www/html/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/enrollib.php');

\core\session\manager::set_user(get_admin());
$base = rtrim(getenv('ACCEES_API_BASE') ?: $CFG->wwwroot, '/');
$host = getenv('ACCEES_HTTP_HOST');
$headers = $host ? ["Host: {$host}"] : [];

function uc13_request(string $url, string $method, array $headers, ?string $body = null): array {
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ]);
    if ($body !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
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
$secret = $clientmanager->create_secret((int) $client->id);
$course = null;
$limitedtoken = null;
$failed = 0;
try {
    [$status, $auth] = uc13_request("{$base}/oauth2/token", 'POST',
        [...$headers, 'Content-Type: application/x-www-form-urlencoded'], http_build_query([
            'grant_type' => 'client_credentials',
            'client_id' => $client->clientidentifier,
            'client_secret' => $secret,
            'scope' => 'local_accees_sync_api:enrol:manage',
        ]));
    if ($status !== 200 || empty($auth['access_token'])) {
        throw new \RuntimeException("OAuth Client Credentials failed: HTTP {$status} " . json_encode($auth));
    }
    echo "OAuth Client Credentials: HTTP 200\n";
    $authheaders = [...$headers, 'Authorization: Bearer ' . $auth['access_token'], 'Content-Type: application/json'];
    $course = create_course((object) [
        'shortname' => 'UC13-QA-' . bin2hex(random_bytes(4)),
        'fullname' => 'UC-LMS-13 smoke course',
        'category' => $DB->get_field_sql('SELECT MIN(id) FROM {course_categories}'),
    ]);
    $student = $DB->get_record('user', ['username' => 'accees_student1'], '*', MUST_EXIST);
    $plugin = enrol_get_plugin('manual');
    $instances = $DB->get_records('enrol', ['courseid' => $course->id, 'enrol' => 'manual']);
    $instance = reset($instances);
    if (!$instance) {
        $instance = $DB->get_record('enrol', ['id' => $plugin->add_instance($course)], '*', MUST_EXIST);
    }
    $plugin->enrol_user($instance, $student->id, $DB->get_field('role', 'id', ['shortname' => 'student']));
    $plugin->update_user_enrol($instance, $student->id, ENROL_USER_SUSPENDED);
    $DB->set_field('enrol', 'status', ENROL_INSTANCE_DISABLED, ['id' => $instance->id]);
    $selfplugin = enrol_get_plugin('self');
    $selfinstance = $DB->get_record('enrol', ['id' => $selfplugin->add_instance($course)], '*', MUST_EXIST);
    $selfplugin->enrol_user($selfinstance, $student->id);
    $api = "{$base}/api/rest/v2/local_accees_sync_api/courses";
    $target = "{$api}/{$course->id}/enrolments";
    $missingcourse = (int) $DB->get_field_sql('SELECT MAX(id) FROM {course}') + 1;
    $missinguser = (int) $DB->get_field_sql('SELECT MAX(id) FROM {user}') + 1;
    $success = [
        'course_id' => (int) $course->id,
        'user_id' => (int) $student->id,
        'username' => $student->username,
        'enrolment_status' => 'unenrolled',
    ];
    $patsecret = bin2hex(random_bytes(32));
    $limitedtoken = (new \core\api\repository\api_token_repository())->create_token(
        'UC-LMS-13 capability check', $patsecret, (int) $student->id,
        ['local_accees_sync_api:enrol:manage'], null, time() + 300,
    );
    $pat = 'pat_' . rtrim(base64_encode($limitedtoken->get_id() . '/' . $patsecret), '=');
    $cases = [
        ['unauthenticated', $target, $headers, null, 401, 'UNAUTHORIZED'],
        ['missing capability', "{$target}?user_id={$student->id}",
            [...$headers, "Authorization: Bearer {$pat}"], null, 403, 'FORBIDDEN'],
        ['missing identifier', $target, $authheaders, null, 400, 'MISSING_IDENTIFIER'],
        ['ambiguous identifier', "{$target}?user_id={$student->id}&username={$student->username}",
            $authheaders, null, 400, 'MISSING_IDENTIFIER'],
        ['invalid body identifier', $target, $authheaders, '{"user_id":true}', 400, 'MISSING_IDENTIFIER'],
        ['invalid JSON', $target, $authheaders, '{', 400, 'MISSING_IDENTIFIER'],
        ['missing course', "{$api}/{$missingcourse}/enrolments?user_id={$student->id}", $authheaders, null,
            404, 'COURSE_NOT_FOUND'],
        ['missing user', "{$target}?user_id={$missinguser}", $authheaders, null, 404, 'USER_NOT_FOUND'],
        ['query username', "{$target}?username={$student->username}", $authheaders, null, 200, $success],
        ['repeat delete', "{$target}?user_id={$student->id}", $authheaders, null, 404, 'ENROLMENT_NOT_FOUND'],
        ['query ignore missing', "{$target}?user_id={$student->id}&ignore_missing=true", $authheaders, null, 200, $success],
        ['body ignore missing', $target, $authheaders,
            json_encode(['username' => $student->username, 'ignore_missing' => true]), 200, $success],
        ['explicit ignore false', "{$target}?user_id={$student->id}&ignore_missing=false", $authheaders, null,
            404, 'ENROLMENT_NOT_FOUND'],
        ['body user_id', $target, $authheaders, json_encode(['user_id' => (int) $student->id]), 200, $success, true],
        ['body username', $target, $authheaders, json_encode(['username' => $student->username]), 200, $success, true],
        ['query user_id', "{$target}?user_id={$student->id}", $authheaders, null, 200, $success, true],
        ['query identifier precedence', "{$target}?user_id={$student->id}", $authheaders,
            '{"username":"does-not-exist"}', 200, $success, true],
    ];
    foreach ($cases as $case) {
        [$name, $url, $requestheaders, $body, $expected, $payload] = $case;
        if ($case[6] ?? false) {
            $plugin->enrol_user($instance, $student->id);
        }
        [$status, $json] = uc13_request($url, 'DELETE', $requestheaders, $body);
        $passed = $status === $expected && (is_array($payload) ? $json === $payload
            : ($json['error']['code'] ?? null) === $payload);
        if ($expected === 200) {
            $passed = $passed
                && !$DB->record_exists('user_enrolments', ['enrolid' => $instance->id, 'userid' => $student->id])
                && $DB->record_exists('user_enrolments', ['enrolid' => $selfinstance->id, 'userid' => $student->id]);
        }
        echo json_encode(['case' => $name, 'passed' => $passed, 'http' => $status, 'response' => $json],
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
        $failed += (int) !$passed;
    }
    [$status, $schema] = uc13_request("{$base}/api/rest/v2/openapi.json", 'GET', $headers);
    $operation = $schema['paths']['/local_accees_sync_api/courses/{courseid}/enrolments']['delete'] ?? [];
    $passed = $status === 200 && isset($operation['responses']['200'])
        && array_column($operation['parameters'] ?? [], 'name') === ['courseid', 'username', 'user_id', 'ignore_missing']
        && ($operation['security'][0]['oauth2'] ?? []) === ['local_accees_sync_api:enrol:manage'];
    echo json_encode(['case' => 'OpenAPI route and scope', 'passed' => $passed, 'http' => $status], JSON_THROW_ON_ERROR) . PHP_EOL;
    $failed += (int) !$passed;
} finally {
    if ($limitedtoken) {
        $DB->delete_records('rest_api_tokens', ['id' => $limitedtoken->get_id()]);
    }
    foreach ($clientmanager->get_secrets((int) $client->id) as $entry) {
        if (password_verify($secret, $entry->secret)) {
            $clientmanager->revoke_secret((int) $entry->id);
        }
    }
    if ($course) {
        delete_course($course, false);
    }
}
exit((int) ($failed > 0));

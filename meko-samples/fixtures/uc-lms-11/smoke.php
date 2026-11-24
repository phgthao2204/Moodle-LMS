<?php
// Run on a Moodle test instance.
define('CLI_SCRIPT', true);
$config = getenv('MOODLE_CONFIG') ?: '/var/www/html/config.php';
require($config);
require_once($CFG->dirroot . '/course/lib.php');
$base = rtrim(getenv('ACCEES_API_BASE') ?: $CFG->wwwroot, '/');
$host = getenv('ACCEES_HTTP_HOST');
$headers = $host ? ["Host: {$host}"] : [];
$oauthclient = getenv('ACCEES_OAUTH_CLIENT_ID');
$oauthsecret = getenv('ACCEES_OAUTH_CLIENT_SECRET');
$version = getenv('ACCEES_API_VERSION') ?: '1.0';
\core\session\manager::set_user(get_admin());

function accees_smoke_request(string $url, string $method, array $headers, ?string $body = null): array {
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    if ($body !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
    }
    $response = curl_exec($curl);
    if ($response === false) {
        throw new \RuntimeException(curl_error($curl));
    }
    return [curl_getinfo($curl, CURLINFO_HTTP_CODE), json_decode($response, true)];
}

$entity = null;
$course = null;
$failed = false;
try {
    if ($oauthclient || $oauthsecret) {
        if (!$oauthclient || !$oauthsecret) {
            throw new \RuntimeException('Both ACCEES OAuth client variables are required.');
        }
        [$status, $auth] = accees_smoke_request(
            getenv('ACCEES_OAUTH_TOKEN_URL') ?: "{$base}/oauth2/token",
            'POST',
            [...$headers, 'Content-Type: application/x-www-form-urlencoded'],
            http_build_query([
                'grant_type' => 'client_credentials',
                'client_id' => $oauthclient,
                'client_secret' => $oauthsecret,
                'scope' => 'local_accees_sync_api:course:read local_accees_sync_api:course:write',
            ]),
        );
        if ($status !== 200 || empty($auth['access_token'])) {
            throw new \RuntimeException("OAuth token request failed with HTTP {$status}.");
        }
        echo "OAuth Client Credentials token endpoint: HTTP 200\n";
        $token = $auth['access_token'];
    } else {
        $repo = new \core\api\repository\api_token_repository();
        $secret = bin2hex(random_bytes(32));
        $entity = $repo->create_token('UC-LMS-11 smoke', $secret, get_admin()->id,
            ['local_accees_sync_api:course:write', 'local_accees_sync_api:course:read'], null, time() + 300);
        $token = 'pat_' . rtrim(base64_encode($entity->get_id() . '/' . $secret), '=');
    }
    $api = "{$base}/api/rest/v2/local_accees_sync_api/courses";
    $authheaders = [...$headers, "Authorization: Bearer {$token}", "X-ACCEES-API-Version: {$version}"];
    $course = create_course((object) [
        'shortname' => 'UC11-QA-' . bin2hex(random_bytes(4)),
        'fullname' => 'UC-LMS-11 test course',
        'category' => $DB->get_field_sql('SELECT MIN(id) FROM {course_categories}'),
        'visible' => 0,
    ]);
    $cases = [
        [200, $course->id, [
            'fullname' => 'UC-LMS-11 updated',
            'custom_fields' => [
                'accees_duration' => '45',
                'accees_course_status' => 'published',
                'accees_delivery_mode' => 'blended',
            ],
            'lom' => ['classification' => ['institution' => 'CTU', 'is_featured' => true]],
        ]],
        [422, $course->id, ['custom_fields' => ['accees_is_featured' => 'yes']]],
        [404, (int) $DB->get_field_sql('SELECT MAX(id) FROM {course}') + 1, ['visible' => false]],
    ];
    foreach ($cases as [$expected, $id, $payload]) {
        [$status, $json] = accees_smoke_request("{$api}/{$id}", 'PATCH',
            [...$authheaders, 'Content-Type: application/json'], json_encode($payload, JSON_THROW_ON_ERROR));
        echo json_encode(['expected' => $expected, 'http' => $status, 'response' => $json],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) . PHP_EOL;
        $failed = $failed || $status !== $expected || !is_array($json);
        if ($expected === 200) {
            $failed = $failed || ($json['custom_fields']['accees_duration'] ?? null) !== '45'
                || ($json['custom_fields']['accees_institution'] ?? null) !== 'CTU'
                || ($json['custom_fields']['accees_is_featured'] ?? null) !== true
                || ($json['custom_fields']['accees_course_status'] ?? null) !== 'published'
                || ($json['custom_fields']['accees_delivery_mode'] ?? null) !== 'blended'
                || $DB->get_field('course', 'fullname', ['id' => $id]) !== 'UC-LMS-11 updated';
        }
    }
    foreach (["{$api}/{$course->id}", "{$api}?search=" . rawurlencode($course->shortname)] as $url) {
        [$status, $json] = accees_smoke_request($url, 'GET', $authheaders);
        $item = $json['items'][0] ?? $json;
        $passed = $status === 200 && (int) ($item['id'] ?? 0) === (int) $course->id
            && ($item['lom']['lifecycle']['status'] ?? null) === 'published'
            && ($item['lom']['technical']['delivery_mode'] ?? null) === 'blended';
        echo json_encode(['method' => 'GET', 'http' => $status, 'passed' => $passed, 'response' => $json],
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL;
        $failed = $failed || !$passed;
    }
} finally {
    if ($entity) {
        $DB->delete_records('rest_api_tokens', ['id' => $entity->get_id()]);
    }
    if ($course) {
        delete_course($course, false);
    }
}
exit((int) $failed);

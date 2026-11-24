<?php
$body = file_get_contents('php://input');
file_put_contents('/tmp/uc14-webhook.json', json_encode([
    'body' => $body,
    'signature' => $_SERVER['HTTP_X_ACCEES_SIGNATURE'] ?? '',
]));
http_response_code(file_exists('/tmp/uc14-reject-webhook') ? 503 : 204);

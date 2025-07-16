<?php
require_once __DIR__ . '/vendor/autoload.php';

define('LTI_CONSUMER_KEY', 'fox');
define('LTI_SHARED_SECRET', 'dog');

function validate_oauth($params, $secret, $launch_url) {
    $base_string = build_base_string($launch_url, 'POST', $params);
    $signing_key = rawurlencode($secret) . '&';
    $calculated_signature = base64_encode(hash_hmac('sha1', $base_string, $signing_key, true));
    return $calculated_signature === $params['oauth_signature'];
}

function build_base_string($url, $method, $params) {
    $r = [];
    ksort($params);
    foreach ($params as $key => $value) {
        if ($key != 'oauth_signature') {
            $r[] = rawurlencode($key) . '=' . rawurlencode($value);
        }
    }
    return $method . '&' . rawurlencode($url) . '&' . rawurlencode(implode('&', $r));
}

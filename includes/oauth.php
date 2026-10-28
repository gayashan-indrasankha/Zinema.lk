<?php
function oauth_start_flow() {
    $now = time();
    $flows = $_SESSION['oauth_flows'] ?? [];
    foreach ($flows as $key => $flow) if (($flow['issued_at'] ?? 0) < $now - 600) unset($flows[$key]);
    while (count($flows) >= 5) array_shift($flows);
    $state = bin2hex(random_bytes(32));
    $verifier = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
    $flows[$state] = ['issued_at' => $now, 'verifier' => $verifier, 'link_user_id' => $_SESSION['user_id'] ?? null];
    $_SESSION['oauth_flows'] = $flows;
    return ['state' => $state, 'challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=')];
}

function oauth_consume_flow($state, $now = null) {
    if (!is_string($state) || !preg_match('/^[a-f0-9]{64}$/D', $state)) return null;
    $flow = $_SESSION['oauth_flows'][$state] ?? null;
    unset($_SESSION['oauth_flows'][$state]);
    $now = $now ?? time();
    if (!is_array($flow) || ($flow['issued_at'] ?? 0) < $now - 600 || ($flow['issued_at'] ?? 0) > $now) return null;
    return $flow;
}

function oauth_http_json($url, $body = null, $access = null) {
    $curl = curl_init($url);
    $options = [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_TIMEOUT => 5, CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS];
    if ($body !== null) $options += [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($body)];
    if ($access !== null) $options[CURLOPT_HTTPHEADER] = ['Authorization: Bearer ' . $access];
    curl_setopt_array($curl, $options);
    $data = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    $parsed = is_string($data) ? json_decode($data, true) : null;
    return $status === 200 && is_array($parsed) ? $parsed : null;
}

function oauth_fail($reason) {
    header('Location: ' . BASE_URL . '/profile.php?error=oauth_' . $reason, true, 303);
    exit;
}

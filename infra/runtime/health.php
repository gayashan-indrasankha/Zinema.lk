<?php
require_once __DIR__ . '/database.php';

function zinema_health_response(string $probe, ?callable $dependency = null): array {
    if ($probe === 'live') return [200, ['status' => 'alive']];
    if (!in_array($probe, ['dependencies', 'ready'], true)) return [404, ['status' => 'not_found']];
    // Pure config only: no admin/session/remember-cookie bootstrap.
    require_once dirname(__DIR__, 2) . '/Zinema.lk Website/includes/environment.php';
    $available = ($dependency ?? 'zinema_dependency_available')();
    if (!$available) return [503, ['status' => 'dependency_unavailable']];
    if ($probe === 'dependencies') {
        return [200, ['status' => 'dependencies_available', 'schema' => 'unverified']];
    }
    // ZL-14 must supply a reviewed schema and actual application-readiness assertions.
    return [503, ['status' => 'schema_unverified', 'next_issue' => 'ZL-14']];
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $probe = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');
    [$status, $body] = zinema_health_response($probe);
    http_response_code($status);
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    echo json_encode($body, JSON_THROW_ON_ERROR);
}

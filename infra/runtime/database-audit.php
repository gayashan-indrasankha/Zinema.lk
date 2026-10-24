<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__, 2) . '/Zinema.lk Website/includes/environment.php';
if (APP_ENV === 'production' || DB_HOST !== 'db' || recovery_env('RECOVERY_CONTAINER_MODE') !== 'local') {
    fwrite(STDERR, "{\"error\":\"NONLOCAL_DATABASE_REFUSED\"}\n");
    exit(1);
}
require __DIR__ . '/database.php';
if (!zinema_dependency_available()) {
    fwrite(STDERR, "{\"error\":\"DEPENDENCY_UNAVAILABLE\"}\n");
    exit(1);
}
try {
    $pdo = new PDO('mysql:host=db;dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS,
        [PDO::ATTR_TIMEOUT => 2, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $grants = $pdo->query('SHOW GRANTS FOR CURRENT_USER')->fetchAll(PDO::FETCH_COLUMN);
    $combined = implode("\n", $grants);
    $unsafe = preg_match('/\b(?:ALL PRIVILEGES|CREATE|ALTER|DROP|GRANT OPTION|SUPER|FILE|PROCESS)\b/', $combined) === 1;
    $count = $pdo->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()')->fetchColumn();
    echo json_encode(['databaseAvailable' => true, 'leastPrivilegeVerified' => !$unsafe,
        'applicationTableCount' => (int)$count, 'schemaReady' => false], JSON_THROW_ON_ERROR) . "\n";
    if ($unsafe) exit(1);
} catch (Throwable $error) {
    fwrite(STDERR, "{\"error\":\"DATABASE_AUDIT_FAILED\"}\n");
    exit(1);
}

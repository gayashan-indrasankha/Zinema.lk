<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/database/Runner.php';
try {
    $db = \Zinema\Database\Runner::connect(\Zinema\Database\Runner::settings());
    \Zinema\Database\Runner::target($db);
    $summary = []; $counts = [];
    foreach (['site_settings','rate_limits','movie_parts','episode_parts','schema_migrations'] as $table) {
        $order = $table === 'schema_migrations' ? 'version' : 'id';
        $rows = $db->query('SELECT * FROM ' . $table . ' ORDER BY ' . $order)->fetchAll();
        $summary[$table] = ['structure' => \Zinema\Database\Runner::structure($db, $table), 'rows' => $rows];
        $counts[$table] = count($rows);
    }
    echo json_encode(['sha256' => hash('sha256', json_encode($summary, JSON_THROW_ON_ERROR)), 'rowCounts' => $counts]) . "\n";
} catch (Throwable $error) {
    fwrite(STDERR, "{\"error\":\"SYNTHETIC_DIGEST_FAILED\"}\n"); exit(1);
}

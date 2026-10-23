<?php
declare(strict_types=1);
try {
    if (getenv('APP_ENV') !== 'test' || getenv('DB_HOST') !== 'db' || getenv('DB_NAME') !== 'zinema_recovery_test' ||
        getenv('RECOVERY_CONTAINER_MODE') !== 'local') throw new RuntimeException();
    if (!is_dir('/runtime/sessions')) mkdir('/runtime/sessions', 0700, true);
    require dirname(__DIR__, 2) . '/Zinema.lk Website/includes/database.php';
    require dirname(__DIR__, 2) . '/Zinema.lk Website/admin/config.php';
    $identity = $pdo->query('SELECT @@server_uuid')->fetchColumn();
    $marker = $pdo->query('SELECT instance_hash FROM recovery_test_target WHERE id=1')->fetchColumn();
    if ($identity !== getenv('RECOVERY_SERVER_UUID') || !hash_equals(hash('sha256', (string)getenv('RECOVERY_DB_TOKEN')), $marker)) throw new RuntimeException();
    $stmt = $pdo->prepare('SELECT :fixture + :fixture'); $stmt->execute(['fixture' => 2]);
    $repeated = $stmt->fetchColumn() == 4;
    $stmt = $conn->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ?');
    $key = 'fixture_label'; $stmt->bind_param('s', $key); $stmt->execute();
    $mysqli = $stmt->get_result()->fetch_assoc()['setting_value'] === 'Synthetic cinema';
    $stmt->close();
    require dirname(__DIR__, 2) . '/Zinema.lk Website/includes/settings.php';
    require dirname(__DIR__, 2) . '/Zinema.lk Website/includes/rate_limiter.php';
    $legacyDdl = ['settingsRequiresCreatePrivilege' => false, 'rateLimiterRequiresCreatePrivilege' => false];
    try { getSetting($conn, 'fixture_label'); }
    catch (mysqli_sql_exception $error) {
        if ($error->getCode() !== 1142) throw $error;
        $legacyDdl['settingsRequiresCreatePrivilege'] = true;
    }
    try { new RateLimiter($conn); }
    catch (mysqli_sql_exception $error) {
        if ($error->getCode() !== 1142) throw $error;
        $legacyDdl['rateLimiterRequiresCreatePrivilege'] = true;
    }
    $conn->close(); session_write_close();
    if (!$repeated || !$mysqli || !$pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES)) throw new RuntimeException();
    echo json_encode(['legacyPdoBootstrap' => true, 'legacyMysqliBootstrap' => true,
        'repeatedNamedParametersPreserved' => true, 'observedLegacyDdlBlockers' => $legacyDdl,
        'businessRoutesVerified' => false]) . "\n";
} catch (Throwable $error) {
    fwrite(STDERR, "{\"error\":\"LEGACY_ACCESS_TEST_FAILED\",\"detailsWithheld\":true}\n"); exit(1);
}

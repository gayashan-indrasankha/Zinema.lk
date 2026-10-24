<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/database/Runner.php';
use Zinema\Database\Runner;
use Zinema\Database\MigrationFailure;

$passed = []; $migrations = Runner::load(dirname(__DIR__, 2) . '/database');
function check(string $name, bool $condition): void {
    global $passed;
    if (!$condition) throw new RuntimeException($name);
    $passed[] = $name;
}
function database(string $name): PDO {
    putenv('DB_NAME=' . $name);
    return Runner::connect(Runner::settings());
}
function reason(string $name, string $code, callable $action): void {
    try { $action(); } catch (MigrationFailure $error) { check($name, $error->reason === $code); return; }
    check($name, false);
}
function rejectedSql(string $name, callable $action): void {
    try { $action(); } catch (PDOException $error) { check($name, true); return; }
    check($name, false);
}
try {
    $db = database('zinema_recovery_test');
    $fresh = Runner::apply($db, $migrations);
    check('fresh applies four ordered migrations', $fresh['applied'] === 4 && $fresh['fullApplicationReady'] === false);
    check('ledger has four applied versions', (int)$db->query("SELECT COUNT(*) FROM schema_migrations WHERE status='applied'")->fetchColumn() === 4);
    $again = Runner::apply($db, $migrations);
    check('rerun skips all completed migrations', $again['skipped'] === 4 && $again['applied'] === 0);
    check('rerun does not increment completed attempts', (int)$db->query('SELECT MAX(attempts) FROM schema_migrations')->fetchColumn() === 1);
    foreach ($migrations as $migration) check('structure matches ' . $migration['table'], Runner::structure($db, $migration['table']) === $migration['expected']);
    check('unresolved catalogue and token tables are not fabricated', $db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ('movies','users','whatsapp_tokens')")->fetchColumn() == 0);

    $query = $db->prepare('INSERT INTO site_settings(setting_key, setting_value) VALUES (?, ?)');
    $query->execute(['fixture_label', 'Synthetic cinema']);
    $query->execute(['fixture_unicode', "Cinema \u{1F3AC}"]);
    rejectedSql('setting keys are unique', fn() => $query->execute(['fixture_label', 'duplicate']));
    $query = $db->prepare('INSERT INTO movie_parts(movie_id, part_number, message_id, file_name, file_size) VALUES (?, ?, ?, ?, ?)');
    $query->execute([501, 1, 'fictional-message-001', null, null]);
    $query->execute([501, 2, 'fictional-message-002', 'synthetic-placeholder.txt', 123]);
    rejectedSql('movie and part pair is unique', fn() => $query->execute([501, 1, 'fictional-duplicate', null, null]));
    rejectedSql('message identifier is required', fn() => $query->execute([502, 1, null, null, null]));
    check('optional part metadata remains nullable', $db->query('SELECT file_name IS NULL AND file_size IS NULL FROM movie_parts WHERE movie_id=501 AND part_number=1')->fetchColumn() == 1);
    $query = $db->prepare('INSERT INTO episode_parts(episode_id, part_number, message_id) VALUES (?, ?, ?)');
    $query->execute([601, 1, 'fictional-episode-message']);
    rejectedSql('episode and part pair is unique', fn() => $query->execute([601, 1, 'fictional-duplicate']));
    $query = $db->prepare('SELECT part_number, message_id, file_name FROM movie_parts WHERE movie_id = ? ORDER BY part_number');
    $query->execute([501]); check('existing movie part lookup shape executes', count($query->fetchAll()) === 2);
    $query = $db->prepare('SELECT message_id, file_name FROM episode_parts WHERE episode_id = ? AND part_number = ?');
    $query->execute([601, 1]); check('existing episode lookup shape executes', $query->fetchColumn() === 'fictional-episode-message');
    $query = $db->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ?');
    $query->execute(["fixture_label' OR 1=1 --"]); check('parameter payload remains data', $query->fetch() === false);
    $db->beginTransaction(); $db->prepare('INSERT INTO site_settings(setting_key) VALUES (?)')->execute(['rollback_fixture']); $db->rollBack();
    check('InnoDB DML transaction rollback is verified', $db->query("SELECT COUNT(*) FROM site_settings WHERE setting_key='rollback_fixture'")->fetchColumn() == 0);

    $app = new PDO('mysql:host=db;dbname=zinema_recovery_test;charset=utf8mb4', 'zinema_recovery_ci', (string)getenv('ZL14_APP_PASSWORD'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_TIMEOUT => 2]);
    rejectedSql('runtime account cannot create schema', fn() => $app->exec('CREATE TABLE forbidden_runtime_ddl(id INT)'));
    check('migrator has no destructive table privilege', !preg_match('/\b(?:DROP|ALL PRIVILEGES|GRANT OPTION)\b/', implode("\n", $db->query('SHOW GRANTS FOR CURRENT_USER')->fetchAll(PDO::FETCH_COLUMN))));
    rejectedSql('migrator cannot create arbitrary databases', fn() => $db->exec('CREATE DATABASE forbidden_database'));
    $stmt = $app->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ?'); $stmt->execute(['fixture_label']);
    check('runtime PDO can use migrated settings', $stmt->fetchColumn() === 'Synthetic cinema');
    $mysqli = mysqli_init(); $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);
    $mysqli->real_connect('db', 'zinema_recovery_ci', (string)getenv('ZL14_APP_PASSWORD'), 'zinema_recovery_test'); $mysqli->set_charset('utf8mb4');
    $stmt = $mysqli->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ?'); $key = 'fixture_label'; $stmt->bind_param('s', $key); $stmt->execute();
    check('runtime MySQLi can use migrated settings', $stmt->get_result()->fetch_assoc()['setting_value'] === 'Synthetic cinema');
    $stmt->close(); $mysqli->close();
    $plans = [];
    foreach (['SELECT * FROM movie_parts WHERE movie_id=501 AND part_number=1', "SELECT setting_value FROM site_settings WHERE setting_key='fixture_label'",
        "SELECT * FROM rate_limits WHERE ip_address='192.0.2.1' AND action_type='token_request'"] as $sql) {
        $plan = $db->query('EXPLAIN ' . $sql)->fetch(); $plans[] = ['selectedKey' => $plan['key'], 'possibleKeys' => $plan['possible_keys']];
    }
    check('unique movie part index is selected', $plans[0]['selectedKey'] === 'unique_movie_part');
    check('setting unique index is selected', $plans[1]['selectedKey'] === 'setting_key');
    check('rate limit composite index is available', str_contains((string)$plans[2]['possibleKeys'], 'idx_ip_action'));

    $upgrade = database('zinema_zl14_upgrade');
    $historical = json_decode((string)file_get_contents(__DIR__ . '/legacy-parts.json'), true, 512, JSON_THROW_ON_ERROR);
    foreach ($historical['tables'] as $table) $upgrade->exec($table['sql']);
    $upgrade->prepare('INSERT INTO movie_parts(movie_id,part_number,message_id) VALUES (?,?,?)')->execute([701, 1, 'fictional-upgrade']);
    $result = Runner::apply($upgrade, $migrations);
    check('two historical part tables are adopted', $result['adoptedOrRecovered'] === 2 && $result['applied'] === 4);
    check('upgrade preserves synthetic data', $upgrade->query('SELECT message_id FROM movie_parts WHERE movie_id=701')->fetchColumn() === 'fictional-upgrade');
    $db = database('zinema_zl14_failure');
    reason('SQL failure is tracked', 'MIGRATION_STEP_FAILED', fn() => Runner::apply($db, $migrations,
        static function(PDO $connection): void { $connection->query('SELECT synthetic_missing_column FROM site_settings'); }));
    check('DDL remains after the failed step', Runner::structure($db, 'site_settings') !== null);
    check('failed ledger row records no raw SQL diagnostic', $db->query('SELECT error_code FROM schema_migrations')->fetchColumn() === 'MIGRATION_STEP_FAILED');
    check('failed implicit-commit step can be reconciled', Runner::apply($db, $migrations)['adoptedOrRecovered'] === 1);
    check('recovery increments the failed attempt only', $db->query("SELECT attempts FROM schema_migrations WHERE version='0001'")->fetchColumn() == 2);
    $db = database('zinema_zl14_drift'); Runner::apply($db, $migrations);
    $db->exec('ALTER TABLE site_settings ADD COLUMN synthetic_unreviewed INT NULL');
    reason('out-of-band schema drift is refused', 'MIGRATION_SCHEMA_DRIFT', fn() => Runner::apply($db, $migrations));
    $db = database('zinema_zl14_checksum'); Runner::apply($db, $migrations);
    $db->prepare('UPDATE schema_migrations SET checksum=? WHERE version=?')->execute([str_repeat('0',64), '0001']);
    reason('altered applied checksum is refused', 'APPLIED_MIGRATION_CHANGED', fn() => Runner::apply($db, $migrations));
    $db = database('zinema_zl14_locking'); $other = database('zinema_zl14_locking');
    $lock = Runner::lockName('zinema_zl14_locking'); $q = $db->prepare('SELECT GET_LOCK(?,0)'); $q->execute([$lock]);
    reason('concurrent migrator fails without mutations', 'MIGRATION_LOCKED', fn() => Runner::apply($other, $migrations));
    $q = $db->prepare('SELECT RELEASE_LOCK(?)'); $q->execute([$lock]);
    check('released advisory lock permits migration', Runner::apply($other, $migrations)['applied'] === 4);
    $other->exec("UPDATE schema_migrations SET status='running' WHERE version='0001'");
    check('interrupted running step reconciles verified DDL', Runner::apply($other, $migrations)['adoptedOrRecovered'] === 1);
    $other->exec('ALTER TABLE schema_migrations ADD COLUMN synthetic_ledger_drift INT');
    reason('ledger structure drift is refused', 'MIGRATION_LEDGER_DRIFT', fn() => Runner::apply($other, $migrations));
    $saved = getenv('RECOVERY_DB_TOKEN'); putenv('RECOVERY_DB_TOKEN=' . str_repeat('0',64));
    reason('wrong disposable marker is refused', 'MIGRATION_TARGET_REFUSED', fn() => Runner::target($other)); putenv('RECOVERY_DB_TOKEN=' . $saved);
    $saved = getenv('RECOVERY_SERVER_UUID'); putenv('RECOVERY_SERVER_UUID=00000000-0000-0000-0000-000000000001');
    reason('wrong server identity is refused', 'MIGRATION_TARGET_REFUSED', fn() => Runner::target($other)); putenv('RECOVERY_SERVER_UUID=' . $saved);
    $saved = getenv('DB_PASS'); putenv('DB_PASS=LOCAL_ONLY_' . str_repeat('0',64));
    reason('invalid synthetic credentials fail generically', 'MIGRATION_CONNECTION_FAILED', fn() => Runner::connect(Runner::settings())); putenv('DB_PASS=' . $saved);
    echo json_encode(['passed' => count($passed), 'failed' => 0, 'checks' => $passed, 'queryPlans' => $plans,
        'scope' => 'four-table-subset', 'fullApplicationReady' => false], JSON_THROW_ON_ERROR) . "\n";
} catch (Throwable $error) {
    fwrite(STDERR, json_encode(['passed' => count($passed), 'error' => $error instanceof MigrationFailure ? $error->reason : 'DATABASE_TEST_FAILED',
        'lastPassed' => end($passed) ?: null, 'detailsWithheld' => true]) . "\n"); exit(1);
}

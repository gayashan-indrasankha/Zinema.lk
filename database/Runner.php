<?php
declare(strict_types=1);

namespace Zinema\Database;

use PDO;
use RuntimeException;
use Throwable;

final class MigrationFailure extends RuntimeException {
    public function __construct(public readonly string $reason) { parent::__construct($reason); }
}

/** Restricted, additive installer for the verified four-table subset, not production. */
final class Runner {
    public static function settings(): array {
        $get = static fn(string $key): string => trim((string)getenv($key));
        $settings = [];
        foreach (['APP_ENV', 'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'RECOVERY_CONTAINER_MODE',
            'RECOVERY_DB_TOKEN', 'RECOVERY_SERVER_UUID'] as $key) $settings[$key] = $get($key);
        if ($settings['APP_ENV'] !== 'test' || $settings['DB_HOST'] !== 'db' ||
            $settings['RECOVERY_CONTAINER_MODE'] !== 'local' ||
            !preg_match('/^zinema_(?:recovery_test|zl14_(?:upgrade|failure|drift|restore|checksum|locking))$/D', $settings['DB_NAME']) ||
            $settings['DB_USER'] !== 'zinema_recovery_migrator' ||
            !preg_match('/^LOCAL_ONLY_[a-f0-9]{64}$/D', $settings['DB_PASS']) ||
            !preg_match('/^[a-f0-9]{64}$/D', $settings['RECOVERY_DB_TOKEN']) ||
            !preg_match('/^[a-f0-9-]{36}$/D', $settings['RECOVERY_SERVER_UUID'])) {
            throw new MigrationFailure('MIGRATION_TARGET_REFUSED');
        }
        return $settings;
    }

    public static function connect(array $settings): PDO {
        // A caller cannot bypass settings validation by supplying an arbitrary DSN.
        if ($settings !== self::settings()) throw new MigrationFailure('MIGRATION_TARGET_REFUSED');
        // ATTR_TIMEOUT alone does not bound a stalled mysqlnd greeting/read.
        // Scope this setting to construction of this new migration connection.
        $readTimeout = ini_set('mysqlnd.net_read_timeout', '2');
        if ($readTimeout === false) throw new MigrationFailure('MIGRATION_DRIVER_UNSUPPORTED');
        try {
            return @new PDO('mysql:host=db;port=3306;dbname=' . $settings['DB_NAME'] . ';charset=utf8mb4',
                $settings['DB_USER'], $settings['DB_PASS'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_TIMEOUT => 2,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        } catch (Throwable $error) {
            throw new MigrationFailure('MIGRATION_CONNECTION_FAILED');
        } finally {
            ini_set('mysqlnd.net_read_timeout', $readTimeout);
        }
    }

    public static function target(PDO $db): void {
        $s = self::settings();
        try {
            $server = $db->query('SELECT DATABASE() AS name, @@server_uuid AS uuid')->fetch();
            $marker = $db->query('SELECT database_name, instance_hash, data_class FROM recovery_test_target WHERE id = 1')->fetch();
            if ($server['name'] !== $s['DB_NAME'] || $server['uuid'] !== $s['RECOVERY_SERVER_UUID'] ||
                !$marker || $marker['database_name'] !== $s['DB_NAME'] || $marker['data_class'] !== 'synthetic' ||
                !hash_equals(hash('sha256', $s['RECOVERY_DB_TOKEN']), $marker['instance_hash'])) {
                throw new MigrationFailure('MIGRATION_TARGET_REFUSED');
            }
        } catch (Throwable $error) {
            throw new MigrationFailure('MIGRATION_TARGET_REFUSED');
        }
    }

    public static function load(string $directory): array {
        $manifest = json_decode((string)file_get_contents($directory . '/migrations.json'), true, 512, JSON_THROW_ON_ERROR);
        if (($manifest['schemaVersion'] ?? null) !== 1 || ($manifest['fullApplicationReady'] ?? true) !== false ||
            ($manifest['scope'] ?? '') !== 'source-backed-subset') throw new MigrationFailure('INVALID_MIGRATION_MANIFEST');
        $result = []; $previous = '';
        foreach ($manifest['migrations'] ?? [] as $entry) {
            $version = $entry['version'] ?? '';
            $file = $entry['file'] ?? '';
            if (!preg_match('/^\d{4}$/D', $version) || strcmp($version, $previous) <= 0 ||
                !preg_match('~^database/migrations/' . $version . '_[a-z_]+\.sql$~D', $file)) {
                throw new MigrationFailure('INVALID_MIGRATION_ORDER');
            }
            $full = $directory . '/migrations/' . basename($file);
            if (!is_file($full) || is_link($full) || is_link(dirname($full))) throw new MigrationFailure('MIGRATION_FILE_UNAVAILABLE');
            $sql = (string)file_get_contents($full);
            if (!hash_equals($entry['sha256'] ?? '', hash('sha256', $sql))) throw new MigrationFailure('MIGRATION_CHECKSUM_MISMATCH');
            $expected = self::definition($sql);
            if ($expected['table'] !== ($entry['table'] ?? '') ||
                !in_array($expected['table'], ['site_settings', 'rate_limits', 'movie_parts', 'episode_parts'], true)) {
                throw new MigrationFailure('UNREVIEWED_MIGRATION_TABLE');
            }
            $entry['sql'] = $sql; $entry['expected'] = $expected;
            $result[] = $entry; $previous = $version;
        }
        if (array_column($result, 'table') !== ['site_settings','rate_limits','movie_parts','episode_parts']) {
            throw new MigrationFailure('INCOMPLETE_MIGRATION_MANIFEST');
        }
        return $result;
    }

    /** Narrow parser for these reviewed CREATE statements; rejects unsupported SQL. */
    public static function definition(string $sql): array {
        if (substr_count($sql, ';') !== 1 ||
            !preg_match('/^CREATE TABLE ([a-z_]+) \((.*)\) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\s*$/sD', trim($sql), $match)) {
            throw new MigrationFailure('UNSUPPORTED_MIGRATION_SQL');
        }
        $parts = []; $depth = 0; $quote = false; $start = 0; $body = $match[2];
        for ($i = 0; $i < strlen($body); $i++) {
            $c = $body[$i];
            if ($c === "'") $quote = !$quote;
            if (!$quote && $c === '(') $depth++;
            if (!$quote && $c === ')') $depth--;
            if (!$quote && $depth === 0 && $c === ',') { $parts[] = trim(substr($body, $start, $i - $start)); $start = $i + 1; }
        }
        $parts[] = trim(substr($body, $start)); $columns = []; $indexes = [];
        foreach ($parts as $part) {
            $part = str_replace('`', '', $part);
            if (preg_match('/^(PRIMARY KEY|UNIQUE KEY|KEY|INDEX)\s*(\w+)?\s*\(([^)]+)\)$/iD', $part, $index)) {
                $name = strtoupper($index[1]) === 'PRIMARY KEY' ? 'PRIMARY' : $index[2];
                $indexes[$name] = ['columns' => array_map('trim', explode(',', $index[3])),
                    'unique' => in_array(strtoupper($index[1]), ['PRIMARY KEY', 'UNIQUE KEY'], true)];
                continue;
            }
            if (!preg_match('/^(\w+)\s+(INT(?:\(11\))?|BIGINT(?:\(20\))?|VARCHAR\(\d+\)|TEXT|TIMESTAMP)\b(.*)$/iD', $part, $column)) {
                // A word boundary follows INT/TEXT/TIMESTAMP, but VARCHAR ends in ')'.
                if (!preg_match('/^(\w+)\s+(VARCHAR\(\d+\)|INT\(11\)|BIGINT\(20\))(.*)$/iD', $part, $column)) throw new MigrationFailure('UNSUPPORTED_COLUMN_DEFINITION');
            }
            [$all, $name, $type, $rest] = $column;
            $type = strtolower((string)preg_replace('/^(INT|BIGINT)\((?:11|20)\)$/i', '$1', $type));
            $primary = stripos($rest, 'PRIMARY KEY') !== false;
            $default = null;
            if (preg_match("/DEFAULT\\s+('([^']*)'|NULL|CURRENT_TIMESTAMP(?:\\(\\))?|[0-9]+)/i", $rest, $d)) {
                $default = strcasecmp($d[1], 'NULL') === 0 ? null : trim($d[1], "'");
            }
            $extra = stripos($rest, 'AUTO_INCREMENT') !== false ? 'auto_increment' : '';
            if (stripos($rest, 'ON UPDATE CURRENT_TIMESTAMP') !== false) $extra = 'on update current_timestamp';
            $columns[$name] = ['type' => $type, 'nullable' => !$primary && stripos($rest, 'NOT NULL') === false,
                'default' => self::normalDefault($default), 'extra' => $extra,
                'collation' => preg_match('/^(?:varchar|text)/', $type) ? 'utf8mb4_unicode_ci' : null];
            if ($primary) $indexes['PRIMARY'] = ['columns' => [$name], 'unique' => true];
            if (!$primary && stripos($rest, 'UNIQUE') !== false) $indexes[$name] = ['columns' => [$name], 'unique' => true];
        }
        ksort($indexes);
        return ['table' => $match[1], 'engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci',
            'columns' => $columns, 'indexes' => $indexes, 'additionalConstraints' => []];
    }

    private static function normalDefault(mixed $value): ?string {
        if ($value === null) return null;
        return strcasecmp((string)$value, 'CURRENT_TIMESTAMP()') === 0 ? 'current_timestamp' :
            (strcasecmp((string)$value, 'CURRENT_TIMESTAMP') === 0 ? 'current_timestamp' : (string)$value);
    }

    public static function structure(PDO $db, string $table): ?array {
        $query = $db->prepare('SELECT ENGINE, TABLE_COLLATION FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
        $query->execute([$table]); $info = $query->fetch(); if (!$info) return null;
        $query = $db->prepare('SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA, COLLATION_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? ORDER BY ORDINAL_POSITION');
        $query->execute([$table]); $columns = [];
        foreach ($query as $column) {
            $extra = strtolower(str_replace(['DEFAULT_GENERATED', '()'], '', $column['EXTRA']));
            $columns[$column['COLUMN_NAME']] = ['type' => strtolower($column['COLUMN_TYPE']),
                'nullable' => $column['IS_NULLABLE'] === 'YES', 'default' => self::normalDefault($column['COLUMN_DEFAULT']),
                'extra' => trim($extra), 'collation' => $column['COLLATION_NAME']];
        }
        $query = $db->prepare('SELECT INDEX_NAME, COLUMN_NAME, NON_UNIQUE, SUB_PART, COLLATION, IS_VISIBLE, INDEX_TYPE FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? ORDER BY INDEX_NAME, SEQ_IN_INDEX');
        $query->execute([$table]); $indexes = [];
        foreach ($query as $index) {
            $name = $index['INDEX_NAME'];
            if (!isset($indexes[$name])) $indexes[$name] = ['columns' => [], 'unique' => (int)$index['NON_UNIQUE'] === 0];
            $shape = $index['COLUMN_NAME'];
            if ($index['SUB_PART'] !== null) $shape .= '(' . $index['SUB_PART'] . ')';
            if ($index['COLLATION'] === 'D') $shape .= ' DESC';
            if ($index['IS_VISIBLE'] !== 'YES' || $index['INDEX_TYPE'] !== 'BTREE') $shape .= ' unreviewed_index_options';
            $indexes[$name]['columns'][] = $shape;
        }
        ksort($indexes);
        $query = $db->prepare("SELECT CONSTRAINT_NAME, CONSTRAINT_TYPE FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = ? AND CONSTRAINT_TYPE IN ('FOREIGN KEY','CHECK') ORDER BY CONSTRAINT_NAME");
        $query->execute([$table]);
        return ['table' => $table, 'engine' => $info['ENGINE'], 'collation' => $info['TABLE_COLLATION'],
            'columns' => $columns, 'indexes' => $indexes, 'additionalConstraints' => $query->fetchAll()];
    }

    public static function lockName(string $database): string { return 'zl14.' . substr(hash('sha256', $database), 0, 40); }

    public static function apply(PDO $db, array $migrations, ?callable $afterDdl = null): array {
        self::target($db);
        $lock = self::lockName(self::settings()['DB_NAME']);
        $q = $db->prepare('SELECT GET_LOCK(?, 0)'); $q->execute([$lock]);
        if ((int)$q->fetchColumn() !== 1) throw new MigrationFailure('MIGRATION_LOCKED');
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
                version VARCHAR(4) PRIMARY KEY, checksum CHAR(64) NOT NULL,
                status ENUM('running','applied','failed') NOT NULL,
                attempts INT NOT NULL DEFAULT 1,
                started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                applied_at TIMESTAMP NULL, error_code VARCHAR(40) NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $ledger = self::structure($db, 'schema_migrations');
            $ledgerTypes = ['version'=>'varchar(4)','checksum'=>'char(64)', 'status'=>"enum('running','applied','failed')",
                'attempts'=>'int','started_at'=>'timestamp','applied_at'=>'timestamp','error_code'=>'varchar(40)'];
            if (array_map(static fn(array $column): string => $column['type'], $ledger['columns']) !== $ledgerTypes ||
                $ledger['indexes'] !== ['PRIMARY'=>['columns'=>['version'],'unique'=>true]] ||
                $ledger['engine'] !== 'InnoDB' || $ledger['additionalConstraints'] !== []) {
                throw new MigrationFailure('MIGRATION_LEDGER_DRIFT');
            }
            $rows = $db->query('SELECT version, checksum, status FROM schema_migrations ORDER BY version')->fetchAll();
            $known = array_column($migrations, null, 'version');
            foreach ($rows as $row) {
                if (!isset($known[$row['version']])) throw new MigrationFailure('MIGRATION_HISTORY_UNKNOWN');
                if (!hash_equals($known[$row['version']]['sha256'], $row['checksum'])) throw new MigrationFailure('APPLIED_MIGRATION_CHANGED');
            }
            $prior = array_column($rows, null, 'version'); $applied = 0; $skipped = 0; $adopted = 0;
            foreach ($migrations as $migration) {
                $version = $migration['version'];
                if (!hash_equals($migration['sha256'], hash('sha256', $migration['sql'])) ||
                    self::definition($migration['sql']) !== $migration['expected']) throw new MigrationFailure('MIGRATION_CHECKSUM_MISMATCH');
                $actual = self::structure($db, $migration['table']);
                if ($actual !== null && $actual !== $migration['expected']) throw new MigrationFailure('MIGRATION_SCHEMA_DRIFT');
                if (($prior[$version]['status'] ?? null) === 'applied') {
                    if ($actual === null) throw new MigrationFailure('MIGRATION_SCHEMA_DRIFT');
                    $skipped++; continue;
                }
                $q = $db->prepare("INSERT INTO schema_migrations(version, checksum, status) VALUES (?, ?, 'running')
                    ON DUPLICATE KEY UPDATE status='running', attempts=attempts+1, error_code=NULL");
                $q->execute([$version, $migration['sha256']]);
                try {
                    if ($actual === null) {
                        $db->exec($migration['sql']);
                        if ($afterDdl !== null) $afterDdl($db, $version);
                    } else { $adopted++; }
                    if (self::structure($db, $migration['table']) !== $migration['expected']) throw new MigrationFailure('MIGRATION_SCHEMA_DRIFT');
                    $q = $db->prepare("UPDATE schema_migrations SET status='applied', applied_at=CURRENT_TIMESTAMP, error_code=NULL WHERE version=?");
                    $q->execute([$version]); $applied++;
                } catch (Throwable $error) {
                    $q = $db->prepare("UPDATE schema_migrations SET status='failed', error_code='MIGRATION_STEP_FAILED' WHERE version=?");
                    $q->execute([$version]);
                    throw new MigrationFailure('MIGRATION_STEP_FAILED');
                }
            }
            return ['applied' => $applied, 'skipped' => $skipped, 'adoptedOrRecovered' => $adopted,
                'scope' => 'four-table-subset', 'fullApplicationReady' => false];
        } finally {
            $q = $db->prepare('SELECT RELEASE_LOCK(?)'); $q->execute([$lock]);
        }
    }
}

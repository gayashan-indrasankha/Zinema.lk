'use strict';
// Creates a new tmpfs-only MySQL instance; never mounts or resets an existing volume.
const fs = require('node:fs');
const path = require('node:path');
const cp = require('node:child_process');
const crypto = require('node:crypto');
const environment = require('./environment.cjs');
function failure(code) { throw Object.assign(new Error(code), { code }); }
async function main(profile = 'ci') {
  await environment.main(['config', profile]); // checks local Linux daemon and the reviewed Compose plan
  const settings = environment.validateSettings(environment.parseSettings(fs.readFileSync(environment.managedPaths(profile).local, 'utf8')), profile);
  const run = crypto.randomBytes(12).toString('hex'), name = 'zinema-zl14-' + run;
  const password = () => 'LOCAL_ONLY_' + crypto.randomBytes(32).toString('hex');
  const rootPassword = password(), appPassword = password(), migrationPassword = password();
  const token = crypto.randomBytes(32).toString('hex'), markerHash = crypto.createHash('sha256').update(token).digest('hex');
  const databases = ['zinema_recovery_test', ...['upgrade','failure','drift','restore','checksum','locking'].map(n => 'zinema_zl14_' + n)];
  let networkCreated = false, container;
  const docker = (args, { input, env = {}, timeout = 120000, allowFailure = false } = {}) => {
    const result = cp.spawnSync('docker', args, { cwd: environment.root, env: { ...environment.cleanEnvironment(), ...env },
      input, encoding: 'utf8', timeout, maxBuffer: 8e6 });
    if ((result.error || result.status !== 0) && !allowFailure) {
      // Forward only our deliberately minimal JSON diagnostics, never Docker logs/settings.
      for (const line of (result.stderr || '').split('\n')) {
        try { const diagnostic = JSON.parse(line); if (/^[A-Z_]+$/.test(diagnostic.error || '')) process.stderr.write(JSON.stringify(diagnostic) + '\n'); } catch {}
      }
      failure('DISPOSABLE_DATABASE_COMMAND_FAILED');
    }
    return result;
  };
  function owned() {
    if (!container) failure('NO_OWNED_TEST_CONTAINER');
    const labels = JSON.parse(docker(['inspect','--format','{{json .Config.Labels}}',container]).stdout);
    if (labels['io.zinema.phase'] !== 'zl14' || labels['io.zinema.run'] !== run) failure('UNOWNED_DATABASE_REFUSED');
  }
  function rootSql(sql) {
    owned();
    return docker(['exec','-i',container,'sh','-c',
      'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysql --user=root --protocol=socket --batch --raw --skip-column-names --default-character-set=utf8mb4'], { input: sql }).stdout.trim();
  }
  try {
    if (docker(['network','inspect',name], { allowFailure: true }).status === 0) failure('TEST_RESOURCE_COLLISION');
    docker(['network','create','--internal','--label','io.zinema.phase=zl14','--label','io.zinema.run=' + run,name]); networkCreated = true;
    const rootEnv = { MYSQL_ROOT_PASSWORD: rootPassword, MYSQL_DATABASE: 'zinema_recovery_test',
      MYSQL_USER: 'zinema_recovery_ci', MYSQL_PASSWORD: appPassword, MYSQL_INITDB_SKIP_TZINFO: '1', MYSQL_ROOT_HOST: 'localhost' };
    const created = docker(['create','--pull','never','--name',name + '-db','--user','999:999',
      '--network',name,'--network-alias','db','--label','io.zinema.phase=zl14','--label','io.zinema.run=' + run,
      '--label','com.docker.compose.project=' + name,'--label','com.docker.compose.service=database-test',
      '--read-only','--cap-drop','ALL','--security-opt','no-new-privileges:true',
      '--memory','1g','--pids-limit','256','--cpus','1',
      '--tmpfs','/var/lib/mysql:rw,nosuid,size=768m,uid=999,gid=999,mode=0750',
      '--tmpfs','/tmp:rw,noexec,nosuid,size=64m,uid=999,gid=999',
      '--tmpfs','/var/run/mysqld:rw,nosuid,size=16m,uid=999,gid=999,mode=0755',
      '--log-opt','max-size=5m','--log-opt','max-file=2',
      '--health-cmd','mysqladmin ping --silent --host=127.0.0.1','--health-interval','2s','--health-timeout','3s','--health-retries','30',
      ...Object.keys(rootEnv).flatMap(k => ['--env',k]),settings.ZINEMA_PROJECT + '-mysql:zl13',
      '--character-set-server=utf8mb4','--collation-server=utf8mb4_unicode_ci'], { env: rootEnv });
    container = created.stdout.trim(); owned();
    const mounts = JSON.parse(docker(['inspect','--format','{{json .Mounts}}',container]).stdout);
    const ports = JSON.parse(docker(['inspect','--format','{{json .HostConfig.PortBindings}}',container]).stdout);
    if (mounts.some(m => m.Type !== 'tmpfs') || (ports && Object.keys(ports).length)) failure('PERSISTENT_OR_PUBLIC_TEST_DATABASE_REFUSED');
    docker(['start',container]);
    let healthy = false;
    for (let attempt = 0; attempt < 90; attempt++) {
      if (docker(['inspect','--format','{{.State.Health.Status}}',container]).stdout.trim() === 'healthy') { healthy = true; break; }
      await new Promise(resolve => setTimeout(resolve, 1000));
    }
    if (!healthy) failure('DISPOSABLE_DATABASE_STARTUP_FAILED');
    const initialTables = Number(rootSql("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema NOT IN ('mysql','information_schema','performance_schema','sys');"));
    if (initialTables !== 0) failure('NONEMPTY_DISPOSABLE_TARGET_REFUSED');
    const uuid = rootSql('SELECT @@server_uuid;');
    if (!/^[a-f0-9-]{36}$/.test(uuid)) failure('INVALID_TEST_SERVER_IDENTITY');
    let sql = "CREATE USER 'zinema_recovery_migrator'@'%' IDENTIFIED BY '" + migrationPassword + "';\n";
    for (const database of databases) {
      if (database !== 'zinema_recovery_test') sql += 'CREATE DATABASE `' + database + '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n';
      sql += 'CREATE TABLE `' + database + '`.recovery_test_target (id INT PRIMARY KEY, database_name VARCHAR(64) NOT NULL, instance_hash CHAR(64) NOT NULL, data_class VARCHAR(16) NOT NULL);\n';
      sql += 'INSERT INTO `' + database + "`.recovery_test_target VALUES (1,'" + database + "','" + markerHash + "','synthetic');\n";
      sql += 'GRANT SELECT,INSERT,UPDATE,CREATE,ALTER,INDEX ON `' + database.replaceAll('_','\\_') + "`.* TO 'zinema_recovery_migrator'@'%';\n";
    }
    rootSql(sql);
    const shared = { APP_ENV: 'test', DB_HOST: 'db', DB_NAME: 'zinema_recovery_test', DB_USER: 'zinema_recovery_migrator',
      DB_PASS: migrationPassword, RECOVERY_CONTAINER_MODE: 'local', RECOVERY_DB_TOKEN: token, RECOVERY_SERVER_UUID: uuid,
      RES_OPTIONS: 'attempts:1 timeout:1', ZL14_APP_PASSWORD: appPassword };
    const php = (script, changes = {}, allowFailure = false) => {
      const env = { ...shared, ...changes };
      const output = docker(['run','--pull','never','--rm','--read-only','--cap-drop','ALL','--security-opt','no-new-privileges:true',
        '--network',name,'--user','33:33','--label','io.zinema.phase=zl14','--label','io.zinema.run=' + run,
        '--label','com.docker.compose.project=' + name,'--label','com.docker.compose.service=database-test',
        '--tmpfs','/tmp:rw,noexec,nosuid,size=32m,uid=33,gid=33',
        '--tmpfs','/runtime:rw,noexec,nosuid,size=16m,uid=33,gid=33,mode=0700',
        ...Object.keys(env).flatMap(k => ['--env',k]),'--entrypoint','php',settings.ZINEMA_PROJECT + '-web:zl13',script], { env, allowFailure });
      if (output.status !== 0) {
        let error;
        for (const line of (output.stderr + '\n' + output.stdout).split('\n')) {
          try { const value = JSON.parse(line); if (/^[A-Z_]+$/.test(value.error || '')) error = value.error; } catch {}
        }
        return { exit: output.status, error };
      }
      return JSON.parse(output.stdout.trim());
    };
    const integration = php('tests/database/integration.php');
    process.stdout.write(JSON.stringify({ databaseIntegration: integration }) + '\n');
    const access = php('tests/database/access.php', { DB_USER: 'zinema_recovery_ci', DB_PASS: appPassword,
      BASE_URL: 'http://127.0.0.1:9', SESSION_SAVE_PATH: '/runtime/sessions' });
    process.stdout.write(JSON.stringify({ preservedLegacyAccess: access }) + '\n');
    const before = php('tests/database/digest.php');
    owned();
    const dump = docker(['exec',container,'sh','-c',
      'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysqldump --user=root --protocol=socket --single-transaction --skip-lock-tables --skip-triggers --no-tablespaces --set-gtid-purged=OFF --default-character-set=utf8mb4 --complete-insert --skip-extended-insert --skip-comments --skip-add-drop-table --skip-add-locks --skip-disable-keys zinema_recovery_test site_settings rate_limits movie_parts episode_parts schema_migrations']).stdout;
    if (/\b(?:DROP|TRUNCATE|CREATE\s+DATABASE|USE|GRANT|DEFINER)\b/i.test(dump)) failure('UNSAFE_SYNTHETIC_BACKUP_REFUSED');
    // Encryption roundtrip is in memory; no plaintext/key/report artifact is written.
    const key = crypto.randomBytes(32), iv = crypto.randomBytes(12), cipher = crypto.createCipheriv('aes-256-gcm', key, iv);
    const encrypted = Buffer.concat([cipher.update(dump,'utf8'),cipher.final()]);
    const decipher = crypto.createDecipheriv('aes-256-gcm',key,iv); decipher.setAuthTag(cipher.getAuthTag());
    const restored = Buffer.concat([decipher.update(encrypted),decipher.final()]).toString('utf8'); key.fill(0);
    if (Number(rootSql("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='zinema_zl14_restore' AND table_name<>'recovery_test_target';")) !== 0) failure('NONEMPTY_RESTORE_TARGET_REFUSED');
    rootSql('USE `zinema_zl14_restore`;\n' + restored);
    const after = php('tests/database/digest.php', { DB_NAME: 'zinema_zl14_restore' });
    if (before.sha256 !== after.sha256) failure('SYNTHETIC_RESTORE_MISMATCH');
    const restoredLedger = php('scripts/migrate.php', { DB_NAME: 'zinema_zl14_restore' });
    if (restoredLedger.skipped !== 4) failure('RESTORED_LEDGER_INVALID');
    process.stdout.write(JSON.stringify({ syntheticBackupRestore: 'passed', encryptedRoundtrip: 'AES-256-GCM',
      matchingStructureAndData: true, restoredLedgerVersions: 4, plaintextWritten: false, persistentVolumeTouched: false }) + '\n');
    docker(['pause',container]);
    try {
      const unavailable = php('scripts/migrate.php', {}, true);
      if (unavailable.exit !== 1 || unavailable.error !== 'MIGRATION_CONNECTION_FAILED') {
        process.stderr.write(JSON.stringify({ outageProbeExit: unavailable.exit ?? 0,
          error: unavailable.error || 'NO_CLASSIFIED_OUTAGE_ERROR' }) + '\n');
        failure('DATABASE_OUTAGE_UNVERIFIED');
      }
    } finally { docker(['unpause',container]); }
    if (php('scripts/migrate.php').skipped !== 4) failure('DATABASE_OUTAGE_RECOVERY_FAILED');
    process.stdout.write(JSON.stringify({ unavailableDatabaseRejected: true, unpausedDatabaseRecovered: true }) + '\n');
  } finally {
    if (container) { owned(); docker(['rm','--force',container]); }
    if (networkCreated) {
      const labels = JSON.parse(docker(['network','inspect','--format','{{json .Labels}}',name]).stdout);
      if (labels['io.zinema.run'] !== run) failure('UNOWNED_NETWORK_REFUSED');
      docker(['network','rm',name]);
    }
    process.stdout.write(JSON.stringify({ disposableCleanup: 'completed', existingVolumesRemoved: 0 }) + '\n');
  }
}
module.exports = { main };
if (require.main === module) main(process.argv[2]).catch(error => {
  process.stderr.write(JSON.stringify({ error: /^[A-Z_]+$/.test(error.code || '') ? error.code : 'DATABASE_TEST_FAILED', detailsWithheld: true }) + '\n');
  process.exitCode = 1;
});

'use strict';
const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const cp = require('node:child_process');
const vm = require('node:vm');
const root = path.resolve(__dirname, '../..');
function probe(mode, changes = {}) {
  const env = { ...(process.platform === 'win32' ? { SystemRoot: process.env.SystemRoot || 'C:\\Windows' } : {}),
    APP_ENV: 'test', DB_HOST: 'db', DB_NAME: 'zinema_recovery_test', DB_USER: 'zinema_recovery_migrator',
    DB_PASS: 'LOCAL_ONLY_' + 'a'.repeat(64), RECOVERY_CONTAINER_MODE: 'local',
    RECOVERY_DB_TOKEN: 'b'.repeat(64), RECOVERY_SERVER_UUID: '00000000-0000-0000-0000-000000000001', ...changes };
  const result = cp.spawnSync(process.env.PHP_BINARY || 'php', ['-n', path.join(__dirname,'runner-driver.php'), mode],
    { encoding: 'utf8', env, timeout: 5000 });
  assert.equal(result.status, 0); assert.equal(result.stderr, ''); return JSON.parse(result.stdout);
}
test('four independently sourced migrations have deterministic identifiers', () => {
  const result = probe('manifest');
  assert.deepEqual(result.versions, ['0001','0002','0003','0004']);
  assert.deepEqual(result.tables, ['site_settings','rate_limits','movie_parts','episode_parts']);
  assert.equal(result.fullApplicationReady, false);
});
for (const [mode, expected] of [['reversed','INVALID_MIGRATION_ORDER'],['modified','MIGRATION_CHECKSUM_MISMATCH'],
  ['missing','MIGRATION_FILE_UNAVAILABLE'],['unsafe','UNSUPPORTED_MIGRATION_SQL']]) {
  test('migration preflight rejects ' + mode + ' fixture before connection', () => assert.equal(probe(mode).error, expected));
}
for (const [name, changes] of [
  ['production environment',{ APP_ENV: 'production' }], ['arbitrary host',{ DB_HOST: 'example.invalid' }],
  ['unknown database',{ DB_NAME: 'unrelated' }], ['root role',{ DB_USER: 'root' }],
  ['missing credential',{ DB_PASS: '' }], ['missing local opt-in',{ RECOVERY_CONTAINER_MODE: '' }],
  ['missing disposable marker',{ RECOVERY_DB_TOKEN: '' }], ['missing server identity',{ RECOVERY_SERVER_UUID: '' }]
]) test('migration target validation refuses ' + name, () => assert.equal(probe('settings',changes).error,'MIGRATION_TARGET_REFUSED'));
test('fully explicit fixture settings pass static validation only', () => assert.equal(probe('settings').accepted,true));

test('legacy direct database adapter remains disabled before provider imports', () => {
  assert.throws(() => require('../../whatsapp-bot-deploy/config/database.js'), /Legacy direct database adapter disabled/);
});
function tokenReader(rows) {
  const source = fs.readFileSync(path.join(root,'whatsapp-bot-deploy/config/database.js'),'utf8');
  const start = source.indexOf('async function getTokenData('), end = source.indexOf('async function markTokenAsUsed(',start);
  assert.ok(start > 0 && end > start);
  let captured;
  const fn = vm.runInNewContext(source.slice(start,end) + '\ngetTokenData;', {
    pool: { execute: async (sql,args) => { captured = { sql,args }; return [rows]; } },
    console: { log() {}, error() {} },
    Date: class extends Date { constructor(...args) { super(...(args.length ? args : ['2020-01-01T00:00:00Z'])); } }
  }, { timeout: 1000 });
  return { fn, captured: () => captured };
}
for (const [label, rows, expected] of [
  ['missing', [], 'not_found'],
  ['inactive', [{ is_active:0 }], 'inactive'],
  ['used', [{ is_active:1,is_used:1 }], 'already_used'],
  ['expired', [{ is_active:1,is_used:0,expires_at:'2019-01-01T00:00:00Z' }], 'expired']
]) test('offline legacy Node token shape: ' + label, async () => {
  const reader = tokenReader(rows); const result = await reader.fn('fictional-key');
  assert.equal(result.valid,false); assert.equal(result.error,expected);
  assert.equal(reader.captured().args[0],'FICTIONAL-KEY');
});
test('offline Node lookup retains parameter binding and the unresolved token columns', async () => {
  const reader = tokenReader([{ is_active:1,is_used:0,expires_at:'2030-01-01T00:00:00Z',message_id:'fictional-message' }]);
  const result = await reader.fn("fictional' OR 1=1 --");
  assert.equal(result.valid,true);
  assert.equal(reader.captured().sql.includes("fictional'"),false);
  assert.match(reader.captured().sql,/WHERE t\.token = \?/);
  for (const column of ['message_id','is_used','is_active']) assert.ok(reader.captured().sql.includes('t.'+column));
});

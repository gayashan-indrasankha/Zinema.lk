'use strict';
const { test } = require('node:test');
const assert = require('node:assert/strict');
const path = require('node:path');
const cp = require('node:child_process');
const fs = require('node:fs');
const net = require('node:net');
const envTool = require('../../scripts/environment.cjs');
const root = path.resolve(__dirname, '../..');
function settings(profile) {
  return envTool.parseSettings(fs.readFileSync(path.join(root, 'infra/environments', profile, '.env.example'), 'utf8'));
}
function php(probe, dependency, changes = {}) {
  const env = {
    ...(process.platform === 'win32' ? { SystemRoot: process.env.SystemRoot || 'C:\\Windows' } : {}),
    APP_ENV: 'test', BASE_URL: 'http://127.0.0.1:9', DB_HOST: '127.0.0.1',
    DB_USER: 'zinema_recovery_fixture', DB_PASS: 'FAKE_TEST_PASSWORD', DB_NAME: 'zinema_recovery_test',
    SESSION_SAVE_PATH: path.join(root, '.runtime/zl13-fixture-sessions'), ...changes
  };
  for (const key of Object.keys(env)) if (env[key] === undefined) delete env[key];
  const result = cp.spawnSync(process.env.PHP_BINARY || 'php',
    ['-n', path.join(__dirname, 'health-driver.php'), probe, dependency || 'unavailable'],
    { encoding: 'utf8', env, timeout: 5000 });
  assert.equal(result.status, 0, 'Isolated PHP probe must exit normally');
  assert.equal(result.stderr, '');
  return JSON.parse(result.stdout);
}
for (const profile of envTool.profiles) {
  test(profile + ': only exact local identities and separate credentials are accepted', () => {
    const s = settings(profile);
    assert.strictEqual(envTool.validateSettings(s, profile), s);
    assert.equal(s.ZINEMA_PROFILE, profile);
    assert.notEqual(s.DB_PASS, s.MYSQL_ROOT_PASSWORD);
  });
}
for (const [label, changes] of [
  ['public base URL', { BASE_URL: 'https://example.invalid' }],
  ['production mode', { APP_ENV: 'production' }],
  ['foreign database', { DB_NAME: 'unrelated' }],
  ['foreign user', { DB_USER: 'root' }],
  ['foreign project', { ZINEMA_PROJECT: 'unrelated' }],
  ['non-profile port', { WEB_PORT: '3306' }],
  ['unknown secret key', { OTHER_SECRET: 'fictional' }],
  ['identical app and root credentials', { DB_PASS: 'FAKE_ZL13_SAME', MYSQL_ROOT_PASSWORD: 'FAKE_ZL13_SAME' }]
]) {
  test('managed environment rejects ' + label, () => {
    assert.throws(() => envTool.validateSettings({ ...settings('development'), ...changes }, 'development'));
  });
}
test('duplicate and malformed managed environment lines are rejected', () => {
  assert.throws(() => envTool.parseSettings('DB_PASS=fictional\nDB_PASS=other\n'));
  assert.throws(() => envTool.parseSettings('not a setting\n'));
});
test('unknown profile cannot select another file or production settings', () => {
  for (const value of ['production', '../development', '']) assert.throws(() => envTool.managedPaths(value));
});
test('process liveness needs no database or application configuration', () => {
  const result = php('live', 'unavailable', { APP_ENV: undefined, DB_PASS: undefined });
  assert.equal(result.http, 200);
  assert.deepEqual(JSON.parse(result.output), { status: 'alive' });
});
test('unavailable dependency reports 503 without exposing connection values', () => {
  const result = php('dependencies');
  assert.equal(result.http, 503);
  assert.deepEqual(JSON.parse(result.output), { status: 'dependency_unavailable' });
});
test('database availability does not claim schema readiness', () => {
  const result = php('dependencies', 'available');
  assert.equal(result.http, 200);
  assert.deepEqual(JSON.parse(result.output), { status: 'dependencies_available', schema: 'unverified' });
});
test('readiness remains 503 even with available dependencies', () => {
  const result = php('ready', 'available');
  assert.equal(result.http, 503);
  assert.deepEqual(JSON.parse(result.output), { status: 'schema_unverified', next_issue: 'ZL-14' });
});
test('unknown health probes return minimal 404 responses', () => {
  assert.equal(php('unrecognized').http, 404);
});
test('original non-container loopback configuration remains accepted', () => {
  assert.equal(php('config').output, 'configuration_valid');
});
test('container database hostname needs the exact explicit local opt-in', () => {
  assert.equal(php('config', '', { DB_HOST: 'db', RECOVERY_CONTAINER_MODE: 'local' }).output, 'configuration_valid');
  for (const flag of [undefined, 'true', 'production']) {
    const r = php('config', '', { DB_HOST: 'db', RECOVERY_CONTAINER_MODE: flag });
    assert.equal(r.http, 503);
    assert.ok(r.output.startsWith('Recovery configuration unavailable:'));
  }
});
test('local opt-in cannot authorize an arbitrary database hostname', () => {
  assert.equal(php('config', '', { DB_HOST: 'example.invalid', RECOVERY_CONTAINER_MODE: 'local' }).http, 503);
});
for (const [key, value] of [['APP_ENV', undefined], ['DB_PASS', undefined], ['DB_NAME', 'unrelated'],
  ['MAIL_MODE', 'live']]) {
  test('pure bootstrap safely rejects invalid configuration: ' + key, () => {
    const result = php('config', '', { [key]: value });
    assert.equal(result.http, 503);
    assert.ok(result.output.startsWith('Recovery configuration unavailable:'));
    assert.equal(result.output.includes('FAKE_TEST_PASSWORD'), false);
  });
}
for (const key of ['PAYMENT_MODE', 'WHATSAPP_MODE', 'EXTRACTOR_MODE', 'MEDIA_MODE']) {
  test('unapproved mode cannot activate through bootstrap: ' + key, () => {
    assert.equal(php('config', '', { [key]: 'live', ALLOW_LIVE_INTEGRATIONS:'true' }).output, 'configuration_valid');
  });
}
test('existing bot lockfile agrees with its manifest without running install scripts', () => {
  const pkg = JSON.parse(fs.readFileSync(path.join(root, 'whatsapp-bot-deploy/package.json'), 'utf8'));
  const lock = JSON.parse(fs.readFileSync(path.join(root, 'whatsapp-bot-deploy/package-lock.json'), 'utf8'));
  assert.equal(lock.lockfileVersion, 3);
  assert.deepEqual(lock.packages[''].dependencies, pkg.dependencies);
  assert.deepEqual(lock.packages[''].devDependencies, pkg.devDependencies);
});
test('reviewed build context exactly matches deny-by-default Docker exclusions', () => {
  const paths = envTool.checkContext();
  assert.ok(paths.includes('apps/web/src/Views/Ads.php'));
  assert.ok(paths.includes('whatsapp-bot-deploy/src/config/environment.js'));
  assert.equal(paths.some(p => /(?:^|\/)\.env$|\.recovery-private|whatsapp-session/.test(p)), false);
  const migrations = JSON.parse(fs.readFileSync(path.join(root,'database/migrations.json'),'utf8'));
  const content = JSON.parse(fs.readFileSync(path.join(root,'database/content-migrations.json'),'utf8'));
  assert.deepEqual(paths.filter(p => /\.sql$/.test(p)).sort(), [...migrations.migrations, ...content.migrations].map(m => m.file).sort());
  assert.equal(paths.includes('Zinema.lk Website/Database.sql'), false);
  assert.equal(paths.includes('Zinema.lk Website/admin/whatsapp-bot-tracking.php'), false);
});
test('port collision is detected without stopping the existing listener', async () => {
  const server = net.createServer();
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  try {
    await assert.rejects(envTool.freePort(server.address().port), /LOCAL_PORT_IN_USE/);
    assert.equal(server.listening, true);
  } finally { await new Promise(resolve => server.close(resolve)); }
});
test('ambient database and provider secrets never enter Docker interpolation environment', () => {
  const before = process.env.DB_PASS;
  try {
    process.env.DB_PASS = 'FAKE_TEST_AMBIENT';
    assert.equal(Object.hasOwn(envTool.cleanEnvironment(), 'DB_PASS'), false);
  } finally {
    if (before === undefined) delete process.env.DB_PASS; else process.env.DB_PASS = before;
  }
});

function localPlan() {
  const env = settings('development');
  const labels = { 'io.zinema.phase': 'zl13', 'io.zinema.profile': 'development' };
  const common = { read_only: true, cap_drop: ['ALL'], security_opt: ['no-new-privileges:true'],
    networks: { isolated: {} }, labels };
  const web = { ...structuredClone(common), user: '33:33',
    networks: { isolated: {}, ingress: {} },
    build: { context: root, dockerfile: 'infra/php/Dockerfile' },
    ports: [{ host_ip: '127.0.0.1', published: env.WEB_PORT, target: 8080 }],
    environment: { APP_ENV: env.APP_ENV, DB_NAME: env.DB_NAME, DB_USER: env.DB_USER,
      DB_HOST: 'db', RECOVERY_CONTAINER_MODE: 'local', DB_PASS: env.DB_PASS } };
  for (const key of ['MAIL', 'OAUTH', 'WHATSAPP', 'MEDIA', 'EXTRACTOR', 'PAYMENT']) web.environment[key + '_MODE'] = 'disabled';
  for (const key of ['ALLOW_LIVE_INTEGRATIONS', 'ALLOW_LIVE_WHATSAPP', 'ALLOW_PRODUCTION_API', 'FUTURE_AI_ENABLED']) web.environment[key] = 'false';
  const db = { ...structuredClone(common), user: '999:999',
    build: { context: root, dockerfile: 'infra/mysql/Dockerfile' },
    environment: { MYSQL_DATABASE: env.DB_NAME, MYSQL_USER: env.DB_USER, MYSQL_PASSWORD: env.DB_PASS },
    volumes: [{ type: 'volume', source: 'mysql_data', target: '/var/lib/mysql' }] };
  return { env, plan: { name: env.ZINEMA_PROJECT, services: { web, db },
    networks: { isolated: { internal: true }, ingress: { driver: 'bridge' } }, volumes: { mysql_data: { labels } } } };
}
test('a reviewed synthetic Compose plan passes the execution gate', () => {
  const { env, plan } = localPlan();
  assert.equal(envTool.validatePlan(plan, env).schemaReady, false);
});
for (const [label, mutate] of [
  ['public port', p => { p.services.web.ports[0].host_ip = '0.0.0.0'; }],
  ['outbound network', p => { p.networks.isolated.internal = false; }],
  ['repository bind mount', p => { p.services.web.volumes = [{ type: 'bind', source: root }]; }],
  ['root web account', p => { p.services.web.user = '0:0'; }],
  ['live WhatsApp', p => { p.services.web.environment.WHATSAPP_MODE = 'live'; }],
  ['foreign volume', p => { p.services.db.volumes[0].source = 'unrelated'; }],
  ['production database', p => { p.services.web.environment.DB_NAME = 'unrelated'; }],
  ['database root credential in web', p => { p.services.web.environment.MYSQL_ROOT_PASSWORD = 'FAKE_TEST_VALUE'; }]
]) {
  test('execution gate rejects ' + label, () => {
    const { env, plan } = localPlan(); mutate(plan);
    assert.throws(() => envTool.validatePlan(plan, env));
  });
}

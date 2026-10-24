'use strict';
const fs = require('node:fs');
const path = require('node:path');
const cp = require('node:child_process');
const crypto = require('node:crypto');
const net = require('node:net');
const root = path.resolve(__dirname, '..');
const profiles = ['development', 'staging', 'ci'];
const marker = '# Managed ZL-13 local configuration; no production credentials.';
const scannerImage = 'ghcr.io/gitleaks/gitleaks:v8.30.1@sha256:c00b6bd0aeb3071cbcb79009cb16a60dd9e0a7c60e2be9ab65d25e6bc8abbb7f';
function fail(code) { const e = new Error(code); e.code = code; throw e; }
function cleanEnvironment() {
  const result = {};
  for (const key of ['PATH', 'Path', 'SystemRoot', 'WINDIR', 'USERPROFILE', 'HOME', 'HOMEDRIVE',
    'HOMEPATH', 'APPDATA', 'LOCALAPPDATA', 'TEMP', 'TMP', 'ProgramFiles', 'ProgramFiles(x86)',
    'ProgramW6432', 'ProgramData', 'CommonProgramFiles', 'CommonProgramFiles(x86)']) {
    if (process.env[key]) result[key] = process.env[key];
  }
  return result; // No ambient DB, Compose, Docker-host or provider settings.
}
function managedPaths(profile) {
  if (!profiles.includes(profile)) fail('UNKNOWN_PROFILE');
  const directory = path.join(root, 'infra/environments', profile);
  return { directory, example: path.join(directory, '.env.example'), local: path.join(directory, '.env') };
}
function parseSettings(text) {
  const result = {};
  for (const line of text.split(/\r?\n/)) {
    if (!line.trim() || line.startsWith('#')) continue;
    const m = line.match(/^([A-Z_]+)=(.*)$/);
    if (!m || Object.hasOwn(result, m[1])) fail('INVALID_ENV_FORMAT');
    result[m[1]] = m[2];
  }
  return result;
}
function validateSettings(env, profile) {
  managedPaths(profile);
  const keys = ['ZINEMA_PROFILE', 'ZINEMA_PROJECT', 'APP_ENV', 'BASE_URL', 'WEB_PORT',
    'DB_NAME', 'DB_USER', 'DB_PASS', 'MYSQL_ROOT_PASSWORD'];
  if (Object.keys(env).length !== keys.length || keys.some(k => !env[k])) fail('MISSING_OR_UNKNOWN_ENV_KEY');
  const app = profile === 'development' ? 'development' : 'test';
  const port = { development: '8080', staging: '8081', ci: '8082' }[profile];
  if (env.ZINEMA_PROFILE !== profile || env.APP_ENV !== app || env.WEB_PORT !== port ||
      env.BASE_URL !== 'http://127.0.0.1:' + port || env.DB_NAME !== 'zinema_recovery_' + app ||
      env.DB_USER !== 'zinema_recovery_' + profile) fail('UNSAFE_ENV_IDENTITY');
  const project = profile === 'ci' ? /^zinema-zl13-ci(?:-\d+)?$/ : new RegExp('^zinema-zl13-' + profile + '$');
  if (!project.test(env.ZINEMA_PROJECT)) fail('UNSAFE_PROJECT_NAME');
  for (const key of ['DB_PASS', 'MYSQL_ROOT_PASSWORD']) {
    if (!/^(?:LOCAL_ONLY_[a-f0-9]{64}|FAKE_ZL13_[A-Z_]+)$/.test(env[key])) fail('UNSAFE_LOCAL_CREDENTIAL_FORMAT');
  }
  if (env.DB_PASS === env.MYSQL_ROOT_PASSWORD) fail('DATABASE_IDENTITIES_MUST_DIFFER');
  return env;
}
function loadSettings(profile, example = false) {
  const p = managedPaths(profile), file = example ? p.example : p.local;
  if (!fs.existsSync(file)) fail('RUN_INIT_FIRST');
  const text = fs.readFileSync(file, 'utf8');
  if (!example && !text.startsWith(marker + '\n')) fail('UNMANAGED_ENV_FILE_PRESERVED');
  return validateSettings(parseSettings(text), profile);
}
function initialize(profile) {
  const p = managedPaths(profile);
  if (fs.existsSync(p.local)) return { profile, initialized: false, existingPreserved: true };
  const env = loadSettings(profile, true);
  env.DB_PASS = 'LOCAL_ONLY_' + crypto.randomBytes(32).toString('hex');
  env.MYSQL_ROOT_PASSWORD = 'LOCAL_ONLY_' + crypto.randomBytes(32).toString('hex');
  if (profile === 'ci') env.ZINEMA_PROJECT += '-' + Date.now();
  fs.writeFileSync(p.local, marker + '\n' + Object.entries(env).map(([k, v]) => k + '=' + v).join('\n') + '\n',
    { flag: 'wx', mode: 0o600 });
  return { profile, initialized: true, valuesRedacted: true };
}
function docker(args, { input, timeout = 30000, allowFailure = false } = {}) {
  const r = cp.spawnSync('docker', args, { cwd: root, env: cleanEnvironment(), input,
    encoding: 'utf8', timeout, maxBuffer: 25 * 1024 * 1024 });
  if ((r.error || r.status !== 0) && !allowFailure) fail(r.error?.code === 'ETIMEDOUT' ? 'DOCKER_TIMEOUT' : 'DOCKER_COMMAND_FAILED');
  return r;
}
function doctor() {
  const context = docker(['context', 'inspect', '--format', '{{json .Endpoints.docker.Host}}']);
  const host = JSON.parse(context.stdout);
  if (!/^(?:npipe:\/\/|unix:\/\/)/.test(host)) fail('REMOTE_DOCKER_DAEMON_REFUSED');
  const engine = docker(['info', '--format', '{{json .OSType}}']);
  if (JSON.parse(engine.stdout) !== 'linux') fail('LINUX_CONTAINERS_REQUIRED');
  const version = docker(['compose', 'version', '--short']).stdout.trim();
  return { localLinuxDaemon: true, composeVersion: version };
}
function composeArgs(profile, env, args) {
  return ['compose', '--project-directory', root, '--env-file', managedPaths(profile).local,
    '-f', path.join(root, 'compose.yaml'), '-p', env.ZINEMA_PROJECT, ...args];
}
function validatePlan(plan, env) {
  if (plan.name !== env.ZINEMA_PROJECT || Object.keys(plan.services).sort().join(',') !== 'db,web') fail('UNEXPECTED_SERVICES');
  if (Object.keys(plan.networks).sort().join(',') !== 'ingress,isolated' ||
      !plan.networks.isolated.internal || plan.networks.ingress.internal) fail('UNEXPECTED_NETWORKS');
  for (const [name, service] of Object.entries(plan.services)) {
    if (service.privileged || !service.read_only || service.network_mode ||
        service.user !== (name === 'web' ? '33:33' : '999:999') ||
        service.cap_add?.length || !service.cap_drop?.includes('ALL') ||
        !service.security_opt?.some(v => v.startsWith('no-new-privileges')) ||
        Object.keys(service.networks || {}).sort().join(',') !== (name === 'web' ? 'ingress,isolated' : 'isolated')) fail('UNSAFE_CONTAINER_POLICY');
    if (service.volumes?.some(v => v.type === 'bind')) fail('BIND_MOUNT_REFUSED');
    if (service.build.context !== root || service.build.dockerfile !== 'infra/' + (name === 'web' ? 'php' : 'mysql') + '/Dockerfile') fail('UNREVIEWED_BUILD_CONTEXT');
    if (service.labels?.['io.zinema.phase'] !== 'zl13' ||
        service.labels?.['io.zinema.profile'] !== env.ZINEMA_PROFILE) fail('MISSING_OWNERSHIP_LABEL');
  }
  const web = plan.services.web, db = plan.services.db;
  if (db.ports?.length || web.ports?.length !== 1 || web.ports[0].host_ip !== '127.0.0.1' ||
      String(web.ports[0].published) !== env.WEB_PORT || web.ports[0].target !== 8080) fail('NONLOCAL_PORT_REFUSED');
  if (web.volumes?.length || db.volumes?.length !== 1 ||
      db.volumes[0].type !== 'volume' || db.volumes[0].source !== 'mysql_data' ||
      db.volumes[0].target !== '/var/lib/mysql') fail('UNEXPECTED_STORAGE');
  for (const key of ['MAIL', 'OAUTH', 'WHATSAPP', 'MEDIA', 'EXTRACTOR', 'PAYMENT']) {
    if (web.environment[key + '_MODE'] !== 'disabled') fail('LIVE_INTEGRATION_REFUSED');
  }
  for (const key of ['ALLOW_LIVE_INTEGRATIONS', 'ALLOW_LIVE_WHATSAPP', 'ALLOW_PRODUCTION_API', 'FUTURE_AI_ENABLED']) {
    if (web.environment[key] !== 'false') fail('LIVE_FLAG_REFUSED');
  }
  if (web.environment.DB_HOST !== 'db' || web.environment.RECOVERY_CONTAINER_MODE !== 'local' ||
      web.environment.APP_ENV !== env.APP_ENV || web.environment.DB_NAME !== env.DB_NAME ||
      web.environment.DB_USER !== env.DB_USER || db.environment.MYSQL_DATABASE !== env.DB_NAME ||
      db.environment.MYSQL_USER !== env.DB_USER ||
      web.environment.DB_PASS !== env.DB_PASS || db.environment.MYSQL_PASSWORD !== env.DB_PASS ||
      Object.hasOwn(web.environment, 'MYSQL_ROOT_PASSWORD')) fail('DATABASE_BOUNDARY_INVALID');
  if (plan.volumes.mysql_data.labels['io.zinema.phase'] !== 'zl13') fail('UNOWNED_VOLUME_REFUSED');
  return { services: ['db', 'web'], loopbackPort: Number(env.WEB_PORT), internalDatabaseNetwork: true,
    webEgressRestricted: false,
    bindMounts: 0, schemaReady: false };
}
function dockerIgnore(files) {
  const parents = new Set();
  for (const file of files) {
    let dir = path.posix.dirname(file);
    while (dir !== '.') { parents.add(dir); dir = path.posix.dirname(dir); }
  }
  return '# ZL-13 reviewed build inputs only. Real environments, Git and private stores never enter context.\n**\n' +
    [...parents].sort((a, b) => a.split('/').length - b.split('/').length || a.localeCompare(b))
      .map(p => '!' + p + '/\n' + p + '/*').join('\n') +
    '\n' + [...files].sort().map(p => '!' + p).join('\n') + '\n';
}
function checkContext() {
  const manifest = JSON.parse(fs.readFileSync(path.join(root, 'infra/environment-files.json'), 'utf8'));
  if (manifest.schemaVersion !== 1 || new Set(manifest.files).size !== manifest.files.length) fail('INVALID_CONTEXT_MANIFEST');
  const migrationManifest = JSON.parse(fs.readFileSync(path.join(root, 'database/migrations.json'), 'utf8'));
  const contentManifest = JSON.parse(fs.readFileSync(path.join(root, 'database/content-migrations.json'), 'utf8'));
  const exactContentFiles = ['database/content-migrations/0005_content_rights_records.sql', 'database/content-migrations/0006_content_rights_events.sql'];
  if (contentManifest.scope !== 'synthetic-rights-extension' || contentManifest.fullApplicationReady !== false ||
      JSON.stringify(contentManifest.migrations.map(m => m.file)) !== JSON.stringify(exactContentFiles)) fail('UNREVIEWED_CONTENT_SQL_PROFILE');
  const reviewedSql = new Map([...migrationManifest.migrations, ...contentManifest.migrations].map(m => [m.file, m.sha256]));
  for (const file of manifest.files) {
    if (typeof file !== 'string' || file.includes('\\') || file.includes('..') || path.isAbsolute(file) ||
        /(?:^|\/)(?:\.git|\.recovery-private|node_modules|uploads|whatsapp-session|logs|backups)(?:\/|$)|(?:^|\/)\.env$/i.test(file) ||
        (/\.sql$/i.test(file) && (!/^database\/(?:migrations|content-migrations)\/\d{4}_[a-z_]+\.sql$/.test(file) || !reviewedSql.has(file)))) fail('PRIVATE_CONTEXT_INPUT_REFUSED');
    const full = path.join(root, file);
    if (!fs.existsSync(full) || fs.lstatSync(full).isSymbolicLink() || !fs.lstatSync(full).isFile()) fail('MISSING_OR_UNSAFE_CONTEXT_INPUT');
    let parent = path.dirname(full);
    while (parent !== root) {
      if (fs.lstatSync(parent).isSymbolicLink()) fail('LINKED_CONTEXT_PARENT');
      parent = path.dirname(parent);
    }
    if (/\.sql$/.test(file)) {
      const sql = fs.readFileSync(full, 'utf8');
      if (!/^CREATE TABLE (?:site_settings|rate_limits|movie_parts|episode_parts|content_rights_records|content_rights_events) \(/.test(sql) ||
          (sql.match(/;/g) || []).length !== 1 || /\b(?:DROP|TRUNCATE|INSERT|DELETE|GRANT|DEFINER|OUTFILE)\b/i.test(sql) ||
          crypto.createHash('sha256').update(sql).digest('hex') !== reviewedSql.get(file)) fail('UNREVIEWED_SQL_CONTEXT_INPUT');
    }
  }
  if (fs.readFileSync(path.join(root, '.dockerignore'), 'utf8') !== dockerIgnore(manifest.files)) fail('DOCKERIGNORE_MANIFEST_DRIFT');
  return manifest.files;
}
function configuration(profile, checkBuildInputs = true) {
  doctor(); const env = loadSettings(profile); if (checkBuildInputs) checkContext();
  const r = docker(composeArgs(profile, env, ['config', '--format', 'json']));
  const plan = JSON.parse(r.stdout);
  return { env, plan, summary: validatePlan(plan, env) };
}
function ownedResources(env, plan) {
  for (const [kind, name] of [['volume', plan.volumes.mysql_data.name],
    ...Object.values(plan.networks).map(network => ['network', network.name])]) {
    const r = docker([kind, 'inspect', name], { allowFailure: true });
    if (r.status !== 0) continue;
    const labels = JSON.parse(r.stdout)[0].Labels;
    if (labels?.['io.zinema.phase'] !== 'zl13' || labels?.['io.zinema.profile'] !== env.ZINEMA_PROFILE) fail('EXISTING_UNOWNED_RESOURCE_REFUSED');
  }
  const ids = docker(['ps', '-aq', '--filter', 'label=com.docker.compose.project=' + env.ZINEMA_PROJECT]).stdout.trim().split(/\s+/).filter(Boolean);
  for (const id of ids) {
    const labels = JSON.parse(docker(['inspect', '--format', '{{json .Config.Labels}}', id]).stdout);
    if (labels['io.zinema.phase'] !== 'zl13' || labels['io.zinema.profile'] !== env.ZINEMA_PROFILE) fail('UNOWNED_CONTAINER_REFUSED');
  }
  return ids;
}
async function freePort(port) {
  await new Promise((resolve, reject) => {
    const server = net.createServer();
    server.once('error', () => reject(Object.assign(new Error('LOCAL_PORT_IN_USE'), { code: 'LOCAL_PORT_IN_USE' })));
    server.listen(Number(port), '127.0.0.1', () => server.close(resolve));
  });
}
async function request(port, route) {
  try {
    const response = await fetch('http://127.0.0.1:' + port + route, { signal: AbortSignal.timeout(5000), redirect: 'manual' });
    const text = await response.text();
    let body; try { body = JSON.parse(text); } catch { body = null; }
    return { status: response.status, body };
  } catch (error) {
    fail(error.name === 'TimeoutError' ? 'HEALTH_REQUEST_TIMEOUT' : 'HEALTH_CONNECTION_FAILED');
  }
}
async function smoke(port) {
  const live = await request(port, '/__health/live');
  const dependencies = await request(port, '/__health/dependencies');
  const ready = await request(port, '/__health/ready');
  if (live.status !== 200 || live.body?.status !== 'alive' || dependencies.status !== 200 ||
      dependencies.body?.schema !== 'unverified' || ready.status !== 503 ||
      ready.body?.status !== 'schema_unverified') fail('HEALTH_CONTRACT_FAILED');
  const paths = [];
  for (const [route, allowed] of [['/css/style.css', [200]], ['/.env', [403, 404]],
    ['/.git/config', [403, 404]], ['/includes/environment.php', [403]],
    ['/fb-video-api/index.js', [403, 404]], ['/download.php', [403]], ['/api/subscribe.php', [403]],
    ['/admin/run_migration.php', [403]]]) {
    const response = await request(port, route);
    if (!allowed.includes(response.status)) fail('SAFE_HTTP_ROUTE_CHECK_FAILED');
    paths.push({ route, status: response.status });
  }
  return { live: live.status, dependencies: dependencies.status, readiness: ready.status,
    readinessReason: ready.body.status, safePaths: paths };
}
async function main(args) {
  const [action, profile = 'development'] = args;
  if (action === 'doctor') { console.log(JSON.stringify(doctor())); return; }
  if (action === 'init') { console.log(JSON.stringify(initialize(profile))); return; }
  if (action === 'context') { console.log(JSON.stringify({ reviewedInputs: checkContext().length })); return; }
  if (action === 'secrets') {
    doctor(); const files = checkContext().filter(p => !/\.(?:png|ico)$/i.test(p));
    docker(['pull', scannerImage], { timeout: 120000 });
    const input = files.map(p => 'FILE ' + p + '\n' + fs.readFileSync(path.join(root, p), 'utf8')).join('\n');
    docker(['run', '--rm', '--read-only', '--cap-drop', 'ALL', '--security-opt', 'no-new-privileges:true', '--user', '65532:65532', '--network', 'none', '-i', scannerImage, 'stdin', '--redact=100', '--no-banner', '--log-level', 'error'], { input, timeout: 120000 });
    console.log(JSON.stringify({ secretScan: 'passed', textFiles: files.length, valuesRedacted: true })); return;
  }
  if (!['config', 'up', 'down', 'status', 'health', 'test', 'logs', 'restart', 'failures', 'audit'].includes(action)) fail('UNKNOWN_COMMAND');
  const { env, plan, summary } = configuration(profile, !['down', 'status', 'logs'].includes(action));
  const existing = ownedResources(env, plan);
  if (action === 'config') { console.log(JSON.stringify({ profile, ...summary, valuesRedacted: true })); return; }
  if (action === 'up') {
    if (!existing.length) await freePort(env.WEB_PORT);
    docker(composeArgs(profile, env, ['up', '-d', '--build', '--wait', '--wait-timeout', '180']), { timeout: 1200000 });
    console.log(JSON.stringify({ profile, started: true, ...await smoke(env.WEB_PORT) })); return;
  }
  if (action === 'down') {
    docker(composeArgs(profile, env, ['down', '--remove-orphans']), { timeout: 120000 });
    console.log(JSON.stringify({ profile, stopped: true, volumesPreserved: true })); return;
  }
  if (action === 'health') { console.log(JSON.stringify({ profile, ...await smoke(env.WEB_PORT) })); return; }
  if (action === 'status') {
    const r = docker(composeArgs(profile, env, ['ps', '--format', 'json']));
    const raw = r.stdout.trim();
    const services = raw.startsWith('[') ? JSON.parse(raw) : raw.split('\n').filter(Boolean).map(JSON.parse);
    console.log(JSON.stringify({ profile, services: services.map(s => ({ service: s.Service, state: s.State, health: s.Health })) })); return;
  }
  if (action === 'logs') {
    const r = docker(composeArgs(profile, env, ['logs', '--no-color', '--tail', '100']));
    console.log(JSON.stringify({ profile, capturedLines: r.stdout.split('\n').filter(Boolean).length,
      errorLines: r.stdout.split('\n').filter(l => /error|fatal|denied/i.test(l)).length,
      rawLogContentsWithheld: true })); return;
  }
  if (action === 'test') {
    const r = docker(composeArgs(profile, env, ['run', '--rm', '--no-deps', '--entrypoint', 'node', 'web', 'scripts/verify.cjs']), { timeout: 180000 });
    console.log(r.stdout.trim()); return;
  }
  if (action === 'audit') {
    const r = docker(composeArgs(profile, env, ['exec', '-T', 'web', 'php', 'infra/runtime/database-audit.php']));
    console.log(r.stdout.trim()); return;
  }
  if (action === 'restart') {
    docker(composeArgs(profile, env, ['restart', '--timeout', '10', 'web']), { timeout: 60000 });
    await new Promise(resolve => setTimeout(resolve, 1500));
    console.log(JSON.stringify({ profile, restarted: true, ...await smoke(env.WEB_PORT) })); return;
  }
  if (action === 'failures') {
    const badCredentials = docker(composeArgs(profile, env,
      ['exec', '-T', '-e', 'DB_PASS=FAKE_ZL13_INVALID_PASSWORD', 'web', 'php', 'infra/runtime/database-audit.php']),
      { allowFailure: true });
    if (badCredentials.status !== 1 || !badCredentials.stderr.includes('DEPENDENCY_UNAVAILABLE')) fail('INVALID_CREDENTIAL_FAILURE_UNVERIFIED');
    // Only this owned local project's DB is temporarily stopped; persistent volumes are retained.
    docker(composeArgs(profile, env, ['stop', '--timeout', '10', 'db']), { timeout: 60000 });
    let unavailable;
    try {
      const live = await request(env.WEB_PORT, '/__health/live');
      unavailable = await request(env.WEB_PORT, '/__health/dependencies');
      if (live.status !== 200 || unavailable.status !== 503) fail('DEPENDENCY_FAILURE_NOT_ISOLATED');
    } finally {
      docker(composeArgs(profile, env, ['start', 'db']), { timeout: 60000 });
    }
    let recovered = false;
    for (let i = 0; i < 20; i++) {
      const r = await request(env.WEB_PORT, '/__health/dependencies');
      if (r.status === 200) { recovered = true; break; }
      await new Promise(resolve => setTimeout(resolve, 1000));
    }
    if (!recovered) fail('DEPENDENCY_RECOVERY_FAILED');
    console.log(JSON.stringify({ profile, invalidSyntheticCredentialsRejected: true,
      databaseOutage: unavailable.status, recovered, volumesPreserved: true })); return;
  }
}
module.exports = { root, profiles, parseSettings, validateSettings, validatePlan, dockerIgnore,
  checkContext, initialize, cleanEnvironment, managedPaths, freePort, smoke, main };
if (require.main === module) main(process.argv.slice(2)).catch(e => {
  process.stderr.write(JSON.stringify({ error: /^[A-Z_]+$/.test(e.code || '') ? e.code : 'ENVIRONMENT_CHECK_FAILED',
    valuesRedacted: true }) + '\n'); process.exitCode = 1;
});

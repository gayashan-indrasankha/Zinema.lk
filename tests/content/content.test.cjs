'use strict';
const { test, after } = require('node:test');
const assert = require('node:assert/strict');
const path = require('node:path');
const cp = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const crypto = require('node:crypto');
const root = path.resolve(__dirname, '../..');
const env = process.platform === 'win32' ? { SystemRoot: process.env.SystemRoot || 'C:\\Windows' } : {};
function php(name) {
  return cp.spawnSync(process.env.PHP_BINARY || 'php', ['-n', path.join(__dirname, 'contracts.php'), name], {
    cwd: root, env, encoding: 'utf8', timeout: 10000, maxBuffer: 1e6
  });
}
const list = php('list');
assert.equal(list.status, 0, 'PHP contract fixture must be available');
for (const name of JSON.parse(list.stdout)) test('ZL-15: ' + name, () => {
  const result = php(name);
  assert.equal(result.status, 0, result.stderr);
  assert.deepEqual(JSON.parse(result.stdout), { passed: true, synthetic: true });
});
test('ZL-15: domain has no upload, fetch, shell or legacy bootstrap path', () => {
  for (const file of fs.readdirSync(path.join(root, 'apps/web/src/Content'))) {
    const text = fs.readFileSync(path.join(root, 'apps/web/src/Content', file), 'utf8');
    assert.doesNotMatch(text, /curl_exec|move_uploaded_file|shell_exec|\bexec\s*\(|admin\/config\.php|file_get_contents\s*\(/);
  }
});
const parent = fs.realpathSync.native(os.tmpdir()), owned = new Set();
after(() => {
  for (const directory of owned) {
    assert.equal(path.dirname(directory), parent);
    assert.ok(path.basename(directory).startsWith('zinema-zl15-manifest-'));
    assert.equal(fs.realpathSync.native(directory), directory);
    assert.equal(fs.lstatSync(directory).isSymbolicLink(), false);
    fs.rmSync(directory, { recursive: true, force: false });
  }
});
for (const [name, code, mutate] of [
  ['changed bytes', 'CONTENT_MIGRATION_CHECKSUM_MISMATCH', (m, dir) => fs.appendFileSync(path.join(dir, m.migrations[0].file), '\n')],
  ['reversed order', 'INVALID_CONTENT_MIGRATION_ORDER', m => m.migrations.reverse()],
  ['missing entry', 'INVALID_CONTENT_MIGRATION_MANIFEST', m => m.migrations.pop()],
  ['false readiness claim', 'INVALID_CONTENT_MIGRATION_MANIFEST', m => { m.fullApplicationReady = true; }],
  ['unreviewed table', 'UNREVIEWED_CONTENT_MIGRATION', m => { m.migrations[0].table = 'movies'; }],
  ['unreviewed path', 'UNREVIEWED_CONTENT_MIGRATION', m => { m.migrations[0].file = 'database/content-migrations/0007_movies.sql'; }],
  ['missing file', 'UNREVIEWED_CONTENT_MIGRATION', (m, dir) => fs.unlinkSync(path.join(dir, m.migrations[0].file))],
  ['dangerous SQL despite a matching checksum', 'UNSUPPORTED_MIGRATION_SQL', (m, dir) => {
    const entry = m.migrations[0], file = path.join(dir, entry.file);
    fs.appendFileSync(file, '\nDROP TABLE fictional_fixture;');
    entry.sha256 = crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex');
  }]
]) test('ZL-15: migration preflight rejects ' + name + ' before connecting', () => {
  const directory = fs.mkdtempSync(path.join(parent, 'zinema-zl15-manifest-')); owned.add(directory);
  const base = JSON.parse(fs.readFileSync(path.join(root, 'database/migrations.json'), 'utf8'));
  const manifest = JSON.parse(fs.readFileSync(path.join(root, 'database/content-migrations.json'), 'utf8'));
  for (const file of ['database/migrations.json', ...base.migrations.map(m => m.file), ...manifest.migrations.map(m => m.file)]) {
    assert.match(file, /^database\/(?:migrations.json|(?:content-migrations|migrations)\/\d{4}_[a-z_]+\.sql)$/);
    const dest = path.join(directory, file); fs.mkdirSync(path.dirname(dest), { recursive: true });
    fs.copyFileSync(path.join(root, file), dest);
  }
  mutate(manifest, directory);
  fs.writeFileSync(path.join(directory, 'database/content-migrations.json'), JSON.stringify(manifest));
  const result = cp.spawnSync(process.env.PHP_BINARY || 'php', ['-n', path.join(__dirname, 'migration-driver.php'), directory], { env, encoding: 'utf8', timeout: 5000 });
  assert.equal(result.status, 0); assert.equal(result.stderr, '');
  assert.deepEqual(JSON.parse(result.stdout), { error: code });
});

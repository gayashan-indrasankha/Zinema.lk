'use strict';
// Synthetic fixtures only. Never export or inspect the real application/private stores.
const { test, after } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const cp = require('node:child_process');
const crypto = require('node:crypto');
const tool = require('./prepare-public-export.cjs');
const CLI = path.join(__dirname, 'prepare-public-export.cjs');
const owned = new Set();
const temporaryParent = fs.realpathSync.native(os.tmpdir());

function fixture() {
  const base = fs.mkdtempSync(path.join(temporaryParent, 'zinema-zl10-'));
  owned.add(base);
  const source = path.join(base, 'source'), output = path.join(base, 'export');
  fs.mkdirSync(source);
  const f = { base, source, output };
  put(f, 'README.md', 'Synthetic project fixture.\n');
  return f;
}
function put(f, rel, content) {
  const full = path.join(f.source, ...rel.split('/'));
  fs.mkdirSync(path.dirname(full), { recursive: true });
  fs.writeFileSync(full, content);
  return full;
}
function plan(f, output = f.output) { return tool.buildPlan({ source: f.source, output }); }
function publishFixture(f) { tool.performExport(plan(f)); }
function validate(f) { return tool.validateExport({ source: f.source, output: f.output }); }
function expectCode(fn, code) {
  assert.throws(fn, error => error instanceof tool.ExportError && error.code === code);
}
function cli(f, args = []) {
  return cp.spawnSync(process.execPath, [CLI, '--source', f.source, '--output', f.output, ...args],
    { encoding: 'utf8', cwd: f.base, timeout: 15000 });
}
function changeManifest(f, edit) {
  const p = path.join(f.output, tool.MANIFEST);
  const manifest = JSON.parse(fs.readFileSync(p, 'utf8'));
  edit(manifest);
  fs.writeFileSync(p, JSON.stringify(manifest));
}
after(() => {
  for (const base of owned) {
    // Verify every resolved recursive-delete target before cleanup, especially on Windows.
    assert.equal(path.dirname(base), temporaryParent);
    assert.ok(path.basename(base).startsWith('zinema-zl10-'));
    assert.ok(!fs.lstatSync(base).isSymbolicLink());
    assert.equal(fs.realpathSync.native(base), base);
    assert.notEqual(base, process.cwd());
    fs.rmSync(base, { recursive: true, force: false });
  }
});

test('default CLI is a dry run, writes nothing and preserves source', () => {
  const f = fixture(), before = fs.readFileSync(path.join(f.source, 'README.md'));
  const r = cli(f);
  assert.equal(r.status, 0);
  assert.equal(JSON.parse(r.stdout).action, 'dry-run');
  assert.equal(fs.existsSync(f.output), false);
  assert.deepEqual(fs.readFileSync(path.join(f.source, 'README.md')), before);
});

test('explicit synthetic export and validation preserve approved bytes, lockfiles and DDL', () => {
  const f = fixture();
  put(f, 'Zinema.lk Website/includes/functions.php', '<?php function fictional_example() { return true; }\n');
  put(f, 'whatsapp-bot-deploy/package.json', '{"name":"synthetic-fixture","version":"1.0.0"}\n');
  put(f, 'whatsapp-bot-deploy/package-lock.json', '{"lockfileVersion":3,"packages":{}}\n');
  put(f, 'Zinema.lk Website/migrations/movie_parts.sql',
    'CREATE TABLE movie_parts (id INT PRIMARY KEY, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP);\n');
  put(f, '.env.example', 'APP_ENV=development\nDB_PASS=REPLACE_FAKE_PASSWORD\n');
  const r = cli(f, ['--export']);
  assert.equal(r.status, 0);
  const v = cli(f, ['--validate']);
  assert.equal(v.status, 0);
  assert.equal(JSON.parse(v.stdout).valid, true);
  const m = JSON.parse(fs.readFileSync(path.join(f.output, tool.MANIFEST), 'utf8'));
  assert.equal(m.fileCount, 6);
  for (const entry of m.files) {
    const original = fs.readFileSync(path.join(f.source, entry.path));
    assert.deepEqual(fs.readFileSync(path.join(f.output, entry.path)), original);
    assert.equal(entry.sha256, crypto.createHash('sha256').update(original).digest('hex'));
    assert.equal(entry.size, original.length);
  }
});

test('deny rules prevent even opening sensitive paths regardless of ignore negation', () => {
  const f = fixture();
  put(f, '.gitignore', '!**\n');
  for (const p of ['.env', 'Zinema.lk Website/Database.sql', 'whatsapp-bot-deploy/whatsapp-session/creds.json',
    'whatsapp-bot-deploy/.wwebjs_auth/state.json', 'whatsapp-bot-deploy/node_modules/example/index.js',
    'Zinema.lk Website/includes/PHPMailer/src/Example.php', 'Zinema.lk Website/vendor/example.php',
    '.recovery-private/notes.md', 'Zinema.lk Website/uploads/example.mp4', 'whatsapp-bot-deploy/debug.log',
    '.git/objects/example', 'Zinema.lk Website/backup.zip']) put(f, p, 'Synthetic excluded fixture.');
  const originalOpen = fs.openSync;
  fs.openSync = function (p, ...args) {
    if (typeof p === 'string' && p.startsWith(f.source) && !['README.md', '.gitignore'].includes(path.relative(f.source, p))) {
      assert.fail('A sensitive fixture was opened');
    }
    return originalOpen.call(this, p, ...args);
  };
  let p;
  try { p = plan(f); } finally { fs.openSync = originalOpen; }
  assert.deepEqual(p.entries.map(e => e.path), ['.gitignore', 'README.md']);
  assert.ok(p.exclusions.environment > 0);
  assert.ok(p.exclusions['database-dump'] > 0);
  assert.ok(p.exclusions['excluded-directory'] > 0);
});

test('unknown files are excluded even with an allowed-looking extension', () => {
  const f = fixture();
  put(f, 'Zinema.lk Website/unreviewed.php', '<?php echo "fictional";');
  assert.deepEqual(plan(f).entries.map(e => e.path), ['README.md']);
  assert.ok(plan(f).exclusions['unapproved-path'] > 0);
});

test('working-tree bytes are used without any Git dependency', () => {
  const f = fixture();
  put(f, 'README.md', 'Current uncommitted synthetic content.\n');
  publishFixture(f);
  assert.equal(fs.readFileSync(path.join(f.output, 'README.md'), 'utf8'), 'Current uncommitted synthetic content.\n');
  assert.equal(fs.existsSync(path.join(f.output, '.git')), false);
});

test('source itself and descendant destinations are rejected', () => {
  const f = fixture();
  expectCode(() => plan(f, f.source), 'DESTINATION_OVERLAPS_SOURCE');
  expectCode(() => plan(f, path.join(f.source, 'export')), 'DESTINATION_OVERLAPS_SOURCE');
});

test('relative and traversal destination arguments are rejected', () => {
  const f = fixture();
  expectCode(() => plan(f, 'relative-export'), 'UNSAFE_ABSOLUTE_PATH');
  expectCode(() => plan(f, f.base + path.sep + 'child' + path.sep + '..' + path.sep + 'export'), 'UNSAFE_ABSOLUTE_PATH');
});

test('existing empty output is refused without touching its content', () => {
  const f = fixture();
  fs.mkdirSync(f.output);
  expectCode(() => plan(f), 'DESTINATION_EXISTS');
  assert.deepEqual(fs.readdirSync(f.output), []);
});

test('existing populated output is never overwritten', () => {
  const f = fixture();
  fs.mkdirSync(f.output);
  fs.writeFileSync(path.join(f.output, 'keep.txt'), 'Keep synthetic content.');
  expectCode(() => plan(f), 'DESTINATION_EXISTS');
  assert.equal(fs.readFileSync(path.join(f.output, 'keep.txt'), 'utf8'), 'Keep synthetic content.');
});

test('nonexistent output parent is rejected without creating directories', () => {
  const f = fixture();
  expectCode(() => plan(f, path.join(f.base, 'missing-parent', 'output')), 'DESTINATION_PARENT_REQUIRED');
  assert.equal(fs.existsSync(path.join(f.base, 'missing-parent')), false);
});

test('source-root junction or directory symlink is rejected', () => {
  const f = fixture(), linked = path.join(f.base, 'source-link');
  fs.symlinkSync(f.source, linked, process.platform === 'win32' ? 'junction' : 'dir');
  expectCode(() => tool.buildPlan({ source: linked, output: f.output }), 'LINK_PATH_REJECTED');
});

test('output-ancestor junction or directory symlink is rejected', () => {
  const f = fixture(), outside = path.join(f.base, 'outside'), link = path.join(f.base, 'linked-parent');
  fs.mkdirSync(outside);
  fs.symlinkSync(outside, link, process.platform === 'win32' ? 'junction' : 'dir');
  expectCode(() => plan(f, path.join(link, 'export')), 'LINK_PATH_REJECTED');
  assert.deepEqual(fs.readdirSync(outside), []);
});

test('source subtree symlink escape is excluded without copying its files', () => {
  const f = fixture(), outside = path.join(f.base, 'outside');
  fs.mkdirSync(outside);
  fs.writeFileSync(path.join(outside, 'functions.php'), '<?php echo "fictional external fixture";');
  fs.mkdirSync(path.join(f.source, 'Zinema.lk Website'));
  fs.symlinkSync(outside, path.join(f.source, 'Zinema.lk Website', 'includes'), process.platform === 'win32' ? 'junction' : 'dir');
  const p = plan(f);
  assert.deepEqual(p.entries.map(e => e.path), ['README.md']);
  assert.equal(p.exclusions.link, 1);
  tool.performExport(p);
  assert.equal(fs.existsSync(path.join(f.output, 'Zinema.lk Website')), false);
});

test('source hardlink is held without reading its linked content', () => {
  const f = fixture(), outside = path.join(f.base, 'outside.php');
  fs.writeFileSync(outside, '<?php echo "fictional linked fixture";');
  fs.mkdirSync(path.join(f.source, 'Zinema.lk Website', 'includes'), { recursive: true });
  fs.linkSync(outside, path.join(f.source, 'Zinema.lk Website', 'includes', 'functions.php'));
  const p = plan(f);
  assert.equal(p.exclusions.hardlink, 1);
  assert.deepEqual(p.entries.map(e => e.path), ['README.md']);
});

test('literal credential candidates are withheld and values do not enter CLI reports', () => {
  const f = fixture(), fakeValue = ['fixture', 'rejected', 'credential'].join('-');
  put(f, 'Zinema.lk Website/includes/functions.php', 'const API_KEY = "' + fakeValue + '";');
  const r = cli(f);
  assert.equal(r.status, 0);
  const summary = JSON.parse(r.stdout);
  assert.equal(summary.excludedCounts['credential-pattern'], 1);
  assert.equal((r.stdout + r.stderr).includes(fakeValue), false);
  assert.equal(fs.existsSync(f.output), false);
});

test('recognizable fake provider-token fixture is rejected without exposing it', () => {
  const f = fixture(), fakeValue = ['gh', 'p_', '000000000000000000000000'].join('');
  put(f, 'Zinema.lk Website/includes/functions.php', 'const fictional = "' + fakeValue + '";');
  assert.equal(plan(f).exclusions['credential-pattern'], 1);
});

test('unsafe example credentials, live flags and unknown variables are rejected', () => {
  const f = fixture(), fakeValue = ['fixture', 'not-a-real-password'].join('-');
  put(f, '.env.example', 'APP_ENV=development\nDB_PASS=' + fakeValue + '\n');
  assert.equal(plan(f).exclusions['unsafe-example'], 1);
  put(f, '.env.example', 'APP_ENV=production\nALLOW_LIVE_WHATSAPP=true\n');
  assert.equal(plan(f).exclusions['unsafe-example'], 1);
  put(f, '.env.example', 'APP_ENV=development\nUNREVIEWED_NAME=fictional\n');
  assert.equal(plan(f).exclusions['unsafe-example'], 1);
});

test('SQL data and procedural execution are withheld while DDL clauses remain allowed', () => {
  const f = fixture();
  put(f, 'Zinema.lk Website/migrations/movie_parts.sql', 'CREATE TABLE movie_parts (id INT); INSERT INTO movie_parts VALUES (1);');
  assert.equal(plan(f).exclusions['sql-data-or-execution'], 1);
  put(f, 'Zinema.lk Website/migrations/movie_parts.sql',
    "CREATE TABLE movie_parts (id INT COMMENT 'Soft delete flag', updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP);\n");
  assert.ok(plan(f).entries.some(e => e.path.endsWith('movie_parts.sql')));
  put(f, 'Zinema.lk Website/migrations/movie_parts.sql',
    'CREATE TABLE movie_parts (id INT); CREATE PROCEDURE fictional() SELECT 1;');
  assert.equal(plan(f).exclusions['sql-data-or-execution'], 1);
});

test('source changes after planning fail before creating output', () => {
  const f = fixture(), p = plan(f);
  put(f, 'README.md', 'Changed synthetic source after planning.\n');
  expectCode(() => tool.performExport(p), 'SOURCE_CHANGED');
  assert.equal(fs.existsSync(f.output), false);
});

test('SQL comments and quoted punctuation cannot hide a data statement', () => {
  const f = fixture();
  put(f, 'Zinema.lk Website/migrations/movie_parts.sql',
    "CREATE TABLE movie_parts (id INT COMMENT '-- synthetic comment'); INSERT INTO movie_parts VALUES (1);\n");
  assert.equal(plan(f).exclusions['sql-data-or-execution'], 1);
  put(f, 'Zinema.lk Website/migrations/movie_parts.sql',
    'CREATE TABLE movie_parts (id INT); /*! fictional executable directive */');
  assert.equal(plan(f).exclusions['sql-data-or-execution'], 1);
});

test('unknown SQL statements and unterminated strings are held for review', () => {
  const f = fixture();
  put(f, 'Zinema.lk Website/migrations/movie_parts.sql', 'CREATE TABLE movie_parts (id INT); SELECT 1;');
  assert.equal(plan(f).exclusions['sql-data-or-execution'], 1);
  put(f, 'Zinema.lk Website/migrations/movie_parts.sql', "CREATE TABLE movie_parts (id INT COMMENT 'unterminated);");
  assert.equal(plan(f).exclusions['sql-data-or-execution'], 1);
});

test('oversized, binary and malformed JSON candidates are withheld', () => {
  const f = fixture();
  put(f, 'Zinema.lk Website/includes/functions.php', Buffer.alloc(2 * 1024 * 1024 + 1, 32));
  assert.equal(plan(f).exclusions['size-limit'], 1);
  put(f, 'Zinema.lk Website/includes/functions.php', Buffer.from([0, 1, 2]));
  assert.equal(plan(f).exclusions.encoding, 1);
  put(f, 'whatsapp-bot-deploy/package.json', '{invalid fictional JSON');
  assert.equal(plan(f).exclusions['invalid-json'], 1);
});

test('output appearing after planning is not overwritten', () => {
  const f = fixture(), p = plan(f);
  fs.mkdirSync(f.output);
  expectCode(() => tool.performExport(p), 'DESTINATION_EXISTS');
});

test('tampered output bytes fail validation', () => {
  const f = fixture(); publishFixture(f);
  fs.writeFileSync(path.join(f.output, 'README.md'), 'Changed synthetic output.');
  expectCode(() => validate(f), 'EXPORT_HASH_MISMATCH');
});

test('missing output file fails manifest file-count validation', () => {
  const f = fixture(); publishFixture(f);
  fs.unlinkSync(path.join(f.output, 'README.md'));
  expectCode(() => validate(f), 'EXPORT_FILE_COUNT_MISMATCH');
});

test('injected environment output is rejected without opening it', () => {
  const f = fixture(); publishFixture(f);
  fs.writeFileSync(path.join(f.output, '.env'), 'Synthetic excluded fixture.');
  expectCode(() => validate(f), 'UNAPPROVED_EXPORT_FILE');
});

test('injected private directory is rejected', () => {
  const f = fixture(); publishFixture(f);
  fs.mkdirSync(path.join(f.output, '.git'));
  expectCode(() => validate(f), 'UNAPPROVED_EXPORT_DIRECTORY');
});

test('output junction or symlink injection is rejected', () => {
  const f = fixture(); publishFixture(f);
  const outside = path.join(f.base, 'outside'); fs.mkdirSync(outside);
  fs.symlinkSync(outside, path.join(f.output, 'linked'), process.platform === 'win32' ? 'junction' : 'dir');
  expectCode(() => validate(f), 'UNSAFE_EXPORT_PATH');
});

test('hardlinked output bytes and manifest substitution are rejected', () => {
  const f = fixture(); publishFixture(f);
  const exported = path.join(f.output, 'README.md');
  fs.linkSync(exported, path.join(f.base, 'outside.txt'));
  expectCode(() => validate(f), 'UNSAFE_EXPORT_PATH');
  const other = fixture(); publishFixture(other);
  fs.linkSync(path.join(other.output, tool.MANIFEST), path.join(other.base, 'outside-manifest.json'));
  expectCode(() => validate(other), 'INVALID_EXPORT_MANIFEST');
});

test('manifest traversal, duplicate entries and count mismatches are rejected', () => {
  for (const mutate of [m => { m.files[0].path = '../escape.php'; }, m => { m.files.push(m.files[0]); m.fileCount++; },
    m => { m.fileCount++; }]) {
    const f = fixture(); publishFixture(f); changeManifest(f, mutate);
    assert.throws(() => validate(f), error => error instanceof tool.ExportError);
  }
});

test('manifest metadata must have an approved schema and typed counts', () => {
  const f = fixture(); publishFixture(f);
  changeManifest(f, m => { m.excludedCounts = 42; });
  expectCode(() => validate(f), 'INVALID_EXPORT_MANIFEST');
});

test('CLI requires an explicit output and rejects ambiguous actions', () => {
  const f = fixture();
  const missing = cp.spawnSync(process.execPath, [CLI, '--source', f.source], { encoding: 'utf8' });
  assert.equal(missing.status, 1);
  assert.equal(JSON.parse(missing.stderr).error, 'OUTPUT_REQUIRED');
  const conflicting = cli(f, ['--dry-run', '--export']);
  assert.equal(conflicting.status, 1);
  assert.equal(JSON.parse(conflicting.stderr).error, 'INVALID_ARGUMENTS');
});

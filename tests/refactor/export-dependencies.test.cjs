'use strict';
const { test, after } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const tool = require('../../scripts/prepare-public-export.cjs');
const PAIRS = [
  ['Zinema.lk Website/includes/ads.php', 'apps/web/src/Views/Ads.php'],
  ['whatsapp-bot-deploy/config/environment.js', 'whatsapp-bot-deploy/src/config/environment.js']
];
const parent = fs.realpathSync.native(os.tmpdir()), owned = new Set();
function fixture() {
  const base = fs.mkdtempSync(path.join(parent, 'zinema-zl12-export-'));
  owned.add(base);
  const source = path.join(base, 'source'), output = path.join(base, 'candidate');
  fs.mkdirSync(source);
  const f = { base, source, output };
  put(f, 'README.md', 'Fictional export regression fixture.\n');
  return f;
}
function put(f, rel, bytes) {
  const full = path.join(f.source, ...rel.split('/'));
  fs.mkdirSync(path.dirname(full), { recursive: true });
  fs.writeFileSync(full, bytes);
  return full;
}
function plan(f) { return tool.buildPlan({ source: f.source, output: f.output }); }
function code(fn, expected) {
  assert.throws(fn, e => e instanceof tool.ExportError && e.code === expected);
}
after(() => {
  for (const base of owned) {
    assert.equal(path.dirname(base), parent);
    assert.ok(path.basename(base).startsWith('zinema-zl12-export-'));
    assert.equal(fs.lstatSync(base).isSymbolicLink(), false);
    assert.equal(fs.realpathSync.native(base), base);
    assert.notEqual(base, process.cwd());
    fs.rmSync(base, { recursive: true, force: false });
  }
});
for (const [shim, module] of PAIRS) {
  const label = shim.endsWith('.php') ? 'PHP helper' : 'Node configuration';
  const bytes = shim.endsWith('.php')
    ? '<?php function fictional_export_fixture() { return null; }\n'
    : "'use strict'; module.exports = {};\n";
  test(label + ': reviewed shim and implementation export together with matching bytes', () => {
    const f = fixture();
    put(f, shim, bytes); put(f, module, bytes);
    const p = plan(f);
    assert.deepEqual(p.entries.map(e => e.path).sort(), ['README.md', shim, module].sort());
    assert.equal(fs.existsSync(f.output), false);
    const validation = tool.performExport(p);
    assert.equal(validation.valid, true);
    assert.deepEqual(fs.readFileSync(path.join(f.output, module)), Buffer.from(bytes));
    assert.equal(tool.validateExport(f).valid, true);
  });
  test(label + ': missing implementation fails closed before any output', () => {
    const f = fixture(); put(f, shim, bytes);
    code(() => plan(f), 'MISSING_MODULE_DEPENDENCY');
    assert.equal(fs.existsSync(f.output), false);
  });
  test(label + ': content-rejected implementation cannot leave an exportable shim', () => {
    const f = fixture(); put(f, shim, bytes); put(f, module, Buffer.from([0, 1]));
    code(() => plan(f), 'MISSING_MODULE_DEPENDENCY');
    assert.equal(fs.existsSync(f.output), false);
  });
  test(label + ': mutated plan cannot drop a required implementation', () => {
    const f = fixture(); put(f, shim, bytes); put(f, module, bytes);
    const p = plan(f); p.entries = p.entries.filter(e => e.path !== module);
    code(() => tool.performExport(p), 'MISSING_MODULE_DEPENDENCY');
    assert.equal(fs.existsSync(f.output), false);
  });
  test(label + ': edited manifest cannot hide a removed implementation', () => {
    const f = fixture(); put(f, shim, bytes); put(f, module, bytes);
    tool.performExport(plan(f));
    const manifestPath = path.join(f.output, tool.MANIFEST);
    const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
    manifest.files = manifest.files.filter(e => e.path !== module);
    manifest.fileCount = manifest.files.length;
    fs.writeFileSync(manifestPath, JSON.stringify(manifest));
    const target = path.resolve(f.output, module);
    assert.ok(target.startsWith(f.output + path.sep));
    fs.unlinkSync(target);
    code(() => tool.validateExport(f), 'MISSING_MODULE_DEPENDENCY');
  });
}
test('unapproved app code stays excluded without opening its contents', () => {
  const f = fixture(), unknown = put(f, 'apps/web/src/Config/unapproved.php', '<?php /* fictional */\n');
  const original = fs.openSync;
  fs.openSync = function (file, ...args) {
    if (file === unknown) assert.fail('Unapproved source must not be opened');
    return original.call(this, file, ...args);
  };
  try {
    assert.deepEqual(plan(f).entries.map(e => e.path), ['README.md']);
  } finally {
    fs.openSync = original;
  }
});

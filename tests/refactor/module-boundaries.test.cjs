'use strict';
const { test, after } = require('node:test');
const assert = require('node:assert/strict');
const { createRequire } = require('node:module');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const cp = require('node:child_process');
const root = path.resolve(__dirname, '../..');
const legacy = path.join(root, 'whatsapp-bot-deploy/config/environment.js');
const canonical = path.join(root, 'whatsapp-bot-deploy/src/config/environment.js');
const parent = fs.realpathSync.native(os.tmpdir()), owned = new Set();
const minimalEnvironment = () => process.platform === 'win32'
  ? { SystemRoot: process.env.SystemRoot || 'C:\\Windows' } : {};
function fixture() {
  const base = fs.mkdtempSync(path.join(parent, 'zinema-zl12-rollback-'));
  owned.add(base);
  return base;
}
after(() => {
  for (const base of owned) {
    assert.equal(path.dirname(base), parent);
    assert.ok(path.basename(base).startsWith('zinema-zl12-rollback-'));
    assert.equal(fs.lstatSync(base).isSymbolicLink(), false);
    assert.equal(fs.realpathSync.native(base), base);
    assert.notEqual(base, root);
    fs.rmSync(base, { recursive: true, force: false });
  }
});
function php(module) {
  const r = cp.spawnSync(process.env.PHP_BINARY || 'php',
    ['-n', '-d', 'allow_url_fopen=0', path.join(root, 'tests/fixtures/ads-contract.php'), module],
    { env: minimalEnvironment(), encoding: 'utf8', timeout: 5000, cwd: __dirname });
  assert.equal(r.status, 0, 'Pure PHP fixture must execute');
  assert.equal(r.stderr, '');
  const result = JSON.parse(r.stdout);
  delete result.includedFileCount; // A shim changes file count, not public helper behavior.
  return result;
}
function nodeConfig(module) {
  const env = {
    ...minimalEnvironment(), APP_ENV: 'test', API_URL: 'http://127.0.0.1:9/api/whatsapp/',
    API_KEY: 'FAKE_ZL12_KEY', SESSION_PATH: path.join(root, '.runtime/zl12-fixture-sessions'),
    LOCAL_MEDIA_PATH: path.join(root, '.runtime/zl12-fixture-media'), BOT_INSTANCE_ID: '7'
  };
  const r = cp.spawnSync(process.execPath, ['-e',
    'const m=require(process.argv[1]),c=m.readConfig();process.stdout.write(JSON.stringify({appEnv:c.appEnv,apiUrl:c.apiUrl,botInstanceId:c.botInstanceId,live:c.liveWhatsApp,methods:Object.keys(m).sort()}));',
    module], { env, encoding: 'utf8', timeout: 5000, cwd: __dirname });
  assert.equal(r.status, 0, 'Pure Node fixture must execute');
  assert.equal(r.stderr, '');
  return JSON.parse(r.stdout);
}
test('legacy and canonical CommonJS paths share the same exported object', () => {
  assert.strictEqual(require(legacy), require(canonical));
  assert.deepEqual(Object.keys(require(legacy)).sort(), ['assertLiveWhatsAppEnabled', 'readConfig']);
});
for (const [consumer, request] of [
  ['whatsapp-bot-deploy/index.js', './config/environment'],
  ['whatsapp-bot-deploy/config/api-client.js', './environment'],
  ['whatsapp-bot-deploy/config/multi-tier-forwarder.js', './environment']
]) {
  test('existing config import resolves from ' + consumer, () => {
    // createRequire selects resolution context without executing the consumer.
    const fromConsumer = createRequire(path.join(root, consumer));
    assert.equal(fromConsumer.resolve(request), legacy);
    assert.strictEqual(fromConsumer(request), require(canonical));
  });
}
test('direct PHP module and original include path have identical observable contracts', () => {
  assert.deepEqual(php(path.join(root, 'Zinema.lk Website/includes/ads.php')),
    php(path.join(root, 'apps/web/src/Views/Ads.php')));
});
test('PHP rollback and reapply preserve the contract in an isolated fixture', () => {
  const base = fixture(), module = path.join(base, 'Module.php'), shim = path.join(base, 'shim.php');
  fs.copyFileSync(path.join(root, 'apps/web/src/Views/Ads.php'), module);
  const wrapper = "<?php\nrequire_once __DIR__ . '/Module.php';\n";
  fs.writeFileSync(shim, wrapper);
  const migrated = php(shim);
  fs.copyFileSync(module, shim); // Rehearse restoring retained implementation, not workspace state.
  assert.deepEqual(php(shim), migrated);
  fs.writeFileSync(shim, wrapper);
  assert.deepEqual(php(shim), migrated);
});
test('Node rollback and reapply preserve the contract in fresh isolated processes', () => {
  const base = fixture(), module = path.join(base, 'module.js'), shim = path.join(base, 'shim.js');
  fs.copyFileSync(canonical, module);
  const wrapper = "'use strict'; module.exports = require('./module');\n";
  fs.writeFileSync(shim, wrapper);
  const migrated = nodeConfig(shim);
  fs.copyFileSync(module, shim);
  assert.deepEqual(nodeConfig(shim), migrated);
  fs.writeFileSync(shim, wrapper);
  assert.deepEqual(nodeConfig(shim), migrated);
});

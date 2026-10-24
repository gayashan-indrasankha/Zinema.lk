'use strict';
// Characterize legacy public helper/import contracts using explicit fictional input.
const { test } = require('node:test');
const assert = require('node:assert/strict');
const path = require('node:path');
const cp = require('node:child_process');
const fs = require('node:fs');
const root = path.resolve(__dirname, '../..');
const ads = 'Zinema.lk Website/includes/ads.php';
const configPath = path.join(root, 'whatsapp-bot-deploy/config/environment.js');
const { readConfig, assertLiveWhatsAppEnabled } = require(configPath);
const functions = ['renderMobileBannerAd', 'renderDesktopBannerAd', 'renderRectangleAd',
  'renderResponsiveBannerAd', 'getSmartLinkUrl', 'renderSocialBar', 'renderPopunder', 'renderNativeBanner'];
const minimalEnvironment = () => process.platform === 'win32'
  ? { SystemRoot: process.env.SystemRoot || 'C:\\Windows' } : {};
const fixture = () => ({
  APP_ENV: 'test', API_URL: 'http://127.0.0.1:9/api/whatsapp/',
  API_KEY: 'FAKE_ZL12_KEY', SESSION_PATH: path.join(root, '.runtime/zl12-fixture-sessions'),
  LOCAL_MEDIA_PATH: path.join(root, '.runtime/zl12-fixture-media'), BOT_INSTANCE_ID: '7'
});
function phpContract(module, cwd = root) {
  const result = cp.spawnSync(process.env.PHP_BINARY || 'php',
    ['-n', '-d', 'allow_url_fopen=0', path.join(root, 'tests/fixtures/ads-contract.php'), module],
    { cwd, env: minimalEnvironment(), encoding: 'utf8', timeout: 5000 });
  assert.equal(result.error?.code, undefined, 'PHP must be available; set PHP_BINARY to its absolute path');
  assert.equal(result.status, 0, 'Helper characterization must complete without application bootstrap');
  assert.equal(result.stderr, '', 'No PHP warning/error is permitted');
  return JSON.parse(result.stdout);
}
for (const [label, cwd, module] of [
  ['absolute include from repository root', root, path.join(root, ads)],
  ['original relative include from website root', path.join(root, 'Zinema.lk Website'), 'includes/ads.php'],
  ['absolute include from unrelated working directory', __dirname, path.join(root, ads)]
]) {
  test('disabled ads contract: ' + label, () => {
    const actual = phpContract(module, cwd);
    assert.equal(actual.includeOutput, '');
    assert.equal(actual.repeatOutput, '');
    assert.equal(actual.adsEnabled, false);
    assert.equal(actual.smartLink, '');
    assert.equal(actual.sessionStarted, false);
    assert.deepEqual(actual.headers, []);
    assert.deepEqual(Object.keys(actual.functions), functions);
    for (const name of functions) {
      assert.deepEqual(actual.functions[name], {
        parameters: 0, requiredParameters: 0,
        value: name === 'getSmartLinkUrl' ? '' : null, output: ''
      });
    }
  });
}
test('bot config: frozen normalized DTO and input is not mutated', () => {
  const env = fixture(), before = { ...env }, actual = readConfig(env);
  assert.deepEqual(actual, {
    appEnv: 'test', apiUrl: 'http://127.0.0.1:9/api/whatsapp',
    apiKey: 'FAKE_ZL12_KEY', sessionPath: env.SESSION_PATH,
    mediaPath: env.LOCAL_MEDIA_PATH, botInstanceId: '7', liveWhatsApp: false
  });
  assert.ok(Object.isFrozen(actual));
  assert.deepEqual(env, before);
  assert.throws(() => assertLiveWhatsAppEnabled(actual), /live WhatsApp startup disabled/);
});
for (const key of ['APP_ENV', 'API_URL', 'API_KEY', 'SESSION_PATH', 'LOCAL_MEDIA_PATH', 'BOT_INSTANCE_ID']) {
  test('bot config rejects missing required key: ' + key, () => {
    const env = fixture(); delete env[key];
    assert.throws(() => readConfig(env), new RegExp(key));
  });
  test('bot config rejects placeholder required key: ' + key, () => {
    assert.throws(() => readConfig({ ...fixture(), [key]: 'REPLACE_SYNTHETIC' }), new RegExp(key));
  });
}
for (const [label, patch, key] of [
  ['unknown environment', { APP_ENV: 'staging' }, 'APP_ENV'],
  ['malformed URL', { API_URL: 'invalid' }, 'API_URL'],
  ['non-loopback test API', { API_URL: 'http://example.invalid/api' }, 'API_URL'],
  ['localhost alias is not the allowed loopback', { API_URL: 'http://localhost:9/api' }, 'API_URL'],
  ['HTTPS is not local mode', { API_URL: 'https://127.0.0.1/api' }, 'API_URL'],
  ['URL credentials', { API_URL: 'http://fiction:fiction@127.0.0.1:9/api' }, 'API_URL'],
  ['URL query', { API_URL: 'http://127.0.0.1:9/api?fixture=1' }, 'API_URL'],
  ['URL fragment', { API_URL: 'http://127.0.0.1:9/api#fixture' }, 'API_URL'],
  ['relative session path', { SESSION_PATH: 'relative/sessions' }, 'SESSION_PATH'],
  ['relative media path', { LOCAL_MEDIA_PATH: 'relative/media' }, 'LOCAL_MEDIA_PATH'],
  ['legacy auth directory', { SESSION_PATH: path.join(root, 'whatsapp-session') }, 'SESSION_PATH'],
  ['alternate auth directory', { LOCAL_MEDIA_PATH: path.join(root, '.wwebjs_auth') }, 'LOCAL_MEDIA_PATH'],
  ['non-numeric instance', { BOT_INSTANCE_ID: 'fixture' }, 'BOT_INSTANCE_ID'],
  ['production without API approval', { APP_ENV: 'production', API_URL: 'https://example.invalid/api' }, 'API_URL'],
  ['production HTTP despite approval', { APP_ENV: 'production', API_URL: 'http://example.invalid/api', ALLOW_PRODUCTION_API: 'true' }, 'API_URL']
]) {
  test('bot config rejects ' + label, () => {
    assert.throws(() => readConfig({ ...fixture(), ...patch }), new RegExp(key));
  });
}
test('live flag alone cannot enable WhatsApp outside production', () => {
  for (const APP_ENV of ['test', 'development']) {
    const actual = readConfig({ ...fixture(), APP_ENV, ALLOW_LIVE_WHATSAPP: 'true' });
    assert.equal(actual.liveWhatsApp, false);
    assert.throws(() => assertLiveWhatsAppEnabled(actual), /startup disabled/);
  }
});
test('production approval flags cannot enable an unapproved provider', () => {
  const env = { ...fixture(), APP_ENV: 'production', API_URL: 'https://example.invalid/api/',
    ALLOW_PRODUCTION_API: 'true' };
  assert.equal(readConfig(env).liveWhatsApp, false);
  const approved = readConfig({ ...env, ALLOW_LIVE_WHATSAPP: 'true' });
  assert.equal(approved.liveWhatsApp, false);
  assert.throws(() => assertLiveWhatsAppEnabled(approved), /startup disabled/);
  // Only pure validation is called; no bot entry point, HTTP client or live socket is imported.
});
test('default process config uses only the explicitly supplied synthetic child environment', () => {
  const result = cp.spawnSync(process.execPath, ['-e',
    'const c=require(process.argv[1]).readConfig();if(c.appEnv!=="test"||c.liveWhatsApp)process.exit(1);',
    configPath], { env: { ...minimalEnvironment(), ...fixture() }, encoding: 'utf8', timeout: 5000 });
  assert.equal(result.status, 0);
  assert.equal(result.stdout, '');
  assert.equal(result.stderr, '');
});
test('configuration parsing creates no session or media directory', () => {
  const env = fixture();
  assert.equal(fs.existsSync(env.SESSION_PATH), false, 'Fixture directory must be absent');
  assert.equal(fs.existsSync(env.LOCAL_MEDIA_PATH), false, 'Fixture directory must be absent');
  readConfig(env);
  assert.equal(fs.existsSync(env.SESSION_PATH), false);
  assert.equal(fs.existsSync(env.LOCAL_MEDIA_PATH), false);
});

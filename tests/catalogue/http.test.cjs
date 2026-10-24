'use strict';
const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const cp = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const net = require('node:net');
let child, directory, base;
before(async () => {
  const server = net.createServer();
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  const port = server.address().port; await new Promise(resolve => server.close(resolve));
  directory = fs.mkdtempSync(path.join(fs.realpathSync.native(os.tmpdir()), 'zinema-zl16-http-'));
  const env = process.platform === 'win32' ? { SystemRoot: process.env.SystemRoot || 'C:\\Windows' } : {};
  const fixture = cp.spawnSync(process.env.PHP_BINARY || 'php', ['-n', '-r', "require 'tests/catalogue/fixtures.php'; echo json_encode(apiWorld()['records'],JSON_THROW_ON_ERROR);"], { cwd:path.join(__dirname,'../..'),env,encoding:'utf8' });
  assert.equal(fixture.status,0); const recordsPath=path.join(directory,'synthetic.json');
  fs.writeFileSync(recordsPath,fixture.stdout,{mode:0o600}); env.ZL16_HTTP_RECORDS=recordsPath;
  child = cp.spawn(process.env.PHP_BINARY || 'php', ['-n', '-d', 'display_errors=0', '-S', '127.0.0.1:' + port,
    '-t', directory, path.join(__dirname, 'http-router.php')], { env, stdio: 'ignore' });
  child.on('error', () => {}); base = 'http://127.0.0.1:' + port;
  for (let i = 0; i < 50; i++) {
    try { const r = await fetch(base + '/health', { signal: AbortSignal.timeout(500) }); if (r.ok) return; } catch {}
    await new Promise(resolve => setTimeout(resolve, 50));
  }
  throw new Error('LOCAL_PHP_HTTP_FIXTURE_UNAVAILABLE');
});
after(async () => {
  if (child && child.exitCode === null) {
    const closed = new Promise(resolve => child.once('close', resolve)); child.kill();
    await Promise.race([closed, new Promise(resolve => setTimeout(resolve, 2000))]);
    if (child.exitCode === null) child.kill('SIGKILL');
  }
  if (directory) {
    assert.equal(path.dirname(directory), fs.realpathSync.native(os.tmpdir()));
    assert.ok(path.basename(directory).startsWith('zinema-zl16-http-'));
    assert.equal(fs.lstatSync(directory).isSymbolicLink(), false); fs.unlinkSync(path.join(directory,'synthetic.json')); fs.rmdirSync(directory);
  }
});
test('ZL-16 HTTP: actual public entry stays unconfigured and never connects to a catalogue', async () => {
  const r = await fetch(base + '/real?resource=movies', { signal: AbortSignal.timeout(5000) });
  assert.equal(r.status, 503); const body = await r.json(); assert.equal(body.error.code, 'CATALOGUE_NOT_CONFIGURED');
  assert.equal(r.headers.get('cache-control'), 'private, no-store');
  const post = await fetch(base + '/real', { method: 'POST', body: 'is_premium=true' });
  assert.equal(post.status, 405); assert.equal(post.headers.get('allow'), 'GET');
});
test('ZL-16 HTTP: real controller with test-only adapters returns filtered whitelisted JSON', async () => {
  const r = await fetch(base + '/fixture?resource=movies', { headers: { 'X-Country': 'US', Authorization: 'Bearer FICTIONAL_TEST_CLAIM' } });
  assert.equal(r.status, 200); const body = await r.json();
  assert.deepEqual(body.data.items.map(i => i.id), ['501', '502']);
  assert.equal(r.headers.get('x-content-type-options'), 'nosniff');
  assert.equal(body.data.items[0].availability.playbackAvailable, false);
  const forged = await fetch(base + '/fixture?resource=movies&is_premium=true'); assert.equal(forged.status, 400);
});
test('ZL-16 HTTP: private and missing IDs have the same safe failure body', async () => {
  const a = await fetch(base + '/fixture?resource=movies&id=503'), b = await fetch(base + '/fixture?resource=movies&id=999');
  assert.equal(a.status, 404); assert.equal(b.status, 404);
  assert.deepEqual((await a.json()).error, (await b.json()).error);
});

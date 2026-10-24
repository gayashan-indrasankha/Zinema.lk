'use strict';
const { test } = require('node:test');
const assert = require('node:assert/strict');
const cp = require('node:child_process');
const path = require('node:path');
const env = process.platform === 'win32' ? { SystemRoot: process.env.SystemRoot || 'C:\\Windows' } : {};
function run(name) { return cp.spawnSync(process.env.PHP_BINARY || 'php', ['-n', path.join(__dirname, 'contracts.php'), name], { env, encoding: 'utf8', timeout: 10000, maxBuffer: 1e6 }); }
const list = run('list'); assert.equal(list.status, 0);
for (const name of JSON.parse(list.stdout)) test('ZL-17: ' + name, () => {
  const result = run(name); assert.equal(result.status, 0, result.stderr); assert.deepEqual(JSON.parse(result.stdout), { passed: true });
});

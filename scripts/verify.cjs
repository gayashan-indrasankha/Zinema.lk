'use strict';
// Uses only reviewed file metadata and parser subprocesses. No application startup.
const fs = require('node:fs');
const path = require('node:path');
const cp = require('node:child_process');
const environment = require('./environment.cjs');
process.chdir(path.resolve(__dirname, '..'));
const files = environment.checkContext(), failures = [];
let php = 0, js = 0;
for (const file of files) {
  let result;
  if (file.endsWith('.php')) {
    php++; result = cp.spawnSync(process.env.PHP_BINARY || 'php', ['-n', '-l', file], { encoding: 'utf8', timeout: 5000 });
  } else if (/\.(js|cjs|mjs)$/.test(file)) {
    js++; result = cp.spawnSync(process.execPath, ['--check', file], { encoding: 'utf8', timeout: 5000 });
  }
  if (result && (result.error || result.status !== 0)) failures.push(file);
}
if (failures.length) {
  console.error(JSON.stringify({ syntaxFailures: failures, sourceDiagnosticsWithheld: true }));
  process.exit(1);
}
const tests = ['scripts/test-public-export.cjs',
  ...fs.readdirSync('tests/refactor').filter(p => p.endsWith('.test.cjs')).map(p => 'tests/refactor/' + p),
  ...fs.readdirSync('tests/environment').filter(p => p.endsWith('.test.cjs')).map(p => 'tests/environment/' + p),
  ...fs.readdirSync('tests/database').filter(p => p.endsWith('.test.cjs')).map(p => 'tests/database/' + p),
  ...fs.readdirSync('tests/content').filter(p => p.endsWith('.test.cjs')).map(p => 'tests/content/' + p),
  ...fs.readdirSync('tests/catalogue').filter(p => p.endsWith('.test.cjs')).map(p => 'tests/catalogue/' + p),
  ...fs.readdirSync('tests/playback').filter(p => p.endsWith('.test.cjs')).map(p => 'tests/playback/' + p),
  ...fs.readdirSync('tests/identity').filter(p => p.endsWith('.test.cjs')).map(p => 'tests/identity/' + p),
  ...fs.readdirSync('tests/quarantine').filter(p => p.endsWith('.test.cjs')).map(p => 'tests/quarantine/' + p)];
const result = cp.spawnSync(process.execPath, ['--test', ...tests], {
  encoding: 'utf8', timeout: 120000, maxBuffer: 4e6,
  env: { ...environment.cleanEnvironment(), PHP_BINARY: process.env.PHP_BINARY || 'php' }
});
if (result.error || result.status !== 0) {
  // These are synthetic test diagnostics, but still report names only by default.
  const failed = (result.stdout || '').split('\n').filter(l => /^not ok |^✖ /.test(l)).map(l => l.slice(0, 180));
  console.error(JSON.stringify({ testsPassed: false, failed, diagnosticsWithheld: true }));
  process.exit(1);
}
const match = key => Number((result.stdout.match(new RegExp('(?:#|ℹ) ' + key + ' (\\d+)')) || [])[1] || 0);
console.log(JSON.stringify({ phpParsed: php, jsParsed: js, syntaxFailures: 0,
  tests: match('tests'), passed: match('pass'), failed: match('fail'), skipped: match('skipped'),
  phpVersion: cp.execFileSync(process.env.PHP_BINARY || 'php', ['-r', 'echo PHP_VERSION;'], { encoding: 'utf8' }),
  nodeVersion: process.version, noApplicationStartup: true }));

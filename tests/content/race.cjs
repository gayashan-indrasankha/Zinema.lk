'use strict';
const cp = require('node:child_process');
const env = { ...process.env, ZL15_RACE_START: String(Date.now() / 1000 + 1) };
function worker() {
  return new Promise((resolve, reject) => {
    const child = cp.spawn(process.env.PHP_BINARY || 'php', ['tests/content/race.php'], { env, timeout: 10000 });
    let output = '';
    child.stdout.on('data', chunk => { output += chunk; });
    child.stderr.on('data', () => {}); // Fixture failures never dump SQL/settings.
    child.on('error', reject);
    child.on('close', code => {
      if (code !== 0) { reject(new Error('CONTENT_RACE_WORKER_FAILED')); return; }
      try { resolve(JSON.parse(output).outcome); } catch { reject(new Error('CONTENT_RACE_WORKER_FAILED')); }
    });
  });
}
Promise.all([worker(), worker()]).then(outcomes => {
  if (outcomes.sort().join(',') !== 'VERSION_CONFLICT,saved') throw new Error('CONTENT_RACE_UNVERIFIED');
  process.stdout.write(JSON.stringify({ actualConcurrentEditors: 2, committed: 1, staleRejected: 1, passed: true }));
}).catch(() => { process.stderr.write('{"error":"CONTENT_RACE_UNVERIFIED"}\n'); process.exitCode = 1; });

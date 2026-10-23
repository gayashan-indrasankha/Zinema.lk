// Syntax only: never require an application module or start its services.
const cp = require('node:child_process');
const path = require('node:path');
const files = cp.execFileSync('git', ['ls-files', '-z'], { encoding: 'utf8' })
  .split('\0').filter(Boolean);
let failed = 0;
const js = files.filter(file => /\.(?:js|cjs|mjs)$/.test(file));
for (const file of js) {
  const result = cp.spawnSync(process.execPath, ['--check', file], { encoding: 'utf8' });
  if (result.status !== 0) {
    failed++;
    // Parser diagnostics can quote source. Print paths only in public logs.
    console.error(`JavaScript syntax failed: ${file}`);
  }
}
const php = files.filter(file => path.extname(file) === '.php');
const phpBinary = process.env.PHP_BINARY || 'php';
const available = cp.spawnSync(phpBinary, ['--version'], { encoding: 'utf8' });
if (available.status === 0) {
  for (const file of php) {
    const result = cp.spawnSync(phpBinary, ['-n', '-l', file], { encoding: 'utf8' });
    if (result.status !== 0) {
      failed++;
      console.error(`PHP syntax failed: ${file}`);
    }
  }
  console.log(`PHP: ${php.length} files checked.`);
} else {
  console.log(`PHP: NOT CHECKED (${php.length} files); PHP unavailable.`);
  if (process.env.REQUIRE_PHP === 'true') failed++;
}
console.log(`JavaScript: ${js.length} files checked. Total syntax failures: ${failed}.`);
console.log('Syntax checks do not verify application behavior or database compatibility.');
process.exitCode = failed ? 1 : 0;

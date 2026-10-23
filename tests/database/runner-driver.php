<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/database/Runner.php';
use Zinema\Database\Runner;
try {
    $mode = $argv[1] ?? 'manifest';
    $directory = dirname(__DIR__, 2) . '/database';
    if (in_array($mode, ['reversed','modified','missing','unsafe'], true)) {
        $temporary = sys_get_temp_dir() . '/zinema-zl14-fixture-' . bin2hex(random_bytes(8));
        mkdir($temporary . '/migrations', 0700, true);
        $manifest = json_decode((string)file_get_contents($directory . '/migrations.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach ($manifest['migrations'] as $entry) copy($directory . '/migrations/' . basename($entry['file']), $temporary . '/migrations/' . basename($entry['file']));
        if ($mode === 'reversed') $manifest['migrations'] = array_reverse($manifest['migrations']);
        $file = $temporary . '/migrations/' . basename($manifest['migrations'][0]['file']);
        if ($mode === 'modified') file_put_contents($file, "-- synthetic modified fixture\n", FILE_APPEND);
        if ($mode === 'missing') unlink($file); // only this newly created fictional fixture file
        if ($mode === 'unsafe') {
            file_put_contents($file, "DROP TABLE site_settings;\n");
            $manifest['migrations'][0]['sha256'] = hash_file('sha256', $file);
        }
        file_put_contents($temporary . '/migrations.json', json_encode($manifest)); $directory = $temporary;
    }
    if ($mode === 'settings') { Runner::settings(); echo "{\"accepted\":true}\n"; }
    else { $entries = Runner::load($directory); echo json_encode(['versions' => array_column($entries,'version'),
        'tables' => array_column($entries,'table'), 'fullApplicationReady' => false]) . "\n"; }
} catch (Throwable $error) {
    echo json_encode(['error' => $error instanceof \Zinema\Database\MigrationFailure ? $error->reason : 'STATIC_TEST_FAILED']) . "\n";
}

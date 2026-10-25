<?php
/** Development storage accounting. Physical files, including unknown files, consume quota. */
class LocalStorageError extends RuntimeException {}

function local_storage_settings() {
    if (getenv('UPLOAD_QUOTA_MODE') !== 'local') return null;
    if (!defined('APP_ENV') || APP_ENV !== 'development' || DB_HOST !== '127.0.0.1' ||
        DB_NAME !== 'zinema_recovery_development' || getenv('ALLOW_LIVE_INTEGRATIONS') !== 'false')
        throw new LocalStorageError('storage_target_denied');
    $owner = getenv('UPLOAD_STORAGE_OWNER') ?: '';
    $bytes = filter_var(getenv('UPLOAD_MAX_BYTES'), FILTER_VALIDATE_INT);
    $files = filter_var(getenv('UPLOAD_MAX_FILES'), FILTER_VALIDATE_INT);
    $state = getenv('UPLOAD_STORAGE_STATE');
    $root = dirname(__DIR__) . '/uploads';
    if (!preg_match('/^zinema-dev-[a-f0-9]{8}(?:-operator-fixtures)?$/D', $owner) ||
        $state !== '/var/lib/zinema/storage' || !$bytes || $bytes < 1 || $bytes > 2147483648 ||
        !$files || $files < 1 || $files > 10000 || is_link($root) || realpath($root) !== $root ||
        is_link($state) || realpath($state) !== $state)
        throw new LocalStorageError('storage_configuration_denied');
    return ['root'=>$root,'state'=>$state,'owner'=>$owner,'max_bytes'=>$bytes,'max_files'=>$files];
}

function storage_write_json($path, $data) {
    $temp = $path . '.' . bin2hex(random_bytes(8)) . '.tmp';
    $json = json_encode($data, JSON_THROW_ON_ERROR);
    if (@file_put_contents($temp, $json, LOCK_EX) !== strlen($json) || !@chmod($temp, 0640) || !@rename($temp, $path)) {
        if (is_file($temp) && !is_link($temp)) @unlink($temp);
        throw new LocalStorageError('storage_state_write_failed');
    }
}

function initialize_local_upload_storage() {
    if (PHP_SAPI !== 'cli') throw new LocalStorageError('storage_bootstrap_denied');
    $settings = local_storage_settings();
    if (!$settings) throw new LocalStorageError('storage_disabled');
    // First initialization shares the admission lock: two simultaneous starts
    // must not replace a newly populated manifest with an empty one.
    $lockPath = $settings['state'] . '/storage.lock';
    if (is_link($lockPath)) throw new LocalStorageError('storage_lock_denied');
    $bootstrap = @fopen($lockPath, 'c+');
    if (!$bootstrap || !flock($bootstrap, LOCK_EX)) throw new LocalStorageError('storage_lock_failed');
    try {
        $marker = $settings['state'] . '/owner.json';
        $newOwner = !file_exists($marker);
        if ($newOwner) {
            if (file_exists($settings['state'].'/manifest.json')) throw new LocalStorageError('storage_owner_denied');
            storage_write_json($marker, ['schema'=>1,'owner'=>$settings['owner'],'database'=>DB_NAME]);
        }
        storage_check_owner($settings);
        if (!file_exists($settings['state'] . '/manifest.json')) {
            if (!$newOwner) throw new LocalStorageError('storage_manifest_invalid');
            storage_write_json($settings['state'] . '/manifest.json', ['schema'=>1,'owner'=>$settings['owner'],'assets'=>[]]);
        }
        // The web worker must own state files, not merely their directory.
        foreach (['owner.json','manifest.json','storage.lock'] as $name) {
            if (!@chown($settings['state'].'/'.$name, 'www-data') || !@chgrp($settings['state'].'/'.$name, 'www-data'))
                throw new LocalStorageError('storage_permissions_failed');
        }
        @chmod($lockPath,0660);
    } finally { flock($bootstrap,LOCK_UN);fclose($bootstrap); }
    storage_with_lock($settings, false, static fn($manifest)=>true);
}

function storage_check_owner($settings) {
    $path = $settings['state'] . '/owner.json';
    if (is_link($path) || !is_file($path) || filesize($path) > 2048) throw new LocalStorageError('storage_owner_denied');
    $owner = json_decode(file_get_contents($path), true);
    if (($owner['schema'] ?? null) !== 1 || ($owner['owner'] ?? null) !== $settings['owner'] ||
        ($owner['database'] ?? null) !== DB_NAME) throw new LocalStorageError('storage_owner_denied');
}

function storage_managed_path($path) {
    return is_string($path) && preg_match('~^(?:[a-f0-9]{32}\.(?:png|jpg|webp)|trailers/[a-f0-9]{32}\.(?:mp4|webm))$~D', $path);
}

function storage_with_lock($settings, $exclusive, $callback) {
    storage_check_owner($settings);
    $lockPath = $settings['state'] . '/storage.lock';
    if (is_link($lockPath)) throw new LocalStorageError('storage_lock_denied');
    $lock = @fopen($lockPath, 'c+');
    if (!$lock || !flock($lock, $exclusive ? LOCK_EX : LOCK_SH)) throw new LocalStorageError('storage_lock_failed');
    @chmod($lockPath, 0660); @chown($lockPath, 'www-data'); @chgrp($lockPath, 'www-data');
    try {
        $path = $settings['state'] . '/manifest.json';
        if (is_link($path) || !is_file($path) || filesize($path) > 2097152) throw new LocalStorageError('storage_manifest_invalid');
        $manifest = json_decode(file_get_contents($path), true);
        if (($manifest['schema'] ?? null) !== 1 || ($manifest['owner'] ?? null) !== $settings['owner'] ||
            !is_array($manifest['assets'] ?? null) || count($manifest['assets']) > 10000)
            throw new LocalStorageError('storage_manifest_invalid');
        foreach ($manifest['assets'] as $relative=>$asset) {
            if (!storage_managed_path($relative) || !is_array($asset) || !is_int($asset['bytes'] ?? null) ||
                $asset['bytes'] < 1 || $asset['bytes'] > 20971520 || !is_int($asset['created_at'] ?? null) ||
                $asset['created_at'] < 1 || !is_string($asset['sha256'] ?? null) ||
                !preg_match('/^[a-f0-9]{64}$/D', $asset['sha256'])) throw new LocalStorageError('storage_manifest_invalid');
        }
        return $callback($manifest);
    } finally { flock($lock, LOCK_UN); fclose($lock); }
}

function storage_scan_files($settings) {
    $entries = []; $unsafe = []; $bytes = 0; $seen = 0;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($settings['root'], FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($iterator as $file) {
        if (++$seen > 20000) throw new LocalStorageError('storage_inventory_limit');
        $relative = substr($file->getPathname(), strlen($settings['root'])+1);
        if ($file->isLink()) { $unsafe[]=['entry_ref'=>hash('sha256',$relative),'reason'=>'symbolic_link']; continue; }
        if ($file->isDir()) continue;
        $stat = @lstat($file->getPathname());
        if (!$stat || !$file->isFile()) { $unsafe[]=['entry_ref'=>hash('sha256',$relative),'reason'=>'special_entry']; continue; }
        $entries[$relative] = ['bytes'=>$stat['size'],'mtime'=>$stat['mtime'],'linked'=>$stat['nlink'] !== 1];
        $bytes += $stat['size'];
        if ($stat['nlink'] !== 1) $unsafe[]=['entry_ref'=>hash('sha256',$relative),'reason'=>'hard_link'];
    }
    return ['entries'=>$entries,'unsafe'=>$unsafe,'bytes'=>$bytes,'files'=>count($entries)];
}

function storage_commit_upload($source, $relative) {
    if (!storage_managed_path($relative) || is_link($source) || !is_file($source)) throw new LocalStorageError('storage_input_denied');
    $settings = local_storage_settings();
    $root = $settings ? $settings['root'] : dirname(__DIR__) . '/uploads';
    $write = static function ($manifest = null) use ($source, $relative, $settings, $root) {
        $size = filesize($source);
        if ($size < 1 || $size > 20971520) throw new LocalStorageError('storage_input_denied');
        if ($settings) {
            $scan = storage_scan_files($settings);
            if ($scan['unsafe']) throw new LocalStorageError('storage_unsafe_entries');
            if ($scan['files']+1 > $settings['max_files'] || $scan['bytes']+$size > $settings['max_bytes'])
                throw new LocalStorageError('quota_exceeded');
            if (isset($manifest['assets'][$relative])) throw new LocalStorageError('storage_collision');
            if (count($manifest['assets']) >= 10000) throw new LocalStorageError('storage_manifest_limit');
        }
        $target = $root . '/' . $relative;
        $dir = dirname($target);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) throw new LocalStorageError('storage_write_failed');
        if (is_link($dir) || realpath($dir) !== $dir) throw new LocalStorageError('storage_path_denied');
        $output = @fopen($target, 'x+b');
        if (!$output) throw new LocalStorageError('storage_write_failed');
        $created = fstat($output); $input = null;
        try {
            $input = @fopen($source, 'rb');
            if (!$input || stream_copy_to_stream($input, $output, $size+1) !== $size || !fflush($output) ||
                fstat($output)['size'] !== $size || !@chmod($target, 0644)) throw new LocalStorageError('storage_write_failed');
            if ($settings) {
                $manifest['assets'][$relative] = ['bytes'=>$size,'sha256'=>hash_file('sha256',$target),'created_at'=>time()];
                storage_write_json($settings['state'].'/manifest.json', $manifest);
                @chown($settings['state'].'/manifest.json','www-data'); @chgrp($settings['state'].'/manifest.json','www-data');
            }
        } catch (Throwable $error) {
            // Discard only this newly-created partial file, never a previous upload.
            $current = @lstat($target);
            if ($current && !is_link($target) && $current['ino'] === $created['ino']) @unlink($target);
            throw $error;
        } finally { if (is_resource($input)) fclose($input); fclose($output); }
        return 'uploads/' . $relative;
    };
    return $settings ? storage_with_lock($settings, true, $write) : $write();
}

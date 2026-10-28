<?php
/** Failure simulations only on the separate, explicitly fictional quarantine volume. */
ini_set('display_errors','0');
require_once __DIR__.'/operations.php';
require_once __DIR__.'/upload_cleanup.php';
try {
    if (PHP_SAPI!=='cli' || getenv('ZINEMA_QUARANTINE_FIXTURES')!=='1' ||
        !str_ends_with(getenv('UPLOAD_STORAGE_OWNER') ?: '', '-quarantine-fixtures')) throw new LocalOperationError('fixture_denied');
    $input=json_decode(stream_get_contents(STDIN,16385),true,48,JSON_THROW_ON_ERROR);
    $run=$input['run'] ?? '';
    if (!is_string($run) || !preg_match('/^[a-f0-9]{12}$/D',$run)) throw new LocalOperationError('fixture_denied');
    foreach (['max_bytes'=>'UPLOAD_MAX_BYTES','max_files'=>'UPLOAD_MAX_FILES','quarantine_bytes'=>'UPLOAD_QUARANTINE_MAX_BYTES','quarantine_files'=>'UPLOAD_QUARANTINE_MAX_FILES'] as $field=>$name)
        if (isset($input[$field])) { if (!is_int($input[$field]) || $input[$field]<1) throw new LocalOperationError('fixture_denied');putenv($name.'='.$input[$field]); }
    [$db,$settings]=operator_context();
    $action=$input['action'] ?? '';$response=['success'=>true];
    $signal=$input['signal'] ?? $run;
    if (!is_string($signal) || !preg_match('/^[a-f0-9]{12}$/D',$signal)) throw new LocalOperationError('fixture_denied');
    $signalPath=$settings['state'].'/fixture-'.$signal.'-ready';
    if ($action==='signal') $response['ready']=is_file($signalPath);
    elseif ($action==='execute') {
        $hook=static function($phase,$id) use ($input,$signalPath) {
            if (($input['pause_phase'] ?? null)===$phase) {
                file_put_contents($signalPath,'ready');usleep(1200000);
            }
            if (($input['fail_phase'] ?? null)===$phase) throw new RuntimeException('fictional process interruption');
        };
        $response=operator_dispatch($db,$settings,$input['request'] ?? null,$hook);
    } else {
        $relative=cleanup_relative($input['path'] ?? null);
        $manifest=storage_with_lock($settings,false,static fn($m)=>$m);
        $asset=$manifest['assets'][$relative] ?? null;
        if (!$asset) throw new LocalOperationError('fixture_denied');
        $path=$settings['root'].'/'.$relative;
        if ($action==='probe') {
            $response['public']=cleanup_probe($path,$asset);
            unset($response['public']['identity']);
            $blobId=$asset['blob_id'] ?? ($manifest['cleanup_ops'][$asset['operation_id'] ?? '']['blob_id'] ?? null);
            $response['archive']=$blobId ? cleanup_probe(cleanup_directory($settings).'/'.$blobId.'.blob',$asset) : ['present'=>false,'intact'=>false];
            unset($response['archive']['identity']);
        } elseif ($action==='hold_reference' || $action==='reference') {
            $gate=storage_reference_acquire($db);
            try {
                if (empty($input['bypass'])) storage_validate_reference_values($settings,[$input['path']]);
                if ($action==='hold_reference') { file_put_contents($signalPath,'ready');usleep(1200000); }
                $title='Local Test Quarantine '.$run;
                $stmt=$db->prepare("INSERT INTO collections(title,description,genre,cover_image) VALUES(?,'Fictional cleanup reference','Demo',?)");
                $stmt->bind_param('ss',$title,$input['path']);$stmt->execute();$response['reference_id']=$db->insert_id;
            } finally { storage_reference_release($db,$gate); }
        } elseif ($action==='clear_reference') {
            $gate=storage_reference_acquire($db);
            try {
                $title='Local Test Quarantine '.$run;
                $stmt=$db->prepare("UPDATE collections SET cover_image='' WHERE title=? AND cover_image=?");
                $stmt->bind_param('ss',$title,$input['path']);$stmt->execute();
            } finally { storage_reference_release($db,$gate); }
        } elseif ($action==='collision') {
            if (storage_location($asset)!=='quarantined') throw new LocalOperationError('fixture_denied');
            $file=fopen($path,'x');fwrite($file,'fictional restore collision');fclose($file);
        } elseif ($action==='unregistered_generated') {
            if (!cleanup_probe($path,$asset)['intact']) throw new LocalOperationError('fixture_denied');
            $relative=bin2hex(random_bytes(16)).'.png';
            copy($path,$settings['root'].'/'.$relative);touch($settings['root'].'/'.$relative,time()-172800);
            $response['path']='uploads/'.$relative;
        } elseif ($action==='remove_collision') {
            if (storage_location($asset)!=='quarantined' || is_link($path) || file_get_contents($path)!=='fictional restore collision') throw new LocalOperationError('fixture_denied');
            unlink($path);
        } elseif ($action==='partial_copy') {
            $op=$manifest['cleanup_ops'][$asset['operation_id'] ?? ''] ?? null;
            if (!$op || $op['action']!=='quarantine' || $op['status']!=='pending' || !cleanup_probe($path,$asset)['intact']) throw new LocalOperationError('fixture_denied');
            $part=cleanup_directory($settings).'/'.$op['blob_id'].'.part';
            $file=fopen($part,'x');fwrite($file,file_get_contents($path,false,null,0,8));fclose($file);
        } elseif ($action==='tamper_archive' || $action==='repair_archive') {
            $blobId=$asset['blob_id'] ?? null;
            if (!$blobId) throw new LocalOperationError('fixture_denied');
            $blob=cleanup_directory($settings).'/'.$blobId.'.blob';$backup=$settings['state'].'/fixture-'.$run.'-'.$blobId.'-backup';
            if ($action==='tamper_archive') {
                if (file_exists($backup) || !cleanup_probe($blob,$asset)['intact']) throw new LocalOperationError('fixture_denied');
                copy($blob,$backup);file_put_contents($blob,'fictional archive change');
            } else {
                if (!cleanup_probe($backup,$asset)['intact'] || is_link($blob)) throw new LocalOperationError('fixture_denied');
                copy($backup,$blob);unlink($backup);
            }
        } else throw new LocalOperationError('invalid_fixture_action');
    }
} catch (Throwable $error) {
    $known=$error instanceof LocalOperationError || $error instanceof LocalStorageError;
    $response=['success'=>false,'error'=>$known ? $error->getMessage() : 'fixture_unavailable'];
}
echo json_encode($response,JSON_UNESCAPED_SLASHES).PHP_EOL;
exit(($response['success'] ?? false) ? 0 : 1);

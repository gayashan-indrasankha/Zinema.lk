<?php
/** Reviewed, recoverable development cleanup. Quarantine archives are never purged. */
function cleanup_relative($path) {
    if (!is_string($path) || !str_starts_with($path,'uploads/') || !storage_managed_path(substr($path,8)))
        throw new LocalOperationError('cleanup_path_denied');
    return substr($path,8);
}
function cleanup_directory($settings) {
    $dir=$settings['state'].'/quarantine';
    if (!file_exists($dir) && !@mkdir($dir,0700)) throw new LocalStorageError('quarantine_unavailable');
    if (is_link($dir) || storage_real_path($dir)!==str_replace('\\','/',$dir) || !is_dir($dir)) throw new LocalStorageError('quarantine_unavailable');
    return $dir;
}
function cleanup_probe($path,$asset,$links=1) {
    clearstatcache(true,$path);
    $stat=@lstat($path);
    if (!$stat) return ['present'=>false,'intact'=>false];
    $regular=($stat['mode'] & 0170000)===0100000;
    $safe=$regular && !is_link($path) && storage_real_path($path)===str_replace('\\','/',$path) && $stat['nlink']===$links;
    $intact=$safe && $stat['size']===$asset['bytes'] && hash_equals($asset['sha256'],hash_file('sha256',$path));
    return ['present'=>true,'intact'=>$intact,'mtime'=>$stat['mtime'],'identity'=>[$stat['dev'],$stat['ino']]];
}
function cleanup_quota($settings,$manifest) {
    $dir=cleanup_directory($settings);$bytes=0;$files=0;$unknown=[];
    $known=[];
    foreach ($manifest['cleanup_ops'] ?? [] as $op) $known[$op['blob_id']]=true;
    foreach (new DirectoryIterator($dir) as $entry) {
        if ($entry->isDot()) continue;
        if (++$files>20000) throw new LocalStorageError('quarantine_inventory_limit');
        $stat=@lstat($entry->getPathname());
        if (!$stat || $entry->isLink() || !$entry->isFile() || $stat['nlink']!==1 ||
            !preg_match('/^([a-f0-9]{32})\.(blob|part)$/D',$entry->getFilename(),$match) || !isset($known[$match[1]]))
            $unknown[]=['entry_ref'=>hash('sha256',$entry->getFilename()),'reason'=>'unrecognized_or_unsafe'];
        if ($stat && $entry->isFile() && !$entry->isLink()) $bytes+=$stat['size'];
    }
    return ['bytes'=>$bytes,'files'=>$files,'unknown'=>$unknown,'max_bytes'=>$settings['quarantine_max_bytes'],'max_files'=>$settings['quarantine_max_files']];
}
function cleanup_review_locked($db,$settings,$manifest,$relative) {
    $asset=$manifest['assets'][$relative] ?? null;
    if (!$asset) throw new LocalOperationError('cleanup_not_owned');
    $location=storage_location($asset);$public=cleanup_probe($settings['root'].'/'.$relative,$asset);
    [$refs,$coverage,$complete]=operator_references($db);
    $count=$refs[basename($relative)]['count'] ?? 0;
    $scan=storage_scan_files($settings);
    $blob=isset($asset['blob_id']) ? cleanup_probe(cleanup_directory($settings).'/'.$asset['blob_id'].'.blob',$asset) : ['present'=>false,'intact'=>false];
    if ($location==='pending') $status='recovery_required';
    elseif ($location==='quarantined') $status=$blob['intact'] && !$public['present'] ? 'restorable' : 'quarantine_changed_or_collision';
    elseif (!$public['intact']) $status=$public['present'] ? 'changed_or_unsafe_hold' : 'missing_hold';
    elseif ($count>0) $status='referenced_hold';
    elseif (!$complete || $scan['unsafe']) $status='inventory_incomplete_hold';
    elseif ($asset['created_at']>time()-86400 || $public['mtime']>time()-86400) $status='young_hold';
    else $status='review_candidate';
    return ['path'=>'uploads/'.$relative,'revision'=>storage_revision($asset),'location'=>$location,'status'=>$status,
        'quarantine_eligible'=>$status==='review_candidate' && !storage_pending($manifest),
        'restore_eligible'=>$status==='restorable' && !storage_pending($manifest),
        'reference_count'=>$count,'reference_scan_complete'=>$complete,'reference_columns'=>$coverage,
        'public_present'=>$public['present'],'public_intact'=>$public['intact'],'archive_intact'=>$blob['intact'],
        'operation_id'=>$asset['operation_id'] ?? null,'active_usage'=>['bytes'=>$scan['bytes'],'files'=>$scan['files']],
        'quarantine_usage'=>cleanup_quota($settings,$manifest)];
}
function cleanup_public_operation($id,$op) {
    return ['id'=>$id,'action'=>$op['action'],'path'=>'uploads/'.$op['path'],'status'=>$op['status'],
        'phase'=>$op['phase'],'result'=>$op['result'] ?? null,'actor'=>$op['actor'],'evidence_ref'=>$op['evidence_ref'],
        'case_ref'=>$op['case_ref'],'recovery_case'=>$op['recovery_case'] ?? null];
}
function cleanup_checkpoint(&$manifest,$settings,$id,$phase,$input,$hook=null) {
    if (count($manifest['cleanup_audit'] ?? [])>=6000) throw new LocalStorageError('cleanup_audit_limit');
    $op=&$manifest['cleanup_ops'][$id];$asset=&$manifest['assets'][$op['path']];
    $op['phase']=$phase;$asset['revision']=storage_revision($asset)+1;
    $manifest['cleanup_audit'][]=['sequence'=>count($manifest['cleanup_audit'] ?? [])+1,'operation_id'=>$id,
        'phase'=>$phase,'actor'=>$input['actor'],'evidence_ref'=>$input['evidence_ref'],'case_ref'=>$input['case_ref'],
        'revision'=>$asset['revision'],'at'=>gmdate('Y-m-d\TH:i:s\Z')];
    storage_save_manifest($settings,$manifest);
    if ($hook) $hook($phase,$id);
}
function cleanup_copy($source,$part,$asset,$canDiscardPart) {
    if (!cleanup_probe($source,$asset)['intact']) throw new LocalOperationError('cleanup_source_changed');
    if (@lstat($part)) {
        if (cleanup_probe($part,$asset)['intact']) return;
        $stat=lstat($part);
        if (!$canDiscardPart || is_link($part) || ($stat['mode'] & 0170000)!==0100000 || $stat['nlink']!==1 || $stat['size']>$asset['bytes'])
            throw new LocalOperationError('cleanup_partial_unsafe');
        // A reviewed retry may discard only its named partial copy, with verified source retained.
        if (!unlink($part)) throw new LocalStorageError('cleanup_copy_failed');
    }
    $out=@fopen($part,'x+b');$in=null;
    if (!$out) throw new LocalStorageError('cleanup_copy_failed');
    try {
        $in=@fopen($source,'rb');
        if (!$in || stream_copy_to_stream($in,$out,$asset['bytes']+1)!==$asset['bytes'] || !fflush($out) ||
            (function_exists('fsync') && !fsync($out))) throw new LocalStorageError('cleanup_copy_failed');
    } finally { if (is_resource($in)) fclose($in);fclose($out); }
    if (!cleanup_probe($part,$asset)['intact']) throw new LocalStorageError('cleanup_copy_changed');
}
function cleanup_active_capacity($settings,$asset,$part=null) {
    $scan=storage_scan_files($settings);
    if ($scan['unsafe']) throw new LocalStorageError('storage_unsafe_entries');
    $existing=$part && is_file($part) ? filesize($part) : 0;
    if ($scan['files']+($existing ? 0 : 1)>$settings['max_files'] || $scan['bytes']-$existing+$asset['bytes']>$settings['max_bytes'])
        throw new LocalStorageError('quota_exceeded');
}
function cleanup_publish(&$manifest,$settings,$id,$input,$hook,$resume) {
    $op=&$manifest['cleanup_ops'][$id];$asset=$manifest['assets'][$op['path']];
    $source=cleanup_directory($settings).'/'.$op['blob_id'].'.blob';
    $target=$settings['root'].'/'.$op['path'];$part=dirname($target).'/.'.$id.'.restore.part';
    if (!cleanup_probe($source,$asset)['intact']) throw new LocalOperationError('quarantine_archive_changed');
    if (@lstat($target)) {
        $samePart=@lstat($part) && @fileinode($part)===@fileinode($target) && !is_link($part);
        $probe=cleanup_probe($target,$asset,$samePart ? 2 : 1);
        $published=isset($op['published_identity']) && ($probe['identity'] ?? null)===$op['published_identity'];
        if ((!$published && !$samePart) || !$probe['intact'])
            throw new LocalOperationError('cleanup_restore_collision');
        if ($samePart && !unlink($part)) throw new LocalStorageError('cleanup_copy_failed');
        return;
    }
    if (!is_dir(dirname($target)) && !mkdir(dirname($target),0755,true)) throw new LocalStorageError('cleanup_copy_failed');
    if (is_link(dirname($target)) || storage_real_path(dirname($target))!==str_replace('\\','/',dirname($target))) throw new LocalOperationError('cleanup_path_denied');
    cleanup_active_capacity($settings,$asset,$part);
    cleanup_copy($source,$part,$asset,$resume);
    if (!@chmod($part,0644)) throw new LocalStorageError('cleanup_copy_failed');
    // Same-filesystem hard-link publication is atomic and cannot overwrite a collision.
    if (!@link($part,$target)) throw new LocalOperationError('cleanup_restore_collision');
    $op['published_identity']=cleanup_probe($target,$asset,2)['identity'];
    cleanup_checkpoint($manifest,$settings,$id,'published',$input,$hook);
    if (!unlink($part)) throw new LocalStorageError('cleanup_copy_failed');
    if ($hook) $hook('restore_published',$id);
}
function cleanup_run(&$manifest,$db,$settings,$id,$input,$hook,$resume) {
    $op=&$manifest['cleanup_ops'][$id];$relative=$op['path'];$asset=&$manifest['assets'][$relative];
    $source=$settings['root'].'/'.$relative;$dir=cleanup_directory($settings);$blob=$dir.'/'.$op['blob_id'].'.blob';
    $rollback=($op['resolution'] ?? 'finish')==='rollback';
    if ($rollback) {
        if ($op['action']==='quarantine') {
            if (!cleanup_probe($source,$asset)['intact']) cleanup_publish($manifest,$settings,$id,$input,$hook,true);
            $asset['location']='active';
        } else {
            // Rollback restore only while it has not published a public copy. Never delete an unexpected public file.
            if (@lstat($source)) throw new LocalOperationError('cleanup_restore_already_published');
            $part=dirname($source).'/.'.$id.'.restore.part';
            if (@lstat($part)) {
                if (!cleanup_probe($blob,$asset)['intact'] || is_link($part) || !is_file($part) || lstat($part)['nlink']!==1)
                    throw new LocalOperationError('cleanup_partial_unsafe');
                if (!unlink($part)) throw new LocalStorageError('cleanup_copy_failed');
            }
            $asset['location']='quarantined';
        }
        $op['status']='completed';$op['result']='rolled_back';$asset['operation_id']=null;
        cleanup_checkpoint($manifest,$settings,$id,'rolled_back',$input,$hook);
        return;
    }
    if ($op['action']==='quarantine') {
        [$references,,$complete]=operator_references($db);
        if (!$complete || ($references[basename($relative)]['count'] ?? 0)>0) throw new LocalOperationError('cleanup_references_changed');
        $part=$dir.'/'.$op['blob_id'].'.part';
        if (@lstat($blob) && @lstat($part) && !is_link($part) && @fileinode($part)===@fileinode($blob) && cleanup_probe($blob,$asset,2)['intact']) {
            if (!unlink($part)) throw new LocalStorageError('cleanup_copy_failed');
        }
        if (!cleanup_probe($blob,$asset)['intact']) {
            if (@lstat($blob)) throw new LocalOperationError('quarantine_archive_changed');
            cleanup_copy($source,$part,$asset,$resume);
            if (!@link($part,$blob) || !unlink($part)) throw new LocalStorageError('cleanup_copy_failed');
            @chmod($blob,0600);
        }
        cleanup_checkpoint($manifest,$settings,$id,'copied',$input,$hook);
        if (@lstat($source)) {
            $probe=cleanup_probe($source,$asset);
            if (!$probe['intact'] || $probe['identity']!==$op['source_identity']) throw new LocalOperationError('cleanup_source_changed');
            if (!cleanup_probe($blob,$asset)['intact']) throw new LocalOperationError('quarantine_archive_changed');
            // The reference gate remains held throughout copy, recheck and removal.
            [$references,,$complete]=operator_references($db);
            if (!$complete || ($references[basename($relative)]['count'] ?? 0)>0) throw new LocalOperationError('cleanup_references_changed');
            if (!unlink($source)) throw new LocalStorageError('cleanup_remove_failed');
        }
        if ($hook) $hook('source_removed',$id);
        $asset['location']='quarantined';$asset['blob_id']=$op['blob_id'];
        $op['result']='quarantined';
    } else {
        cleanup_publish($manifest,$settings,$id,$input,$hook,$resume);
        $asset['location']='active';$op['result']='restored';
    }
    $op['status']='completed';$asset['operation_id']=null;
    cleanup_checkpoint($manifest,$settings,$id,'completed',$input,$hook);
}
function cleanup_execute($db,$settings,$input,$hook=null) {
    foreach (['actor'=>'/^demo_[a-z0-9_]{3,40}$/D','evidence_ref'=>'/^fixture:[a-z0-9-]{3,64}$/D','case_ref'=>'/^fixture:[a-z0-9-]{3,64}$/D'] as $key=>$pattern)
        if (!is_string($input[$key] ?? null) || !preg_match($pattern,$input[$key])) throw new LocalOperationError('cleanup_review_required');
    if (($input['reviewed'] ?? null)!==true || !is_int($input['revision'] ?? null) || $input['revision']<0)
        throw new LocalOperationError('cleanup_review_required');
    $gate=storage_reference_acquire($db);
    try { return storage_with_lock($settings,true,static function($manifest) use ($db,$settings,$input,$hook) {
        $action=$input['action'];$relative=cleanup_relative($input['path']);
        $asset=$manifest['assets'][$relative] ?? null;
        if (!$asset) throw new LocalOperationError('cleanup_not_owned');
        $canonical=$input;ksort($canonical);$signature=hash('sha256',json_encode($canonical));
        $case=$manifest['cleanup_cases'][$input['case_ref']] ?? null;
        $resume=false;
        if ($case) {
            if (!hash_equals($case['signature'],$signature)) throw new LocalOperationError('cleanup_case_conflict');
            $id=$case['operation_id'];$op=$manifest['cleanup_ops'][$id];
            if ($action!=='storage-recover' || $op['status']==='completed')
                return ['success'=>$op['status']==='completed','replayed'=>true,'operation'=>cleanup_public_operation($id,$op)];
            $resume=true;
        } else {
            if (storage_revision($asset)!==$input['revision']) throw new LocalOperationError('cleanup_stale_review');
            if (count($manifest['cleanup_cases'] ?? [])>=2000 || count($manifest['cleanup_audit'] ?? [])>5968)
                throw new LocalStorageError('cleanup_audit_limit');
            if ($action==='storage-recover') {
                $id=$input['operation_id'] ?? '';
                $op=$manifest['cleanup_ops'][$id] ?? null;
                if (!$op || $op['path']!==$relative || $op['status']!=='pending' || ($asset['operation_id'] ?? null)!==$id)
                    throw new LocalOperationError('cleanup_recovery_denied');
                if (isset($op['recovery_case'])) throw new LocalOperationError('cleanup_recovery_case_required');
                if (!in_array($input['decision'] ?? null,['finish','rollback'],true)) throw new LocalOperationError('cleanup_recovery_denied');
                $public=cleanup_probe($settings['root'].'/'.$relative,$asset);
                if ($input['decision']==='finish' && $op['action']==='quarantine') {
                    [$refs,,$complete]=operator_references($db);
                    if (!$complete || ($refs[basename($relative)]['count'] ?? 0)>0) throw new LocalOperationError('cleanup_references_changed');
                }
                if ($input['decision']==='rollback' && $op['action']==='restore' && $public['present'])
                    throw new LocalOperationError('cleanup_restore_already_published');
                if ($input['decision']==='rollback' && $op['action']==='quarantine' && $public['present'] && !$public['intact'])
                    throw new LocalOperationError('cleanup_source_changed');
                $manifest['cleanup_ops'][$id]['resolution']=$input['decision'];
                $manifest['cleanup_ops'][$id]['recovery_case']=$input['case_ref'];
                $resume=true;
            } else {
                if (storage_pending($manifest)) throw new LocalOperationError('storage_recovery_required');
                if (count($manifest['cleanup_ops'] ?? [])>=1000) throw new LocalStorageError('cleanup_audit_limit');
                $review=cleanup_review_locked($db,$settings,$manifest,$relative);
                if ($action==='storage-quarantine') {
                    if (!$review['quarantine_eligible']) throw new LocalOperationError('cleanup_not_eligible');
                    $quota=$review['quarantine_usage'];
                    if ($quota['unknown']) throw new LocalOperationError('quarantine_unknown_entries');
                    if ($quota['bytes']+$asset['bytes']>$quota['max_bytes'] || $quota['files']+1>$quota['max_files'])
                        throw new LocalOperationError('quarantine_quota_exceeded');
                } else {
                    if (!$review['restore_eligible']) throw new LocalOperationError('cleanup_restore_denied');
                    cleanup_active_capacity($settings,$asset);
                }
                $id=bin2hex(random_bytes(16));
                $manifest['cleanup_ops'][$id]=['action'=>$action==='storage-quarantine' ? 'quarantine' : 'restore',
                    'path'=>$relative,'blob_id'=>$action==='storage-quarantine' ? $id : $asset['blob_id'],
                    'status'=>'pending','phase'=>'prepared','actor'=>$input['actor'],'evidence_ref'=>$input['evidence_ref'],
                    'case_ref'=>$input['case_ref'],'source_identity'=>cleanup_probe($settings['root'].'/'.$relative,$asset)['identity'] ?? null];
                $manifest['assets'][$relative]['location']='pending';$manifest['assets'][$relative]['operation_id']=$id;
            }
            $manifest['cleanup_cases'][$input['case_ref']]=['operation_id'=>$id,'signature'=>$signature];
        }
        try {
            if (!$case) cleanup_checkpoint($manifest,$settings,$id,$resume ? 'recovery_reviewed' : 'prepared',$input,$hook);
            cleanup_run($manifest,$db,$settings,$id,$input,$hook,$resume);
            return ['success'=>true,'replayed'=>$case!==null,'operation'=>cleanup_public_operation($id,$manifest['cleanup_ops'][$id])];
        } catch (Throwable $error) {
            $persisted=json_decode(file_get_contents($settings['state'].'/manifest.json'),true);
            if (!isset($persisted['cleanup_ops'][$id])) throw $error;
            if ($persisted['cleanup_ops'][$id]['status']==='completed')
                return ['success'=>true,'replayed'=>true,'operation'=>cleanup_public_operation($id,$persisted['cleanup_ops'][$id])];
            return ['success'=>false,'error'=>'cleanup_pending_review','operation'=>cleanup_public_operation($id,$persisted['cleanup_ops'][$id])];
        }
    }); } finally { storage_reference_release($db,$gate); }
}

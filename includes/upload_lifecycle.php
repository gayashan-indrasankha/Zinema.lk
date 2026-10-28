<?php
/** Metadata and cooperative reference gate shared by development writers and CLI. */
function storage_location($asset) { return $asset['location'] ?? 'active'; }
function storage_revision($asset) { return $asset['revision'] ?? 0; }
function storage_pending($manifest) {
    foreach ($manifest['cleanup_ops'] ?? [] as $op) if ($op['status'] === 'pending') return true;
    return false;
}
function storage_validate_lifecycle($manifest) {
    foreach ($manifest['assets'] as $asset) {
        if (!in_array(storage_location($asset),['active','pending','quarantined'],true) ||
            !is_int(storage_revision($asset)) || storage_revision($asset)<0)
            throw new LocalStorageError('storage_manifest_invalid');
    }
    foreach (['cleanup_ops'=>1000,'cleanup_cases'=>2000,'cleanup_audit'=>6000] as $key=>$limit) {
        if (isset($manifest[$key]) && (!is_array($manifest[$key]) || count($manifest[$key])>$limit))
            throw new LocalStorageError('storage_manifest_invalid');
    }
    foreach ($manifest['cleanup_ops'] ?? [] as $id=>$op) {
        if (!is_string($id) || !preg_match('/^[a-f0-9]{32}$/D',$id) || !is_array($op) ||
            !storage_managed_path($op['path'] ?? null) || !in_array($op['action'] ?? null,['quarantine','restore'],true) ||
            !in_array($op['status'] ?? null,['pending','completed'],true) ||
            !is_string($op['blob_id'] ?? null) || !preg_match('/^[a-f0-9]{32}$/D',$op['blob_id']) ||
            !isset($manifest['assets'][$op['path']])) throw new LocalStorageError('storage_manifest_invalid');
    }
    foreach ($manifest['assets'] as $relative=>$asset) {
        if (storage_location($asset)==='pending') {
            $pending=$manifest['cleanup_ops'][$asset['operation_id'] ?? ''] ?? null;
            if (!$pending || $pending['path']!==$relative || $pending['status']!=='pending')
                throw new LocalStorageError('storage_manifest_invalid');
        }
        if (storage_location($asset)==='quarantined' && (!is_string($asset['blob_id'] ?? null) ||
            !preg_match('/^[a-f0-9]{32}$/D',$asset['blob_id']))) throw new LocalStorageError('storage_manifest_invalid');
        if (isset($asset['blob_id']) && (($manifest['cleanup_ops'][$asset['blob_id']]['path'] ?? null)!==$relative ||
            ($manifest['cleanup_ops'][$asset['blob_id']]['action'] ?? null)!=='quarantine'))
            throw new LocalStorageError('storage_manifest_invalid');
    }
    foreach ($manifest['cleanup_cases'] ?? [] as $case=>$entry) {
        if (!is_string($case) || !preg_match('/^fixture:[a-z0-9-]{3,64}$/D',$case) ||
            !is_array($entry) || !isset($manifest['cleanup_ops'][$entry['operation_id'] ?? '']) ||
            !is_string($entry['signature'] ?? null) || !preg_match('/^[a-f0-9]{64}$/D',$entry['signature']))
            throw new LocalStorageError('storage_manifest_invalid');
    }
    foreach ($manifest['cleanup_audit'] ?? [] as $index=>$event) {
        if (!is_int($index) || !is_array($event) || ($event['sequence'] ?? null)!==$index+1 ||
            !isset($manifest['cleanup_ops'][$event['operation_id'] ?? '']) ||
            !is_string($event['actor'] ?? null) || !preg_match('/^demo_[a-z0-9_]{3,40}$/D',$event['actor']) ||
            !is_string($event['evidence_ref'] ?? null) || !preg_match('/^fixture:[a-z0-9-]{3,64}$/D',$event['evidence_ref']))
            throw new LocalStorageError('storage_manifest_invalid');
    }
}
function storage_save_manifest($settings,$manifest) {
    storage_validate_lifecycle($manifest);
    storage_write_json($settings['state'].'/manifest.json',$manifest);
    @chown($settings['state'].'/manifest.json','www-data'); @chgrp($settings['state'].'/manifest.json','www-data');
}
function storage_reference_acquire($db) {
    if (getenv('UPLOAD_QUOTA_MODE') !== 'local') return null;
    local_storage_settings();
    $name='zinema-development-upload-references';
    $stmt=$db->prepare('SELECT GET_LOCK(?,5)');$stmt->bind_param('s',$name);$stmt->execute();
    if ((int)$stmt->get_result()->fetch_row()[0] !== 1) throw new LocalStorageError('storage_reference_busy');
    return $name;
}
function storage_reference_release($db,$name) {
    if ($name===null) return;
    $stmt=$db->prepare('SELECT RELEASE_LOCK(?)');$stmt->bind_param('s',$name);$stmt->execute();
}
function storage_validate_reference_values($settings,$values) {
    if (!$settings) return;
    $names=[];$seen=0;
    $walk=static function($value,$depth=0) use (&$walk,&$names,&$seen) {
        if (++$seen>1000 || $depth>8) throw new LocalStorageError('storage_reference_invalid');
        if (is_array($value)) { foreach ($value as $item) $walk($item,$depth+1); }
        elseif (is_string($value)) {
            $text=rawurldecode(html_entity_decode($value,ENT_QUOTES | ENT_HTML5,'UTF-8'));
            preg_match_all('~(?<![a-f0-9])([a-f0-9]{32}\.(?:png|jpg|webp|mp4|webm))(?![a-z0-9])~i',$text,$matches);
            foreach ($matches[1] as $name) $names[strtolower($name)]=true;
        }
    };
    $walk($values);
    if (!$names) return;
    storage_with_lock($settings,false,static function($manifest) use ($settings,$names) {
        foreach ($manifest['assets'] as $relative=>$asset) {
            if (!isset($names[basename($relative)])) continue;
            $path=$settings['root'].'/'.$relative;
            if (storage_location($asset)!=='active' || is_link($path) || !is_file($path))
                throw new LocalStorageError('storage_reference_unavailable');
        }
    });
}

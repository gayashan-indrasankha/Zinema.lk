<?php
/** Local-only operator functions. No browser endpoint or delivery transport. */
class LocalOperationError extends RuntimeException {}

function operator_context($bootstrap = false) {
    if (PHP_SAPI !== 'cli' || getenv('APP_ENV') !== 'development' || getenv('ALLOW_LIVE_INTEGRATIONS') !== 'false' ||
        getenv('DB_HOST') !== '127.0.0.1' || getenv('DB_NAME') !== 'zinema_recovery_development' ||
        getenv('DB_PORT') !== '3306' || getenv('UPLOAD_QUOTA_MODE') !== 'local') throw new LocalOperationError('local_target_denied');
    foreach (['WHATSAPP','MEDIA','EXTRACTOR','PAYMENT'] as $adapter)
        if (getenv($adapter.'_MODE') !== 'disabled') throw new LocalOperationError('integrations_must_be_disabled');
    foreach (['DB_PASS'=>'db_password','GOOGLE_CLIENT_SECRET'=>'oauth_secret'] as $key=>$file) {
        $value = @file_get_contents('/run/secrets/'.$file);
        if (!is_string($value) || trim($value) === '') throw new LocalOperationError('local_configuration_unavailable');
        putenv($key.'='.trim($value));
    }
    require_once '/var/www/html/includes/environment.php';
    require_once '/var/www/html/includes/upload_storage.php';
    $settings = local_storage_settings();
    if (!$settings) throw new LocalOperationError('local_storage_required');
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    $db->set_charset('utf8mb4');
    $identity = $db->query('SELECT DATABASE(), @@port')->fetch_row();
    if ($identity[0] !== 'zinema_recovery_development' || (int)$identity[1] !== 3306) throw new LocalOperationError('local_target_denied');
    if ($bootstrap) initialize_local_upload_storage();
    storage_check_owner($settings);
    return [$db, $settings];
}

function operator_token_row($db, $id, $lock = false) {
    $stmt = $db->prepare("SELECT id, claim_state, claim_revision, claimed_at, expires_at, is_active, is_used, assigned_bot_id,
        reconciled_at, claim_hash IS NOT NULL AS has_claim, TIMESTAMPDIFF(SECOND,claimed_at,NOW()) AS age_seconds
        FROM whatsapp_tokens WHERE id=?" . ($lock ? ' FOR UPDATE' : ''));
    $stmt->bind_param('i',$id); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) throw new LocalOperationError('not_found');
    foreach (['id','claim_revision','is_active','is_used','assigned_bot_id','has_claim','age_seconds'] as $key)
        $row[$key] = $row[$key] === null ? null : (int)$row[$key];
    $row['eligible'] = $row['has_claim'] === 1 && $row['is_used'] === 0 && $row['reconciled_at'] === null &&
        in_array($row['claim_state'], ['claimed','sending','uncertain'], true) && $row['age_seconds'] >= 600;
    unset($row['has_claim']);
    return $row;
}

function operator_audit($db, $id) {
    $stmt = $db->prepare('SELECT id, token_id, observed_revision, previous_state, outcome, actor, evidence_ref, workers_stopped, created_at FROM delivery_reconciliations WHERE token_id=?');
    $stmt->bind_param('i',$id); $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function operator_resolve($db, $input) {
    $id = $input['token_id']; $revision = $input['revision'];
    $outcome = $input['outcome'] ?? ''; $actor = $input['actor'] ?? ''; $evidence = $input['evidence_ref'] ?? '';
    if (!in_array($outcome, ['delivered','not_delivered'], true) || !is_string($actor) ||
        !preg_match('/^demo_[a-z0-9_]{3,40}$/D',$actor) || !is_string($evidence) ||
        !preg_match('/^fixture:[a-z0-9-]{3,64}$/D',$evidence) || ($input['workers_stopped'] ?? null) !== true)
        throw new LocalOperationError('review_acknowledgement_required');
    $db->begin_transaction();
    try {
        $row = operator_token_row($db,$id,true);
        $audit = operator_audit($db,$id);
        if ($audit) {
            if ((int)$audit['observed_revision'] === $revision && $audit['outcome'] === $outcome &&
                $audit['actor'] === $actor && $audit['evidence_ref'] === $evidence) {
                $db->commit(); return ['success'=>true,'replayed'=>true,'audit'=>$audit];
            }
            throw new LocalOperationError('decision_conflict');
        }
        if ($row['claim_revision'] !== $revision) throw new LocalOperationError('stale_review');
        if (!$row['eligible']) throw new LocalOperationError('claim_not_eligible');
        $stmt = $db->prepare('INSERT INTO delivery_reconciliations(token_id,observed_revision,previous_state,outcome,actor,evidence_ref,workers_stopped) VALUES(?,?,?,?,?,?,1)');
        $stmt->bind_param('iissss',$id,$revision,$row['claim_state'],$outcome,$actor,$evidence); $stmt->execute();
        $state = $outcome === 'delivered' ? 'completed' : 'uncertain';
        $used = $outcome === 'delivered' ? 1 : 0;
        $stmt = $db->prepare('UPDATE whatsapp_tokens SET claim_revision=claim_revision+1, claim_state=?, is_used=?, is_active=0, reconciled_at=NOW(), used_at=IF(?=1,NOW(),used_at), expires_at=expires_at WHERE id=? AND claim_revision=? AND reconciled_at IS NULL');
        $stmt->bind_param('siiii',$state,$used,$used,$id,$revision); $stmt->execute();
        if ($stmt->affected_rows !== 1) throw new LocalOperationError('stale_review');
        $audit = operator_audit($db,$id);
        $db->commit();
        return ['success'=>true,'replayed'=>false,'audit'=>$audit];
    } catch (Throwable $error) { $db->rollback(); throw $error; }
}

function operator_references($db) {
    // Only media/path columns are read; credentials, bearer tokens and contact columns are excluded.
    $columns = $db->query("SELECT c.TABLE_NAME, c.COLUMN_NAME FROM information_schema.columns c
        JOIN information_schema.tables t ON t.TABLE_SCHEMA=c.TABLE_SCHEMA AND t.TABLE_NAME=c.TABLE_NAME
        WHERE c.TABLE_SCHEMA=DATABASE() AND t.TABLE_TYPE='BASE TABLE'
        AND c.DATA_TYPE IN ('char','varchar','text','mediumtext','longtext') ORDER BY c.TABLE_NAME, c.ORDINAL_POSITION")->fetch_all(MYSQLI_ASSOC);
    $references = []; $coverage = []; $complete = true;
    foreach ($columns as $column) {
        $table = $column['TABLE_NAME']; $field = $column['COLUMN_NAME'];
        if (!preg_match('/^[a-z0-9_]+$/D',$table) || !preg_match('/^[a-z0-9_]+$/D',$field) ||
            !preg_match('/(?:url|path|image|file_name|shot_video_file)$/D',$field)) continue;
        $coverage[] = $table.'.'.$field;
        $rows = $db->query("SELECT LEFT(`$field`,2048), OCTET_LENGTH(`$field`) FROM `$table` WHERE `$field` IS NOT NULL LIMIT 10001")->fetch_all(MYSQLI_NUM);
        if (count($rows) > 10000) { $complete = false; continue; }
        foreach ($rows as [$value,$length]) {
            if ((int)$length > 2048) $complete = false;
            $value = str_replace('\\','/',rawurldecode(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            preg_match_all('~(?<![a-f0-9])([a-f0-9]{32}\.(?:png|jpg|webp|mp4|webm))(?![a-z0-9])~i',$value,$matches);
            foreach (array_unique($matches[1]) as $name) {
                $name = strtolower($name);
                $references[$name]['count'] = ($references[$name]['count'] ?? 0)+1;
                $references[$name]['sources'][$table.'.'.$field] = true;
            }
        }
    }
    return [$references,$coverage,$complete];
}

function operator_inventory($db,$settings) {
    return storage_with_lock($settings,false,static function ($manifest) use ($db,$settings) {
        $db->begin_transaction(MYSQLI_TRANS_START_READ_ONLY | MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT);
        try { [$references,$coverage,$complete] = operator_references($db); $db->commit(); }
        catch (Throwable $error) { $db->rollback(); throw $error; }
        $scan = storage_scan_files($settings); $items = []; $counts = [];
        foreach ($manifest['assets'] as $relative=>$asset) {
            $file = $scan['entries'][$relative] ?? null;
            $reference = $references[basename($relative)] ?? ['count'=>0,'sources'=>[]];
            if (storage_location($asset)==='quarantined') $status='quarantined';
            elseif (storage_location($asset)==='pending') $status='recovery_required';
            elseif (!$file) $status = 'missing_or_unsafe';
            elseif ($file['linked']) $status = 'linked_hold';
            elseif ($file['bytes'] !== $asset['bytes'] || !hash_equals($asset['sha256'],hash_file('sha256',$settings['root'].'/'.$relative))) $status = 'changed_hold';
            elseif ($reference['count'] > 1) $status = 'shared_hold';
            elseif ($reference['count'] === 1) $status = 'referenced_hold';
            elseif (!$complete || $scan['unsafe']) $status = 'inventory_incomplete_hold';
            elseif ($asset['created_at'] > time()-86400 || $file['mtime'] > time()-86400) $status = 'young_hold';
            else $status = 'review_candidate';
            $counts[$status] = ($counts[$status] ?? 0)+1;
            $items[] = ['path'=>'uploads/'.$relative,'bytes'=>$asset['bytes'],'status'=>$status,
                'reference_count'=>$reference['count'],'reference_sources'=>array_keys($reference['sources'])];
        }
        $unknown = [];
        foreach ($scan['entries'] as $relative=>$file) {
            if (!isset($manifest['assets'][$relative])) $unknown[] = ['entry_ref'=>hash('sha256',$relative),'bytes'=>$file['bytes'],'status'=>'unregistered_hold'];
        }
        return ['success'=>true,'read_only'=>true,'deletion_supported'=>false,'quota'=>[
            'max_bytes'=>$settings['max_bytes'],'used_bytes'=>$scan['bytes'],'remaining_bytes'=>max(0,$settings['max_bytes']-$scan['bytes']),
            'max_files'=>$settings['max_files'],'used_files'=>$scan['files'],'remaining_files'=>max(0,$settings['max_files']-$scan['files'])],
            'counts'=>$counts,'managed'=>$items,'unregistered'=>$unknown,'unsafe'=>$scan['unsafe'],
            'reference_columns'=>$coverage,'reference_scan_complete'=>$complete,
            'cleanup_available'=>true,'recovery_required'=>storage_pending($manifest)];
    });
}

function operator_dispatch($db,$settings,$input,$checkpoint=null) {
    if (!is_array($input) || !is_string($input['action'] ?? null)) throw new LocalOperationError('invalid_request');
    $action = $input['action'];
    $allowed = match ($action) {
        'held'=>['action','after_id'], 'inventory'=>['action'], 'review'=>['action','token_id'],
        'resolve'=>['action','token_id','revision','outcome','actor','evidence_ref','workers_stopped'],
        'storage-review'=>['action','path'], 'storage-history'=>['action','path'],
        'storage-quarantine','storage-restore'=>['action','path','revision','actor','evidence_ref','case_ref','reviewed'],
        'storage-recover'=>['action','path','revision','actor','evidence_ref','case_ref','reviewed','operation_id','decision'],
        default=>throw new LocalOperationError('invalid_action'),
    };
    if (array_diff(array_keys($input),$allowed)) throw new LocalOperationError('invalid_request');
    if (str_starts_with($action,'storage-')) {
        require_once __DIR__.'/upload_cleanup.php';
        $relative=cleanup_relative($input['path'] ?? null);
        if ($action==='storage-review') return storage_with_lock($settings,false,static fn($manifest)=>
            ['success'=>true,'review'=>cleanup_review_locked($db,$settings,$manifest,$relative)]);
        if ($action==='storage-history') return storage_with_lock($settings,false,static function($manifest) use ($relative) {
            $ops=[];$events=[];
            foreach ($manifest['cleanup_ops'] ?? [] as $id=>$op) if ($op['path']===$relative) $ops[]=cleanup_public_operation($id,$op);
            foreach ($manifest['cleanup_audit'] ?? [] as $event) if (($manifest['cleanup_ops'][$event['operation_id']]['path'] ?? null)===$relative) $events[]=$event;
            return ['success'=>true,'operations'=>$ops,'audit'=>$events,'permanent_purge_supported'=>false];
        });
        if ($checkpoint && (PHP_SAPI!=='cli' || getenv('ZINEMA_QUARANTINE_FIXTURES')!=='1')) throw new LocalOperationError('fixture_denied');
        return cleanup_execute($db,$settings,$input,$checkpoint);
    }
    if ($action === 'inventory') return operator_inventory($db,$settings);
    if ($action === 'held') {
        $after = $input['after_id'] ?? 0;
        if (!is_int($after) || $after < 0) throw new LocalOperationError('invalid_request');
        $stmt = $db->prepare("SELECT id FROM whatsapp_tokens WHERE id>? AND claim_hash IS NOT NULL AND reconciled_at IS NULL AND is_used=0 AND claim_state IN ('claimed','sending','uncertain') ORDER BY id LIMIT 100");
        $stmt->bind_param('i',$after); $stmt->execute(); $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        return ['success'=>true,'held'=>array_map(static fn($r)=>operator_token_row($db,(int)$r['id']),$rows),
            'next_after_id'=>$rows ? (int)$rows[count($rows)-1]['id'] : null];
    }
    $id = $input['token_id'] ?? null;
    if (!is_int($id) || $id < 1) throw new LocalOperationError('invalid_request');
    if ($action === 'review') return ['success'=>true,'review'=>operator_token_row($db,$id),'audit'=>operator_audit($db,$id)];
    if ($action === 'resolve') {
        if (!is_int($input['revision'] ?? null) || $input['revision'] < 0) throw new LocalOperationError('invalid_request');
        return operator_resolve($db,$input);
    }
    throw new LocalOperationError('invalid_action');
}

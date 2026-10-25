<?php
/** Destructive simulations are restricted to the separate disposable fictional volume. */
ini_set('display_errors','0');
require_once __DIR__.'/operations.php';
function fixture_png($path) {
    $image = imagecreatetruecolor(4,4);
    imagefill($image,0,0,imagecolorallocate($image,40,150,210));
    imagepng($image,$path); imagedestroy($image);
}
try {
    if (PHP_SAPI !== 'cli' || getenv('ZINEMA_OPERATOR_FIXTURES') !== '1' ||
        !str_ends_with(getenv('UPLOAD_STORAGE_OWNER') ?: '', '-operator-fixtures')) throw new LocalOperationError('fixture_denied');
    $input = json_decode(stream_get_contents(STDIN,8193),true,32,JSON_THROW_ON_ERROR);
    $action = $input['action'] ?? '';
    $run = $input['run'] ?? '';
    if (!is_string($run) || !preg_match('/^[a-f0-9]{12}$/D',$run)) throw new LocalOperationError('fixture_denied');
    foreach (['max_bytes'=>'UPLOAD_MAX_BYTES','max_files'=>'UPLOAD_MAX_FILES'] as $key=>$variable) {
        if (isset($input[$key])) {
            if (!is_int($input[$key]) || $input[$key] < 1) throw new LocalOperationError('fixture_denied');
            putenv($variable.'='.$input[$key]);
        }
    }
    [$db,$settings] = operator_context($action === 'init');
    $response = ['success'=>true];
    if ($action === 'init') { /* Ownership and manifest already verified. */ }
    elseif ($action === 'stats') {
        $response += storage_with_lock($settings,false,static function($manifest) use ($settings) {
            $scan=storage_scan_files($settings);
            return ['files'=>$scan['files'],'bytes'=>$scan['bytes'],'registered'=>count($manifest['assets'])];
        });
    } elseif ($action === 'store' || $action === 'invalid_path') {
        $source = tempnam(sys_get_temp_dir(),'zinema-fixture-'); fixture_png($source);
        try {
            $relative = $action === 'invalid_path' ? '../escape.png' : bin2hex(random_bytes(16)).'.png';
            $response['path'] = storage_commit_upload($source,$relative);
            $response['bytes'] = filesize($source);
        } finally { @unlink($source); }
    } elseif (in_array($action,['age','change','missing'],true)) {
        $relative = substr($input['path'] ?? '',8);
        if (!storage_managed_path($relative)) throw new LocalOperationError('fixture_denied');
        storage_with_lock($settings,true,static function($manifest) use ($settings,$relative,$action) {
            if (!isset($manifest['assets'][$relative])) throw new LocalOperationError('fixture_denied');
            $path=$settings['root'].'/'.$relative;
            if (is_link($path) || realpath($path) !== $path) throw new LocalOperationError('fixture_denied');
            if ($action === 'age') {
                $manifest['assets'][$relative]['created_at']=time()-172800;
                touch($path,time()-172800);
                storage_write_json($settings['state'].'/manifest.json',$manifest);
                chown($settings['state'].'/manifest.json','www-data'); chgrp($settings['state'].'/manifest.json','www-data');
            } elseif ($action === 'change') file_put_contents($path,'fictional changed file');
            else unlink($path); // Only a registered fixture on the fixture-only volume.
        });
    } elseif ($action === 'reference') {
        $path = $input['path'] ?? '';
        if (!is_string($path) || !str_starts_with($path,'uploads/') || !storage_managed_path(substr($path,8))) throw new LocalOperationError('fixture_denied');
        $reference = !empty($input['encoded']) ? '/'.str_replace('/','%2F',$path) : $path;
        $title = 'Local Test Operations '.$run.' '.bin2hex(random_bytes(4));
        if (($input['source'] ?? 'collections') === 'delivery') {
            $contentId=random_int(10000000,100000000);
            $stmt=$db->prepare("INSERT INTO whatsapp_message_ids(content_type,content_id,message_id,local_file_path) VALUES('movie',?,'fictional-inventory-message',?)");
            $stmt->bind_param('is',$contentId,$reference);$stmt->execute();
        } else {
            $stmt = $db->prepare("INSERT INTO collections(title,description,genre,cover_image) VALUES(?,'Fictional fixture','Demo',?)");
            $stmt->bind_param('ss',$title,$reference); $stmt->execute();
        }
    } elseif ($action === 'unregistered') {
        $path=$settings['root'].'/fixture-'.$run.'-unknown.txt';
        $handle=fopen($path,'x'); fwrite($handle,'fictional unregistered media'); fclose($handle);
    } elseif ($action === 'links') {
        $outside=$settings['state'].'/fixture-'.$run.'-outside.txt';
        file_put_contents($outside,'FICTIONAL-OUTSIDE-NOT-READ');
        // Hard links cannot span the two named-volume filesystems.
        $inside=$settings['root'].'/fixture-'.$run.'-hardlink-source.txt';
        file_put_contents($inside,'fictional hardlink source');
        if (!symlink($outside,$settings['root'].'/fixture-'.$run.'-symlink') ||
            !link($inside,$settings['root'].'/fixture-'.$run.'-hardlink')) throw new LocalOperationError('fixture_link_failed');
    } elseif ($action === 'remove_links') {
        foreach (['symlink','hardlink','hardlink-source.txt'] as $suffix) {
            $path=$settings['root'].'/fixture-'.$run.'-'.$suffix;
            if (dirname($path) !== $settings['root']) throw new LocalOperationError('fixture_denied');
            if (is_link($path) || is_file($path)) unlink($path);
        }
    } elseif ($action === 'damage_manifest') {
        $backup=$settings['state'].'/fixture-'.$run.'-manifest.json';
        if (file_exists($backup)) throw new LocalOperationError('fixture_denied');
        if (!empty($input['missing'])) rename($settings['state'].'/manifest.json',$backup);
        else {
            copy($settings['state'].'/manifest.json',$backup);
            file_put_contents($settings['state'].'/manifest.json','{invalid fixture manifest');
        }
    } elseif ($action === 'restore_manifest') {
        $backup=$settings['state'].'/fixture-'.$run.'-manifest.json';
        $manifest=json_decode(file_get_contents($backup),true,32,JSON_THROW_ON_ERROR);
        if (($manifest['owner'] ?? null)!==$settings['owner']) throw new LocalOperationError('fixture_denied');
        storage_write_json($settings['state'].'/manifest.json',$manifest); unlink($backup);
        chown($settings['state'].'/manifest.json','www-data'); chgrp($settings['state'].'/manifest.json','www-data');
    } elseif ($action === 'token_cleanup') {
        // A temporary table shadows the real name only on this verified connection.
        // No existing token or audit row is modified or deleted by this test.
        $stage='create_temporary';
        $db->query("CREATE TEMPORARY TABLE whatsapp_tokens (
            id int PRIMARY KEY, token varchar(12), content_type varchar(10), content_id int,
            expires_at datetime, is_used tinyint NOT NULL DEFAULT 0, claim_hash char(64),
            claim_state varchar(16), reconciled_at datetime)");
        $stage='seed_temporary';
        $db->query("INSERT INTO whatsapp_tokens(id,token,content_type,content_id,expires_at,claim_hash,claim_state,reconciled_at) VALUES
            (1,'TFIXTURE0001','movie',1,DATE_SUB(NOW(),INTERVAL 1 HOUR),NULL,NULL,NULL),
            (2,'TFIXTURE0002','movie',1,DATE_SUB(NOW(),INTERVAL 1 HOUR),REPEAT('a',64),'uncertain',NULL),
            (3,'TFIXTURE0003','movie',1,DATE_SUB(NOW(),INTERVAL 1 HOUR),NULL,NULL,NOW())");
        $stage='load_cleanup_helper';
        require_once '/var/www/html/includes/whatsapp_token.php';
        $stage='execute_temporary_cleanup';
        $response['deleted']=cleanupExpiredTokens($db);
        $stage='inspect_temporary';
        $response['remaining_ids']=array_column($db->query('SELECT id FROM whatsapp_tokens ORDER BY id')->fetch_all(MYSQLI_ASSOC),'id');
    } elseif ($action === 'reference_limit') {
        // Shadow only the media columns read by inventory, only on this fixture connection.
        $db->query('CREATE TEMPORARY TABLE movies (cover_image varchar(500), video_url longtext)');
        $db->query("INSERT INTO movies(video_url) VALUES(REPEAT('x',3000))");
        $response=operator_inventory($db,$settings);
    } elseif ($action === 'http_upload') {
        $source=tempnam(sys_get_temp_dir(),'zinema-http-');fixture_png($source);
        $curl=curl_init();
        try {
            $headers=['X-Test-Key: '.trim(file_get_contents('/run/secrets/control_key'))];
            foreach (['max_bytes'=>'X-Fixture-Max-Bytes','max_files'=>'X-Fixture-Max-Files'] as $key=>$header)
                if (isset($input[$key])) $headers[]=$header.': '.$input[$key];
            curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_COOKIEFILE=>'',CURLOPT_TIMEOUT=>10,CURLOPT_HTTPHEADER=>$headers]);
            curl_setopt($curl,CURLOPT_URL,'http://127.0.0.1:9082/admin/login.php');
            $form=curl_exec($curl);
            if (!is_string($form) || !preg_match('/name="csrf_token" value="([a-f0-9]{64})"/',$form,$match)) throw new LocalOperationError('fixture_http_failed');
            curl_setopt_array($curl,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query(['username'=>'demo_admin','password'=>trim(file_get_contents('/run/secrets/demo_password')),'csrf_token'=>$match[1]])]);
            curl_exec($curl);
            if (curl_getinfo($curl,CURLINFO_HTTP_CODE)!==302) throw new LocalOperationError('fixture_http_failed');
            curl_setopt_array($curl,[CURLOPT_POST=>false,CURLOPT_URL=>'http://127.0.0.1:9082/admin/login.php']);
            $form=curl_exec($curl);preg_match('/name="csrf_token" value="([a-f0-9]{64})"/',$form,$match);
            $title='Local Test Operations HTTP '.$run;
            $fields=['title'=>$title,'release_date'=>'2026-01-01','genre'=>'Demo','rating'=>'5','video_url'=>'','csrf_token'=>$match[1],
                'cover_image'=>new CURLFile($source,'image/png','fictional.png')];
            if (isset($input['movie_id'])) { $fields['edit_movie']='1';$fields['movie_id']=$input['movie_id']; }
            else $fields['add_movie']='1';
            curl_setopt_array($curl,[CURLOPT_URL=>'http://127.0.0.1:9082/admin/movies.php',CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$fields]);
            curl_exec($curl);$response['http']=curl_getinfo($curl,CURLINFO_HTTP_CODE);
            $stmt=$db->prepare('SELECT id,cover_image FROM movies WHERE title=? ORDER BY id DESC LIMIT 1');
            $stmt->bind_param('s',$title);$stmt->execute();$response['record']=$stmt->get_result()->fetch_assoc();
        } finally { curl_close($curl); @unlink($source); }
    } else throw new LocalOperationError('invalid_fixture_action');
} catch (Throwable $error) {
    $known=$error instanceof LocalOperationError || $error instanceof LocalStorageError;
    $response=['success'=>false,'error'=>$known ? $error->getMessage() : 'fixture_unavailable'];
    if (($action ?? '') === 'token_cleanup') $response['diagnostic']=['stage'=>$stage ?? 'guard','category'=>get_class($error),'code'=>$error->getCode()];
}
echo json_encode($response,JSON_UNESCAPED_SLASHES).PHP_EOL;
exit(($response['success'] ?? false) ? 0 : 1);

<?php
// CLI-only fictional product seed. No arbitrary SQL, production target or deletion.
ini_set('display_errors','0');
require_once __DIR__.'/operations.php';
try {
    [$db,$settings]=operator_context();
    $input=json_decode(stream_get_contents(STDIN,2049),true,16,JSON_THROW_ON_ERROR);
    $run=$input['run'] ?? '';
    if (!is_string($run) || !preg_match('/^[a-f0-9]{12}$/D',$run)) throw new LocalOperationError('fixture_denied');
    $title='Local Test Product '.$run;
    $image=imagecreatetruecolor(48,72);imagefill($image,0,0,imagecolorallocate($image,40,60,140));
    $temp=tempnam(sys_get_temp_dir(),'product-');imagepng($image,$temp);imagedestroy($image);
    try {$cover=storage_commit_upload($temp,bin2hex(random_bytes(16)).'.png');} finally {unlink($temp);}
    $temp=tempnam(sys_get_temp_dir(),'product-');file_put_contents($temp,base64_decode(file_get_contents(__DIR__.'/fixtures/motion-demo.mp4.b64'),true));
    try {$clip=storage_commit_upload($temp,'trailers/'.bin2hex(random_bytes(16)).'.mp4');} finally {unlink($temp);}
    $admin=$db->query("SELECT id FROM admins WHERE username='demo_admin'")->fetch_row()[0] ?? null;
    if (!$admin) throw new LocalOperationError('fixture_denied');
    $gate=storage_reference_acquire($db);$db->begin_transaction();
    try {
        $stmt=$db->prepare("INSERT INTO collections(title,description,genre,cover_image) VALUES(?,'Original fictional collection','Demo',?)");$stmt->bind_param('ss',$title,$cover);$stmt->execute();$collection=$db->insert_id;
        $stmt=$db->prepare("INSERT INTO movies(title,release_date,genre,rating,cover_image,video_url,collection_id,description) VALUES(?,'2026-01-01','Demo',5,?,'',?,'Original fictional movie')");$stmt->bind_param('ssi',$title,$cover,$collection);$stmt->execute();$movie=$db->insert_id;
        $stmt=$db->prepare("INSERT INTO series(title,release_date,genre,description,cover_image) VALUES(?,'2026-01-01','Demo','Original fictional series',?)");$stmt->bind_param('ss',$title,$cover);$stmt->execute();$series=$db->insert_id;
        $stmt=$db->prepare("INSERT INTO episodes(series_id,season_number,episode_number,title,description,video_url,thumb_image) VALUES(?,1,1,?,'Original fictional episode','',?)");$stmt->bind_param('iss',$series,$title,$cover);$stmt->execute();$episode=$db->insert_id;
        $stmt=$db->prepare("INSERT INTO shots(title,description,fb_share_url,shot_video_file,linked_content_type,linked_content_id,admin_id) VALUES(?,'Original motion demonstration','',?,'movie',?,?)");$stmt->bind_param('ssii',$title,$clip,$movie,$admin);$stmt->execute();$shot=$db->insert_id;
        $stmt=$db->prepare("INSERT INTO trailers(movie_id,title,description,video_url,thumbnail_url) VALUES(?,?,'Original motion demonstration',?,?)");$stmt->bind_param('isss',$movie,$title,$clip,$cover);$stmt->execute();$trailer=$db->insert_id;
        $db->commit();
    } catch (Throwable $e) {$db->rollback();throw $e;} finally {storage_reference_release($db,$gate);}
    // Observe the real issuance policy without exposing an online fake bot to other connections.
    require_once '/var/www/html/includes/whatsapp_token.php';
    $_SERVER['REMOTE_ADDR']='127.0.0.1';$bot=random_int(1000000000,2000000000);
    $db->begin_transaction();
    try {
        $stmt=$db->prepare("INSERT INTO bot_health_status(bot_id,bot_phone,status,last_heartbeat) VALUES(?,'0000000000','online',NOW())");$stmt->bind_param('i',$bot);$stmt->execute();
        $stmt=$db->prepare("INSERT INTO whatsapp_message_ids(content_type,content_id,message_id) VALUES('movie',?,'fictional-message')");$stmt->bind_param('i',$movie);$stmt->execute();
        $issued=createWhatsAppToken($db,$movie,'movie',10);
        if (!isset($issued['token'])) throw new RuntimeException('Fictional issuance failed');
        $stmt=$db->prepare("UPDATE bot_health_status SET status='offline' WHERE bot_id=? AND bot_phone='0000000000'");$stmt->bind_param('i',$bot);$stmt->execute();$db->commit();
    } catch (Throwable $e) {$db->rollback();throw $e;}
    $result=['success'=>true,'run'=>$run,'title'=>$title,'movie'=>$movie,'series'=>$series,'episode'=>$episode,'collection'=>$collection,'shot'=>$shot,'trailer'=>$trailer,'cover'=>$cover,'clip'=>$clip,
        'delivery'=>['token'=>$issued['token'],'token_id'=>$issued['id'],'bot_id'=>$issued['assigned_bot_id']]];
} catch (Throwable $e) {$result=['success'=>false,'error'=>'fictional_product_seed_failed'];}
echo json_encode($result,JSON_UNESCAPED_SLASHES).PHP_EOL;
exit($result['success'] ? 0 : 1);

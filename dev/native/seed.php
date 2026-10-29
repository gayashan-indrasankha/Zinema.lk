<?php
// Only the privately bootstrapped native fixture may seed itself.
if (PHP_SAPI!=='cli' || !defined('ZINEMA_NATIVE_CONTEXT')) exit(1);
try {
    $db=native_demo_db();$c=ZINEMA_NATIVE_CONTEXT;
    require_once $c['public'].'/includes/upload_storage.php';
    initialize_local_upload_storage();
    $marker=$c['home'].'/native/seeded.json';
    if (file_exists($marker)) {
        $value=json_decode(file_get_contents($marker),true);
        if (($value['owner'] ?? '')!==$c['owner']) throw new RuntimeException();
    } else {
        $cover=substr(hash('sha256',$c['owner'].'cover'),0,32).'.png';
        $clip='trailers/'.substr(hash('sha256',$c['owner'].'clip'),0,32).'.mp4';
        storage_commit_upload($c['home'].'/generated-acceptance.png',$cover);
        storage_commit_upload($c['public'].'/demo.mp4',$clip);
        $coverUrl=BASE_URL.'/uploads/'.$cover;$clipUrl=BASE_URL.'/uploads/'.$clip;
        $stmt=$db->prepare('UPDATE movies SET cover_image=?,video_url=\'\' WHERE id IN (1,2)');$stmt->bind_param('s',$coverUrl);$stmt->execute();
        $stmt=$db->prepare('UPDATE shots SET shot_video_file=? WHERE id=1');$stmt->bind_param('s',$clipUrl);$stmt->execute();
        $stmt=$db->prepare('UPDATE trailers SET video_url=?,thumbnail_url=? WHERE id=1');$stmt->bind_param('ss',$clipUrl,$coverUrl);$stmt->execute();
        foreach ([['site_name','Zinema Local'],['default_bot_phone','000000000001'],['group_mode_enabled','0'],['token_expiry_minutes','10'],['rate_limit_per_minute','100'],['rate_limit_per_hour','1000']] as [$key,$value]) {
            $stmt=$db->prepare('INSERT INTO site_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');$stmt->bind_param('ss',$key,$value);$stmt->execute();
        }
        foreach ([['movie',1],['movie',2],['episode',1],['episode',2]] as [$type,$id]) {
            $dir=$c['home'].'/native/bot/media/'.($type==='movie'?'movies':'episodes').'/'.$id;
            if (!mkdir($dir,0700,true)) throw new RuntimeException();
            if (!copy($c['public'].'/demo.mp4',$dir.'/full.mp4')) throw new RuntimeException();
            $message='fictional-native-'.$type.'-'.$id;
            $stmt=$db->prepare('INSERT INTO whatsapp_message_ids(content_type,content_id,part_number,message_id,file_name) VALUES(?,?,0,?,\'original-demo.mp4\')');$stmt->bind_param('sis',$type,$id,$message);$stmt->execute();
        }
        storage_write_json($marker,['owner'=>$c['owner'],'schema'=>1]);
    }
    echo '{"success":true}';
} catch (Throwable $error) {fwrite(STDERR,"Native fictional initialization refused; private diagnostics suppressed.\n");exit(1);}

<?php
// CLI-only, exact local target and fictional records. Never a web deployment helper.
if(PHP_SAPI!=='cli')exit(1);
if(getenv('ZINEMA_STAGING_FIXTURES')!=='1'||!preg_match('/^stg-[a-f0-9]{12}$/D',getenv('ZINEMA_STAGING_CASE') ?: ''))exit(1);
require_once '/opt/zinema/dev/operations.php';
try {
    [$db,$settings]=operator_context();
    $input=json_decode(stream_get_contents(STDIN),true,32,JSON_THROW_ON_ERROR);$action=$input['action'] ?? '';
    if($action==='populate') {
        $f=$input['fixture'];$cover=$f['cover'];$clip=$f['clip'];$collection=(int)$f['collection'];$admin=$db->query("SELECT id FROM admins WHERE username='demo_admin'")->fetch_row()[0];
        if(!str_starts_with($cover,'uploads/')||!storage_managed_path(substr($cover,8))||!str_starts_with($clip,'uploads/')||!storage_managed_path(substr($clip,8)))throw new RuntimeException();
        $description=str_repeat('Fictional staging description. ',100);$db->begin_transaction();
        try {
            $movies=[];$series=[];
            $stmt=$db->prepare("INSERT INTO movies(title,release_date,genre,rating,cover_image,video_url,collection_id,description) VALUES(?,'2026-01-01','Demo',5,?,'',?,?)");
            for($i=1;$i<=250;$i++){$title='Local Test Stage Movie '.$i;$stmt->bind_param('ssis',$title,$cover,$collection,$description);$stmt->execute();$movies[]=$db->insert_id;}
            $stmt=$db->prepare("INSERT INTO series(title,release_date,genre,description,cover_image) VALUES(?,'2026-01-01','Demo',?,?)");
            for($i=1;$i<=40;$i++){$title='Local Test Stage Series '.$i;$stmt->bind_param('sss',$title,$description,$cover);$stmt->execute();$series[]=$db->insert_id;}
            $stmt=$db->prepare("INSERT INTO episodes(series_id,season_number,episode_number,title,description,video_url,thumb_image) VALUES(?,1,?,?,?,'',?)");
            for($i=0;$i<200;$i++){$parent=$series[(int)floor($i/5)];$number=$i%5+1;$title='Local Test Stage Episode '.($i+1);$stmt->bind_param('iisss',$parent,$number,$title,$description,$cover);$stmt->execute();}
            $stmt=$db->prepare("INSERT INTO collections(title,description,genre,cover_image) VALUES(?,?,'Demo',?)");
            for($i=1;$i<=20;$i++){$title='Local Test Stage Collection '.$i;$stmt->bind_param('sss',$title,$description,$cover);$stmt->execute();}
            $stmt=$db->prepare("INSERT INTO trailers(movie_id,title,description,video_url,thumbnail_url) VALUES(?,?,?,?,?)");
            for($i=0;$i<10;$i++){$movie=$movies[$i];$title='Local Test Stage Trailer '.($i+1);$stmt->bind_param('issss',$movie,$title,$description,$clip,$cover);$stmt->execute();}
            $db->commit();
        }catch(Throwable $e){$db->rollback();throw $e;}
        file_put_contents('/var/lib/zinema/sessions/fictional-worker-state.json',json_encode(['fixture'=>true,'pending'=>'held','payload'=>'original-local-test-state']));
        $result=['success'=>true,'movies_added'=>250,'series_added'=>40,'episodes_added'=>200,'collections_added'=>20,'trailers_added'=>10];
    } elseif($action==='probe') {
        $tables=$db->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE' ORDER BY TABLE_NAME")->fetch_all(MYSQLI_NUM);$hashes=[];$counts=[];
        foreach($tables as [$table]) {
            if(!preg_match('/^[a-z_]+$/D',$table))throw new RuntimeException();
            $rows=$db->query("SELECT * FROM `$table`")->fetch_all(MYSQLI_ASSOC);$encoded=array_map(static fn($row)=>json_encode($row,JSON_THROW_ON_ERROR),$rows);sort($encoded,SORT_STRING);$hashes[$table]=hash('sha256',implode("\n",$encoded));$counts[$table]=count($rows);
        }
        $files=[];
        foreach(['uploads'=>'/var/www/html/uploads','storage'=>'/var/lib/zinema/storage','sessions'=>'/var/lib/zinema/sessions'] as $prefix=>$dir) {
            foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS)) as $file) {
                if($file->isLink()||!$file->isFile())throw new RuntimeException();
                $files[$prefix.'/'.substr($file->getPathname(),strlen($dir)+1)]=hash_file('sha256',$file->getPathname());
            }
        }
        ksort($files);$inventory=operator_inventory($db,$settings);
        $ddlDenied=false;try{$db->query('CREATE TABLE staging_permission_probe(id INT)');}catch(mysqli_sql_exception $e){$ddlDenied=(int)$e->getCode()===1142;}
        $result=['success'=>true,'tables'=>count($tables),'views'=>(int)$db->query("SELECT COUNT(*) FROM information_schema.VIEWS WHERE TABLE_SCHEMA=DATABASE()")->fetch_row()[0],'row_hashes'=>$hashes,'row_counts'=>$counts,'files'=>$files,'inventory_available'=>is_array($inventory),'ddl_denied'=>$ddlDenied];
    } elseif($action==='admission') {
        $image=imagecreatetruecolor(32,32);$temp=tempnam(sys_get_temp_dir(),'stage-');imagepng($image,$temp);imagedestroy($image);
        try{$path=storage_commit_upload($temp,bin2hex(random_bytes(16)).'.png');}finally{unlink($temp);}
        $result=['success'=>is_file('/var/www/html/'.$path)];
    } else throw new RuntimeException();
    echo json_encode($result,JSON_THROW_ON_ERROR).PHP_EOL;
}catch(Throwable $e){echo '{"success":false,"error":"staging_fixture_unavailable"}'.PHP_EOL;exit(1);}

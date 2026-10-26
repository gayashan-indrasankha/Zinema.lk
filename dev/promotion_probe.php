<?php
// CLI-only production-configuration probe against the exact owned fixture target.
if(PHP_SAPI!=='cli'||getenv('ZINEMA_PROMOTION_FIXTURES')!=='1'||getenv('APP_ENV')!=='production'||getenv('ALLOW_LIVE_INTEGRATIONS')!=='false'||getenv('DB_HOST')!=='127.0.0.1'||getenv('DB_NAME')!=='zinema_recovery_development'||getenv('BASE_URL')!=='https://127.0.0.1:8443')exit(1);
putenv('DB_PASS='.trim(file_get_contents('/run/secrets/db_password')));
require_once '/var/www/html/includes/environment.php';
require_once '/var/www/html/includes/upload_storage.php';
$rows=[];$check=static function($name,$ok)use(&$rows){$rows[]=['name'=>$name,'passed'=>(bool)$ok];};
try {
    $db=new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME,DB_PORT);
    $check('Exact fictional database/port', $db->query('SELECT DATABASE(),@@port')->fetch_row()===[DB_NAME,'3306']);
    $settings=local_storage_settings();storage_check_owner($settings);$scan=storage_scan_files($settings);
    $check('Production-config quota uses owned state',is_array($settings)&&$settings['max_bytes']===67108864);
    $original=file_get_contents($settings['state'].'/manifest.json');
    $image=imagecreatetruecolor(16,16);$temp=tempnam(sys_get_temp_dir(),'promotion-');imagepng($image,$temp);imagedestroy($image);
    try {
        putenv('UPLOAD_MAX_BYTES='.max(1,$scan['bytes']));$denied=false;
        try{storage_commit_upload($temp,bin2hex(random_bytes(16)).'.png');}catch(LocalStorageError $e){$denied=$e->getMessage()==='quota_exceeded';}
        $check('Byte quota refuses an additional upload',$denied);
        putenv('UPLOAD_MAX_BYTES=67108864');putenv('UPLOAD_MAX_FILES='.max(1,$scan['files']));$denied=false;
        try{storage_commit_upload($temp,bin2hex(random_bytes(16)).'.png');}catch(LocalStorageError $e){$denied=$e->getMessage()==='quota_exceeded';}
        $check('File quota refuses an additional upload',$denied);
        $check('Refused uploads preserve manifest bytes',file_get_contents($settings['state'].'/manifest.json')===$original);
        putenv('UPLOAD_MAX_FILES=500');putenv('ZINEMA_PROMOTION_FIXTURES=0');$denied=false;
        try{local_storage_settings();}catch(LocalStorageError $e){$denied=$e->getMessage()==='storage_target_denied';}
        $check('Production quota cannot escape explicit local fixture guard',$denied);
    }finally{unlink($temp);}
}catch(Throwable $e){$check('Production quota probe completed',false);}
$report=['tests'=>$rows,'summary'=>['total'=>count($rows),'passed'=>count(array_filter($rows,static fn($r)=>$r['passed'])),'failed'=>count(array_filter($rows,static fn($r)=>!$r['passed']))]];
echo json_encode($report).PHP_EOL;exit($report['summary']['failed']?1:0);

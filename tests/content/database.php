<?php
declare(strict_types=1);
require __DIR__.'/fixtures.php';
require_once dirname(__DIR__,2).'/database/ContentMigrations.php';
use Zinema\Content\{ContentMigrations,PdoRepository,ContentFailure};
use Zinema\Database\{Runner,MigrationFailure};

$passed=[];
function dbCheck(string $name,bool $condition):void { global $passed; check($condition); $passed[]=$name; }
function migrateDb(string $name):PDO { putenv('DB_NAME='.$name); return Runner::connect(Runner::settings()); }
function dbReject(string $name,callable $work):void {
    try { $work(); } catch (PDOException $error) { dbCheck($name,true); return; } check(false);
}
function migrationReject(string $name,string $reason,callable $work):void {
    try { $work(); } catch (MigrationFailure $error) { dbCheck($name,$error->reason===$reason); return; } check(false);
}
try {
    $mode=getenv('ZL15_MODE'); $migrations=ContentMigrations::load(dirname(__DIR__,2));
    if ($mode==='migrations') {
        $db=migrateDb('zinema_recovery_test'); $fresh=ContentMigrations::apply($db,$migrations);
        dbCheck('fresh ordered original-four plus rights-two apply',$fresh['applied']===6 && $fresh['scope']==='synthetic-rights-extension' && !$fresh['fullApplicationReady']);
        dbCheck('repeat skips six unchanged versions',ContentMigrations::apply($db,$migrations)['skipped']===6);
        foreach (array_slice($migrations,4) as $m) dbCheck('exact schema '.$m['table'],Runner::structure($db,$m['table'])===$m['expected']);
        dbCheck('six applied checksum ledger rows',(int)$db->query("SELECT COUNT(*) FROM schema_migrations WHERE status='applied'")->fetchColumn()===6);
        dbCheck('catalogue and accounts are not fabricated',(int)$db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ('movies','series','episodes','shots','users','admins')")->fetchColumn()===0);
        $upgrade=migrateDb('zinema_zl14_upgrade'); Runner::apply($upgrade,Runner::load(dirname(__DIR__,2).'/database'));
        $q=$upgrade->prepare('INSERT INTO site_settings(setting_key,setting_value) VALUES (?,?)');$q->execute(['fixture','sample']);
        $extension=ContentMigrations::apply($upgrade,$migrations);
        dbCheck('verified subset extends without replay',$extension['applied']===2 && $extension['skipped']===4);
        dbCheck('original synthetic subset row preserved',$upgrade->query("SELECT setting_value FROM site_settings WHERE setting_key='fixture'")->fetchColumn()==='sample');
        $failure=migrateDb('zinema_zl14_failure');
        migrationReject('new DDL post-commit failure is tracked','MIGRATION_STEP_FAILED',fn()=>ContentMigrations::apply($failure,$migrations,static function(PDO $db,string $v):void { if($v==='0005') $db->query('SELECT invalid_column FROM content_rights_records'); }));
        dbCheck('failed new version and existing table survive implicit commit',$failure->query("SELECT status FROM schema_migrations WHERE version='0005'")->fetchColumn()==='failed' && Runner::structure($failure,'content_rights_records')!==null);
        $retry=ContentMigrations::apply($failure,$migrations);
        dbCheck('exact new schema forward recovery succeeds',$retry['skipped']===4 && $retry['applied']===2 && $retry['adoptedOrRecovered']===1);
        $drift=migrateDb('zinema_zl14_drift'); ContentMigrations::apply($drift,$migrations); $drift->exec('ALTER TABLE content_rights_records ADD unreviewed INT NULL');
        migrationReject('rights schema drift refuses rerun','MIGRATION_SCHEMA_DRIFT',fn()=>ContentMigrations::apply($drift,$migrations));
        $db=migrateDb('zinema_recovery_test'); $other=Runner::connect(Runner::settings());
        $q=$other->prepare('SELECT GET_LOCK(?,0)'); $q->execute([Runner::lockName('zinema_recovery_test')]);
        try { migrationReject('extension shares the original advisory lock','MIGRATION_LOCKED',fn()=>ContentMigrations::apply($db,$migrations)); }
        finally { $q=$other->prepare('SELECT RELEASE_LOCK(?)');$q->execute([Runner::lockName('zinema_recovery_test')]); }
        echo json_encode(['passed'=>count($passed),'checks'=>$passed,'dataClass'=>'synthetic']); exit;
    }
    if (!in_array($mode,['service','overprivileged'],true)) throw new RuntimeException('UNKNOWN_MODE');
    putenv('DB_NAME=zinema_recovery_test'); $migrator=Runner::connect(Runner::settings());
    rejects('CONTENT_SERVICE_ROLE_REQUIRED',fn()=>new PdoRepository($migrator)); dbCheck('migration identity refused for ingestion',true);
    $password=(string)getenv('ZL15_CONTENT_PASSWORD');check(preg_match('/^LOCAL_ONLY_[a-f0-9]{64}$/D',$password)===1);
    $connect=static fn()=>new PDO('mysql:host=db;dbname=zinema_recovery_test;charset=utf8mb4','zinema_content_service',$password,
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>2]);
    $connection=$connect();
    if ($mode==='overprivileged') {
        rejects('CONTENT_SERVICE_PRIVILEGES_REFUSED',fn()=>new PdoRepository($connection));
        echo json_encode(['passed'=>true,'overprivilegedIdentityRefused'=>true]);exit;
    }
    $repository=new PdoRepository($connection); $w=ready('film','movie',$repository); publish($w);
    dbCheck('real parameterized workflow persists and is eligible',$w['service']->availability($w['record']['id'],$w['asset_id'],['territory'=>'LK','channel'=>'web'])['allowed']);
    dbCheck('public playback remains denied',!$w['service']->canStream()['allowed']);
    $w['auth']->identity=EDITOR; $audit=$w['service']->audit($w['record']['id']);
    dbCheck('one durable audit event per committed version',count($audit)===$w['record']['version']);
    $m=metadata('movie','502'); $m['title']="Synthetic ' ; SELECT is inert data"; $r=$w['service']->create($m);
    dbCheck('SQL punctuation remains a bound text value',$repository->find($r['id'])['metadata']['title']===$m['title']);
    rejects('DUPLICATE_REFERENCE',fn()=>$w['service']->create($m)); dbCheck('database unique reference blocks duplicate draft',true);
    $draft=metadata(); $draft['catalogue']=null; $a=$w['service']->create($draft); $b=$w['service']->create($draft);
    dbCheck('independent unassociated drafts remain distinct',$a['id']!==$b['id']);
    $stale=$repository->find($r['id']); $updated=$stale; $updated['version']++; $updated['metadata']['title']='Synthetic winner';
    $event=$audit[0]; $event['id']=bin2hex(random_bytes(16));$event['record_id']=$r['id'];$event['subject_id']=$r['id'];$event['record_version']=$updated['version'];$event['state']='draft';$event['action']='edit_metadata';
    $second=new PdoRepository($connect()); $second->save($stale['version'],$updated,$event);
    rejects('VERSION_CONFLICT',fn()=>$repository->save($stale['version'],$updated,$event));dbCheck('second connection cannot overwrite a stale version',true);
    $current=$repository->find($r['id']);$bad=$current;$bad['version']++;
    $duplicate=$event;$duplicate['record_version']=$bad['version'];
    rejects('DUPLICATE_REFERENCE',fn()=>$repository->save($current['version'],$bad,$duplicate));
    dbCheck('audit uniqueness failure atomically rolls back record update',$repository->find($r['id'])['version']===$current['version']);
    $q=$connection->prepare('SELECT COUNT(*) FROM content_rights_events WHERE record_id=?');$q->execute([$r['id']]);
    dbCheck('failed transaction adds no audit row',(int)$q->fetchColumn()===$current['version']);
    foreach (['DELETE FROM content_rights_events WHERE id=?','UPDATE content_rights_events SET action=? WHERE id=?',
        'CREATE TABLE should_not_exist (id INT)'] as $sql) dbReject('service rejects '.strtok($sql,' '),function()use($connection,$sql):void{
        $q=$connection->prepare($sql);$q->execute(substr_count($sql,'?')===2?['forbidden',str_repeat('f',32)]:(str_contains($sql,'?')?[str_repeat('f',32)]:[]));
    });
    dbReject('required document cannot be null',function()use($connection):void{ $q=$connection->prepare('INSERT INTO content_rights_records(id,version,state,content_type,document) VALUES (?,1,?,?,NULL)');$q->execute([str_repeat('f',32),'draft','movie']); });
    $connection->beginTransaction();
    try { rejects('TRANSACTION_BOUNDARY_REFUSED',fn()=>$repository->save($current['version'],$bad,$event));dbCheck('external transaction cannot absorb a workflow commit',true); }
    finally { $connection->rollBack(); }
    command($w,'revoke_grant',['grant_id'=>$w['grant_id']],PUBLISHER);
    dbCheck('revocation is observed by a fresh repository connection',!\Zinema\Content\Policy::available($second->find($w['record']['id']),$w['asset_id'],['territory'=>'LK','channel'=>'web'],$w['clock']->now())['allowed']);
    $w['auth']->identity=EDITOR; dbCheck('bounded repository scan returns only its page',count($repository->scan('',2))===2);
    $connection->beginTransaction();
    try {
        $q=$connection->prepare('UPDATE content_rights_records SET document=? WHERE id=?');$q->execute(['invalid-json',$b['id']]);
        rejects('CORRUPT_CONTENT_RECORD',fn()=>$repository->find($b['id']));dbCheck('malformed persisted JSON fails closed',true);
    } finally { $connection->rollBack(); }
    $connection->beginTransaction();
    try {
        $q=$connection->prepare('UPDATE content_rights_records SET state=? WHERE id=?');$q->execute(['published',$b['id']]);
        rejects('CORRUPT_CONTENT_RECORD',fn()=>$repository->find($b['id']));dbCheck('row and aggregate state disagreement fails closed',true);
    } finally { $connection->rollBack(); }
    echo json_encode(['passed'=>count($passed),'checks'=>$passed,'raceId'=>$r['id'],'raceVersion'=>$current['version'],'dataClass'=>'synthetic']);
} catch (Throwable $error) {
    $reason=$error instanceof ContentFailure || $error instanceof MigrationFailure ? $error->reason : 'CONTENT_DATABASE_ASSERTION_FAILED';
    fwrite(STDERR,json_encode(['error'=>$reason,'passedBeforeFailure'=>count($passed),'valuesRedacted'=>true]));exit(1);
}

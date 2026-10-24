<?php
declare(strict_types=1);
require __DIR__.'/fixtures.php';
use Zinema\Catalogue\{ApiFailure,Context,Controller,PdoSearchRepository,Query};
use Zinema\Database\Runner;
$checks=[];
function sqlCheck(string $name,bool $condition):void{global $checks;apiAssert($condition);$checks[]=$name;}
try {
    $migrator=Runner::connect(Runner::settings());Runner::target($migrator);
    $password=(string)getenv('ZL16_READER_PASSWORD');apiAssert(preg_match('/^LOCAL_ONLY_[a-f0-9]{64}$/D',$password)===1);
    $reader=new PDO('mysql:host=db;dbname=zinema_recovery_test;charset=utf8mb4','zinema_catalogue_reader',$password,
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>2]);
    if(getenv('ZL16_MODE')==='overprivileged'){
        try{new PdoSearchRepository($reader);}catch(ApiFailure $e){echo '{"overprivilegedReaderRefused":true}';exit;}throw new RuntimeException();
    }
    $repo=new PdoSearchRepository($reader);$records=apiWorld()['records'];
    $records[]=apiRecord('movie','504',"Fictional 50%_ ! O'Brien");
    $records[]=apiRecord('movie','505','සිනමා 演示 École');
    $draft=apiRecord('movie','506','Do not expose fictional draft');$draft['state']='draft';$records[]=$draft;
    foreach(['507','508','509'] as $id)$records[]=apiRecord('movie',$id,'Same Title');
    for($i=1000;$i<1512;$i++){$r=apiRecord('movie',(string)$i,'Fictional Perf '.str_pad((string)($i-1000),4,'0',STR_PAD_LEFT));
        $r['metadata']['release_year']=2020+($i%7);$r['metadata']['runtime_seconds']=($i%3)*300;$records[]=$r;}
    $insert=$migrator->prepare('INSERT INTO content_rights_records(id,version,state,content_type,catalogue_id,document) VALUES (?,?,?,?,?,?)');
    $migrator->beginTransaction();foreach($records as $r){$m=$r['metadata'];$insert->execute([$r['id'],$r['version'],$r['state'],$m['type'],$m['catalogue']['id'],json_encode($r,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);}$migrator->commit();
    $w=apiWorld($repo,$records);
    $response=apiCall($w,['resource'=>'movies','q'=>'alpha']);sqlCheck('real SQL case-insensitive title search',apiIds($response)===['501']);
    sqlCheck('real SQL Unicode search',apiIds(apiCall($w,['resource'=>'movies','q'=>'演示']))===['505']);
    sqlCheck('real SQL accent and case collation',apiIds(apiCall($w,['resource'=>'movies','q'=>'ecole']))===['505']);
    sqlCheck('LIKE percent underscore quote and escape are literal',apiIds(apiCall($w,['resource'=>'movies','q'=>"%_ ! O'B"]))===['504']);
    sqlCheck('SQL-shaped text is only a bound value',apiIds(apiCall($w,['q'=>"' OR 1=1 --"]))===[]);
    $r=apiCall($w,['resource'=>'movies','q'=>'Alpha','genre'=>'drama','language'=>'si','release_year'=>'2026','max_runtime'=>'600']);
    sqlCheck('real SQL combined filters',apiIds($r)===['501']);
    sqlCheck('runtime numeric bound',apiIds(apiCall($w,['resource'=>'movies','q'=>'Alpha','max_runtime'=>'599']))===[]);
    sqlCheck('hidden draft detail is indistinguishable from missing',apiCall($w,['resource'=>'movies','id'=>'506'])['body']['error']===apiCall($w,['resource'=>'movies','id'=>'999'])['body']['error']);
    sqlCheck('private metadata entitlement is denied by ID',apiCall($w,['resource'=>'movies','id'=>'503'])['status']===404);
    foreach(['title_asc','title_desc','release_year_asc','release_year_desc'] as $sort){
        $cursor=null;$ids=[];do{$q=['resource'=>'movies','q'=>'Same Title','limit'=>'1','sort'=>$sort];if($cursor!==null)$q['cursor']=$cursor;$r=apiCall($w,$q);apiAssert($r['status']===200);array_push($ids,...apiIds($r));$cursor=$r['body']['data']['nextCursor'];}while($cursor!==null);
        sqlCheck('real keyset stable ties '.$sort,$ids===['507','508','509']);
    }
    sqlCheck('real parent and episode filtering',apiIds(apiCall($w,['resource'=>'episodes','parent_type'=>'series','parent_id'=>'601']))===['701']);
    $r=apiCall($w,['resource'=>'availability','content_type'=>'movie','id'=>'502']);sqlCheck('anonymous premium denial with separate preview eligibility',!$r['body']['data']['availability']['playbackEligible']&&$r['body']['data']['availability']['previewEligible']);
    $w['contexts']->context=new Context(EDITOR,true,'LK',true,30,str_repeat('2',32));
    sqlCheck('own server-side grant allows eligibility only',apiCall($w,['resource'=>'availability','content_type'=>'movie','id'=>'502'])['body']['data']['availability']['playbackEligible']);
    $w['contexts']->context=new Context(str_repeat('d',32),true,'LK',true,30,str_repeat('3',32));
    sqlCheck('cross-user access grant refusal',!apiCall($w,['resource'=>'availability','content_type'=>'movie','id'=>'502'])['body']['data']['availability']['playbackEligible']);
    $w['contexts']->context=new Context(null,true,'LK',true,null,str_repeat('1',32));
    foreach($records as $r)if($r['metadata']['catalogue']['id']==='501'){$changed=$r;break;}
    foreach($changed['grants'] as &$g)$g['revoked']=true;unset($g);$changed['version']++;
    $update=$migrator->prepare('UPDATE content_rights_records SET version=?,document=? WHERE id=?');$update->execute([$changed['version'],json_encode($changed,JSON_THROW_ON_ERROR),$changed['id']]);
    sqlCheck('fresh SQL reads observe revocation without cache',apiIds(apiCall($w,['resource'=>'movies','q'=>'Alpha']))===[]);
    $w['clock']->time='2027-01-01T00:00:00Z';sqlCheck('expiry removes all public results',apiIds(apiCall($w,['q'=>'Same Title']))===[]);$w['clock']->time='2026-10-24T00:00:00Z';
    foreach(['INSERT INTO content_rights_records(id) VALUES (?)','UPDATE content_rights_records SET state=?','DELETE FROM content_rights_records WHERE id=?'] as $sql){
        try{$q=$reader->prepare($sql);$q->execute(['forbidden']);throw new RuntimeException();}catch(PDOException $e){sqlCheck('reader refuses '.strtok($sql,' '),true);}
    }
    // New request process after the deliberate 2027 expiry probe; do not rewind its limiter clock.
    $w=apiWorld($repo,$records);$samples=[];$bytes=[];
    for($i=0;$i<20;$i++){$start=hrtime(true);$r=apiCall($w,['resource'=>'movies','q'=>'Fictional Perf','limit'=>'20','sort'=>$i%2?'release_year_desc':'title_asc']);
        if($r['status']!==200||count(apiIds($r))!==20){fwrite(STDERR,json_encode(['stage'=>'performance','status'=>$r['status'],'code'=>$r['body']['error']['code']??'WRONG_COUNT','items'=>isset($r['body']['data']['items'])?count($r['body']['data']['items']):0])."\n");throw new RuntimeException();}$samples[]=(hrtime(true)-$start)/1e6;$bytes[]=strlen($r['raw']);}
    sort($samples);sqlCheck('bounded synthetic response and measured time',max($samples)<2000&&max($bytes)<=262144);
    $plan=PdoSearchRepository::plan(Query::parse(['q'=>'Fictional Perf']),null,200);$explain=$migrator->prepare('EXPLAIN '.$plan['sql']);
    foreach($plan['values'] as $i=>$v)$explain->bindValue($i+1,$v,is_int($v)?PDO::PARAM_INT:PDO::PARAM_STR);$explain->execute();$rows=$explain->fetchAll(PDO::FETCH_ASSOC);
    $detail=$migrator->prepare("EXPLAIN SELECT id FROM content_rights_records WHERE content_type=? AND catalogue_id=? AND state='published' LIMIT 1");$detail->execute(['movie','502']);$detailPlan=$detail->fetch(PDO::FETCH_ASSOC);
    sqlCheck('detail uses existing verified unique content reference',$detailPlan['key']==='unique_content_reference');
    echo json_encode(['passed'=>count($checks),'checks'=>$checks,'syntheticRows'=>count($records),
        'performance'=>['samples'=>count($samples),'pageSize'=>20,'p50Ms'=>round($samples[9],2),'p95Ms'=>round($samples[18],2),'maxMs'=>round(max($samples),2),'maxBytes'=>max($bytes)],
        'queryPlans'=>['search'=>array_map(static fn($r)=>['access'=>$r['type'],'key'=>$r['key'],'extra'=>$r['Extra']],$rows),'detailKey'=>$detailPlan['key']],
        'newIndexes'=>0,'realCatalogueIntegration'=>false],JSON_THROW_ON_ERROR);
}catch(Throwable $e){if(isset($migrator)&&$migrator->inTransaction())$migrator->rollBack();fwrite(STDERR,json_encode(['error'=>$e instanceof ApiFailure?$e->reason:'CATALOGUE_DATABASE_TEST_FAILED','passedBeforeFailure'=>count($checks),'valuesRedacted'=>true,'failureLine'=>$e->getLine(),'failureClass'=>get_class($e),'sampleCount'=>isset($samples)?count($samples):0,'responseStatus'=>isset($r['status'])?$r['status']:null,'responseCode'=>$r['body']['error']['code']??null,'responseItems'=>isset($r['body']['data']['items'])?count($r['body']['data']['items']):null,'performanceProbe'=>isset($samples)&&count($samples)===20?['p50Ms'=>round($samples[9],2),'p95Ms'=>round($samples[18],2),'maxMs'=>round(max($samples),2),'maxBytes'=>max($bytes)]:null]));exit(1);}

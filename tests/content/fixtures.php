<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/apps/web/src/Content/bootstrap.php';
use Zinema\Content\{Authorization,Catalogue,Clock,Repository,CatalogueService,ContentFailure,Validation};

const EDITOR='aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
const REVIEWER='bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';
const PUBLISHER='cccccccccccccccccccccccccccccccc';

final class FixtureClock implements Clock {
    public string $time='2026-10-24T00:00:00Z';
    public function now():DateTimeImmutable { return new DateTimeImmutable($this->time); }
}
final class FixtureAuthorization implements Authorization {
    public string $identity=EDITOR; public bool $enabled=true; public ?string $onlyId=null;
    public array $rules=[EDITOR=>['content.create','content.edit','content.view','content.audit','content.discovery'],
        REVIEWER=>['rights.verify','assets.review','content.review','content.view','content.audit'],
        PUBLISHER=>['content.publish','content.revoke','content.view','content.audit','content.discovery']];
    public function actor(string $permission,string $recordId):?string {
        return $this->enabled && ($this->onlyId===null || $this->onlyId===$recordId) &&
            in_array($permission,$this->rules[$this->identity]??[],true) ? $this->identity : null;
    }
}
final class FixtureCatalogue implements Catalogue {
    public bool $verified=true;
    public array $items=['movie:501'=>null,'movie:502'=>null,'series:601'=>null,
        'episode:701'=>['type'=>'series','id'=>'601'],'episode:702'=>['type'=>'series','id'=>'601'],
        'shot:801'=>['type'=>'movie','id'=>'501']];
    public function exists(string $type,string $id,?array $parent):bool {
        return $this->verified && array_key_exists($type.':'.$id,$this->items) && $this->items[$type.':'.$id]===$parent;
    }
}
final class MemoryRepository implements Repository {
    public array $records=[],$audit=[]; public int $reads=0;
    public function find(string $id):?array { $this->reads++; return $this->records[$id]??null; }
    public function create(array $record,array $event):void {
        foreach ($this->records as $existing) if ($record['metadata']['catalogue']!==null &&
            $existing['metadata']['type']===$record['metadata']['type'] &&
            ($existing['metadata']['catalogue']['id']??null)===$record['metadata']['catalogue']['id']) throw new ContentFailure('DUPLICATE_REFERENCE');
        $this->records[$record['id']]=$record; $this->audit[$record['id']][]=$event;
    }
    public function save(int $expectedVersion,array $record,array $event):void {
        if (($this->records[$record['id']]['version']??null)!==$expectedVersion) throw new ContentFailure('VERSION_CONFLICT');
        $this->records[$record['id']]=$record; $this->audit[$record['id']][]=$event;
    }
    public function scan(string $afterId,int $limit):array {
        $rows=$this->records; ksort($rows); return array_slice(array_values(array_filter($rows,static fn($r)=>strcmp($r['id'],$afterId)>0)),0,$limit);
    }
    public function events(string $id,int $afterVersion,int $limit):array {
        return array_slice(array_values(array_filter($this->audit[$id]??[],static fn($e)=>$e['record_version']>$afterVersion)),0,$limit);
    }
}
function metadata(string $type='movie',?string $id=null):array {
    $default=['movie'=>'501','series'=>'601','episode'=>'701','shot'=>'801']; $id=$id??$default[$type];
    $parent=$type==='episode'?['type'=>'series','id'=>'601']:($type==='shot'?['type'=>'movie','id'=>'501']:null);
    return ['type'=>$type,'title'=>'Fictional Lantern Journey','synopsis'=>'Synthetic testing metadata. No real distribution agreement.',
        'genres'=>['drama'],'language'=>'si','runtime_seconds'=>600,'tags'=>['synthetic'],'release_year'=>2026,
        'guidance'=>'Synthetic only','credits'=>[['role'=>'director','name'=>'Fictional Director']],
        'catalogue'=>['id'=>$id,'parent'=>$parent]];
}
function assetInput(string $role='film',string $suffix='1'):array {
    return ['role'=>$role,'mime'=>['metadata'=>'application/json','poster'=>'image/png','subtitle'=>'text/vtt'][$role]??'video/mp4',
        'duration_seconds'=>600,'sha256'=>str_repeat('a',64),'storage_ref'=>'asset:'.str_repeat($suffix,32),
        'provenance_ref'=>'provenance:'.str_repeat('2',32)];
}
function world(?Repository $repository=null):array {
    $repo=$repository??new MemoryRepository(); $auth=new FixtureAuthorization(); $catalogue=new FixtureCatalogue(); $clock=new FixtureClock();
    return ['repo'=>$repo,'auth'=>$auth,'catalogue'=>$catalogue,'clock'=>$clock,'service'=>new CatalogueService($repo,$auth,$catalogue,$clock)];
}
function command(array &$w,string $action,array $input=[],string $actor=EDITOR):array {
    $w['auth']->identity=$actor;
    return $w['record']=$w['service']->execute($w['record']['id'],$w['record']['version'],$action,$input);
}
function ready(string $role='film',string $type='movie',?Repository $repository=null,?string $catalogueId=null):array {
    $w=world($repository); $w['record']=$w['service']->create(metadata($type,$catalogueId));
    command($w,'add_holder',['holder_ref'=>'holder:'.str_repeat('3',32)]); $w['holder_id']=array_key_first($w['record']['holders']);
    command($w,'verify_holder',['holder_id'=>$w['holder_id'],'verification_ref'=>'evidence:'.str_repeat('4',32)],REVIEWER);
    command($w,'add_asset',assetInput($role)); $w['asset_id']=array_key_first($w['record']['assets']);
    command($w,'set_asset_available',['asset_id'=>$w['asset_id'],'available'=>true],REVIEWER);
    $w['grant_input']=['asset_id'=>$w['asset_id'],'holder_id'=>$w['holder_id'],'permission'=>Validation::PERMISSIONS[$role],
        'channel'=>'web','territories'=>['LK'],'starts_at'=>'2026-01-01T00:00:00Z','ends_at'=>'2027-01-01T00:00:00Z',
        'evidence_ref'=>'evidence:'.str_repeat('5',32)];
    command($w,'add_grant',$w['grant_input']); $w['grant_id']=array_key_first($w['record']['grants']);
    command($w,'verify_grant',['grant_id'=>$w['grant_id']],REVIEWER); return $w;
}
function publish(array &$w):void {
    command($w,'submit'); command($w,'approve',[],REVIEWER);
    command($w,'publish',['asset_ids'=>[$w['asset_id']],'territories'=>['LK'],'channel'=>'web'],PUBLISHER);
}
function check(bool $condition):void { if (!$condition) throw new RuntimeException('ASSERTION_FAILED'); }
function rejects(string $reason,callable $work):void {
    try { $work(); } catch (ContentFailure $error) {
        if ($error->reason!==$reason) throw new ContentFailure('EXPECTED_'.$reason.'_GOT_'.$error->reason);
        return;
    }
    throw new RuntimeException('EXPECTED_REJECTION');
}

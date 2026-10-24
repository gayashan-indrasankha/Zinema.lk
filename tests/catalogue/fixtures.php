<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/apps/web/src/Catalogue/bootstrap.php';
if (PHP_SAPI==='cli') require_once dirname(__DIR__).'/content/fixtures.php';
const API_EDITOR='aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
final class ApiClock implements \Zinema\Content\Clock {
    public string $time='2026-10-24T00:00:00Z';
    public function now():DateTimeImmutable {return new DateTimeImmutable($this->time);}
}
use Zinema\Catalogue\{Access,AccessRules,Context,ContextProvider,Controller,CursorCodec,EntitlementPolicy,FixedWindowLimiter,Grants,Query,SearchRepository,SearchService};

final class ApiContexts implements ContextProvider {
    public ?Context $context;
    public function __construct(?Context $context=null){$this->context=$context??new Context(null,true,'LK',true,null,str_repeat('1',32));}
    public function resolve():?Context{return $this->context;}
}
final class ApiRules implements AccessRules {
    public array $rules=[];
    public function rule(string $type,string $id,string $operation):?array{return $this->rules[$type.':'.$id.':'.$operation]??null;}
}
final class ApiGrants implements Grants {
    public array $grants=[];
    public function permits(string $subject,string $type,string $id,string $assetId,string $operation,string $territory,string $channel,DateTimeImmutable $now):bool {
        $end=$this->grants[$subject.':'.$type.':'.$id.':'.$assetId.':'.$operation.':'.$territory.':'.$channel]??null;
        return is_string($end)&&$now<new DateTimeImmutable($end);
    }
}
final class ApiCanonical implements \Zinema\Content\Catalogue {
    public array $items=[];public bool $available=true;
    public function exists(string $type,string $id,?array $parent):bool {
        return $this->available&&array_key_exists($type.':'.$id,$this->items)&&$this->items[$type.':'.$id]===$parent;
    }
}
function apiRecord(string $type,string $id,string $title):array {
    static $templates=[];
    if(!isset($templates[$type])){
        $w=ready('metadata',$type);
        $roles=match($type){'movie'=>['film','trailer'],'episode'=>['episode','trailer'],'shot'=>['shot'],default=>['trailer']};
        foreach($roles as $i=>$role){
            command($w,'add_asset',assetInput($role,(string)($i+6)));$assetId=array_key_last($w['record']['assets']);
            command($w,'set_asset_available',['asset_id'=>$assetId,'available'=>true],REVIEWER);
            $g=$w['grant_input'];$g['asset_id']=$assetId;$g['permission']=\Zinema\Content\Validation::PERMISSIONS[$role];
            command($w,'add_grant',$g);command($w,'verify_grant',['grant_id'=>array_key_last($w['record']['grants'])],REVIEWER);
        }
        command($w,'submit');command($w,'approve',[],REVIEWER);
        command($w,'publish',['asset_ids'=>array_keys($w['record']['assets']),'territories'=>['LK'],'channel'=>'web'],PUBLISHER);
        $templates[$type]=$w['record'];
    }
    $r=$templates[$type];$r['id']=substr(hash('sha256','fictional-record-'.$type.'-'.$id),0,32);
    $r['metadata']['title']=$title;$r['metadata']['catalogue']['id']=$id;return $r;
}
final class ApiMemoryRepository implements SearchRepository {
    public array $records=[];public bool $fail=false;public int $reads=0;
    public static function value(array $r,Query $q):string|int{return str_starts_with($q->filters['sort'],'title_')?$r['metadata']['title']:$r['metadata']['release_year'];}
    private static function compare(array $a,array $b,string $sort):int{
        $n=is_string($a['value'])?strcasecmp($a['value'],$b['value']):($a['value']<=>$b['value']);
        if(str_ends_with($sort,'_desc'))$n=-$n;
        return $n?:strcmp($a['type'],$b['type'])?:strcmp($a['id'],$b['id']);
    }
    public function candidates(Query $q,?array $position,int $budget):iterable {
        if($this->fail)throw new \Zinema\Catalogue\ApiFailure(503,'CATALOGUE_UNAVAILABLE');$this->reads++;
        $rows=[];$f=$q->filters;
        foreach($this->records as $r){$m=$r['metadata'];if($r['state']!=='published'||($f['content_type']!==null&&$f['content_type']!==$m['type'])||
            ($f['q']!==''&&!preg_match('/'.preg_quote($f['q'],'/').'/ui',$m['title']))||
            ($f['genre']!==null&&!in_array($f['genre'],$m['genres'],true))||($f['language']!==null&&$f['language']!==$m['language'])||
            ($f['release_year']!==null&&$f['release_year']!==$m['release_year'])||($f['max_runtime']!==null&&$m['runtime_seconds']>$f['max_runtime'])||
            ($f['parent_type']!==null&&$f['parent_type']!==($m['catalogue']['parent']['type']??null))||
            ($f['parent_id']!==null&&$f['parent_id']!==($m['catalogue']['parent']['id']??null)))continue;
            $p=['value'=>self::value($r,$q),'type'=>$m['type'],'id'=>$m['catalogue']['id']];
            if($position!==null&&self::compare($p,$position,$f['sort'])<=0)continue;$rows[]=['record'=>$r,'sort_value'=>$p['value'],'position'=>$p];
        }
        usort($rows,static fn($a,$b)=>self::compare($a['position'],$b['position'],$f['sort']));
        foreach(array_slice($rows,0,$budget+1) as $row)yield $row;
    }
    public function find(string $type,string $id):?array {
        if($this->fail)throw new \Zinema\Catalogue\ApiFailure(503,'CATALOGUE_UNAVAILABLE');$this->reads++;
        foreach($this->records as $r)if($r['state']==='published'&&$r['metadata']['type']===$type&&$r['metadata']['catalogue']['id']===$id)return $r;return null;
    }
}
function apiWorld(?SearchRepository $source=null,?array $records=null):array {
    $records=$records??[apiRecord('movie','501','Fictional Alpha'),apiRecord('movie','502','Fictional Beta'),
        apiRecord('movie','503','Private Fictional Gamma'),apiRecord('series','601','Fictional Series'),
        apiRecord('episode','701','Fictional Episode'),apiRecord('shot','801','Fictional Shot')];
    $repo=$source??new ApiMemoryRepository();if($repo instanceof ApiMemoryRepository)$repo->records=$records;
    $rules=new ApiRules();$canonical=new ApiCanonical();$grants=new ApiGrants();$clock=new ApiClock();$contexts=new ApiContexts();
    foreach($records as $r){$m=$r['metadata'];$key=$m['type'].':'.$m['catalogue']['id'];$canonical->items[$key]=$m['catalogue']['parent'];
        foreach(['metadata','preview','playback'] as $op)$rules->rules[$key.':'.$op]=['mode'=>'public','minimum_age'=>0];
    }
    $rules->rules['movie:502:playback']=['mode'=>'grant','minimum_age'=>0];$rules->rules['movie:503:metadata']=['mode'=>'grant','minimum_age'=>0];
    foreach($records as $r){$m=$r['metadata'];foreach($r['assets'] as $id=>$asset){
        foreach(['metadata','preview','playback'] as $op)$grants->grants[API_EDITOR.':'.$m['type'].':'.$m['catalogue']['id'].':'.$id.':'.$op.':LK:web']='2027-01-01T00:00:00Z';
    }}
    $access=new Access($canonical,new EntitlementPolicy($rules,$grants),$clock);$key=str_repeat('x',32);$codec=new CursorCodec($key);
    $service=new SearchService($repo,$access,$codec,$clock);$limiter=new FixedWindowLimiter(1000);
    return ['repo'=>$repo,'records'=>$records,'rules'=>$rules,'canonical'=>$canonical,'grants'=>$grants,'clock'=>$clock,'contexts'=>$contexts,
        'service'=>$service,'codec'=>$codec,'limiter'=>$limiter,'controller'=>new Controller($service,$contexts,$limiter,$clock)];
}
function apiCall(array $w,array $query=[],string $method='GET'):array {
    $response=$w['controller']->handle($method,$query);return ['status'=>$response->status,'body'=>json_decode($response->body,true,32,JSON_THROW_ON_ERROR),'headers'=>$response->headers,'raw'=>$response->body];
}
function apiIds(array $response):array{return array_column($response['body']['data']['items'],'id');}
function apiAssert(bool $condition):void{if(!$condition)throw new RuntimeException('API_ASSERTION_FAILED');}

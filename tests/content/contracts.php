<?php
declare(strict_types=1);
require __DIR__.'/fixtures.php';
use Zinema\Content\{CatalogueService,Validation,Policy,UnverifiedCatalogue};
require_once dirname(__DIR__,2).'/database/ContentMigrations.php';

$cases=[];
$cases['authorized draft is synthetic and not published']=function(){ $w=world(); $r=$w['service']->create(metadata()); check($r['state']==='draft' && $r['version']===1 && $r['data_class']==='synthetic'); };
foreach (['movie','series','episode','shot'] as $type) $cases['metadata supports verified '.$type]=function()use($type){$w=world();check($w['service']->create(metadata($type))['metadata']['type']===$type);};
foreach (['title'=>'','type'=>'collection','runtime_seconds'=>-1,'language'=>'xx','genres'=>['unreviewed'],'release_year'=>0,
    'guidance'=>'<script>bad</script>','synopsis'=>"invalid\0text",'tags'=>['same','same']] as $key=>$value) {
    $cases['metadata rejects invalid '.$key]=function()use($key,$value){$w=world();$m=metadata();$m[$key]=$value;try{$w['service']->create($m);}catch(\Zinema\Content\ContentFailure $e){check(true);return;}check(false);};
}
$cases['client cannot supply publication or verification status']=function(){ $w=world();$m=metadata();$m['state']='published';rejects('INVALID_FIELDS',fn()=>$w['service']->create($m)); };
$cases['unknown canonical item is not fabricated']=function(){ $w=world();rejects('CATALOGUE_UNVERIFIED',fn()=>$w['service']->create(metadata('movie','999'))); };
$cases['unverified default catalogue refuses association']=function(){ $w=world();$service=new CatalogueService($w['repo'],$w['auth']);rejects('CATALOGUE_UNVERIFIED',fn()=>$service->create(metadata())); };
$cases['episode requires parent']=function(){ $m=metadata('episode');$m['catalogue']['parent']=null;rejects('PARENT_REQUIRED',fn()=>Validation::metadata($m)); };
$cases['episode parent must match canonical lookup']=function(){ $w=world();$m=metadata('episode');$m['catalogue']['parent']['id']='999';rejects('CATALOGUE_UNVERIFIED',fn()=>$w['service']->create($m)); };
foreach (['0','-1','1 OR 1=1','01','9999999999999999999'] as $id) $cases['catalogue ID rejects '.$id]=function()use($id){$m=metadata();$m['catalogue']['id']=$id;rejects('INVALID_CATALOGUE_REFERENCE',fn()=>Validation::metadata($m));};
$cases['duplicate canonical association is rejected']=function(){ $w=world();$w['service']->create(metadata());rejects('DUPLICATE_REFERENCE',fn()=>$w['service']->create(metadata())); };
$cases['unassociated draft cannot submit']=function(){ $w=world();$m=metadata();$m['catalogue']=null;$w['record']=$w['service']->create($m);rejects('CATALOGUE_UNVERIFIED',function()use(&$w){command($w,'submit');}); };
$cases['default authorization denies create before persistence']=function(){ $w=world();$s=new CatalogueService($w['repo']);rejects('UNAUTHORIZED',fn()=>$s->create(metadata()));check($w['repo']->records===[]); };
$cases['default authorization denies read without existence leak']=function(){ $w=world();$s=new CatalogueService($w['repo']);rejects('UNAUTHORIZED',fn()=>$s->read(str_repeat('d',32)));check($w['repo']->reads===0); };
$cases['unauthorized update is rejected before lookup']=function(){ $w=world();$w['auth']->enabled=false;rejects('UNAUTHORIZED',fn()=>$w['service']->execute(str_repeat('d',32),1,'submit'));check($w['repo']->reads===0); };
$cases['record scoped authority prevents IDOR']=function(){ $w=world();$r=$w['service']->create(metadata());$w['auth']->onlyId=str_repeat('d',32);rejects('UNAUTHORIZED',fn()=>$w['service']->read($r['id'])); };
$cases['editor cannot approve']=function(){ $w=ready();command($w,'submit');rejects('UNAUTHORIZED',function()use(&$w){command($w,'approve');}); };
$cases['independent submission review is mandatory']=function(){ $w=ready();$w['auth']->rules[EDITOR][]='content.review';command($w,'submit');rejects('INDEPENDENT_REVIEW_REQUIRED',function()use(&$w){command($w,'approve');}); };
$cases['holder entry does not verify identity']=function(){ $w=world();$w['record']=$w['service']->create(metadata());command($w,'add_holder',['holder_ref'=>'holder:'.str_repeat('3',32)]);check(current($w['record']['holders'])['verified_by']===null); };
$cases['self verification is forbidden']=function(){ $w=ready();$w['auth']->rules[EDITOR][]='rights.verify';rejects('INDEPENDENT_REVIEW_REQUIRED',function()use(&$w){command($w,'verify_grant',['grant_id'=>$w['grant_id']]);}); };
$cases['valid workflow records independent approval']=function(){ $w=ready();publish($w);check($w['record']['state']==='published' && $w['record']['approved_by']===REVIEWER); };
$cases['draft cannot publish directly']=function(){ $w=ready();rejects('APPROVAL_REQUIRED',function()use(&$w){command($w,'publish',['asset_ids'=>[$w['asset_id']],'territories'=>['LK'],'channel'=>'web'],PUBLISHER);}); };
$cases['rejected content requires a new draft and review']=function(){ $w=ready();command($w,'submit');command($w,'reject',['reason'=>'rights_unverified'],REVIEWER);rejects('INVALID_TRANSITION',function()use(&$w){command($w,'approve',[],REVIEWER);}); };
$cases['metadata edit invalidates approval and asset availability']=function(){ $w=ready();command($w,'submit');command($w,'approve',[],REVIEWER);command($w,'edit_metadata',$w['record']['metadata']);check($w['record']['state']==='draft' && $w['record']['approved_by']===null && !$w['record']['assets'][$w['asset_id']]['available']); };
$cases['licenses cannot be moved onto a different title']=function(){ $w=ready();$m=$w['record']['metadata'];$m['catalogue']['id']='502';rejects('IMMUTABLE_CATALOGUE_REFERENCE',function()use(&$w,$m){command($w,'edit_metadata',$m);}); };
$cases['stale competing approval is rejected']=function(){ $w=ready();command($w,'submit');$v=$w['record']['version'];command($w,'approve',[],REVIEWER);rejects('VERSION_CONFLICT',fn()=>$w['service']->execute($w['record']['id'],$v,'reject',['reason'=>'request_changes'])); };
$cases['published asset requires active verified grant']=function(){ $w=ready();publish($w);check($w['service']->availability($w['record']['id'],$w['asset_id'],['territory'=>'LK','channel'=>'web'])['allowed']); };
foreach (['future'=>'2025-12-31T23:59:59Z','expiry'=>'2027-01-01T00:00:00Z','after_expiry'=>'2028-01-01T00:00:00Z'] as $name=>$time) {
    $cases['authorization denies '.$name.' immediately without cron']=function()use($time){$w=ready();publish($w);$w['clock']->time=$time;check(!$w['service']->availability($w['record']['id'],$w['asset_id'],['territory'=>'LK','channel'=>'web'])['allowed']);};
}
$cases['inclusive license start is eligible']=function(){ $w=ready();publish($w);$w['clock']->time='2026-01-01T00:00:00Z';check($w['service']->availability($w['record']['id'],$w['asset_id'],['territory'=>'LK','channel'=>'web'])['allowed']); };
$cases['expiry also prevents initial publication']=function(){ $w=ready();$w['clock']->time='2027-01-01T00:00:00Z';command($w,'submit');command($w,'approve',[],REVIEWER);rejects('PUBLICATION_RIGHTS_DENIED',function()use(&$w){command($w,'publish',['asset_ids'=>[$w['asset_id']],'territories'=>['LK'],'channel'=>'web'],PUBLISHER);}); };
$cases['territory mismatch denies availability']=function(){ $w=ready();publish($w);check(!$w['service']->availability($w['record']['id'],$w['asset_id'],['territory'=>'US','channel'=>'web'])['allowed']); };
$cases['channel mismatch denies availability']=function(){ $w=ready();publish($w);check(!$w['service']->availability($w['record']['id'],$w['asset_id'],['territory'=>'LK','channel'=>'messaging'])['allowed']); };
$cases['grant revocation immediately removes new authorization']=function(){ $w=ready();publish($w);command($w,'revoke_grant',['grant_id'=>$w['grant_id']],PUBLISHER);check($w['record']['state']==='revoked' && !$w['service']->availability($w['record']['id'],$w['asset_id'],['territory'=>'LK','channel'=>'web'])['allowed']); };
$cases['holder revocation denies all associated permissions']=function(){ $w=ready();publish($w);command($w,'revoke_holder',['holder_id'=>$w['holder_id']],PUBLISHER);check(!$w['service']->availability($w['record']['id'],$w['asset_id'],['territory'=>'LK','channel'=>'web'])['allowed']); };
$cases['revoked grant cannot be silently reverified']=function(){ $w=ready();command($w,'revoke_grant',['grant_id'=>$w['grant_id']],PUBLISHER);rejects('INDEPENDENT_REVIEW_REQUIRED',function()use(&$w){command($w,'verify_grant',['grant_id'=>$w['grant_id']],REVIEWER);}); };
$cases['unpublished content is unavailable']=function(){ $w=ready();publish($w);command($w,'unpublish',[],PUBLISHER);check(!$w['service']->availability($w['record']['id'],$w['asset_id'],['territory'=>'LK','channel'=>'web'])['allowed']); };
$cases['canonical removal denies availability']=function(){ $w=ready();publish($w);$w['catalogue']->verified=false;check($w['service']->availability($w['record']['id'],$w['asset_id'],['territory'=>'LK','channel'=>'web'])['reason']==='CATALOGUE_UNVERIFIED'); };
$cases['missing rights deny publication review']=function(){ $w=ready();$w['repo']->records[$w['record']['id']]['grants']=[];command($w,'submit');rejects('VERIFIED_GRANT_REQUIRED',function()use(&$w){command($w,'approve',[],REVIEWER);}); };
$cases['unverified rights are not active rights']=function(){ $w=ready();$r=$w['record'];$r['grants'][$w['grant_id']]['verified_by']=null;check(!Policy::rights($r,$w['asset_id'],['territory'=>'LK','channel'=>'web'],$w['clock']->now())['allowed']); };
$cases['unverified holder invalidates otherwise verified grant']=function(){ $w=ready();$r=$w['record'];$r['holders'][$w['holder_id']]['verified_by']=null;check(!Policy::rights($r,$w['asset_id'],['territory'=>'LK','channel'=>'web'],$w['clock']->now())['allowed']); };
$cases['malformed persisted dates deny rights instead of lexical acceptance']=function(){ $w=ready();$r=$w['record'];$r['grants'][$w['grant_id']]['starts_at']='0';$r['grants'][$w['grant_id']]['ends_at']='z';check(!Policy::rights($r,$w['asset_id'],['territory'=>'LK','channel'=>'web'],$w['clock']->now())['allowed']); };
$cases['missing private verification reference denies rights']=function(){ $w=ready();$r=$w['record'];$r['holders'][$w['holder_id']]['verification_ref']=null;check(!Policy::rights($r,$w['asset_id'],['territory'=>'LK','channel'=>'web'],$w['clock']->now())['allowed']); };
$cases['unknown persisted asset role cannot match a null permission']=function(){ $w=ready();$r=$w['record'];$r['assets'][$w['asset_id']]['role']='unknown';$r['grants'][$w['grant_id']]['permission']=null;check(!Policy::rights($r,$w['asset_id'],['territory'=>'LK','channel'=>'web'],$w['clock']->now())['allowed']); };
$cases['missing grant author cannot satisfy independent review']=function(){ $w=ready();$r=$w['record'];unset($r['grants'][$w['grant_id']]['entered_by']);check(!Policy::rights($r,$w['asset_id'],['territory'=>'LK','channel'=>'web'],$w['clock']->now())['allowed']); };
foreach (['poster','trailer','shot','subtitle','metadata'] as $role) $cases[$role.' permission never grants full film rights']=function()use($role){$type=$role==='shot'?'shot':'movie';$w=ready($role,$type);$r=$w['record'];$other=str_repeat('d',32);$r['assets'][$other]=Validation::asset(assetInput($type==='shot'?'shot':'film','6'),$type);$r['assets'][$other]['available']=true;check(!Policy::rights($r,$other,['territory'=>'LK','channel'=>'web'],$w['clock']->now())['allowed']);};
$cases['each episode needs its own grant']=function(){ $one=ready('episode','episode');publish($one);$two=ready('episode','episode',null,'702');$r=$two['record'];$r['state']='published';$r['publication']=['asset_ids'=>[$two['asset_id']],'territories'=>['LK'],'channel'=>'web'];$r['holders']=$one['record']['holders'];$r['grants']=$one['record']['grants'];check(Policy::available($one['record'],$one['asset_id'],['territory'=>'LK','channel'=>'web'],$one['clock']->now())['allowed'] && !Policy::available($r,$two['asset_id'],['territory'=>'LK','channel'=>'web'],$two['clock']->now())['allowed']); };
$cases['entitlement and delivery remain denied even when rights pass']=function(){ $w=ready();publish($w);check(!$w['service']->canStream()['allowed']); };
$cases['metadata discovery exposes only reviewed DTO fields']=function(){ $w=ready('metadata');publish($w);$w['auth']->identity=EDITOR;$items=$w['service']->eligibleMetadata(['territory'=>'LK','channel'=>'web'])['items'];check(count($items)===1 && array_keys($items[0])===['record_id','catalogue_id','type','title','language','playable'] && $items[0]['playable']===false); };
$cases['poster-only publication does not imply metadata discovery rights']=function(){ $w=ready('poster');publish($w);$w['auth']->identity=EDITOR;check($w['service']->eligibleMetadata(['territory'=>'LK','channel'=>'web'])['items']===[]); };
$cases['unpublished metadata is absent from discovery']=function(){ $w=ready('metadata');$w['auth']->identity=EDITOR;check($w['service']->eligibleMetadata(['territory'=>'LK','channel'=>'web'])['items']===[]); };
$cases['expired metadata is absent from discovery']=function(){ $w=ready('metadata');publish($w);$w['clock']->time='2027-01-01T00:00:00Z';$w['auth']->identity=EDITOR;check($w['service']->eligibleMetadata(['territory'=>'LK','channel'=>'web'])['items']===[]); };
$cases['query pagination rejects an unbounded limit']=function(){ $w=world();rejects('INVALID_INTEGER',fn()=>$w['service']->eligibleMetadata(['territory'=>'LK','channel'=>'web'],'',101)); };
foreach (['https://example.invalid/private.mp4','file:///etc/passwd','../media/film.mp4','asset:../secret','http://127.0.0.1/admin'] as $ref) $cases['opaque asset reference rejects '.substr(hash('sha256',$ref),0,8)]=function()use($ref){$input=assetInput();$input['storage_ref']=$ref;rejects('UNSAFE_REFERENCE',fn()=>Validation::asset($input,'movie'));};
$cases['rights agreement URL is not accepted as private evidence']=function(){ $w=ready();$g=$w['grant_input'];$g['evidence_ref']='https://example.invalid/agreement.pdf';rejects('UNSAFE_REFERENCE',fn()=>Validation::grant($g)); };
$cases['permission must match exact asset role']=function(){ $w=ready('poster');$g=$w['grant_input'];$g['permission']='stream';rejects('PERMISSION_ASSET_MISMATCH',function()use(&$w,$g){command($w,'add_grant',$g);}); };
$cases['unknown asset cannot receive a grant']=function(){ $w=ready();$g=$w['grant_input'];$g['asset_id']=str_repeat('d',32);rejects('ASSET_NOT_FOUND',function()use(&$w,$g){command($w,'add_grant',$g);}); };
$cases['repeated grant submission is rejected']=function(){ $w=ready();rejects('DUPLICATE_GRANT',function()use(&$w){command($w,'add_grant',$w['grant_input']);}); };
foreach (['2026-02-30T00:00:00Z','2026-10-24T00:00:00+05:30','tomorrow'] as $time) $cases['UTC dates reject '.substr(hash('sha256',$time),0,8)]=fn()=>rejects('INVALID_UTC_TIME',fn()=>Validation::utc($time));
$cases['license end must be later than start']=function(){ $w=ready();$g=$w['grant_input'];$g['ends_at']=$g['starts_at'];rejects('INVALID_LICENSE_WINDOW',fn()=>Validation::grant($g)); };
$cases['unknown territory code is rejected']=fn()=>rejects('UNSUPPORTED_VALUE',fn()=>Policy::context(['territory'=>'ZZ','channel'=>'web']));
$cases['metadata text keeps SQL punctuation as inert data']=function(){ $w=world();$m=metadata();$m['title']="Fictional ' ; SELECT testing";check($w['service']->create($m)['metadata']['title']===$m['title']); };
$cases['audit records actor action target version without documents']=function(){ $w=ready();$w['auth']->identity=EDITOR;$events=$w['service']->audit($w['record']['id']);check(count($events)===$w['record']['version']);foreach($events as $i=>$e){check($e['record_version']===$i+1 && array_keys($e)===['id','record_id','record_version','actor_id','action','subject_id','state','occurred_at']);} };
$cases['audit pagination is bounded and ordered']=function(){ $w=ready();$w['auth']->identity=EDITOR;$events=$w['service']->audit($w['record']['id'],2,2);check(count($events)===2 && $events[0]['record_version']===3 && $events[1]['record_version']===4); };
$cases['new migration profile keeps original subset and adds two workflow tables']=function(){ $m=\Zinema\Content\ContentMigrations::load(dirname(__DIR__,2));check(array_column($m,'table')===['site_settings','rate_limits','movie_parts','episode_parts','content_rights_records','content_rights_events']); };
$cases['original migration loader remains restricted to its four tables']=function(){ $m=\Zinema\Database\Runner::load(dirname(__DIR__,2).'/database');check(count($m)===4); };
$cases['migration parser preserves VARCHAR twenty while normalizing integer display width']=function(){ $r=\Zinema\Database\Runner::definition(file_get_contents(dirname(__DIR__,2).'/database/content-migrations/0005_content_rights_records.sql'));check($r['columns']['state']['type']==='varchar(20)' && $r['columns']['version']['type']==='int'); };

try {
    $name=$argv[1]??'';
    if ($name==='list') { echo json_encode(array_keys($cases),JSON_THROW_ON_ERROR); exit; }
    if (!isset($cases[$name])) throw new RuntimeException('UNKNOWN_CASE');
    $cases[$name](); echo json_encode(['passed'=>true,'synthetic'=>true],JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
    $reason=$error instanceof \Zinema\Content\ContentFailure ? $error->reason : 'TEST_ASSERTION_FAILED';
    fwrite(STDERR,json_encode(['error'=>$reason,'valuesRedacted'=>true])); exit(1);
}

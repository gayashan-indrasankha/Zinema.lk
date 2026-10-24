<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/apps/web/src/Playback/bootstrap.php';
require_once __DIR__.'/../catalogue/fixtures.php';
use Zinema\Catalogue\{Access,Context,EntitlementPolicy,FixedWindowLimiter};
use Zinema\Playback\{Controller,LocalFixtureProvider,Service,SessionCodec,SourceProvider,UnavailableProvider};
function playbackWorld(?SourceProvider $provider=null,?array $state=null):array{
    $records=$state['records']??null;$w=apiWorld(null,$records);$w['clock']->time=gmdate('Y-m-d\TH:i:s\Z',time()+(int)($state['offset']??0));
    if(isset($state['identity']))$w['contexts']->context=new Context($state['identity'],true,'LK',true,30,str_repeat('2',32));
    if(isset($state['territory']))$w['contexts']->context=new Context(null,true,$state['territory'],true,null,str_repeat('1',32));
    $entitlements=new EntitlementPolicy($w['rules'],$w['grants']);$access=new Access($w['canonical'],$entitlements,$w['clock']);$codec=new SessionCodec($state['sessionKey']??str_repeat('k',32));
    $w['playback']=new Service($w['repo'],$access,$entitlements,$w['clock'],$provider??new UnavailableProvider(),$codec);$w['codec']=$codec;
    $w['playbackController']=new Controller($w['playback'],$w['contexts'],new FixedWindowLimiter(200),$w['clock'],$state['origin']??'http://127.0.0.1:19017');return $w;
}
function playbackRecords(string $digest):array{
    $records=apiWorld()['records'];
    foreach(['movie'=>'501','episode'=>'701'] as $type=>$id){
        $w=ready('metadata',$type);$metadata=$w['record']['metadata'];$metadata['runtime_seconds']=12;command($w,'edit_metadata',$metadata);foreach(array_keys($w['record']['assets']) as $existingAsset)command($w,'set_asset_available',['asset_id'=>$existingAsset,'available'=>true],REVIEWER);$input=assetInput($type==='movie'?'film':'episode','6');$input['sha256']=$digest;$input['duration_seconds']=12;
        command($w,'add_asset',$input);$asset=array_key_last($w['record']['assets']);command($w,'set_asset_available',['asset_id'=>$asset,'available'=>true],REVIEWER);
        $grant=$w['grant_input'];$grant['asset_id']=$asset;$grant['permission']='stream';command($w,'add_grant',$grant);command($w,'verify_grant',['grant_id'=>array_key_last($w['record']['grants'])],REVIEWER);
        command($w,'submit');command($w,'approve',[],REVIEWER);command($w,'publish',['asset_ids'=>array_keys($w['record']['assets']),'territories'=>['LK'],'channel'=>'web'],PUBLISHER);
        foreach($records as &$r)if($r['metadata']['type']===$type&&$r['metadata']['catalogue']['id']===$id)$r=$w['record'];unset($r);
    }
    return $records;
}
function playbackResponse(array $w,string $route,array $input=[],string $method='GET',string $cookie='',array $headers=[]):array{
    $post=$route==='session';$r=$w['playbackController']->handle($route,$method,$post?[]:$input,$post?json_encode($input,JSON_THROW_ON_ERROR):'', $headers,$cookie);
    return ['status'=>$r->status,'body'=>str_starts_with($r->headers['Content-Type'],'application/json')?json_decode($r->body,true):null,'raw'=>$r->body,'headers'=>$r->headers];
}
function sessionHeaders():array{return ['origin'=>'http://127.0.0.1:19017','x-zinema-playback'=>'1','content-type'=>'application/json'];}
function sessionCookie(array $response):string{return explode(';',substr($response['headers']['Set-Cookie'],strlen('zinema_playback=')))[0];}

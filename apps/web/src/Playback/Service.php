<?php
declare(strict_types=1);
namespace Zinema\Playback;
use Zinema\Catalogue\{Access,ApiFailure,Context,EntitlementPolicy,Query,SearchRepository};
use Zinema\Content\{Clock,Policy};

final class Service {
    public function __construct(private SearchRepository $records,private Access $access,private EntitlementPolicy $entitlements,private Clock $clock,private SourceProvider $provider,private SessionCodec $sessions){}
    public static function reference(array $input):array{
        if(count($input)!==2||array_diff(array_keys($input),['type','id'])||!is_string($input['type'])||!in_array($input['type'],['movie','series','episode','shot'],true)||!is_string($input['id']))throw new ApiFailure(400,'INVALID_REQUEST');
        return [$input['type'],Query::id($input['id'])];
    }
    private function record(string $type,string $id,Context $context):array{
        $r=$this->records->find($type,$id);
        if($r===null||!$this->access->evaluate($r,$context)['discoverable'])throw new ApiFailure(404,'CONTENT_UNAVAILABLE');
        $parent=$r['metadata']['catalogue']['parent'];if($parent!==null){$p=$this->records->find($parent['type'],$parent['id']);if($p===null||!$this->access->evaluate($p,$context)['discoverable'])throw new ApiFailure(404,'CONTENT_UNAVAILABLE');}
        return $r;
    }
    private function asset(array $r,Context $context,?string $only=null):array{
        $now=$this->clock->now();$eligible=false;
        foreach($r['assets'] as $id=>$asset){
            if(($only!==null&&$id!==$only)||!in_array($asset['role'],['film','episode'],true)||!Policy::available($r,$id,['territory'=>$context->territory,'channel'=>'web'],$now)['allowed']||!$this->entitlements->permits($context,$r,$id,'playback',$now))continue;
            $eligible=true;$digest=$this->provider->digest();if($digest!==null&&hash_equals($digest,$asset['sha256']))return [$id,$digest];
        }
        throw new ApiFailure($eligible?503:404,$eligible?'PLAYBACK_UNAVAILABLE':'CONTENT_UNAVAILABLE');
    }
    public function link(string $shotId,Context $context):array{
        Query::id($shotId);$r=$this->record('shot',$shotId,$context);
        if(!$this->access->evaluate($r,$context)['previewEligible'])throw new ApiFailure(404,'CONTENT_UNAVAILABLE');
        $p=$r['metadata']['catalogue']['parent'];if($p===null||!in_array($p['type'],['movie','series'],true))throw new ApiFailure(404,'CONTENT_UNAVAILABLE');
        return ['href'=>($p['type']==='movie'?'movie-details.php':'series-details.php').'?catalogue=1&id='.rawurlencode($p['id'])];
    }
    public function details(array $ref,Context $context):array{
        [$type,$id]=self::reference($ref);$r=$this->record($type,$id,$context);$m=$r['metadata'];$watch=false;
        if(in_array($type,['movie','episode'],true)){try{$this->asset($r,$context);$watch=true;}catch(ApiFailure $e){if(!in_array($e->reason,['CONTENT_UNAVAILABLE','PLAYBACK_UNAVAILABLE'],true))throw $e;}}
        return ['id'=>$id,'contentType'=>$type,'title'=>$m['title'],'synopsis'=>$m['synopsis'],'genre'=>$m['genres'],'language'=>$m['language'],'releaseYear'=>$m['release_year'],'runtimeSeconds'=>$m['runtime_seconds'],'watchable'=>$watch,'playbackState'=>$watch?'local_fixture_ready':'unavailable'];
    }
    public function issue(array $ref,Context $context):array{
        [$type,$id]=self::reference($ref);if(!in_array($type,['movie','episode'],true))throw new ApiFailure(404,'CONTENT_UNAVAILABLE');
        $r=$this->record($type,$id,$context);[$asset,$digest]=$this->asset($r,$context);$now=$this->clock->now()->getTimestamp();$until=$now+60;
        foreach($r['grants'] as $g)if($g['asset_id']===$asset&&$g['permission']==='stream'&&$g['revoked']===false){$end=(new \DateTimeImmutable($g['ends_at']))->getTimestamp();if($end>$now)$until=min($until,$end);}
        $claims=['type'=>$type,'id'=>$id,'asset'=>$asset,'digest'=>$digest,'version'=>$r['version'],'binding'=>$context->fingerprint(),'issued'=>$now,'until'=>$until];
        return ['cookie'=>$this->sessions->issue($claims),'descriptor'=>['status'=>'ready','progressScope'=>$this->sessions->progressScope($context->fingerprint()),'assetVersion'=>$r['version'],'mode'=>'local_fixture','manifest'=>'/api/playback/media.php?file=master.m3u8','expiresAt'=>$until,'captions'=>'/api/playback/media.php?file=captions.vtt']];
    }
    public function media(string $cookie,string $name,Context $context):array{
        if(!preg_match('/^[a-z][a-z0-9_-]{0,24}\.(m3u8|ts|vtt)$/D',$name))throw new ApiFailure(404,'CONTENT_UNAVAILABLE');
        $c=$this->sessions->read($cookie,$context->fingerprint(),$this->clock->now()->getTimestamp());
        [$type,$id]=self::reference(['type'=>$c['type'],'id'=>$c['id']]);$r=$this->record($type,$id,$context);
        if($r['version']!==$c['version'])throw new ApiFailure(404,'CONTENT_UNAVAILABLE');
        [$asset,$digest]=$this->asset($r,$context,$c['asset']);if(!hash_equals($digest,$c['digest']))throw new ApiFailure(404,'CONTENT_UNAVAILABLE');
        return $this->provider->resource($name);
    }
}

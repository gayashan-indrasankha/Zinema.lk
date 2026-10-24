<?php
declare(strict_types=1);
namespace Zinema\Catalogue;
use Zinema\Content\{Catalogue,Clock,Policy,Validation};

final class EntitlementPolicy {
    public function __construct(private AccessRules $rules=new MissingRules(),private Grants $grants=new MissingGrants()) {}
    public function permits(Context $context,array $record,string $assetId,string $operation,\DateTimeImmutable $now):bool {
        if(!in_array($operation,['metadata','preview','playback'],true)||!$context->territoryVerified||
            !in_array($context->territory,Validation::TERRITORIES,true))return false;
        if($context->subject!==null&&(!preg_match('/^[a-f0-9]{32}$/D',$context->subject)||!$context->active))return false;
        $m=$record['metadata'];$rule=$this->rules->rule($m['type'],$m['catalogue']['id'],$operation);
        if(!is_array($rule)||array_keys($rule)!==['mode','minimum_age']||
            !in_array($rule['mode'],['public','authenticated','grant'],true)||!is_int($rule['minimum_age'])||
            $rule['minimum_age']<0||$rule['minimum_age']>120)return false;
        if($rule['minimum_age']>0&&($context->verifiedAge===null||$context->verifiedAge<$rule['minimum_age']))return false;
        if($rule['mode']==='public')return true;
        if($context->subject===null||!$context->active)return false;
        return $rule['mode']==='authenticated'||$this->grants->permits($context->subject,$m['type'],$m['catalogue']['id'],$assetId,$operation,$context->territory,'web',$now);
    }
}
final class Access {
    public function __construct(private Catalogue $canonical,private EntitlementPolicy $entitlements,private Clock $clock) {}
    public function evaluate(array $record,Context $context):array {
        $result=['discoverable'=>false,'metadataAvailable'=>false,'previewEligible'=>false,'previewAvailable'=>false,
            'playbackEligible'=>false,'playbackAvailable'=>false,'deliveryState'=>'not_configured'];
        $m=$record['metadata'];$ref=$m['catalogue'];
        if($ref===null||!$context->territoryVerified||!in_array($context->territory,Validation::TERRITORIES,true)||
            !$this->canonical->exists($m['type'],$ref['id'],$ref['parent']))return $result;
        $scope=['territory'=>$context->territory,'channel'=>'web'];$now=$this->clock->now();
        foreach($record['assets'] as $id=>$asset){
            $operation=match($asset['role']){'metadata'=>'metadata','shot','trailer'=>'preview','film','episode'=>'playback',default=>null};
            if($operation===null||!Policy::available($record,$id,$scope,$now)['allowed']||
                !$this->entitlements->permits($context,$record,$id,$operation,$now))continue;
            if($operation==='metadata'){$result['discoverable']=true;$result['metadataAvailable']=true;}
            elseif($operation==='preview')$result['previewEligible']=true;
            else $result['playbackEligible']=true;
        }
        if(!$result['discoverable']){$result['previewEligible']=false;$result['playbackEligible']=false;}
        return $result;
    }
}

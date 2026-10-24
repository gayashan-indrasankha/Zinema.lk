<?php
declare(strict_types=1);
namespace Zinema\Content;

final class CatalogueService {
    public function __construct(private Repository $repository, private Authorization $authorization = new DenyAuthorization(),
        private Catalogue $catalogue = new UnverifiedCatalogue(), private Clock $clock = new UtcClock()) {}

    private function actor(string $permission,string $id): string {
        $actor=$this->authorization->actor($permission,$id);
        Validation::require($actor!==null,'UNAUTHORIZED'); return Validation::id($actor);
    }
    private function record(string $id): array {
        Validation::id($id); $record=$this->repository->find($id);
        Validation::require($record!==null,'NOT_FOUND'); return $record;
    }
    private function canonical(array $metadata): void {
        $ref=$metadata['catalogue'];
        Validation::require($ref!==null && $this->catalogue->exists($metadata['type'],$ref['id'],$ref['parent']),'CATALOGUE_UNVERIFIED');
    }
    private function event(array $record,string $actor,string $action,string $subject):array {
        return ['id'=>bin2hex(random_bytes(16)),'record_id'=>$record['id'],'record_version'=>$record['version'],
            'actor_id'=>$actor,'action'=>$action,'subject_id'=>$subject,'state'=>$record['state'],
            'occurred_at'=>$this->clock->now()->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z')];
    }
    private function draft(array &$record):void {
        $record['state']='draft'; $record['submitted_by']=null; $record['approved_by']=null;
        $record['approval_version']=null; $record['publication']=null;
    }
    public function create(array $metadata):array {
        $id=bin2hex(random_bytes(16)); $actor=$this->actor('content.create',$id);
        $metadata=Validation::metadata($metadata);
        if ($metadata['catalogue']!==null) $this->canonical($metadata);
        $record=['id'=>$id,'version'=>1,'data_class'=>'synthetic','state'=>'draft','metadata'=>$metadata,
            'created_by'=>$actor,'submitted_by'=>null,'approved_by'=>null,'approval_version'=>null,'publication'=>null,
            'holders'=>[],'assets'=>[],'grants'=>[]];
        $this->repository->create($record,$this->event($record,$actor,'create',$id)); return $record;
    }
    public function read(string $id):array {
        Validation::id($id); $this->actor('content.view',$id); return $this->record($id);
    }
    /** Internal command boundary only: no request routing, sessions, uploads or live credentials. */
    public function execute(string $id,int $expectedVersion,string $action,array $input=[]):array {
        Validation::id($id); Validation::integer($expectedVersion,1,2147483646);
        $permissions=['edit_metadata'=>'content.edit','add_holder'=>'content.edit','verify_holder'=>'rights.verify',
            'revoke_holder'=>'content.revoke','add_asset'=>'content.edit','set_asset_available'=>'assets.review',
            'add_grant'=>'content.edit','verify_grant'=>'rights.verify','revoke_grant'=>'content.revoke',
            'submit'=>'content.edit','approve'=>'content.review','reject'=>'content.review','publish'=>'content.publish','unpublish'=>'content.publish'];
        Validation::require(isset($permissions[$action]),'UNKNOWN_ACTION'); $actor=$this->actor($permissions[$action],$id);
        $record=$this->record($id); Validation::require($record['version']===$expectedVersion,'VERSION_CONFLICT');
        $subject=$id;
        switch ($action) {
            case 'edit_metadata':
                $metadata=Validation::metadata($input);
                Validation::require($metadata['type']===$record['metadata']['type'],'IMMUTABLE_CONTENT_TYPE');
                if ($metadata['catalogue']!==$record['metadata']['catalogue']) {
                    Validation::require($record['metadata']['catalogue']===null && !$record['assets'] && !$record['grants'],'IMMUTABLE_CATALOGUE_REFERENCE');
                }
                if ($metadata['catalogue']!==null) $this->canonical($metadata);
                $record['metadata']=$metadata;
                foreach ($record['assets'] as &$asset) $asset['available']=false; unset($asset);
                $this->draft($record); break;
            case 'add_holder':
                Validation::keys($input,['holder_ref']); Validation::require(count($record['holders'])<8,'HOLDER_LIMIT');
                $ref=Validation::reference($input['holder_ref'],'holder');
                foreach ($record['holders'] as $holder) Validation::require($holder['holder_ref']!==$ref,'DUPLICATE_HOLDER');
                $subject=bin2hex(random_bytes(16)); $record['holders'][$subject]=['holder_ref'=>$ref,'entered_by'=>$actor,
                    'verified_by'=>null,'verification_ref'=>null,'revoked'=>false]; $this->draft($record); break;
            case 'verify_holder':
                Validation::keys($input,['holder_id','verification_ref']); $subject=Validation::id($input['holder_id']);
                Validation::require(isset($record['holders'][$subject]),'HOLDER_NOT_FOUND');
                $holder=&$record['holders'][$subject];
                Validation::require(!$holder['revoked'] && $holder['entered_by']!==$actor,'INDEPENDENT_REVIEW_REQUIRED');
                $holder['verification_ref']=Validation::reference($input['verification_ref'],'evidence'); $holder['verified_by']=$actor;
                unset($holder); $this->draft($record); break;
            case 'revoke_holder':
                Validation::keys($input,['holder_id']); $subject=Validation::id($input['holder_id']);
                Validation::require(isset($record['holders'][$subject]),'HOLDER_NOT_FOUND');
                $record['holders'][$subject]['revoked']=true; $record['state']='revoked'; $record['publication']=null; break;
            case 'add_asset':
                Validation::require(count($record['assets'])<16,'ASSET_LIMIT');
                $asset=Validation::asset($input,$record['metadata']['type']);
                foreach ($record['assets'] as $existing) Validation::require($existing['storage_ref']!==$asset['storage_ref'],'DUPLICATE_ASSET');
                $subject=bin2hex(random_bytes(16)); $record['assets'][$subject]=$asset; $this->draft($record); break;
            case 'set_asset_available':
                Validation::keys($input,['asset_id','available']); $subject=Validation::id($input['asset_id']);
                Validation::require(isset($record['assets'][$subject]),'ASSET_NOT_FOUND'); Validation::require(is_bool($input['available']),'INVALID_AVAILABILITY');
                $record['assets'][$subject]['available']=$input['available']; $this->draft($record); break;
            case 'add_grant':
                Validation::require(count($record['grants'])<64,'GRANT_LIMIT'); $grant=Validation::grant($input);
                Validation::require(isset($record['assets'][$grant['asset_id']]),'ASSET_NOT_FOUND');
                Validation::require(isset($record['holders'][$grant['holder_id']]),'HOLDER_NOT_FOUND');
                Validation::require(Validation::PERMISSIONS[$record['assets'][$grant['asset_id']]['role']]===$grant['permission'],'PERMISSION_ASSET_MISMATCH');
                foreach ($record['grants'] as $existing) {
                    $keys=['asset_id','holder_id','permission','channel','territories','starts_at','ends_at','evidence_ref'];
                    Validation::require(array_intersect_key($existing,array_flip($keys))!==array_intersect_key($grant,array_flip($keys)),'DUPLICATE_GRANT');
                }
                $subject=bin2hex(random_bytes(16)); $grant['entered_by']=$actor; $record['grants'][$subject]=$grant; $this->draft($record); break;
            case 'verify_grant':
                Validation::keys($input,['grant_id']); $subject=Validation::id($input['grant_id']);
                Validation::require(isset($record['grants'][$subject]),'GRANT_NOT_FOUND'); $grant=&$record['grants'][$subject];
                $holder=$record['holders'][$grant['holder_id']];
                Validation::require(!$grant['revoked'] && $grant['entered_by']!==$actor,'INDEPENDENT_REVIEW_REQUIRED');
                Validation::require($holder['verified_by']!==null && !$holder['revoked'],'HOLDER_UNVERIFIED');
                $grant['verified_by']=$actor; unset($grant); $this->draft($record); break;
            case 'revoke_grant':
                Validation::keys($input,['grant_id']); $subject=Validation::id($input['grant_id']);
                Validation::require(isset($record['grants'][$subject]),'GRANT_NOT_FOUND');
                $record['grants'][$subject]['revoked']=true; $record['state']='revoked'; $record['publication']=null; break;
            case 'submit':
                Validation::keys($input,[]); Validation::require($record['state']==='draft','INVALID_TRANSITION');
                $this->canonical($record['metadata']); Validation::require(count($record['assets'])>0,'ASSET_REQUIRED');
                $record['state']='pending_review'; $record['submitted_by']=$actor; break;
            case 'approve':
                Validation::keys($input,[]); Validation::require($record['state']==='pending_review','INVALID_TRANSITION');
                Validation::require($record['submitted_by']!==$actor,'INDEPENDENT_REVIEW_REQUIRED'); $this->canonical($record['metadata']);
                $verified=array_filter($record['grants'],static fn($g)=>$g['verified_by']!==null && !$g['revoked']);
                Validation::require(count($verified)>0,'VERIFIED_GRANT_REQUIRED');
                $record['state']='approved'; $record['approved_by']=$actor; $record['approval_version']=$expectedVersion+1; break;
            case 'reject':
                Validation::keys($input,['reason']); Validation::one($input['reason'],['rights_unverified','metadata_invalid','request_changes']);
                Validation::require($record['state']==='pending_review','INVALID_TRANSITION');
                Validation::require($record['submitted_by']!==$actor,'INDEPENDENT_REVIEW_REQUIRED');
                $record['state']='rejected'; $record['approved_by']=null; $record['approval_version']=null; break;
            case 'publish':
                Validation::keys($input,['asset_ids','territories','channel']);
                Validation::require($record['state']==='approved' && $record['approval_version']===$expectedVersion && $record['approved_by']!==null,'APPROVAL_REQUIRED');
                $this->canonical($record['metadata']);
                $publication=['asset_ids'=>Validation::strings($input['asset_ids'],16,[Validation::class,'id']),
                    'territories'=>Validation::strings($input['territories'],6,static fn($t)=>Validation::one($t,Validation::TERRITORIES)),
                    'channel'=>Validation::one($input['channel'],['web'])];
                foreach ($publication['asset_ids'] as $assetId) foreach ($publication['territories'] as $territory) {
                    $policy=Policy::rights($record,$assetId,['territory'=>$territory,'channel'=>'web'],$this->clock->now());
                    Validation::require($policy['allowed'],'PUBLICATION_RIGHTS_DENIED');
                }
                $record['publication']=$publication; $record['state']='published'; break;
            case 'unpublish':
                Validation::keys($input,[]); Validation::require(in_array($record['state'],['published','approved'],true),'INVALID_TRANSITION');
                $record['state']='unpublished'; $record['publication']=null; $record['approved_by']=null; $record['approval_version']=null; break;
        }
        $record['version']=$expectedVersion+1;
        $this->repository->save($expectedVersion,$record,$this->event($record,$actor,$action,$subject)); return $record;
    }
    public function availability(string $id,string $assetId,array $context):array {
        $record=$this->read($id); Validation::id($assetId); $context=Policy::context($context);
        if (!$record['metadata']['catalogue'] || !$this->catalogue->exists($record['metadata']['type'],$record['metadata']['catalogue']['id'],$record['metadata']['catalogue']['parent'])) {
            return ['allowed'=>false,'reason'=>'CATALOGUE_UNVERIFIED'];
        }
        return Policy::available($record,$assetId,$context,$this->clock->now());
    }
    public function canStream():array { return Policy::canStream(); }
    /** Bounded internal discovery DTO. Entitlement and real public search adapters remain absent. */
    public function eligibleMetadata(array $context,string $afterId='',int $limit=20):array {
        $this->actor('content.discovery',str_repeat('0',32)); $context=Policy::context($context);
        if ($afterId!=='') Validation::id($afterId); Validation::integer($limit,1,100);
        $records=$this->repository->scan($afterId,$limit); $items=[];
        foreach ($records as $record) {
            foreach ($record['assets'] as $assetId=>$asset) {
                if ($asset['role']!=='metadata') continue;
                if ($this->availability($record['id'],$assetId,$context)['allowed']) {
                    $m=$record['metadata']; $items[]=['record_id'=>$record['id'],'catalogue_id'=>$m['catalogue']['id'],
                        'type'=>$m['type'],'title'=>$m['title'],'language'=>$m['language'],'playable'=>false]; break;
                }
            }
        }
        return ['items'=>$items,'next_cursor'=>count($records)===$limit ? $records[count($records)-1]['id'] : null];
    }
    public function audit(string $id,int $afterVersion=0,int $limit=50):array {
        Validation::id($id); $this->actor('content.audit',$id); $this->record($id);
        Validation::integer($afterVersion,0,2147483646); Validation::integer($limit,1,100);
        return $this->repository->events($id,$afterVersion,$limit);
    }
}

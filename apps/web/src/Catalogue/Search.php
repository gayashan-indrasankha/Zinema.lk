<?php
declare(strict_types=1);
namespace Zinema\Catalogue;
use Zinema\Content\{Clock,Validation};

final class Record {
    /** Decode/validate the existing rights aggregate; no new catalogue fields inferred. */
    public static function decode(array $row):array {
        try {
            if(!is_string($row['document'])||strlen($row['document'])>60000)throw new \RuntimeException();
            $r=json_decode($row['document'],true,64,JSON_THROW_ON_ERROR);
            if(!is_array($r)||array_diff(['id','version','state','data_class','metadata','assets','holders','grants'],array_keys($r))||!is_array($r['metadata']))throw new \RuntimeException();
            Validation::metadata($r['metadata']);
            Validation::id($r['id']);Validation::integer($r['version'],1,2147483647);
            if($r['id']!==$row['id']||$r['version']!==(int)$row['version']||$r['state']!==$row['state']||
                $r['data_class']!=='synthetic'||$r['metadata']['type']!==$row['content_type']||
                ($r['metadata']['catalogue']['id']??null)!==$row['catalogue_id'])throw new \RuntimeException();
            Validation::one($r['state'],Validation::STATES);Validation::metadata($r['metadata']);
            if(!is_array($r['assets'])||count($r['assets'])>16||!is_array($r['holders'])||count($r['holders'])>8||
                !is_array($r['grants'])||count($r['grants'])>64)throw new \RuntimeException();
            foreach($r['assets'] as $id=>$asset){
                Validation::id($id);if(!is_array($asset)||!is_bool($asset['available']??null))throw new \RuntimeException();
                Validation::asset(array_intersect_key($asset,array_flip(['role','mime','duration_seconds','sha256','storage_ref','provenance_ref'])),$r['metadata']['type']);
            }
            foreach($r['holders'] as $id=>$holder){
                Validation::id($id);if(!is_array($holder)||!is_bool($holder['revoked']??null)||!array_key_exists('verified_by',$holder))throw new \RuntimeException();
                Validation::id($holder['entered_by']??null);Validation::reference($holder['holder_ref']??null,'holder');
                if($holder['verified_by']!==null){Validation::id($holder['verified_by']);Validation::reference($holder['verification_ref']??null,'evidence');}
            }
            foreach($r['grants'] as $id=>$grant){
                Validation::id($id);if(!is_array($grant)||!is_bool($grant['revoked']??null)||!array_key_exists('verified_by',$grant))throw new \RuntimeException();
                Validation::grant(array_intersect_key($grant,array_flip(['asset_id','holder_id','permission','channel','territories','starts_at','ends_at','evidence_ref'])));
                if(!isset($r['assets'][$grant['asset_id']],$r['holders'][$grant['holder_id']]))throw new \RuntimeException();
                Validation::id($grant['entered_by']??null);if($grant['verified_by']!==null)Validation::id($grant['verified_by']);
            }
            if($r['state']==='published'){
                Validation::id($r['approved_by']??null);Validation::id($r['submitted_by']??null);
                Validation::integer($r['approval_version']??null,1,$r['version']);
                if($r['approved_by']===$r['submitted_by']||!is_array($r['publication']??null))throw new \RuntimeException();
                Validation::keys($r['publication'],['asset_ids','territories','channel']);
                Validation::strings($r['publication']['asset_ids'],16,[Validation::class,'id']);
                Validation::strings($r['publication']['territories'],6,static fn($t)=>Validation::one($t,Validation::TERRITORIES));
                Validation::one($r['publication']['channel'],['web']);
            }
            return $r;
        }catch(\Throwable $e){throw new ApiFailure(503,'CATALOGUE_UNAVAILABLE');}
    }
}
final class SearchService {
    public const BUDGET=200;
    public function __construct(private SearchRepository $repository,private Access $access,private CursorCodec $cursors,private Clock $clock) {}
    private function eligible(array $record,Context $context):?array {
        $availability=$this->access->evaluate($record,$context);
        if(!$availability['discoverable'])return null;
        $parent=$record['metadata']['catalogue']['parent'];
        if($parent!==null){
            $p=$this->repository->find($parent['type'],$parent['id']);
            if($p===null||!$this->access->evaluate($p,$context)['discoverable'])return null;
        }
        return $availability;
    }
    private static function dto(array $record,array $availability):array {
        $m=$record['metadata'];$dto=['id'=>$m['catalogue']['id'],'contentType'=>$m['type'],'title'=>$m['title'],
            'synopsis'=>$m['synopsis'],'genre'=>$m['genres'],'language'=>$m['language'],'releaseYear'=>$m['release_year'],
            'runtimeSeconds'=>$m['runtime_seconds'],'tags'=>$m['tags'],'availability'=>$availability];
        if($m['catalogue']['parent']!==null)$dto['parent']=$m['catalogue']['parent'];return $dto;
    }
    public function search(Query $query,Context $context):array {
        $binding=$query->fingerprint($context);$now=$this->clock->now();
        $position=$query->cursor===null?null:$this->cursors->read($query->cursor,$binding,$now,$query->filters['sort']);
        $items=[];$positions=[];$seen=0;
        foreach($this->repository->candidates($query,$position,self::BUDGET) as $candidate){
            if(++$seen>self::BUDGET)throw new ApiFailure(503,'SEARCH_BUDGET_EXCEEDED');
            $r=$candidate['record'];$a=$this->eligible($r,$context);if($a===null)continue;
            $filter=$query->filters['availability'];
            if(($filter==='preview_eligible'&&!$a['previewEligible'])||($filter==='playback_eligible'&&!$a['playbackEligible']))continue;
            $items[]=self::dto($r,$a);$positions[]=['value'=>$candidate['sort_value'],'type'=>$r['metadata']['type'],'id'=>$r['metadata']['catalogue']['id']];
            if(count($items)>$query->limit)break;
        }
        $more=count($items)>$query->limit;if($more){array_pop($items);array_pop($positions);}
        return ['items'=>$items,'nextCursor'=>$more?$this->cursors->issue($positions[count($positions)-1],$binding,$now):null];
    }
    public function detail(Query $query,Context $context):array {
        $record=$this->repository->find($query->filters['content_type'],$query->id);
        $availability=$record===null?null:$this->eligible($record,$context);
        if($record===null||$availability===null)throw new ApiFailure(404,'CONTENT_UNAVAILABLE');
        if($query->resource==='playback'){
            if(!$availability['playbackEligible'])throw new ApiFailure(404,'CONTENT_UNAVAILABLE');
            throw new ApiFailure(503,'PLAYBACK_NOT_CONFIGURED');
        }
        return $query->resource==='availability'?['id'=>$record['metadata']['catalogue']['id'],
            'contentType'=>$record['metadata']['type'],'availability'=>$availability]:self::dto($record,$availability);
    }
}

<?php
declare(strict_types=1);
namespace Zinema\Catalogue;
use Zinema\Content\Validation;

final class Query {
    public const RESOURCES=['movies','series','episodes','shots','search','availability','playback'];
    public const SORTS=['title_asc','title_desc','release_year_asc','release_year_desc'];
    public function __construct(public readonly string $resource,public readonly array $filters,
        public readonly ?string $id,public readonly int $limit,public readonly ?string $cursor) {}
    public static function number(string $value,int $min,int $max):int {
        if (!preg_match('/^(?:0|[1-9][0-9]{0,8})$/D',$value) || (int)$value<$min || (int)$value>$max) throw new ApiFailure(400,'INVALID_REQUEST');
        return (int)$value;
    }
    public static function id(string $value):string {
        if (!preg_match('/^[1-9][0-9]{0,17}$/D',$value)) throw new ApiFailure(400,'INVALID_REQUEST'); return $value;
    }
    private static function one(string $value,array $allowed):string {
        if (!in_array($value,$allowed,true)) throw new ApiFailure(400,'INVALID_REQUEST'); return $value;
    }
    public static function parse(array $input):self {
        $keys=['resource','id','q','content_type','genre','language','release_year','max_runtime','sort','availability','parent_type','parent_id','limit','cursor'];
        if (count($input)>count($keys) || array_diff(array_keys($input),$keys)) throw new ApiFailure(400,'INVALID_REQUEST');
        $bytes=0;foreach($input as $key=>$value){if(!is_string($value)||preg_match('//u',$value)!==1||preg_match('/[\x00-\x1F\x7F]/',$value))throw new ApiFailure(400,'INVALID_REQUEST');$bytes+=strlen($key)+strlen($value);}
        if($bytes>4096)throw new ApiFailure(400,'INVALID_REQUEST');
        $resource=self::one($input['resource']??'search',self::RESOURCES);
        $q=preg_replace('/\s+/u',' ',preg_replace('/^\s+|\s+$/u','',$input['q']??''));
        if(strlen($q)>200)throw new ApiFailure(400,'INVALID_REQUEST');
        $fixed=['movies'=>'movie','series'=>'series','episodes'=>'episode','shots'=>'shot'][$resource]??null;
        $type=isset($input['content_type'])?self::one($input['content_type'],Validation::TYPES):$fixed;
        if($fixed!==null&&$type!==$fixed)throw new ApiFailure(400,'INVALID_REQUEST');
        $id=isset($input['id'])?self::id($input['id']):null;
        if(in_array($resource,['availability','playback'],true)&&($id===null||$type===null))throw new ApiFailure(400,'INVALID_REQUEST');
        if($id!==null&&$type===null)throw new ApiFailure(400,'INVALID_REQUEST');
        if($id!==null&&array_intersect(array_keys($input),['q','genre','language','release_year','max_runtime','sort','availability','parent_type','parent_id','limit','cursor']))throw new ApiFailure(400,'INVALID_REQUEST');
        $parentType=isset($input['parent_type'])?self::one($input['parent_type'],['movie','series']):null;
        $parentId=isset($input['parent_id'])?self::id($input['parent_id']):null;
        if(($parentType===null)!==($parentId===null)||($type==='episode'&&$parentType!==null&&$parentType!=='series'))throw new ApiFailure(400,'INVALID_REQUEST');
        $filters=['q'=>$q,'content_type'=>$type,
            'genre'=>isset($input['genre'])?self::one($input['genre'],Validation::GENRES):null,
            'language'=>isset($input['language'])?self::one($input['language'],Validation::LANGUAGES):null,
            'release_year'=>isset($input['release_year'])?self::number($input['release_year'],1888,2200):null,
            'max_runtime'=>isset($input['max_runtime'])?self::number($input['max_runtime'],0,86400):null,
            'sort'=>self::one($input['sort']??'title_asc',self::SORTS),
            'availability'=>self::one($input['availability']??'discoverable',['discoverable','preview_eligible','playback_eligible']),
            'parent_type'=>$parentType,'parent_id'=>$parentId];
        $limit=self::number($input['limit']??'20',1,50);$cursor=$input['cursor']??null;
        if($cursor!==null&&(strlen($cursor)>2048||$cursor===''))throw new ApiFailure(400,'INVALID_CURSOR');
        return new self($resource,$filters,$id,$limit,$cursor);
    }
    public function fingerprint(Context $context):string {
        return hash('sha256',json_encode([$this->resource,$this->filters,$this->limit,$context->fingerprint()],JSON_THROW_ON_ERROR));
    }
}
final class CursorCodec {
    public function __construct(private string $key) { if(strlen($key)<32)throw new \InvalidArgumentException('CURSOR_KEY_REQUIRED'); }
    private static function encode(string $value):string { return rtrim(strtr(base64_encode($value),'+/','-_'),'='); }
    public function issue(array $position,string $binding,\DateTimeImmutable $now):string {
        $payload=self::encode(json_encode(['v'=>1,'until'=>$now->getTimestamp()+300,'binding'=>$binding,'position'=>$position],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
        return $payload.'.'.self::encode(hash_hmac('sha256',$payload,$this->key,true));
    }
    public function read(string $cursor,string $binding,\DateTimeImmutable $now,string $sort):array {
        if(!preg_match('/^([A-Za-z0-9_-]+)\.([A-Za-z0-9_-]{43})$/D',$cursor,$m)||
            !hash_equals(self::encode(hash_hmac('sha256',$m[1],$this->key,true)),$m[2]))throw new ApiFailure(400,'INVALID_CURSOR');
        try{$payload=json_decode((string)base64_decode(strtr($m[1],'-_','+/'),true),true,16,JSON_THROW_ON_ERROR);}catch(\Throwable $e){throw new ApiFailure(400,'INVALID_CURSOR');}
        $p=$payload['position']??null;
        if(($payload['v']??null)!==1||($payload['binding']??null)!==$binding||!is_int($payload['until']??null)||
            $payload['until']<=$now->getTimestamp()||$payload['until']>$now->getTimestamp()+300||!is_array($p)||
            array_keys($p)!==['value','type','id']||!in_array($p['type']??null,Validation::TYPES,true)||
            !is_string($p['id']??null)||!preg_match('/^[1-9][0-9]{0,17}$/D',$p['id']))throw new ApiFailure(400,'INVALID_CURSOR');
        $valid=str_starts_with($sort,'title_')?is_string($p['value'])&&strlen($p['value'])<=200&&preg_match('//u',$p['value'])===1:
            is_int($p['value'])&&$p['value']>=1888&&$p['value']<=2200;
        if(!$valid)throw new ApiFailure(400,'INVALID_CURSOR');return $p;
    }
}

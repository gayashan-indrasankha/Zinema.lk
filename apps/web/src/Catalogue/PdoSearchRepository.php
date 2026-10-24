<?php
declare(strict_types=1);
namespace Zinema\Catalogue;
require_once dirname(__DIR__,4).'/database/Runner.php';
use PDO;
use Zinema\Database\Runner;
use Zinema\Content\Validation;

/** Reads the verified synthetic rights projection only; never guesses legacy catalogue DDL. */
final class PdoSearchRepository implements SearchRepository {
    public function __construct(private PDO $db) {
        $this->guard();
        if($db->getAttribute(PDO::ATTR_DRIVER_NAME)!=='mysql'||$db->getAttribute(PDO::ATTR_ERRMODE)!==PDO::ERRMODE_EXCEPTION||
            !in_array($db->getAttribute(PDO::ATTR_EMULATE_PREPARES),[false,0],true)||
            !str_starts_with((string)$db->query('SELECT CURRENT_USER()')->fetchColumn(),'zinema_catalogue_reader@'))throw new ApiFailure(503,'CATALOGUE_UNAVAILABLE');
        $name=Runner::settings()['DB_NAME'];$seen=[];
        foreach($db->query('SHOW GRANTS')->fetchAll(PDO::FETCH_COLUMN) as $grant){
            $flat=str_replace(['`','\\'],'',$grant);if(str_starts_with($flat,'GRANT USAGE ON *.* TO '))continue;
            $ok=false;foreach(['content_rights_records','recovery_test_target'] as $table){
                if(str_starts_with($flat,'GRANT SELECT ON '.$name.'.'.$table.' TO ')&&!str_contains($flat,'WITH GRANT OPTION')){$seen[$table]=true;$ok=true;}
            }
            if(!$ok)throw new ApiFailure(503,'CATALOGUE_UNAVAILABLE');
        }
        if(count($seen)!==2)throw new ApiFailure(503,'CATALOGUE_UNAVAILABLE');
    }
    private function guard():void { try{Runner::target($this->db);}catch(\Throwable $e){throw new ApiFailure(503,'CATALOGUE_UNAVAILABLE');} }
    private static function field(string $name):string {
        $paths=['title'=>'$.metadata.title','genre'=>'$.metadata.genres','language'=>'$.metadata.language',
            'year'=>'$.metadata.release_year','runtime'=>'$.metadata.runtime_seconds',
            'parent_type'=>'$.metadata.catalogue.parent.type','parent_id'=>'$.metadata.catalogue.parent.id'];
        $doc="CASE WHEN JSON_VALID(document) THEN document ELSE '{}' END";
        $expression="JSON_EXTRACT($doc, '".$paths[$name]."')";
        if($name==='genre')return $expression;
        if(in_array($name,['year','runtime'],true))return 'CAST(JSON_UNQUOTE('.$expression.') AS UNSIGNED)';
        return '(JSON_UNQUOTE('.$expression.') COLLATE utf8mb4_unicode_ci)';
    }
    public static function plan(Query $query,?array $position,int $budget):array {
        if($budget<1||$budget>200)throw new ApiFailure(400,'INVALID_REQUEST');
        $where=["state='published'",'JSON_VALID(document)'];$values=[];$f=$query->filters;
        if($f['content_type']!==null){$where[]='content_type=?';$values[]=$f['content_type'];}
        if($f['q']!==''){$where[]=self::field('title')." LIKE ? ESCAPE '!'";$values[]='%'.str_replace(['!','%','_'],['!!','!%','!_'],$f['q']).'%';}
        foreach(['language'=>'language','release_year'=>'year','max_runtime'=>'runtime','parent_type'=>'parent_type','parent_id'=>'parent_id'] as $key=>$name){
            if($f[$key]!==null){$where[]=self::field($name).($key==='max_runtime'?'<=?':'=?');$values[]=$f[$key];}
        }
        if($f['genre']!==null){$where[]='JSON_CONTAINS('.self::field('genre').',JSON_QUOTE(?))=1';$values[]=$f['genre'];}
        $sort=self::field(str_starts_with($f['sort'],'title_')?'title':'year');$descending=str_ends_with($f['sort'],'_desc');
        if($position!==null){
            $where[]='('.$sort.($descending?'<?':' >?').' OR ('.$sort.'=? AND (content_type>? OR (content_type=? AND catalogue_id>?))))';
            array_push($values,$position['value'],$position['value'],$position['type'],$position['type'],$position['id']);
        }
        $sql='SELECT /*+ MAX_EXECUTION_TIME(1000) */ id,version,state,content_type,catalogue_id,document,'.$sort.' AS sort_value FROM content_rights_records WHERE '.
            implode(' AND ',$where).' ORDER BY '.$sort.($descending?' DESC':' ASC').',content_type ASC,catalogue_id ASC LIMIT ?';
        $values[]=$budget+1;return ['sql'=>$sql,'values'=>$values];
    }
    public function candidates(Query $query,?array $position,int $budget):iterable {
        $this->guard();$plan=self::plan($query,$position,$budget);
        try{
            $q=$this->db->prepare($plan['sql']);foreach($plan['values'] as $i=>$value)$q->bindValue($i+1,$value,is_int($value)?PDO::PARAM_INT:PDO::PARAM_STR);
            $q->execute();while($row=$q->fetch(PDO::FETCH_ASSOC)){
                $record=Record::decode($row);$value=str_starts_with($query->filters['sort'],'title_')?(string)$row['sort_value']:(int)$row['sort_value'];
                yield ['record'=>$record,'sort_value'=>$value];
            }
        }catch(ApiFailure $e){throw $e;}catch(\Throwable $e){throw new ApiFailure(503,'CATALOGUE_UNAVAILABLE');}
    }
    public function find(string $type,string $id):?array {
        Validation::one($type,Validation::TYPES);Query::id($id);$this->guard();
        try{$q=$this->db->prepare("SELECT /*+ MAX_EXECUTION_TIME(1000) */ id,version,state,content_type,catalogue_id,document FROM content_rights_records WHERE content_type=? AND catalogue_id=? AND state='published' LIMIT 1");
            $q->execute([$type,$id]);$row=$q->fetch(PDO::FETCH_ASSOC);return $row?Record::decode($row):null;
        }catch(ApiFailure $e){throw $e;}catch(\Throwable $e){throw new ApiFailure(503,'CATALOGUE_UNAVAILABLE');}
    }
}

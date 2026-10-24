<?php
declare(strict_types=1);
namespace Zinema\Content;

require_once dirname(__DIR__,4).'/database/Runner.php';
use PDO;
use Throwable;
use Zinema\Database\Runner;

/** Test-only persistence. A production PDO connection is intentionally refused. */
final class PdoRepository implements Repository {
    public function __construct(private PDO $db) {
        $this->guard();
        Validation::require($db->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql' &&
            $db->getAttribute(PDO::ATTR_ERRMODE)===PDO::ERRMODE_EXCEPTION &&
            in_array($db->getAttribute(PDO::ATTR_EMULATE_PREPARES),[false,0],true),'UNSUPPORTED_CONTENT_CONNECTION');
        $user=(string)$db->query('SELECT CURRENT_USER()')->fetchColumn();
        Validation::require(str_starts_with($user,'zinema_content_service@'),'CONTENT_SERVICE_ROLE_REQUIRED');
        $name=Runner::settings()['DB_NAME']; $seen=[];
        foreach ($db->query('SHOW GRANTS')->fetchAll(PDO::FETCH_COLUMN) as $grant) {
            $flat=str_replace(['`','\\'],'',$grant);
            if (str_starts_with($flat,'GRANT USAGE ON *.* TO ')) continue;
            $allowed=['records'=>'GRANT SELECT, INSERT, UPDATE ON '.$name.'.content_rights_records TO ',
                'events'=>'GRANT SELECT, INSERT ON '.$name.'.content_rights_events TO ',
                'marker'=>'GRANT SELECT ON '.$name.'.recovery_test_target TO '];
            $match=false;
            foreach ($allowed as $key=>$prefix) if (str_starts_with($flat,$prefix) && !str_contains($flat,'WITH GRANT OPTION')) { $seen[$key]=true; $match=true; }
            Validation::require($match,'CONTENT_SERVICE_PRIVILEGES_REFUSED');
        }
        Validation::require(count($seen)===3,'CONTENT_SERVICE_PRIVILEGES_REFUSED');
    }
    private function guard():void {
        try { Runner::target($this->db); } catch (Throwable $error) { throw new ContentFailure('CONTENT_DATABASE_REFUSED'); }
    }
    private function document(array $row):array {
        try { $record=json_decode($row['document'],true,64,JSON_THROW_ON_ERROR); }
        catch (Throwable $error) { throw new ContentFailure('CORRUPT_CONTENT_RECORD'); }
        Validation::require(is_array($record) && ($record['id']??null)===$row['id'] &&
            ($record['version']??null)===(int)$row['version'] && ($record['state']??null)===$row['state'] &&
            ($record['data_class']??null)==='synthetic' && in_array($record['state'],Validation::STATES,true) &&
            ($record['metadata']['type']??null)===$row['content_type'] &&
            ($record['metadata']['catalogue']['id']??null)===$row['catalogue_id'] &&
            isset($record['assets'],$record['holders'],$record['grants']) && is_array($record['assets']) &&
            is_array($record['holders']) && is_array($record['grants']),'CORRUPT_CONTENT_RECORD');
        return $record;
    }
    public function find(string $id):?array {
        Validation::id($id); $this->guard();
        try { $q=$this->db->prepare('SELECT id, version, state, content_type, catalogue_id, document FROM content_rights_records WHERE id=?');
            $q->execute([$id]); $row=$q->fetch(PDO::FETCH_ASSOC); return $row ? $this->document($row) : null;
        } catch (ContentFailure $error) { throw $error; } catch (Throwable $error) { throw new ContentFailure('CONTENT_READ_FAILED'); }
    }
    private function payload(array $record):array {
        Validation::id($record['id']); Validation::integer($record['version'],1,2147483647);
        Validation::one($record['state'],Validation::STATES); Validation::one($record['metadata']['type'],Validation::TYPES);
        Validation::require(($record['data_class']??null)==='synthetic','NONSYNTHETIC_RECORD_REFUSED');
        $json=json_encode($record,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        Validation::require(strlen($json)<=60000,'RECORD_SIZE_LIMIT');
        return [$record['id'],$record['version'],$record['state'],$record['metadata']['type'],$record['metadata']['catalogue']['id']??null,$json];
    }
    private function append(array $event,array $record):void {
        Validation::require($event['record_id']===$record['id'] && $event['record_version']===$record['version'] &&
            $event['state']===$record['state'],'AUDIT_RECORD_MISMATCH');
        foreach (['id','record_id','actor_id','subject_id'] as $key) Validation::id($event[$key]);
        Validation::utc($event['occurred_at']);
        Validation::require(preg_match('/^[a-z_]{1,32}$/D',$event['action'])===1,'INVALID_AUDIT_ACTION');
        $q=$this->db->prepare('INSERT INTO content_rights_events (id,record_id,record_version,actor_id,action,subject_id,state,occurred_at) VALUES (?,?,?,?,?,?,?,?)');
        $q->execute([$event['id'],$event['record_id'],$event['record_version'],$event['actor_id'],$event['action'],$event['subject_id'],$event['state'],$event['occurred_at']]);
    }
    private function transaction(callable $work):void {
        $this->guard(); Validation::require(!$this->db->inTransaction(),'TRANSACTION_BOUNDARY_REFUSED');
        try { $this->db->beginTransaction(); $work(); $this->db->commit(); }
        catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if ($error instanceof ContentFailure) throw $error;
            if ($error instanceof \PDOException && $error->getCode()==='23000') throw new ContentFailure('DUPLICATE_REFERENCE');
            throw new ContentFailure('CONTENT_WRITE_FAILED');
        }
    }
    public function create(array $record,array $event):void {
        $values=$this->payload($record); Validation::require($record['version']===1 && $record['state']==='draft','INVALID_INITIAL_RECORD');
        $this->transaction(function()use($record,$event,$values):void {
            $q=$this->db->prepare('INSERT INTO content_rights_records (id,version,state,content_type,catalogue_id,document) VALUES (?,?,?,?,?,?)');
            $q->execute($values); $this->append($event,$record);
        });
    }
    public function save(int $expectedVersion,array $record,array $event):void {
        Validation::integer($expectedVersion,1,2147483646);
        Validation::require($record['version']===$expectedVersion+1,'VERSION_CONFLICT'); $values=$this->payload($record);
        $this->transaction(function()use($expectedVersion,$record,$event,$values):void {
            $q=$this->db->prepare('UPDATE content_rights_records SET version=?,state=?,content_type=?,catalogue_id=?,document=? WHERE id=? AND version=?');
            $q->execute([$values[1],$values[2],$values[3],$values[4],$values[5],$values[0],$expectedVersion]);
            Validation::require($q->rowCount()===1,'VERSION_CONFLICT'); $this->append($event,$record);
        });
    }
    public function scan(string $afterId,int $limit):array {
        if ($afterId!=='') Validation::id($afterId); Validation::integer($limit,1,100); $this->guard();
        try { $q=$this->db->prepare('SELECT id,version,state,content_type,catalogue_id,document FROM content_rights_records WHERE id>? ORDER BY id LIMIT ?');
            $q->bindValue(1,$afterId,PDO::PARAM_STR); $q->bindValue(2,$limit,PDO::PARAM_INT); $q->execute();
            return array_map(fn($row)=>$this->document($row),$q->fetchAll(PDO::FETCH_ASSOC));
        } catch (ContentFailure $error) { throw $error; } catch (Throwable $error) { throw new ContentFailure('CONTENT_READ_FAILED'); }
    }
    public function events(string $id,int $afterVersion,int $limit):array {
        Validation::id($id); Validation::integer($afterVersion,0,2147483646); Validation::integer($limit,1,100); $this->guard();
        try { $q=$this->db->prepare('SELECT id,record_id,record_version,actor_id,action,subject_id,state,occurred_at FROM content_rights_events WHERE record_id=? AND record_version>? ORDER BY record_version LIMIT ?');
            $q->bindValue(1,$id,PDO::PARAM_STR); $q->bindValue(2,$afterVersion,PDO::PARAM_INT); $q->bindValue(3,$limit,PDO::PARAM_INT); $q->execute();
            return $q->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $error) { throw new ContentFailure('CONTENT_READ_FAILED'); }
    }
}

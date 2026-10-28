<?php
/** Bounded, private PHP queue: expired claims are held, never automatically replayed. */
final class ExtractorQueue {
    private $state; private $owner; private $clock; private $verify;
    public function __construct($state,$owner,$verify,$clock=null) {
        if (!is_dir($state) || is_link($state) || str_replace('\\','/',realpath($state) ?: '')!==str_replace('\\','/',$state) ||
            !preg_match('/^zinema-account-[a-f0-9]{16}$/D',$owner) || !is_callable($verify)) throw new ExtractorFailure('extractor_configuration',503);
        $this->state=$state;$this->owner=$owner;$this->verify=$verify;$this->clock=$clock ?? 'time';
    }
    private function transaction($callback) {
        $lock=$this->state.'/queue.lock';$path=$this->state.'/queue.json';
        foreach ([$lock,$path] as $file) if (is_link($file) || (file_exists($file) && (!is_file($file) || lstat($file)['nlink']!==1))) throw new ExtractorFailure('extractor_state',503);
        $fd=@fopen($lock,'c+');if (!$fd || !flock($fd,LOCK_EX)) {if ($fd) fclose($fd);throw new ExtractorFailure('extractor_state',503);}
        try {
            @chmod($lock,0600);$now=($this->clock)();
            $data=['schema'=>1,'owner'=>$this->owner,'heartbeat'=>0,'jobs'=>[],'audit'=>[]];
            if (file_exists($path)) {
                if (filesize($path)>131072) throw new ExtractorFailure('extractor_state',503);
                $data=json_decode(file_get_contents($path),true,16,JSON_THROW_ON_ERROR);
                if (!is_array($data) || ($data['schema'] ?? null)!==1 || ($data['owner'] ?? '')!==$this->owner ||
                    !is_int($data['heartbeat'] ?? null) || !is_array($data['jobs'] ?? null) || count($data['jobs'])>32 ||
                    !is_array($data['audit'] ?? null) || count($data['audit'])>128) throw new ExtractorFailure('extractor_state',503);
            }
            foreach ($data['jobs'] as $id=>&$job) {
                if (!is_string($id) || !preg_match('/^[a-f0-9]{32}$/D',$id) || !is_array($job) ||
                    !is_int($job['admin'] ?? null) || !is_int($job['revision'] ?? null) || $job['revision']<0 ||
                    !is_int($job['deadline'] ?? null) || !valid_facebook_url($job['source'] ?? null) ||
                    !in_array($job['state'] ?? '',['queued','claimed','working','done','held'],true)) throw new ExtractorFailure('extractor_state',503);
                if ((in_array($job['state'],['claimed','working','done'],true) || isset($job['receipt_hash'])) &&
                    (!is_string($job['receipt_hash'] ?? null) || !preg_match('/^[a-f0-9]{64}$/D',$job['receipt_hash']))) throw new ExtractorFailure('extractor_state',503);
                if ($job['state']==='done' && (!is_string($job['url'] ?? null) || strlen($job['url'])>2048)) throw new ExtractorFailure('extractor_state',503);
                if (in_array($job['state'],['queued','claimed','working'],true) && $job['deadline']<=$now) {
                    $job['state']='held';$job['revision']++;
                }
                // Completed media can subsequently be quarantined. Never hand out a stale result.
                if ($job['state']==='done' && !($this->verify)($job['url'])) {$job['state']='held';$job['revision']++;unset($job['url']);}
            } unset($job);
            $result=$callback($data,$now);
            $json=json_encode($data,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);
            if (strlen($json)>131072) throw new ExtractorFailure('extractor_capacity',429);
            $temp=$path.'.'.bin2hex(random_bytes(8)).'.tmp';
            $output=null;
            try {
                $output=@fopen($temp,'x+b');
                if (!$output || !@chmod($temp,0600) || fwrite($output,$json)!==strlen($json) || !fflush($output) || !fsync($output)) throw new ExtractorFailure('extractor_state',503);
                fclose($output);$output=null;
                if (!@rename($temp,$path)) throw new ExtractorFailure('extractor_state',503);
            } finally {if (is_resource($output)) fclose($output);if (is_file($temp) && !is_link($temp)) @unlink($temp);}
            return $result;
        } catch (ExtractorFailure $e) {throw $e;}
        catch (Throwable $e) {throw new ExtractorFailure('extractor_state',503);}
        finally {flock($fd,LOCK_UN);fclose($fd);}
    }
    private static function view($id,$job) {
        $value=['success'=>true,'mode'=>'account-queue','job_id'=>$id,'state'=>$job['state'],'revision'=>$job['revision']];
        if ($job['state']==='done') $value['url']=$job['url'];
        return $value;
    }
    public function admin($action,$admin,$input=[]) {
        if (!is_int($admin) || $admin<1) throw new ExtractorFailure('administrator_required',403);
        return $this->transaction(function(&$data,$now) use ($action,$admin,$input) {
            if ($action==='health') return ['success'=>true,'mode'=>'account-queue','online'=>$data['heartbeat']>0 && $data['heartbeat']>$now-90,'version'=>'outbound-queue-1'];
            if ($action==='extract') {
                $source=$input['url'] ?? null;
                if (!is_string($source) || strlen($source)>512 || !valid_facebook_url($source)) throw new ExtractorFailure('invalid_source',422);
                foreach ($data['jobs'] as $id=>$job) if ($job['admin']===$admin && $job['source']===$source) return self::view($id,$job);
                if (count($data['jobs'])>=32) throw new ExtractorFailure('extractor_capacity',429);
                if ($data['heartbeat']===0 || $data['heartbeat']<=$now-90) throw new ExtractorFailure('extractor_offline',503);
                $id=bin2hex(random_bytes(16));$data['jobs'][$id]=['admin'=>$admin,'source'=>$source,'state'=>'queued','revision'=>0,'deadline'=>$now+900];
                return self::view($id,$data['jobs'][$id]);
            }
            $id=$input['job_id'] ?? '';$job=is_string($id)?($data['jobs'][$id] ?? null):null;
            if ($action!=='status' || !$job || $job['admin']!==$admin) throw new ExtractorFailure('extractor_job_unavailable',404);
            return self::view($id,$job);
        });
    }
    public function worker($input) {
        return $this->transaction(function(&$data,$now) use ($input) {
            $action=$input['action'] ?? '';
            if (!in_array($action,['heartbeat','claim','begin','complete','hold'],true)) throw new ExtractorFailure('invalid_action',400);
            $allowed=in_array($action,['heartbeat','claim'],true)?['action']:['action','job_id','receipt'];
            if ($action==='complete') $allowed[]='url';
            if (array_diff(array_keys($input),$allowed)) throw new ExtractorFailure('invalid_request',400);
            $data['heartbeat']=$now;
            if ($action==='heartbeat') return ['success'=>true];
            if ($action==='claim') {
                // One extractor task at a time across all personal workers.
                foreach ($data['jobs'] as $job) if (in_array($job['state'],['claimed','working'],true)) return ['success'=>true,'job'=>null];
                foreach ($data['jobs'] as $id=>&$job) if ($job['state']==='queued') {
                    $receipt=bin2hex(random_bytes(32));$job['receipt_hash']=hash('sha256',$receipt);$job['state']='claimed';$job['revision']++;$job['deadline']=$now+60;
                    return ['success'=>true,'job'=>['job_id'=>$id,'receipt'=>$receipt,'url'=>$job['source']]];
                } unset($job);
                return ['success'=>true,'job'=>null];
            }
            $id=$input['job_id'] ?? '';$receipt=$input['receipt'] ?? '';
            if (!is_string($id) || !isset($data['jobs'][$id]) || !is_string($receipt) || !preg_match('/^[a-f0-9]{64}$/D',$receipt)) throw new ExtractorFailure('extractor_claim_denied',409);
            $job=&$data['jobs'][$id];
            if (!hash_equals($job['receipt_hash'] ?? '',hash('sha256',$receipt))) throw new ExtractorFailure('extractor_claim_denied',409);
            if ($action==='complete' && $job['state']==='done' && ($job['url'] ?? null)===($input['url'] ?? null)) return ['success'=>true,'state'=>'done'];
            if (!in_array($job['state'],['claimed','working'],true)) throw new ExtractorFailure('extractor_claim_held',409);
            if ($action==='begin' && $job['state']==='claimed') {$job['state']='working';$job['deadline']=$now+120;}
            elseif ($action==='hold') $job['state']='held';
            elseif ($action==='complete' && $job['state']==='working') {
                $url=$input['url'] ?? null;
                if (!is_string($url) || strlen($url)>2048 || !($this->verify)($url)) throw new ExtractorFailure('extractor_result_denied',422);
                $job['url']=$url;$job['state']='done';
            } else throw new ExtractorFailure('extractor_claim_denied',409);
            $job['revision']++;return ['success'=>true,'state'=>$job['state']];
        });
    }
    public function review($input) {
        return $this->transaction(function(&$data,$now) use ($input) {
            if (($input['action'] ?? '')==='inventory' && array_keys($input)===['action']) {
                $jobs=[];foreach ($data['jobs'] as $id=>$job) $jobs[]=self::view($id,$job);
                return ['success'=>true,'jobs'=>$jobs,'audit'=>$data['audit'],'replay_supported'=>false];
            }
            if (array_diff(array_keys($input),['action','job_id','revision','reviewed','workers_stopped','actor','evidence_ref']) ||
                ($input['action'] ?? '')!=='archive' || ($input['reviewed'] ?? null)!==true || ($input['workers_stopped'] ?? null)!==true ||
                !preg_match('/^demo_[a-z0-9_]{3,40}$/D',$input['actor'] ?? '') || !preg_match('/^fixture:[a-z0-9-]{3,64}$/D',$input['evidence_ref'] ?? '')) throw new ExtractorFailure('review_required',409);
            $id=$input['job_id'] ?? '';$job=is_string($id)?($data['jobs'][$id] ?? null):null;
            if (!$job || !in_array($job['state'],['held','done'],true) || ($input['revision'] ?? null)!==$job['revision']) throw new ExtractorFailure('review_stale',409);
            if (count($data['audit'])>=128) throw new ExtractorFailure('extractor_capacity',429);
            $data['audit'][]=['job_id'=>$id,'source_hash'=>hash('sha256',$job['source']),'state'=>$job['state'],'revision'=>$job['revision'],
                'actor'=>$input['actor'],'evidence_ref'=>$input['evidence_ref'],'at'=>$now,'outcome'=>'archived_without_replay'];
            unset($data['jobs'][$id]);return ['success'=>true,'replay_supported'=>false];
        });
    }
}
function account_extractor_queue() {
    if (!defined('ZINEMA_STELLAR_CONTEXT') || EXTRACTOR_MODE!=='account-queue' || getenv('ALLOW_LIVE_INTEGRATIONS')!=='false') throw new ExtractorFailure('extractor_disabled',503);
    require_once __DIR__.'/upload_storage.php';$c=ZINEMA_STELLAR_CONTEXT;$settings=local_storage_settings();
    return new ExtractorQueue($c['private'].'/extractor',$settings['owner'],static function($url) use ($settings) {
        if (!str_starts_with($url,BASE_URL.'/uploads/trailers/')) return false;
        $relative=substr($url,strlen(BASE_URL.'/uploads/'));
        if (!storage_managed_path($relative) || !str_ends_with($relative,'.mp4')) return false;
        return storage_with_lock($settings,false,static function($manifest) use ($settings,$relative) {
            $asset=$manifest['assets'][$relative] ?? null;$path=$settings['root'].'/'.$relative;
            return $asset && storage_location($asset)==='active' && !is_link($path) && is_file($path) && lstat($path)['nlink']===1 &&
                filesize($path)===$asset['bytes'] && hash_equals($asset['sha256'],hash_file('sha256',$path));
        });
    });
}

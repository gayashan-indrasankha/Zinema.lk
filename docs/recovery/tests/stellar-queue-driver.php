<?php
// CLI fictional test driver only; excluded from website packages.
if (PHP_SAPI!=='cli') {http_response_code(404);exit;}
require_once __DIR__.'/../../../includes/extractor_client.php';
require_once __DIR__.'/../../../includes/extractor_queue.php';
require_once __DIR__.'/../../../ops/staging/stellar/runtime.php';
$v=json_decode(stream_get_contents(STDIN),true,16,JSON_THROW_ON_ERROR);
$state=$v['directory'];$boundary=str_replace('\\','/',realpath(__DIR__.'/../../../.runtime')).'/';
if (!str_starts_with(str_replace('\\','/',realpath($state) ?: ''),$boundary)) exit(2);
try {
    if (($v['scope'] ?? '')==='context') {
        $context=stellar_runtime_context($v['config'],$v['private'],$v['public'],$v['remote'] ?? null);
        $result=['valid'=>true,'mode'=>$context['mode']];
    } else {
        $queue=new ExtractorQueue($state,'zinema-account-'.str_repeat('a',16),static fn($url)=>$url==='http://127.0.0.1:9999/uploads/trailers/'.str_repeat('a',32).'.mp4',static fn()=>$v['now'] ?? 1000);
        $result=match ($v['scope']) {
            'admin'=>$queue->admin($v['input']['action'],$v['admin'] ?? 1,$v['input']),
            'worker'=>$queue->worker($v['input']),
            'review'=>$queue->review($v['input']),
            default=>throw new RuntimeException(),
        };
    }
    echo json_encode($result,JSON_UNESCAPED_SLASHES);
} catch (ExtractorFailure $e) {echo json_encode(['success'=>false,'error'=>$e->getMessage(),'status'=>$e->httpStatus]);}
catch (Throwable $e) {echo '{"valid":false}';}

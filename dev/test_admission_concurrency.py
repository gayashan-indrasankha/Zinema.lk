"""Independent PHP process races against owned SQL admission locks; fictional peers."""
import concurrent.futures
import json
import secrets
import subprocess
import sys
from local import BASE,ENV,ROOT,STATE,require_local_docker,require_owned_gateway
require_local_docker();require_owned_gateway()
code=r"""
ini_set('display_errors','0');
try {
 require '/opt/zinema/dev/operations.php';[$db,$settings]=operator_context();
 require '/var/www/html/includes/auth.php';require '/var/www/html/includes/rate_limiter.php';
 $input=json_decode(stream_get_contents(STDIN,2049),true);
 if(!preg_match('/^[a-f0-9]{12}$/D',$input['run'] ?? ''))exit(2);
 $run=$input['run'];$_SERVER['REMOTE_ADDR']='2001:db8:'.substr($run,0,4).':'.substr($run,4,4).':'.substr($run,8,4).'::1';
 if($input['mode']==='password') {$r=authenticate_password($db,'parallel@example.invalid','fictional-invalid');$allowed=empty($r['rate_limited']);$limit=20;}
 elseif($input['mode']==='token') {$limiter=new RateLimiter($db);$allowed=$limiter->checkLimit('fictional_parallel')['allowed'];$limit=(int)getSetting($db,'rate_limit_per_minute','10');}
 else exit(2);
 echo json_encode(['admitted'=>$allowed,'limit'=>$limit]);
}catch(Throwable $e){echo '{"error":"local_probe_unavailable"}';exit(1);}
"""
rows=[]
for mode,count in [('password',28),('token',16)]:
    run=secrets.token_hex(6)
    def probe(_):
        result=subprocess.run(BASE+['exec','-T','web','php','-r',code],cwd=ROOT,env=ENV,input=json.dumps({'run':run,'mode':mode}),capture_output=True,text=True,timeout=30)
        if result.returncode:raise RuntimeError('Local admission probe unavailable')
        return json.loads(result.stdout)
    try:
        with concurrent.futures.ThreadPoolExecutor(max_workers=8) as pool:results=list(pool.map(probe,range(count)))
        passed=sum(r['admitted'] for r in results)==min(count,results[0]['limit'])
    except Exception:passed=False
    rows.append({'name':'Independent PHP processes cannot oversubscribe '+mode+' admission','passed':passed,'evidence':'runtime verified locally'})
report={'scope':'Owned local DB; 8 concurrent CLI workers and documentation-only IPv6 identities; no account/provider contacted','tests':rows,'summary':{'total':len(rows),'passed':sum(r['passed'] for r in rows),'failed':sum(not r['passed'] for r in rows)}}
(STATE/'results/production-admission-concurrency.json').write_text(json.dumps(report,indent=2)+'\n');print(json.dumps(report['summary']));sys.exit(1 if report['summary']['failed'] else 0)

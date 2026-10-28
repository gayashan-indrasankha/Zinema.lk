"""TLS/security/load checks inside the labelled isolated rehearsal namespace only."""
import concurrent.futures,http.cookiejar,json,re,ssl,sys,time,urllib.request,urllib.error,urllib.parse
from pathlib import Path
URL='https://127.0.0.1:8443';CTX=ssl.create_default_context(cafile='/run/ca.pem');ROWS=[]
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self,*_):return None
class Client:
    def __init__(self):
        self.jar=http.cookiejar.CookieJar();self.opener=urllib.request.build_opener(urllib.request.HTTPSHandler(context=CTX),urllib.request.HTTPCookieProcessor(self.jar),NoRedirect())
    def request(self,path,data=None,headers=None,raw=None):
        assert path.startswith('/') and not path.startswith('//')
        request=urllib.request.Request(URL+path,data=raw if raw is not None else urllib.parse.urlencode(data).encode() if data is not None else None,headers=headers or {})
        try:response=self.opener.open(request,timeout=8)
        except urllib.error.HTTPError as error:response=error
        return response.status,response.read(),response.headers
    def csrf(self,page='/admin/login.php'):
        match=re.search(rb'name="csrf_token" value="([a-f0-9]{64})"',self.request(page)[1])
        if not match:raise RuntimeError('csrf_absent')
        return match[1].decode()
def check(name,condition):ROWS.append({'name':name,'passed':bool(condition)})
def metrics(concurrency,requests):
    paths=['/movies','/tv-series','/collections','/api/live_search.php?q=Stage&type=movies','/api/trailers.php?limit=5'];values=[]
    def one(i):
        start=time.monotonic()
        try:
            with urllib.request.urlopen(URL+paths[i%len(paths)],context=CTX,timeout=8) as response:status=response.status;size=len(response.read())
        except Exception:return (time.monotonic()-start)*1000,False,0
        return (time.monotonic()-start)*1000,status==200,size
    start=time.monotonic()
    with concurrent.futures.ThreadPoolExecutor(max_workers=concurrency) as pool:values=list(pool.map(one,range(requests)))
    elapsed=time.monotonic()-start;times=sorted(x[0] for x in values)
    return {'concurrency':concurrency,'requests':requests,'errors':sum(not x[1] for x in values),'p50_ms':round(times[int((len(times)-1)*.50)],2),'p95_ms':round(times[int((len(times)-1)*.95)],2),'p99_ms':round(times[int((len(times)-1)*.99)],2),'max_ms':round(max(times),2),'elapsed_s':round(elapsed,2),'response_bytes':sum(x[2] for x in values)}
def run(mode):
    if mode=='outage':
        try:status=Client().request('/movies')[0]
        except Exception:status=0
        check('Local outage probe emits website-unavailable alert',status!=200)
        return {'alerts':[{'code':'website_unavailable','observed_status':status}],'live_alert_sent':False}
    client=Client();status,body,headers=client.request('/admin/login.php')
    check('Artifact is available through verified local TLS',status==200)
    check('Secure HTTP-only SameSite session cookie',any(cookie.secure and 'HttpOnly' in cookie._rest and cookie._rest.get('SameSite')=='Lax' for cookie in client.jar))
    check('Security headers and CSP enforced at TLS proxy',headers.get('X-Content-Type-Options')=='nosniff' and 'frame-ancestors' in headers.get('Content-Security-Policy','') and headers.get('X-Frame-Options')=='DENY')
    check('Runtime version header hidden',headers.get('X-Powered-By') is None)
    check('Unrecognised Host rejected',client.request('/movies',headers={'Host':'unrelated.example.invalid'})[0]==421)
    for path in ['/.env','/.git/config','/.runtime/example','/Database.sql','/dev/http/control.php','/includes/database.php','/api/includes/auth.php','/fb-video-api/index.js','/uploads/fictional.php','/admin/run_migration.php']:
        check('Artifact refuses private/unsafe path '+path,client.request(path)[0] in {403,404})
    check('Missing CSRF rejects administrator sign-in',client.request('/admin/login.php',{'username':'demo_admin','password':'not-real'})[0]==403)
    password=Path('/run/demo_password').read_text().strip();token=client.csrf()
    check('Fictional admin authenticates over TLS',client.request('/admin/login.php',{'username':'demo_admin','password':password,'csrf_token':token})[0]==302)
    check('Administrator dashboard works with CRUD-only DB identity',client.request('/admin/index.php')[0]==200)
    check('Administrator extractor remains disabled',client.request('/admin/extractor.php',headers={'Content-Type':'application/json','X-CSRF-Token':client.csrf()},raw=b'{"action":"health"}')[0]==503)
    for path in ['/whatsapp-watch.php?id=1','/api/whatsapp/health.php']:
        check('Live delivery stays disabled '+path,client.request(path)[0]==503)
    check('Guest cannot enter administrator dashboard',Client().request('/admin/index.php')[0]==302)
    if mode=='source':
        loads=[metrics(5,100),metrics(25,300)]
        for value in loads:check('Bounded TLS read load has no HTTP errors at '+str(value['concurrency'])+' clients',value['errors']==0)
        try:
            urllib.request.urlopen(URL+'/movies',context=ssl.create_default_context(),timeout=3);untrusted=False
        except (urllib.error.URLError,ssl.SSLError):untrusted=True
        check('Temporary CA is explicitly trusted; default trust refuses it',untrusted)
        return {'loads':loads,'monitor':{'healthy':client.request('/movies')[0]==200}}
    # Exercise the proxy's header overwrite only after restore/login checks.
    guest=Client();statuses=[]
    for i in range(22):
        statuses.append(guest.request('/admin/login.php',{'username':'demo_admin','password':'not-real','csrf_token':guest.csrf()},headers={'X-Real-IP':'2001:db8::'+str(i),'X-Forwarded-For':'2001:db8::'+str(i)})[0])
    check('Spoofed forwarding headers cannot bypass shared password admission',429 in statuses and statuses[-1]==429)
    return {'restore_http_verified':True}
if __name__=='__main__':
    mode=sys.argv[1];details={}
    try:details=run(mode)
    except Exception as error:
        reason=getattr(error,'reason',None)
        details={'error_type':type(error).__name__,'reason_type':type(reason).__name__,'errno':getattr(reason,'errno',None),'ssl_verify_code':getattr(reason,'verify_code',None)}
        check('HTTP rehearsal completed ('+type(error).__name__+')',False)
    result={'scope':'Owned isolated artifact, synthetic identities; explicit local test CA; no provider requests','tests':ROWS,'details':details,'summary':{'total':len(ROWS),'passed':sum(x['passed'] for x in ROWS),'failed':sum(not x['passed'] for x in ROWS)}}
    Path('/results/'+mode+'.json').write_text(json.dumps(result,indent=2)+'\n');print(json.dumps(result['summary']));sys.exit(1 if result['summary']['failed'] else 0)

"""Verify a locked Stellar bundle on owned loopback Apache; no PHP web execution or DB."""
import json,re,sys,time,subprocess,secrets,socket,urllib.request,urllib.error
from pathlib import Path
from local import ROOT
from prepare_stellar import prepare
def local_command(args,**kwargs):
    try:return subprocess.run(args,cwd=ROOT,capture_output=True,timeout=10,**kwargs)
    except subprocess.TimeoutExpired:raise RuntimeError('local_command_timeout') from None

def native_rehearsal():
    # Local fallback never restarts Docker or accesses its unrelated containers.
    home=ROOT/'.runtime/stellar-native'/('case-'+secrets.token_hex(6));home.mkdir(parents=True)
    bundle=home/'bundle';manifest=prepare(bundle);rows=[];process=None;error=None;http_rows=[]
    def check(name,value):
        rows.append({'name':name,'passed':bool(value)})
        if not value:raise RuntimeError('native_assertion_failed')
    httpd=Path('C:/xampp/apache/bin/httpd.exe');php=Path('C:/xampp/php/php.exe');marker=secrets.token_hex(16)
    with socket.socket() as available:available.bind(('127.0.0.1',0));port=available.getsockname()[1]
    normalize=lambda p:p.as_posix()
    config=[f'ServerRoot "C:/xampp/apache"',f'Listen 127.0.0.1:{port}','ServerName 127.0.0.1',f'PidFile "{normalize(home/"httpd.pid")}"',f'ErrorLog "{normalize(home/"apache.log")}"','LogLevel warn']
    config += [f'LoadModule {m}_module modules/mod_{m}.so' for m in ['authz_core','authz_host','dir','mime','rewrite','headers','env']]
    config += ['TypesConfig "C:/xampp/apache/conf/mime.types"',f'DocumentRoot "{normalize(bundle/"public_html")}"','DirectoryIndex index.php','<Directory />','Require all denied','AllowOverride None','</Directory>',f'<Directory "{normalize(bundle/"public_html")}">','Require local','AllowOverride All','Options -Indexes +FollowSymLinks','</Directory>',f'Header always set X-Zinema-Candidate "{marker}"','Header always set X-Powered-By "fictional-version-probe"']
    conf=home/'httpd.conf';conf.write_text('\n'.join(config)+'\n')
    log=(home/'console.log').open('wb')
    try:
        result=local_command([str(httpd),'-t','-f',str(conf)])
        check('Owned native Apache configuration validates',result.returncode==0)
        process=subprocess.Popen([str(httpd),'-f',str(conf)],stdout=log,stderr=log,creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
        (home/'ownership.json').write_text(json.dumps({'purpose':'native-locked-candidate','config':str(conf),'pid':process.pid,'port':port}))
        class NoRedirect(urllib.request.HTTPRedirectHandler):
            def redirect_request(self,*args):return None
        client=urllib.request.build_opener(urllib.request.ProxyHandler({}),NoRedirect())
        for _ in range(100):
            if process.poll() is not None:raise RuntimeError('native_apache_exited')
            with socket.socket() as probe:
                probe.settimeout(.1)
                if probe.connect_ex(('127.0.0.1',port))==0:break
            time.sleep(.1)
        else:raise RuntimeError('native_apache_not_ready')
        for route in ['/','/index.php','/admin/index.php','/api/whatsapp/health.php','/.user.ini','/assets/js/security.js','/uploads/fictional.php','/zinema-private/staging/candidate.php']:
            if process.poll() is not None:raise RuntimeError('native_apache_exited')
            try:response=client.open('http://127.0.0.1:'+str(port)+route,timeout=3)
            except urllib.error.HTTPError as failure:response=failure
            with response:
                owned=response.headers.get('X-Zinema-Candidate')==marker
                status=response.status;version_removed=response.headers.get('X-Powered-By') is None;response.read(2048)
            check('Owned native Apache denies '+route,status==403 and owned and version_removed);http_rows.append({'path':route,'status':status,'owned':owned,'version_header_removed':version_removed})
        result=local_command([str(php),'-d','auto_prepend_file='+str(bundle/'zinema-private/staging/bootstrap.php'),str(bundle/'public_html/index.php')])
        check('Explicit private CLI prepend stops before application execution',result.returncode==0 and result.stdout==b'Private staging candidate is disabled.' and not result.stderr)
        result=local_command([str(php),str(bundle/'zinema-private/staging/check.php')])
        check('Exported private disabled template validates',result.returncode==0 and not result.stderr and json.loads(result.stdout)['valid_disabled_template'])
        result=local_command([str(php),str(bundle/'zinema-private/staging/host-capabilities.php')])
        check('Private capability probe stays read-only and offline',result.returncode==0 and not result.stderr and json.loads(result.stdout)['database_connection_attempted'] is False)
    except Exception as failure:error=str(failure) if str(failure).startswith('native_') else type(failure).__name__
    finally:
        if process is not None and process.poll() is None:
            # The live Popen handle pins this exact owned parent; only its child tree.
            stopped=local_command(['taskkill','/PID',str(process.pid),'/T','/F'])
            process.wait(timeout=10);rows.append({'name':'Owned native Apache stopped','passed':stopped.returncode==0})
        log.close()
    with socket.socket() as probe:
        probe.settimeout(.2);closed=probe.connect_ex(('127.0.0.1',port))!=0
    rows.append({'name':'Native loopback test port closed','passed':closed})
    text=(home/'console.log').read_text(errors='replace')+((home/'apache.log').read_text(errors='replace') if (home/'apache.log').exists() else '')
    diagnostics={kind:len(re.findall('PHP '+kind,text)) for kind in ['Warning','Notice','Fatal error','Deprecated']}
    report={'date':'2026-10-28','scope':'Native loopback Apache deny rules and private PHP CLI guard; no PHP web execution, DB or providers','tests':rows,'http':http_rows,'error':error,'source_commit':manifest['source_commit'],'bundle_files':len(manifest['files']),'php_diagnostics':diagnostics,'summary':{'total':len(rows),'passed':sum(r['passed'] for r in rows),'failed':sum(not r['passed'] for r in rows)},'limitations':['Actual shared-host handler/user.ini, private extractor and account not verified.','Docker test was unavailable; no global Docker restart was performed.','Application feature/TLS/DB/provider tests were not repeated; locked staging must be activated only after review.']}
    (home/'results.json').write_text(json.dumps(report,indent=2)+'\n');print(json.dumps({'report':str(home/'results.json'),'summary':report['summary'],'error':error}));return 1 if error or report['summary']['failed'] else 0
if __name__=='__main__':
    if sys.argv[1:] not in [[],['--native']]:
        print('Use the owned Windows native rehearsal without additional arguments.',file=sys.stderr);sys.exit(2)
    sys.exit(native_rehearsal())

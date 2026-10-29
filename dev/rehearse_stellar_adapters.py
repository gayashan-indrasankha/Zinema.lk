"""Owned native Apache/PHP + fresh SQL + outbound fake worker; never hosting access."""
import base64,contextlib,io,json,os,re,secrets,socket,subprocess,sys,time,urllib.request,urllib.error,http.cookiejar
from pathlib import Path
from prepare_stellar import prepare,ROOT,TEMPLATES
from test_stellar_adapters import fixture_configuration,PHP
sys.path.insert(0,str(ROOT/'docs/recovery/tests'))
import audit

def free_port():
    with socket.socket() as s:s.bind(('127.0.0.1',0));return s.getsockname()[1]

def run():
    case=ROOT/'.runtime/stellar-adapter-rehearsals'/('case-'+secrets.token_hex(6));case.mkdir(parents=True)
    report={'scope':'Local native account adapters and fictional outbound extraction; not provider verification','tests':[],'external_connections':False,'production_access':False,'publishing':False}
    folder=None;process=None;log=None;port=free_port();origin=f'http://127.0.0.1:{port}'
    def check(name,passed):
        report['tests'].append({'name':name,'passed':bool(passed)})
        if not passed:raise RuntimeError('local_assertion_failed:'+name)
    def command(args,**kw):
        p=subprocess.run([str(x) for x in args],cwd=ROOT,capture_output=True,timeout=40,**kw)
        if p.returncode:raise RuntimeError('owned_local_command_failed')
        return p.stdout
    try:
        # Existing harness refuses unknown/occupied ports and verifies @@datadir before SQL.
        captured=io.StringIO()
        def own_fixture(created):
            nonlocal folder
            folder=created
        with contextlib.redirect_stdout(captured):audit.prepare(own_fixture)
        folder=Path(json.loads(captured.getvalue().splitlines()[-1])['runtime']);report['native_fixture']=folder.name
        manifest=prepare(case/'bundle',working_copy=True);report['source_commit']=manifest['source_commit'];report['source_overlays']=manifest['source_overlays']
        public=case/'bundle/public_html';private=case/'bundle/zinema-private/staging'
        for name in ['storage','sessions','extractor','logs']:(private/name).mkdir(mode=0o700)
        (public/'uploads/trailers').mkdir(parents=True)
        # Remove the unconditional PACKAGE guard only inside this new fictional fixture.
        raw=(public/'.htaccess').read_bytes();guard=(TEMPLATES/'guard.htaccess').read_bytes()+b'\n';assert raw.startswith(guard)
        (public/'.htaccess').write_bytes(guard.replace(b'Require all denied',b'# Local fixture access is controlled by Require local and the private bootstrap',1)+raw[len(guard):])
        password=secrets.token_hex(24)
        audit.sql(folder,f"CREATE USER 'zinema_recovery_fixture'@'127.0.0.1' IDENTIFIED BY '{password}'; GRANT ALL ON zinema_recovery_test.* TO 'zinema_recovery_fixture'@'127.0.0.1';")
        c=fixture_configuration(private,port,audit.DB_PORT,password)
        config=private/'runtime-config.php'
        def save_config(value):
            config.write_text("<?php return json_decode(base64_decode('"+base64.b64encode(json.dumps(value).encode()).decode()+"'),true,16,JSON_THROW_ON_ERROR);\n");config.chmod(0o600)
        save_config(c)
        def operator(value):return json.loads(command([PHP,private/'operator.php'],input=json.dumps(value).encode()))
        check('Private CLI initializes account-owned quota state without chown',operator({'action':'initialize'})['success'])
        clip='a'*32+'.mp4';source=folder/'web/demo.mp4';check('Only generated legal clip is used',source.is_file())
        code="require '"+(private/'bootstrap.php').as_posix()+"';require '"+(public/'includes/environment.php').as_posix()+"';require '"+(public/'includes/upload_storage.php').as_posix()+"';echo storage_commit_upload('"+source.as_posix()+"','trailers/"+clip+"');"
        check('Account quota writer admits generated clip',command([PHP,'-r',code]).decode()=='uploads/trailers/'+clip)
        # Private prepend is explicit in mod_php: user.ini behavior on CGI/FastCGI remains an account gate.
        conf=['ServerRoot "C:/xampp/apache"',f'Listen 127.0.0.1:{port}','ServerName 127.0.0.1',f'PidFile "{case.as_posix()}/httpd.pid"',f'ErrorLog "{case.as_posix()}/apache.log"','LogLevel warn']
        conf += [f'LoadModule {m}_module modules/mod_{m}.so' for m in ['authz_core','authz_host','dir','mime','rewrite','headers','env']]
        conf += ['LoadModule php_module "C:/xampp/php/php8apache2_4.dll"','PHPIniDir "C:/xampp/php"','TypesConfig "C:/xampp/apache/conf/mime.types"',f'DocumentRoot "{public.as_posix()}"','DirectoryIndex index.php','<Directory />','Require all denied','AllowOverride None','</Directory>',f'<Directory "{public.as_posix()}">','Require local','AllowOverride All','Options -Indexes +FollowSymLinks','</Directory>','<FilesMatch "\\.php$">','SetHandler application/x-httpd-php','</FilesMatch>',f'php_admin_value auto_prepend_file "{private.as_posix()}/bootstrap.php"','php_admin_flag display_errors Off',f'php_admin_value error_log "{case.as_posix()}/php.log"']
        conf_path=case/'httpd.conf';conf_path.write_text('\n'.join(conf)+'\n')
        command([audit.HTTPD,'-t','-f',conf_path]);log=open(case/'console.log','w')
        process=subprocess.Popen([str(audit.HTTPD),'-f',str(conf_path)],cwd=ROOT,stdout=log,stderr=log,creationflags=audit.NO_WINDOW)
        (case/'ownership.json').write_text(json.dumps({'pid':process.pid,'config':str(conf_path),'port':port,'purpose':'fictional-account-adapter-rehearsal'}))
        audit.wait_port(port,process)
        jar=http.cookiejar.CookieJar()
        class LocalRedirect(urllib.request.HTTPRedirectHandler):
            def redirect_request(self,req,fp,code,msg,headers,newurl):
                if not newurl.startswith(origin+'/'):raise RuntimeError('redirect_denied')
                return super().redirect_request(req,fp,code,msg,headers,newurl)
        client=urllib.request.build_opener(urllib.request.ProxyHandler({}),LocalRedirect(),urllib.request.HTTPCookieProcessor(jar))
        def request(route,data=None,headers=None,form=False,method=None):
            assert route.startswith('/') and not route.startswith('//');h=dict(headers or {})
            if data is not None:
                from urllib.parse import urlencode
                h['Content-Type']='application/x-www-form-urlencoded' if form else 'application/json';data=(urlencode(data) if form else json.dumps(data)).encode()
            try:response=client.open(urllib.request.Request(origin+route,data=data,headers=h,method=method),timeout=6)
            except urllib.error.HTTPError as e:response=e
            with response:body=response.read().decode(errors='replace');return response.status,body,dict(response.headers)
        for route in ['/.user.ini','/zinema-private/staging/runtime-config.php','/ops/staging/stellar/operator.php']:
            check('Private/INI material is inaccessible: '+route,request(route)[0] in [403,404])
        check('Anonymous worker call is denied',request('/api/extractor-work.php',{'action':'heartbeat'})[0]==403)
        key={'X-Extractor-Worker-Key':c['extractor_worker_key']}
        check('Wrong worker key is denied',request('/api/extractor-work.php',{'action':'heartbeat'},{'X-Extractor-Worker-Key':'0'*64})[0]==403)
        check('Worker rejects GET',request('/api/extractor-work.php',headers=key)[0]==405)
        check('Worker rejects oversized request',request('/api/extractor-work.php',{'padding':'x'*3000},key)[0]==413)
        check('Independent key sends heartbeat',request('/api/extractor-work.php',{'action':'heartbeat'},key)[0]==200)
        check('Worker key does not authorize browser endpoint',request('/admin/extractor.php',{'action':'health'},key)[0]==403)
        status,body,_=request('/admin/login.php');csrf=re.search('name="csrf_token" value="([a-f0-9]+)"',body).group(1)
        check('Anonymous browser with CSRF still cannot extract',request('/admin/extractor.php',{'action':'health'},{'X-CSRF-Token':csrf})[0]==403)
        check('Fictional administrator can sign in',request('/admin/login.php',{'username':'audit_admin','password':audit.SAMPLE_PASSWORD,'csrf_token':csrf},form=True)[0]==200)
        body=request('/admin/manage_shots.php')[1];csrf=re.search('name="csrf_token" value="([a-f0-9]+)"',body).group(1)
        check('Worker key is never rendered to administrator',c['extractor_worker_key'] not in body)
        check('Administrator still needs CSRF',request('/admin/extractor.php',{'action':'health'})[0]==403)
        def admin(value):
            status,raw,_=request('/admin/extractor.php',value,{'X-CSRF-Token':csrf});return status,json.loads(raw)
        check('Admin health reports authenticated outbound worker',admin({'action':'health'})[1]['online'])
        source_url='https://www.facebook.com/share/v/fictional/'
        status,job=admin({'action':'extract','url':source_url});check('Admin enqueues instead of waiting in PHP',status==200 and job['state']=='queued')
        before=audit.sql(folder,'SELECT COUNT(*) FROM shots;',True)
        form=dict(submit_shot='1',title='Fictional pending clip',description='Generated fixture',content_type='movie',linked_content_id='1',fb_share_url=source_url,csrf_token=csrf)
        check('Pending extraction cannot create a broken shot',request('/admin/manage_shots.php',form,form=True)[0]==409 and audit.sql(folder,'SELECT COUNT(*) FROM shots;',True)==before)
        worker_dir=case/'worker';worker_dir.mkdir();worker_config=worker_dir/'fixture.json'
        worker_config.write_text(json.dumps(dict(mode='local-fixture',externalConnections=False,publishing=False,origin=origin,key=c['extractor_worker_key'],directory=str(worker_dir.resolve()),fixtureUrl=origin+'/uploads/trailers/'+clip)))
        result=json.loads(command(['node',ROOT/'fb-video-api/outbound-worker.cjs','--local-fixture',worker_config]))
        check('Outbound fake worker completes one claim',result['state']=='done')
        result=admin({'action':'status','job_id':job['job_id']})[1]
        check('Administrator observes only the verified same-origin managed clip',result['state']=='done' and result['url']==origin+'/uploads/trailers/'+clip)
        # Exercise existing recoverable algorithms under account ownership, real locks and fresh SQL.
        mpath=private/'storage/manifest.json';m=json.loads(mpath.read_text());m['assets']['trailers/'+clip]['created_at']=int(time.time())-172800;mpath.write_text(json.dumps(m));os.utime(public/'uploads/trailers'/clip,(time.time()-172800,)*2)
        review=operator(dict(action='storage-review',path='uploads/trailers/'+clip))['review']
        check('Account operator inventories an owned review candidate',review['quarantine_eligible'])
        intent=dict(path='uploads/trailers/'+clip,revision=review['revision'],actor='demo_owner',evidence_ref='fixture:account-cleanup',case_ref='fixture:account-quarantine',reviewed=True)
        check('Account cleanup quarantines with audit',operator(dict(action='storage-quarantine',**intent))['success'])
        review=operator(dict(action='storage-review',path=intent['path']))['review'];check('Quarantined clip is recoverable',review['restore_eligible'])
        intent.update(revision=review['revision'],case_ref='fixture:account-restore')
        check('Account restore preserves original clip',operator(dict(action='storage-restore',**intent))['success'])
        check('Account history retains both actions',len(operator(dict(action='storage-history',path=intent['path']))['operations'])==2)
        # A private client allowlist is checked before application or DB work.
        c['allowed_clients']=['127.0.0.2'];save_config(c)
        check('Private bootstrap rejects an unlisted client',request('/index.php')[0]==503)
        save_config({**c,'allowed_clients':['127.0.0.1']})
        audit.RESULT_PREFIX='STELLAR_ADAPTERS'
        with contextlib.redirect_stdout(io.StringIO()):
            try:audit.check(folder)
            except SystemExit as result:
                if result.code:raise RuntimeError('local_assertion_failed:retained_website_regressions')
        regression=json.loads((ROOT/'docs/recovery/STELLAR_ADAPTERS_RETAINED_HTTP.json').read_text())
        report['website_regressions']=regression.get('summary',{})
        check('Retained website regressions pass on the fresh database',regression['summary']['failed']==0)
    except Exception as error:
        report['error']=str(error) if str(error).startswith(('local_assertion_failed:','owned_local_command_failed')) else type(error).__name__
    finally:
        if process is not None and process.poll() is None:
            command(['taskkill','/PID',str(process.pid),'/T','/F']);process.wait(timeout=10)
        if log:log.close()
        if folder:
            with contextlib.redirect_stdout(io.StringIO()):audit.stop(folder)
        with socket.socket() as probe:probe.settimeout(.2);closed=probe.connect_ex(('127.0.0.1',port))!=0
        report['tests'].append({'name':'Owned adapter HTTP process stopped and port closed','passed':closed})
    logs=''.join(p.read_text(errors='replace') for p in [case/'php.log',case/'console.log'] if p.exists())
    report['php_diagnostics']={k:len(re.findall('PHP '+k,logs)) for k in ['Warning','Notice','Fatal error','Deprecated']}
    report['summary']={'total':len(report['tests']),'passed':sum(t['passed'] for t in report['tests']),'failed':sum(not t['passed'] for t in report['tests'])}
    report['limitations']=['Native mod_php does not verify Stellar CGI/FastCGI user.ini or account policy.','Only generated clip and loopback fake extraction; no upstream/SDK/provider contacted.','Upload speed, sustained personal-machine capacity and independent backups remain unverified.']
    (case/'results.json').write_text(json.dumps(report,indent=2)+'\n')
    (ROOT/'docs/recovery/STELLAR_ADAPTERS_REHEARSAL.json').write_text(json.dumps(report,indent=2)+'\n')
    print(json.dumps({'report':str(case/'results.json'),'summary':report['summary'],'error':report.get('error'),'php_diagnostics':report['php_diagnostics']}))
    return 1 if report.get('error') or report['summary']['failed'] or any(report['php_diagnostics'].values()) else 0
if __name__=='__main__':sys.exit(run())

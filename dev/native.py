"""Persistent whole local demo on installed Windows runtimes. No Docker or live SDK."""
import argparse,contextlib,hashlib,io,json,os,re,secrets,shutil,socket,subprocess,sys,time,traceback,urllib.request,urllib.error,webbrowser
from pathlib import Path
ROOT=Path(__file__).resolve().parent.parent
sys.path.insert(0,str(ROOT/'docs/recovery/tests'))
import audit
STATE=ROOT/'.runtime/native-local';POINTER=STATE/'active.json'
SMTP_PORT=11025
PORTS=[8088,3307,SMTP_PORT,9081,3334,9090]

def checked_home(value):
    raw=Path(value);home=raw.resolve()
    if home.parent!=(ROOT/'.runtime').resolve() or not re.fullmatch(r'audit-[0-9]{8}-[0-9]{6}',home.name) or raw.is_symlink():raise RuntimeError('native_target_denied')
    owner=json.loads((home/'ownership.json').read_text());c=json.loads((home/'native-config.json').read_text())
    if c.get('schema')!=1 or c.get('external_connections') is not False or c.get('publishing') is not False or owner.get('native_owner')!=c.get('owner') or not re.fullmatch('zinema-native-[a-f0-9]{12}',c.get('owner','')):raise RuntimeError('native_target_denied')
    e=c['environment']
    if any(e.get(k)!=v for k,v in {'APP_ENV':'test','DB_HOST':'127.0.0.1','DB_NAME':'zinema_recovery_test','DB_PORT':'3307','BASE_URL':'http://127.0.0.1:8088','ALLOW_LIVE_INTEGRATIONS':'false','MEDIA_MODE':'disabled','PAYMENT_MODE':'disabled'}.items()):raise RuntimeError('native_target_denied')
    if e.get('MAIL_HOST')!='127.0.0.1' or e.get('MAIL_PORT') not in ['1025',str(SMTP_PORT)]:raise RuntimeError('native_target_denied')
    return home,c

def current():
    value=json.loads(POINTER.read_text())
    if value.get('root')!=str(ROOT) or value.get('purpose')!='whole-native-local-demo':raise RuntimeError('native_owner_denied')
    return checked_home(value['home'])

def real_whatsapp(home):
    file=home/'native/whatsapp-live.json'
    if not file.exists():return None
    if file.is_symlink() or file.stat().st_size>16384:raise RuntimeError('native_live_configuration_denied')
    a=json.loads(file.read_text())
    owner=json.loads((home/'ownership.json').read_text())
    if a.get('schema')!=1 or a.get('owner')!=owner.get('native_owner') or a.get('mode')!='owner-local-whatsapp' or a.get('liveWhatsApp') is not True or any(a.get(k) is not False for k in ['production','publishing','automaticJobs']) or a.get('sourceRoot')!='D:\\Zinema.lk\\whatsapp-bot-deploy' or not re.fullmatch('[0-9]{8,15}',a.get('accountPhone','')) or a.get('recipients')!=[a['accountPhone']] or a.get('maxAttachmentBytes')!=20971520:raise RuntimeError('native_live_configuration_denied')
    return a

def runtime_ports(c):
    return [port for port in PORTS if port!=SMTP_PORT]+[int(c['environment']['MAIL_PORT'])]

def upgrade_mail_port(home,c):
    """Migrate only stopped, owned native state; never stop a competing SMTP service."""
    home,verified=checked_home(home)
    if verified!=c:raise RuntimeError('native_target_denied')
    if c['environment']['MAIL_PORT']==str(SMTP_PORT):return c
    if any(audit.port_open(port) for port in PORTS):raise RuntimeError('native_ports_occupied_preserve_unknown_services')
    updated=json.loads(json.dumps(c));updated['environment']['MAIL_PORT']=str(SMTP_PORT)
    pending=home/'native-config.pending.json';pending.write_text(json.dumps(updated));pending.chmod(0o600)
    pending.replace(home/'native-config.json')
    return updated

def verify_processes(home):
    records=json.loads((home/'native/processes.json').read_text())
    if set(records)!={'bot','extractor','fakes'}:raise RuntimeError('native_process_owner_denied')
    ownership=json.loads((home/'ownership.json').read_text())
    records['apache']={'pid':ownership['pids']['apache']}
    for role,item in records.items():
        pid=item.get('pid');script=home/'httpd.conf' if role=='apache' else ROOT/('dev/fakes.py' if role=='fakes' else 'dev/native/whatsapp-live.cjs' if role=='bot' and real_whatsapp(home) else 'dev/native/services.cjs')
        if not isinstance(pid,int) or pid<1:raise RuntimeError('native_process_owner_denied')
        probe=subprocess.run(['powershell','-NoProfile','-Command',f'Get-CimInstance Win32_Process -Filter "ProcessId={pid}" | Select-Object ExecutablePath,CommandLine | ConvertTo-Json -Compress'],capture_output=True,text=True,timeout=10,creationflags=audit.NO_WINDOW)
        process=json.loads(probe.stdout) if probe.returncode==0 and probe.stdout.strip() else None
        allowed={'httpd.exe'} if role=='apache' else {'node.exe','python.exe','python3.exe'}
        if not process or str(script).lower() not in (process.get('CommandLine') or '').lower() or str(home).lower() not in (process.get('CommandLine') or '').lower() or Path(process.get('ExecutablePath') or '').name.lower() not in allowed:raise RuntimeError('native_process_owner_denied')

def request(port,route,payload=None,key=None):
    if port not in [8088,9081,9090,3334] or not route.startswith('/') or route.startswith('//'):raise RuntimeError('native_request_denied')
    headers={};body=None
    if payload is not None:body=json.dumps(payload).encode();headers['Content-Type']='application/json'
    if key:headers[{9090:'X-Local-Control',8088:'X-Api-Key',3334:'X-Extractor-Key',9081:'X-Test-Key'}[port]]=key
    opener=urllib.request.build_opener(urllib.request.ProxyHandler({}))
    try:r=opener.open(urllib.request.Request(f'http://127.0.0.1:{port}'+route,data=body,headers=headers),timeout=6)
    except urllib.error.HTTPError as error:r=error
    with r:raw=r.read(1048577)
    if len(raw)>1048576:raise RuntimeError('native_response_limit')
    return r.status,raw

def dependencies():
    expected=json.loads((ROOT/'docs/recovery/tests/extractor-http/package-lock.json').read_text())
    candidates=list((ROOT/'.runtime').glob('audit-*/extractor-deps'))+[STATE/'dependencies']
    for folder in candidates:
        try:
            if folder.is_symlink() or not (folder/'node_modules/express/package.json').is_file():continue
            if json.loads((folder/'package-lock.json').read_text())==expected:return folder.resolve()
        except (OSError,ValueError):continue
    raise RuntimeError('locked_local_extractor_dependencies_required')

def clean_env(extra=None):
    env={k:v for k,v in os.environ.items() if k.upper() not in {'NODE_OPTIONS','HTTP_PROXY','HTTPS_PROXY','ALL_PROXY','NODE_EXTRA_CA_CERTS'}}
    env.update(ALLOW_LIVE_INTEGRATIONS='false',ALLOW_LIVE_WHATSAPP='false')
    if extra:env.update(extra)
    return env

def configure(home,env,owner):
    identity='zinema-native-'+secrets.token_hex(6);owner['native_owner']=identity
    for item in ['native','native/storage','native/bot','web/uploads']:(home/item).mkdir(exist_ok=True,mode=0o700)
    env.update(SESSION_SAVE_PATH=(home/'sessions').as_posix(),MAIL_MODE='local',MAIL_HOST='127.0.0.1',MAIL_PORT=str(SMTP_PORT),MAIL_USERNAME='local-sink',MAIL_PASSWORD='local-unused',MAIL_FROM_EMAIL='demo@example.invalid',MAIL_FROM_NAME='Zinema Local Demo',
        OAUTH_MODE='local',GOOGLE_CLIENT_ID='zinema-local',GOOGLE_CLIENT_SECRET=secrets.token_hex(32),GOOGLE_REDIRECT_URI='http://127.0.0.1:8088/google-callback.php',OAUTH_AUTHORIZE_URL='http://127.0.0.1:9081/authorize',OAUTH_TOKEN_URL='http://127.0.0.1:9081/token',OAUTH_USERINFO_URL='http://127.0.0.1:9081/userinfo',
        WHATSAPP_MODE='local',WHATSAPP_API_KEY=secrets.token_hex(32),EXTRACTOR_MODE='local',EXTRACTOR_API_KEY=secrets.token_hex(32),FB_VIDEO_API_URL='http://127.0.0.1:3334',TRUSTED_PROXY_IPS='',ZINEMA_NATIVE_FIXTURES='1',
        UPLOAD_QUOTA_MODE='native',UPLOAD_STORAGE_STATE=(home/'native/storage').as_posix(),UPLOAD_STORAGE_OWNER=identity,UPLOAD_MAX_BYTES='268435456',UPLOAD_MAX_FILES='1000',UPLOAD_QUARANTINE_MAX_BYTES='67108864',UPLOAD_QUARANTINE_MAX_FILES='500')
    keys=['APP_ENV','BASE_URL','DB_HOST','DB_PORT','DB_NAME','DB_USER','DB_PASS','SESSION_SAVE_PATH','ALLOW_LIVE_INTEGRATIONS']+[x+'_MODE' for x in ['MAIL','OAUTH','WHATSAPP','MEDIA','EXTRACTOR','PAYMENT']]+['MAIL_HOST','MAIL_PORT','MAIL_USERNAME','MAIL_PASSWORD','MAIL_FROM_EMAIL','MAIL_FROM_NAME','GOOGLE_CLIENT_ID','GOOGLE_CLIENT_SECRET','GOOGLE_REDIRECT_URI','OAUTH_AUTHORIZE_URL','OAUTH_TOKEN_URL','OAUTH_USERINFO_URL','WHATSAPP_API_KEY','EXTRACTOR_API_KEY','FB_VIDEO_API_URL','TRUSTED_PROXY_IPS','ZINEMA_NATIVE_FIXTURES','UPLOAD_QUOTA_MODE','UPLOAD_STORAGE_STATE','UPLOAD_STORAGE_OWNER','UPLOAD_MAX_BYTES','UPLOAD_MAX_FILES','UPLOAD_QUARANTINE_MAX_BYTES','UPLOAD_QUARANTINE_MAX_FILES']
    c={'schema':1,'owner':identity,'external_connections':False,'publishing':False,'environment':{key:env[key] for key in keys},'control_key':secrets.token_hex(32),'express_deps':str(dependencies()),'fake_media_url':'http://127.0.0.1:8088/uploads/trailers/'+hashlib.sha256((identity+'clip').encode()).hexdigest()[:32]+'.mp4'}
    (home/'native-config.json').write_text(json.dumps(c));(home/'native-config.json').chmod(0o600)
    (home/'native-prepend.php').write_text("<?php try { require '"+(ROOT/'dev/native/bootstrap.php').as_posix()+"'; native_demo_bootstrap(__DIR__); } catch (Throwable $e) { http_response_code(503); exit('Local demo configuration unavailable.'); }\n")
    (home/'preview-access.txt').write_text('WHOLE LOCAL FICTIONAL DEMO ONLY\nWebsite: http://127.0.0.1:8088\nConsole/inbox: http://127.0.0.1:8088/__local/index.php\nAdministrator: audit_admin\nMember: verified@example.invalid\nBoth use password: '+audit.SAMPLE_PASSWORD+'\nFake Google sign-in authorizes oauth@example.invalid locally. Signup requires .invalid email; use the local inbox to verify.\n')

def apache_config(home,conf):
    alias=(ROOT/'dev/native/http').as_posix()
    conf.insert(0,'LoadModule alias_module modules/mod_alias.so')
    conf.extend([f'php_admin_value auto_prepend_file "{home.as_posix()}/native-prepend.php"',f'Alias /__local/ "{alias}/"',f'<Directory "{alias}">','Require local','AllowOverride None','Options -Indexes','</Directory>'])

def cli(home,script,payload=None):
    result=subprocess.run([str(audit.PHP),'-c',str(home/'php-config'),'-d','auto_prepend_file='+str(home/'native-prepend.php'),str(ROOT/script)],cwd=ROOT,env=clean_env(),input=json.dumps(payload).encode() if payload is not None else None,capture_output=True,timeout=25)
    if result.returncode or result.stderr:raise RuntimeError('native_private_cli_failed')
    try:return json.loads(result.stdout)
    except ValueError:raise RuntimeError('native_private_cli_failed') from None

def resume(home,c):
    if any(audit.port_open(p) for p in runtime_ports(c)):raise RuntimeError('native_ports_occupied')
    ownership=json.loads((home/'ownership.json').read_text())
    db_log=open(home/'db-console-resume.log','a');db=subprocess.Popen([str(audit.MYSQL/'mysqld.exe'),'--no-defaults',f'--basedir={audit.MYSQL.parent}',f'--datadir={home/"db"}','--bind-address=127.0.0.1','--port=3307','--skip-name-resolve',f'--pid-file={home/"mysql.pid"}',f'--log-error={home/"mysql.log"}','--console'],stdout=db_log,stderr=db_log,creationflags=audit.NO_WINDOW)
    ownership['pids']['mysql']=db.pid;(home/'ownership.json').write_text(json.dumps(ownership));audit.wait_port(3307,db);audit.sql(home,'SELECT 1;')
    with contextlib.redirect_stdout(io.StringIO()):audit.refresh(home)
    log=open(home/'apache-console-resume.log','a');apache=subprocess.Popen([str(audit.HTTPD),'-f',str(home/'httpd.conf')],env=clean_env(c['environment']),stdout=log,stderr=log,creationflags=audit.NO_WINDOW)
    ownership['pids']['apache']=apache.pid;(home/'ownership.json').write_text(json.dumps(ownership));audit.wait_port(8088,apache)
    return [db,apache]

def spawn(home,role):
    live=role=='bot' and real_whatsapp(home) is not None
    script=ROOT/('dev/fakes.py' if role=='fakes' else 'dev/native/whatsapp-live.cjs' if live else 'dev/native/services.cjs')
    args=[sys.executable,'-B',str(script),'--native',str(home)] if role=='fakes' else ['node',str(script),str(home)] if live else ['node',str(script),role,str(home)]
    log=open(home/f'native/{role}.log','a');p=subprocess.Popen(args,cwd=ROOT,env=clean_env(),stdout=log,stderr=log,creationflags=audit.NO_WINDOW)
    records=json.loads((home/'native/processes.json').read_text()) if (home/'native/processes.json').exists() else {}
    records[role]={'pid':p.pid,'script':str(script)};(home/'native/processes.json').write_text(json.dumps(records))
    try:audit.wait_port({'fakes':9081,'bot':9090,'extractor':3334}[role],p)
    except Exception:
        if p.poll() is None:p.terminate();p.wait(timeout=5)
        raise
    return p

def stop_extra(home,role,p=None):
    if role=='bot':
        try:request(9090,'/shutdown',{},checked_home(home)[1]['control_key'])
        except Exception:pass
        if p:
            try:p.wait(timeout=7);return
            except subprocess.TimeoutExpired:pass
    if p and p.poll() is None:p.terminate();p.wait(timeout=7)

def open_local_site():
    try:opened=webbrowser.open('http://127.0.0.1:8088',new=2)
    except Exception:opened=False
    if not opened:print('Open the local website manually: http://127.0.0.1:8088',flush=True)

def up(open_browser=False):
    STATE.mkdir(parents=True,exist_ok=True);home=None;children={};base_children=[]
    if POINTER.exists():
        home,c=current()
        if audit.port_open(8088):
            verify_processes(home);audit.sql(home,'SELECT 1;')
            status,raw=request(9090,'/status',{},c['control_key'])
            if status!=200 or json.loads(raw).get('success') is not True:raise RuntimeError('native_simulator_not_ready')
            if request(9081,'/health')[0]!=200 or not audit.port_open(int(c['environment']['MAIL_PORT'])) or request(3334,'/health',{},c['environment']['EXTRACTOR_API_KEY'])[0]!=200:raise RuntimeError('native_services_not_ready')
            print(json.dumps({'status':'already_running','website':'http://127.0.0.1:8088','console':'http://127.0.0.1:8088/__local/index.php','access_file':str(home/'preview-access.txt'),'external_connections':real_whatsapp(home) is not None}),flush=True)
            if open_browser:open_local_site()
            return
    if any(audit.port_open(port) for port in PORTS):raise RuntimeError('native_ports_occupied_preserve_unknown_services')
    if home is not None:c=upgrade_mail_port(home,c)
    dependencies()
    try:
        if home is None:
            def owned(created):
                nonlocal home
                home=created
            with contextlib.redirect_stdout(io.StringIO()):audit.prepare(owned,configure,apache_config)
            home,c=checked_home(home)
            POINTER.write_text(json.dumps({'purpose':'whole-native-local-demo','root':str(ROOT),'home':str(home)}))
        else:base_children=resume(home,c)
        if (home/'native/stop.request').exists():(home/'native/stop.request').unlink()
        cli(home,'dev/native/seed.php')
        for role in ['fakes','extractor','bot']:children[role]=spawn(home,role)
        for _ in range(30):
            try:
                status,raw=request(9090,'/status',{},c['control_key'])
                if status==200 and json.loads(raw).get('success'):break
            except Exception:pass
            time.sleep(.2)
        else:raise RuntimeError('native_simulator_not_ready')
        status,raw=request(8088,'/api/whatsapp/health.php',{},c['environment']['WHATSAPP_API_KEY'])
        if status!=200 or json.loads(raw).get('success') is not True:raise RuntimeError('native_bridge_not_ready')
        if request(9081,'/health')[0]!=200 or not audit.port_open(int(c['environment']['MAIL_PORT'])) or request(3334,'/health',{},c['environment']['EXTRACTOR_API_KEY'])[0]!=200:raise RuntimeError('native_services_not_ready')
        live=real_whatsapp(home) is not None
        print(json.dumps({'status':'ready','website':'http://127.0.0.1:8088','console':'http://127.0.0.1:8088/__local/index.php','runtime':str(home),'access_file':str(home/'preview-access.txt'),'integrations':'owner-approved real WhatsApp; other providers simulated' if live else 'local substitutes only; no real accounts','external_connections':live}),flush=True)
        if open_browser:open_local_site()
        while not (home/'native/stop.request').exists():
            if (home/'native/restart-bot.request').exists():
                stop_extra(home,'bot',children['bot']);children['bot']=spawn(home,'bot');(home/'native/restart-bot.request').unlink()
            if any(p.poll() is not None for p in children.values()):raise RuntimeError('native_child_stopped_preserve_evidence')
            time.sleep(.25)
    except KeyboardInterrupt:pass
    finally:
        for role,p in reversed(list(children.items())):stop_extra(home,role,p)
        if home is not None:
            with contextlib.redirect_stdout(io.StringIO()):audit.stop(home)
        for p in base_children:
            try:p.wait(timeout=5)
            except subprocess.TimeoutExpired:pass

def main():
    parser=argparse.ArgumentParser(description='Whole native local demo; never live integrations.')
    parser.add_argument('command',choices=['up','status','down','restart-bot','operator']);parser.add_argument('request_file',nargs='?')
    parser.add_argument('--open-browser',action='store_true',help='Open the localhost website after verified startup (up only).');args=parser.parse_args()
    if args.open_browser and args.command!='up':parser.error('--open-browser is available only with up')
    if args.command=='up':up(args.open_browser);return
    home,c=current()
    if args.command in ['down','restart-bot']:
        (home/'native'/('stop.request' if args.command=='down' else 'restart-bot.request')).write_text('requested\n')
        if args.command=='down':
            for _ in range(80):
                if not any(audit.port_open(port) for port in runtime_ports(c)):break
                time.sleep(.25)
            else:raise RuntimeError('native_stop_pending_preserve_processes_and_review')
        print(json.dumps({'requested':args.command,'data_preserved':True}));return
    if args.command=='operator':
        file=Path(args.request_file or '').resolve()
        if (ROOT/'.runtime').resolve() not in file.parents or file.is_symlink() or file.stat().st_size>16384:raise RuntimeError('private_request_file_required')
        print(json.dumps(cli(home,'dev/native/operator.php',json.loads(file.read_text())),indent=2));return
    if not any(audit.port_open(port) for port in runtime_ports(c)):print(json.dumps({'status':'stopped','data_preserved':True}));return
    verify_processes(home)
    audit.sql(home,'SELECT 1;')
    status,raw=request(9090,'/status',{},c['control_key']);bot=json.loads(raw)
    print(json.dumps({'website':request(8088,'/movies')[0],'local_oauth':request(9081,'/health')[0],'local_smtp_listening':audit.port_open(int(c['environment']['MAIL_PORT'])),'local_smtp_port':int(c['environment']['MAIL_PORT']),'extractor':request(3334,'/health',{},c['environment']['EXTRACTOR_API_KEY'])[0],'bot_status':status,'bot_mode':bot.get('mode'),'bot_connection':bot.get('connection'),'bot_online':bot.get('online'),'deliveries':len(bot.get('outbox',[])),'held':len(bot.get('held',[])),'external_connections':real_whatsapp(home) is not None}))

if __name__=='__main__':
    try:main()
    except Exception as error:
        STATE.mkdir(parents=True,exist_ok=True)
        (STATE/'last-error.json').write_text(json.dumps({'type':type(error).__name__,'frames':[{'file':Path(frame.filename).name,'line':frame.lineno,'function':frame.name} for frame in traceback.extract_tb(error.__traceback__)]}))
        code=str(error) if isinstance(error,RuntimeError) and re.fullmatch('[a-z_]+',str(error)) else 'native_local_start_or_operation_failed'
        print(json.dumps({'success':False,'error':code,'private_evidence_retained':True}),file=sys.stderr);sys.exit(1)

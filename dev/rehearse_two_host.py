"""Two fictional machines on a new internal network. No providers or published ports."""
import json,os,re,secrets,sys,time
from pathlib import Path
import local,rehearse_staging
ROOT=local.ROOT
NODE_IMAGE='node@sha256:55fb5f5a527ea2c5c4a95f963e48ef90aa38c7b346054fdc8ee9957a2b55d205'
class TwoHost(rehearse_staging.Rehearsal):
    def __init__(self):
        super().__init__();self.network=self.image+'-private';self.scenarios={};self.network_created=False
        (self.home/'source/control_key').write_text(secrets.token_hex(32))
    def env(self,production=False):
        return super().env(False)+['-e','WHATSAPP_MODE=local','-e','ZINEMA_TWO_HOST_FIXTURES=1']
    def mounts(self,db):
        return super().mounts(db)+self.mount(self.home/db['side']/'control_key','/run/secrets/control_key')
    def cli(self,db,file,payload=None):
        self.owned(db['name'])
        result=self.docker('run','--rm','-i','--network','container:'+db['name'],*self.label,*super().env(False),*self.mounts(db),self.image,'php','/opt/zinema/dev/'+file,data=json.dumps(payload).encode() if payload is not None else None,allow=True)
        if result.returncode:
            self.diag.write(result.stdout);self.diag.flush();raise RuntimeError('local_step_failed:fixture-'+file)
        return result.stdout
    def db(self,side):
        folder=self.home/side;volume=self.volume(side,'database')
        args=['--network',self.network,'--network-alias','website','--memory','384m','--cpus','.5','--pids-limit','128',*self.mount(volume,'/var/lib/mysql',False,'volume'),*self.mount(ROOT/'database/schema.sql','/docker-entrypoint-initdb.d/001-schema.sql')]
        for name in ['db_root','db_password']:args+=self.mount(folder/name,'/run/secrets/'+name)
        for k,v in {'MARIADB_DATABASE':rehearse_staging.DB_NAME,'MARIADB_USER':rehearse_staging.DB_USER,'MARIADB_PASSWORD_FILE':'/run/secrets/db_password','MARIADB_ROOT_PASSWORD_FILE':'/run/secrets/db_root'}.items():args+=['-e',k+'='+v]
        name=self.container(side+'-db',[*args,rehearse_staging.DB_IMAGE,'--bind-address=127.0.0.1','--skip-name-resolve'])
        self.wait_db(name)
        return {'name':name,'side':side,'volumes':{p:self.volume(side,p) for p in ['uploads','storage','sessions']}}
    def wait_db(self,name):
        for _ in range(80):
            if self.docker('exec',name,'healthcheck.sh','--connect','--innodb_initialized',allow=True).returncode==0:return
            time.sleep(1)
        raise RuntimeError('fictional_db_not_ready')
    def tls(self):
        super().tls();folder=self.home/'tls'
        (folder/'extensions').write_text('subjectAltName=IP:127.0.0.1,DNS:localhost,DNS:website\nbasicConstraints=critical,CA:FALSE\nkeyUsage=critical,digitalSignature,keyEncipherment\nextendedKeyUsage=serverAuth\n')
        self.command([r'C:\xampp\apache\bin\openssl.exe','x509','-req','-in',str(folder/'server.csr'),'-CA',str(folder/'ca.pem'),'-CAkey',str(folder/'ca.key'),'-CAcreateserial','-out',str(folder/'server.pem'),'-days','2','-extfile',str(folder/'extensions')])
    def scenario(self,mode):
        result=self.docker('exec',self.bot,'node','/opt/two_host_bot.cjs',mode,allow=True)
        target=self.results/('two-host-'+mode+'.json')
        if not target.is_file():raise RuntimeError('scenario_report_missing')
        report=json.loads(target.read_text());self.scenarios[mode]=report
        self.check('Two-host '+mode+' scenario',result.returncode==0 and report['summary']['failed']==0)
    def large(self):
        volume=self.volume('large','state')
        name=self.container('large-file-fake',['--network','none','--read-only','--cap-drop','ALL','--memory','192m','--cpus','1','--pids-limit','64','-e','ZINEMA_TWO_HOST_FIXTURES=1','-e','ALLOW_LIVE_WHATSAPP=false',*self.mount(ROOT/'dev/two_host_large.cjs','/opt/two_host_large.cjs'),*self.mount(ROOT/'whatsapp-bot-deploy/config','/opt/zinema/config'),*self.mount(volume,'/state',False,'volume'),*self.mount(self.results,'/results',False),NODE_IMAGE,'node','--max-old-space-size=64','/opt/two_host_large.cjs'])
        result=self.docker('wait',name);report=json.loads((self.results/'two-host-large.json').read_text());self.scenarios['large']=report
        self.check('Large-file exact boundary and bounded-memory streaming',result.stdout.strip()==b'0' and report['summary']['failed']==0)
        return {'local_only':True,'large':report}
    def run(self):
        self.docker('network','create','--internal',*self.label,self.network);self.network_created=True
        info=json.loads(self.docker('network','inspect',self.network).stdout)[0]
        self.check('Owned local bridge is internal',info['Internal'] and info['Labels'].get('zinema.rehearsal.case')==self.case)
        self.docker('build','--file',ROOT/'dev/web.Dockerfile','--tag',self.image,ROOT)
        db=self.db('source');self.cli(db,'seed.php');fixture=json.loads(self.cli(db,'product_fixture.php',{'run':secrets.token_hex(6)}))
        self.check('Fresh fictional catalogue and schema initialized',fixture.get('success') is True)
        (self.home/'fixture.json').write_text(json.dumps({'movie':fixture['movie']}));self.restrict(db);self.tls()
        app=self.container('website',['--network','container:'+db['name'],'--memory','256m','--cpus','.5','--pids-limit','128',*self.env(),*self.mounts(db),self.image])
        config=(ROOT/'dev/staging/nginx.conf').read_text().replace('listen 127.0.0.1:8443 ssl','listen 0.0.0.0:8443 ssl').replace('127\\.0\\.0\\.1|localhost','127\\.0\\.0\\.1|localhost|website').replace('proxy_set_header Host $http_host;','proxy_set_header Host 127.0.0.1:8088;')
        (self.home/'nginx.conf').write_text(config)
        proxy=self.container('tls',['--network','container:'+db['name'],'--user','101:101','--read-only','--tmpfs','/tmp:rw,noexec,nosuid,size=32m','--cap-drop','ALL','--memory','64m','--cpus','.5',*self.mount(self.home/'nginx.conf','/etc/nginx/nginx.conf'),*self.mount(self.home/'tls/server.pem','/run/tls/server.pem'),*self.mount(self.home/'tls/server.key','/run/tls/server.key'),'--entrypoint','nginx',rehearse_staging.NGINX_IMAGE,'-g','daemon off;'])
        state=self.volume('bot','state')
        args=['--network',self.network,'--user',str(os.getuid()) if hasattr(os,'getuid') else '0','--read-only','--cap-drop','ALL','--memory','192m','--cpus','1','--pids-limit','64','--tmpfs','/tmp:rw,noexec,nosuid,size=16m']
        for k,v in {'ZINEMA_TWO_HOST_FIXTURES':'1','API_URL':'https://website:8443/api/whatsapp','ALLOW_LIVE_WHATSAPP':'false','NODE_EXTRA_CA_CERTS':'/run/ca.pem'}.items():args+=['-e',k+'='+v]
        for source,target in [(ROOT/'dev/two_host_bot.cjs','/opt/two_host_bot.cjs'),(ROOT/'whatsapp-bot-deploy/config','/opt/zinema/config'),(self.home/'tls/ca.pem','/run/ca.pem'),(self.home/'fixture.json','/run/fixture.json')]:args+=self.mount(source,target)
        for name in ['adapter_key','control_key']:args+=self.mount(self.home/'source'/name,'/run/secrets/'+name)
        args+=self.mount(state,'/state',False,'volume')+self.mount(self.results,'/results',False)
        self.bot=self.container('fake-personal-bot',[*args,NODE_IMAGE,'node','-e','setInterval(()=>{},10000)'])
        bot_info=self.owned(self.bot);web_info=self.owned(db['name'])
        self.check('Website and bot have distinct network namespaces',bot_info['HostConfig']['NetworkMode']==self.network and bot_info['NetworkSettings']['Networks'][self.network]['IPAddress']!=web_info['NetworkSettings']['Networks'][self.network]['IPAddress'])
        self.check('No fixture container publishes a port',all(not self.owned(c)['HostConfig'].get('PortBindings') for c in self.containers))
        self.check('Database is unreachable from bot namespace',self.docker('exec',self.bot,'node','-e',"const net=require('net');const s=net.connect(3306,'website');s.on('connect',()=>process.exit(1));s.on('error',()=>process.exit(0));s.setTimeout(2000,()=>process.exit(0));",allow=True).returncode==0)
        self.check('Bot namespace has no default route',self.docker('exec',self.bot,'node','-e',"const fs=require('fs');const rows=fs.readFileSync('/proc/net/route','utf8').trim().split('\\n').slice(1);process.exit(rows.some(r=>r.trim().split(/\\s+/)[1]==='00000000')?1:0)",allow=True).returncode==0)
        time.sleep(2);self.scenario('initial');self.scenario('delivery')
        self.stop(self.bot);self.stop(app);self.stop(proxy);self.stop(db['name'])
        self.docker('start',db['name']);self.wait_db(db['name'])
        for container in [app,proxy,self.bot]:self.owned(container);self.docker('start',container)
        time.sleep(2);self.scenario('restart');self.stop(app);self.scenario('outage')
        self.docker('start',app);time.sleep(2);self.scenario('recovered');self.large()
        return {'local_only':True,'providers_contacted':False,'public_ports':False,'separate_network_namespaces':True,'scenarios':self.scenarios,'media_transport':'fictional only; full-media local boundary suite separate','retained_fixture_volumes':len(self.volumes)}
    def cleanup(self):
        super().cleanup()
        if self.network_created:
            result=__import__('subprocess').run(['docker','network','inspect',self.network],env=local.ENV,capture_output=True)
            if result.returncode==0:
                network=json.loads(result.stdout)[0]
                if network['Labels'].get('zinema.rehearsal.case')==self.case and network['Labels'].get('zinema.rehearsal.owner')==local.PROJECT:
                    __import__('subprocess').run(['docker','network','rm',self.network],env=local.ENV,capture_output=True)
if __name__=='__main__':
    rehearsal=TwoHost();details={};error=None
    try:details=rehearsal.large() if sys.argv[1:]==['--large-only'] else rehearsal.run()
    except Exception as failure:
        error=str(failure) if str(failure).startswith(('local_step_failed:','assertion_failed','fictional_db_not_ready','scenario_report_missing')) else type(failure).__name__
        print('Local two-host rehearsal stopped; private diagnostics preserved. Category: '+error,flush=True)
    finally:rehearsal.cleanup()
    diagnostics=(rehearsal.home/'diagnostics.log').read_text(errors='replace')
    php_diagnostics={kind:len(re.findall('PHP '+kind,diagnostics)) for kind in ['Warning','Notice','Fatal error','Deprecated']}
    rehearsal.rows.append({'name':'No PHP warnings/notices/fatals/deprecations in rehearsal','passed':not any(php_diagnostics.values())})
    report={'date':'2026-10-28','scope':'Two local fictional hosts; no provider, production or publication','tests':rehearsal.rows,'details':details,'scenarios':rehearsal.scenarios,'php_diagnostics':php_diagnostics,'error':error,'summary':{'total':len(rehearsal.rows),'passed':sum(x['passed'] for x in rehearsal.rows),'failed':sum(not x['passed'] for x in rehearsal.rows)}}
    (rehearsal.results/'two-host.json').write_text(json.dumps(report,indent=2)+'\n');print(json.dumps({'report':str(rehearsal.results/'two-host.json'),'summary':report['summary']}));sys.exit(1 if error or report['summary']['failed'] else 0)

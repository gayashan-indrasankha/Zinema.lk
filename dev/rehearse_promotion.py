"""Promotion controls on fresh local targets. No route/ports/provider/publish."""
import hashlib,io,json,secrets,sys,time,re,subprocess,tarfile,zipfile,shutil
from pathlib import Path
import local,rehearse_staging
from build_stage_artifact import build
ROOT=local.ROOT
class Promotion(rehearse_staging.Rehearsal):
    def __init__(self,tree):
        super().__init__();self.tree=tree;self.apps={};self.extra={};self.clip=None
    def env(self,production=False):
        args=super().env(production)
        if production:args+=['-e','UPLOAD_QUOTA_MODE=staging','-e','ZINEMA_PROMOTION_FIXTURES=1']
        return args
    def cli(self,db,file,payload=None):
        result=super().cli(db,file,payload)
        if file=='product_fixture.php':self.clip=json.loads(result)['clip']
        return result
    def web(self,db):
        result=super().web(db);self.apps[db['side']]=result[0]
        # The helper is outside the public image; mount it only in a short-lived CLI.
        args=['run','--rm','-i','--network','container:'+db['name'],*self.label,*self.env(True),*self.mounts(db),*self.mount(ROOT/'dev/promotion_probe.php','/opt/promotion_probe.php'),self.image,'php','/opt/promotion_probe.php']
        report=json.loads(self.docker(*args).stdout);self.extra[db['side']+'_quota']=report;self.check(db['side']+' production-config quota assertions',report['summary']['failed']==0)
        return result
    def private_http(self,db,mode,socket_volume):
        result=self.docker('run','--rm','--network','container:'+db['name'],*self.label,*self.mount(ROOT/'dev/promotion_http.py','/opt/promotion_http.py'),*self.mount(ROOT/'dev/staging_http.py','/opt/staging_http.py'),*self.mount(self.home/'tls/ca.pem','/run/ca.pem'),*self.mount(self.home/db['side']/'demo_password','/run/demo_password'),*self.mount(self.results,'/results',False),*self.mount(socket_volume,'/run/zinema',False,'volume'),rehearse_staging.PY_IMAGE,'python','-B','/opt/promotion_http.py',mode,allow=True)
        report=json.loads((self.results/('promotion-'+mode+'.json')).read_text());self.extra['private_'+mode]=report;self.check('Private socket '+mode+' administrator assertions',result.returncode==0 and report['summary']['failed']==0)
    def socket_rehearsal(self,db):
        volume=self.volume(db['side'],'socket');key=self.home/db['side']/'extractor_key';key.write_text(secrets.token_hex(32))
        self.docker('run','--rm','--network','none',*self.label,*self.mount(volume,'/run/zinema',False,'volume'),'--entrypoint','sh',local.PROJECT+'-web','-c','chown 33:33 /run/zinema && chmod 0770 /run/zinema')
        image=self.image+'-extractor';self.docker('build','--file',ROOT/'dev/extractor.Dockerfile','--tag',image,ROOT)
        env={'APP_ENV':'production','EXTRACTOR_MODE':'private-fixture','ALLOW_LIVE_INTEGRATIONS':'false','ZINEMA_PROMOTION_FIXTURES':'1','PORT':'3334','EXTRACTOR_SOCKET':'/run/zinema/extractor.sock','FAKE_MEDIA_URL':'https://127.0.0.1:8443/'+self.clip}
        environment=[item for k,v in env.items() for item in ['-e',k+'='+v]]
        node=self.container('private-extractor',['--network','none','--read-only','--user','33:33','--cap-drop','ALL','--memory','192m','--cpus','.5','--pids-limit','64',*environment,*self.mount(volume,'/run/zinema',False,'volume'),*self.mount(key,'/run/secrets/extractor_key'),image]);time.sleep(2)
        info=self.owned(node);self.check('Private extractor has no network/ports and uses unprivileged read-only process',info['State']['Running'] and info['HostConfig']['NetworkMode']=='none' and not info['HostConfig'].get('PortBindings') and info['HostConfig']['ReadonlyRootfs'] and info['Config']['User']=='33:33')
        original=self.apps[db['side']];self.stop(original)
        app=self.container('private-administrator',['--network','container:'+db['name'],'--memory','256m','--cpus','1','--pids-limit','128',*self.env(True),'-e','EXTRACTOR_MODE=private-fixture',*self.mounts(db),*self.mount(volume,'/run/zinema',False,'volume'),*self.mount(key,'/run/secrets/extractor_key'),self.image]);time.sleep(2)
        self.private_http(db,'healthy',volume);self.stop(node);self.private_http(db,'stopped',volume);self.stop(app)
        self.owned(original);self.docker('start',original);time.sleep(1)
    def http(self,db,mode):
        report=super().http(db,mode)
        if mode=='source':self.socket_rehearsal(db)
        return report
    def crypto(self,operation,source,target,key,allow=False):
        keys=self.home/'keys';keys.mkdir(exist_ok=True);separate=keys/'backup-key'
        if not separate.exists():separate.write_bytes(key.read_bytes())
        mirror=self.home/'mirror';mirror.mkdir(exist_ok=True)
        if operation=='decrypt' and source.name=='snapshot.encrypted':source=mirror/'snapshot.encrypted'
        request=self.home/('backup-request-'+secrets.token_hex(6)+'.json')
        request.write_text(json.dumps({'action':'backup-'+operation,'source':str(source),'target':str(target),'keyFile':str(separate),'writersQuiesced':True}))
        result=self.command(['node',str(ROOT/'ops/local-operator.cjs'),str(request)],allow=allow)
        if operation=='encrypt' and result.returncode==0:
            checksum=json.loads(result.stdout)['sha256'];request=self.home/('mirror-request-'+secrets.token_hex(6)+'.json')
            request.write_text(json.dumps({'action':'backup-mirror','source':str(target),'target':str(mirror/'snapshot.encrypted'),'expectedSha256':checksum}));self.command(['node',str(ROOT/'ops/local-operator.cjs'),str(request)])
        return result
    @staticmethod
    def digest(file):
        value=hashlib.sha256()
        with open(file,'rb') as stream:
            for chunk in iter(lambda:stream.read(65536),b''):value.update(chunk)
        return value.hexdigest()
    def valid_tar_file(self,file):
        seen=0
        with tarfile.open(file,mode='r|') as archive:
            for item in archive:
                seen+=1;parts=Path(item.name).parts
                if seen>20000 or not parts or parts[0] not in {'uploads','storage','sessions'} or any(p in {'.','..'} for p in parts) or item.name.startswith(('/','\\')) or not (item.isfile() or item.isdir()):raise RuntimeError('archive_entry_denied')
    def backup(self,source):
        probe=json.loads(self.cli(source,'staging_fixture.php',{'action':'probe'}));self.check('Source 38 tables/2 views/quota/DDLs denied',probe.get('success') and probe['tables']==38 and probe['views']==2 and probe['inventory_available'] and probe['ddl_denied'])
        payload=self.home/'payload';payload.mkdir();self.sql(source,dump=True,output_file=payload/'data.sql');(payload/'schema.sql').write_bytes((ROOT/'database/schema.sql').read_bytes());(payload/'expected.json').write_text(json.dumps(probe))
        args=[]
        for purpose in ['uploads','storage','sessions']:args+=self.mount(source['volumes'][purpose],'/source/'+purpose,True,'volume')
        self.docker('run','--rm','--network','none',*self.label,'--read-only',*args,'--entrypoint','tar',local.PROJECT+'-web','-C','/source','-cf','-','uploads','storage','sessions',output_file=payload/'files.tar');self.valid_tar_file(payload/'files.tar')
        plain=self.home/'snapshot.zip'
        with zipfile.ZipFile(plain,'w',zipfile.ZIP_DEFLATED,allowZip64=True) as output:
            for file in payload.iterdir():output.write(file,file.name)
        key=self.home/'backup-key';key.write_text(secrets.token_hex(32));encrypted=self.home/'snapshot.encrypted';self.crypto('encrypt',plain,encrypted,key)
        corrupt=self.home/'tampered.encrypted';shutil.copyfile(encrypted,corrupt)
        with corrupt.open('r+b') as stream:stream.seek(-1,2);last=stream.read(1);stream.seek(-1,2);stream.write(bytes([last[0]^1]))
        invalid=self.home/'must-not-exist.zip';result=self.crypto('decrypt',corrupt,invalid,key,True);self.check('Authenticated encryption rejects modified backup before output',result.returncode!=0 and not invalid.exists())
        restored=self.home/'authenticated.zip';self.crypto('decrypt',encrypted,restored,key);self.check('Streamed encrypted/mirrored backup matches original archive',self.digest(plain)==self.digest(restored))
        unpacked=self.home/'authenticated-payload';unpacked.mkdir();contents={}
        with zipfile.ZipFile(restored) as package:
            self.check('Backup payload allowlist',set(package.namelist())=={'data.sql','schema.sql','files.tar','expected.json'})
            for name in package.namelist():
                contents[name]=unpacked/name
                with package.open(name) as incoming,contents[name].open('xb') as outgoing:shutil.copyfileobj(incoming,outgoing,65536)
        return probe,contents,{'encrypted_bytes':encrypted.stat().st_size,'original_bytes':plain.stat().st_size,'sha256':self.digest(encrypted),'algorithm':'AES-256-GCM','key_in_archive':False,'streamed_sql_media_zip_encrypt_mirror_restore':True}
    def restore(self,target,probe,contents):
        self.check('Restore canonical schema fingerprint',self.digest(contents['schema.sql'])==self.digest(ROOT/'database/schema.sql'))
        self.sql(target,data_file=contents['data.sql']);self.restrict(target);self.valid_tar_file(contents['files.tar']);args=[]
        for purpose in ['uploads','storage','sessions']:args+=self.mount(target['volumes'][purpose],'/restore/'+purpose,False,'volume')
        self.docker('run','--rm','-i','--network','none',*self.label,*args,'--entrypoint','tar',local.PROJECT+'-web','-C','/restore','-xpf','-',data_file=contents['files.tar'])
        after=json.loads(self.cli(target,'staging_fixture.php',{'action':'probe'}))
        self.check('Restored row counts and complete row hashes match all tables',after['row_counts']==probe['row_counts'] and after['row_hashes']==probe['row_hashes'])
        self.check('Restored media/private state/session hashes all match',after['files']==probe['files'])
        self.check('Restored owned quota inventory and least privileges survive',after['inventory_available'] and after['ddl_denied'])
        return {'tables':probe['tables'],'views':probe['views'],'rows':sum(probe['row_counts'].values()),'files':len(probe['files']),'file_bytes_verified':True,'row_hashes_match':True,'file_hashes_match':True,'fixture_counts':{name:probe['row_counts'][name] for name in ['movies','series','episodes','collections','trailers']}}
    def run(self):
        rehearse_staging.build=lambda destination:build(destination,self.tree)
        result=super().run();result['artifact']['source_tree']=self.tree;result['artifact']['source_kind']='reviewed-index-tree';result['promotion']=self.extra
        metrics={'websiteReady':True,'dbReady':True,'workerEnabled':False,'workerRegistered':False,'heartbeatAgeSeconds':0,'queuePending':0,'queueOldestAgeSeconds':0,'heldMaintenance':0,'diskUsedPercent':10,'backupAgeSeconds':0,'errorCount':0};folder=self.home/'monitor';folder.mkdir()
        unavailable=bool(result['outage']['details']['alerts'])
        states=[result['source_http']['details']['monitor']['healthy'],not unavailable,not unavailable,result['restore_http']['details']['restore_http_verified']]
        for state in states:
            metrics['websiteReady']=state;request=self.home/('monitor-request-'+secrets.token_hex(6)+'.json');request.write_text(json.dumps({'action':'monitor','directory':str(folder),'metrics':metrics}));self.command(['node',str(ROOT/'ops/local-operator.cjs'),str(request)])
        monitor=json.loads((folder/'monitor-state.json').read_text());self.check('Local monitor retains one alert and recovery without duplicate unchanged alert',len(monitor['outbox'])==2 and monitor['outbox'][0]['code']=='web_unavailable' and monitor['outbox'][1]['event']=='recovered')
        result['monitor_outbox_events']=len(monitor['outbox']);return result
if __name__=='__main__':
    # Caller reviews/stages the candidate first. This creates no commit or remote ref.
    tree=subprocess.run(['git','write-tree'],cwd=ROOT,capture_output=True,text=True,check=True).stdout.strip();rehearsal=Promotion(tree);details={};error=None
    try:details=rehearsal.run()
    except Exception as failure:error=str(failure) if str(failure).startswith(('local_step_failed:','assertion_failed','fictional_db_not_ready')) else type(failure).__name__;print('Local promotion stopped; private diagnostics preserved. Category: '+error,flush=True)
    finally:rehearsal.cleanup()
    diagnostics=(rehearsal.home/'diagnostics.log').read_text(errors='replace');report={'date':'2026-10-26','scope':'Owned local promotion fixtures only; no external adapter/production/publishing','tests':rehearsal.rows,'details':details,'error':error,'summary':{'total':len(rehearsal.rows),'passed':sum(row['passed'] for row in rehearsal.rows),'failed':sum(not row['passed'] for row in rehearsal.rows)},'php_diagnostics':{name:len(re.findall('PHP '+name,diagnostics)) for name in ['Warning','Notice','Fatal error','Deprecated']},'launch_decision':'NO GO; host/provider acceptance and owner approval remain pending'}
    (rehearsal.results/'promotion.json').write_text(json.dumps(report,indent=2)+'\n');print(json.dumps({'report':str(rehearsal.results/'promotion.json'),'summary':report['summary']}));sys.exit(1 if error or report['summary']['failed'] or any(report['php_diagnostics'].values()) else 0)

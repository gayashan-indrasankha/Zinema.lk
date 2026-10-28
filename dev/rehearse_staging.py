"""No published ports, no providers. New owned fixture targets; preserve all volumes.
Commands capture diagnostics privately. Public reports never contain DB rows/keys.
Run: python -X utf8 dev/rehearse_staging.py
"""
import hashlib,io,json,os,secrets,subprocess,sys,tarfile,time,zipfile
from pathlib import Path
import local
from build_stage_artifact import build
ROOT=local.ROOT
DB_IMAGE='mariadb@sha256:0d0945c57f75b3a3b9ebe71aedaaad005fa5305054e8fca3d2e42a8c92b43b5e'
NGINX_IMAGE='nginx@sha256:47928bf67eb62a1863cb08d9ecb52d3e704304ba614eaee17884204677702951'
PY_IMAGE='python@sha256:b658b05ce9c4eb97ec6fc19424818a1f31993659c2b505a532f470e83807815d'
DB_NAME='zinema_recovery_development';DB_USER='zinema_recovery_dev'
class Rehearsal:
    def __init__(self):
        local.require_local_docker();self.case='stg-'+secrets.token_hex(6);self.home=ROOT/'.runtime/staging-rehearsals'/self.case
        self.home.mkdir(parents=True);self.containers=[];self.volumes=[];self.rows=[]
        (self.home/'owner.json').write_text(json.dumps({'case':self.case,'root':str(ROOT),'purpose':'fictional-local-rehearsal'}))
        self.diag=open(self.home/'diagnostics.log','ab');self.image=local.PROJECT+'-'+self.case
        self.label=['--label','zinema.rehearsal.case='+self.case,'--label','zinema.rehearsal.owner='+local.PROJECT]
        for side in ['source','restore']:
            folder=self.home/side;folder.mkdir()
            for name in ['db_root','db_password','oauth_secret','adapter_key','demo_password']:
                (folder/name).write_text(secrets.token_hex(32));os.chmod(folder/name,0o600)
        # Restore accounts are copied from source; only the two DB identities differ.
        (self.home/'restore/demo_password').write_bytes((self.home/'source/demo_password').read_bytes())
        self.results=self.home/'results';self.results.mkdir()
    def command(self,args,data=None,allow=False,output_file=None,data_file=None):
        if data is not None and data_file is not None:raise RuntimeError('ambiguous_input')
        streams=[]
        try:
            options={'stderr':subprocess.PIPE,'stdout':subprocess.PIPE}
            for value in [output_file,data_file]:
                if value is not None and self.home.resolve() not in Path(value).resolve().parents:raise RuntimeError('private_stream_boundary')
            if output_file is not None:
                stream=open(output_file,'xb');streams.append(stream);options['stdout']=stream
            if data_file is not None:
                stream=open(data_file,'rb');streams.append(stream);options['stdin']=stream
            else:options['input']=data
            result=subprocess.run(args,cwd=ROOT,env=local.ENV,**options)
        finally:
            for stream in streams:stream.close()
        self.diag.write(result.stderr);self.diag.flush()
        if result.returncode and not allow:
            self.diag.write(result.stdout or b'');self.diag.flush();raise RuntimeError('local_step_failed:'+Path(args[0]).name)
        return result
    def docker(self,*args,**kw):return self.command(['docker',*map(str,args)],**kw)
    def check(self,name,value):
        self.rows.append({'name':name,'passed':bool(value)});print(json.dumps({'check':name,'passed':bool(value)}),flush=True)
        if not value:raise RuntimeError('assertion_failed')
    def mount(self,source,target,readonly=True,kind='bind'):
        return ['--mount',f'type={kind},source={source},target={target}'+(',readonly' if readonly else '')]
    def container(self,name,args):
        full=self.image+'-'+name
        self.docker('create','--name',full,*self.label,'--security-opt','no-new-privileges:true',*args)
        self.containers.append(full);self.docker('start',full);return full
    def owned(self,name):
        info=json.loads(self.docker('inspect',name).stdout)[0]
        if info['Config']['Labels'].get('zinema.rehearsal.case')!=self.case or info['Config']['Labels'].get('zinema.rehearsal.owner')!=local.PROJECT:raise RuntimeError('ownership_mismatch')
        return info
    def stop(self,name):self.owned(name);self.docker('stop','--time','15',name)
    def volume(self,side,purpose):
        name=self.image+'-'+side+'-'+purpose
        if self.docker('volume','inspect',name,allow=True).returncode==0:raise RuntimeError('fresh_volume_required')
        self.docker('volume','create',*self.label,name)
        labels=json.loads(self.docker('volume','inspect',name).stdout)[0]['Labels']
        if labels.get('zinema.rehearsal.case')!=self.case or labels.get('zinema.rehearsal.owner')!=local.PROJECT:raise RuntimeError('volume_ownership_mismatch')
        self.volumes.append(name);return name
    def db(self,side):
        folder=self.home/side;vol=self.volume(side,'database')
        args=['--network','none','--memory','384m','--cpus','1','--pids-limit','128',*self.mount(vol,'/var/lib/mysql',False,'volume'),*self.mount(ROOT/'database/schema.sql','/docker-entrypoint-initdb.d/001-schema.sql')]
        for name in ['db_root','db_password']:args+=self.mount(folder/name,'/run/secrets/'+name)
        for key,value in {'MARIADB_DATABASE':DB_NAME,'MARIADB_USER':DB_USER,'MARIADB_PASSWORD_FILE':'/run/secrets/db_password','MARIADB_ROOT_PASSWORD_FILE':'/run/secrets/db_root'}.items():args+=['-e',key+'='+value]
        db=self.container(side+'-db',[*args,DB_IMAGE,'--bind-address=127.0.0.1','--skip-name-resolve'])
        for _ in range(80):
            r=self.docker('exec',db,'healthcheck.sh','--connect','--innodb_initialized',allow=True)
            if r.returncode==0:break
            time.sleep(1)
        else:raise RuntimeError('fictional_db_not_ready')
        self.check(side+' DB ownership/no network/ports',self.owned(db)['HostConfig']['NetworkMode']=='none' and not self.owned(db)['HostConfig'].get('PortBindings'))
        state={p:self.volume(side,p) for p in ['uploads','storage','sessions']}
        return {'name':db,'side':side,'volumes':state}
    def env(self,production=False):
        values={'APP_ENV':'production' if production else 'development','ALLOW_LIVE_INTEGRATIONS':'false','BASE_URL':'https://127.0.0.1:8443' if production else 'http://127.0.0.1:8088','DB_HOST':'127.0.0.1','DB_PORT':'3306','DB_NAME':DB_NAME,'DB_USER':DB_USER,'SESSION_SAVE_PATH':'/var/lib/zinema/sessions','TRUSTED_PROXY_IPS':'127.0.0.1','UPLOAD_QUOTA_MODE':'disabled' if production else 'local','UPLOAD_STORAGE_OWNER':local.PROJECT,'UPLOAD_STORAGE_STATE':'/var/lib/zinema/storage','UPLOAD_MAX_BYTES':'67108864','UPLOAD_MAX_FILES':'500','UPLOAD_QUARANTINE_MAX_BYTES':'67108864','UPLOAD_QUARANTINE_MAX_FILES':'500','ZINEMA_STAGING_FIXTURES':'1','ZINEMA_STAGING_CASE':self.case}
        values.update({a+'_MODE':'disabled' for a in ['MAIL','OAUTH','WHATSAPP','MEDIA','EXTRACTOR','PAYMENT']})
        return [entry for key,value in values.items() for entry in ['-e',key+'='+value]]
    def mounts(self,db):
        args=[]
        for purpose,target in [('uploads','/var/www/html/uploads'),('storage','/var/lib/zinema/storage'),('sessions','/var/lib/zinema/sessions')]:args+=self.mount(db['volumes'][purpose],target,False,'volume')
        for name in ['db_password','oauth_secret','adapter_key','demo_password']:args+=self.mount(self.home/db['side']/name,'/run/secrets/'+name)
        return args
    def cli(self,db,file,payload=None):
        self.owned(db['name'])
        result=self.docker('run','--rm','-i','--network','container:'+db['name'],*self.label,*self.env(),*self.mounts(db),local.PROJECT+'-web','php','/opt/zinema/dev/'+file,data=json.dumps(payload).encode() if payload is not None else None)
        return result.stdout
    def sql(self,db,sql=None,dump=False,output_file=None,data_file=None):
        self.owned(db['name'])
        # Constant shell text. Password travels only through the existing mounted file.
        script='umask 077; cnf=$(mktemp /dev/shm/zinema.XXXXXX); trap \'rm -f "$cnf"\' EXIT; printf "[client]\\nuser=root\\npassword=%s\\n" "$(cat /run/secrets/db_root)" > "$cnf"; '
        script+=('exec mariadb-dump --defaults-extra-file="$cnf" --single-transaction --no-create-info --skip-add-drop-table --skip-triggers --skip-lock-tables --hex-blob --skip-comments '+DB_NAME if dump else 'mariadb --defaults-extra-file="$cnf" --batch --skip-column-names '+DB_NAME)
        # No exec: preserve the trap so the private options file is removed on exit.
        script=script.replace('exec mariadb-dump','mariadb-dump')
        return self.docker('exec','-i',db['name'],'sh','-c',script,data=sql,output_file=output_file,data_file=data_file).stdout
    def restrict(self,db):
        self.check(db['side']+' exact database before grants',self.sql(db,b'SELECT DATABASE(),@@port;').strip()==(DB_NAME+'\t3306').encode())
        self.sql(db,(f"REVOKE ALL PRIVILEGES, GRANT OPTION FROM '{DB_USER}'@'%'; GRANT SELECT, INSERT, UPDATE, DELETE ON `{DB_NAME}`.* TO '{DB_USER}'@'%';").encode())
    def tls(self):
        tls=self.home/'tls';tls.mkdir();openssl=r'C:\xampp\apache\bin\openssl.exe'
        local.ENV['OPENSSL_CONF']=r'C:\xampp\apache\conf\openssl.cnf'
        self.command([openssl,'req','-x509','-newkey','rsa:2048','-nodes','-days','2','-keyout',str(tls/'ca.key'),'-out',str(tls/'ca.pem'),'-subj','/CN=Zinema Fictional Rehearsal CA','-addext','basicConstraints=critical,CA:TRUE','-addext','keyUsage=critical,keyCertSign,cRLSign'])
        self.command([openssl,'req','-newkey','rsa:2048','-nodes','-keyout',str(tls/'server.key'),'-out',str(tls/'server.csr'),'-subj','/CN=localhost'])
        ext=tls/'extensions';ext.write_text('subjectAltName=IP:127.0.0.1,DNS:localhost\nbasicConstraints=critical,CA:FALSE\nkeyUsage=critical,digitalSignature,keyEncipherment\nextendedKeyUsage=serverAuth\n')
        self.command([openssl,'x509','-req','-in',str(tls/'server.csr'),'-CA',str(tls/'ca.pem'),'-CAkey',str(tls/'ca.key'),'-CAcreateserial','-out',str(tls/'server.pem'),'-days','2','-extfile',str(ext)])
    def web(self,db):
        app=self.container(db['side']+'-app',['--network','container:'+db['name'],'--memory','256m','--cpus','1','--pids-limit','128',*self.env(True),*self.mounts(db),self.image])
        proxy=self.container(db['side']+'-tls',['--network','container:'+db['name'],'--user','101:101','--read-only','--tmpfs','/tmp:rw,noexec,nosuid,size=32m','--cap-drop','ALL','--memory','64m','--cpus','.5','--pids-limit','32',*self.mount(ROOT/'dev/staging/nginx.conf','/etc/nginx/nginx.conf'),*self.mount(self.home/'tls/server.pem','/run/tls/server.pem'),*self.mount(self.home/'tls/server.key','/run/tls/server.key'),'--entrypoint','nginx',NGINX_IMAGE,'-g','daemon off;'])
        for name in [app,proxy]:
            info=self.owned(name);values=dict(value.split('=',1) for value in info['Config']['Env'] if '=' in value)
            self.check(name.rsplit('-',2)[-2]+' private container/limits',info['HostConfig']['NetworkMode']=='container:'+self.owned(db['name'])['Id'] and not info['HostConfig'].get('PortBindings') and info['HostConfig']['Memory']>0)
            if name==app:self.check(db['side']+' all provider modes disabled',all(values.get(a+'_MODE')=='disabled' for a in ['MAIL','OAUTH','WHATSAPP','MEDIA','EXTRACTOR','PAYMENT']) and values.get('ALLOW_LIVE_INTEGRATIONS')=='false')
        time.sleep(2);return app,proxy
    def http(self,db,mode):
        result=self.docker('run','--rm','--network','container:'+db['name'],*self.label,'--memory','256m','--cpus','1','--pids-limit','64',*self.mount(ROOT/'dev/staging_http.py','/opt/staging_http.py'),*self.mount(self.home/'tls/ca.pem','/run/ca.pem'),*self.mount(self.home/db['side']/'demo_password','/run/demo_password'),*self.mount(self.results,'/results',False),PY_IMAGE,'python','-B','/opt/staging_http.py',mode,allow=True)
        report=json.loads((self.results/(mode+'.json')).read_text());self.check(mode+' TLS/security/load assertions',result.returncode==0 and report['summary']['failed']==0);return report
    def backup(self,source):
        probe=json.loads(self.cli(source,'staging_fixture.php',{'action':'probe'}));self.check('Source 38 tables/2 views/quota/DDLs denied',probe.get('success') and probe['tables']==38 and probe['views']==2 and probe['inventory_available'] and probe['ddl_denied'])
        payload=self.home/'payload';payload.mkdir();(payload/'data.sql').write_bytes(self.sql(source,dump=True));(payload/'schema.sql').write_bytes((ROOT/'database/schema.sql').read_bytes());(payload/'expected.json').write_text(json.dumps(probe))
        args=[]
        for purpose in ['uploads','storage','sessions']:args+=self.mount(source['volumes'][purpose],'/source/'+purpose,True,'volume')
        archive=self.docker('run','--rm','--network','none',*self.label,'--read-only',*args,'--entrypoint','tar',local.PROJECT+'-web','-C','/source','-cf','-','uploads','storage','sessions').stdout
        self.valid_tar(archive);(payload/'files.tar').write_bytes(archive)
        plain=self.home/'snapshot.zip'
        with zipfile.ZipFile(plain,'w',zipfile.ZIP_DEFLATED) as output:
            for file in payload.iterdir():output.write(file,file.name)
        key=self.home/'backup-key';key.write_text(secrets.token_hex(32));os.chmod(key,0o600)
        encrypted=self.home/'snapshot.encrypted';self.crypto('encrypt',plain,encrypted,key)
        corrupt=self.home/'tampered.encrypted';data=bytearray(encrypted.read_bytes());data[-1]^=1;corrupt.write_bytes(data)
        invalid=self.home/'must-not-exist.zip';result=self.crypto('decrypt',corrupt,invalid,key,True);self.check('Authenticated encryption rejects modified backup before output',result.returncode!=0 and not invalid.exists())
        restored=self.home/'authenticated.zip';self.crypto('decrypt',encrypted,restored,key);self.check('Encrypted backup round trip byte equality',plain.read_bytes()==restored.read_bytes())
        with zipfile.ZipFile(restored) as package:
            self.check('Backup payload allowlist',set(package.namelist())=={'data.sql','schema.sql','files.tar','expected.json'})
            contents={name:package.read(name) for name in package.namelist()}
        return probe,contents,{'encrypted_bytes':encrypted.stat().st_size,'original_bytes':plain.stat().st_size,'sha256':hashlib.sha256(encrypted.read_bytes()).hexdigest(),'algorithm':'AES-256-GCM','key_in_archive':False}
    def crypto(self,operation,source,target,key,allow=False):return self.command(['node',str(ROOT/'dev/backup_crypto.cjs'),operation,str(source),str(target),str(key)],allow=allow)
    @staticmethod
    def valid_tar(raw):
        with tarfile.open(fileobj=io.BytesIO(raw)) as archive:
            for item in archive:
                parts=Path(item.name).parts
                if not parts or parts[0] not in {'uploads','storage','sessions'} or any(p in {'.','..'} for p in parts) or item.name.startswith(('/','\\')) or not (item.isfile() or item.isdir()):raise RuntimeError('archive_entry_denied')
    def restore(self,target,probe,contents):
        self.check('Restore canonical schema fingerprint',contents['schema.sql']==(ROOT/'database/schema.sql').read_bytes())
        self.sql(target,contents['data.sql']);self.restrict(target);self.valid_tar(contents['files.tar']);args=[]
        for purpose in ['uploads','storage','sessions']:args+=self.mount(target['volumes'][purpose],'/restore/'+purpose,False,'volume')
        self.docker('run','--rm','-i','--network','none',*self.label,*args,'--entrypoint','tar',local.PROJECT+'-web','-C','/restore','-xpf','-',data=contents['files.tar'])
        after=json.loads(self.cli(target,'staging_fixture.php',{'action':'probe'}))
        self.check('Restored row counts and complete row hashes match all tables',after['row_counts']==probe['row_counts'] and after['row_hashes']==probe['row_hashes'])
        self.check('Restored media/private state/session hashes all match',after['files']==probe['files'])
        self.check('Restored owned quota inventory and least privileges survive',after['inventory_available'] and after['ddl_denied'])
        return {'tables':probe['tables'],'views':probe['views'],'rows':sum(probe['row_counts'].values()),'files':len(probe['files']),'file_bytes_verified':True,'row_hashes_match':True,'file_hashes_match':True,'fixture_counts':{name:probe['row_counts'][name] for name in ['movies','series','episodes','collections','trailers']}}
    def run(self):
        print('Creating isolated source/restore rehearsal with no published ports.',flush=True)
        # Build only; never starts/modifies the normal development stack.
        self.command(local.BASE+['build','web'])
        manifest=build(self.home/'artifact');self.command(['docker','build','--tag',self.image,str(self.home/'artifact')]);self.tls()
        source=self.db('source');self.cli(source,'seed.php');fixture=json.loads(self.cli(source,'product_fixture.php',{'run':secrets.token_hex(6)}))
        allowed={k:fixture[k] for k in ['cover','clip','collection']};seed=json.loads(self.cli(source,'staging_fixture.php',{'action':'populate','fixture':allowed}));self.check('Expanded original fictional catalogue seeded',seed.get('success'))
        self.restrict(source);app,proxy=self.web(source);source_http=self.http(source,'source')
        resources={}
        for component,name in [('database',source['name']),('application',app),('tls',proxy)]:
            self.owned(name);sample=json.loads(self.docker('stats','--no-stream','--format','{{json .}}',name).stdout)
            resources[component]={key:sample[key] for key in ['CPUPerc','MemUsage','MemPerc','PIDs','BlockIO','NetIO']}
        self.stop(app);outage=self.http(source,'outage')
        probe,contents,backup=self.backup(source);target=self.db('restore');comparison=self.restore(target,probe,contents);self.web(target);restore_http=self.http(target,'restore')
        self.check('Restored upload quota admits a new original image',json.loads(self.cli(target,'staging_fixture.php',{'action':'admission'})).get('success'))
        return {'artifact':{'source_commit':manifest['source_commit'],'files':len(manifest['files']),'forbidden_paths':manifest['forbidden_paths']},'source_http':source_http,'outage':outage,'restore_http':restore_http,'backup':backup,'comparison':comparison,'resources_sampled_after_load_not_peak':resources,'local_only':True,'providers_contacted':False,'public_ports':False,'retained_fixture_volumes':len(self.volumes)}
    def cleanup(self):
        for name in reversed(self.containers):
            try:
                self.owned(name);logs=self.docker('logs',name,allow=True);self.diag.write(logs.stdout+logs.stderr)
                self.stop(name);self.docker('rm',name)
            except Exception:self.rows.append({'name':'Owned container cleanup','passed':False})
        self.diag.close()
if __name__=='__main__':
    rehearsal=Rehearsal();details={};error=None
    try:details=rehearsal.run()
    except Exception as failure:error=str(failure) if str(failure).startswith(('local_step_failed:','assertion_failed','fictional_db_not_ready')) else type(failure).__name__;print('Rehearsal stopped; private diagnostics preserved. Category: '+error,flush=True)
    finally:rehearsal.cleanup()
    report={'date':'2026-10-26','scope':'Isolated local production-configuration rehearsal; fictional data; all providers disabled','tests':rehearsal.rows,'details':details,'error':error,'summary':{'total':len(rehearsal.rows),'passed':sum(row['passed'] for row in rehearsal.rows),'failed':sum(not row['passed'] for row in rehearsal.rows)},'launch_decision':'NO GO; real hosting/provider/large-media/operations acceptance remains pending'}
    (rehearsal.results/'rehearsal.json').write_text(json.dumps(report,indent=2)+'\n');print(json.dumps({'report':str(rehearsal.results/'rehearsal.json'),'summary':report['summary']}));sys.exit(1 if error or report['summary']['failed'] else 0)

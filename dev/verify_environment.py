"""Inspect only this checkout's Compose containers. Emit assertions, never environment values."""
import json
from pathlib import Path
import subprocess
import sys
from local import BASE, ENV, PROJECT, ROOT, STATE, require_local_docker

require_local_docker()


def run(args):
    result = subprocess.run(args, cwd=ROOT, env=ENV, capture_output=True, text=True)
    if result.returncode: raise RuntimeError('Owned environment inspection failed')
    return result.stdout


rows=[]
def check(name, passed): rows.append({'name':name,'passed':bool(passed)})


services={}
for service in ['web','db','fakes','gateway']:
    identity=run(BASE+['ps','-q',service]).strip()
    if not identity: raise SystemExit('Expected owned services are not running')
    info=json.loads(run(['docker','inspect',identity]))[0]
    if info['Config']['Labels'].get('com.docker.compose.project') != PROJECT: raise SystemExit('Container ownership mismatch')
    services[service]=info

network=json.loads(run(['docker','network','inspect',PROJECT+'_isolated']))[0]
check('Application network is internal',network['Internal'] is True)
check('Only gateway has an external network',set(services['web']['NetworkSettings']['Networks'])=={PROJECT+'_isolated'} and set(services['gateway']['NetworkSettings']['Networks'])=={PROJECT+'_isolated',PROJECT+'_edge'})
for service in ['db','fakes']:
    check(service+' shares the guarded loopback namespace',services[service]['HostConfig']['NetworkMode']=='container:'+services['web']['Id'])
bindings=[]
for service,info in services.items():
    for port,values in (info['HostConfig'].get('PortBindings') or {}).items():
        for binding in values or []: bindings.append((service,port,binding['HostIp'],binding['HostPort']))
check('Exactly two loopback gateway ports are published',set(bindings)=={('gateway','8088/tcp','127.0.0.1','8088'),('gateway','9081/tcp','127.0.0.1','9081')})
check('Gateway has no private mounts',not services['gateway']['Mounts'])
for service,info in services.items():
    check(service+' is not privileged',not info['HostConfig']['Privileged'])
    allowed={'/run/secrets/db_password','/run/secrets/db_root','/run/secrets/oauth_secret','/run/secrets/adapter_key','/run/secrets/demo_password','/run/secrets/control_key','/docker-entrypoint-initdb.d/001-schema.sql'}
    check(service+' binds only allowlisted schema/secret inputs',all(m['Type']=='volume' or (m['Type']=='bind' and m['Destination'] in allowed and not m['RW']) for m in info['Mounts']))
env=dict(item.split('=',1) for item in services['web']['Config']['Env'] if '=' in item)
check('All production-capable adapters remain disabled',env.get('APP_ENV')=='development' and env.get('ALLOW_LIVE_INTEGRATIONS')=='false' and all(env.get(name+'_MODE')=='disabled' for name in ['WHATSAPP','MEDIA','EXTRACTOR','PAYMENT']))
check('Mail and OAuth target only local fakes',env.get('MAIL_HOST')=='127.0.0.1' and env.get('MAIL_PORT')=='1025' and env.get('OAUTH_TOKEN_URL')=='http://127.0.0.1:9081/token')
check('Upload quota is enabled only for this development owner',env.get('UPLOAD_QUOTA_MODE')=='local' and env.get('UPLOAD_STORAGE_OWNER')==PROJECT and env.get('UPLOAD_STORAGE_STATE')=='/var/lib/zinema/storage')
check('Private storage state is a separate non-public named volume',any(m['Type']=='volume' and m['Destination']=='/var/lib/zinema/storage' for m in services['web']['Mounts']))
check('Quarantine is bounded separately from active uploads',env.get('UPLOAD_QUARANTINE_MAX_BYTES')=='67108864' and env.get('UPLOAD_QUARANTINE_MAX_FILES')=='500')
owner_php="['owner'=>$owner,'database'=>$database]=json_decode(file_get_contents('/var/lib/zinema/storage/owner.json'),true); echo $owner===getenv('UPLOAD_STORAGE_OWNER') && $database===getenv('DB_NAME') ? 'owned' : 'denied';"
check('Storage marker matches the owned development target',run(BASE+['exec','-T','web','php','-r',owner_php]).strip()=='owned')
php="foreach (['.env','Database.sql','.git','.runtime','.recovery-private'] as $name) { if (file_exists('/var/www/html/'.$name)) exit(1); } echo 'contained';"
check('App image contains no legacy/private root inputs',run(BASE+['exec','-T','web','php','-r',php]).strip()=='contained')
result={'scope':'Owned Compose metadata and allowlisted image paths; no secret values retained','tests':rows,'summary':{'total':len(rows),'passed':sum(row['passed'] for row in rows),'failed':sum(not row['passed'] for row in rows)}}
(STATE/'results/environment.json').write_text(json.dumps(result,indent=2)+'\n')
print(json.dumps(result['summary']))
sys.exit(1 if result['summary']['failed'] else 0)

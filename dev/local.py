"""One local entry point. Generated credentials stay under ignored .runtime/.
Uses the Docker engine only; never pushes or runs against production services.
"""
import argparse
import hashlib
import json
import os
from pathlib import Path
import secrets
import subprocess
import sys

ROOT=Path(__file__).resolve().parent.parent
STATE=ROOT/'.runtime/local-dev'
PROJECT='zinema-dev-'+hashlib.sha256(str(ROOT).encode()).hexdigest()[:8]
ENV=dict(os.environ,ZINEMA_DEV_STATE=STATE.as_posix(),ZINEMA_DEV_PROJECT=PROJECT)
BASE=['docker','compose','--env-file',str(ROOT/'dev/compose.env'),'--project-name',PROJECT,'--file',str(ROOT/'dev/compose.yaml')]
_DOCKER_CHECKED=False


def require_local_docker():
    global _DOCKER_CHECKED
    if _DOCKER_CHECKED: return
    # Context inspection reads client configuration; it does not contact its daemon.
    if ENV.get('DOCKER_HOST'): raise RuntimeError('Docker host overrides are disabled; select a local context')
    context=ENV.get('DOCKER_CONTEXT')
    if not context:
        result=subprocess.run(['docker','context','show'],env=ENV,capture_output=True,text=True)
        if result.returncode: raise RuntimeError('Local Docker context unavailable')
        context=result.stdout.strip()
    result=subprocess.run(['docker','context','inspect',context],env=ENV,capture_output=True,text=True)
    if result.returncode: raise RuntimeError('Local Docker context unavailable')
    endpoint=json.loads(result.stdout)[0]['Endpoints']['docker']['Host']
    if not isinstance(endpoint,str) or not (endpoint.startswith('unix:///') or endpoint.startswith('npipe:////./pipe/')):
        raise RuntimeError('Remote Docker contexts are disabled')
    # Pin this process to the inspected context even if the global selection changes.
    ENV['DOCKER_CONTEXT']=context
    _DOCKER_CHECKED=True


def compose(*args):
    require_local_docker()
    result=subprocess.run(BASE+list(args),cwd=ROOT,env=ENV)
    if result.returncode: raise SystemExit(result.returncode)


def require_owned_gateway():
    # Test control keys must never be posted to an unrelated loopback listener.
    require_local_docker()
    result=subprocess.run(BASE+['ps','-q','gateway'],cwd=ROOT,env=ENV,capture_output=True,text=True)
    if result.returncode or not result.stdout.strip(): raise RuntimeError('Start the owned local stack first')
    result=subprocess.run(['docker','inspect',result.stdout.strip()],env=ENV,capture_output=True,text=True)
    if result.returncode: raise RuntimeError('Owned local gateway unavailable')
    info=json.loads(result.stdout)[0]
    bindings=info.get('NetworkSettings',{}).get('Ports',{}).get('8088/tcp') or []
    if info.get('State',{}).get('Running') is not True or info.get('Config',{}).get('Labels',{}).get('com.docker.compose.project') != PROJECT or not any(
            binding.get('HostIp')=='127.0.0.1' and binding.get('HostPort')=='8088' for binding in bindings):
        raise RuntimeError('Owned loopback gateway is not ready')


def initialize():
    marker = STATE/'ownership.json'
    if marker.exists():
        owner = json.loads(marker.read_text())
        if owner.get('project') != PROJECT or owner.get('root') != str(ROOT):
            raise SystemExit('Local state ownership mismatch; preserve the directory and review.')
    STATE.mkdir(parents=True,exist_ok=True)
    for directory in [STATE.parent, STATE]:
        denial=directory/'.htaccess'
        if not denial.exists(): denial.write_text('Require all denied\n',encoding='ascii')
    (STATE/'results').mkdir(exist_ok=True)
    for name in ['db_password','db_root','oauth_secret','adapter_key','control_key','demo_password']:
        target=STATE/name
        if not target.exists(): target.write_text(secrets.token_hex(32),encoding='ascii')
    (STATE/'ownership.json').write_text(json.dumps({'purpose':'isolated-local-development','project':PROJECT,'root':str(ROOT)},indent=2))


def operator_request(payload, service='web'):
    require_local_docker()
    owner=json.loads((STATE/'ownership.json').read_text())
    if owner.get('project') != PROJECT or owner.get('root') != str(ROOT) or service not in {'web','operator-fixtures','quarantine-fixtures'}:
        raise RuntimeError('Local operator ownership mismatch')
    result=subprocess.run(BASE+['exec','-T',service,'php','/opt/zinema/dev/operator.php'],
                          cwd=ROOT,env=ENV,input=json.dumps(payload),capture_output=True,text=True)
    try: response=json.loads(result.stdout)
    except ValueError: raise RuntimeError('Local operator unavailable; no diagnostics printed') from None
    return response


class OperatorParser(argparse.ArgumentParser):
    def error(self, _message):
        # A pasted bearer token must not be echoed in an invalid-ID diagnostic.
        self.exit(2,'Invalid operator arguments. Use operator --help; use numeric IDs, never bearer tokens.\n')


def operator_cli(arguments):
    parser=OperatorParser(description='Local-only claim review and recoverable upload cleanup')
    actions=parser.add_subparsers(dest='action',required=True)
    held=actions.add_parser('held');held.add_argument('--after-id',type=int,default=0)
    actions.add_parser('inventory')
    review=actions.add_parser('review');review.add_argument('--token-id',type=int,required=True)
    resolve=actions.add_parser('resolve')
    resolve.add_argument('--token-id',type=int,required=True)
    resolve.add_argument('--revision',type=int,required=True)
    resolve.add_argument('--outcome',choices=['delivered','not_delivered'],required=True)
    resolve.add_argument('--actor',required=True,help='Fictional demo_ operator name')
    resolve.add_argument('--evidence-ref',required=True,help='Fictional fixture: evidence reference')
    resolve.add_argument('--workers-stopped',action='store_true',help='Explicitly attest workers are stopped and evidence has been reviewed')
    for name in ['storage-review','storage-history','storage-quarantine','storage-restore','storage-recover']:
        item=actions.add_parser(name);item.add_argument('--path',required=True,help='Registered uploads/ path from inventory')
        if name in {'storage-review','storage-history'}: continue
        item.add_argument('--revision',type=int,required=True)
        item.add_argument('--actor',required=True);item.add_argument('--evidence-ref',required=True);item.add_argument('--case-ref',required=True)
        item.add_argument('--reviewed',action='store_true',help='Explicit acknowledgement of ownership and reviewed cleanup/restore facts')
        if name=='storage-recover':
            item.add_argument('--operation-id',required=True);item.add_argument('--decision',choices=['finish','rollback'],required=True)
    response=operator_request(vars(parser.parse_args(arguments)))
    print(json.dumps(response,indent=2))
    raise SystemExit(0 if response.get('success') is True else 1)


if __name__=='__main__':
    command=sys.argv[1] if len(sys.argv)>1 else 'help'
    if command=='init': initialize(); print('Private local configuration initialized; no credentials printed.')
    elif command=='up':
        initialize(); compose('up','--build','--wait','--wait-timeout','180','web','db','fakes','gateway')
        compose('exec','-T','web','php','/opt/zinema/dev/seed.php')
        print('Local app: http://127.0.0.1:8088 ; fake services only. No real bot is started.')
    elif command=='test':
        require_local_docker()
        unit=subprocess.run(BASE+['exec','-T','web','php','/opt/zinema/dev/unit.php'],cwd=ROOT,env=ENV,capture_output=True,text=True)
        if unit.returncode: raise SystemExit('PHP unit checks failed; no sensitive diagnostics printed.')
        results=json.loads(unit.stdout)
        (STATE/'results/oauth-unit.json').write_text(json.dumps(results,indent=2)+'\n')
        print('OAuth state/PKCE unit checks:',results['passed'],'/',results['total'],flush=True)
        compose('run','--build','--rm','--no-deps','tests')
    elif command=='down': compose('down','--remove-orphans')
    elif command=='operator':
        try: operator_cli(sys.argv[2:])
        except (OSError,ValueError,RuntimeError):
            print(json.dumps({'success':False,'error':'local_operator_unavailable'}));raise SystemExit(1)
    elif command=='test-operations':
        raise SystemExit(subprocess.run([sys.executable,str(ROOT/'dev/test_operations.py')],cwd=ROOT,env=ENV).returncode)
    elif command=='test-quarantine':
        raise SystemExit(subprocess.run([sys.executable,str(ROOT/'dev/test_quarantine.py')],cwd=ROOT,env=ENV).returncode)
    elif command=='test-product':
        require_local_docker()
        require_owned_gateway()
        seed=subprocess.run(BASE+['exec','-T','web','php','/opt/zinema/dev/product_fixture.php'],cwd=ROOT,env=ENV,
            input=json.dumps({'run':secrets.token_hex(6)}),capture_output=True,text=True)
        fixture=json.loads(seed.stdout)
        if seed.returncode or fixture.get('success') is not True:raise SystemExit('Fictional product seed unavailable')
        (STATE/'results/product-fixture.json').write_text(json.dumps(fixture,indent=2)+'\n')
        compose('run','--build','--rm','--no-deps','tests','python','-B','dev/product.py')
    elif command=='test-security':
        require_local_docker();require_owned_gateway()
        result=subprocess.run(BASE+['exec','-T','web','php','/opt/zinema/dev/security_unit.php'],cwd=ROOT,env=ENV,capture_output=True,text=True)
        try:report=json.loads(result.stdout)
        except ValueError:raise SystemExit('Local security checks unavailable; diagnostics not printed') from None
        (STATE/'results/product-security.json').write_text(json.dumps(report,indent=2)+'\n')
        print(json.dumps(report.get('summary',{'error':'local_checks_unavailable'})));raise SystemExit(result.returncode)
    elif command=='status': compose('ps')
    elif command=='config': compose('config','--quiet')
    else: print('Usage: python dev/local.py init|up|test|test-operations|test-quarantine|test-product|test-security|operator|down|status|config')

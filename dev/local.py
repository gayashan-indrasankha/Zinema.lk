"""One local entry point. Generated credentials stay under ignored .runtime/.
Uses the Docker engine only; never pushes or runs against production services.
"""
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
ENV=dict(os.environ,ZINEMA_DEV_STATE=STATE.as_posix())
BASE=['docker','compose','--project-name',PROJECT,'--file',str(ROOT/'dev/compose.yaml')]


def compose(*args):
    result=subprocess.run(BASE+list(args),cwd=ROOT,env=ENV)
    if result.returncode: raise SystemExit(result.returncode)


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


if __name__=='__main__':
    command=sys.argv[1] if len(sys.argv)>1 else 'help'
    if command=='init': initialize(); print('Private local configuration initialized; no credentials printed.')
    elif command=='up':
        initialize(); compose('up','--build','--wait','--wait-timeout','180','web','db','fakes','gateway')
        compose('exec','-T','web','php','/opt/zinema/dev/seed.php')
        print('Local app: http://127.0.0.1:8088 ; fake services only. No real bot is started.')
    elif command=='test':
        unit=subprocess.run(BASE+['exec','-T','web','php','/opt/zinema/dev/unit.php'],cwd=ROOT,env=ENV,capture_output=True,text=True)
        if unit.returncode: raise SystemExit('PHP unit checks failed; no sensitive diagnostics printed.')
        results=json.loads(unit.stdout)
        (STATE/'results/oauth-unit.json').write_text(json.dumps(results,indent=2)+'\n')
        print('OAuth state/PKCE unit checks:',results['passed'],'/',results['total'],flush=True)
        compose('run','--build','--rm','--no-deps','tests')
    elif command=='down': compose('down','--remove-orphans')
    elif command=='status': compose('ps')
    elif command=='config': compose('config','--quiet')
    else: print('Usage: python dev/local.py init|up|test|down|status|config')

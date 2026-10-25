"""Runtime checks for extractor recovery modes and retained Node regression results.
Run after audit.py prepare and installing the temporary HTTP-only dependencies:
python docs/recovery/tests/services.py <audit-runtime>
"""
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import sys
import time
import urllib.error
import urllib.request
sys.dont_write_bytecode = True
from audit import ROOT, checked_runtime, port_open, run, wait_port, NO_WINDOW

folder = checked_runtime(sys.argv[1])
assert not port_open(3334), 'Do not reuse an unknown extractor'
rows = []
for mode in ['disabled','local']:
    env = dict(os.environ, APP_ENV='test', EXTRACTOR_MODE=mode, PORT='3334', FAKE_MEDIA_URL='http://127.0.0.1:8088/demo.mp4', NODE_PATH=str(folder/'extractor-deps/node_modules'))
    log = open(folder/f'extractor-{mode}.log','w')
    child = subprocess.Popen(['node',str(ROOT/'fb-video-api/index.js')],env=env,stdout=log,stderr=log,creationflags=NO_WINDOW)
    try:
        wait_port(3334,child)
        for route in ['/health','/api/extract?url=https%3A%2F%2Fexample.invalid%2Fnever-fetch-this']:
            try:
                response=urllib.request.urlopen('http://127.0.0.1:3334'+route,timeout=5)
            except urllib.error.HTTPError as e:
                response=e
            body=json.loads(response.read())
            if route == '/health':
                passed=response.status==200 and body.get('status')=='ok'
            elif mode=='disabled':
                passed=response.status==503 and body.get('success') is False
            else:
                passed=response.status==200 and body.get('url')==env['FAKE_MEDIA_URL'] and body.get('method')=='local-fixture'
            rows.append({'mode':mode,'route':route.split('?')[0],'http':response.status,'passed':passed})
    finally:
        child.terminate(); child.wait(timeout=5); log.close()
assert not port_open(3334)
node=[]
for suite in ['config.test.cjs','bot.test.cjs']:
    result=run(['node','--test','--test-isolation=none','--test-reporter=tap',ROOT/'docs/recovery/tests'/suite])
    text=result.stdout+result.stderr
    # All emitted values originate from fictional test fixtures.
    (ROOT/'docs/recovery/evidence'/f'repair-{suite}.tap').write_text(text,encoding='utf-8')
    node.append({'suite':suite,'exit':result.returncode,'passed':int(re.search(r'^# pass (\d+)',text,re.M)[1]),'failed':int(re.search(r'^# fail (\d+)',text,re.M)[1])})
audits=[]
bot_audit=folder/'bot-audit';bot_audit.mkdir(exist_ok=True)
for name in ['package.json','package-lock.json']:
    shutil.copyfile(ROOT/'whatsapp-bot-deploy'/name,bot_audit/name)
for label,target in [('bot-existing-lockfile',bot_audit),('extractor-temporary-http-dependencies',folder/'extractor-deps')]:
    result=run(['npm.cmd','audit','--prefix',target,'--json'],timeout=60)
    try:
        parsed=json.loads(result.stdout)
        summary=parsed.get('metadata',{}).get('vulnerabilities')
        advisories=[{'package':name,'severity':item['severity'],'advisories':sorted({via['url'] for via in item.get('via',[]) if isinstance(via,dict) and 'url' in via})} for name,item in parsed.get('vulnerabilities',{}).items()]
        audits.append({'scope':label,'exit':result.returncode,'counts':summary,'packages':advisories,'error':bool(parsed.get('error'))})
    except ValueError:
        audits.append({'scope':label,'exit':result.returncode,'error':True})
http_lock=json.loads((folder/'extractor-deps/package-lock.json').read_text())
versions={name:http_lock['packages']['node_modules/'+name]['version'] for name in ['express','cors','got']}
summary={'extractor':rows,'extractor_test_versions':versions,'extractor_processes_stopped':True,'node_suites':node,'npm_audits':audits,'limitations':['Extractor test HTTP dependencies are locked in tests/extractor-http; Chromium/Puppeteer are not exercised or audited by this subset.','Bot audit covers the repaired lockfile, not the previous installed node_modules tree or exploitability. A clean isolated install is tested separately.','Fake-client bot tests do not establish live WhatsApp delivery.']}
(ROOT/'docs/recovery/REPAIR_SERVICE_RESULTS.json').write_text(json.dumps(summary,indent=2)+'\n')
print(json.dumps(summary,indent=2))
raise SystemExit(1 if any(not row['passed'] for row in rows) or any(suite['failed'] for suite in node) or any(audit['exit'] for audit in audits) else 0)

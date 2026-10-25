"""Discoverable entry-point boundaries, not evidence that every feature works."""
import json
import sys
import urllib.request
import urllib.error
from local import ROOT,STATE,require_local_docker,require_owned_gateway
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self,*_):return None
require_local_docker();require_owned_gateway()
routes=json.loads((ROOT/'docs/recovery/PRODUCTION_ROUTE_INVENTORY.json').read_text())['routes']
rows=[]
for row in routes:
    path='/'+row['source']
    internal=row['group']=='Internal partial' or path in {'/admin/_layout.php','/admin/functions.php','/api/whatsapp/token-data.php'} or path.startswith('/api/includes/')
    try:
        response=urllib.request.build_opener(NoRedirect()).open('http://127.0.0.1:8088'+path,timeout=15);status=response.status
    except urllib.error.HTTPError as error:status=error.code
    except OSError:status=0
    passed=(status==403 if internal else status in {200,301,302,303,400,401,403,404,405,409,422,429} or status==503 and (path.startswith('/api/whatsapp/') or path in {'/whatsapp-watch.php','/google-callback.php'}))
    rows.append({'path':path,'group':row['group'],'http':status,'passed':passed,'evidence':'runtime verified boundary only'})
report={'scope':'GET entry-point/error/auth boundaries only; redirect following disabled; no live providers or functional-pass claim','tests':rows,'summary':{'total':len(rows),'passed':sum(r['passed'] for r in rows),'failed':sum(not r['passed'] for r in rows)}}
(STATE/'results/production-routes.json').write_text(json.dumps(report,indent=2)+'\n')
print(json.dumps({'summary':report['summary'],'failures':[{'path':r['path'],'http':r['http']} for r in rows if not r['passed']]}))
sys.exit(1 if report['summary']['failed'] else 0)

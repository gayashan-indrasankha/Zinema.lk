"""Actual PHP -> private Node fake contract; run only by guarded local launcher."""
import base64,json,sys
from pathlib import Path
from integration import Client,PASSWORD,control,KEY
from urllib.request import Request,urlopen
from urllib.error import HTTPError
ROWS=[]
def check(name,value):
    ROWS.append({'name':name,'passed':bool(value)});print(('PASS ' if value else 'FAIL ')+name,flush=True)
def call(client,payload,csrf=True,raw=None):
    headers={'Content-Type':'application/json'}
    if csrf:headers['X-CSRF-Token']=client.csrf('/admin/login.php')
    return client.request('/admin/extractor.php',headers=headers,raw=raw if raw is not None else json.dumps(payload).encode())
def private(path,payload=None,key=None,method='POST'):
    headers={'Content-Type':'application/json'}
    if key is not None:headers['X-Extractor-Key']=key
    req=Request('http://127.0.0.1:3334'+path,data=json.dumps(payload or {}).encode() if method=='POST' else None,headers=headers,method=method)
    try:r=urlopen(req,timeout=6)
    except HTTPError as e:r=e
    return r.status,r.read(),r.headers
def run():
    guest=Client();admin=Client();member=Client()
    admin.post('/admin/login.php',{'username':'demo_admin','password':PASSWORD})
    member.post('/ajax_login.php',{'email':'verified@example.invalid','password':PASSWORD})
    check('Member denial is exercised with a verified signed-in member',member.session())
    check('Guest with CSRF cannot call private administrator proxy',call(guest,{'action':'health'})[0]==403)
    check('Member with CSRF cannot call administrator proxy',call(member,{'action':'health'})[0]==403)
    check('Administrator proxy requires CSRF',call(admin,{'action':'health'},False)[0]==403)
    check('Proxy refuses GET extraction and method confusion',admin.request('/admin/extractor.php?action=extract&url=https://facebook.com/fictional')[0]==405)
    check('Authenticated private health traverses PHP and Node',call(admin,{'action':'health'})[2].get('mode')=='local')
    result=call(admin,{'action':'extract','url':'https://www.facebook.com/reel/fictional-original'})
    expected='http://127.0.0.1:8088/__dev/extractor-media.php'
    check('PHP extraction returns only sanitized local fake contract',result[0]==200 and result[2]=={'success':True,'mode':'local','url':expected,'method':'local-fixture','duration_ms':0})
    check('Returned original demonstration media matches fixture bytes',guest.request(expected)[1]==base64.b64decode(Path('dev/fixtures/motion-demo.mp4.b64').read_bytes()))
    for value in ['http://facebook.com/fictional','https://facebook.com.example.invalid/fictional','https://127.0.0.1/fictional','https://user@facebook.com/fictional',[],42,'https://facebook.com/'+('x'*513)]:
        check('Proxy rejects malformed/deceptive source '+str(len(ROWS)),call(admin,{'action':'extract','url':value})[0]==422)
    for value in [{'action':'unknown'},{'action':[]},{'action':'health','destination':'http://127.0.0.1:9081'},{'action':'extract','url':'https://facebook.com/fictional','key':'fictional'}]:
        check('Proxy refuses unknown fields/actions '+str(len(ROWS)),call(admin,value)[0]==400)
    check('Proxy rejects malformed JSON',call(admin,{},raw=b'{')[0]==400)
    check('Proxy bounds browser request size',call(admin,{},raw=b'{"padding":"'+b'x'*2100+b'"}')[0]==413)
    key=Path('/run/secrets/extractor_key').read_text().strip()
    check('Direct service health requires service key',private('/health')[0]==401)
    check('Direct service rejects wrong key',private('/api/extract',{'url':'https://facebook.com/fictional'},'0'*64)[0]==401)
    check('Service key cannot be supplied through query',private('/health?key=fictional')[0]==401)
    check('Direct service rejects legacy GET even with key',private('/api/extract?url=https://facebook.com/fictional',key=key,method='GET')[0]==405)
    check('Direct service exact-host URL policy enforced',private('/api/extract',{'url':'https://facebook.com.example.invalid/fictional'},key)[0]==422)
    check('Direct service bounds body size',private('/api/extract',{'url':'x'*3000},key)[0]==413)
    check('Service publishes no browser CORS permission',private('/health',key=key)[2].get('Access-Control-Allow-Origin') is None)
    page=admin.request('/admin/manage_shots.php')[1]
    check('Browser receives no private key or service address',key.encode() not in page and b'127.0.0.1:3334' not in page and b'const API_URL = \'extractor.php\'' in page)
    # Create only an exact fictional reference against a known existing demo movie.
    title='Local Test Extractor '+__import__('secrets').token_hex(6)
    movie=json.loads(Path('/results/extractor-fixture.json').read_text())['movie']
    response=admin.post('/admin/manage_shots.php',{'submit_shot':'1','title':title,'description':'Fictional local proxy fixture','content_type':'movie','linked_content_id':movie,'fb_share_url':'https://facebook.com/reel/fictional-original'})
    created=control('catalogue',table='shots',title=title)
    check('Shot save uses the same authenticated server client',response[0]==200 and created.get('path')==expected)
    if created:
        admin.post('/admin/manage_shots.php',{'delete':created['id']})
        check('Only exact created fictional shot removed',not control('catalogue',table='shots',title=title))
    # Exercise global service budget through the proxy after functional cases.
    statuses=[call(admin,{'action':'extract','url':'https://facebook.com/reel/fictional-original'})[0] for _ in range(31)]
    check('Extractor admission fails busy at bounded budget',429 in statuses and all(s in {200,429} for s in statuses))
    check('Busy service still answers authenticated health',call(admin,{'action':'health'})[0]==200)
if __name__=='__main__':
    try:run()
    except Exception as e:check('Harness completed ('+type(e).__name__+')',False)
    result={'scope':'Owned local PHP/Node only; no provider fetch/browser/real recipient','tests':ROWS,'summary':{'total':len(ROWS),'passed':sum(x['passed'] for x in ROWS),'failed':sum(not x['passed'] for x in ROWS)}}
    Path('/results/extractor-connection.json').write_text(json.dumps(result,indent=2)+'\n');print(json.dumps(result['summary']));sys.exit(1 if result['summary']['failed'] else 0)

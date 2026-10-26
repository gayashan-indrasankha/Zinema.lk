"""Fake-only admin -> PHP -> authenticated Unix extractor on isolated local TLS."""
import http.client,json,socket,sys,urllib.parse
from pathlib import Path
from staging_http import Client,check,ROWS
def run(mode):
    client=Client();password=Path('/run/demo_password').read_text().strip();token=client.csrf()
    check('Fictional admin authenticates for private socket',client.request('/admin/login.php',{'username':'demo_admin','password':password,'csrf_token':token})[0]==302)
    headers={'Content-Type':'application/json','X-CSRF-Token':client.csrf()}
    call=lambda action:client.request('/admin/extractor.php',headers=headers,raw=json.dumps(action).encode())
    if mode=='stopped':
        check('Stopped Unix extractor returns stable unavailable result',call({'action':'health'})[0]==502);return
    status,body,_=call({'action':'health'});check('Administrator health traverses authenticated private Unix socket',status==200 and json.loads(body).get('mode')=='private-fixture')
    status,body,_=call({'action':'extract','url':'https://facebook.com/reel/fictional-original'});result=json.loads(body)
    check('Private extraction returns original fixture only',status==200 and result.get('mode')=='private-fixture' and result.get('method')=='local-fixture')
    path=urllib.parse.urlparse(result.get('url','')).path
    check('Returned managed original clip is available over verified TLS',path.startswith('/uploads/') and len(client.request(path)[1])==102793)
    check('Missing CSRF cannot invoke private backend',client.request('/admin/extractor.php',headers={'Content-Type':'application/json'},raw=b'{"action":"health"}')[0]==403)
    guest=Client();check('Guest cannot invoke private backend',guest.request('/admin/extractor.php',headers={'Content-Type':'application/json','X-CSRF-Token':guest.csrf()},raw=b'{"action":"health"}')[0]==403)
    page=client.request('/admin/manage_shots.php')[1]
    check('Administrator page contains no backend credential or socket path',b'X-Extractor-Key' not in page and b'/run/zinema/extractor.sock' not in page)
    class UnixConnection(http.client.HTTPConnection):
        def connect(self):
            self.sock=socket.socket(socket.AF_UNIX,socket.SOCK_STREAM);self.sock.settimeout(3);self.sock.connect('/run/zinema/extractor.sock')
    connection=UnixConnection('localhost');connection.request('POST','/health',body=b'{}',headers={'Content-Type':'application/json','X-Extractor-Key':'0'*64});response=connection.getresponse()
    check('Unix socket also requires independent server key and exposes no CORS',response.status==401 and response.getheader('Access-Control-Allow-Origin') is None);response.read();connection.close()
if __name__=='__main__':
    mode=sys.argv[1]
    try:run(mode)
    except Exception as error:check('Private HTTP probe completed ('+type(error).__name__+')',False)
    report={'tests':ROWS,'summary':{'total':len(ROWS),'passed':sum(row['passed'] for row in ROWS),'failed':sum(not row['passed'] for row in ROWS)}}
    Path('/results/promotion-'+mode+'.json').write_text(json.dumps(report,indent=2)+'\n');print(json.dumps(report['summary']));sys.exit(bool(report['summary']['failed']))

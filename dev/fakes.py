"""Local-only SMTP sink and OAuth code-flow substitute. Never relays or fetches.
Raw fictional messages/codes stay in memory and are never logged.
"""
import asyncio
import base64
from email.utils import parseaddr
import hashlib
import hmac
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
import json
from pathlib import Path
import secrets
import threading
import time
from urllib.parse import parse_qs, urlencode, urlsplit

CONTROL = Path('/run/secrets/control_key').read_text().strip()
CLIENT_SECRET = Path('/run/secrets/oauth_secret').read_text().strip()
REDIRECT = 'http://127.0.0.1:8088/google-callback.php'
LOCK = threading.RLock()
STATE = {'messages': [], 'codes': {}, 'access': {}, 'fail_mail': False,
         'identity': {'id': 'fictional-default', 'email': 'oauth@example.invalid', 'name': 'Fictional OAuth User', 'email_verified': True}, 'scenario': 'normal'}


async def smtp(reader, writer):
    async def send(text):
        writer.write((text+'\r\n').encode()); await writer.drain()
    await send('220 local-sink ESMTP')
    recipients = []
    try:
        while line := await reader.readline():
            command = line.decode(errors='replace').strip()
            verb = command.split(' ',1)[0].upper()
            if verb in {'EHLO','HELO'}:
                await send('250-local-sink'); await send('250 SIZE 131072')
            elif verb == 'MAIL': recipients=[]; await send('250 OK')
            elif verb == 'RCPT':
                address = parseaddr(command.split(':',1)[-1])[1]
                if address.lower().endswith('.invalid') and '@' in address:
                    recipients.append(address); await send('250 OK')
                else: await send('550 Fictional .invalid recipients only')
            elif verb == 'DATA':
                if not recipients: await send('554 No fictional recipient'); continue
                await send('354 End with dot')
                data = bytearray()
                while chunk := await reader.readline():
                    if chunk in {b'.\r\n',b'.\n'}: break
                    data.extend(chunk[1:] if chunk.startswith(b'..') else chunk)
                    if len(data)>131072: raise ValueError('message too large')
                with LOCK:
                    failed=STATE['fail_mail']
                    if not failed: STATE['messages'].append({'to':recipients[:],'raw':data.decode(errors='replace')})
                await send('451 Simulated local delivery failure' if failed else '250 Stored locally')
            elif verb in {'RSET','NOOP'}: await send('250 OK')
            elif verb == 'QUIT': await send('221 Bye'); break
            else: await send('502 Not implemented')
    except Exception:
        pass
    finally:
        writer.close(); await writer.wait_closed()


class Handler(BaseHTTPRequestHandler):
    def log_message(self, *_): pass

    def answer(self, status, payload):
        data=json.dumps(payload).encode()
        self.send_response(status); self.send_header('Content-Type','application/json')
        self.send_header('Cache-Control','no-store'); self.send_header('Content-Length',str(len(data)))
        self.end_headers(); self.wfile.write(data)

    def test_authorized(self):
        return hmac.compare_digest(self.headers.get('X-Test-Key',''), CONTROL)

    def do_GET(self):
        parsed=urlsplit(self.path); params={k:v[0] for k,v in parse_qs(parsed.query).items()}
        if parsed.path=='/health':
            self.send_response(200); self.end_headers(); self.wfile.write(b'ok'); return
        if parsed.path=='/_test/outbox':
            if not self.test_authorized(): self.answer(403,{}); return
            with LOCK: self.answer(200,{'messages':STATE['messages'][:]})
            return
        if parsed.path=='/authorize':
            if params.get('client_id')!='zinema-local' or params.get('redirect_uri')!=REDIRECT or params.get('code_challenge_method')!='S256' or not params.get('code_challenge') or not params.get('state'):
                self.answer(400,{'error':'invalid_request'}); return
            code=secrets.token_urlsafe(32)
            with LOCK:
                STATE['codes'][code]={'challenge':params['code_challenge'],'expires':time.time()+60,'identity':dict(STATE['identity']),'scenario':STATE['scenario']}
            location=REDIRECT+'?'+urlencode({'code':code,'state':params['state']})
            self.send_response(302); self.send_header('Location',location); self.send_header('Cache-Control','no-store'); self.end_headers(); return
        if parsed.path=='/userinfo':
            access=self.headers.get('Authorization','').removeprefix('Bearer ')
            with LOCK: item=STATE['access'].get(access)
            if not item or item['expires']<time.time() or item['scenario']=='userinfo_error': self.answer(401,{'error':'invalid_token'}); return
            self.answer(200,item['identity']); return
        self.answer(404,{})

    def do_POST(self):
        size=int(self.headers.get('Content-Length','0'))
        if size>131072: self.answer(413,{}); return
        body=self.rfile.read(size)
        path=urlsplit(self.path).path
        if path=='/_test/config':
            if not self.test_authorized(): self.answer(403,{}); return
            try: config=json.loads(body)
            except ValueError: self.answer(400,{}); return
            identity=config.get('identity')
            if identity and (not isinstance(identity.get('email'),str) or not identity['email'].endswith('.invalid')):
                self.answer(400,{}); return
            with LOCK:
                if identity: STATE['identity']=identity
                STATE['fail_mail']=bool(config.get('fail_mail',False))
                STATE['scenario']=config.get('scenario','normal')
                if config.get('clear_outbox'): STATE['messages']=[]
            self.answer(200,{'ok':True}); return
        if path=='/token':
            params={k:v[0] for k,v in parse_qs(body.decode()).items()}
            with LOCK: item=STATE['codes'].pop(params.get('code',''),None)
            challenge=base64.urlsafe_b64encode(hashlib.sha256(params.get('code_verifier','').encode()).digest()).decode().rstrip('=')
            if not item or item['expires']<time.time() or item['scenario']=='token_error' or params.get('client_id')!='zinema-local' or not hmac.compare_digest(params.get('client_secret',''),CLIENT_SECRET) or params.get('redirect_uri')!=REDIRECT or not hmac.compare_digest(challenge,item['challenge']):
                self.answer(400,{'error':'invalid_grant'}); return
            access=secrets.token_urlsafe(32)
            with LOCK: STATE['access'][access]={**item,'expires':time.time()+60}
            self.answer(200,{'access_token':access,'token_type':'Bearer','expires_in':60}); return
        self.answer(404,{})


def smtp_thread():
    async def serve():
        server=await asyncio.start_server(smtp,'127.0.0.1',1025,limit=131072)
        async with server: await server.serve_forever()
    asyncio.run(serve())


class ExtractorFaults(BaseHTTPRequestHandler):
    """Private, deterministic transport faults; never logs headers or request bodies."""
    followed=0
    def log_message(self,*_): pass
    def do_POST(self):
        self.rfile.read(min(int(self.headers.get('Content-Length','0')),2048))
        payload=b'{"success":true}'
        if self.path=='/timeout':time.sleep(5)
        if self.path=='/followed':ExtractorFaults.followed+=1
        if self.path=='/counts':payload=json.dumps({'followed':ExtractorFaults.followed}).encode()
        if self.path=='/oversize':payload=b'x'*9000
        self.send_response(302 if self.path=='/redirect' else 200)
        self.send_header('Content-Type','text/html' if self.path=='/html' else 'application/json')
        if self.path=='/redirect':self.send_header('Location','http://127.0.0.1:3335/followed')
        self.send_header('Content-Length',str(len(payload)));self.end_headers()
        try:self.wfile.write(payload)
        except (BrokenPipeError,ConnectionResetError):pass


if __name__=='__main__':
    threading.Thread(target=smtp_thread,daemon=True).start()
    threading.Thread(target=lambda:ThreadingHTTPServer(('127.0.0.1',3335),ExtractorFaults).serve_forever(),daemon=True).start()
    ThreadingHTTPServer(('0.0.0.0',9081),Handler).serve_forever()

"""HTTP/SQL-adapter regressions against owned containers; never print bearer material."""
import concurrent.futures
import base64
from email import policy
from email.parser import BytesParser
import hashlib
import html
import http.cookiejar
import json
from pathlib import Path
import re
import secrets
import struct
import sys
import urllib.error
import urllib.parse
import urllib.request
import zlib

BASE = 'http://127.0.0.1:8088'
FAKE = 'http://127.0.0.1:9081'
KEY = Path('/run/secrets/control_key').read_text().strip()
PASSWORD = Path('/run/secrets/demo_password').read_text().strip()
APIKEY = Path('/run/secrets/adapter_key').read_text().strip()
RUN = secrets.token_hex(5)
ROWS = []


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *_): return None


class Client:
    def __init__(self):
        self.jar = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar), NoRedirect())

    def request(self, path, data=None, headers=None, raw=None):
        url = path if path.startswith('http://') else BASE + path
        assert urllib.parse.urlsplit(url).netloc in {'127.0.0.1:8088', '127.0.0.1:9081'}
        headers = dict(headers or {})
        if data is not None:
            raw = urllib.parse.urlencode(data).encode()
            headers['Content-Type'] = 'application/x-www-form-urlencoded'
        req = urllib.request.Request(url, data=raw, headers=headers)
        try: response = self.opener.open(req, timeout=15)
        except urllib.error.HTTPError as error: response = error
        body = response.read()
        try: parsed = json.loads(body)
        except ValueError: parsed = {}
        return response.status, body, parsed, response.headers

    def csrf(self, page='/login.php'):
        body = self.request(page)[1].decode(errors='replace')
        match = re.search(r'name="csrf_token" value="([a-f0-9]{64})"', body)
        if not match: raise RuntimeError('CSRF field absent')
        return match[1]

    def post(self, path, data):
        return self.request(path, dict(data, csrf_token=self.csrf()))

    def session(self): return self.request('/api/check-session.php')[2].get('logged_in') is True

    def sid(self): return next((c.value for c in self.jar if c.name == 'PHPSESSID'), None)

    def upload(self, page, data, field, name, content, mime='image/png'):
        boundary = secrets.token_hex(16)
        chunks = []
        for key, value in dict(data, csrf_token=self.csrf('/admin/login.php')).items():
            chunks.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode())
        chunks += [f'--{boundary}\r\nContent-Disposition: form-data; name="{field}"; filename="{name}"\r\nContent-Type: {mime}\r\n\r\n'.encode(), content, f'\r\n--{boundary}--\r\n'.encode()]
        return self.request(page, headers={'Content-Type': 'multipart/form-data; boundary='+boundary}, raw=b''.join(chunks))


def check(name, condition):
    ROWS.append({'name': name, 'passed': bool(condition), 'evidence': 'runtime verified'})
    print(('PASS ' if condition else 'FAIL ') + name, flush=True)


def json_call(url, data, headers=None):
    return Client().request(url, raw=json.dumps(data).encode(), headers={'Content-Type':'application/json', **(headers or {})})


def control(action, **data):
    response = json_call('/__dev/control.php', dict(data, action=action), {'X-Test-Key':KEY})
    if response[0] != 200: raise RuntimeError('Fixture control failed')
    return response[2]


def fake(**data):
    return json_call(FAKE+'/_test/config', data, {'X-Test-Key':KEY})


def mail_token(email, route):
    messages = Client().request(FAKE+'/_test/outbox', headers={'X-Test-Key':KEY})[2]['messages']
    for item in reversed(messages):
        if email not in item['to']: continue
        message = BytesParser(policy=policy.default).parsebytes(item['raw'].encode())
        text = '\n'.join(part.get_content() for part in message.walk() if part.get_content_type() in {'text/plain','text/html'})
        match = re.search(re.escape(BASE+'/'+route)+r'\?token=([a-f0-9]{64})', html.unescape(text))
        if match: return match[1]
    raise RuntimeError('Expected fictional mail absent')


def oauth(client, email, provider=None, verified=True, scenario='normal', callback=True):
    fake(identity={'id':provider or 'fictional-'+email, 'email':email, 'email_verified':verified, 'name':'Local Test'}, scenario=scenario)
    start = client.request('/oauth-start.php')
    authorize = start[3].get('Location','')
    query = urllib.parse.parse_qs(urllib.parse.urlsplit(authorize).query)
    check('OAuth authorization carries S256 and state', start[0]==302 and query.get('code_challenge_method')==['S256'] and len(query.get('state',[''])[0])==64)
    response = client.request(authorize)
    location = response[3].get('Location','')
    return client.request(location) if callback else location


def token_fixture(**options):
    token = 'T'+secrets.token_hex(6).upper()[:11]
    return token, control('token', token=token, active=True, **options)['id']


def claim(token=None, action='claim', recipient='fictional-recipient', bot_id=1, **proof):
    payload = dict(action=action, recipient_hash=hashlib.sha256(recipient.encode()).hexdigest(), bot_id=bot_id, **proof)
    if token: payload['token'] = token
    return json_call('/__dev/bridge.php?endpoint=claim-token', payload, {'X-Test-Key':KEY,'X-Api-Key':APIKEY})[2]


def run():
    fake(clear_outbox=True, fail_mail=False, scenario='normal')
    check('Development health is ready', Client().request('/__dev/health.php')[1]==b'ok')
    check('Fixture control rejects missing key', json_call('/__dev/control.php', {'action':'user'})[0]==403)
    check('Fake outbox rejects missing key', Client().request(FAKE+'/_test/outbox')[0]==403)
    check('Normal WhatsApp bridge remains disabled', Client().request('/api/whatsapp/validate-token.php', {'token':'fictional'})[0]==503)
    check('Claim adapter rejects wrong fake API key', json_call('/__dev/bridge.php?endpoint=claim-token', {}, {'X-Test-Key':KEY,'X-Api-Key':'wrong'})[0]==401)
    check('Application namespace has no default route', not any(line.split()[1]=='00000000' for line in Path('/proc/net/route').read_text().splitlines()[1:]))
    for route in ['/', '/movies', '/tv-series', '/collections', '/login.php', '/signup.php', '/trailers.php']:
        check('Public page renders '+route, Client().request(route)[0]==200)
    email = 'mail'+RUN+'@example.invalid'
    user = Client()
    signup = {'username':'mail'+RUN, 'email':email, 'password':PASSWORD}
    check('Signup rejects missing CSRF', user.request('/ajax_signup.php', signup)[0]==403)
    result = user.post('/ajax_signup.php', signup)
    check('Signup sends fake verification and keeps session unauthenticated', result[2].get('success') is True and not user.session())
    original = mail_token(email,'verify-email.php')
    check('Unverified password sign-in is denied', user.post('/ajax_login.php', {'email':email,'password':PASSWORD})[2].get('success') is False)
    check('Resend issues another local email', user.post('/ajax_resend_verification.php', {'email':email})[2].get('success') is True)
    replacement = mail_token(email,'verify-email.php')
    check('Resend revokes original link', original != replacement and b'Invalid verification' in Client().request('/verify-email.php?token='+original)[1])
    sid = user.sid()
    user.request('/verify-email.php?token='+replacement)
    check('Verification signs in and rotates the session', user.session() and sid != user.sid())
    check('Verification link cannot be replayed', b'Invalid verification' in Client().request('/verify-email.php?token='+replacement)[1])
    check('Forgot password sends only to fake sink', user.post('/ajax_forgot_password.php', {'email':email})[2].get('success') is True)
    reset = mail_token(email,'reset-password.php')
    resetter = Client(); changed = secrets.token_hex(20)
    fields = {'token':reset,'password':changed,'confirm_password':changed}
    check('Password reset rejects missing CSRF', resetter.request('/ajax_reset_password.php', fields)[0]==403)
    check('Valid reset changes password', resetter.post('/ajax_reset_password.php', fields)[2].get('success') is True)
    check('Reset token cannot be replayed', resetter.post('/ajax_reset_password.php', fields)[2].get('success') is False)
    check('Old password is rejected after reset', Client().post('/ajax_login.php', {'email':email,'password':PASSWORD})[2].get('success') is False)
    check('New password signs in', Client().post('/ajax_login.php', {'email':email,'password':changed})[2].get('success') is True)
    user.post('/ajax_forgot_password.php', {'email':email}); expired = mail_token(email,'reset-password.php'); control('expire_reset',email=email)
    check('Expired reset cannot change password', resetter.post('/ajax_reset_password.php', dict(fields,token=expired))[2].get('success') is False)
    pending = 'pending'+RUN+'@example.invalid'; fake(fail_mail=True)
    result = Client().post('/ajax_signup.php', {'username':'p'+RUN,'email':pending,'password':PASSWORD})
    check('SMTP failure is honest and preserves a pending account', result[2].get('success') is False and result[2].get('account_created') is True and control('user',email=pending).get('is_verified')==0)
    fake(fail_mail=False)
    Client().post('/ajax_resend_verification.php', {'email':pending}); pending_token = mail_token(pending,'verify-email.php'); control('expire_verification',email=pending)
    check('Expired verification remains unauthenticated', b'expired' in Client().request('/verify-email.php?token='+pending_token)[1] and control('user',email=pending).get('is_verified')==0)
    outsider = Client().post('/ajax_signup.php', {'username':'out'+RUN,'email':'outside'+RUN+'@example.com','password':PASSWORD})
    check('Local mail refuses non-fictional recipients', outsider[2].get('success') is False)
    oauth_user = Client(); oauth_email='oauth'+RUN+'@example.invalid'
    sid = oauth_user.csrf(); before = oauth_user.sid()
    result = oauth(oauth_user,oauth_email)
    check('Verified fake OAuth creates a verified session and rotates ID', result[0]==303 and oauth_user.session() and before!=oauth_user.sid())
    check('OAuth never logs in an unverified identity', 'oauth_identity' in oauth(Client(),'unverified'+RUN+'@example.invalid',verified=False)[3].get('Location',''))
    check('OAuth cannot silently link an existing password account', 'oauth_link_required' in oauth(Client(),email)[3].get('Location',''))
    check('Signed-in owner may explicitly link their password account', oauth(user,email)[3].get('Location','').endswith('/profile.php') and control('user',email=email).get('google_id') is not None)
    check('A different provider subject cannot rebind that email', 'oauth_conflict' in oauth(Client(),email,provider='other-fictional-'+RUN)[3].get('Location',''))
    for scenario,reason in [('token_error','exchange'),('userinfo_error','identity')]:
        check('OAuth handles fake '+scenario, 'oauth_'+reason in oauth(Client(),'err'+RUN+'@example.invalid',scenario=scenario)[3].get('Location',''))
    session = Client(); callback = oauth(session,'state'+RUN+'@example.invalid',callback=False)
    check('OAuth state is bound to its initiating browser', 'oauth_state' in Client().request(callback)[3].get('Location',''))
    altered = re.sub(r'state=[^&]+','state='+('0'*64),callback)
    check('OAuth rejects a forged state', 'oauth_state' in session.request(altered)[3].get('Location',''))
    check('Valid initiating state still completes', session.request(callback)[3].get('Location','').endswith('/profile.php') and session.session())
    check('OAuth state cannot be replayed', 'oauth_state' in session.request(callback)[3].get('Location',''))
    bad_pkce = Client(); callback = oauth(bad_pkce,'pkce'+RUN+'@example.invalid',callback=False)
    code = urllib.parse.parse_qs(urllib.parse.urlsplit(callback).query)['code'][0]
    exchange = Client().request(FAKE+'/token', {'code':code,'client_id':'zinema-local',
        'client_secret':Path('/run/secrets/oauth_secret').read_text().strip(),
        'redirect_uri':BASE+'/google-callback.php','code_verifier':'incorrect-fictional-verifier'})
    check('Fake provider enforces PKCE verifier', exchange[0]==400 and exchange[2].get('error')=='invalid_grant')
    uploads()
    claims()


def png():
    def chunk(kind, data): return struct.pack('!I',len(data))+kind+data+struct.pack('!I',zlib.crc32(kind+data))
    return b'\x89PNG\r\n\x1a\n'+chunk(b'IHDR',struct.pack('!2I5B',2,2,8,2,0,0,0))+chunk(b'IDAT',zlib.compress((b'\x00'+b'\xff\x20\x20'*2)*2))+chunk(b'IEND',b'')


def uploads():
    admin=Client(); csrf=admin.csrf('/admin/login.php')
    check('Fictional admin signs in', admin.request('/admin/login.php',{'username':'demo_admin','password':PASSWORD,'csrf_token':csrf})[0]==302)
    image=png()+b'<?php echo "inert appended payload"; ?>'
    fixtures=[('movies','cover_image',{'add_movie':'1','release_date':'2026-01-01','genre':'Demo','rating':'5','video_url':''}),
              ('series','cover_image',{'add_series':'1','release_date':'2026-01-01','genre':'Demo','description':'Fictional'}),
              ('collections','cover_image',{'description':'Fictional','genre':'Demo'})]
    series_id=None
    for table,field,data in fixtures:
        title='Local Test '+RUN+' '+table; data=dict(data,title=title)
        result=admin.upload('/admin/'+table+'.php',data,field,'cover.png',image)
        item=control('catalogue',table=table,title=title)
        check(table+' accepts a valid image with a generated name',result[0] in {200,302} and bool(re.fullmatch(r'uploads/[a-f0-9]{32}\.png',item.get('path',''))))
        if not item: continue
        fetched=Client().request('/'+item['path'])
        check(table+' serves rewritten image without appended payload',fetched[0]==200 and fetched[1].startswith(b'\x89PNG') and b'<?php' not in fetched[1] and fetched[3].get('X-Content-Type-Options')=='nosniff')
        if table=='series': series_id=item['id']
        if table=='movies':
            edits=dict(data,edit_movie='1',movie_id=item['id']);edits.pop('add_movie')
            for label,name,content in [('PHP disguise','evil.jpg',b'<?php echo 1; ?>'),('SVG','evil.svg',b'<svg/>'),('mismatched extension','wrong.jpg',png()),('malformed','bad.png',b'\x89PNG\r\n\x1a\nno'),('traversal filename','../bad.png',png()),('oversized','big.png',png()+b'x'*(5*1024*1024))]:
                response=admin.upload('/admin/movies.php',edits,field,name,content)
                check('Upload rejects '+label+' and preserves original',response[0]==422 and control('catalogue',table=table,title=title).get('path')==item['path'] and Client().request('/'+item['path'])[0]==200)
        if table=='collections':
            poisoned=dict(data,id=item['id'],existing_cover_image='../../includes/environment.php',csrf_token=admin.csrf('/admin/login.php'))
            admin.request('/admin/collections.php',poisoned)
            check('Collection ignores posted existing image path',control('catalogue',table=table,title=title).get('path')==item['path'])
    if series_id:
        title='Local Test '+RUN+' episode'
        response=admin.upload('/admin/episodes.php',{'add_episode':'1','series_id':series_id,'season_number':1,'episode_number':1,'title':title,'description':'Fictional','video_url':''},'thumb_image','thumbnail.png',png())
        check('Episode thumbnail uses local validated storage',response[0]==200 and control('catalogue',table='episodes',title=title).get('path','').startswith('uploads/'))
    trailer={'action':'add','movie_id':1,'title':'Local Test '+RUN+' trailer','description':'Fictional'}
    check('Trailer rejects executable extension',admin.upload('/admin/trailers.php',trailer,'trailer_video','evil.php',b'<?php echo 1; ?>','video/mp4')[0]==422)
    video=base64.b64decode(Path('dev/fixtures/red-frame.mp4.b64').read_bytes())
    response=admin.upload('/admin/trailers.php',trailer,'trailer_video','original.mp4',video,'video/mp4')
    item=control('catalogue',table='trailers',title=trailer['title'])
    check('Generated original MP4 is stored with a safe name',response[0]==302 and bool(re.fullmatch(r'uploads/trailers/[a-f0-9]{32}\.mp4',item.get('path',''))))
    if item: check('Generated trailer is served as inert MP4',Client().request('/'+item['path'])[1]==video)
    check('Upload requires admin authentication',Client().upload('/admin/movies.php',{'add_movie':1,'title':'Local Test denied'},'cover_image','cover.png',png())[0]==302)


def claims():
    token, token_id=token_fixture()
    with concurrent.futures.ThreadPoolExecutor(max_workers=16) as pool:
        results=list(pool.map(lambda _:claim(token),range(32)))
    winners=[result for result in results if result.get('success') is True]
    check('Concurrent independent HTTP workers grant exactly one token claim',len(winners)==1)
    if not winners: return
    proof={'token_id':token_id,'receipt':winners[0]['receipt']}
    check('Claim does not extend or truncate explicit expiry',550 < control('token_status',token=token).get('remaining_seconds',0) <= 600)
    check('Recipient cannot complete another owner claim',claim(action='complete',recipient='fictional-other',**proof).get('success') is False)
    check('Cannot complete before beginning delivery',claim(action='complete',**proof).get('success') is False)
    check('Wrong receipt cannot begin delivery',claim(action='begin',token_id=token_id,receipt='0'*64).get('success') is False)
    check('Claim owner begins delivery',claim(action='begin',**proof).get('success') is True)
    check('Crash during sending never releases a claim',claim(token).get('success') is False)
    control('age_claim',token=token)
    check('A stale sending claim is never automatically reclaimed',claim(token).get('success') is False)
    control('expire_token',token=token)
    check('Acknowledged owner completes delivery',claim(action='complete',**proof).get('success') is True)
    check('Completion cannot be replayed',claim(action='complete',**proof).get('success') is False)
    check('Consumed token cannot be claimed',claim(token).get('success') is False)
    token, token_id=token_fixture(); won=claim(token); proof={'token_id':token_id,'receipt':won['receipt']}; claim(action='begin',**proof)
    check('Failure holds an uncertain claim',claim(action='fail',**proof).get('success') is True and claim(token).get('success') is False)
    check('Failed claim cannot later complete',claim(action='complete',**proof).get('success') is False)
    token,_=token_fixture(expired=True); check('Expired token cannot be claimed',claim(token).get('success') is False)
    token, token_id=token_fixture(); won=claim(token); control('expire_token',token=token)
    check('Expiry after claim prevents a new delivery',claim(action='begin',token_id=token_id,receipt=won['receipt']).get('success') is False)
    token='T'+secrets.token_hex(6).upper()[:11];control('token',token=token,active=False)
    check('Inactive token cannot be claimed',claim(token).get('success') is False)
    token,_=token_fixture(bot_id=2);check('Assigned bot is enforced',claim(token,bot_id=1).get('success') is False and claim(token,bot_id=2).get('success') is True)
    check('Legacy consume endpoint cannot bypass claim ownership',json_call('/__dev/bridge.php?endpoint=mark-used',{'token_id':token_id},{'X-Test-Key':KEY,'X-Api-Key':APIKEY})[2].get('error')=='claim_protocol_required')


if __name__=='__main__':
    try: run()
    except Exception as error:
        # Exception strings can include URLs/codes; report category only.
        check('Harness completed without exceptions ('+type(error).__name__+')',False)
    result={'environment':'isolated Docker; fake SMTP/OAuth; SQL-only bot adapter; no legacy rows imported', 'tests':ROWS,
            'summary':{'total':len(ROWS),'passed':sum(row['passed'] for row in ROWS),'failed':sum(not row['passed'] for row in ROWS)}}
    Path('/results/integration.json').write_text(json.dumps(result,indent=2)+'\n')
    print(json.dumps(result['summary']))
    sys.exit(1 if result['summary']['failed'] else 0)

"""Positive whole-local HTTP/SQL integration; only the owned native profile."""
import email.policy,email.parser,html,http.cookiejar,json,re,secrets,smtplib,subprocess,time,urllib.request,urllib.parse,urllib.error
from pathlib import Path
import native
home,c=native.current();native.verify_processes(home);native.audit.sql(home,'SELECT 1;')
if native.real_whatsapp(home) is not None:raise RuntimeError('simulator_test_refuses_live_whatsapp')
BASE='http://127.0.0.1:8088';rows=[]
def check(name,value):
    rows.append({'name':name,'passed':bool(value)})
    if not value:raise RuntimeError('native_test_failed')
class Client:
    def __init__(self):
        class Redirect(urllib.request.HTTPRedirectHandler):
            def redirect_request(self,req,fp,code,msg,headers,url):
                if urllib.parse.urlsplit(url).netloc not in ['127.0.0.1:8088','127.0.0.1:9081']:raise RuntimeError('external_redirect_denied')
                return super().redirect_request(req,fp,code,msg,headers,url)
        self.jar=http.cookiejar.CookieJar();self.opener=urllib.request.build_opener(urllib.request.ProxyHandler({}),Redirect(),urllib.request.HTTPCookieProcessor(self.jar))
    def call(self,route,data=None,as_json=False,headers=None):
        h=dict(headers or {});body=None
        if data is not None:body=(json.dumps(data) if as_json else urllib.parse.urlencode(data)).encode();h['Content-Type']='application/json' if as_json else 'application/x-www-form-urlencoded'
        try:r=self.opener.open(urllib.request.Request(BASE+route,data=body,headers=h),timeout=8)
        except urllib.error.HTTPError as error:r=error
        with r:raw=r.read(2097152).decode(errors='replace')
        try:value=json.loads(raw)
        except ValueError:value=None
        return r.status,raw,value,r.url
    def csrf(self):return re.search('name="csrf_token" value="([a-f0-9]+)"',self.call('/login.php')[1])[1]
def bot(route,data):return native.request(9090,'/'+route,data,c['control_key'])
def mails():return json.loads(native.request(9081,'/_test/outbox',key=c['control_key'])[1])['messages']
def mail_link(recipient,kind):
    for message in reversed(mails()):
        if recipient not in message['to']:continue
        parsed=email.parser.Parser(policy=email.policy.default).parsestr(message['raw']);body=''
        for part in parsed.walk():
            if part.get_content_type() in ['text/plain','text/html']:body+=part.get_content()
        match=re.search(r'http://127\.0\.0\.1:8088/'+kind+r'\.php\?token=[a-zA-Z0-9]+',html.unescape(body))
        if match:return match[0]
    raise RuntimeError('fictional_mail_link_missing')
def get_token(client):
    raw=client.call('/whatsapp-watch.php?id=1')[1]
    match=re.search(r'href="(http://127\.0\.0\.1:8088/__local/chat\.php\?token=[A-Z0-9]{12})"',raw)
    if not match:raise RuntimeError('local_handoff_missing')
    return urllib.parse.parse_qs(urllib.parse.urlsplit(match[1]).query)['token'][0]
try:
    bot('control',{'action':'online'})
    check('Owned PHP SQL bridge authenticates',json.loads(native.request(8088,'/api/whatsapp/health.php',{},c['environment']['WHATSAPP_API_KEY'])[1]).get('success'))
    check('Simulator rejects missing key',native.request(9090,'/status',{})[0]==403)
    check('Extractor rejects missing service key',native.request(3334,'/health',{})[0]==401)
    check('Extractor rejects an incorrect service key',native.request(3334,'/health',{},'0'*64)[0]==401)
    guest=Client();check('Local console requires administrator',guest.call('/__local/index.php')[3].endswith('/admin/login.php'))
    admin=Client();admin.call('/admin/login.php',{'username':'audit_admin','password':native.audit.SAMPLE_PASSWORD,'csrf_token':admin.csrf()})
    check('Administrator can open whole local console','Whole local system' in admin.call('/__local/index.php')[1])
    suffix=secrets.token_hex(4);username='native_'+suffix;address=username+'@example.invalid';member=Client()
    registered=member.call('/ajax_signup.php',{'username':username,'email':address,'password':native.audit.SAMPLE_PASSWORD,'csrf_token':member.csrf()})[2]
    check('Signup sends only to local fictional inbox',registered is not None and registered.get('success') is True)
    check('New account starts unverified',native.audit.sql(home,f"SELECT is_verified FROM users WHERE email='{address}';",True)=='0')
    check('Administrator inbox displays a verification action','Verify fictional account' in admin.call('/__local/inbox.php')[1])
    link=mail_link(address,'verify-email');member.call(link.removeprefix(BASE))
    check('Local email verification activates account',native.audit.sql(home,f"SELECT is_verified FROM users WHERE email='{address}';",True)=='1')
    login=member.call('/ajax_login.php',{'email':address,'password':native.audit.SAMPLE_PASSWORD,'csrf_token':member.csrf()})[2]
    check('Verified local account signs in',login is not None and login.get('success') is True)
    forgot=member.call('/ajax_forgot_password.php',{'email':address,'csrf_token':member.csrf()})[2]
    check('Password reset uses local sink',forgot is not None and forgot.get('success') is True)
    reset=urllib.parse.parse_qs(urllib.parse.urlsplit(mail_link(address,'reset-password')).query)['token'][0];replacement='Fictional-native-'+secrets.token_hex(5)
    payload={'token':reset,'password':replacement,'confirm_password':replacement,'csrf_token':member.csrf()};changed=member.call('/ajax_reset_password.php',payload)[2]
    check('Fictional account reset completes',changed is not None and changed.get('success') is True)
    check('Reset link cannot be reused',member.call('/ajax_reset_password.php',payload)[2].get('success') is False)
    oauth=Client();oauth.call('/oauth-start.php');check('Fake OAuth completes without Google',oauth.call('/api/check-session.php')[2].get('logged_in') is True)
    headers={'X-CSRF-Token':admin.csrf()}
    health=admin.call('/admin/extractor.php',{'action':'health'},True,headers)[2];check('Actual fb-video-api app is online in local mode',health is not None and health.get('mode')=='local')
    extracted=admin.call('/admin/extractor.php',{'action':'extract','url':'https://www.facebook.com/share/v/fictional/'},True,headers)[2]
    check('Extraction returns only original managed fixture',extracted is not None and extracted.get('url')==c['fake_media_url'])
    check('Extractor refuses arbitrary network target',admin.call('/admin/extractor.php',{'action':'extract','url':'http://127.0.0.1/private'},True,headers)[0]==422)
    token=get_token(admin);check('Website handoff stays in localhost',len(token)==12)
    before=len(json.loads(bot('status',{})[1])['outbox']);sent=json.loads(bot('deliver',{'token':token})[1]);check('Real claim client completes one simulated delivery',sent.get('success') is True and sent.get('simulated') is True)
    check('Database records completed claim',native.audit.sql(home,f"SELECT claim_state FROM whatsapp_tokens WHERE token='{token}';",True)=='completed')
    bot('deliver',{'token':token});check('Consumed token never duplicates attachment',len(json.loads(bot('status',{})[1])['outbox'])==before+1)
    bot('control',{'action':'offline'});before_tokens=native.audit.sql(home,'SELECT COUNT(*) FROM whatsapp_tokens;',True);offline=admin.call('/whatsapp-watch.php?id=1')[1]
    check('Offline simulator prevents new token issuance','Service Temporarily Unavailable' in offline and native.audit.sql(home,'SELECT COUNT(*) FROM whatsapp_tokens;',True)==before_tokens)
    bot('control',{'action':'online'});check('Simulator can return online',json.loads(bot('status',{})[1]).get('online') is True)
    (home/'native/restart-bot.request').write_text('test\n')
    for _ in range(80):
        if not (home/'native/restart-bot.request').exists():break
        time.sleep(.25)
    native.verify_processes(home);after=json.loads(bot('status',{})[1]);check('Restart preserves outbox and avoids replay',len(after['outbox'])==before+1)
    bot('control',{'action':'online'});bot('deliver',{'token':token});check('Restarted simulator refuses consumed token',len(json.loads(bot('status',{})[1])['outbox'])==before+1)
    inventory=native.cli(home,'dev/native/operator.php',{'action':'inventory'});check('Native quota inventory uses owned state',inventory.get('success') is True and inventory['quota']['max_bytes']==268435456)
    with smtplib.SMTP('127.0.0.1',int(c['environment']['MAIL_PORT']),timeout=3) as smtp:
        smtp.ehlo();smtp.mail('demo@example.invalid');code,_=smtp.rcpt('nobody@example.com');check('SMTP never accepts real recipient domains',code==550)
except Exception as error:
    rows.append({'name':'Whole local integration completed','passed':False,'errorType':type(error).__name__})
finally:
    report={'scope':'Owned native PHP/SQL/SMTP/OAuth/extractor/Node simulator; no provider connections','tests':rows,'summary':{'total':len(rows),'passed':sum(r['passed'] for r in rows),'failed':sum(not r['passed'] for r in rows)},'external_connections':False,'real_whatsapp':False}
    (native.ROOT/'docs/recovery/LOCAL_WHOLE_RESULTS.json').write_text(json.dumps(report,indent=2)+'\n');print(json.dumps({'summary':report['summary'],'failures':[r['name'] for r in rows if not r['passed']]}));raise SystemExit(1 if report['summary']['failed'] else 0)

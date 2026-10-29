"""Private adapter/queue contracts with fictional state; no service or DB starts."""
import concurrent.futures,copy,json,os,secrets,shutil,subprocess,tempfile,unittest
from pathlib import Path
from local import ROOT
PHP='C:/xampp/php/php.exe' if os.name=='nt' else 'php'
DRIVER=ROOT/'docs/recovery/tests/stellar-queue-driver.php'
SOURCE='https://www.facebook.com/share/v/fictional/'
RESULT='http://127.0.0.1:9999/uploads/trailers/'+'a'*32+'.mp4'

def fixture_configuration(private,port=9999,db_port=3307,password='fictional-password'):
    env=dict(APP_ENV='test',BASE_URL=f'http://127.0.0.1:{port}',ALLOW_LIVE_INTEGRATIONS='false',TRUSTED_PROXY_IPS='',DB_HOST='127.0.0.1',DB_PORT=str(db_port),DB_NAME='zinema_recovery_test',DB_USER='zinema_recovery_fixture',DB_PASS=password,SESSION_SAVE_PATH=(private/'sessions').as_posix(),UPLOAD_QUOTA_MODE='account',UPLOAD_STORAGE_STATE=(private/'storage').as_posix(),UPLOAD_STORAGE_OWNER='zinema-account-'+'a'*16,UPLOAD_MAX_BYTES='1048576',UPLOAD_MAX_FILES='20',UPLOAD_QUARANTINE_MAX_BYTES='1048576',UPLOAD_QUARANTINE_MAX_FILES='20',EXTRACTOR_MODE='account-queue')
    env.update({name+'_MODE':'disabled' for name in ['MAIL','OAUTH','WHATSAPP','MEDIA','PAYMENT']})
    env.update({name:'0' for name in ['ZINEMA_TWO_HOST_FIXTURES','ZINEMA_PROMOTION_FIXTURES','ZINEMA_EXTRACTOR_FIXTURES']})
    return dict(version=1,purpose='private-staging-runtime',enabled=True,production_access=False,publishing=False,mode='local-fixture',allowed_clients=['127.0.0.1'],extractor_worker_key=secrets.token_hex(32),environment=env)

class Adapters(unittest.TestCase):
    def setUp(self):
        (ROOT/'.runtime').mkdir(exist_ok=True)
        self.home=Path(tempfile.mkdtemp(prefix='stellar-adapters-',dir=ROOT/'.runtime')).resolve()
        self.private=self.home/'zinema-private/staging';self.public=self.home/'public_html'
        self.private.mkdir(parents=True,mode=0o700)
        for p in [self.private/'extractor',self.private/'storage',self.private/'sessions',self.public/'uploads']:p.mkdir(parents=True,mode=0o700)
        self.directory=self.private/'extractor'
    def tearDown(self):
        if self.home.parent!=(ROOT/'.runtime').resolve() or not self.home.name.startswith('stellar-adapters-'):raise RuntimeError('unsafe_cleanup')
        shutil.rmtree(self.home)
    def call(self,scope,input=None,**kwargs):
        raw=dict(directory=self.directory.as_posix(),scope=scope,input=input or {},**kwargs)
        result=subprocess.run([PHP,str(DRIVER)],input=json.dumps(raw),capture_output=True,text=True,timeout=8,cwd=ROOT)
        self.assertEqual(result.returncode,0);self.assertFalse(result.stderr,result.stderr)
        return json.loads(result.stdout)
    def heartbeat(self):return self.call('worker',dict(action='heartbeat'))
    def enqueue(self,source=SOURCE,**kw):return self.call('admin',dict(action='extract',url=source),**kw)
    def claim(self):return self.call('worker',dict(action='claim'))['job']
    def test_configuration_validates_private_loopback_context(self):
        c=fixture_configuration(self.private)
        self.assertTrue(self.call('context',config=c,private=self.private.as_posix(),public=self.public.as_posix(),remote='127.0.0.1')['valid'])
    def test_administrator_inline_script_parses_for_queue_mode(self):
        import re
        html=(ROOT/'admin/manage_shots.php').read_text()
        code=re.findall(r'<script>(.*?)</script>',html,re.S)[0]
        code=re.sub(r'<\?php.*?\?>','true',code,flags=re.S)
        result=subprocess.run(['node','--check'],input=code,capture_output=True,text=True,timeout=8)
        self.assertEqual(result.returncode,0,result.stderr)
    def test_configuration_mutations_fail_closed(self):
        c=fixture_configuration(self.private)
        changes=[('enabled',False),('production_access',True),('publishing',True),('allowed_clients',['0.0.0.0']),('extractor_worker_key','bad'),('mode','live'),('environment.APP_ENV','production'),('environment.ALLOW_LIVE_INTEGRATIONS','true'),('environment.DB_HOST','example.invalid'),('environment.DB_NAME','legacy'),('environment.BASE_URL','https://example.invalid'),('environment.WHATSAPP_MODE','live'),('environment.SESSION_SAVE_PATH',(self.public/'uploads').as_posix()),('environment.UPLOAD_STORAGE_STATE',(self.public/'uploads').as_posix()),('environment.ZINEMA_PROMOTION_FIXTURES','1')]
        for key,value in changes:
            with self.subTest(key=key):
                d=copy.deepcopy(c);parts=key.split('.');target=d if len(parts)==1 else d[parts[0]];target[parts[-1]]=value
                self.assertFalse(self.call('context',config=d,private=self.private.as_posix(),public=self.public.as_posix(),remote='127.0.0.1')['valid'])
    def test_offline_does_not_accept_work(self):
        self.assertEqual(self.enqueue()['error'],'extractor_offline')
        self.heartbeat();self.assertEqual(self.call('admin',dict(action='health'),now=1091)['online'],False)
    def test_owner_scoping_and_deduplication(self):
        self.heartbeat();job=self.enqueue();self.assertEqual(self.enqueue()['job_id'],job['job_id'])
        self.assertEqual(self.call('admin',dict(action='status',job_id=job['job_id']),admin=2)['status'],404)
        self.assertEqual(self.enqueue(admin=0)['status'],403)
        self.assertNotIn('receipt',self.call('admin',dict(action='status',job_id=job['job_id'])))
    def test_claim_begin_completion_and_idempotent_ack(self):
        self.heartbeat();self.enqueue();job=self.claim();proof={k:job[k] for k in ['job_id','receipt']}
        self.assertIsNone(self.claim())
        self.assertEqual(self.call('worker',dict(action='complete',url=RESULT,**proof))['status'],409)
        self.assertEqual(self.call('worker',dict(action='begin',**proof))['state'],'working')
        self.assertEqual(self.call('worker',dict(action='complete',url='http://example.invalid/private',**proof))['status'],422)
        self.assertEqual(self.call('worker',dict(action='complete',url=RESULT,**proof))['state'],'done')
        self.assertEqual(self.call('worker',dict(action='complete',url=RESULT,**proof))['state'],'done')
        self.assertEqual(self.enqueue()['url'],RESULT)
    def test_expired_claim_and_restart_stay_held(self):
        self.heartbeat();self.enqueue();job=self.claim()
        result=self.call('worker',dict(action='begin',job_id=job['job_id'],receipt=job['receipt']),now=1061)
        self.assertEqual(result['error'],'extractor_claim_held')
        self.assertIsNone(self.call('worker',dict(action='claim'),now=1061)['job'])
        self.assertEqual(self.call('review',dict(action='inventory'),now=1061)['jobs'][0]['state'],'held')
    def test_queued_expiry_survives_subsequent_inventory(self):
        self.heartbeat();self.enqueue()
        for _ in range(2):self.assertEqual(self.call('review',dict(action='inventory'),now=1901)['jobs'][0]['state'],'held')
    def test_receipt_validation_and_strict_fields(self):
        self.heartbeat();self.enqueue();job=self.claim()
        self.assertEqual(self.call('worker',dict(action='begin',job_id=job['job_id'],receipt='0'*64))['status'],409)
        self.assertEqual(self.call('worker',dict(action='heartbeat',surprise=True))['status'],400)
        self.assertEqual(self.enqueue('http://127.0.0.1/private')['status'],422)
    def test_archive_requires_review_and_current_revision(self):
        self.heartbeat();self.enqueue();job=self.claim();self.call('worker',dict(action='hold',job_id=job['job_id'],receipt=job['receipt']))
        view=self.call('review',dict(action='inventory'))['jobs'][0]
        review=dict(action='archive',job_id=view['job_id'],revision=view['revision'],reviewed=True,workers_stopped=True,actor='demo_owner',evidence_ref='fixture:review-one')
        self.assertEqual(self.call('review',{**review,'reviewed':False})['status'],409)
        self.assertEqual(self.call('review',{**review,'revision':0})['status'],409)
        self.assertTrue(self.call('review',review)['success']);inventory=self.call('review',dict(action='inventory'))
        self.assertEqual(inventory['jobs'],[]);self.assertEqual(inventory['audit'][0]['outcome'],'archived_without_replay');self.assertNotIn('source',inventory['audit'][0])
        self.assertEqual(self.call('review',review)['status'],409)
    def test_queue_capacity_is_bounded(self):
        self.heartbeat()
        for i in range(32):self.assertTrue(self.enqueue(SOURCE+'?id='+str(i))['success'])
        self.assertEqual(self.enqueue(SOURCE+'?id=32')['status'],429)
        self.assertLess((self.directory/'queue.json').stat().st_size,131072)
    def test_concurrent_claims_have_one_winner(self):
        self.heartbeat();self.enqueue()
        with concurrent.futures.ThreadPoolExecutor(max_workers=6) as pool:results=list(pool.map(lambda _:self.claim(),range(6)))
        self.assertEqual(sum(r is not None for r in results),1)
    def test_corrupt_state_is_preserved_and_blocked(self):
        (self.directory/'queue.json').write_text('{corrupt')
        self.assertEqual(self.heartbeat()['status'],503);self.assertEqual((self.directory/'queue.json').read_text(),'{corrupt')
    def test_wrong_owner_state_is_blocked(self):
        self.heartbeat();file=self.directory/'queue.json';state=json.loads(file.read_text());state['owner']='different';file.write_text(json.dumps(state))
        self.assertEqual(self.heartbeat()['status'],503)
if __name__=='__main__':unittest.main()

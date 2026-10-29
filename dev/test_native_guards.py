"""Pure native bootstrap/storage and MIME boundaries; never starts a service."""
import copy,importlib.util,json,os,random,shutil,subprocess,sys,unittest
from pathlib import Path
import native
from unittest.mock import patch

class NativeGuards(unittest.TestCase):
    def setUp(self):
        self.home=native.ROOT/'.runtime'/('audit-20990101-'+str(random.randint(100000,999999)))
        self.home.mkdir(exist_ok=False)
        for name in ['web','sessions','db']:(self.home/name).mkdir()
        env={'APP_ENV':'test','BASE_URL':'http://127.0.0.1:8088','DB_HOST':'127.0.0.1','DB_PORT':'3307','DB_NAME':'zinema_recovery_test','DB_USER':'zinema_recovery_test','DB_PASS':'a'*48,'SESSION_SAVE_PATH':(self.home/'sessions').as_posix(),'ALLOW_LIVE_INTEGRATIONS':'false'}
        env.update({adapter+'_MODE':'disabled' for adapter in ['MAIL','OAUTH','WHATSAPP','MEDIA','EXTRACTOR','PAYMENT']})
        self.owner={'purpose':'pure-native-bootstrap-test'};native.configure(self.home,env,self.owner)
        (self.home/'ownership.json').write_text(json.dumps(self.owner));self.config=json.loads((self.home/'native-config.json').read_text())
    def tearDown(self):
        if self.home.parent!=(native.ROOT/'.runtime') or not self.home.name.startswith('audit-20990101-'):raise RuntimeError('cleanup_boundary')
        shutil.rmtree(self.home)
    def php(self,code):
        r=subprocess.run([str(native.audit.PHP),'-r',code],cwd=native.ROOT,capture_output=True,text=True,timeout=8)
        self.assertEqual(r.returncode,0);self.assertFalse(r.stderr);return r.stdout
    def boot(self):
        return self.php("require 'dev/native/bootstrap.php';try{native_demo_bootstrap('"+self.home.as_posix()+"');require 'includes/environment.php';echo recovery_native_fixture()?'allowed':'blocked';}catch(Throwable $e){echo 'blocked';}")
    def test_exact_private_test_context_is_accepted_without_database_access(self):self.assertEqual(self.boot(),'allowed')
    def test_stopped_legacy_mail_port_migrates_without_touching_competing_mail(self):
        self.config['environment']['MAIL_PORT']='1025'
        (self.home/'native-config.json').write_text(json.dumps(self.config))
        with patch.object(native.audit,'port_open',side_effect=lambda port:port==1025):
            updated=native.upgrade_mail_port(self.home,self.config)
        expected=copy.deepcopy(self.config);expected['environment']['MAIL_PORT']='11025'
        self.assertEqual(updated,expected);self.assertEqual(json.loads((self.home/'native-config.json').read_text()),expected)
        self.assertEqual(self.boot(),'allowed')
    def test_mail_port_migration_refuses_active_or_occupied_native_ports(self):
        self.config['environment']['MAIL_PORT']='1025'
        (self.home/'native-config.json').write_text(json.dumps(self.config))
        for occupied in native.PORTS:
            with self.subTest(occupied=occupied),patch.object(native.audit,'port_open',side_effect=lambda port:port==occupied):
                with self.assertRaisesRegex(RuntimeError,'native_ports_occupied'):native.upgrade_mail_port(self.home,self.config)
                self.assertEqual(json.loads((self.home/'native-config.json').read_text()),self.config)
    def test_mail_port_cannot_select_an_arbitrary_service(self):
        for port in ['25','587','11026',11025]:
            with self.subTest(port=port):
                self.config['environment']['MAIL_PORT']=port
                (self.home/'native-config.json').write_text(json.dumps(self.config));self.assertEqual(self.boot(),'blocked')
                with self.assertRaisesRegex(RuntimeError,'native_target_denied'):native.checked_home(self.home)
    def test_unsafe_configuration_mutations_are_rejected(self):
        for key,value in [('publishing',True),('external_connections',True),('environment.APP_ENV','production'),('environment.DB_HOST','production.example.invalid'),('environment.DB_NAME','legacy'),('environment.DB_PORT','3306'),('environment.BASE_URL','https://example.invalid'),('environment.ALLOW_LIVE_INTEGRATIONS','true'),('environment.WHATSAPP_MODE','live'),('environment.MAIL_HOST','smtp.example.invalid'),('environment.OAUTH_TOKEN_URL','https://example.invalid/token'),('environment.UPLOAD_STORAGE_STATE','C:/outside'),('environment.WHATSAPP_API_KEY','invalid')]:
            with self.subTest(key=key):
                c=copy.deepcopy(self.config);parts=key.split('.');target=c if len(parts)==1 else c[parts[0]];target[parts[-1]]=value
                (self.home/'native-config.json').write_text(json.dumps(c));self.assertEqual(self.boot(),'blocked')
        (self.home/'native-config.json').write_text(json.dumps(self.config))
    def test_owner_mismatch_is_rejected(self):
        (self.home/'ownership.json').write_text(json.dumps({'native_owner':'wrong'}));self.assertEqual(self.boot(),'blocked')
    def test_environment_flag_alone_cannot_enable_delivery_or_native_storage(self):
        values=self.config['environment'];lines=''.join("putenv("+json.dumps(k+'='+v)+");" for k,v in values.items())
        code=lines+"require 'includes/environment.php';require 'includes/upload_storage.php';echo recovery_native_fixture()?'unsafe':'blocked';try{local_storage_settings();echo ':unsafe';}catch(LocalStorageError $e){echo ':blocked';}"
        self.assertEqual(self.php(code),'blocked:blocked')
    def test_fictional_mime_links_survive_8bit_quoted_printable_and_base64(self):
        before=sys.argv[:];sys.argv=['fakes.py','--native',str(self.home)]
        try:
            spec=importlib.util.spec_from_file_location('native_mime_fixture',native.ROOT/'dev/fakes.py');module=importlib.util.module_from_spec(spec);spec.loader.exec_module(module)
        finally:sys.argv=before
        from email.message import EmailMessage
        link='http://127.0.0.1:8088/verify-email.php?token='+'a'*64
        for encoding in ['8bit','quoted-printable','base64']:
            with self.subTest(encoding=encoding):
                m=EmailMessage();m['Subject']='Fictional verification';m.set_content('Verify: '+link,cte=encoding)
                result=module.project_message({'to':['demo@example.invalid'],'raw':m.as_string()});self.assertEqual(result['links'],[link])
    def test_role_runner_refuses_a_non_fixture_directory_before_network_access(self):
        r=subprocess.run(['node',str(native.ROOT/'dev/native/services.cjs'),'bot',str(native.ROOT)],capture_output=True,text=True,timeout=8)
        self.assertNotEqual(r.returncode,0)

if __name__=='__main__':unittest.main()

"""Disabled bundle boundaries and pure PHP validation; no account or DB access."""
import io,json,os,shutil,subprocess,tempfile,unittest
from unittest.mock import patch
from pathlib import Path
from prepare_stellar import prepare,checked_new_target,ROOT,TEMPLATES
PHP=r'C:\xampp\php\php.exe' if os.name=='nt' else '/usr/local/bin/php'
class StellarCandidate(unittest.TestCase):
    def setUp(self):
        self.home=Path(tempfile.mkdtemp(prefix='stellar-tests-',dir=ROOT/'.runtime')).resolve()
    def tearDown(self):
        if self.home.parent!=(ROOT/'.runtime').resolve() or not self.home.name.startswith('stellar-tests-'):raise RuntimeError('unsafe_cleanup')
        shutil.rmtree(self.home)
    def php(self,source):
        return subprocess.run([PHP,'-r',source],cwd=ROOT,capture_output=True,text=True)
    def test_checked_candidate_and_validator(self):
        result=subprocess.run([PHP,str(TEMPLATES/'check.php')],cwd=ROOT,capture_output=True,text=True)
        self.assertEqual(result.returncode,0);self.assertTrue(json.loads(result.stdout)['valid_disabled_template'])
    def test_switches_and_paths_cannot_activate_template(self):
        checker=(TEMPLATES/'check.php').as_posix();candidate=(TEMPLATES/'candidate.php').as_posix()
        mutations=["$c['deployment_enabled']=true;","$c['external_connections']=true;","$c['production_access']=true;","$c['publishing']=true;","$c['environment']['WHATSAPP_MODE']='live';","$c['environment']['ALLOW_LIVE_INTEGRATIONS']='true';","$c['environment']['ZINEMA_TWO_HOST_FIXTURES']='1';","$c['environment']['TRUSTED_PROXY_IPS']='0.0.0.0';","$c['environment']['SESSION_SAVE_PATH']='/home/owner/public_html/sessions';","$c['backup_candidate']['enabled']=true;","$c['monitoring_candidate']['collector_interval_seconds']=60;","$c['storage_candidate']['applied']=true;"]
        for change in mutations:
            with self.subTest(change=change):
                result=self.php(f"require '{checker}';$c=require '{candidate}';{change} echo stellar_candidate_valid($c)?'unsafe':'blocked';")
                self.assertEqual(result.stdout,'blocked')
    def test_bootstrap_is_disabled_without_loading_application_or_private_config(self):
        result=self.php("register_shutdown_function(function(){echo '|'.http_response_code();});require 'ops/staging/stellar/bootstrap.php';echo 'application-must-not-run';")
        self.assertEqual(result.returncode,0);self.assertEqual(result.stdout,'Private staging candidate is disabled.|503')
    def test_export_uses_committed_public_source_with_separate_private_material(self):
        manifest=prepare(self.home/'bundle');p=self.home/'bundle';self.assertFalse(manifest['deployment_enabled']);self.assertEqual(manifest['forbidden_paths'],0)
        self.assertGreater(len(manifest['files']),100);self.assertTrue((p/'public_html/.htaccess').read_text().startswith('# Preparation only:'))
        self.assertIn('Require all denied',(p/'public_html/.htaccess').read_text());self.assertTrue((p/'public_html/.user.ini').is_file())
        for name in ['candidate.php','bootstrap.php','check.php','schema.sql','cron.disabled.txt']:self.assertTrue((p/'zinema-private/staging'/name).is_file())
        self.assertTrue((p/'bot-tools/bot-readiness.cjs').is_file())
        for name in ['dev','docs','ops','node_modules','fb-video-api','whatsapp-bot-deploy','uploads','Database.sql','.env','.git','admin/run_migration.php','api/server-check.php']:self.assertFalse((p/'public_html'/name).exists(),name)
        # Git archive honors checkout/export EOL attributes on Windows.
        self.assertEqual((p/'public_html/index.php').read_bytes().replace(b'\r\n',b'\n'),subprocess.check_output(['git','show','HEAD:index.php'],cwd=ROOT).replace(b'\r\n',b'\n'))
    def test_export_never_overwrites_or_leaves_runtime_boundary(self):
        for path in [self.home,ROOT/'must-not-export-stellar',Path('relative-output')]:
            with self.subTest(path=str(path)),self.assertRaises(RuntimeError):checked_new_target(path)
    def test_budget_and_scheduler_are_disabled_and_distinct_from_large_media(self):
        ini=(TEMPLATES/'user.ini.template').read_text();self.assertIn('upload_max_filesize=20M',ini);self.assertIn('session.cookie_secure=1',ini)
        result=self.php("$v=parse_ini_file('ops/staging/stellar/user.ini.template',false,INI_SCANNER_RAW);echo json_encode($v);")
        self.assertEqual(result.returncode,0);values=json.loads(result.stdout)
        self.assertNotIn('expose_php',values);self.assertEqual(values['upload_max_filesize'],'20M')
        self.assertTrue(values['error_log'].startswith('/home/REPLACE_ACCOUNT/zinema-private/'))
        self.assertTrue(values['session.save_path'].startswith('/home/REPLACE_ACCOUNT/zinema-private/'))
        note=(TEMPLATES/'cron.disabled.txt').read_text();self.assertIn('>= 300 seconds',note);self.assertFalse(any(line and not line.startswith('#') for line in note.splitlines()))
    def test_host_capabilities_do_not_load_application_or_connect(self):
        result=subprocess.run([PHP,str(TEMPLATES/'host-capabilities.php')],cwd=ROOT,capture_output=True,text=True)
        self.assertEqual(result.returncode,0);r=json.loads(result.stdout)
        self.assertFalse(r['database_connection_attempted']);self.assertFalse(r['files_written']);self.assertFalse(r['external_connections']);self.assertFalse(r['activation_enabled'])
        self.assertEqual(r['web_handler_and_user_ini_effect'],'not_verified');self.assertNotIn('environment',r);self.assertNotIn('hostname',r)
    def test_stalled_local_command_is_bounded_and_fails_closed(self):
        from rehearse_stellar import local_command
        with patch('rehearse_stellar.subprocess.run',side_effect=subprocess.TimeoutExpired('fictional-helper',10)) as command:
            with self.assertRaisesRegex(RuntimeError,'^local_command_timeout$'):local_command(['fictional-helper'])
            self.assertEqual(command.call_args.kwargs['timeout'],10)
if __name__=='__main__':unittest.main()

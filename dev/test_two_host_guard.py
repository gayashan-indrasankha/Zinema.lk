"""Pure configuration checks; no DB, sessions, sockets or provider calls."""
import json,os,subprocess,unittest
from decimal import Decimal
from pathlib import Path
ROOT=Path(__file__).resolve().parent.parent
PHP=Path(r'C:\xampp\php\php.exe') if os.name=='nt' else Path('/usr/local/bin/php')
class TwoHostGuard(unittest.TestCase):
    def allowed(self,**changes):
        env=dict(os.environ,APP_ENV='development',BASE_URL='http://127.0.0.1:8088',DB_HOST='127.0.0.1',DB_PORT='3306',DB_NAME='zinema_recovery_development',DB_USER='zinema_recovery_dev',DB_PASS='fictional-unused',SESSION_SAVE_PATH=str(ROOT/'.runtime/fictional-guard-session'),ALLOW_LIVE_INTEGRATIONS='false',ZINEMA_TWO_HOST_FIXTURES='1',WHATSAPP_MODE='local',WHATSAPP_API_KEY='fictional-unused')
        env.update({a+'_MODE':'disabled' for a in ['MAIL','OAUTH','MEDIA','EXTRACTOR','PAYMENT']});env.update(changes)
        source="require 'includes/environment.php'; echo recovery_local_delivery_fixture() ? 'allowed' : 'denied';"
        result=subprocess.run([str(PHP),'-r',source],cwd=ROOT,env=env,capture_output=True,text=True)
        return result.returncode==0 and result.stdout=='allowed'
    def test_explicit_fictional_local_gate(self):self.assertTrue(self.allowed())
    def test_default_flag_is_closed(self):self.assertFalse(self.allowed(ZINEMA_TWO_HOST_FIXTURES=''))
    def test_false_flag_is_closed(self):self.assertFalse(self.allowed(ZINEMA_TWO_HOST_FIXTURES='0'))
    def test_disabled_adapter_stays_disabled(self):self.assertFalse(self.allowed(WHATSAPP_MODE='disabled'))
    def test_live_adapter_is_not_a_fixture(self):self.assertFalse(self.allowed(WHATSAPP_MODE='live'))
    def test_live_switch_cannot_bypass(self):self.assertFalse(self.allowed(ALLOW_LIVE_INTEGRATIONS='true'))
    def test_production_flag_is_not_a_fixture(self):self.assertFalse(self.allowed(APP_ENV='production',BASE_URL='https://example.invalid'))
    def test_other_origin_rejected(self):self.assertFalse(self.allowed(BASE_URL='http://127.0.0.1:9999'))
    def test_other_database_port_rejected(self):self.assertFalse(self.allowed(DB_PORT='3307'))
    def test_other_database_name_rejected(self):self.assertFalse(self.allowed(DB_NAME='zinema_recovery_test'))
    def test_remote_database_rejected(self):self.assertFalse(self.allowed(DB_HOST='example.invalid'))
    def test_hosting_candidate_has_no_external_switch(self):
        p=json.loads((ROOT/'ops/hosting/namecheap-stellar.json').read_text())
        for flag in ['deploymentEnabled','externalConnections','productionAccess','publishing']:self.assertIs(p[flag],False)
        self.assertEqual(p['bot']['largeMediaLocalCeilingBytes'],2**31)
        self.assertEqual(Decimal(str(p['pricing']['displayedMonthlyEquivalent']))*12,Decimal(str(p['pricing']['arithmeticAnnualEquivalent'])))
if __name__=='__main__':unittest.main()

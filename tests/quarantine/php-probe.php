<?php
declare(strict_types=1);
// Deliberately no environment/bootstrap/DB. Fake connection must never be touched.
$root=dirname(__DIR__,2);$mode=$argv[1]??'';
if($mode==='status'){
 require $root.'/apps/web/src/Integrations/FeatureGate.php';echo json_encode(\Zinema\Integrations\FeatureGate::all());exit;
}
if($mode==='tokens'){
 require $root.'/Zinema.lk Website/includes/whatsapp_token.php';
 $calls=0;$db=new class($calls){public function __construct(public int &$calls){}public function __call($n,$a){$this->calls++;throw new RuntimeException('DATABASE_TOUCHED');}};
 $methods=[static fn()=>generateUniqueToken(),static fn()=>createWhatsAppToken($db,501,'movie'),static fn()=>getActiveDistributionGroup($db),static fn()=>updateSetting($db,'sample','sample')];$denied=0;
 foreach($methods as $call)try{$call();}catch(\Zinema\Integrations\IntegrationDisabled){$denied++;}
 echo json_encode(['denied'=>$denied,'databaseCalls'=>$calls]);exit($denied===count($methods)&&$calls===0?0:1);
}
throw new RuntimeException('UNKNOWN_SAFE_PROBE');

'use strict';
// Executed in a labelled, route-less fake-bot container. Never imports Baileys.
const fs=require('node:fs'),https=require('node:https'),path=require('node:path'),crypto=require('node:crypto');
const mode=process.argv[2],root='/opt/zinema',home='/state';
if(!['initial','delivery','restart','outage','recovered'].includes(mode)||process.env.ZINEMA_TWO_HOST_FIXTURES!=='1'||process.env.ALLOW_LIVE_WHATSAPP!=='false'||process.env.API_URL!=='https://website:8443/api/whatsapp')throw Error('fictional_target_required');
const key=fs.readFileSync('/run/secrets/adapter_key','utf8').trim(),control=fs.readFileSync('/run/secrets/control_key','utf8').trim();
Object.assign(process.env,{API_KEY:key,APP_ENV:'production',ALLOW_PRODUCTION_API:'true',BOT_INSTANCE_ID:'910000001',SESSION_PATH:home+'/session',LOCAL_MEDIA_PATH:home+'/media'});
const api=require(root+'/config/api-client'),{deliverClaimedToken}=require(root+'/config/token-delivery');
const fixture=JSON.parse(fs.readFileSync('/run/fixture.json','utf8')),rows=[],diagnostics=[];
console.log=()=>{};console.error=()=>{}; // Private payloads and URL/SDK-style logs never enter reports.
function check(name,value){rows.push({name,passed:!!value});}
function call(route,data={},options={}) {
 return new Promise((resolve,reject)=>{
  const body=typeof data==='string'?data:JSON.stringify(data);
  const request=https.request('https://website:8443'+route,{method:options.method||'POST',headers:{'Content-Type':'application/json','Content-Length':Buffer.byteLength(body),'X-Api-Key':options.key===undefined?key:options.key,'X-Test-Key':control},...(options.tls||{}),timeout:5000},res=>{
   let text='';res.on('data',chunk=>{text+=chunk;if(text.length>262144)request.destroy();});
   res.on('end',()=>{let json;try{json=JSON.parse(text);}catch{}resolve({status:res.statusCode,json,text,headers:res.headers});});res.on('error',reject);
  });request.on('error',()=>reject(Error('fixture_transport_unavailable')));request.on('timeout',()=>request.destroy());request.end(body);
 });
}
const privateCall=(action,extra={})=>call('/__dev/two-host.php',{action,...extra}).then(r=>{diagnostics.push({fixture_action:action,status:r.status,has_token:typeof r.json?.token==='string',unavailable:r.json?.no_bots_available===true,fixture_failure:r.json?.error==='fictional_control_unavailable'});if(r.status!==200||!r.json)throw Error('fixture_control_failed');return r.json;});
const issue=()=>privateCall('issue',{movie:fixture.movie});
const heartbeat=(extra={})=>call('/api/whatsapp/bot-heartbeat.php',{bot_id:910000001,status:'online',queue_size:0,bot_phone:'000000000001',...extra});
const page=()=>call('/whatsapp-watch.php?id='+fixture.movie,{}, {method:'GET'});
const unavailable=r=>r.status===200&&r.text.includes('Service Temporarily Unavailable')&&!/<a\b[^>]*href=["']https:\/\/wa\.me\/[^>]*class=["']btn-whatsapp-main/.test(r.text);
const stateFile=home+'/scenario.json';
async function main(){
 if(mode==='initial'){
  check('Authenticated remote-namespace bridge health succeeds',(await api.request('health.php')).success===true);
  check('Bridge refuses missing key',(await call('/api/whatsapp/health.php',{}, {key:''})).status===401);
  check('Bridge refuses incorrect key',(await call('/api/whatsapp/health.php',{}, {key:'fixture-wrong'})).status===401);
  let rejected=false;try{await call('/api/whatsapp/health.php',{}, {tls:{agent:new https.Agent({ca:require('node:tls').rootCertificates[0]})}});}catch{rejected=true;}
  check('Untrusted TLS chain is rejected',rejected);
  rejected=false;try{await call('/api/whatsapp/health.php',{}, {tls:{servername:'wrong.example.invalid'}});}catch{rejected=true;}
  check('TLS hostname mismatch is rejected',rejected);
  check('Health GET is refused',(await call('/api/whatsapp/health.php',{}, {method:'GET'})).status===405);
  check('Bridge refuses oversized metadata instead of accepting video bytes',(await call('/api/whatsapp/health.php',{fixture:'x'.repeat(70000)})).status===413);
  check('Website catalogue works without a bot',(await call('/movies.php',{}, {method:'GET'})).status===200);
  check('No heartbeat shows honest offline page',unavailable(await page()));
  check('Heartbeat GET is refused',(await call('/api/whatsapp/bot-heartbeat.php',{}, {method:'GET'})).status===405);
  check('Malformed heartbeat is refused',(await call('/api/whatsapp/bot-heartbeat.php','{')).status===400);
  check('Invalid heartbeat status is refused',(await heartbeat({status:'ready'})).status===400);
  check('Negative heartbeat queue is refused',(await heartbeat({queue_size:-1})).status===400);
  check('Oversized heartbeat is refused',(await heartbeat({extra:'x'.repeat(5000)})).status===413);
  check('Online heartbeat is acknowledged',(await heartbeat()).json?.success===true);
  let before=(await privateCall('count')).tokens;
  check('Private fixture counter is an actual database count',Number.isInteger(before));
  await heartbeat({queue_size:30});check('Full bot queue does not issue a token',(await issue()).no_bots_available===true);
  check('Busy bot shows unavailable rather than a delivery link',unavailable(await page()));
  check('Unavailable requests do not create tokens',(await privateCall('count')).tokens===before);
  await heartbeat();await privateCall('stale');check('Stale heartbeat blocks new delivery',unavailable(await page()));
  await privateCall('future');check('Future heartbeat cannot keep delivery online',unavailable(await page()));
  await heartbeat({status:'offline'});check('Explicit offline status blocks delivery',unavailable(await page()));
  await heartbeat();let online=await page();
  check('Fresh heartbeat renders the correct fictional bot handoff',online.status===200&&online.text.includes('https://wa.me/000000000001?text=')&&/!get [A-Z0-9]{12}/.test(online.text));
  check('Delivery page does not leak server credential or fixture control key',!online.text.includes(key)&&!online.text.includes(control));
  check('Token-bearing delivery page cannot be cached',/(?:^|,)\s*no-store\s*(?:,|$)/i.test(online.headers['cache-control']||''));
 } else {
  let state=fs.existsSync(stateFile)?JSON.parse(fs.readFileSync(stateFile,'utf8')):{sends:0};
  const deps={claim:api.claimToken,transition:api.transitionToken,forward:async()=>{state.sends++;return {success:true,tier:'local-fake'};}};
  if(mode==='delivery'){
   await heartbeat();const good=await issue();let result=await deliverClaimedToken(deps,good.token,'fixture-recipient-a');
   check('Cross-host real SQL claim/begin/complete acknowledges one fake send',result.success===true&&state.sends===1&&(await privateCall('status',{token:good.token})).claim_state==='completed');
   state.completed=good.token;
   const pending=await issue();const claimed=await api.claimToken(pending.token,'fixture-interrupted');
   check('Pre-restart claim is stored',claimed.success===true);
   check('Pre-restart sending intent is stored',(await api.transitionToken('begin',{token_id:claimed.data.id,receipt:claimed.receipt},'fixture-interrupted')).success===true);
   state.interrupted=pending.token;
   const ready=await issue();state.ready=ready.token;
  } else if(mode==='restart'){
   check('Website and database restart preserve completed claim',(await privateCall('status',{token:state.completed})).claim_state==='completed');
   let result=await deliverClaimedToken(deps,state.completed,'fixture-replay');
   check('New bot process cannot replay a completed token',!result.success&&state.sends===1);
   result=await deliverClaimedToken(deps,state.interrupted,'fixture-interrupted');
   check('Interrupted sending stays held after both restarts',!result.success&&state.sends===1&&(await privateCall('status',{token:state.interrupted})).claim_state==='sending');
   result=await deliverClaimedToken(deps,state.ready,'fixture-ready');
   check('Unclaimed accepted work survives restart and completes once',result.success===true&&state.sends===2);
   const uncertain=await issue();result=await deliverClaimedToken({...deps,forward:async()=>{state.sends++;throw Error('fictional_lost_ack');}},uncertain.token,'fixture-lost-ack');
   check('Lost send acknowledgement produces an uncertain held claim',result.status==='uncertain'&&state.sends===3&&(await privateCall('status',{token:uncertain.token})).claim_state==='uncertain');
   state.uncertain=uncertain.token;
  } else if(mode==='outage'){
   let rejected=false;try{await deliverClaimedToken(deps,state.ready,'fixture-outage');}catch{rejected=true;}
   check('Website outage fails closed before any fake send',rejected&&state.sends===3);
  } else {
   check('Recovered bridge health succeeds',(await api.request('health.php')).success===true);
   let result=await deliverClaimedToken(deps,state.uncertain,'fixture-lost-ack');
   check('Recovered connection does not replay uncertain delivery',!result.success&&state.sends===3);
   await heartbeat();const token=await issue();
   result=await deliverClaimedToken(deps,token.token,'fixture-new');
   check('Fresh work completes after recovery without stale replay',result.success===true&&state.sends===4);
  }
  fs.writeFileSync(stateFile,JSON.stringify(state),{mode:0o600});
 }
}
main().catch(()=>check('Fictional two-host scenario completed without transport/setup failure',false)).finally(()=>{
 const report={mode,scope:'Actual HTTPS PHP/SQL and Node client in separate isolated network namespaces; fictional sender; no SDK',diagnostics,tests:rows,summary:{total:rows.length,passed:rows.filter(r=>r.passed).length,failed:rows.filter(r=>!r.passed).length}};
 fs.writeFileSync('/results/two-host-'+mode+'.json',JSON.stringify(report,null,2)+'\n');
 process.stdout.write(JSON.stringify({mode,summary:report.summary})+'\n');process.exitCode=report.summary.failed?1:0;
});

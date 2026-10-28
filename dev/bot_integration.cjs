'use strict';
// Real Node API client -> loopback relay -> guarded SQL claim adapter -> fake sender.
const fs=require('node:fs');const path=require('node:path');const http=require('node:http');const crypto=require('node:crypto');
const {execFileSync}=require('node:child_process');
const root=path.resolve(__dirname,'..'),state=path.join(root,'.runtime/local-dev'),rows=[];
function check(name,passed){rows.push({name,passed:!!passed,evidence:'runtime verified locally'});console.log((passed?'PASS ':'FAIL ')+name);}
function request(url,data,headers={}) {
 return new Promise((resolve,reject)=>{
  const body=JSON.stringify(data);const req=http.request(url,{method:'POST',headers:{...headers,'Content-Type':'application/json','Content-Length':Buffer.byteLength(body)},timeout:10000},res=>{
   let text='';res.on('data',chunk=>{text+=chunk;if(text.length>1048576)res.destroy();});
   res.on('end',()=>{try{resolve({status:res.statusCode,data:JSON.parse(text)});}catch{reject(Error('Invalid local fixture response'));}});
  });req.on('error',()=>reject(Error('Local fixture unavailable')));req.on('timeout',()=>req.destroy());req.end(body);
 });
}
async function main(){
 // Inspect local context and labelled gateway before reading or sending fixture keys.
 execFileSync('python',['-c',"import sys,json,secrets,subprocess;sys.path.insert(0,'dev');from local import require_local_docker,require_owned_gateway,BASE,ENV,STATE,ROOT;require_local_docker();require_owned_gateway();r=subprocess.run(BASE+['exec','-T','web','php','/opt/zinema/dev/product_fixture.php'],cwd=ROOT,env=ENV,input=json.dumps({'run':secrets.token_hex(6)}),capture_output=True,text=True);f=json.loads(r.stdout);assert r.returncode==0 and f.get('success') is True;(STATE/'results/product-fixture.json').write_text(json.dumps(f))"],{cwd:root,stdio:'pipe'});
 const control=fs.readFileSync(path.join(state,'control_key'),'utf8').trim(),key=fs.readFileSync(path.join(state,'adapter_key'),'utf8').trim();
 const bridge=data=>request('http://127.0.0.1:8088/__dev/bridge.php?endpoint=claim-token',data,{'X-Test-Key':control,'X-Api-Key':key});
 const relay=http.createServer(async(req,res)=>{
  if(req.method!=='POST'||req.headers['x-api-key']!==key){res.writeHead(403);return res.end('{}');}
  let body='';for await(const part of req){body+=part;if(body.length>8192){res.writeHead(413);return res.end('{}');}}
  try{
   if(req.url==='/claim-token.php') {const value=await bridge(JSON.parse(body));res.writeHead(value.status,{'Content-Type':'application/json'});res.end(JSON.stringify(value.data));}
   else if(req.url==='/update-forward-stats.php'){res.writeHead(200,{'Content-Type':'application/json'});res.end('{"success":true}');}
   else {res.writeHead(404);res.end('{}');}
  }catch{res.writeHead(503);res.end('{}');}
 });
 await new Promise(resolve=>relay.listen(0,'127.0.0.1',resolve));
 try{
  Object.assign(process.env,{APP_ENV:'test',API_URL:'http://127.0.0.1:'+relay.address().port,API_KEY:key,SESSION_PATH:path.join(state,'fictional-bot-session'),LOCAL_MEDIA_PATH:path.join(state,'fictional-bot-media'),BOT_INSTANCE_ID:'1',ALLOW_LIVE_WHATSAPP:'false'});
  const api=require('../whatsapp-bot-deploy/config/api-client');const Forwarder=require('../whatsapp-bot-deploy/config/multi-tier-forwarder');
  const {deliverClaimedToken}=require('../whatsapp-bot-deploy/config/token-delivery');
  async function token(){const token='T'+crypto.randomBytes(6).toString('hex').toUpperCase().slice(0,11);const r=await request('http://127.0.0.1:8088/__dev/control.php',{action:'token',token,active:true},{'X-Test-Key':control});if(!r.data.id)throw Error('Fictional token unavailable');return token;}
  async function status(token){return (await request('http://127.0.0.1:8088/__dev/control.php',{action:'token_status',token},{'X-Test-Key':control})).data;}
  let sends=0;
  const forwarder=new Forwarder({sendMessage:async()=>{sends++;return {key:{id:'fictional-ack'}};}},{hasLocalFile:()=>false},api,null);
  forwarder.messageCache.set('fictional-message',{key:{id:'fictional-message'}});
  const deps={claim:api.claimToken,transition:api.transitionToken,forward:(recipient,data)=>forwarder.forwardVideo(recipient,data)};
  let value=await token(),result=await deliverClaimedToken(deps,value,'fictional-recipient-a');
  check('Real Node client and SQL claim bridge complete one fake acknowledged send',result.success===true&&sends===1&&(await status(value)).is_used===1);
  result=await deliverClaimedToken(deps,value,'fictional-recipient-b');check('Consumed delivery cannot replay through a second Node recipient',!result.success&&sends===1);
  value=await token();const results=await Promise.all(['fictional-race-a','fictional-race-b'].map(who=>deliverClaimedToken(deps,value,who)));
  check('Concurrent Node recipients obtain only one SQL claim and one send',results.filter(r=>r.success).length===1&&sends===2);
  value=await token();result=await deliverClaimedToken({...deps,forward:async()=>{throw Error('fictional send failure');}},value,'fictional-failure');
  check('Failed fake send remains uncertain in the actual database',result.status==='uncertain'&&(await status(value)).claim_state==='uncertain'&&(await status(value)).is_used===0);
  value=await token();result=await deliverClaimedToken({...deps,forward:async()=>({success:true}),transition:(action,proof,who)=>action==='complete'?Promise.reject(Error('fictional acknowledgement loss')):api.transitionToken(action,proof,who)},value,'fictional-lost-ack');
  check('Lost completion acknowledgement keeps the actual claim held',result.status==='uncertain'&&(await status(value)).claim_state==='sending');
  const fixture=JSON.parse(fs.readFileSync(path.join(state,'results/product-fixture.json'),'utf8'));
  if(fixture.delivery) {
   const delivery=fixture.delivery;
   const call=(action,data,who)=>api.request('claim-token.php',{...data,action,bot_id:delivery.bot_id,recipient_hash:crypto.createHash('sha256').update(who).digest('hex')});
   const issuedDeps={claim:(token,who)=>call('claim',{token},who),transition:(action,proof,who)=>call(action,proof,who),forward:deps.forward};
   result=await deliverClaimedToken(issuedDeps,delivery.token,'fictional-issued-recipient');
   check('Actual PHP-issued token completes its assigned-bot Node delivery',result.success===true&&result.data.id===delivery.token_id&&sends===3);
   result=await deliverClaimedToken(issuedDeps,delivery.token,'fictional-issued-replay');
   check('The PHP-issued token remains consumed after completion',!result.success&&sends===3);
  } else check('Actual PHP token issuance fixture is present',false);
 }finally{await new Promise(resolve=>relay.close(resolve));}
}
main().catch(()=>check('Local bot integration harness completed',false)).finally(()=>{
 const result={scope:'Real Node HTTP client and owned SQL claim bridge; fake WhatsApp sender, no Baileys or recipient contacted',tests:rows,summary:{total:rows.length,passed:rows.filter(r=>r.passed).length,failed:rows.filter(r=>!r.passed).length}};
 fs.writeFileSync(path.join(state,'results/production-bot-integration.json'),JSON.stringify(result,null,2)+'\n');console.log(JSON.stringify(result.summary));process.exitCode=result.summary.failed?1:0;
});

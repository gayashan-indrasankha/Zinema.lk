'use strict';
// Real Baileys transport from the owner's D: installation, current local claim protocol.
// Deliberately does not execute the legacy entry, old dotenv/session, group jobs or history store.
const fs=require('node:fs'),path=require('node:path'),http=require('node:http'),crypto=require('node:crypto'),{pathToFileURL}=require('node:url'),{createRequire}=require('node:module');
const policy=require('./whatsapp-policy.cjs'),root=path.resolve(__dirname,'../..'),home=path.resolve(process.argv[2]||'');
let startupStage='configuration';
async function main(){
 if(path.dirname(home)!==path.join(root,'.runtime')||!/^audit-[0-9]{8}-[0-9]{6}$/.test(path.basename(home)))throw Error('live_configuration_denied');
 const c=policy.privateJson(path.join(home,'native-config.json')),owner=policy.privateJson(path.join(home,'ownership.json'));
 if(c.schema!==1||owner.native_owner!==c.owner||c.external_connections!==false||c.publishing!==false||c.environment.APP_ENV!=='test'||c.environment.BASE_URL!=='http://127.0.0.1:8088'||c.environment.DB_PORT!=='3307'||!/^[a-f0-9]{64}$/.test(c.control_key))throw Error('live_configuration_denied');
 const a=policy.approval(home,c.owner),directory=path.join(home,'native/real-whatsapp');fs.mkdirSync(directory,{recursive:true,mode:0o700});
 const session=path.join(directory,'account'),qrFile=path.join(directory,'pairing.svg'),store=path.join(directory,'deliveries.json');
 if(fs.lstatSync(directory).isSymbolicLink())throw Error('live_configuration_denied');
 const lease=require('../../whatsapp-bot-deploy/config/worker-lease').acquire(directory);
 process.on('exit',()=>{try{lease.release();}catch{}});
 Object.assign(process.env,{APP_ENV:'test',API_URL:'http://127.0.0.1:8088/api/whatsapp',API_KEY:c.environment.WHATSAPP_API_KEY,SESSION_PATH:directory,LOCAL_MEDIA_PATH:path.join(home,'native/bot/media'),BOT_INSTANCE_ID:'910000001',ALLOW_LIVE_WHATSAPP:'false'});
 const api=require('../../whatsapp-bot-deploy/config/api-client'),{deliverClaimedToken}=require('../../whatsapp-bot-deploy/config/token-delivery'),FileManager=require('../../whatsapp-bot-deploy/config/file-manager'),{inspectMedia}=require('../../whatsapp-bot-deploy/config/media-policy'),{ResourcePressure}=require('../../whatsapp-bot-deploy/config/resource-pressure');
 // Dependency/provider logs never expose phone numbers, message text, credentials or QR strings.
 console.log=()=>{};console.error=()=>{};console.warn=()=>{};console.info=()=>{};
 const guard=new ResourcePressure(process.env.LOCAL_MEDIA_PATH,{minFreeDiskBytes:128*1024*1024}),files=new FileManager(process.env.LOCAL_MEDIA_PATH,{resourceGuard:guard});
 let ledger={schema:1,owner:c.owner,outbox:[],intents:[]},enabled=true,closing=false,busy=false,socket=null,connected=false,connection='connecting',lastDisconnectCode=null,retries=0,reconnectTimer=null,saveChain=Promise.resolve();
 if(fs.existsSync(store)){ledger=policy.privateJson(store,262144);if(ledger.schema!==1||ledger.owner!==c.owner||!Array.isArray(ledger.outbox)||!Array.isArray(ledger.intents)||ledger.intents.length>200||ledger.outbox.length>200)throw Error('live_ledger_denied');for(const intent of ledger.intents)if(intent.state==='intent')intent.state='held';}
 function atomic(file,value){const temp=file+'.'+crypto.randomBytes(8).toString('hex')+'.tmp';const fd=fs.openSync(temp,'wx',0o600);try{fs.writeFileSync(fd,value);fs.fsyncSync(fd);}finally{fs.closeSync(fd);}fs.renameSync(temp,file);}
 const save=()=>atomic(store,JSON.stringify(ledger));save();
 const ready=()=>!closing&&enabled&&connected&&guard.sample().allowed&&!ledger.intents.some(i=>i.state==='held');
 async function heartbeat(){try{await api.request('bot-heartbeat.php',{bot_id:910000001,bot_phone:a.accountPhone,status:ready()?'online':'offline',queue_size:busy?1:0,uptime_seconds:Math.floor(process.uptime()),memory_usage_mb:Math.floor(process.memoryUsage().rss/1048576)});}catch{}}
 const projection=()=>({success:true,mode:'real-whatsapp',online:ready(),connection,lastDisconnectCode,busy,pairingAvailable:fs.existsSync(qrFile)&&connection==='pairing_required',outbox:ledger.outbox.map(i=>({...i})),held:ledger.intents.filter(i=>i.state==='held').map(i=>({job_id:i.job_id,state:'held'})),automaticJobs:false});
 async function deliver(token,recipient){
  if(!policy.recipientAllowed(recipient,a)||!ready()||busy||ledger.intents.length>=200)return {success:false,error:'offline_busy_or_review_required'};
  busy=true;let intent=null;
  try{
   const available=await api.getTokenData(token),file=available.valid===true&&available.data?files.getFilePath(available.data.content_type,available.data.content_id,available.data.part_number):null,media=file?inspectMedia(file):null;
   if(!media||media.bytes>a.maxAttachmentBytes)return {success:false,error:'token_or_approved_media_unavailable'};
   const hash=crypto.createHash('sha256').update(token).digest('hex');if(ledger.intents.some(i=>i.token_hash===hash))return {success:false,error:'already_attempted'};
   intent={job_id:crypto.randomBytes(16).toString('hex'),token_hash:hash,state:'intent'};ledger.intents.push(intent);save();
   const result=await deliverClaimedToken({claim:api.claimToken,transition:api.transitionToken,forward:async(jid,data)=>{
    if(!policy.recipientAllowed(jid,a)||!ready())return {success:false};
    const current=files.getFilePath(data.content_type,data.content_id,data.part_number),checked=current?inspectMedia(current):null;
    if(current!==file||!checked||checked.bytes!==media.bytes||checked.bytes>a.maxAttachmentBytes)return {success:false};guard.assertReady(checked.bytes);
    const sent=await socket.sendMessage(jid,{document:{url:current},mimetype:'video/mp4',fileName:'original-demo.mp4'});
    if(!sent?.key?.id)return {success:false};
    ledger.outbox.push({job_id:intent.job_id,content_type:data.content_type,content_id:Number(data.content_id),bytes:checked.bytes,simulated:false});save();return {success:true,tier:'owner-local-baileys'};
   }},token,recipient);
   intent.state=result.success?'completed':['uncertain','held'].includes(result.status)?'held':'rejected';save();return {success:result.success,state:intent.state,simulated:false};
  }catch{if(intent){intent.state='held';save();}return {success:false,error:'held_for_review'};}finally{busy=false;await heartbeat();}
 }
 function answer(res,status,value){res.writeHead(status,{'Content-Type':'application/json','Cache-Control':'no-store'});res.end(JSON.stringify(value));}
 const server=http.createServer(async(req,res)=>{
  const supplied=req.headers['x-local-control'];if(typeof supplied!=='string'||!/^[a-f0-9]{64}$/.test(supplied)||!crypto.timingSafeEqual(Buffer.from(supplied),Buffer.from(c.control_key)))return answer(res,403,{success:false});
  if(req.method!=='POST')return answer(res,405,{success:false});
  try{let raw='';for await(const chunk of req){raw+=chunk;if(Buffer.byteLength(raw)>2048)return answer(res,413,{success:false});}const input=JSON.parse(raw||'{}');
   if(req.url==='/status')return answer(res,200,projection());
   if(req.url==='/control'&&['online','offline'].includes(input.action)){enabled=input.action==='online';await heartbeat();return answer(res,200,projection());}
   if(req.url==='/shutdown'){if(busy)return answer(res,409,{success:false,error:'busy'});answer(res,200,{success:true});void shutdown();return;}
   if(req.url==='/deliver'&&typeof input.token==='string'&&/^[A-Z0-9]{12}$/.test(input.token)&&Object.keys(input).every(k=>k==='token'))return answer(res,200,await deliver(input.token,a.recipients[0]+'@s.whatsapp.net'));
   answer(res,400,{success:false});
  }catch{if(!res.writableEnded)answer(res,400,{success:false});}
 });
 const timer=setInterval(()=>void heartbeat(),5000);server.listen(9090,'127.0.0.1',()=>void heartbeat());
 async function shutdown(){if(closing)return;closing=true;connected=false;connection='stopped';clearInterval(timer);clearTimeout(reconnectTimer);await heartbeat();try{socket?.end(new Error('owner_local_stop'));}catch{}await saveChain.catch(()=>{});lease.release();server.close();setTimeout(()=>process.exit(0),500).unref();}
 process.on('SIGTERM',()=>void shutdown());process.on('SIGINT',()=>void shutdown());
 startupStage='sdk_import';
 const deps=createRequire(path.join(a.sourceRoot,'package.json')),sdk=await import(pathToFileURL(path.join(a.sourceRoot,'node_modules/@whiskeysockets/baileys/lib/index.js')).href),pino=deps('pino'),QRCode=deps('qrcode-terminal/vendor/QRCode'),level=deps('qrcode-terminal/vendor/QRCode/QRErrorCorrectLevel');
 startupStage='auth_state';
 const {state,saveCreds}=await sdk.useMultiFileAuthState(session);
 function qrSvg(value){const qr=new QRCode(-1,level.L);qr.addData(value);qr.make();const n=qr.getModuleCount();let cells='';for(let y=0;y<n;y++)for(let x=0;x<n;x++)if(qr.isDark(y,x))cells+=`M${x+4} ${y+4}h1v1h-1z`;return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${n+8} ${n+8}" width="360" height="360"><rect width="100%" height="100%" fill="white"/><path d="${cells}" fill="black"/></svg>`;}
 function connect(){
  if(closing)return;connection='connecting';
  socket=sdk.default({version:a.webVersion,auth:state,logger:pino({level:'silent'}),browser:sdk.Browsers.ubuntu('Chrome'),printQRInTerminal:false,markOnlineOnConnect:false,syncFullHistory:false,shouldSyncHistoryMessage:()=>false,fireInitQueries:false,connectTimeoutMs:25000});
  socket.ev.on('creds.update',()=>{saveChain=saveChain.then(saveCreds).catch(()=>{connection='credentials_write_failed';connected=false;void heartbeat();});});
  socket.ev.on('connection.update',update=>{
   if(update.qr){atomic(qrFile,qrSvg(update.qr));connection='pairing_required';}
   if(update.connection==='open'){
    if(policy.phone(socket.user?.id||state.creds.me?.id)!==a.accountPhone){connection='account_mismatch';connected=false;try{socket.end(new Error('account_mismatch'));}catch{}void heartbeat();return;}
    connected=true;connection='online';retries=0;if(fs.existsSync(qrFile))fs.unlinkSync(qrFile);void heartbeat();
   }
   if(update.connection==='close'){
    connected=false;const code=update.lastDisconnect?.error?.output?.statusCode;
    lastDisconnectCode=Number.isInteger(code)?code:null;
    if(closing||connection==='account_mismatch')return;
    if(code===sdk.DisconnectReason.loggedOut){connection='pairing_required';void heartbeat();return;}
    connection='reconnecting';void heartbeat();if(++retries<=5)reconnectTimer=setTimeout(connect,Math.min(10000,1000*retries));else connection='connection_failed';
   }
  });
  socket.ev.on('messages.upsert',event=>{if(event.type!=='notify')return;for(const message of event.messages||[]){const parsed=policy.command(message,a);if(parsed)void deliver(parsed.token,parsed.recipient);}});
 }
 startupStage='connecting';connect();
}
main().catch(error=>{try{const dir=path.join(home,'native/real-whatsapp');if(fs.existsSync(dir))fs.writeFileSync(path.join(dir,'startup-error.json'),JSON.stringify({error:'real_whatsapp_start_failed',stage:startupStage,errorType:error?.name||'Error',code:/^[A-Z_]+$/.test(error?.code||'')?error.code:null,secretsPrinted:false}));}catch{}process.exit(1);});

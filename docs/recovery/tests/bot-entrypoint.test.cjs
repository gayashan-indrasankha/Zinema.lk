'use strict';
// The real CLI guard is tested separately. This VM injects an SDK/API graph which
// cannot import HTTP, HTTPS, Baileys, dotenv or any actual authentication store.
const {test}=require('node:test'),assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path'),vm=require('node:vm'),{EventEmitter}=require('node:events');
const root=path.resolve(__dirname,'../../..'),privateRoot=path.join(root,'.runtime','bot-entrypoint-tests');fs.mkdirSync(privateRoot,{recursive:true});
async function settle(predicate){for(let i=0;i<100;i++){if(predicate())return;await new Promise(resolve=>setTimeout(resolve,1));}throw Error('Fake worker did not reach expected state');}
async function fixture(t,options={}) {
 const dir=fs.mkdtempSync(path.join(privateRoot,'case-')),sockets=[],logs=[],jobs=[],intervals=new Map(),timers=new Map(),openDescriptors=new Set(),apiCalls=[],exits=[],globals={};let id=0,now=0;
 const baseClock=Date.now(),reviewDeadline=baseClock+(options.reviewDuration||3600000);
 class FixtureDate extends Date {constructor(...args){super(...(args.length?args:[baseClock+now]));}static now(){return baseClock+now;}}
 const fakeProcess=new EventEmitter();Object.assign(fakeProcess,{pid:12345,env:{BOT_TIMEZONE:'Asia/Colombo',ADMIN_NUMBERS:'0000000002',STORAGE_GROUP_IDS:'fictional-cache'},memoryUsage:()=>({heapUsed:1000}),exit:code=>exits.push(code)});
 const config={appEnv:'test',apiUrl:'http://127.0.0.1:1',apiKey:'fictional-key',sessionPath:path.join(dir,'session'),mediaPath:path.join(dir,'media'),botInstanceId:'1',liveWhatsApp:false};
 const shim={...fs,openSync:(...args)=>{const fd=fs.openSync(...args);openDescriptors.add(fd);return fd;},closeSync:fd=>{openDescriptors.delete(fd);return fs.closeSync(fd);}};
 const timer=(fn,ms)=>{const handle={id:++id,unref(){return this;}};timers.set(handle,{fn,at:now+ms});return handle;};
 const api={testConnection:options.health||(async()=>true),request:async(endpoint,data)=>{apiCalls.push({endpoint,data});return {success:true};},getMessageId:async()=>({success:false}),addMessageId:async()=>({success:true}),claimToken:async()=>({success:false,error:'fixture_unavailable'}),transitionToken:async()=>({success:false}),logForward:async()=>{},needsRefresh:async()=>false,logRefresh:async()=>{},getRefreshStats:async()=>({}),getAgingMessages:async()=>({messages:[]})};
 const store={messages:{},bind(){},writeToFile(){},readFromFile(){},loadMessages:async()=>[]};
 const sdk={default:()=>{const socket={ev:new EventEmitter(),user:{id:'0000000000:7@s.whatsapp.net',name:'fictional-private-name'},sent:[],closed:0,sendMessage:async(jid,payload)=>{socket.sent.push({jid,payload});return {key:{id:'fixture-ack'}};},chatModify:async()=>{},end(){this.closed++;}};sockets.push(socket);return socket;},useMultiFileAuthState:async()=>({state:{creds:options.creds||{registered:true}},saveCreds:async()=>{}}),fetchLatestBaileysVersion:async()=>({version:[1,0,0]}),Browsers:{ubuntu:()=>[]},DisconnectReason:{loggedOut:401},makeInMemoryStore:()=>store,delay:async()=>{},downloadMediaMessage:async()=>Buffer.alloc(0)};
 const moduleCache=new Map();let subject;
 function load(file) {
  if(moduleCache.has(file))return moduleCache.get(file).exports;
  const module={exports:{}};moduleCache.set(file,module);
  const scopedRequire=name=>{
   if(name==='@whiskeysockets/baileys')return sdk;
   if(name==='pino')return ()=>({});
   if(name==='node-schedule')return {scheduleJob:(spec,callback)=>{const job={spec,callback,active:true,cancel(){this.active=false;},nextInvocation:()=>null};jobs.push(job);return job;}};
   if(name==='fs'||name==='node:fs')return shim;
   if(name==='./config/resource-pressure')return {ResourcePressure:class {sample(){return {allowed:!options.pressure};}assertReady(){if(options.pressure)throw Error('media_disk_pressure');}}};
   if(['path','node:path','crypto','node:crypto','events','node:events','os','node:os'].includes(name))return require(name);
   if(name.startsWith('.')) {
    const target=path.resolve(path.dirname(file),name)+'.js';
    if(target===path.join(root,'whatsapp-bot-deploy/config/environment.js'))return {readConfig:()=>config,assertLiveWhatsAppEnabled:value=>{assert.equal(value,config);assert.equal(value.liveWhatsApp,false);}};
    if(target===path.join(root,'whatsapp-bot-deploy/config/api-client.js'))return api;
    if(target===path.join(root,'whatsapp-bot-deploy/config/pairing-approval.js'))return {assertReviewedPairing:()=>{if(options.pairingReview===false)throw Error('pairing_review_required');return {validUntil:reviewDeadline};}};
    if(!target.startsWith(path.join(root,'whatsapp-bot-deploy')+path.sep))throw Error('Unexpected fixture module');
    return load(target);
   }
   throw Error('Unmocked dependency refused: '+name);
  };
  const scope={module,require:scopedRequire,Buffer,Date:FixtureDate,global:globals,process:fakeProcess,console:{log:value=>logs.push(value),error:value=>logs.push(value),warn:value=>logs.push(value),info(){},debug(){}},setTimeout:timer,clearTimeout:handle=>timers.delete(handle),setInterval:(fn,ms)=>{const key=++id;intervals.set(key,{fn,ms});return key;},clearInterval:key=>intervals.delete(key)};
  let code=fs.readFileSync(file,'utf8');if(file.endsWith(path.join('whatsapp-bot-deploy','index.js')))code+='\nmodule.exports={shutdownWorker,state:()=>({ready:workerReady,active:!!activeSocket,queue:messageQueue,reconnect:reconnectController})};';
  vm.runInNewContext(code,scope,{filename:file});return module.exports;
 }
 const emit=async(socket,event,value)=>{for(const callback of socket.ev.listeners(event))await callback(value);};
 t.after(async()=>{if(subject&&!exits.length){await subject.shutdownWorker(0);}for(const fd of openDescriptors)try{fs.closeSync(fd);}catch{}if(!path.resolve(dir).startsWith(privateRoot+path.sep))throw Error('Unsafe fixture cleanup');fs.rmSync(dir,{recursive:true,force:true});});
 subject=load(path.join(root,'whatsapp-bot-deploy/index.js'));await settle(()=>sockets.length===1||exits.length>0);
 return {subject,config,sockets,logs,jobs,intervals,timers,apiCalls,exits,emit,globals,async tick(ms){now+=ms;for(const [handle,task] of [...timers])if(task.at<=now){timers.delete(handle);await task.fn();}await new Promise(resolve=>setImmediate(resolve));}};
}
test('Fake SDK worker has one set of jobs/timers across open, duplicate open and reconnect',async t=>{
 const f=await fixture(t);const first=f.sockets[0];await f.emit(first,'connection.update',{connection:'open'});await settle(()=>f.subject.state().ready);assert.equal(f.jobs.filter(j=>j.active).length,4);assert.equal(f.intervals.size,2);assert.equal(first.sent.length,0);
 await f.emit(first,'connection.update',{connection:'open'});assert.equal(f.intervals.size,2);assert.equal(f.jobs.length,4);
 await f.emit(first,'connection.update',{connection:'close',lastDisconnect:{error:{message:'fictional-private-error'}}});assert.equal(f.intervals.size,0);assert.equal(f.timers.size,2);
 await f.tick(5000);assert.equal(f.sockets.length,2);await f.emit(f.sockets[1],'connection.update',{connection:'open'});assert.equal(f.jobs.length,4);assert.equal(f.intervals.size,2);
 await f.subject.shutdownWorker(0);assert.equal(f.exits[0],0);assert.equal(f.intervals.size,0);assert.equal(f.timers.size,0);assert.equal(f.jobs.filter(j=>j.active).length,0);assert.equal(fs.existsSync(path.join(f.config.sessionPath,'worker.lock')),false);assert.equal(f.logs.some(line=>String(line).includes('fictional-private')),false);
});
test('Close during API readiness cannot resurrect jobs or online timers from the stale open',async t=>{let release;const gate=new Promise(resolve=>release=resolve);const f=await fixture(t,{health:async()=>gate}),socket=f.sockets[0];const opening=f.emit(socket,'connection.update',{connection:'open'});await Promise.resolve();await f.emit(socket,'connection.update',{connection:'close',lastDisconnect:{}});release(true);await opening;assert.equal(f.subject.state().ready,false);assert.equal(f.jobs.length,0);assert.equal(f.intervals.size,0);});
test('QR payload is never logged and stops the worker for a separately reviewed pairing flow',async t=>{const f=await fixture(t);await f.emit(f.sockets[0],'connection.update',{qr:'fictional-private-pairing-payload'});assert.equal(f.exits[0],1);assert.equal(f.logs.some(line=>String(line).includes('fictional-private-pairing-payload')),false);assert.equal(f.timers.size,0);});
test('Unregistered and local-fake credentials stop before any worker socket',async t=>{for(const creds of [{registered:false},{registered:true,zinemaLocalFake:true}]){const f=await fixture(t,{creds});assert.equal(f.sockets.length,0);assert.equal(f.exits[0],1);assert.equal(f.intervals.size,0);assert.equal(f.jobs.length,0);}});
test('Registered credentials without reviewed owner approval cannot start a socket',async t=>{const f=await fixture(t,{pairingReview:false});assert.equal(f.sockets.length,0);assert.equal(f.exits[0],1);assert.equal(f.jobs.length,0);assert.equal(f.logs.some(v=>String(v).includes('pairing_review_required')),false);});
test('Reconnect cannot extend the owner test window or permit later scheduled sends',async t=>{const f=await fixture(t,{reviewDuration:10000});await f.emit(f.sockets[0],'connection.update',{connection:'open'});await f.emit(f.sockets[0],'connection.update',{connection:'close',lastDisconnect:{}});await f.tick(5000);assert.equal(f.sockets.length,2);await f.emit(f.sockets[1],'connection.update',{connection:'open'});assert.equal(f.subject.state().ready,true);await f.tick(5000);assert.equal(f.exits[0],1);assert.equal(f.subject.state().ready,false);assert.equal(f.jobs.filter(j=>j.active).length,0);assert.equal(f.timers.size,0);});
test('Signal shutdown has a hard drain deadline and refuses late socket events',async t=>{const f=await fixture(t);await f.emit(f.sockets[0],'connection.update',{connection:'open'});let release;const gate=new Promise(resolve=>release=resolve);f.subject.state().queue.add(async()=>gate);const pending=f.subject.shutdownWorker(0);assert.equal(f.subject.state().queue.add(async()=>{}),false);await f.tick(5000);await pending;assert.equal(f.exits[0],0);const count=f.sockets.length;await f.emit(f.sockets[0],'connection.update',{connection:'close',lastDisconnect:{}});await f.tick(60000);assert.equal(f.sockets.length,count);assert.equal(f.timers.size,0);release();});
test('Unavailable API readiness fails closed with session evidence retained',async t=>{const f=await fixture(t,{health:async()=>false});await f.emit(f.sockets[0],'connection.update',{connection:'open'});assert.equal(f.exits[0],1);assert.equal(f.subject.state().ready,false);assert.equal(f.jobs.length,0);assert.equal(f.intervals.size,0);assert.equal(fs.existsSync(f.config.sessionPath),true);});

test('Pressure reports offline and blocks new SDK sends without replaying work',async t=>{
 const options={},f=await fixture(t,options),socket=f.sockets[0];await f.emit(socket,'connection.update',{connection:'open'});options.pressure=true;
 for(const interval of f.intervals.values())if(interval.ms===30000)await interval.fn();
 await settle(()=>f.apiCalls.some(call=>call.endpoint==='bot-heartbeat.php' && call.data.status==='offline'));
 await f.emit(socket,'messages.upsert',{type:'notify',messages:[{key:{id:'fixture-pressure-help',fromMe:false,remoteJid:'0000000002@s.whatsapp.net'},message:{conversation:'!help'}}]});
 assert.equal(socket.sent.length,0);assert.equal(f.subject.state().queue.getStatus().size,0);
});
test('Fake incoming commands are gated by readiness and legacy processing reports held status',async t=>{const f=await fixture(t),socket=f.sockets[0];const message=(id,text)=>({type:'notify',messages:[{key:{id,fromMe:false,remoteJid:'0000000002@s.whatsapp.net'},message:{conversation:text}}]});await f.emit(socket,'messages.upsert',message('fixture-before-open','!help'));assert.equal(socket.sent.length,0);await f.emit(socket,'connection.update',{connection:'open'});await f.emit(socket,'messages.upsert',message('fixture-help','!help'));assert.equal(socket.sent.length>0,true);await f.emit(socket,'messages.upsert',message('fixture-process','!process'));assert.equal(socket.sent.some(m=>m.payload.text?.includes('held for operator review')),true);assert.equal(f.logs.some(line=>String(line).includes('0000000002')),false);});

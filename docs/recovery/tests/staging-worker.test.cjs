'use strict';
const {test}=require('node:test'),assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path'),vm=require('node:vm'),{Readable,Writable}=require('node:stream'),{spawnSync}=require('node:child_process');
const root=path.resolve(__dirname,'../../..'),base=path.join(root,'.runtime','staging-worker-tests');fs.mkdirSync(base,{recursive:true});
const {ResourcePressure}=require('../../../whatsapp-bot-deploy/config/resource-pressure');
const FileManager=require('../../../whatsapp-bot-deploy/config/file-manager');
const {ExtractorWorker}=require('../../../fb-video-api/queue-worker.cjs');
const {queueTransport}=require('../../../fb-video-api/queue-transport.cjs');
const {waitForExtraction}=require('../../../js/extractor-queue');
const MiB=1024*1024,GiB=1024*MiB;
function directory(t){const p=fs.mkdtempSync(path.join(base,'case-'));t.after(()=>{if(path.dirname(p)!==base)throw Error('fixture_boundary');fs.rmSync(p,{recursive:true,force:true});});return p;}
function pressure(dir,overrides={}){return new ResourcePressure(dir,{memory:()=>({rss:64*MiB}),freeMemory:()=>2*GiB,disk:()=>({bavail:32*1024,bsize:MiB}),...overrides});}
test('RSS, free RAM, disk reserve and unreadable statistics fail closed',t=>{
 const dir=directory(t);
 for(const [options,reason] of [[{memory:()=>({rss:384*MiB})},'worker_memory_pressure'],[{freeMemory:()=>128*MiB},'system_memory_pressure'],[{disk:()=>({bavail:9*1024,bsize:MiB})},'media_disk_pressure'],[{disk:()=>{throw Error('private-device');}},'resource_status_unknown'],[{memory:()=>({heapUsed:1})},'resource_status_unknown']]) {
  const guard=pressure(dir,options);assert.equal(guard.sample().reason,reason);assert.throws(()=>guard.assertReady(),new RegExp(reason));
 }
 assert.equal(pressure(dir).sample(23*GiB).allowed,false);assert.equal(pressure(dir).sample(2*GiB).allowed,true);
});
test('Disk pressure rejects ingestion before reading the source or opening a temporary file',async t=>{
 const dir=directory(t),manager=new FileManager(dir,{resourceGuard:pressure(dir,{disk:()=>({bavail:1,bsize:MiB})})});let read=false;
 const source=Readable.from((async function*(){read=true;yield Buffer.from('fixture');})());
 await assert.rejects(manager.saveStream('movie',1,null,source),/media_disk_pressure/);assert.equal(read,false);assert.equal(manager.getFilePath('movie',1),null);
});
test('Pressure during a real stream removes only its own partial, preserving older media',async t=>{
 const dir=directory(t);let checks=0;
 const guard=pressure(dir,{disk:()=>({bavail:++checks>=3?1:32768,bsize:MiB})}),manager=new FileManager(dir,{resourceGuard:guard});
 const old=manager.getSavePath('movie',1,null);fs.writeFileSync(old,'previous fictional original');
 async function* source(){for(let i=0;i<256;i++)yield Buffer.alloc(65536,17);}
 await assert.rejects(manager.saveStream('movie',1,null,Readable.from(source()),'.mp4',{expectedBytes:16*MiB}),/media_disk_pressure/);
 assert.equal(fs.readFileSync(old,'utf8'),'previous fictional original');assert.deepEqual(fs.readdirSync(path.dirname(old)),['full.mp4']);
});
test('Injected ENOSPC removes the newly created partial and does not publish',async t=>{
 const dir=directory(t),file=path.join(root,'whatsapp-bot-deploy/config/file-manager.js'),moduleState={exports:{}},shim={...fs};
 shim.createWriteStream=(target,options)=>{
  const fd=fs.openSync(target,options.flags,options.mode);return new Writable({write(_chunk,_e,next){const e=Error('fictional disk full');e.code='ENOSPC';next(e);},destroy(error,next){fs.closeSync(fd);next(error);}});
 };
 vm.runInNewContext(fs.readFileSync(file,'utf8'),{module:moduleState,Buffer,console:{log(){},error(){}},require:name=>name==='fs'?shim:name.startsWith('.')?require(path.resolve(path.dirname(file),name)):require(name)},{filename:file});
 const manager=new moduleState.exports(dir,{resourceGuard:pressure(dir)});
 await assert.rejects(manager.saveStream('movie',1,null,Readable.from([Buffer.from('fictional')])),{code:'ENOSPC'});
 assert.deepEqual(fs.readdirSync(manager.ensureContentFolder('movie',1)),[]);
});
test('A real 64 MiB ingestion stays within the configured memory ceiling',async t=>{
 const dir=directory(t),manager=new FileManager(dir,{resourceGuard:pressure(dir)});let peak=process.memoryUsage().rss;const before=peak;
 async function* source(){for(let i=0;i<1024;i++){peak=Math.max(peak,process.memoryUsage().rss);yield Buffer.alloc(65536,7);}}
 const target=await manager.saveStream('movie',1,null,Readable.from(source()),'.mp4',{expectedBytes:64*MiB});
 assert.equal(fs.statSync(target).size,64*MiB);assert.ok(peak-before<128*MiB);assert.ok(peak<384*MiB);
 fs.writeFileSync(path.join(base,'real-stream-result.json'),JSON.stringify({bytes:64*MiB,peakRssBytes:peak,rssGrowthBytes:peak-before,physicalScratchFile:true},null,2));
});
test('A constrained child processes virtual 2 GiB with bounded RSS and no large disk file',()=>{
 const result=spawnSync(process.execPath,['--max-old-space-size=64',path.join(__dirname,'worker-memory-child.cjs')],{encoding:'utf8',timeout:60000,windowsHide:true});
 assert.equal(result.status,0,result.stderr);const report=JSON.parse(result.stdout);assert.equal(report.bytes,2*GiB);assert.equal(report.physicalMediaBytesWritten,0);assert.ok(report.peakRssBytes<256*MiB);assert.ok(report.rssGrowthBytes<96*MiB);
 const evidence=path.join(base,'memory-result.json');fs.writeFileSync(evidence,JSON.stringify(report,null,2));
});
test('Extractor intent is written before begin and side effects; completion clears intent',async t=>{
 const dir=directory(t),job={job_id:'a'.repeat(32),receipt:'b'.repeat(64),url:'https://www.facebook.com/share/v/fictional/'},calls=[];
 const worker=new ExtractorWorker({directory:dir,bridge:async input=>{calls.push(input.action);if(input.action==='claim')return {job};if(input.action==='begin')assert.equal(worker.read().state,'intent');return {success:true};},extract:async()=>{assert.equal(worker.read().state,'working');return 'fictional-result';}});
 assert.equal((await worker.runOnce()).state,'done');assert.equal(worker.read().state,'idle');assert.deepEqual(calls,['heartbeat','claim','begin','complete']);
});
test('Lost completion acknowledgement and restart stay held without another extraction',async t=>{
 const dir=directory(t);let extracts=0,calls=0;
 const deps={directory:dir,bridge:async input=>{calls++;if(input.action==='claim')return {job:{job_id:'a'.repeat(32),receipt:'b'.repeat(64),url:'fictional'}};if(input.action==='complete')throw Error('lost ack');return {success:true};},extract:async()=>{extracts++;return 'fictional-result';}};
 assert.equal((await new ExtractorWorker(deps).runOnce()).state,'held');const before=calls;
 assert.equal((await new ExtractorWorker(deps).runOnce()).state,'held');assert.equal(extracts,1);assert.equal(calls,before);
});
test('Corrupt extractor intent cannot claim new work',async t=>{
 const dir=directory(t);fs.writeFileSync(path.join(dir,'extraction-intent.json'),'{broken');let calls=0;
 const worker=new ExtractorWorker({directory:dir,bridge:async()=>{calls++;},extract:async()=>{throw Error();}});
 assert.equal((await worker.runOnce()).state,'unavailable');assert.equal(calls,0);assert.equal(fs.readFileSync(worker.file,'utf8'),'{broken');
});
test('Separate workers sharing private state cannot both claim concurrently',async t=>{
 const dir=directory(t);let release,calls=0;const gate=new Promise(resolve=>release=resolve);
 const deps={directory:dir,bridge:async input=>{calls++;if(input.action==='heartbeat')await gate;return {job:null};},extract:async()=>{throw Error();}};
 const first=new ExtractorWorker(deps).runOnce();await Promise.resolve();
 assert.equal((await new ExtractorWorker(deps).runOnce()).state,'held');assert.equal(calls,1);release();assert.equal((await first).state,'idle');
});
test('Administrator polling supports completion, held review and a bounded waiting period',async()=>{
 let states=['queued','working','done'],calls=0;
 const request=async()=>({mode:'account-queue',job_id:'a'.repeat(32),state:states[calls++],url:calls===3?'fictional':undefined});
 assert.equal((await waitForExtraction(request,'fictional',()=>{},async()=>{})).url,'fictional');
 await assert.rejects(waitForExtraction(async()=>({mode:'account-queue',state:'held'}),'fictional'),/held_for_review/);
 let polls=0;await assert.rejects(waitForExtraction(async()=>{polls++;return {mode:'account-queue',state:'queued',job_id:'a'.repeat(32)};},'fictional',()=>{},async()=>{}),/still_pending/);assert.equal(polls,41);
});
test('Private HTTPS transport needs explicit configuration and cannot disable TLS or redirect',async()=>{
 const {EventEmitter}=require('node:events');let calls=0;const options=[];
 const c={origin:'https://staging.example.invalid',key:'b'.repeat(64),mode:'private-staging',enabled:true,externalConnections:true,reviewed:true};
 const fakeRequest=(url,opts,reply)=>{
  calls++;options.push(opts);assert.equal(url,c.origin+'/api/extractor-work.php');
  const request=new EventEmitter();request.destroy=error=>{request.emit('error',error);request.emit('close');};
  request.end=()=>{const response=new EventEmitter();response.statusCode=calls===1?200:302;response.headers={'content-type':'application/json',location:'https://external.example.invalid'};response.setEncoding=()=>{};response.destroy=error=>response.emit('error',error);reply(response);response.emit('data','{"success":true}');response.emit('end');request.emit('close');};return request;
 };
 for(const mutate of [{enabled:false},{externalConnections:false},{reviewed:false},{origin:'http://staging.example.invalid'},{origin:'https://staging.example.invalid/private'},{origin:'https://user@staging.example.invalid'}])assert.throws(()=>queueTransport({...c,...mutate}),/transport_denied/);
 const bridge=queueTransport(c,{httpsRequest:fakeRequest,httpRequest:()=>{throw Error('Unexpected HTTP');}});
 assert.equal((await bridge({action:'heartbeat'})).success,true);await assert.rejects(bridge({action:'heartbeat'}),/worker_unavailable/);
 assert.equal(calls,2);assert.equal(options[0].rejectUnauthorized,true);assert.equal(options[0].minVersion,'TLSv1.2');assert.equal(options[0].agent,false);
});
test('Outbound transport uses only its authenticated fixed loopback endpoint',async t=>{
 const http=require('node:http'),key='c'.repeat(64);let calls=0;
 const server=http.createServer((request,response)=>{
  calls++;assert.equal(request.url,'/api/extractor-work.php');assert.equal(request.method,'POST');assert.equal(request.headers['x-extractor-worker-key'],key);
  let raw='';request.on('data',part=>raw+=part);request.on('end',()=>{assert.equal(JSON.parse(raw).action,'heartbeat');response.writeHead(200,{'Content-Type':'application/json'});response.end('{"success":true}');});
 });
 await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));t.after(()=>new Promise(resolve=>server.close(resolve)));
 const bridge=queueTransport({origin:'http://127.0.0.1:'+server.address().port,key,mode:'local-fixture',externalConnections:false});
 assert.equal((await bridge({action:'heartbeat'})).success,true);await assert.rejects(bridge({padding:'x'.repeat(3000)}),/contract_denied/);assert.equal(calls,1);
});

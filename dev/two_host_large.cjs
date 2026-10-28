'use strict';
// Original sparse byte fixtures; no codec/SDK/provider certification.
const fs=require('node:fs'),path=require('node:path'),crypto=require('node:crypto'),{Readable}=require('node:stream');
if(process.env.ZINEMA_TWO_HOST_FIXTURES!=='1'||process.env.ALLOW_LIVE_WHATSAPP!=='false')throw Error('fixture_required');
const home='/state',MAX=2147483648,rows=[];let count=0;
Object.assign(process.env,{APP_ENV:'test',API_URL:'http://127.0.0.1:8088/api/whatsapp',API_KEY:'fictional-unused',SESSION_PATH:home+'/session',LOCAL_MEDIA_PATH:home+'/media',BOT_INSTANCE_ID:'1'});
console.log=()=>{};console.error=()=>{};
const FileManager=require('/opt/zinema/config/file-manager'),Forwarder=require('/opt/zinema/config/multi-tier-forwarder');
function check(name,value){rows.push({name,passed:!!value});}
function sparse(file,bytes){fs.mkdirSync(path.dirname(file),{recursive:true});const fd=fs.openSync(file,'wx',0o600);try{fs.ftruncateSync(fd,bytes);if(bytes)fs.writeSync(fd,Buffer.from('fictional-boundary'),0,Math.min(18,bytes),0);}finally{fs.closeSync(fd);}}
async function main(){
 fs.mkdirSync(home+'/session',{recursive:true});const manager=new FileManager(home+'/media');
 for(const [id,size] of [[1,MAX-1],[2,MAX],[3,MAX+1],[4,0]])sparse(manager.getSavePath('movie',id,null),size);
 let sends=0;
 const api={addMessageId:async()=>({success:true}),updateCacheMessageId:async()=>({success:true})};
 const fake={sendMessage:async(_who,payload)=>{sends++;if(payload.document?.url){const hash=crypto.createHash('sha256');for await(const chunk of fs.createReadStream(payload.document.url,{highWaterMark:65536})){count+=chunk.length;hash.update(chunk);}return {key:{id:'fictional-ack'},fixtureDigest:hash.digest('hex')};}return {key:{id:'fictional-ack'}};}};
 const forwarder=new Forwarder(fake,manager,api);
 const above=await forwarder.performTier3Upload('fixture-recipient','movie',3,null);
 check('A 2 GiB plus one byte source is rejected before any fake send',above.success===false&&sends===0);
 const empty=await forwarder.performTier3Upload('fixture-recipient','movie',4,null);
 check('Empty local source is rejected before any fake send',empty.success===false&&sends===0);
 check('Below and exact 2 GiB lengths remain 64-bit integers',manager.getFileInfo('movie',1).size===MAX-1&&manager.getFileInfo('movie',2).size===MAX);
 let refused=false;try{await manager.readFile('movie',2);}catch(error){refused=error.message==='media_stream_required';}
 check('Large originals cannot enter the whole-buffer reader',refused);
 const bounded=manager.readStream('movie',2);let first;for await(const chunk of bounded){first=chunk.length;break;}
 check('Large-file reader uses bounded stream chunks',first>0&&first<=65536);
 const result=await forwarder.performTier3Upload('fixture-recipient','movie',2,null);
 check('Actual 2 GiB file streams to fake document sink without truncation',result.success===true&&sends===1&&count===MAX);
 check('Bounded-heap large-file fake send stays below 192 MiB peak RSS',process.resourceUsage().maxRSS<192*1024);
 if(typeof manager.saveStream==='function') {
  const source=manager.getFilePath('movie',2);const hash=crypto.createHash('sha256');
  for await(const chunk of fs.createReadStream(source))hash.update(chunk);
  const expected=hash.digest('hex');
  const stored=await manager.saveStream('episode',1,null,fs.createReadStream(source),'.mp4',{expectedBytes:MAX,expectedSha256:expected});
  const actual=crypto.createHash('sha256');for await(const chunk of fs.createReadStream(stored))actual.update(chunk);
  check('Actual 2 GiB streaming ingestion has matching length and SHA-256',fs.statSync(stored).size===MAX&&actual.digest('hex')===expected);
  check('Streamed ingestion also stays below 192 MiB peak RSS',process.resourceUsage().maxRSS<192*1024);
  const original=await manager.saveStream('episode',2,null,Readable.from([Buffer.from('original')]),'.mp4');
  let rejected=false;try{await manager.saveStream('episode',2,null,Readable.from([Buffer.from('conflict')]),'.mp4');}catch{rejected=true;}
  check('Conflicting stream never overwrites an owned original',rejected&&fs.readFileSync(original,'utf8')==='original');
  rejected=false;try{await manager.saveStream('episode',3,null,Readable.from([Buffer.alloc(17)]),'.mp4',{maxBytes:16});}catch{rejected=true;}
  check('Streaming quota overflow publishes no destination',rejected&&manager.getFilePath('episode',3)===null);
  rejected=false;try{await manager.saveStream('episode',4,null,Readable.from([Buffer.from('truncated')]),'.mp4',{expectedBytes:20});}catch{rejected=true;}
  check('Truncated stream publishes no destination',rejected&&manager.getFilePath('episode',4)===null);
  rejected=false;try{await manager.saveStream('episode',5,null,Readable.from([Buffer.from('mismatch')]),'.mp4',{expectedSha256:'0'.repeat(64)});}catch{rejected=true;}
  check('Hash mismatch publishes no destination',rejected&&manager.getFilePath('episode',5)===null);
  const interrupted=Readable.from((async function*(){yield Buffer.from('partial');throw Error('fixture_disconnect');})());
  rejected=false;try{await manager.saveStream('episode',6,null,interrupted,'.mp4');}catch{rejected=true;}
  check('Interrupted ingestion preserves original and removes only its private partial',rejected&&manager.getFilePath('episode',6)===null&&fs.readFileSync(original,'utf8')==='original'&&!fs.readdirSync(path.dirname(manager.getSavePath('episode',6))).some(f=>f.startsWith('.ingest-')));
 } else check('Streaming ingestion adapter is available',false);
}
main().catch(()=>check('Large-file local fixture completed without setup failure',false)).finally(()=>{
 const report={scope:'Local fictional bytes and fake document sender; no real video/codec/SDK/provider',max_bytes:MAX,streamed_bytes:count,peak_rss_kib:process.resourceUsage().maxRSS,tests:rows,summary:{total:rows.length,passed:rows.filter(x=>x.passed).length,failed:rows.filter(x=>!x.passed).length}};
 fs.writeFileSync('/results/two-host-large.json',JSON.stringify(report,null,2)+'\n');process.stdout.write(JSON.stringify({summary:report.summary,peak_rss_kib:report.peak_rss_kib})+'\n');process.exitCode=report.summary.failed?1:0;
});

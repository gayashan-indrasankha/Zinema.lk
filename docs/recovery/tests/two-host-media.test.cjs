'use strict';
const {test}=require('node:test'),assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path'),crypto=require('node:crypto');
const {Readable}=require('node:stream');
const FileManager=require('../../../whatsapp-bot-deploy/config/file-manager');
const {inspectMedia,MAX_MEDIA_BYTES}=require('../../../whatsapp-bot-deploy/config/media-policy');
const base=path.resolve(__dirname,'../../../.runtime/two-host-media-tests');fs.mkdirSync(base,{recursive:true});
function fixture(t){const dir=fs.mkdtempSync(path.join(base,'case-'));t.after(()=>{if(path.dirname(dir)!==base)throw Error('fixture_boundary');fs.rmSync(dir,{recursive:true,force:true});});return new FileManager(dir);}
const bytes=value=>Readable.from([Buffer.from(value)]);
test('2 GiB is an exact integer ceiling distinct from decimal 2 GB',()=>{assert.equal(MAX_MEDIA_BYTES,2147483648);assert.ok(MAX_MEDIA_BYTES>2000000000);});
test('Stream ingestion validates length/hash and preserves identical published bytes',async t=>{
 const m=fixture(t),value='original fictional fixture',digest=crypto.createHash('sha256').update(value).digest('hex');
 const target=await m.saveStream('movie',1,null,bytes(value),'.mp4',{expectedBytes:Buffer.byteLength(value),expectedSha256:digest});
 assert.equal(await m.saveStream('movie',1,null,bytes(value)),target);assert.equal(fs.readFileSync(target,'utf8'),value);
 await assert.rejects(m.saveStream('movie',1,null,bytes('different')),/media_conflict/);assert.equal(fs.readFileSync(target,'utf8'),value);
});
test('Cooperating stream publishers cannot replace each other',async t=>{
 const m=fixture(t),results=await Promise.allSettled([m.saveStream('movie',2,null,bytes('first')),m.saveStream('movie',2,null,bytes('second'))]);
 assert.equal(results.filter(r=>r.status==='fulfilled').length,1);assert.ok(['first','second'].includes(fs.readFileSync(m.getFilePath('movie',2),'utf8')));
});
test('Overflow, empty, truncation and digest failures publish no destination',async t=>{
 const m=fixture(t);let id=10;
 for(const [source,options] of [[bytes('overflow'),{maxBytes:2}],[bytes(''),{}],[bytes('short'),{expectedBytes:10}],[bytes('bad'),{expectedSha256:'0'.repeat(64)}]]){
  await assert.rejects(m.saveStream('episode',id,null,source,'.mp4',options));assert.equal(m.getFilePath('episode',id),null);id++;
 }
});
test('Aborted and disconnected streams leave no partial published file',async t=>{
 const m=fixture(t),source=Readable.from((async function*(){yield Buffer.from('partial');throw Error('fictional_disconnect');})());
 await assert.rejects(m.saveStream('movie',20,null,source),/fictional_disconnect/);
 const controller=new AbortController();controller.abort();
 await assert.rejects(m.saveStream('movie',21,null,bytes('cancelled'),'.mp4',{signal:controller.signal}),/abort/i);
 for(const id of [20,21]){assert.equal(m.getFilePath('movie',id),null);assert.deepEqual(fs.readdirSync(m.ensureContentFolder('movie',id)),[]);}
});
test('Invalid lengths and caps cannot raise the local media ceiling',async t=>{
 const m=fixture(t);
 for(const maxBytes of [0,-1,MAX_MEDIA_BYTES+1,Infinity,1.5])await assert.rejects(m.saveStream('movie',30,null,bytes('x'),'.mp4',{maxBytes}));
 await assert.rejects(m.saveStream('movie',30,null,bytes('x'),'.mp4',{expectedBytes:MAX_MEDIA_BYTES+1}));
});
test('Preflight rejects empty files, hardlinks and unsupported extensions',async t=>{
 const m=fixture(t),file=await m.saveStream('movie',40,null,bytes('fixture'),'.mkv');
 assert.deepEqual(inspectMedia(file),{bytes:7,mimetype:'video/x-matroska'});
 const empty=m.getSavePath('movie',41,null);fs.writeFileSync(empty,'');assert.throws(()=>inspectMedia(empty));
 const linked=m.getSavePath('movie',42,null);fs.linkSync(file,linked);assert.throws(()=>inspectMedia(linked));
 assert.throws(()=>inspectMedia(path.join(m.basePath,'file.exe')));
});

'use strict';
// Fresh constrained process, virtual 2 GiB sink; no giant allocation or media-volume writes.
const fs=require('node:fs'),path=require('node:path'),vm=require('node:vm'),{Readable,Writable}=require('node:stream');
const root=path.resolve(__dirname,'../../..'),moduleState={exports:{}},files=new Map(),directories=new Set([root]);
let diskBytes=0,peak=process.memoryUsage().rss;
const sample=()=>{peak=Math.max(peak,process.memoryUsage().rss);};const before=peak;
const fake={existsSync:p=>files.has(p)||directories.has(p),lstatSync:p=>({isSymbolicLink:()=>false,isFile:()=>files.has(p)}),
    mkdirSync:p=>directories.add(p),statSync:p=>({isFile:()=>true,size:files.get(p)||0}),
    createWriteStream:p=>new Writable({highWaterMark:65536,write(chunk,_e,next){diskBytes+=chunk.length;files.set(p,diskBytes);sample();next();}}),
    promises:{open:async()=>({sync:async()=>{},close:async()=>{}}),link:async(a,b)=>{files.set(b,files.get(a));},unlink:async p=>{files.delete(p);}}};
const file=path.join(root,'whatsapp-bot-deploy/config/file-manager.js');
vm.runInNewContext(fs.readFileSync(file,'utf8'),{module:moduleState,exports:moduleState.exports,Buffer,console:{log(){},error(){}},require:name=>name==='fs'?fake:name.startsWith('.')?require(path.resolve(path.dirname(file),name)):require(name)},{filename:file});
const manager=new moduleState.exports(path.join(root,'virtual-only'));
const total=2*1024*1024*1024,chunk=Buffer.alloc(65536,23);
async function* source(){for(let count=0;count<total;count+=chunk.length)yield chunk;}
manager.saveStream('movie',1,null,Readable.from(source()),'.mp4',{expectedBytes:total}).then(()=>{
    sample();console.log(JSON.stringify({bytes:diskBytes,peakRssBytes:peak,rssGrowthBytes:peak-before,heapLimitMiB:64,physicalMediaBytesWritten:0,virtualSink:true}));
}).catch(()=>{console.error('Constrained memory fixture failed.');process.exitCode=1;});

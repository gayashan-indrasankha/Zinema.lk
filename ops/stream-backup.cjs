'use strict';
// Files only under ignored local fixtures. No shell, remote store or network adapter.
const fs=require('node:fs'),path=require('node:path'),crypto=require('node:crypto');
const {pipeline,finished}=require('node:stream/promises'),{Transform}=require('node:stream');
const {checked,read}=require('./private-files.cjs');
const magic=Buffer.from('ZINEMA-STREAM-BACKUP/1\n'),MAX=1099511627776;
async function digest(file){file=checked(file);const hash=crypto.createHash('sha256');for await(const chunk of fs.createReadStream(file))hash.update(chunk);return hash.digest('hex');}
function temporary(target){return target+'.'+crypto.randomBytes(12).toString('hex')+'.tmp';}
function publish(temp,target){const fd=fs.openSync(temp,'r+');try{fs.fsyncSync(fd);}finally{fs.closeSync(fd);}fs.linkSync(temp,target);fs.unlinkSync(temp);}
async function transform(operation,input,target,keyFile,{writersQuiesced=false,maxBytes=10737418240}={}) {
 if(!['encrypt','decrypt'].includes(operation)||!Number.isSafeInteger(maxBytes)||maxBytes<1||maxBytes>MAX||operation==='encrypt'&&writersQuiesced!==true)throw Error('backup_review_required');
 input=checked(input);target=checked(target,{absent:true});keyFile=checked(keyFile);
 if([path.dirname(input),path.dirname(target)].includes(path.dirname(keyFile)))throw Error('backup_key_must_be_separate');
 const text=read(keyFile,128).toString().trim();if(!/^[a-f0-9]{64}$/.test(text))throw Error('backup_key_required');
 const key=Buffer.from(text,'hex'),stat=fs.statSync(input),overhead=magic.length+28;
 if(stat.size>(operation==='decrypt'?maxBytes+overhead:maxBytes))throw Error('backup_size_limit');
 const temp=temporary(target);let output;
 try {
  if(operation==='encrypt') {
   const nonce=crypto.randomBytes(12),header=Buffer.concat([magic,nonce]),cipher=crypto.createCipheriv('aes-256-gcm',key,nonce);cipher.setAAD(header);
   output=fs.createWriteStream(temp,{flags:'wx',mode:0o600});output.write(header);
   await pipeline(fs.createReadStream(input),cipher,output,{end:false});output.end(cipher.getAuthTag());await finished(output);
  }else {
   if(stat.size<overhead)throw Error('backup_authentication_failed');
   const fd=fs.openSync(input,'r'),header=Buffer.alloc(magic.length+12),tag=Buffer.alloc(16);
   try{if(fs.readSync(fd,header,0,header.length,0)!==header.length||fs.readSync(fd,tag,0,16,stat.size-16)!==16||!header.subarray(0,magic.length).equals(magic))throw Error('backup_authentication_failed');}finally{fs.closeSync(fd);}
   const decipher=crypto.createDecipheriv('aes-256-gcm',key,header.subarray(magic.length));decipher.setAAD(header);decipher.setAuthTag(tag);
   let bytes=0;const bound=new Transform({transform(chunk,_encoding,done){bytes+=chunk.length;done(bytes>maxBytes?Error('backup_size_limit'):null,chunk);}});
   output=fs.createWriteStream(temp,{flags:'wx',mode:0o600});
   const source=stat.size===overhead?require('node:stream').Readable.from([]):fs.createReadStream(input,{start:header.length,end:stat.size-17});
   await pipeline(source,decipher,bound,output);
  }
  // Only a fully authenticated restore is published; partial plaintext is private.
  const after=fs.statSync(input);if(after.size!==stat.size||after.mtimeMs!==stat.mtimeMs||after.ino!==stat.ino)throw Error('backup_source_changed');
  publish(temp,target);return {success:true,operation,bytes:fs.statSync(target).size,sha256:await digest(target)};
 }catch{output?.destroy();throw Error('backup_authentication_or_io_failed');}
 finally{if(output&&!output.closed)try{await finished(output);}catch{}if(fs.existsSync(temp))fs.unlinkSync(temp);}
}
async function mirror(source,target,expectedSha256) {
 source=checked(source);target=checked(target,{absent:true});if(path.dirname(source)===path.dirname(target)||!/^[a-f0-9]{64}$/.test(expectedSha256))throw Error('separate_mirror_required');
 const temp=temporary(target);try{await pipeline(fs.createReadStream(source),fs.createWriteStream(temp,{flags:'wx',mode:0o600}));if(await digest(temp)!==expectedSha256)throw Error();publish(temp,target);return {success:true,sha256:expectedSha256};}catch{throw Error('backup_mirror_failed');}finally{if(fs.existsSync(temp))fs.unlinkSync(temp);}
}
module.exports={transform,mirror,digest};

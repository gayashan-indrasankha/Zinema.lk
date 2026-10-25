'use strict';
const fs=require('node:fs'),path=require('node:path'),crypto=require('node:crypto');
function acquire(sessionPath) {
 if(!path.isAbsolute(sessionPath))throw Error('worker_lease_review_required');
 fs.mkdirSync(sessionPath,{recursive:true,mode:0o700});
 if(fs.lstatSync(sessionPath).isSymbolicLink())throw Error('worker_lease_review_required');
 const file=path.join(sessionPath,'worker.lock'),nonce=crypto.randomBytes(16).toString('hex');let fd;
 try{fd=fs.openSync(file,'wx',0o600);fs.writeFileSync(fd,JSON.stringify({pid:process.pid,nonce}));fs.fsyncSync(fd);}catch{if(fd!==undefined)fs.closeSync(fd);throw Error('worker_lease_review_required');}
 let released=false,closed=false;
 return {release(){if(released)return;try{const stat=fs.lstatSync(file);if(!stat.isFile()||stat.isSymbolicLink()||JSON.parse(fs.readFileSync(file,'utf8')).nonce!==nonce)throw Error();fs.closeSync(fd);closed=true;fs.unlinkSync(file);released=true;}catch{if(!closed){fs.closeSync(fd);closed=true;}throw Error('worker_lease_review_required');}}};
}
module.exports={acquire};

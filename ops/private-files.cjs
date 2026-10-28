'use strict';
const fs=require('node:fs'),path=require('node:path'),crypto=require('node:crypto');
const ROOT=path.resolve(__dirname,'../.runtime');
const canonical=value=>process.platform==='win32'?value.toLowerCase():value;
function checked(value,{directory=false,absent=false}={}) {
 const file=path.resolve(value);
 if(!canonical(file).startsWith(canonical(ROOT)+path.sep))throw Error('private_path_denied');
 let cursor=file;
 while(!fs.existsSync(cursor)){const parent=path.dirname(cursor);if(parent===cursor)throw Error('private_path_denied');cursor=parent;}
 if(canonical(fs.realpathSync(cursor))!==canonical(cursor))throw Error('private_path_denied');
 if(fs.existsSync(file)) {
  const stat=fs.lstatSync(file);
  if(absent||stat.isSymbolicLink()||(directory?!stat.isDirectory():!stat.isFile()||stat.nlink!==1))throw Error('private_path_denied');
 } else if(!absent)throw Error('private_path_denied');
 return file;
}
function read(file,limit=1048576){file=checked(file);if(fs.statSync(file).size>limit)throw Error('private_state_limit');return fs.readFileSync(file);}
function hash(raw){return crypto.createHash('sha256').update(raw).digest('hex');}
function atomic(file,value,{create=false}={}) {
 file=checked(file,{absent:create});const temp=file+'.'+crypto.randomBytes(12).toString('hex')+'.tmp';let fd;
 try {
  fd=fs.openSync(temp,'wx',0o600);fs.writeFileSync(fd,typeof value==='string'||Buffer.isBuffer(value)?value:JSON.stringify(value));fs.fsyncSync(fd);fs.closeSync(fd);fd=undefined;
  if(create){fs.linkSync(temp,file);fs.unlinkSync(temp);}else require('../whatsapp-bot-deploy/config/atomic-rename').replace(temp,file);
 }finally{if(fd!==undefined)fs.closeSync(fd);if(fs.existsSync(temp))fs.unlinkSync(temp);}
}
function json(file){try{return JSON.parse(read(file));}catch{throw Error('private_state_unavailable');}}
module.exports={ROOT,checked,read,hash,atomic,json};

'use strict';
// Local fixture encryption. No network, credential generation, escrow or publication.
const fs=require('node:fs'),path=require('node:path'),crypto=require('node:crypto');
const boundary=path.resolve(__dirname,'../.runtime'),magic=Buffer.from('ZINEMA-LOCAL-BACKUP/1\n');
function checked(file,existing=true,limit=67108864){const resolved=path.resolve(file),parent=fs.realpathSync(path.dirname(resolved));const canon=value=>process.platform==='win32'?value.toLowerCase():value;if(!canon(resolved).startsWith(canon(boundary)+path.sep)||canon(parent)!==canon(path.dirname(resolved)))throw Error();if(existing&&(!fs.lstatSync(resolved).isFile()||fs.lstatSync(resolved).isSymbolicLink()||fs.statSync(resolved).size>limit))throw Error();return resolved;}
function transform(operation,input,output,keyfile){if(!['encrypt','decrypt'].includes(operation))throw Error();input=checked(input,true,67108864+(operation==='decrypt'?magic.length+28:0));output=checked(output,false);keyfile=checked(keyfile,true,128);if(fs.existsSync(output))throw Error();const text=fs.readFileSync(keyfile,'utf8').trim();if(!/^[a-f0-9]{64}$/.test(text))throw Error();const key=Buffer.from(text,'hex'),raw=fs.readFileSync(input);let result;
 if(operation==='encrypt'){const nonce=crypto.randomBytes(12),cipher=crypto.createCipheriv('aes-256-gcm',key,nonce);cipher.setAAD(magic);const data=Buffer.concat([cipher.update(raw),cipher.final()]);result=Buffer.concat([magic,nonce,cipher.getAuthTag(),data]);}
 else{if(raw.length<magic.length+28||!raw.subarray(0,magic.length).equals(magic))throw Error();const offset=magic.length,decipher=crypto.createDecipheriv('aes-256-gcm',key,raw.subarray(offset,offset+12));decipher.setAAD(magic);decipher.setAuthTag(raw.subarray(offset+12,offset+28));result=Buffer.concat([decipher.update(raw.subarray(offset+28)),decipher.final()]);}
 fs.writeFileSync(output,result,{flag:'wx',mode:0o600});return {success:true,operation};
}
module.exports={transform};
if(require.main===module){try{process.stdout.write(JSON.stringify(transform(...process.argv.slice(2)))+'\n');}catch{process.stdout.write('{"success":false,"error":"local_backup_crypto_failed"}\n');process.exitCode=1;}}

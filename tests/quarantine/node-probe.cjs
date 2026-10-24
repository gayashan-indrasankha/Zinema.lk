'use strict';
// Runs reviewed source only, with transport/dependency/file-side-effect tripwires.
const fs=require('node:fs'),Module=require('node:module'),path=require('node:path');
const stats={network:0,privateReads:0,fileWrites:0,timers:0,providerImports:0};
const trip=key=>()=>{stats[key]++;throw Object.assign(new Error('OFFLINE_TRIPWIRE'),{code:'OFFLINE_TRIPWIRE'});};
for(const name of ['node:http','node:https']){const m=require(name);m.request=trip('network');m.get=trip('network');}
require('node:net').connect=trip('network');require('node:net').createConnection=trip('network');require('node:dns').lookup=trip('network');globalThis.fetch=trip('network');globalThis.setTimeout=trip('timers');globalThis.setInterval=trip('timers');
for(const name of ['writeFileSync','appendFileSync','mkdirSync','unlinkSync','createWriteStream','createReadStream'])fs[name]=trip('fileWrites');
const read=fs.readFileSync;fs.readFileSync=function(file,...args){if(typeof file==='string'&&/(?:^|[\\/])(?:\.env(?:$|\.)|whatsapp-session|\.wwebjs_auth|pending-requests\.json|message-cache\.json|warming-state\.json)/i.test(file)){stats.privateReads++;throw Error('PRIVATE_STORE_REFUSED');}return Reflect.apply(read,this,[file,...args]);};
const original=Module._load;Module._load=function(request,...args){if(/^(?:@whiskeysockets\/|whatsapp-web|puppeteer|express$|cors$|got$|mysql2|node-schedule$)/.test(request)){stats.providerImports++;throw Error('PROVIDER_IMPORT_REFUSED');}return Reflect.apply(original,this,[request,...args]);};
let code='NO_GUARD';try{require(path.resolve(process.argv[2]));}catch(e){code=e.code||'UNCLASSIFIED';}
console.log(JSON.stringify({code,...stats}));if(code!=='INTEGRATION_DISABLED'||Object.values(stats).some(n=>n!==0))process.exitCode=1;

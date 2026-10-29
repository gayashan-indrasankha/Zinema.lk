'use strict';
const fs=require('node:fs'),path=require('node:path'),crypto=require('node:crypto');
function privateJson(file,limit=16384){const s=fs.lstatSync(file);if(!s.isFile()||s.isSymbolicLink()||s.size>limit)throw Error('live_configuration_denied');return JSON.parse(fs.readFileSync(file,'utf8'));}
function approval(home,owner,readManifest=fs.readFileSync){
 const a=privateJson(path.join(home,'native/whatsapp-live.json'));
 if(a.schema!==1||a.owner!==owner||a.mode!=='owner-local-whatsapp'||a.liveWhatsApp!==true||a.production!==false||a.publishing!==false||a.automaticJobs!==false||a.sourceRoot!=='D:\\Zinema.lk\\whatsapp-bot-deploy'||typeof a.accountPhone!=='string'||!/^\d{8,15}$/.test(a.accountPhone)||!Array.isArray(a.recipients)||a.recipients.length!==1||a.recipients[0]!==a.accountPhone||a.maxAttachmentBytes!==20971520)throw Error('live_configuration_denied');
 if(!Array.isArray(a.webVersion)||a.webVersion.length!==3||a.webVersion[0]!==2||a.webVersion[1]!==3000||!Number.isSafeInteger(a.webVersion[2])||a.webVersion[2]<1||a.webVersion[2]>1e12)throw Error('live_configuration_denied');
 const manifest=path.join(a.sourceRoot,'node_modules/@whiskeysockets/baileys/package.json');
 if(crypto.createHash('sha256').update(readManifest(manifest)).digest('hex')!==a.sdkManifestSha256)throw Error('live_sdk_changed');
 return Object.freeze(a);
}
function phone(jid){return typeof jid==='string'&&/^\d+(?::\d+)?@s\.whatsapp\.net$/.test(jid)?jid.split('@')[0].split(':')[0]:null;}
function recipientAllowed(jid,a){return phone(jid)!==null&&a.recipients.includes(phone(jid));}
function command(message,a){
 const jid=message?.key?.remoteJid;
 if(!recipientAllowed(jid,a))return null;
 let body=message.message;if(body?.ephemeralMessage)body=body.ephemeralMessage.message;
 const text=body?.conversation||body?.extendedTextMessage?.text;
 const match=typeof text==='string'?text.trim().match(/^!get ([A-Z0-9]{12})$/):null;
 return match?{token:match[1],recipient:jid}:null;
}
module.exports={approval,privateJson,phone,recipientAllowed,command};

'use strict';
const {test}=require('node:test'),assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path'),crypto=require('node:crypto');
const policy=require('../../../dev/native/whatsapp-policy.cjs');
const accountPhone='99000000001',a={accountPhone,recipients:[accountPhone]};
const sampleToken='A'.repeat(12),commandText='!get '+sampleToken;
test('Only approved direct owner JIDs are accepted, including device suffixes',()=>{assert.equal(policy.recipientAllowed(accountPhone+'@s.whatsapp.net',a),true);assert.equal(policy.recipientAllowed(accountPhone+':2@s.whatsapp.net',a),true);for(const jid of ['99000000002@s.whatsapp.net',accountPhone+'@g.us',accountPhone+'@lid','status@broadcast',null,''])assert.equal(policy.recipientAllowed(jid,a),false);});
test('Owner token commands work for incoming and self-chat without accepting arbitrary text',()=>{for(const fromMe of [true,false])assert.deepEqual(policy.command({key:{remoteJid:accountPhone+'@s.whatsapp.net',fromMe},message:{conversation:commandText}},a),{token:sampleToken,recipient:accountPhone+'@s.whatsapp.net'});for(const text of ['!help','!get short',commandText+' extra',commandText.toLowerCase()])assert.equal(policy.command({key:{remoteJid:accountPhone+'@s.whatsapp.net'},message:{conversation:text}},a),null);});
test('Group messages and non-owner commands cannot reach delivery',()=>{for(const remoteJid of ['99000000002@s.whatsapp.net','99000000001@g.us'])assert.equal(policy.command({key:{remoteJid},message:{conversation:commandText}},a),null);});
test('Ephemeral and extended owner messages parse without using captions or history',()=>{assert.ok(policy.command({key:{remoteJid:accountPhone+'@s.whatsapp.net'},message:{ephemeralMessage:{message:{extendedTextMessage:{text:commandText}}}}},a));assert.equal(policy.command({key:{remoteJid:accountPhone+'@s.whatsapp.net'},message:{documentMessage:{caption:commandText}}},a),null);});
test('Private owner approval rejects widened recipients, jobs, production and SDK changes',()=>{
 const home=path.resolve(__dirname,'../../../.runtime/live-policy-'+crypto.randomBytes(6).toString('hex'));fs.mkdirSync(path.join(home,'native'),{recursive:true});
 const sourceRoot='D:\\Zinema.lk\\whatsapp-bot-deploy',manifestBytes=Buffer.from(JSON.stringify({name:'fictional-sdk',version:'0.0.0'}));
 const expected={schema:1,owner:'fictional-owner',mode:'owner-local-whatsapp',liveWhatsApp:true,production:false,publishing:false,automaticJobs:false,sourceRoot,accountPhone,recipients:[accountPhone],maxAttachmentBytes:20971520,webVersion:[2,3000,123],sdkManifestSha256:crypto.createHash('sha256').update(manifestBytes).digest('hex')};
 const file=path.join(home,'native/whatsapp-live.json');
 const verify=()=>policy.approval(home,'fictional-owner',()=>manifestBytes);
 try{fs.writeFileSync(file,JSON.stringify(expected));assert.equal(verify().mode,'owner-local-whatsapp');for(const patch of [{owner:'other'},{production:true},{publishing:true},{automaticJobs:true},{liveWhatsApp:false},{recipients:[accountPhone,'99000000002']},{sourceRoot:'C:\\outside'},{maxAttachmentBytes:2147483648},{accountPhone:99000000001},{webVersion:[]},{sdkManifestSha256:'0'.repeat(64)}]){fs.writeFileSync(file,JSON.stringify({...expected,...patch}));assert.throws(verify);}}
 finally{if(path.dirname(home)!==path.resolve(__dirname,'../../../.runtime')||!path.basename(home).startsWith('live-policy-'))throw Error('cleanup_boundary');fs.rmSync(home,{recursive:true});}
});

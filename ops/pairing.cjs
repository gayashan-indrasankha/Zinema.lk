'use strict';
// Only fictional local challenges. No SDK, socket, QR renderer, browser or network.
const fs=require('node:fs'),path=require('node:path'),crypto=require('node:crypto');
const {checked,read,json,atomic,hash}=require('./private-files.cjs'),Lease=require('../whatsapp-bot-deploy/config/worker-lease');
function context(session,keyFile,callback) {
 session=checked(session,{directory:true});keyFile=checked(keyFile);
 if(keyFile.startsWith(session+path.sep))throw Error('pairing_key_must_be_separate');
 const key=read(keyFile,128).toString().trim();if(!/^[a-f0-9]{64}$/.test(key))throw Error('pairing_review_key_required');
 const lease=Lease.acquire(session);
 try{return callback({session,key:Buffer.from(key,'hex'),intent:path.join(session,'pairing-intent.json'),receipt:path.join(session,'pairing-receipt.json'),credentials:path.join(session,'creds.json'),challenge:path.join(session,'pairing-challenge.json')});}finally{lease.release();}
}
function mac(body,key){return crypto.createHmac('sha256',key).update(JSON.stringify(body)).digest('hex');}
function receiptValid(value,key){return value?.body?.adapter==='local-fake'&&/^[a-f0-9]{64}$/.test(value.mac||'')&&crypto.timingSafeEqual(Buffer.from(value.mac,'hex'),Buffer.from(mac(value.body,key),'hex'));}
function prepare(session,keyFile,{identity,accountRef,dedicatedAccount,ttlSeconds=120}={},now=Date.now()) {
 return context(session,keyFile,f=>{
  if(!/^fictional_account_[0-9]{1,5}$/.test(identity||'')||!/^demo_[a-z0-9_]{3,40}$/.test(accountRef||'')||dedicatedAccount!==true||!Number.isInteger(ttlSeconds)||ttlSeconds<30||ttlSeconds>300)throw Error('dedicated_fake_account_required');
  if(fs.readdirSync(f.session).some(name=>name!=='worker.lock'))throw Error('fresh_pairing_session_required');
  const challenge='FICTIONAL-PAIRING-'+crypto.randomBytes(24).toString('hex');
  const intent={schema:1,adapter:'local-fake',state:'awaiting_owner_review',identityHash:hash(identity),accountRef,challengeHash:hash(challenge),createdAt:now,expiresAt:now+ttlSeconds*1000};
  atomic(f.intent,intent,{create:true});atomic(f.challenge,{adapter:'local-fake',challenge,expiresAt:intent.expiresAt},{create:true});
  return {success:true,state:intent.state,expiresAt:intent.expiresAt};
 });
}
function review(session,keyFile,now=Date.now()){return context(session,keyFile,f=>{const intent=json(f.intent);if(intent.adapter!=='local-fake'||intent.state!=='awaiting_owner_review'||now>=intent.expiresAt)throw Error('pairing_review_expired');return {success:true,revision:hash(read(f.intent)),identityRef:intent.identityHash,challengeRef:intent.challengeHash,expiresAt:intent.expiresAt};});}
function approve(session,keyFile,input,now=Date.now(),fault=()=>{}) {
 return context(session,keyFile,f=>{
  const intent=json(f.intent);
  if(input?.reviewed!==true||input.dedicatedAccount!==true||!/^demo_[a-z0-9_]{3,40}$/.test(input.actor||'')||!/^fixture:[a-z0-9-]{3,64}$/.test(input.evidenceRef||'')||hash(input.identity||'')!==intent.identityHash||input.challengeRef!==intent.challengeHash)throw Error('pairing_owner_review_required');
  if(intent.state==='sealed') {
   const receipt=json(f.receipt),credentials=json(f.credentials);
   if(!receiptValid(receipt,f.key)||receipt.body.actor!==input.actor||receipt.body.evidenceRef!==input.evidenceRef||receipt.body.reviewRevision!==input.revision||receipt.body.identityHash!==intent.identityHash||credentials.registered!==true||credentials.zinemaLocalFake!==true||hash(credentials.me?.id||'')!==intent.identityHash)throw Error('pairing_receipt_unavailable');
   return {success:true,state:'sealed',replayed:true};
  }
  if(intent.state!=='awaiting_owner_review'||now>=intent.expiresAt)throw Error('pairing_review_expired');
  if(input.revision!==hash(read(f.intent))||hash(json(f.challenge).challenge||'')!==intent.challengeHash)throw Error('pairing_review_conflict');
  const body={adapter:'local-fake',identityHash:intent.identityHash,accountRef:intent.accountRef,challengeHash:intent.challengeHash,reviewRevision:input.revision,actor:input.actor,evidenceRef:input.evidenceRef,reviewedAt:now};
  if(fs.existsSync(f.receipt)) {
   const saved=json(f.receipt);if(!receiptValid(saved,f.key)||saved.body.reviewRevision!==input.revision||saved.body.actor!==input.actor||saved.body.evidenceRef!==input.evidenceRef)throw Error('pairing_receipt_conflict');
  }else atomic(f.receipt,{body,mac:mac(body,f.key)},{create:true});
  fault('after_receipt');
  if(fs.existsSync(f.credentials)){const saved=json(f.credentials);if(saved.zinemaLocalFake!==true||hash(saved.me?.id||'')!==intent.identityHash)throw Error('pairing_session_conflict');}
  else atomic(f.credentials,{registered:true,zinemaLocalFake:true,me:{id:input.identity}},{create:true});
  fault('after_credentials');atomic(f.intent,{...intent,state:'sealed'});
  return {success:true,state:'sealed'};
 });
}
module.exports={prepare,review,approve,receiptValid};

'use strict';
// No SDK/network. A receipt is owner evidence, not authorization to bypass live guards.
const fs=require('node:fs'),path=require('node:path'),crypto=require('node:crypto');
function identityHash(value) {
 if(typeof value!=='string'||!/^\d{5,20}(?::\d{1,5})?@s\.whatsapp\.net$/.test(value))throw Error('pairing_review_required');
 return crypto.createHash('sha256').update(value.replace(/:\d+@/,'@')).digest('hex');
}
function verify(state,receipt,key,{accountRef,now=Date.now()}={}) {
 const fail=()=>{throw Error('pairing_review_required');};
 if(state?.creds?.registered!==true||state.creds.zinemaLocalFake===true||!Buffer.isBuffer(key)||key.length!==32||!/^test_[a-z0-9_-]{3,40}$/.test(accountRef||''))fail();
 const body=receipt?.body;
 if(!body||Object.keys(body).sort().join(',')!=='accountRef,adapter,approvedAt,identityHash,scope,validUntil'||body.adapter!=='baileys-reviewed'||body.scope!=='dedicated-test-account'||body.accountRef!==accountRef||body.identityHash!==identityHash(state.creds.me?.id)||!Number.isSafeInteger(body.approvedAt)||!Number.isSafeInteger(body.validUntil)||body.approvedAt>now||body.validUntil<=now||body.validUntil-body.approvedAt>3600000||body.validUntil<=body.approvedAt||!Number.isSafeInteger(now))fail();
 const expected=crypto.createHmac('sha256',key).update(JSON.stringify(body)).digest();
 if(!/^[a-f0-9]{64}$/.test(receipt.mac||'')||!crypto.timingSafeEqual(expected,Buffer.from(receipt.mac,'hex')))fail();
 return true;
}
function privateRead(file,limit) {
 const stat=fs.lstatSync(file);if(!stat.isFile()||stat.isSymbolicLink()||stat.nlink!==1||stat.size>limit||fs.realpathSync(file)!==path.resolve(file))throw Error('pairing_review_required');
 return fs.readFileSync(file,'utf8');
}
function assertReviewedPairing(state,session,env=process.env) {
 try {
  const keyFile=env.PAIRING_REVIEW_KEY_FILE;
  if(typeof keyFile!=='string'||!path.isAbsolute(keyFile)||path.resolve(keyFile).startsWith(path.resolve(session)+path.sep))throw Error();
  const key=privateRead(keyFile,128).trim();if(!/^[a-f0-9]{64}$/.test(key))throw Error();
  const receipt=JSON.parse(privateRead(path.join(session,'pairing-receipt.json'),4096));
  verify(state,receipt,Buffer.from(key,'hex'),{accountRef:env.BOT_PAIRING_ACCOUNT_REF});
  return Object.freeze({validUntil:receipt.body.validUntil});
 }catch{throw Error('pairing_review_required');}
}
module.exports={identityHash,verify,assertReviewedPairing};

'use strict';
const fs=require('node:fs'),path=require('node:path');
const privateFiles=require('./private-files.cjs'),Lease=require('../whatsapp-bot-deploy/config/worker-lease'),ledger=require('../whatsapp-bot-deploy/config/maintenance-ledger');
const {checked,read,hash,json,atomic}=privateFiles;
function files(session){session=checked(session,{directory:true});return {session,checkpoint:path.join(session,'warming-state.json'),excluded:path.join(session,'maintenance-exclusions.json'),pending:path.join(session,'maintenance-review.pending.json')};}
function locked(session,callback){const f=files(session),lease=Lease.acquire(f.session);try{return callback(f);}finally{lease.release();}}
function held(f) {
 const raw=read(f.checkpoint),state=JSON.parse(raw);
 if(state?.held!==true||!Array.isArray(state.remainingFiles)||state.remainingFiles.length>10000)throw Error('held_review_required');
 const item=state.reason==='inflight_send'?state.remainingFiles[0]:state.reason==='inflight_group_refresh'?state.heldWork:null;
 const itemKey=ledger.key(item);return {raw,state,item,itemKey,revision:hash(raw)};
}
function review(session){return locked(session,f=>{
 ledger.assertNoPending(f.session);const current=held(f);
 return {success:true,revision:current.revision,itemRef:current.itemKey,remaining:current.state.remainingFiles.length,outcomes:['verified_completed','withdraw']};
});}
function finish(f,intent,fault=()=>{}) {
 if(intent?.schema!==1||!/^([a-f0-9]{32})$/.test(intent.operationId))throw Error('review_journal_unavailable');
 if(typeof intent.before!=='string'||typeof intent.after!=='string'||typeof intent.afterExclusions!=='string'||hash(intent.before)!==intent.beforeHash||hash(intent.after)!==intent.afterHash||hash(intent.afterExclusions)!==intent.afterExclusionHash)throw Error('review_journal_unavailable');
 if(JSON.parse(intent.after).held!==false||JSON.parse(intent.afterExclusions).schema!==1)throw Error('review_journal_unavailable');
 const cpHash=hash(read(f.checkpoint));const exclusionRaw=fs.existsSync(f.excluded)?read(f.excluded):null;
 const exclusionHash=exclusionRaw?hash(exclusionRaw):null;
 if(![intent.beforeHash,intent.afterHash].includes(cpHash)||![intent.beforeExclusionHash,intent.afterExclusionHash].includes(exclusionHash))throw Error('review_state_conflict');
 if(exclusionHash!==intent.afterExclusionHash)atomic(f.excluded,intent.afterExclusions,{create:!exclusionRaw});
 fault('after_exclusions');
 if(cpHash!==intent.afterHash)atomic(f.checkpoint,intent.after);
 fault('after_checkpoint');
 const destination=path.join(f.session,'maintenance-review-'+intent.operationId+'.json');
 if(fs.existsSync(destination)){if(hash(read(destination))!==hash(JSON.stringify(intent)))throw Error('review_audit_conflict');}
 else atomic(destination,intent,{create:true});
 fault('after_audit');fs.unlinkSync(f.pending);
 return {success:true,operationId:intent.operationId,outcome:intent.outcome};
}
function resolve(session,input,verifyMetadata=()=>({verified:false}),fault=()=>{}) {
 return locked(session,f=>{
  ledger.assertNoPending(f.session);
  if(input?.reviewed!==true||input.workersStopped!==true||!/^demo_[a-z0-9_]{3,40}$/.test(input.actor||'')||!/^fixture:[a-z0-9-]{3,64}$/.test(input.evidenceRef||'')||!(/^[a-f0-9]{32}$/.test(input.operationId||''))||!['verified_completed','withdraw'].includes(input.outcome))throw Error('review_acknowledgement_required');
  const current=held(f);if(current.revision!==input.revision)throw Error('stale_review');
  const destination=path.join(f.session,'maintenance-review-'+input.operationId+'.json');if(fs.existsSync(destination))throw Error('review_operation_exists');
  if(input.outcome==='verified_completed') {
   const proof=verifyMetadata({item:current.item,itemRef:current.itemKey,revision:current.revision});
   if(proof?.verified!==true||proof.itemRef!==current.itemKey||proof.revision!==current.revision)throw Error('metadata_evidence_required');
  }
  const excluded=ledger.exclusions(f.session);if(input.outcome==='withdraw')excluded.items[current.itemKey]=true;
  const after={...current.state,held:false,remainingFiles:current.state.reason==='inflight_send'?current.state.remainingFiles.slice(1):current.state.remainingFiles,reason:'operator_reviewed',reviewOperation:input.operationId};delete after.heldWork;
  const afterRaw=JSON.stringify(after),exclusionRaw=JSON.stringify(excluded);
  if(Object.keys(excluded.items).length>10000)throw Error('review_ledger_limit');
  const intent={schema:1,operationId:input.operationId,reviewedAt:Date.now(),actor:input.actor,evidenceRef:input.evidenceRef,outcome:input.outcome,itemRef:current.itemKey,beforeHash:current.revision,afterHash:hash(afterRaw),before:current.raw.toString(),after:afterRaw,beforeExclusionHash:fs.existsSync(f.excluded)?hash(read(f.excluded)):null,afterExclusionHash:hash(exclusionRaw),afterExclusions:exclusionRaw};
  if(Buffer.byteLength(JSON.stringify(intent))>1048576)throw Error('review_journal_limit');
  atomic(f.pending,intent,{create:true});fault('after_intent');return finish(f,intent,fault);
 });
}
function recover(session,{operationId,reviewed}={}){return locked(session,f=>{const intent=json(f.pending);if(reviewed!==true||intent.operationId!==operationId)throw Error('review_acknowledgement_required');return finish(f,intent);});}
module.exports={review,resolve,recover};

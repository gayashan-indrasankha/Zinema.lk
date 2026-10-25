'use strict';
// Fake recipients, fake API and fake WhatsApp client only. No Baileys is imported.
const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname,'../../..');
const temporary = path.join(root,'.runtime',`audit-bot-unit-${process.pid}`);
Object.assign(process.env,{APP_ENV:'test',API_URL:'http://127.0.0.1:8088/api/whatsapp',API_KEY:'fictional-fake-key',SESSION_PATH:path.join(temporary,'sessions'),LOCAL_MEDIA_PATH:path.join(temporary,'media'),BOT_INSTANCE_ID:'1',ALLOW_LIVE_WHATSAPP:'false'});
const Forwarder = require(path.join(root,'whatsapp-bot-deploy/config/multi-tier-forwarder'));
const FileManager = require(path.join(root,'whatsapp-bot-deploy/config/file-manager'));
const Queue = require(path.join(root,'whatsapp-bot-deploy/config/upload-queue'));
let instance=0;
function fixture(options={}) {
  process.env.SESSION_PATH=path.join(temporary,'sessions',String(++instance));
  const sends=[];
  const client={sendMessage:async(...args)=>{sends.push(args);return {key:{id:'fictional-sent-id'}};},fetchMessagesFromChat:async()=>[],...options.client};
  const manager={hasLocalFile:()=>false,...options.manager};
  const api={addMessageId:async()=>{}};
  return {forwarder:new Forwarder(client,manager,api,options.group||null),sends};
}
const token={content_type:'movie',content_id:1,message_id:'fictional-cache-id'};
test('cached-message forwarding reaches only the fake client',async()=>{
  const {forwarder,sends}=fixture();
  forwarder.messageCache.set(token.message_id,{key:{id:token.message_id}});
  const result=await forwarder.forwardVideo('fictional-recipient',token);
  assert.equal(result.success,true);assert.equal(result.tier,1);assert.equal(sends.length,1);
});
test('cache miss and missing local file return a failure',async()=>{
  const {forwarder,sends}=fixture();
  const result=await forwarder.forwardVideo('fictional-recipient',token);
  assert.equal(result.success,false);assert.equal(sends.length,0);
});
test('storage-group lookup forwards using the fake client',async()=>{
  const {forwarder,sends}=fixture({group:'fictional-group',client:{fetchMessagesFromChat:async()=>[{key:{id:token.message_id}}]}});
  const result=await forwarder.forwardVideo('fictional-recipient',token);
  assert.equal(result.success,true);assert.equal(result.tier,2);assert.equal(sends.length,1);
});
test('coalesced requests deliver and resolve waiting recipients',async()=>{
  const {forwarder,sends}=fixture();let resolved=false;
  forwarder.activeOperations.set('movie_1_0',{waiters:[{chatId:'fictional-waiter',resolve:()=>{resolved=true;}}]});
  await forwarder.broadcastToWaiters('movie_1_0',{key:{id:'fictional-message'}});
  assert.equal(resolved,true);assert.equal(sends.length,1);
});
test('local upload must reject a missing delivery acknowledgement',async(t)=>{
  // Avoid retaining the legacy five-minute Promise.race timer in this unit test.
  t.mock.timers.enable({apis:['setTimeout']});
  const media=path.join(temporary,'sample.mp4');fs.mkdirSync(temporary,{recursive:true});fs.writeFileSync(media,'fictional-file');
  const {forwarder}=fixture({client:{sendMessage:async()=>undefined},manager:{getFilePath:()=>media}});
  const result=await forwarder.performTier3Upload('fictional-recipient','movie',1,null);
  assert.equal(result.success,false);
});
test('file manager resolves fixture media and absent files',()=>{
  const base=path.join(temporary,'media');const manager=new FileManager(base);
  fs.mkdirSync(path.join(base,'movies','1'),{recursive:true});fs.writeFileSync(path.join(base,'movies','1','full.mp4'),'fictional');
  assert.equal(manager.getFilePath('movie',1),path.join(base,'movies','1','full.mp4'));
  assert.equal(manager.getFilePath('movie',2),null);
});
test('upload queue respects configured concurrency with fake uploads',async()=>{
  const queue=new Queue({maxConcurrent:1,delayBetweenUploads:1});let active=0,max=0;
  queue.setUploadHandler(async task=>{active++;max=Math.max(max,active);await new Promise(resolve=>setTimeout(resolve,2));active--;return {success:true,id:task.contentId};});
  const results=await Promise.all([1,2,3].map(contentId=>queue.enqueue({contentType:'movie',contentId,chatId:'fictional-recipient'})));
  assert.equal(max,1);assert.equal(results.length,3);assert.equal(queue.stats.processed,3);
});

test('concurrent recipients and duplicate requests await acknowledgement',async()=>{
  let release; const gate=new Promise(resolve=>{release=resolve;});
  const {forwarder,sends}=fixture({client:{sendMessage:async(chat,payload)=>{
    sends.push([chat,payload]);
    if(chat==='owner') await gate;
    return {key:{id:'ack-'+chat}};
  }}});
  forwarder.messageCache.set(token.message_id,{key:{id:token.message_id}});
  const first=forwarder.forwardVideo('owner',token);
  const duplicateOwner=forwarder.forwardVideo('owner',token);
  const second=forwarder.forwardVideo('waiter',token);
  const duplicateWaiter=forwarder.forwardVideo('waiter',token);
  let prematurelyFinished=false; second.then(()=>{prematurelyFinished=true;});
  await Promise.resolve(); assert.equal(prematurelyFinished,false);
  release();
  const results=await Promise.all([first,duplicateOwner,second,duplicateWaiter]);
  assert.ok(results.every(result=>result.success));
  assert.deepEqual(sends.map(([chat])=>chat),['owner','waiter']);
  assert.equal(forwarder.activeOperations.size,0);
});

test('failed primary delivery settles all coalesced recipients as failures',async()=>{
  let release;const gate=new Promise(resolve=>{release=resolve;});
  const {forwarder}=fixture({client:{sendMessage:async()=>{await gate;throw new Error('Rejected');}}});
  forwarder.messageCache.set(token.message_id,{key:{id:token.message_id}});
  const first=forwarder.forwardVideo('owner',token);
  const second=forwarder.forwardVideo('waiter',token);
  release();
  const results=await Promise.all([first,second]);
  assert.ok(results.every(result=>result.success===false));
  assert.equal(forwarder.activeOperations.size,0);
});

test('one recipient rejection is not reported as a successful delivery',async()=>{
  let release;const gate=new Promise(resolve=>{release=resolve;});
  const {forwarder}=fixture({client:{sendMessage:async(chat)=>{
    if(chat==='owner') {await gate;return {key:{id:'owner-ack'}};}
    throw new Error('Rejected');
  }}});
  forwarder.messageCache.set(token.message_id,{key:{id:token.message_id}});
  const first=forwarder.forwardVideo('owner',token);
  const second=forwarder.forwardVideo('waiter',token);
  release();
  assert.equal((await first).success,true);assert.equal((await second).success,false);
});

test('cached forwarding also rejects an absent acknowledgement',async()=>{
  const {forwarder}=fixture({client:{sendMessage:async()=>undefined}});
  forwarder.messageCache.set(token.message_id,{key:{id:token.message_id}});
  assert.equal((await forwarder.forwardVideo('owner',token)).success,false);
});

test('successful disk upload releases its timeout and retains acknowledgement',async()=>{
  const media=path.join(temporary,'acknowledged.mp4');fs.mkdirSync(temporary,{recursive:true});fs.writeFileSync(media,'fictional-file');
  const {forwarder,sends}=fixture({manager:{getFilePath:()=>media}});
  const result=await forwarder.performTier3Upload('fictional-recipient','movie',1,null);
  assert.equal(result.success,true);assert.equal(result.messageId,'fictional-sent-id');assert.equal(sends.length,1);
});

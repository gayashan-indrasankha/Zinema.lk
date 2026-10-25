'use strict';
const {test}=require('node:test');const assert=require('node:assert/strict');const path=require('node:path');
const root=path.resolve(__dirname,'../../..');
const Health=require(path.join(root,'whatsapp-bot-deploy/config/health-monitor'));
const Reconnect=require(path.join(root,'whatsapp-bot-deploy/config/reconnect-controller'));
const {isAdminIdentity}=require(path.join(root,'whatsapp-bot-deploy/config/admin-identity'));
const MessageQueue=require(path.join(root,'whatsapp-bot-deploy/config/message-queue'));
test('Incoming command queue rejects overflow and runs accepted tasks sequentially',async()=>{
 let release;const gate=new Promise(resolve=>{release=resolve;});const order=[];
 const queue=new MessageQueue({maxQueueSize:2,sleep:async()=>{}});
 assert.equal(queue.add(async()=>{await gate;order.push(1);}),true);
 assert.equal(queue.add(async()=>order.push(2)),true);assert.equal(queue.add(async()=>order.push(3)),true);
 assert.equal(queue.add(async()=>order.push(4)),false);assert.equal(queue.getStatus().size,2);
 release();await new Promise(resolve=>setImmediate(resolve));assert.deepEqual(order,[1,2,3]);assert.equal(queue.getStatus().isProcessing,false);
});
test('Incoming task failure does not prevent the next accepted command',async()=>{
 const queue=new MessageQueue({sleep:async()=>{}});let completed=false;
 queue.add(async()=>{throw Error('fictional');});queue.add(async()=>{completed=true;});
 await new Promise(resolve=>setImmediate(resolve));assert.equal(completed,true);assert.equal(queue.getStatus().size,0);
});
test('Administrator identity comparison rejects substring, malformed and empty identities',()=>{
 assert.equal(isAdminIdentity('0000000000',[' +0000000000 ']),true);
 for(const sender of ['10000000000','00000000001','','abc0000000000']) assert.equal(isAdminIdentity(sender,['0000000000']),false);
 assert.equal(isAdminIdentity('0000000000',['','abc']),false);
});
test('Health accepts a fictional Baileys user identity',async()=>{
 const data=await new Health(1,{}, {user:{id:'0000000000:7@s.whatsapp.net'}}).collectHealthData();
 assert.equal(data.bot_phone,'0000000000');
});
test('Repeated monitor starts retain one timer and stop clears it',async t=>{
 t.mock.timers.enable({apis:['setInterval']});let calls=0;
 const h=new Health(1,{},{});h.sendHeartbeat=()=>{calls++;};h.start(1);h.start(1);assert.equal(calls,2);
 t.mock.timers.tick(1000);assert.equal(calls,3);h.stop();t.mock.timers.tick(1000);assert.equal(calls,3);
});
function fixture(connect=async()=>{}) {
 const tasks=new Map(),delays=[];let id=0,exhausted=0;
 const controller=new Reconnect(connect,{schedule:(fn,delay)=>{tasks.set(++id,fn);delays.push(delay);return id;},cancel:key=>tasks.delete(key),maxAttempts:3,onExhausted:()=>{exhausted++;}});
 return {controller,tasks,delays,exhausted:()=>exhausted,fire:async()=>{const [key,fn]=tasks.entries().next().value;tasks.delete(key);await fn();}};
}
test('Reconnect backoff survives connection attempts and has a hard bound',async()=>{
 let calls=0;const f=fixture(async()=>{calls++;});
 for(let i=0;i<3;i++){assert.equal(f.controller.disconnected(),true);await f.fire();}
 assert.equal(f.controller.disconnected(),false);assert.equal(f.exhausted(),1);assert.deepEqual(f.delays,[5000,10000,20000]);assert.equal(calls,3);
});
test('Duplicate close events schedule one retry; login resets budget',async()=>{
 const f=fixture();f.controller.disconnected();f.controller.disconnected();assert.equal(f.tasks.size,1);
 f.controller.connected();assert.equal(f.tasks.size,0);assert.equal(f.controller.attempts,0);
 f.controller.disconnected();assert.equal(f.delays.at(-1),5000);f.controller.disconnected(false);assert.equal(f.tasks.size,0);
});
test('A rejected reconnect retries within the same budget without unhandled errors',async()=>{
 const f=fixture(async()=>{throw Error('fictional failure');});f.controller.disconnected();await f.fire();await f.fire();await f.fire();
 assert.equal(f.tasks.size,0);assert.equal(f.exhausted(),1);
});
test('A close while connect is unresolved cannot start an overlapping connection',async()=>{
 let release;const gate=new Promise(resolve=>release=resolve);const f=fixture(async()=>gate);
 f.controller.disconnected();const pending=f.fire();await Promise.resolve();f.controller.disconnected();assert.equal(f.tasks.size,0);
 release();await pending;assert.equal(f.tasks.size,1);f.controller.stop();assert.equal(f.tasks.size,0);
});
test('Stopping during reconnect prevents a rejected in-flight attempt from rescheduling',async()=>{
 let reject;const f=fixture(()=>new Promise((_,no)=>reject=no));f.controller.disconnected();const pending=f.fire();await Promise.resolve();f.controller.stop();reject(Error('fixture'));await pending;assert.equal(f.tasks.size,0);
});
test('Stopped health collection cannot publish a late online heartbeat',async()=>{
 let release;const gate=new Promise(resolve=>release=resolve);let sent=0;const monitor=new Health(1,{request:async()=>{sent++;}},{});
 monitor.collectHealthData=async()=>{await gate;return {status:'online'};};monitor.running=true;const pending=monitor.sendHeartbeat();monitor.stop();release();await pending;assert.equal(sent,0);
});
test('Shutdown rejects new commands and drops only work that has not begun',async()=>{
 let release;const gate=new Promise(resolve=>release=resolve);const queue=new MessageQueue({sleep:async()=>{}});let ran=0;
 queue.add(async()=>{await gate;ran++;});queue.add(async()=>{ran++;});assert.equal(queue.stop(),1);assert.equal(queue.add(async()=>{}),false);release();await queue.whenIdle();assert.equal(ran,1);
});
test('Structured worker logger drops raw values and accepts only bounded safe counts',()=>{
 const lines=[];const target={log:(v)=>lines.push(v),info(){},warn(){},error:(v)=>lines.push(v),debug(){}};
 const logger=require(path.join(root,'whatsapp-bot-deploy/config/safe-logger')).install(target);
 const canary='fictional-secret-phone-url-token';target.log(canary,{key:canary});target.error(Error(canary));logger.event('connection_open',{pending:2,token:canary,active:NaN});logger.event(canary,{count:3});logger.restore();
 assert.equal(lines.some(line=>line.includes(canary)),false);assert.equal(JSON.parse(lines[2]).pending,2);assert.equal(JSON.parse(lines[2]).active,undefined);assert.equal(JSON.parse(lines[3]).event,'worker_error');
});

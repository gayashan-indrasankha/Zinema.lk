'use strict';
class MessageQueue {
 constructor({maxQueueSize=200,sleep=ms=>new Promise(resolve=>setTimeout(resolve,ms))}={}) {
  this.queue=[];this.isProcessing=false;this.maxQueueSize=maxQueueSize;this.sleep=sleep;
  this.accepting=true;this.idleWaiters=[];
 }
 add(task,metadata={}) {
  if(!this.accepting||typeof task!=='function'||this.queue.length>=this.maxQueueSize)return false;
  this.queue.push({task,metadata,addedAt:Date.now()});this.process();return true;
 }
 async process() {
  if(this.isProcessing)return;this.isProcessing=true;
  try {
   while(this.queue.length) {
    const {task}=this.queue.shift();
    try {await task();}catch {console.error('Queued task failed; sensitive details suppressed.');}
    if(this.queue.length)await this.sleep(2000+Math.floor(Math.random()*2001));
   }
  }finally{this.isProcessing=false;for(const resolve of this.idleWaiters.splice(0))resolve();}
 }
 stop(){this.accepting=false;const pending=this.queue.length;this.queue.length=0;return pending;}
 whenIdle(){return this.isProcessing ? new Promise(resolve=>this.idleWaiters.push(resolve)) : Promise.resolve();}
 getStatus(){return {size:this.queue.length,isProcessing:this.isProcessing};}
}
module.exports=MessageQueue;

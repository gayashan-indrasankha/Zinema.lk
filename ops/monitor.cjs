'use strict';
const path=require('node:path'),fs=require('node:fs');
const {checked,json,atomic}=require('./private-files.cjs'),Lease=require('../whatsapp-bot-deploy/config/worker-lease');
const booleanFields=['websiteReady','dbReady','workerEnabled','workerRegistered'];
const numbers=['heartbeatAgeSeconds','queuePending','queueOldestAgeSeconds','heldMaintenance','diskUsedPercent','backupAgeSeconds','errorCount'];
const codes=['web_unavailable','database_unavailable','worker_unregistered','heartbeat_stale','queue_stale','maintenance_held','disk_high','backup_stale','error_budget'];
function evaluate(metrics,policy) {
 if(!metrics||Object.keys(metrics).some(key=>![...booleanFields,...numbers].includes(key))||booleanFields.some(key=>typeof metrics[key]!=='boolean')||numbers.some(key=>!Number.isSafeInteger(metrics[key])||metrics[key]<0||metrics[key]>1000000000)||metrics.diskUsedPercent>100)throw Error('monitor_metrics_denied');
 const p=policy.monitoring,active=[];
 if(!metrics.websiteReady)active.push('web_unavailable');if(!metrics.dbReady)active.push('database_unavailable');
 if(metrics.workerEnabled&&!metrics.workerRegistered)active.push('worker_unregistered');
 if(metrics.workerEnabled&&metrics.heartbeatAgeSeconds>p.heartbeatMaxAgeSeconds)active.push('heartbeat_stale');
 if(metrics.queuePending>0&&metrics.queueOldestAgeSeconds>p.queueMaxAgeSeconds)active.push('queue_stale');
 if(metrics.heldMaintenance>0)active.push('maintenance_held');if(metrics.diskUsedPercent>=p.diskUsedPercent)active.push('disk_high');
 if(metrics.backupAgeSeconds>p.backupMaxAgeSeconds)active.push('backup_stale');if(metrics.errorCount>=p.errorCount)active.push('error_budget');return active;
}
function record(directory,metrics,policy,now=Date.now()) {
 policy=require('./policy.cjs').validate(policy);directory=checked(directory,{directory:true});const lease=Lease.acquire(directory);
 try {
  const file=path.join(directory,'monitor-state.json'),exists=fs.existsSync(file),previous=exists?json(file):{schema:1,sequence:0,active:[],outbox:[]};
  if(previous.schema!==1||!Number.isSafeInteger(previous.sequence)||previous.sequence<0||!Array.isArray(previous.active)||previous.active.some(c=>!codes.includes(c))||!Array.isArray(previous.outbox)||previous.outbox.length>policy.monitoring.outboxMaxEvents||previous.outbox.some(e=>!['alert','recovered'].includes(e?.event)||!codes.includes(e?.code)||!Number.isSafeInteger(e?.sequence)||!Number.isSafeInteger(e?.at)))throw Error('monitor_state_unavailable');
  const active=evaluate(metrics,policy),events=[...active.filter(c=>!previous.active.includes(c)).map(code=>({event:'alert',code})),...previous.active.filter(c=>!active.includes(c)).map(code=>({event:'recovered',code}))];
  if(previous.outbox.length+events.length>policy.monitoring.outboxMaxEvents)throw Error('monitor_outbox_full');
  if(!Number.isSafeInteger(now)||now<0)throw Error('monitor_clock_denied');
  const outbox=[...previous.outbox];let sequence=previous.sequence;
  for(const event of events)outbox.push({...event,sequence:++sequence,at:now});
  atomic(file,{schema:1,sequence,active,outbox},{create:!exists});return {success:true,newEvents:events.length,active:active.length,pending:outbox.length};
 }finally{lease.release();}
}
module.exports={evaluate,record};

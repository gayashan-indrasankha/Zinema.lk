'use strict';
// Legacy log arguments can contain credentials, JIDs, URLs, media names or SDK state.
// Do not attempt to guess every secret pattern: emit fixed events and bounded counts.
const events=new Set(['worker_activity','worker_error','worker_warning','worker_started','connection_open','connection_closed','shutdown_started','shutdown_complete','pairing_required','maintenance_held','lease_rejected']);
const fields=new Set(['active','pending','count','duration_ms','attempt']);
function install(target=console) {
 const originals={};for(const name of ['log','info','warn','error','debug'])originals[name]=target[name];
 const write=(level,event,counts={})=>{
  const record={level,event:events.has(event)?event:'worker_error'};
  for(const [key,value] of Object.entries(counts))if(fields.has(key)&&Number.isSafeInteger(value)&&value>=0&&value<=1000000000)record[key]=value;
  const sink=level==='error' ? originals.error : originals.log;
  try{sink?.call(target,JSON.stringify(record));}catch{}
 };
 for(const name of ['log','info','warn','error','debug'])target[name]=()=>write(name==='error'?'error':name==='warn'?'warn':'info',name==='error'?'worker_error':name==='warn'?'worker_warning':'worker_activity');
 return {event:(event,counts)=>write('info',event,counts),restore:()=>{for(const [name,fn] of Object.entries(originals))target[name]=fn;}};
}
module.exports={install};

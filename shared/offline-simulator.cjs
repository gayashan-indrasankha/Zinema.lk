'use strict';
const gate=require('./integration-gate.cjs');
/** Pure in-memory demonstration; no socket, credentials, file store, timer or provider. */
class DeliverySimulator {
 constructor(env){this.env=env;this.queue=[];this.results=new Map();this.tokens=new Map();this.events=[];gate.assertSimulation('whatsapp',env);}
 validate(request){if(!request||Object.keys(request).sort().join(',')!=='content,id,recipient'||!['movie:501','episode:701'].includes(request.content)||!['viewer-a','viewer-b'].includes(request.recipient)||!/^sample-[0-9]{1,3}$/.test(request.id))throw new Error('SYNTHETIC_INPUT_REQUIRED');}
 enqueue(request){gate.assertSimulation('whatsapp',this.env);this.validate(request);if(this.results.has(request.id)||this.queue.some(r=>r.id===request.id))return {state:'duplicate'};if(this.queue.length>=16)throw new Error('SIMULATION_CAPACITY');this.queue.push(Object.freeze({...request}));return {state:'queued'};}
 issue(request){gate.assertSimulation('whatsapp',this.env);this.validate(request);if(this.tokens.size>=16)throw new Error('SIMULATION_CAPACITY');const token='SIMULATION_ONLY_'+request.id;this.tokens.set(token,Object.freeze({...request}));return {token,usableForLiveDelivery:false};}
 redeem(token,recipient){gate.assertSimulation('whatsapp',this.env);const r=this.tokens.get(token);if(!r||r.recipient!==recipient)throw new Error('SIMULATION_TOKEN_UNAVAILABLE');this.tokens.delete(token);return this.enqueue(r);}
 tick(){gate.assertSimulation('whatsapp',this.env);const r=this.queue.shift();if(!r)return {state:'empty'};const result=Object.freeze({state:'simulated',method:r.content==='movie:501'?'cached-forward':'synthetic-fallback',externalOperations:0});this.results.set(r.id,result);if(this.results.size>16)this.results.delete(this.results.keys().next().value);this.events.push({event:'simulated-delivery'});if(this.events.length>16)this.events.shift();return result;}
}
function extractionFixture(id,env){gate.assertSimulation('extraction',env);if(id!=='original-pattern')throw new Error('SYNTHETIC_INPUT_REQUIRED');return Object.freeze({state:'simulated',fixture:'original-pattern',externalOperations:0});}
module.exports={DeliverySimulator,extractionFixture};

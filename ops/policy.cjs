'use strict';
const path=require('node:path');
function validate(value) {
 const fail=()=>{throw Error('promotion_policy_denied');};
 if(!value||value.version!==1||value.localOnly!==true||value.externalConnections!==false||value.productionAccess!==false||value.publishing!==false)fail();
 const {pairing:p,extractor:e,storage:s,backup:b,monitoring:m}=value;
 if(p?.adapter!=='local-fake'||p.dedicatedAccountRequired!==true||!Number.isInteger(p.challengeTtlSeconds)||p.challengeTtlSeconds<30||p.challengeTtlSeconds>300)fail();
 if(e?.transport!=='unix'||e.socket!=='/run/zinema/extractor.sock'||e.credentialFile!=='/run/secrets/extractor_key'||e.upstream!=='disabled'||e.browser!=='disabled'||e.maxBodyBytes!==2048||e.maxResponseBytes!==8192||e.deadlineMs!==4000)fail();
 const roots=['publicRoot','uploads','originals','sessions','state','backups'];
 if(!s||roots.some(key=>typeof s[key]!=='string'||!s[key].startsWith('/srv/zinema/')||path.posix.normalize(s[key])!==s[key])||new Set(roots.map(key=>s[key])).size!==roots.length||s.keyFile!=='/run/secrets/backup_key')fail();
 for(const key of roots.filter(k=>k!=='publicRoot'))if(s[key].startsWith(s.publicRoot+'/')||s.publicRoot.startsWith(s[key]+'/'))fail();
 for(const key of ['maxBytes','maxFiles','maxUploadBytes','quarantineMaxBytes'])if(!Number.isSafeInteger(s[key])||s[key]<1)fail();
 if(s.maxFiles>10000||s.maxUploadBytes>20971520||s.quarantineMaxBytes>s.maxBytes||s.maxUploadBytes>s.maxBytes)fail();
 if(b?.algorithm!=='AES-256-GCM'||b.mode!=='local-mirror'||b.offHostEnabled!==false||b.writersQuiescedRequired!==true||!Number.isSafeInteger(b.intervalSeconds)||b.intervalSeconds<60||!Number.isSafeInteger(b.restoreDrillSeconds)||b.restoreDrillSeconds<b.intervalSeconds)fail();
 if(m?.sink!=='local-outbox')fail();
 for(const key of ['heartbeatMaxAgeSeconds','queueMaxAgeSeconds','backupMaxAgeSeconds','diskUsedPercent','errorCount','outboxMaxEvents'])if(!Number.isSafeInteger(m[key])||m[key]<1)fail();
 if(m.diskUsedPercent>100||m.outboxMaxEvents>1000)fail();
 return JSON.parse(JSON.stringify(value));
}
module.exports={validate};
if(require.main===module){try{validate(require('./promotion-policy.json'));console.log('{"success":true,"localOnly":true}');}catch{console.log('{"success":false,"error":"promotion_policy_denied"}');process.exitCode=1;}}

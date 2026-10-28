'use strict';
const fs=require('node:fs'),path=require('node:path'),crypto=require('node:crypto');
function key(item) {
 let identity;
 if(item?.kind==='group_refresh') {
  if(['sourceGroupId','messageId','targetGroupId'].some(k=>typeof item[k]!=='string'||!item[k]||item[k].length>512))throw Error('maintenance_review_required');
  identity=['group_refresh',item.sourceGroupId,item.messageId,item.targetGroupId];
 }else {
  if(!['movie','episode'].includes(item?.type)||!Number.isSafeInteger(Number(item.contentId))||Number(item.contentId)<1||!Number.isSafeInteger(Number(item.partNumber||0))||Number(item.partNumber||0)<0)throw Error('maintenance_review_required');
  identity=['warming',item.type,Number(item.contentId),Number(item.partNumber||0)];
 }
 return crypto.createHash('sha256').update(JSON.stringify(identity)).digest('hex');
}
function assertNoPending(session) {if(fs.existsSync(path.join(session,'maintenance-review.pending.json')))throw Error('maintenance_review_required');}
function exclusions(session) {
 const file=path.join(session,'maintenance-exclusions.json');
 if(!fs.existsSync(file))return {schema:1,items:{}};
 const stat=fs.lstatSync(file);if(!stat.isFile()||stat.isSymbolicLink()||stat.nlink!==1||stat.size>1048576)throw Error('maintenance_review_required');
 const value=JSON.parse(fs.readFileSync(file,'utf8'));
 if(value?.schema!==1||!value.items||Array.isArray(value.items)||typeof value.items!=='object'||Object.keys(value.items).length>10000||Object.entries(value.items).some(([k,v])=>!(/^[a-f0-9]{64}$/.test(k))||v!==true))throw Error('maintenance_review_required');
 return value;
}
function excluded(session,item){return exclusions(session).items[key(item)]===true;}
module.exports={key,exclusions,excluded,assertNoPending};

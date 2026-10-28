'use strict';
function timeZone(){const value=process.env.BOT_TIMEZONE||'Asia/Colombo';new Intl.DateTimeFormat('en',{timeZone:value});return value;}
function parts(now=new Date()){const values=new Intl.DateTimeFormat('en-GB',{timeZone:timeZone(),hour:'2-digit',minute:'2-digit',hourCycle:'h23'}).formatToParts(now);return {hour:Number(values.find(p=>p.type==='hour').value),minute:Number(values.find(p=>p.type==='minute').value)};}
module.exports={timeZone,parts};

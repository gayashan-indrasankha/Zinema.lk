'use strict';
const express=require('express');const crypto=require('node:crypto');
const {normalizeUrl}=require('./extraction');
function createApp(config,{clock=Date.now,extract=async()=>({url:config.fakeMediaUrl})}={}) {
 const app=express();app.disable('x-powered-by');let inFlight=0,windowStart=clock(),admitted=0;
 app.use((req,res,next)=>{
  res.set('Cache-Control','no-store');
  const supplied=req.get('X-Extractor-Key')||'';
  if (!config.apiKey) return res.status(503).json({success:false,error:'disabled'});
  const digest=value=>crypto.createHash('sha256').update(value).digest();
  if (supplied.length!==64 || !crypto.timingSafeEqual(digest(supplied),digest(config.apiKey))) return res.status(401).json({success:false,error:'unauthorized'});
  next();
 });
 app.use(express.json({limit:2048,strict:true}));
 app.post('/health',(_req,res)=>res.json({success:true,mode:config.mode,uptime:Math.floor(process.uptime()),version:'local-contract-1'}));
 app.post('/api/extract',async(req,res)=>{
  if (!['local','private-fixture'].includes(config.mode)) return res.status(503).json({success:false,error:'disabled'});
  if (!req.body || Object.keys(req.body).some(key=>key!=='url')) return res.status(400).json({success:false,error:'invalid_request'});
  try {if (typeof req.body.url!=='string'||req.body.url.length>512) throw Error();normalizeUrl(req.body.url);} catch {return res.status(422).json({success:false,error:'invalid_source'});}
  if (clock()-windowStart>=60000) {windowStart=clock();admitted=0;}
  if (inFlight>=2 || admitted>=30) return res.status(429).set('Retry-After','60').json({success:false,error:'busy'});
  admitted++;inFlight++;
  try {const result=await extract();return res.json({success:true,mode:config.mode,url:result.url,method:'local-fixture',duration_ms:0});}
  catch {return res.status(502).json({success:false,error:'unavailable'});}finally{inFlight--;}
 });
 app.use((_req,res)=>res.status(405).json({success:false,error:'method_not_allowed'}));
 app.use((err,_req,res,_next)=>res.status(err.type==='entity.too.large'?413:400).json({success:false,error:'invalid_request'}));
 return app;
}
module.exports={createApp};

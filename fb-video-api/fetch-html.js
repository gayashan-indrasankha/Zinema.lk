'use strict';
const https=require('node:https');const dns=require('node:dns').promises;const net=require('node:net');
const {normalizeUrl}=require('./extraction');
const blocked4=new net.BlockList(),blocked6=new net.BlockList();
for(const [ip,bits] of [['0.0.0.0',8],['10.0.0.0',8],['100.64.0.0',10],['127.0.0.0',8],['169.254.0.0',16],['172.16.0.0',12],['192.0.0.0',24],['192.0.2.0',24],['192.168.0.0',16],['198.18.0.0',15],['198.51.100.0',24],['203.0.113.0',24],['224.0.0.0',4],['240.0.0.0',4]])blocked4.addSubnet(ip,bits,'ipv4');
for(const [ip,bits] of [['::',128],['::1',128],['::ffff:0:0',96],['fc00::',7],['fe80::',10],['ff00::',8],['2001:db8::',32],['2001::',32],['2001:2::',48],['2001:10::',28],['2001:20::',28],['2002::',16],['3fff::',20]])blocked6.addSubnet(ip,bits,'ipv6');
function publicAddress(address) {
 const family=net.isIP(address);
 if(family===4)return !blocked4.check(address,'ipv4');
 if(family===6)return /^[23][a-f0-9]{3}:/i.test(address)&&!blocked6.check(address,'ipv6');
 return false;
}
function createFetcher({resolve=(host)=>dns.lookup(host,{all:true,verbatim:true}),request=https.request}={}) {
 return async function fetchHtml(value) {
  let url=new URL(normalizeUrl(value));
  for(let redirects=0;redirects<=3;redirects++) {
   let dnsTimer;
   const addresses=await Promise.race([resolve(url.hostname),new Promise((_,reject)=>{
    dnsTimer=setTimeout(()=>reject(Error('Upstream DNS timeout')),10000);dnsTimer.unref?.();
   })]).finally(()=>clearTimeout(dnsTimer));
   if(!Array.isArray(addresses)||!addresses.length||addresses.some(item=>!publicAddress(item.address)||item.family!==net.isIP(item.address)))throw Error('Unsafe upstream address');
   let requestTimer;
   const response=await new Promise((resolveResponse,reject)=>{
    let failed=false;
    const req=request({protocol:'https:',hostname:url.hostname,port:443,servername:url.hostname,path:url.pathname+url.search,method:'GET',rejectUnauthorized:true,
     headers:{'User-Agent':'facebookexternalhit/1.1','Accept':'text/html','Accept-Encoding':'identity'},
     lookup:(_host,options,callback)=>options?.all ? callback(null,addresses) : callback(null,addresses[0].address,addresses[0].family)},res=>{
      const status=res.statusCode;
      if([301,302,303,307,308].includes(status)) {res.destroy();return resolveResponse({location:res.headers.location});}
      if(status!==200 || Number(res.headers['content-length']||0)>2097152 || !/^text\/html\b|^application\/xhtml\+xml\b/i.test(res.headers['content-type']||'')) {res.destroy();return reject(Error('Upstream unavailable'));}
      const parts=[];let bytes=0;
      res.on('data',part=>{bytes+=Buffer.byteLength(part);if(bytes>2097152){failed=true;res.destroy();reject(Error('Upstream response too large'));}else parts.push(Buffer.from(part));});
      res.on('end',()=>{if(!failed)resolveResponse({body:Buffer.concat(parts).toString('utf8')});});
      res.on('error',()=>reject(Error('Upstream response failed')));
     });
    req.setTimeout(10000,()=>req.destroy(Error('Upstream timeout')));
    requestTimer=setTimeout(()=>req.destroy(Error('Upstream deadline')),10000);requestTimer.unref?.();
    req.on('error',()=>reject(Error('Upstream request failed')));req.end();
   }).finally(()=>clearTimeout(requestTimer));
   if(response.body!==undefined)return response.body;
   if(!response.location || redirects===3)throw Error('Upstream redirect rejected');
   url=new URL(normalizeUrl(new URL(response.location,url).href));
  }
  throw Error('Upstream unavailable');
 };
}
module.exports={createFetcher,publicAddress};

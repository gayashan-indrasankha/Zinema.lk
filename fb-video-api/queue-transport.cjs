'use strict';
const http=require('node:http'),https=require('node:https');
// Fixed, owner-configured website origin only. No request can choose a destination.
function queueTransport(config,adapters={httpRequest:http.request,httpsRequest:https.request}) {
    const {origin,key,mode}=config,url=new URL(origin);
    if(typeof key!=='string'||!/^[a-f0-9]{64}$/.test(key)||url.username||url.password||url.search||url.hash||url.pathname!=='/')throw Error('worker_transport_denied');
    const local=mode==='local-fixture' && /^http:\/\/127\.0\.0\.1:[0-9]{1,5}$/.test(origin) && config.externalConnections===false;
    const staging=mode==='private-staging' && config.enabled===true && config.externalConnections===true && config.reviewed===true &&
        /^https:\/\/[a-z0-9](?:[a-z0-9.-]*[a-z0-9])$/.test(origin) && !url.port && url.hostname.includes('.') && !url.hostname.endsWith('.local');
    if(!local&&!staging)throw Error('worker_transport_denied');
    return input=>new Promise((resolve,reject)=>{
        const body=JSON.stringify(input);if(Buffer.byteLength(body)>2048)return reject(Error('worker_contract_denied'));
        const send=local?adapters.httpRequest:adapters.httpsRequest;
        const request=send(origin+'/api/extractor-work.php',{method:'POST',agent:false,rejectUnauthorized:true,minVersion:'TLSv1.2',headers:{'Content-Type':'application/json','Accept':'application/json','Content-Length':Buffer.byteLength(body),'X-Extractor-Worker-Key':key}},response=>{
            let raw='';response.setEncoding('utf8');
            response.on('data',chunk=>{raw+=chunk;if(Buffer.byteLength(raw)>8192)response.destroy(Error('worker_contract_denied'));});
            response.on('error',()=>reject(Error('worker_unavailable')));
            response.on('end',()=>{try{
                const result=JSON.parse(raw);
                if(response.statusCode!==200 || !/^application\/json(?:;|$)/i.test(response.headers['content-type']||'') || result.success!==true)throw Error();
                resolve(result);
            }catch{reject(Error('worker_unavailable'));}});
        });
        const deadline=setTimeout(()=>request.destroy(Error('worker_timeout')),4000);
        request.on('close',()=>clearTimeout(deadline));request.on('error',()=>reject(Error('worker_unavailable')));request.end(body);
    });
}
module.exports={queueTransport};

'use strict';
const {test}=require('node:test');const assert=require('node:assert/strict');const {EventEmitter}=require('node:events');
const {createFetcher,publicAddress}=require('../../../fb-video-api/fetch-html');
function fixture(responses,addresses=[{address:'8.8.8.8',family:4}]) {
 const seen=[];let lookups=0;
 const fetch=createFetcher({resolve:async()=>{lookups++;return addresses;},request:(options,callback)=>{
  seen.push(options);const req=new EventEmitter();req.setTimeout=()=>{};req.destroy=()=>req.emit('error',Error('fixture'));
  req.end=()=>queueMicrotask(()=>{const value=responses.shift();const res=new EventEmitter();res.destroy=()=>{};res.statusCode=value.status||200;res.headers={'content-type':'text/html',...value.headers};callback(res);if(res.statusCode===200){res.emit('data',Buffer.from(value.body||'<html>Original fixture</html>'));res.emit('end');}});return req;
 }});return {fetch,seen,lookups:()=>lookups};
}
test('Public address filter excludes loopback, private, mapped and metadata ranges',()=>{
 for(const ip of ['127.0.0.1','10.0.0.1','169.254.169.254','192.168.1.1','100.64.1.1','::1','::ffff:127.0.0.1','fc00::1','fe80::1','2001:db8::1'])assert.equal(publicAddress(ip),false);
 assert.equal(publicAddress('8.8.8.8'),true);assert.equal(publicAddress('2606:4700:4700::1111'),true);
});
test('HTTPS request pins validated DNS results and preserves TLS verification',async()=>{
 const f=fixture([{}]);assert.match(await f.fetch('https://www.facebook.com/watch/?v=fixture'),/Original fixture/);
 assert.equal(f.lookups(),1);const opts=f.seen[0];assert.equal(opts.rejectUnauthorized,true);assert.equal(opts.servername,'www.facebook.com');
 opts.lookup('www.facebook.com',{},(_error,ip,family)=>{assert.equal(ip,'8.8.8.8');assert.equal(family,4);});
});
test('Mixed private/public DNS answers fail before any HTTP request',async()=>{
 const f=fixture([],[{address:'8.8.8.8',family:4},{address:'127.0.0.1',family:4}]);await assert.rejects(f.fetch('https://facebook.com/a'));assert.equal(f.seen.length,0);
});
test('Redirect hosts are validated before resolution or a second request',async()=>{
 const f=fixture([{status:302,headers:{location:'https://facebook.com.example.invalid/a'}}]);await assert.rejects(f.fetch('https://facebook.com/a'));assert.equal(f.seen.length,1);assert.equal(f.lookups(),1);
});
test('Allowed redirects recheck DNS and redirect chains are bounded',async()=>{
 const f=fixture([{status:302,headers:{location:'https://www.facebook.com/b'}},{}]);assert.match(await f.fetch('https://fb.watch/a'),/Original fixture/);assert.equal(f.lookups(),2);
 const loop=fixture(Array.from({length:4},()=>({status:302,headers:{location:'/again'}})));await assert.rejects(loop.fetch('https://facebook.com/a'));assert.equal(loop.seen.length,4);
});
test('Oversized, non-HTML and failed responses are rejected',async()=>{
 for(const response of [{body:'x'.repeat(2097153)},{headers:{'content-type':'application/octet-stream'}},{status:500}]){const f=fixture([response]);await assert.rejects(f.fetch('https://facebook.com/a'));assert.equal(f.seen.length,1);}
});
test('A stalled DNS resolver is bounded without any network call',async t=>{
 t.mock.timers.enable({apis:['setTimeout']});let contacted=false;
 const fetch=createFetcher({resolve:()=>new Promise(()=>{}),request:()=>{contacted=true;}});
 const pending=fetch('https://facebook.com/a');t.mock.timers.tick(10000);await assert.rejects(pending,/DNS timeout/);assert.equal(contacted,false);
});
test('A stalled HTTP response has an absolute deadline',async t=>{
 t.mock.timers.enable({apis:['setTimeout']});
 const fetch=createFetcher({resolve:async()=>[{address:'8.8.8.8',family:4}],request:()=>{
  const req=new EventEmitter();req.end=()=>{};req.setTimeout=()=>{};req.destroy=()=>req.emit('error',Error('fixture timeout'));return req;
 }});
 const pending=fetch('https://facebook.com/a');await new Promise(resolve=>setImmediate(resolve));t.mock.timers.tick(10000);await assert.rejects(pending,/request failed/);
});

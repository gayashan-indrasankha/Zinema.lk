'use strict';
const fs=require('node:fs'),path=require('node:path'),os=require('node:os'),cp=require('node:child_process'),net=require('node:net'),crypto=require('node:crypto');
async function start(){
 const root=path.resolve(__dirname,'../..'),directory=fs.mkdtempSync(path.join(os.tmpdir(),'zinema-zl19-http-'));fs.mkdirSync(path.join(directory,'sessions'));
 const minimal=process.platform==='win32'?{SystemRoot:process.env.SystemRoot||'C:\\Windows'}:{};
 const php=process.env.PHP_BINARY||'php';
 const generated=cp.spawnSync(php,['-n','-r',"require 'tests/identity/fixtures.php';$s=new FixtureStore();$p=new \\Zinema\\Identity\\Password();seed($s,$p);seed($s,$p,str_repeat('b',32),true,true,['account.read','admin.enter']);echo json_encode(array_values(array_map(fn($a)=>get_object_vars($a),$s->accounts)),JSON_THROW_ON_ERROR);"],{cwd:root,env:minimal,encoding:'utf8',timeout:10000});
 if(generated.status!==0)throw Error('SYNTHETIC_STATE_GENERATION_FAILED');
 fs.writeFileSync(path.join(directory,'state.json'),JSON.stringify({accounts:JSON.parse(generated.stdout),tokens:{},events:[],mail:[],limits:{},now:1792800000,key:crypto.randomBytes(32).toString('hex')}),{mode:0o600});
 const socket=net.createServer();await new Promise(resolve=>socket.listen(0,'127.0.0.1',resolve));const port=socket.address().port;await new Promise(resolve=>socket.close(resolve));const origin='http://127.0.0.1:'+port;
 const child=cp.spawn(php,['-n','-d','display_errors=0','-S','127.0.0.1:'+port,'-t',directory,path.join(__dirname,'router.php')],{cwd:root,env:{...minimal,ZL19_FIXTURE_ROOT:directory,ZL19_FIXTURE_ORIGIN:origin},stdio:'ignore'});child.on('error',()=>{});
 const state=()=>JSON.parse(fs.readFileSync(path.join(directory,'state.json'),'utf8'));
 async function close(){if(child.exitCode===null){const done=new Promise(resolve=>child.once('close',resolve));child.kill();await Promise.race([done,new Promise(resolve=>setTimeout(resolve,2000))]);if(child.exitCode===null && child.signalCode===null)throw Error('FIXTURE_SERVER_DID_NOT_STOP');}if(path.dirname(directory)!==os.tmpdir()||!path.basename(directory).startsWith('zinema-zl19-http-')||fs.lstatSync(directory).isSymbolicLink())throw Error('UNSAFE_CLEANUP');for(const name of fs.readdirSync(path.join(directory,'sessions'))){if(!/^sess_[a-zA-Z0-9,-]+$/.test(name))throw Error('UNEXPECTED_SESSION_FIXTURE');fs.unlinkSync(path.join(directory,'sessions',name));}fs.rmdirSync(path.join(directory,'sessions'));fs.unlinkSync(path.join(directory,'state.json'));fs.rmdirSync(directory);}
 for(let i=0;i<60;i++){try{const r=await fetch(origin+'/health',{signal:AbortSignal.timeout(300)});if(r.ok)return {origin,state,close};}catch{}await new Promise(resolve=>setTimeout(resolve,50));}
 await close();throw Error('LOCAL_AUTH_SERVER_UNAVAILABLE');
}
function client(server){let cookie='',csrf='';return {get cookie(){return cookie;},set cookie(v){cookie=v;},get csrf(){return csrf;},async request(route,data,options={}){const headers={Cookie:cookie,...(data?{'Content-Type':'application/json',Origin:server.origin,'X-CSRF-Token':csrf}:{}),...options.headers};const r=await fetch(server.origin+route,{method:options.method||(data?'POST':'GET'),headers,body:data?JSON.stringify(data):undefined,redirect:'manual'});const set=r.headers.getSetCookie();for(const v of set){if(v.startsWith('ZL19SID='))cookie=v.split(';')[0];}const body=await r.json();if(body.csrf)csrf=body.csrf;return {status:r.status,body,headers:r.headers,set};}};}
module.exports={start,client};

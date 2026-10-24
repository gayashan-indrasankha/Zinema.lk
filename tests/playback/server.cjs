'use strict';
const fs=require('node:fs'),cp=require('node:child_process'),net=require('node:net'),path=require('node:path'),crypto=require('node:crypto');
async function start(media){
 const root=path.resolve(__dirname,'../..'),directory=path.join(root,'.recovery-private','zl17-server-'+crypto.randomBytes(8).toString('hex'));fs.mkdirSync(directory,{recursive:true});
 const socket=net.createServer();await new Promise(resolve=>socket.listen(0,'127.0.0.1',resolve));const port=socket.address().port;await new Promise(resolve=>socket.close(resolve));const origin='http://127.0.0.1:'+port;
 const env={ZL17_MEDIA:path.resolve(media),ZL17_STATE:path.join(directory,'state.json')};if(process.platform==='win32')env.SystemRoot=process.env.SystemRoot||'C:\\Windows';
 const php=process.env.PHP_BINARY||'php';const code="require 'tests/playback/fixtures.php';$p=new \\Zinema\\Playback\\LocalFixtureProvider(getenv('ZL17_MEDIA'));echo json_encode(['records'=>playbackRecords($p->digest())],JSON_THROW_ON_ERROR);";
 const generated=cp.spawnSync(php,['-n','-r',code],{cwd:root,env,encoding:'utf8',timeout:15000});if(generated.status!==0)throw Error('SYNTHETIC_STATE_CREATION_FAILED');const initial={...JSON.parse(generated.stdout),origin,sessionKey:crypto.randomBytes(32).toString('hex')};
 const state=value=>fs.writeFileSync(env.ZL17_STATE,JSON.stringify(value),{mode:0o600});state(initial);
 const child=cp.spawn(php,['-n','-d','display_errors=0','-S','127.0.0.1:'+port,'-t',directory,path.join(__dirname,'router.php')],{cwd:root,env,stdio:'ignore'});child.on('error',()=>{});
 async function close(){if(child.exitCode===null){const done=new Promise(resolve=>child.once('close',resolve));child.kill();await Promise.race([done,new Promise(resolve=>setTimeout(resolve,2000))]);if(child.exitCode===null)child.kill('SIGKILL');}if(path.dirname(directory)!==path.join(root,'.recovery-private')||fs.lstatSync(directory).isSymbolicLink())throw Error('UNSAFE_CLEANUP');fs.unlinkSync(env.ZL17_STATE);fs.rmdirSync(directory);}
 for(let i=0;i<60;i++){try{const r=await fetch(origin+'/health',{signal:AbortSignal.timeout(300)});if(r.ok)return {origin,initial,state,close};}catch{}await new Promise(resolve=>setTimeout(resolve,50));}
 await close();throw Error('LOCAL_PLAYBACK_SERVER_UNAVAILABLE');
}
module.exports={start};

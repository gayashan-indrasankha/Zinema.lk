'use strict';
// Live/provider/browser paths remain quarantined. Local mode returns original fixtures only.
const config=require('./environment').readConfig();
const app=require('./app').createApp(config);
let server;
if(config.socket) {
 const fs=require('node:fs'),path=require('node:path'),parent=path.dirname(config.socket),stat=fs.lstatSync(parent);
 if(!stat.isDirectory()||stat.isSymbolicLink()||fs.realpathSync(parent)!==parent||stat.uid!==process.getuid()||(stat.mode&7)!==0||fs.existsSync(config.socket))throw Error('private_socket_review_required');
 process.umask(0o007);
 server=app.listen(config.socket,()=>{fs.chmodSync(config.socket,0o660);console.log('Private fixture socket ready; outbound integrations disabled.');});
}else server=app.listen(config.port,config.host,()=>console.log('Private extractor contract listening on loopback; request details suppressed.'));
server.on('error',()=>{console.error('extractor_startup_failed');process.exitCode=1;});
server.requestTimeout=5000;server.headersTimeout=5000;server.keepAliveTimeout=1000;server.maxConnections=64;
for(const signal of ['SIGINT','SIGTERM'])process.once(signal,()=>{server.close(()=>process.exit(0));setTimeout(()=>process.exit(1),5000).unref();});

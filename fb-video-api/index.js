'use strict';
// Live/provider/browser paths remain quarantined. Local mode returns original fixtures only.
const config=require('./environment').readConfig();
const app=require('./app').createApp(config);
const server=app.listen(config.port,config.host,()=>console.log('Private extractor contract listening on loopback; request details suppressed.'));
server.requestTimeout=5000;server.headersTimeout=5000;server.keepAliveTimeout=1000;server.maxConnections=64;
for(const signal of ['SIGINT','SIGTERM'])process.once(signal,()=>{server.close(()=>process.exit(0));setTimeout(()=>process.exit(1),5000).unref();});

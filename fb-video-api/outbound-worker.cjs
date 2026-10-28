'use strict';
// Executable LOCAL fake only. No upstream extractor, SDK, account or daemon startup.
const fs=require('node:fs'),path=require('node:path');
const {ExtractorWorker}=require('./queue-worker.cjs');
const {queueTransport}=require('./queue-transport.cjs');
function localBridge(origin,key) {
    return queueTransport({origin,key,mode:'local-fixture',externalConnections:false});
}
async function main(args) {
    if(args.length!==2||args[0]!=='--local-fixture')throw Error('local_worker_configuration_denied');
    const file=path.resolve(args[1]),boundary=path.resolve(__dirname,'../.runtime');
    if(!file.startsWith(boundary+path.sep)||fs.lstatSync(file).isSymbolicLink()||fs.statSync(file).size>8192)throw Error('local_worker_configuration_denied');
    const c=JSON.parse(fs.readFileSync(file,'utf8'));
    if(c.mode!=='local-fixture'||c.externalConnections!==false||c.publishing!==false||typeof c.fixtureUrl!=='string'||!new RegExp('^'+c.origin.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')+'/uploads/trailers/[a-f0-9]{32}\\.mp4$').test(c.fixtureUrl)||path.resolve(c.directory)!==path.dirname(file))throw Error('local_worker_configuration_denied');
    const worker=new ExtractorWorker({directory:c.directory,bridge:localBridge(c.origin,c.key),extract:async()=>c.fixtureUrl});
    console.log(JSON.stringify(await worker.runOnce()));
}
if(require.main===module)main(process.argv.slice(2)).catch(()=>{console.error('Local worker refused; no private diagnostics printed.');process.exitCode=1;});
module.exports={localBridge};

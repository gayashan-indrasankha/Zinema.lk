'use strict';
const fs=require('fs'),os=require('os'),path=require('path');
const MiB=1024*1024,GiB=1024*MiB;
class ResourcePressure {
    constructor(root,{maxRssBytes=384*MiB,minFreeMemoryBytes=512*MiB,minFreeDiskBytes=10*GiB,
        memory=()=>process.memoryUsage(),freeMemory=()=>os.freemem(),disk=location=>fs.statfsSync(location)}={}) {
        if(!path.isAbsolute(root)||[maxRssBytes,minFreeMemoryBytes,minFreeDiskBytes].some(n=>!Number.isSafeInteger(n)||n<1))throw Error('resource_configuration_denied');
        this.root=root;this.limits={maxRssBytes,minFreeMemoryBytes,minFreeDiskBytes};this.memory=memory;this.freeMemory=freeMemory;this.disk=disk;
    }
    sample(reservedBytes=0) {
        let rss,freeMemory,available;
        try {
            if(!Number.isSafeInteger(reservedBytes)||reservedBytes<0)throw Error();
            rss=this.memory().rss;freeMemory=this.freeMemory();const stats=this.disk(this.root);
            available=Number(stats.bavail)*Number(stats.bsize);
            if([rss,freeMemory,available].some(n=>!Number.isSafeInteger(n)||n<0)||rss===0)throw Error();
        } catch{return {allowed:false,reason:'resource_status_unknown'};}
        const reason=rss>=this.limits.maxRssBytes?'worker_memory_pressure':freeMemory<this.limits.minFreeMemoryBytes?'system_memory_pressure':available-reservedBytes<this.limits.minFreeDiskBytes?'media_disk_pressure':'ready';
        return {allowed:reason==='ready',reason,rssBytes:rss,freeMemoryBytes:freeMemory,availableDiskBytes:available};
    }
    assertReady(reservedBytes=0) {
        const status=this.sample(reservedBytes);
        if(!status.allowed) {const error=Error(status.reason);error.resourcePressure=true;throw error;}
        return status;
    }
}
module.exports={ResourcePressure};

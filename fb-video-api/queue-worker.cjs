'use strict';
const fs=require('node:fs'),path=require('node:path'),crypto=require('node:crypto');

// A receipt never appears in logs. A non-idle intent survives process restart.
class ExtractorWorker {
    constructor({directory,bridge,extract}) {
        if(!path.isAbsolute(directory)||!fs.statSync(directory).isDirectory()||fs.lstatSync(directory).isSymbolicLink()||fs.realpathSync(directory)!==directory)throw Error('worker_private_state_required');
        this.file=path.join(directory,'extraction-intent.json');this.bridge=bridge;this.extract=extract;this.running=false;
    }
    read() {
        if(!fs.existsSync(this.file))return {state:'idle'};
        const stat=fs.lstatSync(this.file);
        if(!stat.isFile()||stat.isSymbolicLink()||stat.nlink!==1||stat.size>8192)throw Error('worker_state_denied');
        const data=JSON.parse(fs.readFileSync(this.file,'utf8'));
        if(!data||!['idle','intent','working','held'].includes(data.state))throw Error('worker_state_denied');
        return data;
    }
    write(value) {
        const temp=this.file+'.'+crypto.randomBytes(8).toString('hex')+'.tmp';
        try {const fd=fs.openSync(temp,'wx',0o600);try{fs.writeFileSync(fd,JSON.stringify(value));fs.fsyncSync(fd);}finally{fs.closeSync(fd);}fs.renameSync(temp,this.file);}
        finally{if(fs.existsSync(temp))fs.unlinkSync(temp);}
    }
    async runOnce() {
        if(this.running)return {state:'busy'};this.running=true;
        let job,lease;
        try {
            const lock=this.file+'.lock';
            try {lease=fs.openSync(lock,'wx',0o600);fs.writeFileSync(lease,'private-extractor-worker');fs.fsyncSync(lease);}
            catch(error){if(error.code==='EEXIST')return {state:'held'};throw error;}
            if(this.read().state!=='idle')return {state:'held'};
            await this.bridge({action:'heartbeat'});
            const response=await this.bridge({action:'claim'});job=response.job;
            if(!job)return {state:'idle'};
            if(!/^[a-f0-9]{32}$/.test(job.job_id)||!/^[a-f0-9]{64}$/.test(job.receipt)||typeof job.url!=='string'||job.url.length>512)throw Error('worker_contract_denied');
            this.write({state:'intent',job_id:job.job_id,receipt:job.receipt});
            await this.bridge({action:'begin',job_id:job.job_id,receipt:job.receipt});
            this.write({state:'working',job_id:job.job_id,receipt:job.receipt});
            const url=await this.extract(job.url);
            await this.bridge({action:'complete',job_id:job.job_id,receipt:job.receipt,url});
            this.write({state:'idle'});return {state:'done'};
        } catch {
            if(job) {
                // Failure to hold is still safe: the website lease expires into held.
                try{this.write({state:'held',job_id:job.job_id,receipt:job.receipt});}catch{}
                try{await this.bridge({action:'hold',job_id:job.job_id,receipt:job.receipt});}catch{}
            }
            return {state:job?'held':'unavailable'};
        } finally{
            if(lease!==undefined){
                const owned=fs.fstatSync(lease);fs.closeSync(lease);const current=fs.lstatSync(this.file+'.lock');
                if(current.ino===owned.ino && current.dev===owned.dev && !current.isSymbolicLink())fs.unlinkSync(this.file+'.lock');
            }
            this.running=false;
        }
    }
}
module.exports={ExtractorWorker};

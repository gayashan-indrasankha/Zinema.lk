/**
 * Auto Refresher (Tier 2 Maintenance)
 * Periodically forwards media from Storage Groups to Cache Group to keep them 'hot'
 */

const schedule = require('node-schedule');
const fs = require('fs');
const path = require('path');

class AutoRefresher {
    constructor(sock, apiClient, cacheGroupId, storageGroupIds = []) {
        this.sock = sock;
        this.apiClient = apiClient;
        this.cacheGroupId = cacheGroupId;
        this.storageGroupIds = storageGroupIds;
        this.isRefreshing = false;
        this.job = null;
        this.lastProgressUpdate = 0;

        // Interval in hours (default 72h / 3 days)
        this.refreshIntervalHours = parseInt(process.env.REFRESH_INTERVAL_HOURS) || 72;

        // Upload retry config
        this.maxRetries = 1;
        this.consecutiveErrors = 0;
        this.maxConsecutiveErrors = 5;

        // Bandwidth tracking (informational only, no limit)
        this.bytesUploadedThisSession = 0;

        // Reconnection support
        this.socketReady = true;
        this._reconnectResolve = null;
        this.stopRequested=false;
    }

    /**
     * Update socket reference after reconnection (called from index.js)
     */
    updateSocket(newSock) {
        this.sock = newSock;
        this.socketReady = true;
        this.bytesUploadedThisSession = 0;
        this.consecutiveErrors = 0;
        console.log(`🔌 AutoRefresher: Socket updated after reconnection`);

        if (this._reconnectResolve) {
            this._reconnectResolve();
            this._reconnectResolve = null;
        }
    }

    /**
     * Wait for socket to reconnect (max 2 minutes)
     * Returns true if reconnected, false if timed out
     */
    async waitForReconnection(maxWaitMs = 120000) {
        if (this.socketReady) return true;

        console.log(`⏳ Waiting up to ${maxWaitMs / 1000}s for socket to reconnect...`);

        return new Promise(resolve => {
            const timeout = setTimeout(() => {
                this._reconnectResolve = null;
                console.log(`❌ Reconnection timed out after ${maxWaitMs / 1000}s`);
                resolve(false);
            }, maxWaitMs);

            this._reconnectResolve = () => {
                clearTimeout(timeout);
                console.log(`✅ Socket reconnected! Resuming uploads...`);
                resolve(true);
            };
        });
    }

    /**
     * Generate a visual progress bar
     */
    generateProgressBar(current, total, width = 20) {
        const percent = Math.round((current / total) * 100);
        const filled = Math.round((current / total) * width);
        const empty = width - filled;
        const bar = '█'.repeat(filled) + '░'.repeat(empty);
        return `[${bar}] ${percent}% (${current}/${total})`;
    }

    /**
     * Log progress to console with visual bar
     */
    logProgress(current, total, fileName) {
        const progressBar = this.generateProgressBar(current, total);
        console.log(`\n${progressBar}`);
        console.log(`📤 Current: ${fileName}`);
    }

    /**
     * Send progress update to WhatsApp (rate limited to avoid flooding)
     */
    async sendProgressUpdate(notifyJid, current, total, refreshed, errors) {
        if (!notifyJid || !this.sock) return;

        const now = Date.now();
        const timeSinceLastUpdate = now - this.lastProgressUpdate;

        // Send update every 20 items or every 60 seconds (reduced to ease rate limiting)
        if (current % 20 === 0 || timeSinceLastUpdate > 60000 || current === total) {
            this.lastProgressUpdate = now;

            const progressBar = this.generateProgressBar(current, total);
            const message = `📊 *Refresh Progress*\n\n${progressBar}\n\n✅ Refreshed: ${refreshed}\n❌ Errors: ${errors}`;

            try {
                await this.sock.sendMessage(notifyJid, { text: message });
                await new Promise(r => setTimeout(r, 2000)); // Small delay after progress msg
            } catch (e) {
                console.error(`Failed to send progress update: ${e.message}`);
            }
        }
    }

    /**
     * Calculate smart delay based on file size
     * WhatsApp drops the connection after ~5GB of rapid uploads
     */
    getSmartDelay(filePath) {
        try {
            const stats = fs.statSync(filePath);
            const sizeMB = stats.size / 1024 / 1024;

            if (sizeMB > 1000) return 180000 + Math.random() * 120000; // 3-5 min for 1GB+
            if (sizeMB > 500) return 120000 + Math.random() * 60000;  // 2-3 min for 500MB-1GB
            if (sizeMB > 200) return 60000 + Math.random() * 30000;   // 1-1.5 min for 200-500MB
            if (sizeMB > 50) return 30000 + Math.random() * 15000;   // 30-45s for 50-200MB
            return 15000 + Math.random() * 10000;                      // 15-25s for small files
        } catch (e) {
            return 60000;
        }
    }

    /**
     * Check if an error indicates a connection drop
     */
    isConnectionError(error) {
        const msg = error.message || '';
        return msg.includes('close') || msg.includes('Stream Errored') ||
            msg.includes('reconnect') || msg.includes('Connection') ||
            msg.includes('ECONNRESET') || msg.includes('EPIPE') ||
            msg.includes('socket hang up') || msg.includes('write after end');
    }

    /**
     * Calculate upload timeout based on file size
     * Base: 3 minutes + 1 minute per 100MB
     */
    getUploadTimeout(filePath) {
        try {
            const stats = fs.statSync(filePath);
            const sizeMB = stats.size / 1024 / 1024;
            const timeoutMs = (3 * 60 * 1000) + (sizeMB / 100) * (60 * 1000);
            const timeoutMin = (timeoutMs / 60000).toFixed(1);
            console.log(`   ⏱️ Upload timeout: ${timeoutMin} min (file: ${sizeMB.toFixed(0)} MB)`);
            return timeoutMs;
        } catch (e) {
            return 10 * 60 * 1000;
        }
    }

    /**
     * Send a message with timeout and retry logic
     * Timeouts are NOT retried — they indicate rate limiting.
     */
    async sendMessageWithRetry(jid,content,filePath=null) {
        const timeoutMs=filePath ? this.getUploadTimeout(filePath) : 600000;let deadline;
        try {
            const result=await Promise.race([Promise.resolve().then(()=>this.sock.sendMessage(jid,content)),new Promise((_,reject)=>{deadline=setTimeout(()=>reject(Error('Upload acknowledgement unavailable')),timeoutMs);})]);
            if(!result?.key?.id)throw Error('Upload acknowledgement unavailable');
            return result;
        }finally{clearTimeout(deadline);}
    }

    /**
     * Start the auto refresher schedule
     */
    start() {
        if(this.job || this.isRefreshing)return;
        this.stopRequested=false;
        if (!this.cacheGroupId || this.storageGroupIds.length === 0) {
            console.log('⚠️ AutoRefresher: Cache group or Storage groups missing. Disabled.');
            return;
        }

        // Schedule to run at 1:00 AM every day (staggered with Cache Warmer at 12:30 AM)
        this.job = schedule.scheduleJob({rule:'0 1 * * *',tz:require('./worker-clock').timeZone()}, async () => {
            console.log('\n🔄 Scheduled Auto-Refresh cycle starting...');
            await this.runRefreshCycle();
        });

        console.log(`🔄 AutoRefresher: Scheduled for 1:00 AM daily (Interval: ${this.refreshIntervalHours}h)`);
    }

    /**
     * Run the full refresh cycle across all storage groups
     */
    async runRefreshCycle(manualNotifyJid=null) {
        if(this.stopRequested || !this.socketReady || this.gate?.running || this.isRefreshing || !this.warmer)return {success:false,message:'Maintenance unavailable.'};
        try {
            const pending=this.warmer.loadState();
            if(pending?.held || pending?.remainingFiles?.length)return {success:false,held:!!pending.held,paused:true};
        }catch{return {success:false,error:'maintenance_checkpoint_unavailable'};}
        this.isRefreshing=true;this.gate.running=true;
        let refreshed=0,skipped=0;
        try {
            if(manualNotifyJid)await this.sock.sendMessage(manualNotifyJid,{text:'Starting cache maintenance.'});
            for(const storageId of this.storageGroupIds) {
                if(this.stopRequested || !this.socketReady)break;
                const messages=await this.sock.fetchMessagesFromChat(storageId,100);
                if(!Array.isArray(messages)||messages.length>100)throw Error();
                for(const msg of messages) {
                    if(this.stopRequested || !this.socketReady)break;
                    if(!(msg.message?.videoMessage||msg.message?.documentMessage||msg.message?.imageMessage))continue;
                    if(!await this.apiClient.needsRefresh(msg.key.id)){skipped++;continue;}
                    this.warmer.saveState({remainingFiles:[],held:true,reason:'inflight_group_refresh'});
                    try {
                        await this.sendMessageWithRetry(this.cacheGroupId,{forward:msg});
                        if(await this.apiClient.logRefresh(msg.key.id,'','video')!==true)throw Error();
                        this.warmer.clearState();refreshed++;
                    }catch{return {success:false,held:true,refreshed,skipped,message:'Unconfirmed cache refresh; review checkpoint.'};}
                    if(!this.stopRequested)await new Promise(resolve=>setTimeout(resolve,5000));
                }
            }
            if(manualNotifyJid && !this.stopRequested)await this.sock.sendMessage(manualNotifyJid,{text:`Cache maintenance: ${refreshed} refreshed, ${skipped} skipped.`});
            return {success:!this.stopRequested,refreshed,skipped};
        }catch{return {success:false,error:'refresh_unavailable',refreshed,skipped};}
        finally{this.isRefreshing=false;this.gate.running=false;}
    }

    stop() {
        this.stopRequested=true;
        if (this.job) {
            this.job.cancel();
            this.job = null;
        }
    }

    /**
     * Refresh specific ID range (for manual command !refresh movie 96-100)
     * Re-uploads content from local disk to cache group
     * @param {string} contentType - 'movie' or 'episode'
     * @param {number} startId - Starting content ID
     * @param {number} endId - Ending content ID
     * @param {string} notifyJid - JID to send progress notifications to
     */
    setWarmer(warmer) {this.warmer=warmer;this.gate=warmer.gate;}

    async refreshIdRange(contentType,startId,endId,notifyJid=null) {
        if(!this.warmer || this.stopRequested || !this.socketReady)return {success:false,message:'Maintenance unavailable.'};
        const result=await this.warmer.warmIdRange(contentType,startId,endId,true,notifyJid,true);
        return {...result,refreshed:result.warmed||0};
    }

    async refreshSingleFile() {throw Error('Use checkpointed maintenance workflow');}

}

module.exports = AutoRefresher;

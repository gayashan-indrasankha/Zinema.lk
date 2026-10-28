/**
 * Cache Warmer
 * Proactively refreshes videos from local disk to WhatsApp cache
 * 
 * Features:
 * - Auto-warm on bot startup
 * - Nightly warming at 12:00 AM to 5:00 AM
 * - Manual warming via command with ID range
 * - Resume from remaining files across multiple nights
 */

const schedule = require('node-schedule');
const fs = require('fs');
const path = require('path');

class CacheWarmer {
    constructor(sock, fileManager, cacheGroupId) {
        this.sock = sock; // Baileys socket
        this.fileManager = fileManager;
        this.cacheGroupId = cacheGroupId;
        this.isRunning = false;
        this.job = null;
        this.apiClient = null;
        this.stateFile = path.join(require('./environment').readConfig().sessionPath, 'warming-state.json');

        // Progress tracking
        this.progressChatId = null;  // Where to send progress updates
        this.lastProgressUpdate = 0;  // Timestamp of last progress message

        // Upload retry config
        this.maxRetries = 1; // An unacknowledged send is ambiguous; do not repeat it automatically.
        this.consecutiveErrors = 0;
        this.maxConsecutiveErrors = 5; // Allow more errors since we can recover from connection drops

        // Bandwidth tracking (informational only, no limit)
        this.bytesUploadedThisSession = 0;

        // Reconnection support
        this.socketReady = true;
        this._reconnectResolve = null;
        this.stopRequested = false;
        this.gate={running:false};

        // Statistics
        this.stats = {
            lastRunTime: null,
            videosWarmed: 0,
            errors: 0,
            totalRuns: 0
        };
    }

    /**
     * Update socket reference after reconnection (called from index.js)
     */
    updateSocket(newSock) {
        this.sock = newSock;
        this.socketReady = true;
        this.bytesUploadedThisSession = 0; // Reset — fresh connection
        this.consecutiveErrors = 0;
        console.log(`🔌 CacheWarmer: Socket updated after reconnection`);

        // Resolve any pending waitForReconnection promise
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
     * Send progress update to WhatsApp (rate limited to avoid flooding)
     */
    async sendProgressUpdate(current, total, warmed, errors, skipped = 0) {
        if (!this.progressChatId || !this.sock) return;

        const now = Date.now();
        const timeSinceLastUpdate = now - this.lastProgressUpdate;

        // Send update every 20 items or every 60 seconds (reduced from 10/30s to ease rate limiting)
        if (current % 20 === 0 || timeSinceLastUpdate > 60000 || current === total) {
            this.lastProgressUpdate = now;

            const progressBar = this.generateProgressBar(current, total);
            const message = `📊 *Upload Progress*\n\n${progressBar}\n\n✅ Uploaded: ${warmed}\n⏭️ Skipped: ${skipped}\n❌ Errors: ${errors}`;

            try {
                await this.sock.sendMessage(this.progressChatId, { text: message });
                // Small delay after sending progress to not interfere with uploads
                await new Promise(r => setTimeout(r, 2000));
            } catch (e) {
                console.error(`Failed to send progress update: ${e.message}`);
            }
        }
    }

    /**
     * Calculate smart delay based on file size
     * WhatsApp drops the connection after ~5GB of rapid uploads, so large files
     * need LONG cooldowns between them to avoid rate limiting
     */
    getSmartDelay(filePath) {
        try {
            const stats = fs.statSync(filePath);
            const sizeMB = stats.size / 1024 / 1024;

            if (sizeMB > 1000) return 180000 + Math.random() * 120000; // 3-5 min for 1GB+ files
            if (sizeMB > 500) return 120000 + Math.random() * 60000;  // 2-3 min for 500MB-1GB
            if (sizeMB > 200) return 60000 + Math.random() * 30000;   // 1-1.5 min for 200-500MB
            if (sizeMB > 50) return 30000 + Math.random() * 15000;   // 30-45s for 50-200MB
            return 15000 + Math.random() * 10000;                      // 15-25s for small files
        } catch (e) {
            return 60000; // Default 1 min if can't read file
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
     * Tiered formula — large files get significantly more time:
     *   ≤ 1GB: 5min base + 1.5min per 100MB  (500MB → ~12.5 min, 1GB → ~20 min)
     *   > 1GB: 10min base + 2min per 100MB   (1.7GB → ~45.7 min, 2GB → ~51 min)
     */
    getUploadTimeout(filePath) {
        try {
            const stats = fs.statSync(filePath);
            const sizeMB = stats.size / 1024 / 1024;
            let timeoutMs;
            if (sizeMB > 1024) {
                // Large files: 10min base + 2min per 100MB
                timeoutMs = (10 * 60 * 1000) + (sizeMB / 100) * (2 * 60 * 1000);
            } else {
                // Normal files: 5min base + 1.5min per 100MB
                timeoutMs = (5 * 60 * 1000) + (sizeMB / 100) * (1.5 * 60 * 1000);
            }
            const timeoutMin = (timeoutMs / 60000).toFixed(1);
            console.log(`   ⏱️ Upload timeout: ${timeoutMin} min (file: ${sizeMB.toFixed(0)} MB)`);
            return timeoutMs;
        } catch (e) {
            return 15 * 60 * 1000; // Default 15 minutes
        }
    }

    /**
     * Send a message with timeout and retry logic
     * IMPORTANT: Timeouts are NOT retried — they indicate rate limiting.
     * Only non-timeout errors (network blips, etc.) are retried.
     */
    async sendMessageWithRetry(jid, content, filePath = null) {
        const timeoutMs=filePath ? this.getUploadTimeout(filePath) : 600000;
        let deadline;
        try {
            const result=await Promise.race([Promise.resolve().then(()=>this.sock.sendMessage(jid,content)),new Promise((_,reject)=>{deadline=setTimeout(()=>reject(Error('Upload acknowledgement unavailable')),timeoutMs);})]);
            if(!result?.key?.id)throw Error('Upload acknowledgement unavailable');
            this.consecutiveErrors=0;
            if(filePath && fs.existsSync(filePath))this.bytesUploadedThisSession+=fs.statSync(filePath).size;
            return result;
        } finally {clearTimeout(deadline);}
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
     * Set API client (called after initialization)
     */
    setApiClient(apiClient) {
        this.apiClient = apiClient;
    }

    /**
     * Load warming state from file
     */
    loadState() {
        try {
            if (fs.lstatSync(this.stateFile,{throwIfNoEntry:false})) {
                if (fs.lstatSync(this.stateFile).isSymbolicLink() || fs.statSync(this.stateFile).size>1048576) throw Error();
                const value = JSON.parse(fs.readFileSync(this.stateFile, 'utf8'));
                if (!value || !Array.isArray(value.remainingFiles) || value.remainingFiles.length>10000) throw Error();
                return value;
            }
        } catch (error) {
            throw Error('warming_state_unavailable');
        }
        return null;
    }

    /**
     * Save warming state to file
     */
    saveState(state) {
        const temporary=this.stateFile+'.'+require('crypto').randomBytes(12).toString('hex')+'.tmp';
        try {
            const raw=JSON.stringify(state);if(Buffer.byteLength(raw)>1048576)throw Error();
            if(fs.lstatSync(this.stateFile,{throwIfNoEntry:false})?.isSymbolicLink())throw Error();
            const fd=fs.openSync(temporary,'wx',0o600);
            try {fs.writeFileSync(fd,raw,'utf8');fs.fsyncSync(fd);}finally{fs.closeSync(fd);}
            fs.renameSync(temporary,this.stateFile);
            console.log(`💾 Saved warming state: ${state.remainingFiles?.length || 0} files remaining`);
        } catch (error) {
            throw Error('warming_state_unavailable');
        } finally {if(fs.existsSync(temporary))fs.unlinkSync(temporary);}
    }

    /**
     * Clear warming state file
     */
    clearState() {
        try {
            if (fs.lstatSync(this.stateFile,{throwIfNoEntry:false})) {
                if(!fs.lstatSync(this.stateFile).isFile()||fs.lstatSync(this.stateFile).isSymbolicLink())throw Error();
                fs.unlinkSync(this.stateFile);
                console.log(`🗑️ Cleared warming state file`);
            }
        } catch (error) {
            throw Error('warming_state_unavailable');
        }
    }

    /**
     * Check if current time is within warming window (12:00 AM - 5:00 AM)
     */
    isWithinWarmingWindow(now=new Date()) {
        const hour = require('./worker-clock').parts(now).hour;
        // 12:00 AM (0) to 5:00 AM (4) - hours 0-4 inclusive
        return hour >= 0 && hour < 5;
    }

    /**
     * Start the cache warmer - runs on startup and schedules automatic refresh
     */
    async start(warmOnStartup = true) {
        if (this.job || this.isRunning) return;
        this.stopRequested=false;
        // PROACTIVE DAILY REFRESH (3 AM) - Refresh messages older than 10 days
        // This prevents "Download failed" by refreshing BEFORE they expire (~14 days)
        this.proactiveJob = schedule.scheduleJob({rule:'0 3 * * *',tz:require('./worker-clock').timeZone()}, async () => {
            console.log(`\n🔄 PROACTIVE REFRESH (3 AM): Checking for aging messages...`);
            await this.refreshAgingMessages();
        });

        // WEEKLY FULL REFRESH (Sunday 2 AM) - Refresh everything
        this.job = schedule.scheduleJob({rule:'0 2 * * 0',tz:require('./worker-clock').timeZone()}, async () => {
            console.log(`\n🔄 WEEKLY CACHE REFRESH triggered (Sunday 2 AM)...`);
            await this.warmAllLocalFiles(true, true);
        });

        // MONTHLY FULL REFRESH on 1st of each month at 4 AM
        this.monthlyJob = schedule.scheduleJob({rule:'0 4 1 * *',tz:require('./worker-clock').timeZone()}, async () => {
            console.log(`\n📅 MONTHLY FULL CACHE REFRESH triggered (1st of month)...`);
            await this.warmAllLocalFiles(true, true);
        });

        console.log(`🔥 Cache Warmer ready:`);
        console.log(`   📅 Proactive refresh: Every day at 3:00 AM (aging messages only)`);
        console.log(`   📅 Weekly refresh: Every Sunday at 2:00 AM (full)`);
        console.log(`   📅 Monthly refresh: 1st of each month at 4:00 AM (full)`);
        console.log(`   📝 Manual commands: !warm, !warm movie X-Y`);
    }

    /**
     * Stop the scheduler
     */
    stop() {
        this.stopRequested=true;
        if (this.job) {
            this.job.cancel();
            this.job = null;
        }
        if (this.monthlyJob) {
            this.monthlyJob.cancel();
            this.monthlyJob = null;
        }
        if (this.proactiveJob) {
            this.proactiveJob.cancel();
            this.proactiveJob = null;
        }
    }

    /**
     * Refresh messages older than 10 days (before they expire at ~14 days)
     * This runs daily at 3 AM to proactively prevent "Download failed" errors
     */
    async refreshAgingMessages() {
        if(!this.apiClient || this.stopRequested || !this.socketReady || this.gate.running)return {refreshed:0};
        try {
            const response=await this.apiClient.getAgingMessages(10);
            if(!Array.isArray(response?.messages)||response.messages.length>10000)throw Error();
            const files=response.messages.map(msg=>({type:msg.content_type,contentId:msg.content_id,partNumber:msg.part_number,path:this.fileManager.getFilePath(msg.content_type,msg.content_id,msg.part_number)})).filter(file=>file.path);
            const result=await this.warmAllLocalFiles(true,true,files);
            return {refreshed:result.warmed||0,failed:result.errors||0,held:!!result.held};
        }catch{return {refreshed:0,failed:1,error:'refresh_unavailable'};}
    }

    /**
     * Warm ALL local files (used on startup and nightly)
     * @param {boolean} forceRefresh - If true, re-upload even if already in DB
     * @param {boolean} ignoreTimeWindow - If true, bypass time window check (for manual commands)
     */
    async warmAllLocalFiles(forceRefresh=false,ignoreTimeWindow=false,selectedFiles=null) {
        if(this.isRunning || this.gate.running)return {success:false,message:'Maintenance already running.'};
        this.isRunning=true;this.gate.running=true;this.stats.totalRuns++;this.stats.lastRunTime=new Date();
        let warmed=0,errors=0,skipped=0;
        try {
            const saved=this.loadState();
            if(saved?.held)return {success:false,held:true,message:'Warming checkpoint requires operator review.'};
            const files=saved?.remainingFiles?.length ? saved.remainingFiles : (selectedFiles || await this.fileManager.listAllFiles());
            if(!Array.isArray(files)||files.length>10000)throw Error('warming_state_unavailable');
            for(let i=0;i<files.length;i++) {
                if(this.stopRequested || !this.socketReady || (!ignoreTimeWindow && !this.isWithinWarmingWindow())) {
                    this.saveState({remainingFiles:files.slice(i),reason:'worker_paused'});
                    return {success:false,paused:true,warmed,errors,skipped,total:files.length};
                }
                const file=files[i];
                if(this.fileManager.getFilePath && this.fileManager.getFilePath(file.type,file.contentId,file.partNumber)!==file.path)throw Error('warming_state_unavailable');
                if(this.apiClient && !forceRefresh) {
                    const existing=await this.apiClient.getMessageId(file.type,file.contentId,file.partNumber);
                    if(existing && (file.partNumber ? existing.part_message_id : existing.message_id)) {
                        skipped++;this.saveState({remainingFiles:files.slice(i+1)});continue;
                    }
                }
                // A crash or lost acknowledgement leaves this item held, never auto-replayed.
                this.saveState({remainingFiles:files.slice(i),held:true,reason:'inflight_send'});
                try {
                    const id=await this.warmSingleFile(file.type,file.contentId,file.partNumber,file.path);
                    if(id===null)skipped++;else warmed++;
                } catch {
                    errors++;this.stats.errors++;
                    return {success:false,held:true,warmed,errors,skipped,total:files.length,message:'Unconfirmed maintenance send; review checkpoint.'};
                }
                this.saveState({remainingFiles:files.slice(i+1)});
                if(this.stopRequested)continue;
                if(i+1<files.length)await new Promise(resolve=>setTimeout(resolve,this.getSmartDelay(file.path)));
            }
            this.clearState();this.stats.videosWarmed+=warmed;
            return {success:true,warmed,errors,skipped,total:files.length};
        } catch {
            return {success:false,message:'warming_state_unavailable'};
        } finally {this.isRunning=false;this.gate.running=false;}
    }

    async warmIdRange(contentType,startId,endId,ignoreTimeWindow=true,progressChatId=null,forceRefresh=false) {
        if(!['movie','episode'].includes(contentType)||!Number.isSafeInteger(startId)||!Number.isSafeInteger(endId)||startId<1||endId<startId||endId-startId>=100)return {success:false,message:'Invalid bounded media range.'};
        const files=(await this.fileManager.listAllFiles()).filter(file=>file.type===contentType && file.contentId>=startId && file.contentId<=endId);
        const found=new Set(files.map(file=>file.contentId));
        const result=await this.warmAllLocalFiles(forceRefresh,ignoreTimeWindow,files);
        result.notFound=endId-startId+1-found.size;
        if(progressChatId && this.sock && !this.stopRequested) {
            try {await this.sock.sendMessage(progressChatId,{text:result.held ? 'Maintenance held for operator review.' : `Maintenance: ${result.warmed||0} warmed, ${result.errors||0} errors, ${result.notFound} unavailable.`});}catch{}
        }
        return result;
    }

    async warmSingleFile(contentType, contentId, partNumber, filePath) {
        if (!this.cacheGroupId) {
            console.log(`⚠️ No cache group ID configured, skipping warm for ${filePath}`);
            return null;
        }

        if(this.fileManager.getFilePath(contentType,contentId,partNumber)!==filePath)throw Error('Invalid media path');
        const fileInfo = this.fileManager.getFileInfo(contentType, contentId, partNumber);
        console.log(`🔥 Warming: ${filePath} (${fileInfo?.sizeFormatted || 'unknown size'})`);

        // Caption for identification
        const caption = partNumber
            ? `[CACHE] ${contentType}_${contentId}_part${partNumber}`
            : `[CACHE] ${contentType}_${contentId}`;

        const fileName = path.basename(filePath);

        // Track active upload globally so Tier 3 doesn't duplicate it
        const uploadKey = `${contentType}_${contentId}_${partNumber || 0}`;
        if (!global.activeUploads) global.activeUploads = new Set();
        if(global.activeUploads.has(uploadKey))throw Error('Media work already active');
        global.activeUploads.add(uploadKey);

        try {
            let sent;

            // ALL FILES: Upload as document (better for large files)
            console.log(`📄 Warming as DOCUMENT: ${fileName}`);
            sent = await this.sendMessageWithRetry(this.cacheGroupId, {
                document: { url: filePath },
                caption: caption,
                mimetype: 'video/mp4',
                fileName: fileName
            }, filePath);

            // CRITICAL: Manually add to store so Tier 1 & 2 can find it
            if (global.store && global.store.messages) {
                if (!global.store.messages[this.cacheGroupId]) {
                    global.store.messages[this.cacheGroupId] = [];
                }
                // Add the sent message to the store
                if (Array.isArray(global.store.messages[this.cacheGroupId])) {
                    global.store.messages[this.cacheGroupId].push(sent);
                } else {
                    global.store.messages[this.cacheGroupId][sent.key.id] = sent;
                }
                console.log(`📦 Added message to store for Tier 1/2 cache`);
            }

            // CRITICAL: Save to PERSISTENT cache (survives restart!)
            if (global.multiTierForwarder && global.multiTierForwarder.addToMessageCache) {
                global.multiTierForwarder.addToMessageCache(sent.key.id, sent);
                console.log(`💾 Saved to persistent cache: ${sent.key.id}`);
            }

            // Save new message ID to database
            const messageId = sent.key.id;
            await this.updateMessageId(contentType, contentId, partNumber, messageId);

            return messageId;
        } catch (error) {
            console.error(`   ❌ Upload failed: ${error.message}`);
            throw error;
        } finally {
            // Always clear active upload tracking
            if (global.activeUploads) global.activeUploads.delete(uploadKey);
        }
    }

    /**
     * Update message ID in database
     */
    async updateMessageId(contentType,contentId,partNumber,messageId) {
        if(!this.apiClient)throw Error('Cache mapping acknowledgement unavailable');
        const response=await this.apiClient.addMessageId(contentType,contentId,messageId,null,partNumber||0);
        if(response?.success!==true)throw Error('Cache mapping acknowledgement unavailable');
    }

    /**
     * Get warming statistics
     */
    getStats() {
        return {
            ...this.stats,
            isRunning: this.isRunning,
            nextRun: this.job ? this.job.nextInvocation() : null
        };
    }
}

module.exports = CacheWarmer;

/**
 * Multi-Tier Forwarder
 * Implements 4-tier forwarding strategy with AUTO-RECOVERY
 * NOW USING BAILEYS for reliable large file streaming (200MB+)
 */

const fs = require('fs');
const path = require('path');
const UploadQueue = require('./upload-queue');
const {inspectMedia} = require('./media-policy');

class MultiTierForwarder {
    constructor(client, fileManager, apiClient, cacheGroupId = null, storageGroupIds = []) {
        this.client = client;
        this.fileManager = fileManager;
        this.apiClient = apiClient;
        this.cacheGroupId = cacheGroupId;
        this.storageGroupIds = storageGroupIds.length > 0 ? storageGroupIds : (cacheGroupId ? [cacheGroupId] : []);
        this.store = null;

        // Reconnection waiting support (set by index.js on socket update)
        this._reconnectResolve = null;
        this.socketReady = true;

        // Queue persistence file
        this.queueFile = path.join(require('./environment').readConfig().sessionPath, 'pending-requests.json');

        // Persistent message cache file (survives restart!)
        this.messageCacheFile = path.join(require('./environment').readConfig().sessionPath, 'message-cache.json');
        this.messageCache = new Map(); // key: messageId, value: full message object

        // Active operations tracking (for request coalescing)
        this.activeOperations = new Map();

        // Pending cold requests (queued for night)
        this.pendingColdRequests = new Map();

        // Load existing queue and message cache
        this.loadQueue();
        this.loadMessageCache();

        // UPLOAD QUEUE: Rate-limited concurrent uploads
        this.uploadQueue = new UploadQueue({
            maxConcurrent: 1,           // One local large-file upload at a time
            delayBetweenUploads: 5000,  // 5 second gap between uploads
            maxQueueSize: 30            // Bounded personal-server backlog
        });

        // Set the upload handler
        this.uploadQueue.setUploadHandler(async (task) => {
            return await this.performTier3Upload(task.chatId, task.contentType, task.contentId, task.partNumber);
        });

        // Statistics
        this.stats = {
            tier1Hits: 0,
            tier2Hits: 0,
            tier3Hits: 0,
            tier4Queued: 0,
            totalRequests: 0
        };
    }

    setStore(store) {
        this.store = store;
    }

    /**
     * Called by index.js after socket reconnects — mirrors AutoRefresher pattern
     */
    updateSocket(newClient) {
        this.client = newClient;
        this.socketReady = true;
        console.log(`🔌 MultiTierForwarder: Socket updated after reconnection`);
        if (this._reconnectResolve) {
            this._reconnectResolve();
            this._reconnectResolve = null;
        }
    }

    /**
     * Wait up to maxWaitMs for the socket to reconnect (max 90s)
     */
    async waitForReconnection(maxWaitMs = 90000) {
        if (this.socketReady) return true;
        console.log(`⏳ MultiTierForwarder: Waiting up to ${maxWaitMs / 1000}s for socket reconnect...`);
        return new Promise(resolve => {
            const timeout = setTimeout(() => {
                this._reconnectResolve = null;
                console.log(`❌ MultiTierForwarder: Reconnect timed out`);
                resolve(false);
            }, maxWaitMs);
            this._reconnectResolve = () => {
                clearTimeout(timeout);
                console.log(`✅ MultiTierForwarder: Socket reconnected! Retrying forward...`);
                resolve(true);
            };
        });
    }

    // Generate the video caption with instructions
    getVideoCaption() {
        return '';
    }

    // Load message cache from disk
    loadMessageCache() {
        try {
            if (fs.existsSync(this.messageCacheFile)) {
                const data = fs.readFileSync(this.messageCacheFile, 'utf8');
                const parsed = JSON.parse(data);
                for (const [key, value] of Object.entries(parsed)) {
                    this.messageCache.set(key, value);
                }
                console.log(`📦 Loaded ${this.messageCache.size} cached messages from disk`);
            }
        } catch (err) {
            console.error(`❌ Failed to load message cache: ${err.message}`);
        }
    }

    // Save message cache to disk
    saveMessageCache() {
        try {
            const dir = path.dirname(this.messageCacheFile);
            if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
            const data = {};
            for (const [key, value] of this.messageCache.entries()) {
                data[key] = value;
            }
            fs.writeFileSync(this.messageCacheFile, JSON.stringify(data));
            console.log(`💾 Saved ${this.messageCache.size} messages to cache`);
        } catch (err) {
            console.error(`❌ Failed to save message cache: ${err.message}`);
        }
    }

    // Add message to persistent cache
    addToMessageCache(messageId, messageObj) {
        this.messageCache.set(messageId, messageObj);
        // Save immediately after each add to ensure persistence
        this.saveMessageCache();
    }

    loadQueue() {
        try {
            if (fs.existsSync(this.queueFile)) {
                const data = fs.readFileSync(this.queueFile, 'utf8');
                const parsed = JSON.parse(data);
                for (const [key, value] of Object.entries(parsed)) {
                    this.pendingColdRequests.set(key, value);
                }
                console.log(`📋 Loaded ${this.pendingColdRequests.size} queued videos from disk`);
            }
        } catch (err) {
            console.error(`❌ Failed to load queue: ${err.message}`);
        }
    }

    saveQueue() {throw Error('legacy_queue_disabled');}

    isNightTime(now=new Date()) {
        const {hour,minute}=require('./worker-clock').parts(now);
        if (hour === 0 && minute >= 30) return true;
        if (hour >= 1 && hour < 6) return true;
        return false;
    }

    async forwardVideo(chatId, tokenData) {
        const key = `${tokenData.content_type}_${tokenData.content_id}_${tokenData.part_number || 0}`;
        this.stats.totalRequests++;
        const active = this.activeOperations.get(key);
        if (active) {
            if (active.ownerChatId === chatId) return active.promise;
            return this.waitForActiveOperation(key, chatId);
        }
        const operation = {ownerChatId: chatId, waiters: [], promise: null};
        this.activeOperations.set(key, operation);
        operation.promise = Promise.resolve().then(async () => {
            try {
                return await this.deliverVideo(chatId, tokenData, key);
            } catch (error) {
                return {success: false, message: 'Delivery failed.'};
            } finally {
                // A cache miss, upload error or unexpected exception must settle
                // every waiting request instead of leaving token handlers hanging.
                for (const waiter of operation.waiters) {
                    if (!waiter.settled) {
                        waiter.settled = true;
                        waiter.resolve({success: false, message: 'Delivery failed.'});
                    }
                }
                this.activeOperations.delete(key);
            }
        });
        return operation.promise;
    }

    async deliverVideo(chatId, tokenData, operationKey) {
        const {content_type, content_id, message_id, part_number} = tokenData;
        if (message_id) {
            const result = await this.tryTier1Forward(chatId, message_id);
            if(result.uncertain)return {success:false,uncertain:true,message:'Delivery acknowledgement unavailable; claim held.'};
            if (result.success) {
                this.stats.tier1Hits++;
                await this.broadcastToWaiters(operationKey, result.sentMessage);
                return {success: true, tier: 1, message: 'Forwarded via Hot ID'};
            }
        }
        const cached = await this.tryTier2Forward(chatId, content_type, content_id, part_number, message_id);
        if(cached.uncertain)return {success:false,uncertain:true,message:'Delivery acknowledgement unavailable; claim held.'};
        if (cached.success) {
            this.stats.tier2Hits++;
            if (cached.messageId) await this.updateMessageId(content_type, content_id, part_number, cached.messageId);
            await this.broadcastToWaiters(operationKey, cached.sentMessage);
            return {success: true, tier: 2, message: 'Forwarded from Cache Group'};
        }
        if (this.fileManager.hasLocalFile(content_type, content_id, part_number)) {
            const uploaded = await this.tryTier3Upload(chatId, content_type, content_id, part_number, 100);
            if (uploaded.success && uploaded.sentMessage?.key?.id) {
                this.stats.tier3Hits++;
                await this.broadcastToWaiters(operationKey, uploaded.sentMessage);
                return {success: true, tier: 3, message: 'Uploaded from Disk'};
            }
            return {success: false, message: 'Delivery was not acknowledged. Please retry later.'};
        }
        return {success: false, tier: 4, message: 'Video not available - no local file found.'};
    }

    async sendAcknowledged(chatId, payload) {
        try {
            const sent = await this.client.sendMessage(chatId, payload);
            if (!sent || typeof sent.key?.id !== 'string' || !sent.key.id.trim())throw Error();
            return sent;
        }catch{const error=Error('Delivery acknowledgement unavailable');error.deliveryUncertain=true;throw error;}
    }

    async tryTier1Forward(chatId, messageId) {
        try {
            console.log(`🔍 TIER 1: Checking for messageId=${messageId}`);

            // FIRST: Check persistent message cache (survives restart!)
            if (this.messageCache.has(messageId)) {
                console.log(`✅ TIER 1: Found in persistent cache!`);
                const cachedMsg = this.messageCache.get(messageId);

                try {
                    // Try to forward the cached message
                    const sent = await this.sendAcknowledged(chatId, {
                        forward: cachedMsg,
                    });
                    return { success: true, messageId: sent.key.id, sentMessage: sent };
                } catch (forwardError) {
                    if(forwardError.deliveryUncertain)throw forwardError;
                    // Check if this is a stale/expired message error
                    const errorMsg = forwardError.message?.toLowerCase() || '';
                    const isConnectionError = errorMsg.includes('connection') || errorMsg.includes('closed') ||
                        errorMsg.includes('stream') || errorMsg.includes('timed out') || errorMsg.includes('timeout');
                    const isMediaError = errorMsg.includes('media') || errorMsg.includes('expired') ||
                        errorMsg.includes('not found') || errorMsg.includes('unavailable') || errorMsg.includes('download');

                    if (isConnectionError) {
                        // Socket is in a bad state — mark not ready and wait for reconnect
                        console.log(`⚠️ TIER 1: Connection error (${forwardError.message}), waiting for socket reconnect...`);
                        this.socketReady = false;
                        const reconnected = await this.waitForReconnection(90000);
                        if (reconnected) {
                            // Retry forward once with the fresh socket
                            try {
                                console.log(`🔄 TIER 1: Retrying forward after reconnect...`);
                                const sent = await this.sendAcknowledged(chatId, { forward: cachedMsg });
                                return { success: true, messageId: sent.key.id, sentMessage: sent };
                            } catch (retryErr) {
                                console.log(`❌ TIER 1: Retry after reconnect also failed: ${retryErr.message}`);
                                // Fall through to media error handling below
                            }
                        }
                        // Reconnect timed out or retry failed — remove stale entry
                        console.log(`⚠️ TIER 1: Removing stale entry from persistent cache: ${messageId}`);
                        this.messageCache.delete(messageId);
                        this.saveMessageCache();
                        return { success: false, error: `Connection error: ${forwardError.message}` };
                    }

                    if (isMediaError) {
                        console.log(`⚠️ TIER 1: Media expired, removing from cache: ${messageId}`);
                        this.messageCache.delete(messageId);
                        this.saveMessageCache();
                        return { success: false, error: 'Media expired' };
                    }

                    throw forwardError; // Re-throw truly unexpected errors
                }
            }

            // SECOND: Check in-memory store
            if (!this.store || !this.store.messages) {
                console.log(`❌ TIER 1: No store available, not in persistent cache`);
                return { success: false, error: 'No store' };
            }

            // Get messages from cache group
            const cacheMessages = this.store.messages[this.cacheGroupId];
            if (!cacheMessages) {
                console.log(`❌ TIER 1: No messages for cache group in store`);
                return { success: false, error: 'No messages in cache group' };
            }

            // Search for message by ID
            let msg = null;
            if (Array.isArray(cacheMessages)) {
                msg = cacheMessages.find(m => m.key && m.key.id === messageId);
            } else if (typeof cacheMessages === 'object') {
                msg = cacheMessages[messageId] || Object.values(cacheMessages).find(m => m.key && m.key.id === messageId);
            }

            if (!msg) {
                console.log(`❌ TIER 1: Message ${messageId} not found`);
                return { success: false, error: 'Not in store' };
            }

            console.log(`✅ TIER 1: Found message in store, forwarding...`);

            // Save to persistent cache for future restarts
            this.addToMessageCache(messageId, msg);

            const sent = await this.sendAcknowledged(chatId, { forward: msg });
            return { success: true, messageId: sent.key.id, sentMessage: sent };
        } catch (e) {
            console.log(`❌ TIER 1: Error - ${e.message}`);
            return { success: false, uncertain:!!e.deliveryUncertain,error:'Cached delivery unavailable' };
        }
    }

    async tryTier2Forward(chatId, contentType, contentId, partNumber, messageId) {
        try {
            console.log(`🔍 TIER 2: Attempting forward for ${contentType}_${contentId}${partNumber ? '_part' + partNumber : ''}`);

            if (!this.cacheGroupId) {
                console.log(`❌ TIER 2: No cache group configured`);
                return { success: false, error: 'No cache group' };
            }

            // Method 1: Try direct forward if we have message ID
            if (messageId) {
                try {
                    console.log(`🔍 TIER 2: Trying direct forward with messageId=${messageId}`);

                    // Search all available storage group IDs, not just cacheGroupId
                    const groupsToSearch = this.storageGroupIds && this.storageGroupIds.length > 0
                        ? this.storageGroupIds
                        : [this.cacheGroupId];

                    let targetMsg = null;
                    for (const groupId of groupsToSearch) {
                        const messages = await this.client.fetchMessagesFromChat(groupId, 100);
                        console.log(`🔍 TIER 2: Fetched ${messages.length} messages from ${groupId}`);
                        targetMsg = messages.find(m => m.key.id === messageId);
                        if (targetMsg) break;
                    }

                    if (targetMsg) {
                        console.log(`✅ TIER 2: Found message by ID, forwarding...`);
                        // Save to persistent cache
                        this.addToMessageCache(messageId, targetMsg);
                        try {
                            const sent = await this.sendAcknowledged(chatId, { forward: targetMsg });
                            return { success: true, messageId: targetMsg.key.id, sentMessage: targetMsg };
                        } catch (fwdErr) {
                            if(fwdErr.deliveryUncertain)throw fwdErr;
                            const fwdErrMsg = fwdErr.message?.toLowerCase() || '';
                            if (fwdErrMsg.includes('connection') || fwdErrMsg.includes('closed') ||
                                fwdErrMsg.includes('stream') || fwdErrMsg.includes('timeout')) {
                                console.log(`⚠️ TIER 2: Connection error on forward, waiting for reconnect...`);
                                this.socketReady = false;
                                const reconnected = await this.waitForReconnection(90000);
                                if (reconnected) {
                                    try {
                                        const sent = await this.sendAcknowledged(chatId, { forward: targetMsg });
                                        return { success: true, messageId: sent.key.id, sentMessage: targetMsg };
                                    } catch (retryErr) {
                                        console.log(`❌ TIER 2: Retry after reconnect failed: ${retryErr.message}`);
                                    }
                                }
                            }
                            throw fwdErr;
                        }
                    }
                } catch (e) {
                    if(e.deliveryUncertain)throw e;
                    console.log(`⚠️ TIER 2: Direct forward failed: ${e.message}`);
                }
            }

            // Method 2: Search by caption in store
            const messages = await this.client.fetchMessagesFromChat(this.cacheGroupId, 100);

            if (messages.length === 0) {
                console.log(`❌ TIER 2: No messages in cache group store (bot restarted?)`);

                // Fallback: Skip to Tier 3 instead of failing
                console.log(`💡 TIER 2: Skipping to Tier 3 (local disk)`);
                return { success: false, error: 'Store empty after restart' };
            }

            const searchString = partNumber ? `${contentType}_${contentId}_part${partNumber}` : `${contentType}_${contentId}`;
            console.log(`🔍 TIER 2: Searching by caption for "${searchString}"`);

            const targetMsg = messages.find(m => {
                const caption = m.message?.videoMessage?.caption ||
                    m.message?.extendedTextMessage?.text ||
                    m.message?.documentMessage?.caption || "";
                return caption.includes(searchString);
            });

            if (!targetMsg) {
                console.log(`❌ TIER 2: Message not found in cache group`);
                return { success: false, error: 'Not found in group' };
            }

            console.log(`✅ TIER 2: Found message, forwarding...`);
            // Save to persistent cache
            this.addToMessageCache(targetMsg.key.id, targetMsg);
            const sent = await this.sendAcknowledged(chatId, { forward: targetMsg });
            return { success: true, messageId: targetMsg.key.id, sentMessage: targetMsg };
        } catch (e) {
            console.log(`❌ TIER 2: Error - ${e.message}`);
            return { success: false, uncertain:!!e.deliveryUncertain,error:'Group delivery unavailable' };
        }
    }

    /**
     * Queue a Tier 3 upload (rate-limited)
     * This goes through the upload queue to prevent flooding
     */
    async tryTier3Upload(chatId, contentType, contentId, partNumber, priority = 0) {
        // Add to upload queue with priority
        try {
            const result = await this.uploadQueue.enqueue({
                chatId,
                contentType,
                contentId,
                partNumber,
                priority
            });
            return result;
        } catch (e) {
            console.log(`❌ TIER 3: Queue error - ${e.message}`);
            return { success: false, error: e.message };
        }
    }

    /**
     * Actually perform the Tier 3 upload (called by queue)
     */
    async performTier3Upload(chatId, contentType, contentId, partNumber) {
        try {
            const uploadKey = `${contentType}_${contentId}_${partNumber || 0}`;

            // Check if cache warmer is already uploading this file
            if (global.activeUploads && global.activeUploads.has(uploadKey)) {
                console.log(`⏳ TIER 3: Skipping ${uploadKey} — cache warmer is already uploading it`);
                // Wait for the warm upload to finish (check every 10s, max 50 min)
                const maxWait = 50 * 60 * 1000;
                const start = Date.now();
                while (global.activeUploads.has(uploadKey) && (Date.now() - start) < maxWait) {
                    await new Promise(r => setTimeout(r, 10000));
                }
                // After warm finishes, the message should be in cache — return success
                if (!global.activeUploads.has(uploadKey)) {
                    console.log(`✅ TIER 3: Warm upload finished for ${uploadKey}, skipping duplicate upload`);
                    // A warm cache is not delivery to this recipient; continue below.
                }
            }

            const filePath = this.fileManager.getFilePath(contentType, contentId, partNumber);
            if (!filePath) return { success: false, error: 'No local file' };
            const media = inspectMedia(filePath);

            const caption = this.getVideoCaption();
            const fileName = path.basename(filePath);

            // Calculate timeout based on file size (tiered for large files)
            let timeoutMs = 15 * 60 * 1000; // default 15 min
            try {
                const sizeMB = media.bytes / 1024 / 1024;
                if (sizeMB > 1024) {
                    // Large files: 10min base + 2min per 100MB
                    timeoutMs = (10 * 60 * 1000) + (sizeMB / 100) * (2 * 60 * 1000);
                } else {
                    // Normal files: 5min base + 1.5min per 100MB
                    timeoutMs = (5 * 60 * 1000) + (sizeMB / 100) * (1.5 * 60 * 1000);
                }
                console.log(`   ⏱️ Upload timeout: ${(timeoutMs / 60000).toFixed(1)} min (file: ${sizeMB.toFixed(0)} MB)`);
            } catch (e) { }

            let sent;

            // ALL FILES: Send as document (better for large files)
            console.log(`📄 Sending as DOCUMENT: ${fileName} to ${chatId}`);
            let uploadTimer;
            try {
            sent = await Promise.race([
                this.sendAcknowledged(chatId, {
                    document: { url: filePath },
                    caption: caption,
                    mimetype: media.mimetype,
                    fileName: fileName
                }),
                new Promise((_, reject) =>
                    uploadTimer = setTimeout(() => reject(new Error('Upload timed out')), timeoutMs)
                )
            ]);
            } finally { clearTimeout(uploadTimer); }

            // Validate sent object
            console.log(`📬 TIER 3: sendMessage returned: key=${sent?.key?.id}, remoteJid=${sent?.key?.remoteJid}`);

            if (!sent || !sent.key || !sent.key.id) {
                console.log(`❌ TIER 3: Upload returned invalid sent object, video may still have been delivered`);
                return { success: false, error: 'Delivery acknowledgement missing' };
            }

            // Save to persistent cache for future restarts
            this.addToMessageCache(sent.key.id, sent);

            // Update database with new message ID
            await this.updateMessageId(contentType, contentId, partNumber, sent.key.id);

            console.log(`✅ TIER 3: Upload complete! New messageId=${sent.key.id} saved to DB`);
            return { success: true, messageId: sent.key.id, sentMessage: sent };
        } catch (e) {
            console.log(`❌ TIER 3: performTier3Upload error: ${e.message}`);
            return { success: false, error: e.message };
        }
    }

    async processPendingRequests() {
        // Legacy files lack current claim ownership. Preserve bytes; never replay recipients.
        return {processed:0,failed:0,held:this.pendingColdRequests.size,reason:'legacy_queue_review_required'};
    }

    queueForNight() {throw Error('legacy_queue_disabled');}

    async broadcastToWaiters(operationKey, sentMessage) {
        const op = this.activeOperations.get(operationKey);
        if (!op) return;
        for (const waiter of op.waiters) {
            if (waiter.settled) continue;
            try {
                if (!sentMessage?.key?.id) throw new Error('No acknowledged source message');
                await this.sendAcknowledged(waiter.chatId, {forward: sentMessage});
                waiter.resolve({success: true, tier: 'coalesced'});
            } catch (error) {
                waiter.resolve({success: false, message: 'Delivery failed.'});
            }
            waiter.settled = true;
        }
    }

    async waitForActiveOperation(operationKey, chatId) {
        const op = this.activeOperations.get(operationKey);
        if (!op) return {success: false, message: 'Operation no longer available.'};
        const existing = op.waiters.find(waiter => waiter.chatId === chatId);
        if (existing) return existing.promise;
        let resolve;
        const promise = new Promise(done => {resolve = done;});
        op.waiters.push({chatId, resolve, promise, settled: false});
        return promise;
    }

    async updateMessageId(contentType, contentId, partNumber, messageId) {
        try {
            await this.apiClient.addMessageId(contentType, contentId, messageId, null, partNumber || 0);
        } catch (e) {
            console.error(`❌ DB error: ${e.message}`);
        }
    }

    getStats() {
        const total = this.stats.tier1Hits + this.stats.tier2Hits + this.stats.tier3Hits + this.stats.tier4Queued;
        return {
            ...this.stats,
            isNightTime: this.isNightTime(),
            tier1Rate: total > 0 ? ((this.stats.tier1Hits / total) * 100).toFixed(1) + '%' : '0%',
            tier2Rate: total > 0 ? ((this.stats.tier2Hits / total) * 100).toFixed(1) + '%' : '0%',
            tier3Rate: total > 0 ? ((this.stats.tier3Hits / total) * 100).toFixed(1) + '%' : '0%'
        };
    }
}

module.exports = MultiTierForwarder;

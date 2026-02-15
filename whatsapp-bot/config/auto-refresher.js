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
        this.maxRetries = 2;
        this.consecutiveErrors = 0;
        this.maxConsecutiveErrors = 2;

        // Bandwidth tracking
        this.bytesUploadedThisSession = 0;
        this.bandwidthLimitBytes = 3.5 * 1024 * 1024 * 1024;
        this.batchCooldownMs = 30 * 60 * 1000;
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
    async sendMessageWithRetry(jid, content, filePath = null) {
        const timeoutMs = filePath ? this.getUploadTimeout(filePath) : 10 * 60 * 1000;
        const fileSize = filePath ? (fs.existsSync(filePath) ? fs.statSync(filePath).size : 0) : 0;

        // Check bandwidth limit BEFORE attempting upload
        if (fileSize > 0 && this.bytesUploadedThisSession + fileSize > this.bandwidthLimitBytes) {
            const uploadedGB = (this.bytesUploadedThisSession / 1024 / 1024 / 1024).toFixed(1);
            const cooldownMin = Math.round(this.batchCooldownMs / 60000);
            console.log(`\n🛑 BANDWIDTH LIMIT: Uploaded ${uploadedGB} GB this session.`);
            console.log(`   💤 Taking ${cooldownMin} min cooldown to avoid WhatsApp rate limiting...`);
            await new Promise(r => setTimeout(r, this.batchCooldownMs));
            this.bytesUploadedThisSession = 0;
            console.log(`   ✅ Cooldown complete! Resuming uploads...`);
        }

        for (let attempt = 1; attempt <= this.maxRetries; attempt++) {
            try {
                const result = await Promise.race([
                    this.sock.sendMessage(jid, content),
                    new Promise((_, reject) =>
                        setTimeout(() => reject(new Error('Upload timed out')), timeoutMs)
                    )
                ]);
                this.consecutiveErrors = 0;
                if (fileSize > 0) {
                    this.bytesUploadedThisSession += fileSize;
                    const totalMB = (this.bytesUploadedThisSession / 1024 / 1024).toFixed(0);
                    console.log(`   📊 Session bandwidth: ${totalMB} MB uploaded so far`);
                }
                return result;
            } catch (error) {
                console.error(`   ⚠️ Attempt ${attempt}/${this.maxRetries} failed: ${error.message}`);

                if (this.isConnectionError(error)) {
                    throw error;
                }

                if (error.message === 'Upload timed out') {
                    console.log(`   🚫 Upload timed out — likely rate limited. NOT retrying.`);
                    throw error;
                }

                if (attempt < this.maxRetries) {
                    const backoff = 30000 * Math.pow(2, attempt - 1);
                    console.log(`   🔄 Retrying in ${backoff / 1000}s...`);
                    await new Promise(r => setTimeout(r, backoff));
                } else {
                    throw error;
                }
            }
        }
    }

    /**
     * Start the auto refresher schedule
     */
    start() {
        if (!this.cacheGroupId || this.storageGroupIds.length === 0) {
            console.log('⚠️ AutoRefresher: Cache group or Storage groups missing. Disabled.');
            return;
        }

        // Schedule to run at 1:00 AM every day (staggered with Cache Warmer at 12:30 AM)
        this.job = schedule.scheduleJob('0 1 * * *', async () => {
            console.log('\n🔄 Scheduled Auto-Refresh cycle starting...');
            await this.runRefreshCycle();
        });

        console.log(`🔄 AutoRefresher: Scheduled for 1:00 AM daily (Interval: ${this.refreshIntervalHours}h)`);
    }

    /**
     * Run the full refresh cycle across all storage groups
     */
    async runRefreshCycle(manualNotifyJid = null) {
        if (this.isRefreshing) {
            if (manualNotifyJid) {
                await this.sock.sendMessage(manualNotifyJid, { text: '⚠️ Refresh already in progress!' });
            }
            return;
        }

        this.isRefreshing = true;
        const startTime = Date.now();
        console.log('🔄 AutoRefresher: Starting refresh cycle...');

        if (manualNotifyJid) {
            await this.sock.sendMessage(manualNotifyJid, { text: '🔄 Starting cache refresh cycle...' });
        }

        let totalProcessed = 0;
        let totalRefreshed = 0;
        let totalSkipped = 0;
        let totalFailed = 0;

        try {
            for (const storageId of this.storageGroupIds) {
                console.log(`\n📂 Processing Storage Group: ${storageId}`);

                // Fetch messages from this group
                // Use the helper we'll add to the socket
                const messages = await this.sock.fetchMessagesFromChat(storageId, 100);

                // Filter for media messages
                const mediaMessages = messages.filter(m => {
                    return m.message?.videoMessage || m.message?.documentMessage || m.message?.imageMessage;
                });

                console.log(`📦 Found ${mediaMessages.length} media messages to check.`);
                totalProcessed += mediaMessages.length;

                for (const msg of mediaMessages) {
                    try {
                        const messageId = msg.key.id;
                        const fileName = msg.message?.videoMessage?.caption ||
                            msg.message?.documentMessage?.fileName ||
                            msg.message?.imageMessage?.caption ||
                            'unnamed_file';

                        // Check if it needs refresh
                        const shouldRefresh = await this.apiClient.needsRefresh(messageId);

                        if (!shouldRefresh) {
                            totalSkipped++;
                            continue;
                        }

                        // Forward to cache group
                        console.log(`   🔄 Refreshing: ${fileName}`);
                        await this.sock.sendMessage(this.cacheGroupId, {
                            forward: msg
                        });

                        // Log refresh
                        await this.apiClient.logRefresh(messageId, fileName, 'video');
                        totalRefreshed++;

                        // Delay between forwards to avoid rate limit
                        await new Promise(r => setTimeout(r, 5000));

                    } catch (err) {
                        console.error(`   ❌ Failed to refresh message: ${err.message}`);
                        totalFailed++;
                    }
                }
            }

            const duration = ((Date.now() - startTime) / 1000 / 60).toFixed(1);
            const report = `✅ *Auto-Refresh Complete*\n\n` +
                `📦 Processed: ${totalProcessed}\n` +
                `🔄 Refreshed: ${totalRefreshed}\n` +
                `⏭️ Skipped: ${totalSkipped}\n` +
                `❌ Failed: ${totalFailed}\n` +
                `⏱️ Duration: ${duration} min`;

            console.log(`\n${report.replace(/\*/g, '')}`);

            if (manualNotifyJid) {
                await this.sock.sendMessage(manualNotifyJid, { text: report });
            }

        } catch (error) {
            console.error('❌ AutoRefresher Error:', error.message);
        } finally {
            this.isRefreshing = false;
        }
    }

    stop() {
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
    async refreshIdRange(contentType, startId, endId, notifyJid = null) {
        if (this.isRefreshing) {
            if (notifyJid) {
                await this.sock.sendMessage(notifyJid, { text: '⚠️ Refresh already in progress!' });
            }
            return { success: false, message: 'Already running' };
        }

        this.isRefreshing = true;
        const startTime = Date.now();
        console.log(`\n🔄 Refreshing ${contentType}s from ID ${startId} to ${endId}...`);

        let refreshed = 0;
        let notFound = 0;
        let errors = 0;

        try {
            // Get FileManager from global
            const fileManager = global.fileManager;
            if (!fileManager) {
                const errMsg = '❌ FileManager not initialized';
                console.error(errMsg);
                if (notifyJid) await this.sock.sendMessage(notifyJid, { text: errMsg });
                return { success: false, message: errMsg };
            }

            const totalRange = endId - startId + 1;
            let processed = 0;
            this.lastProgressUpdate = 0;

            for (let id = startId; id <= endId; id++) {
                processed++;

                // Show progress in console
                this.logProgress(processed, totalRange, `${contentType} ${id}`);

                // Check for full file
                if (fileManager.hasLocalFile(contentType, id, null)) {
                    try {
                        const filePath = fileManager.getFilePath(contentType, id, null);
                        await this.refreshSingleFile(contentType, id, null, filePath);
                        refreshed++;
                        this.consecutiveErrors = 0;
                        console.log(`✅ Refreshed: ${contentType} ${id}`);
                        const delayMs = this.getSmartDelay(filePath);
                        console.log(`   ⏳ Waiting ${Math.round(delayMs / 1000)}s before next upload...`);
                        await new Promise(r => setTimeout(r, delayMs));
                    } catch (e) {
                        errors++;
                        this.consecutiveErrors++;
                        console.log(`❌ Failed: ${contentType} ${id}: ${e.message}`);
                        if (this.isConnectionError(e)) {
                            console.log(`⚠️ Connection dropped! Stopping refresh at ID ${id}.`);
                            break;
                        }
                        if (this.consecutiveErrors >= this.maxConsecutiveErrors) {
                            console.log(`🚨 ${this.maxConsecutiveErrors} consecutive errors! Likely rate limited. Stopping.`);
                            break;
                        }
                        const cooldown = e.message === 'Upload timed out' ? 600000 : 30000;
                        console.log(`   ⏳ Cooldown: waiting ${cooldown / 1000}s before next attempt...`);
                        await new Promise(r => setTimeout(r, cooldown));
                    }
                } else {
                    // Check for parts (1-20)
                    let hasAnyPart = false;
                    let partBroken = false;
                    for (let part = 1; part <= 20; part++) {
                        if (fileManager.hasLocalFile(contentType, id, part)) {
                            hasAnyPart = true;
                            try {
                                const filePath = fileManager.getFilePath(contentType, id, part);
                                await this.refreshSingleFile(contentType, id, part, filePath);
                                refreshed++;
                                this.consecutiveErrors = 0;
                                console.log(`✅ Refreshed: ${contentType} ${id} Part ${part}`);
                                const delayMs = this.getSmartDelay(filePath);
                                console.log(`   ⏳ Waiting ${Math.round(delayMs / 1000)}s before next upload...`);
                                await new Promise(r => setTimeout(r, delayMs));
                            } catch (e) {
                                errors++;
                                this.consecutiveErrors++;
                                console.log(`❌ Failed: ${contentType} ${id} Part ${part}: ${e.message}`);
                                if (this.isConnectionError(e) || this.consecutiveErrors >= this.maxConsecutiveErrors) {
                                    partBroken = true;
                                    break;
                                }
                                const cooldown = e.message === 'Upload timed out' ? 600000 : 30000;
                                await new Promise(r => setTimeout(r, cooldown));
                            }
                        }
                    }
                    if (partBroken) break;

                    if (!hasAnyPart) {
                        notFound++;
                    }
                }

                // Send WhatsApp progress update
                await this.sendProgressUpdate(notifyJid, processed, totalRange, refreshed, errors);
            }

            const duration = ((Date.now() - startTime) / 1000 / 60).toFixed(1);
            const progressBar = this.generateProgressBar(processed, totalRange);
            const report = `✅ *Refresh Complete*\n\n` +
                `📋 Range: ${contentType} ${startId}-${endId}\n${progressBar}\n\n` +
                `🔄 Refreshed: ${refreshed}\n` +
                `🔍 Not Found: ${notFound}\n` +
                `❌ Errors: ${errors}\n` +
                `⏱️ Duration: ${duration} min`;

            console.log(`\n${report.replace(/\*/g, '')}`);

            if (notifyJid) {
                await this.sock.sendMessage(notifyJid, { text: report });
            }

            return { success: true, refreshed, notFound, errors, range: `${startId}-${endId}` };

        } catch (error) {
            console.error('❌ Refresh Range Error:', error.message);
            if (notifyJid) {
                await this.sock.sendMessage(notifyJid, { text: `❌ Refresh failed: ${error.message}` });
            }
            return { success: false, message: error.message };
        } finally {
            this.isRefreshing = false;
        }
    }

    /**
     * Refresh a single file by uploading from local disk to cache group
     * HYBRID: Parts → video, Full file → document
     * @param {string} contentType - 'movie' or 'episode'
     * @param {number} contentId - Content ID
     * @param {number|null} partNumber - Part number or null for full file
     * @param {string} filePath - Path to the local file
     */
    async refreshSingleFile(contentType, contentId, partNumber, filePath) {
        const fs = require('fs');
        const path = require('path');

        if (!fs.existsSync(filePath)) {
            throw new Error(`File not found: ${filePath}`);
        }

        const stats = fs.statSync(filePath);
        const fileName = path.basename(filePath);
        // Use searchable format: movie_184_part11 (matches what forwarder searches for)
        const caption = partNumber
            ? `${contentType}_${contentId}_part${partNumber}`
            : `${contentType}_${contentId}`;

        console.log(`   📤 Uploading: ${fileName} (${(stats.size / 1024 / 1024).toFixed(2)} MB)`);

        let sentMessage;

        // ALL FILES: Refresh as document (better for large files)
        console.log(`   📄 Refreshing as DOCUMENT: ${fileName}`);
        sentMessage = await this.sendMessageWithRetry(this.cacheGroupId, {
            document: { url: filePath },
            caption: caption,
            mimetype: 'video/mp4',
            fileName: fileName
        }, filePath);

        // ✅ CRITICAL FIX: Update database AND cache with NEW message ID
        // This ensures future requests use the fresh message, not the expired one
        if (sentMessage && sentMessage.key && sentMessage.key.id) {
            const newMessageId = sentMessage.key.id;
            console.log(`   🔄 Updating DB with new message ID: ${newMessageId}`);

            // Update the database with the new message ID
            if (this.apiClient && this.apiClient.addMessageId) {
                await this.apiClient.addMessageId(
                    contentType,
                    contentId,
                    newMessageId,
                    fileName,
                    partNumber || 0
                );
            }

            // ✅ ADD TO PERSISTENT CACHE for instant Tier 1 forwarding
            if (global.multiTierForwarder && global.multiTierForwarder.messageCache) {
                global.multiTierForwarder.addToMessageCache(newMessageId, sentMessage);
                console.log(`   ✅ Added to persistent cache - Tier 1 ready!`);
            }

            // Also add to global store for Tier 2
            if (global.store && global.store.messages) {
                const cacheGroupId = this.cacheGroupId;
                if (!global.store.messages[cacheGroupId]) {
                    global.store.messages[cacheGroupId] = [];
                }
                // Add to store (handle both array and object formats)
                if (Array.isArray(global.store.messages[cacheGroupId])) {
                    global.store.messages[cacheGroupId].push(sentMessage);
                } else if (global.store.messages[cacheGroupId].array) {
                    global.store.messages[cacheGroupId].array.push(sentMessage);
                }
                console.log(`   ✅ Added to store - Tier 2 ready!`);
            }
        }

        // Log refresh to API if available
        if (this.apiClient && this.apiClient.logRefresh) {
            const messageIdKey = `${contentType}_${contentId}${partNumber ? `_part${partNumber}` : ''}`;
            await this.apiClient.logRefresh(messageIdKey, fileName, 'video', stats.size);
        }
    }
}

module.exports = AutoRefresher;

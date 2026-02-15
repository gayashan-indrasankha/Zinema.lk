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
        this.stateFile = path.join(__dirname, '..', 'warming-state.json');

        // Progress tracking
        this.progressChatId = null;  // Where to send progress updates
        this.lastProgressUpdate = 0;  // Timestamp of last progress message

        // Upload retry config
        this.maxRetries = 2; // Only retry non-timeout errors (timeout = rate limited)
        this.consecutiveErrors = 0;
        this.maxConsecutiveErrors = 2; // Stop sooner — 2 failures in a row = rate limited

        // Bandwidth tracking — WhatsApp drops connection after ~4-5GB
        this.bytesUploadedThisSession = 0;
        this.bandwidthLimitBytes = 3.5 * 1024 * 1024 * 1024; // 3.5GB per batch before cooldown
        this.batchCooldownMs = 30 * 60 * 1000; // 30 min cooldown after hitting bandwidth limit

        // Statistics
        this.stats = {
            lastRunTime: null,
            videosWarmed: 0,
            errors: 0,
            totalRuns: 0
        };
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
        const timeoutMs = filePath ? this.getUploadTimeout(filePath) : 10 * 60 * 1000;
        const fileSize = filePath ? (fs.existsSync(filePath) ? fs.statSync(filePath).size : 0) : 0;

        // Check bandwidth limit BEFORE attempting upload
        if (fileSize > 0 && this.bytesUploadedThisSession + fileSize > this.bandwidthLimitBytes) {
            const uploadedGB = (this.bytesUploadedThisSession / 1024 / 1024 / 1024).toFixed(1);
            const cooldownMin = Math.round(this.batchCooldownMs / 60000);
            console.log(`\n🛑 BANDWIDTH LIMIT: Uploaded ${uploadedGB} GB this session.`);
            console.log(`   💤 Taking ${cooldownMin} min cooldown to avoid WhatsApp rate limiting...`);

            if (this.progressChatId) {
                try {
                    await this.sock.sendMessage(this.progressChatId, {
                        text: `⏸️ *Batch Cooldown*\n\nUploaded ${uploadedGB} GB so far.\nTaking a ${cooldownMin} minute break to avoid WhatsApp rate limiting.\nWill resume automatically...`
                    });
                } catch (e) { }
            }

            await new Promise(r => setTimeout(r, this.batchCooldownMs));
            this.bytesUploadedThisSession = 0; // Reset after cooldown
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
                // Track bandwidth on success
                if (fileSize > 0) {
                    this.bytesUploadedThisSession += fileSize;
                    const totalMB = (this.bytesUploadedThisSession / 1024 / 1024).toFixed(0);
                    console.log(`   📊 Session bandwidth: ${totalMB} MB uploaded so far`);
                }
                return result;
            } catch (error) {
                console.error(`   ⚠️ Attempt ${attempt}/${this.maxRetries} failed: ${error.message}`);

                // Connection errors = socket is dead, stop immediately
                if (this.isConnectionError(error)) {
                    throw error;
                }

                // Timeout = rate limited. Do NOT retry (would waste another 20+ min)
                if (error.message === 'Upload timed out') {
                    console.log(`   🚫 Upload timed out — likely rate limited. NOT retrying.`);
                    console.log(`   💤 Cooling down for 10 minutes before next file...`);
                    throw error; // Let caller handle it
                }

                // Other errors: retry with backoff
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
            if (fs.existsSync(this.stateFile)) {
                const data = fs.readFileSync(this.stateFile, 'utf8');
                return JSON.parse(data);
            }
        } catch (error) {
            console.error(`⚠️ Failed to load warming state: ${error.message}`);
        }
        return null;
    }

    /**
     * Save warming state to file
     */
    saveState(state) {
        try {
            fs.writeFileSync(this.stateFile, JSON.stringify(state, null, 2), 'utf8');
            console.log(`💾 Saved warming state: ${state.remainingFiles?.length || 0} files remaining`);
        } catch (error) {
            console.error(`⚠️ Failed to save warming state: ${error.message}`);
        }
    }

    /**
     * Clear warming state file
     */
    clearState() {
        try {
            if (fs.existsSync(this.stateFile)) {
                fs.unlinkSync(this.stateFile);
                console.log(`🗑️ Cleared warming state file`);
            }
        } catch (error) {
            console.error(`⚠️ Failed to clear warming state: ${error.message}`);
        }
    }

    /**
     * Check if current time is within warming window (12:00 AM - 5:00 AM)
     */
    isWithinWarmingWindow() {
        const now = new Date();
        const hour = now.getHours();
        // 12:00 AM (0) to 5:00 AM (4) - hours 0-4 inclusive
        return hour >= 0 && hour < 5;
    }

    /**
     * Start the cache warmer - runs on startup and schedules automatic refresh
     */
    async start(warmOnStartup = true) {
        // PROACTIVE DAILY REFRESH (3 AM) - Refresh messages older than 10 days
        // This prevents "Download failed" by refreshing BEFORE they expire (~14 days)
        this.proactiveJob = schedule.scheduleJob('0 3 * * *', async () => {
            console.log(`\n🔄 PROACTIVE REFRESH (3 AM): Checking for aging messages...`);
            await this.refreshAgingMessages();
        });

        // WEEKLY FULL REFRESH (Sunday 2 AM) - Refresh everything
        this.job = schedule.scheduleJob('0 2 * * 0', async () => {
            console.log(`\n🔄 WEEKLY CACHE REFRESH triggered (Sunday 2 AM)...`);
            await this.warmAllLocalFiles(true, true);
        });

        // MONTHLY FULL REFRESH on 1st of each month at 4 AM
        this.monthlyJob = schedule.scheduleJob('0 4 1 * *', async () => {
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
        if (!this.apiClient) {
            console.log(`⚠️ No API client, cannot check message ages`);
            return { refreshed: 0 };
        }

        try {
            // Get messages older than 10 days from API
            const response = await this.apiClient.getAgingMessages(10);

            if (!response || !response.messages || response.messages.length === 0) {
                console.log(`✅ No aging messages found - all content is fresh!`);
                return { refreshed: 0 };
            }

            console.log(`📋 Found ${response.messages.length} aging messages to refresh`);

            let refreshed = 0;
            let failed = 0;

            for (const msg of response.messages) {
                try {
                    // Use low priority (10) for proactive refresh
                    // User requests have priority 100, so they jump ahead
                    await this.warmSingleFile(
                        msg.content_type,
                        msg.content_id,
                        msg.part_number,
                        this.fileManager.getFilePath(msg.content_type, msg.content_id, msg.part_number)
                    );
                    refreshed++;

                    // Smart delay between uploads based on file size
                    const filePath = this.fileManager.getFilePath(msg.content_type, msg.content_id, msg.part_number);
                    const delayMs = this.getSmartDelay(filePath);
                    console.log(`   ⏳ Waiting ${Math.round(delayMs / 1000)}s before next upload...`);
                    await new Promise(r => setTimeout(r, delayMs));
                } catch (e) {
                    console.log(`   ❌ Failed to refresh ${msg.content_type}_${msg.content_id}: ${e.message}`);
                    failed++;
                }
            }

            console.log(`✅ Proactive refresh complete: ${refreshed} refreshed, ${failed} failed`);
            return { refreshed, failed };
        } catch (e) {
            console.error(`❌ Proactive refresh error: ${e.message}`);
            return { refreshed: 0, error: e.message };
        }
    }

    /**
     * Warm ALL local files (used on startup and nightly)
     * @param {boolean} forceRefresh - If true, re-upload even if already in DB
     * @param {boolean} ignoreTimeWindow - If true, bypass time window check (for manual commands)
     */
    async warmAllLocalFiles(forceRefresh = false, ignoreTimeWindow = false) {
        if (this.isRunning) {
            console.log(`⚠️ Cache warming already in progress`);
            return { success: false, message: 'Already running' };
        }

        this.isRunning = true;
        this.stats.totalRuns++;
        this.stats.lastRunTime = new Date();

        console.log(`\n🔥 Warming ALL local files (Mode: ${forceRefresh ? 'FORCE REFRESH' : 'SMART WARM'})...`);

        try {
            // Check if there's a saved state with remaining files
            const savedState = this.loadState();
            let files;

            if (savedState && savedState.remainingFiles && savedState.remainingFiles.length > 0 && !forceRefresh) {
                files = savedState.remainingFiles;
                console.log(`🔄 Resuming from previous session: ${files.length} files remaining`);
            } else {
                // Get all local files
                files = await this.fileManager.listAllFiles();
                console.log(`📁 Found ${files.length} local files to warm`);
                // Clear any old state since we're starting fresh
                this.clearState();
            }

            let warmed = 0;
            let errors = 0;
            let skipped = 0;

            for (let i = 0; i < files.length; i++) {
                const file = files[i];

                // Check if we're still within the warming window (12:00 AM - 5:00 AM)
                // Skip check if manual command (ignoreTimeWindow = true)
                if (!ignoreTimeWindow && !this.isWithinWarmingWindow()) {
                    console.log(`⏰ Warming window ended (5:00 AM reached). Stopping warm process.`);
                    console.log(`   Progress: ${warmed} warmed, ${skipped} skipped, ${errors} errors`);

                    // Save remaining files for next run
                    const remainingFiles = files.slice(i);
                    this.saveState({
                        remainingFiles,
                        lastRun: new Date().toISOString(),
                        progress: { warmed, skipped, errors }
                    });
                    break;
                }

                try {
                    // SMART WARMING: Check if this file already has a message ID in the DB
                    if (this.apiClient && !forceRefresh) {
                        const existing = await this.apiClient.getMessageId(file.type, file.contentId);
                        // If messageId exists for the specific part, skip it
                        if (existing && (file.partNumber ? existing.part_message_id : existing.message_id)) {
                            console.log(`   ⏭️ Already in cache: ${file.path}`);
                            skipped++;
                            continue;
                        }
                    }

                    const resultId = await this.warmSingleFile(file.type, file.contentId, file.partNumber, file.path);

                    if (resultId === null) {
                        // File was skipped
                        skipped++;
                    } else {
                        warmed++;
                    }

                    // Smart delay based on file size
                    const delayMs = this.getSmartDelay(file.path);
                    console.log(`   ⏳ Waiting ${Math.round(delayMs / 1000)}s before next upload...`);
                    await new Promise(r => setTimeout(r, delayMs));

                } catch (error) {
                    console.error(`❌ Failed to warm ${file.path}: ${error.message}`);
                    errors++;
                    this.consecutiveErrors++;

                    // Stop if connection dropped
                    if (this.isConnectionError(error)) {
                        console.log(`⚠️ Connection dropped during warm. Saving progress and stopping.`);
                        const remainingFiles = files.slice(i + 1);
                        this.saveState({
                            remainingFiles,
                            lastRun: new Date().toISOString(),
                            progress: { warmed, skipped, errors },
                            reason: 'connection_drop'
                        });
                        break;
                    }

                    // Stop after too many consecutive failures (likely rate limited)
                    if (this.consecutiveErrors >= this.maxConsecutiveErrors) {
                        console.log(`🚨 ${this.maxConsecutiveErrors} consecutive errors! Likely rate limited. Saving progress and stopping.`);
                        const remainingFiles = files.slice(i + 1);
                        this.saveState({
                            remainingFiles,
                            lastRun: new Date().toISOString(),
                            progress: { warmed, skipped, errors },
                            reason: 'consecutive_errors'
                        });
                        break;
                    }

                    // Extra cooldown after an error
                    console.log(`   ⏳ Error cooldown: waiting 30s before next attempt...`);
                    await new Promise(r => setTimeout(r, 30000));
                }
            }

            this.stats.videosWarmed += warmed;
            this.stats.errors += errors;

            console.log(`\n✅ Cache Warming Complete!`);
            console.log(`   Warmed: ${warmed}/${files.length}`);
            console.log(`   Skipped: ${skipped}`);
            console.log(`   Errors: ${errors}`);

            // Clear state file when all files are processed
            this.clearState();

            return { success: true, warmed, errors, skipped, total: files.length };

        } catch (error) {
            console.error(`❌ Cache warming failed: ${error.message}`);
            return { success: false, message: error.message };
        } finally {
            this.isRunning = false;
        }
    }

    /**
     * Warm specific ID range (for manual command)
     * @param {string} contentType - 'movie' or 'episode'
     * @param {number} startId - Starting content ID
     * @param {number} endId - Ending content ID
     * @param {boolean} ignoreTimeWindow - If true, bypass time window check
     * @param {string} progressChatId - Chat ID to send progress updates
     */
    async warmIdRange(contentType, startId, endId, ignoreTimeWindow = true, progressChatId = null) {
        if (this.isRunning) {
            return { success: false, message: 'Already running' };
        }

        this.isRunning = true;
        this.progressChatId = progressChatId;
        this.lastProgressUpdate = 0;

        const totalRange = endId - startId + 1;
        console.log(`\n🔥 Warming ${contentType}s from ID ${startId} to ${endId} (${totalRange} items)...`);

        let warmed = 0;
        let notFound = 0;
        let errors = 0;
        let processed = 0;
        let notFoundIds = [];
        let errorIds = [];

        try {
            for (let id = startId; id <= endId; id++) {
                processed++;

                // Show progress in console
                this.logProgress(processed, totalRange, `${contentType} ${id}`);

                // Check if we're still within the warming window (12:00 AM - 5:00 AM)
                // Skip check if manual command (ignoreTimeWindow = true)
                if (!ignoreTimeWindow && !this.isWithinWarmingWindow()) {
                    console.log(`⏰ Warming window ended (5:00 AM reached). Stopping range warm.`);
                    break;
                }

                // Check for full file
                if (this.fileManager.hasLocalFile(contentType, id, null)) {
                    try {
                        const filePath = this.fileManager.getFilePath(contentType, id, null);
                        await this.warmSingleFile(contentType, id, null, filePath);
                        warmed++;
                        this.consecutiveErrors = 0;
                        console.log(`✅ Warmed: ${contentType} ${id}`);
                        const delayMs = this.getSmartDelay(filePath);
                        console.log(`   ⏳ Waiting ${Math.round(delayMs / 1000)}s before next upload...`);
                        await new Promise(r => setTimeout(r, delayMs));
                    } catch (e) {
                        errors++;
                        errorIds.push(id);
                        this.consecutiveErrors++;
                        console.log(`❌ Failed: ${contentType} ${id}: ${e.message}`);
                        if (this.isConnectionError(e)) {
                            console.log(`⚠️ Connection dropped! Stopping warm at ID ${id}.`);
                            break;
                        }
                        if (this.consecutiveErrors >= this.maxConsecutiveErrors) {
                            console.log(`🚨 ${this.maxConsecutiveErrors} consecutive errors! Likely rate limited. Stopping.`);
                            break;
                        }
                        // Timeout = rate limited, long cooldown. Other errors = shorter.
                        const cooldown = e.message === 'Upload timed out' ? 600000 : 30000;
                        console.log(`   ⏳ Cooldown: waiting ${cooldown / 1000}s before next attempt...`);
                        await new Promise(r => setTimeout(r, cooldown)); // 10min if timeout, 30s otherwise
                    }
                } else {
                    // Check for parts (1-20)
                    let hasAnyPart = false;
                    let partBroken = false;
                    for (let part = 1; part <= 20; part++) {
                        if (this.fileManager.hasLocalFile(contentType, id, part)) {
                            hasAnyPart = true;
                            try {
                                const filePath = this.fileManager.getFilePath(contentType, id, part);
                                await this.warmSingleFile(contentType, id, part, filePath);
                                warmed++;
                                this.consecutiveErrors = 0;
                                console.log(`✅ Warmed: ${contentType} ${id} Part ${part}`);
                                const delayMs = this.getSmartDelay(filePath);
                                console.log(`   ⏳ Waiting ${Math.round(delayMs / 1000)}s before next upload...`);
                                await new Promise(r => setTimeout(r, delayMs));
                            } catch (e) {
                                errors++;
                                errorIds.push(`${id}p${part}`);
                                this.consecutiveErrors++;
                                console.log(`❌ Failed: ${contentType} ${id} Part ${part}: ${e.message}`);
                                if (this.isConnectionError(e) || this.consecutiveErrors >= this.maxConsecutiveErrors) {
                                    partBroken = true;
                                    break;
                                }
                                await new Promise(r => setTimeout(r, 30000));
                            }
                        }
                    }
                    if (partBroken) break;

                    if (!hasAnyPart) {
                        notFound++;
                        notFoundIds.push(id);
                    }
                }

                // Send WhatsApp progress update
                await this.sendProgressUpdate(processed, totalRange, warmed, errors, notFound);
            }

            // Final summary
            let summary = `✅ *Warming Complete!*\n\n📋 Range: ${contentType} ${startId}-${endId}\n${this.generateProgressBar(processed, totalRange)}\n\n✅ Warmed: ${warmed}\n⏭️ Not Found: ${notFound}\n❌ Errors: ${errors}`;
            if (notFoundIds.length > 0) {
                summary += `\n\n📝 *Not Found IDs:*\n${notFoundIds.join(', ')}`;
            }
            if (errorIds.length > 0) {
                summary += `\n\n⚠️ *Error IDs:*\n${errorIds.join(', ')}`;
            }

            console.log(`\n${summary.replace(/\*/g, '')}`);

            if (progressChatId) {
                try {
                    await this.sock.sendMessage(progressChatId, { text: summary });
                } catch (e) { }
            }

            return { success: true, warmed, notFound, errors, range: `${startId}-${endId}` };

        } finally {
            this.isRunning = false;
        }
    }

    /**
     * Warm a single file by uploading to cache group - BAILEYS VERSION
     * HYBRID: Parts → video, Full file → document
     */
    async warmSingleFile(contentType, contentId, partNumber, filePath) {
        if (!this.cacheGroupId) {
            console.log(`⚠️ No cache group ID configured, skipping warm for ${filePath}`);
            return null;
        }

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
    async updateMessageId(contentType, contentId, partNumber, messageId) {
        if (!this.apiClient) {
            console.log(`⚠️ API client not set, skipping message ID update`);
            return;
        }

        try {
            await this.apiClient.addMessageId(
                contentType,
                contentId,
                messageId,
                null,
                partNumber || 0
            );
            console.log(`💾 Saved message ID for ${contentType} ${contentId}`);
        } catch (error) {
            console.error(`Failed to update message ID: ${error.message}`);
        }
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

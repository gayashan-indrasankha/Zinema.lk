/**
 * WhatsApp Video Forwarder Bot with Queue System
 * Uses Baileys (WhatsApp Socket API) to forward videos from storage based on tokens
 * Optimized for high traffic with queue management and large file streaming (200MB+)
 * 
 * This version uses API Bridge for remote database access (Namecheap hosting)
 */

const makeWASocket = require('@whiskeysockets/baileys').default;
const { useMultiFileAuthState, DisconnectReason, makeInMemoryStore, delay, fetchLatestBaileysVersion, Browsers } = require('@whiskeysockets/baileys');
const pino = require('pino');
const qrcode = require('qrcode-terminal');
const cliProgress = require('cli-progress');
const fs = require('fs');
const path = require('path');

// Load environment variables from custom file if specified
const envFile = process.env.ENV_FILE || '.env';
require('dotenv').config({ path: envFile });

console.log(`📋 Loading config from: ${envFile}`);

// Use API client instead of direct database connection
const {
    testConnection,
    getTokenData,
    markTokenAsUsed,
    logForward,
    addMessageId,
    needsRefresh,
    logRefresh,
    getRefreshStats
} = require('./config/api-client');

// Health Monitor for admin dashboard
const HealthMonitor = require('./config/health-monitor');
const apiClient = require('./config/api-client');
let healthMonitor = null;

// Ultimate System: Local File Manager and Multi-Tier Forwarder
const FileManager = require('./config/file-manager');
const MultiTierForwarder = require('./config/multi-tier-forwarder');
const LOCAL_MEDIA_PATH = process.env.LOCAL_MEDIA_PATH || 'D:\\bot-media';
let fileManager = null;
let multiTierForwarder = null;

// Configuration
const STORAGE_GROUP_ID = process.env.STORAGE_GROUP_ID;
const TOKEN_PREFIX = process.env.TOKEN_PREFIX || '!get';
const ADMIN_NUMBERS = (process.env.ADMIN_NUMBERS || '').split(',').filter(n => n);

// Helper function to check if a number is an admin
function isAdmin(phoneNumber) {
    return ADMIN_NUMBERS.some(admin => phoneNumber.includes(admin));
}

/**
 * Extract group ID from a WhatsApp message ID
 * Message ID format: true_GROUPID@g.us_MESSAGEHASH_SENDERID@lid
 * Example: true_120363404925435399@g.us_A53248A33E7271FA7164E570564AC5F2_99042583400702@lid
 */
function extractGroupFromMessageId(messageId) {
    if (!messageId) return null;

    // Match pattern: true_NUMBERS@g.us
    const match = messageId.match(/true_(\d+@g\.us)/);
    return match ? match[1] : null;
}

// Parse multiple storage groups from env
// Format: comma-separated group IDs
const STORAGE_GROUP_IDS = (process.env.STORAGE_GROUP_IDS || process.env.STORAGE_GROUP_ID || '')
    .split(',')
    .map(id => id.trim())
    .filter(id => id);

// Progress Indicator for CLI
class ProgressIndicator {
    constructor() {
        this.timer = null;
        this.startTime = null;
        this.barWidth = 30;
        this.progress = 0;
        this.message = '';
    }

    // Start a progress bar for ongoing operations
    startProgressBar(message) {
        this.startTime = Date.now();
        this.progress = 0;
        this.message = message;

        this.timer = setInterval(() => {
            const elapsed = Math.floor((Date.now() - this.startTime) / 1000);

            // Simulate progress that slows down as it approaches 95%
            // This creates a realistic effect where we never reach 100% until completion
            if (this.progress < 95) {
                // Fast at start, slower near end
                const increment = Math.max(0.5, (95 - this.progress) / 20);
                this.progress = Math.min(95, this.progress + increment);
            }

            this.renderBar(elapsed);
        }, 200);
    }

    // Render the progress bar
    renderBar(elapsed) {
        const percent = Math.floor(this.progress);
        const filled = Math.floor((this.progress / 100) * this.barWidth);
        const empty = this.barWidth - filled;

        const filledBar = '█'.repeat(filled);
        const emptyBar = '░'.repeat(empty);

        process.stdout.write(`\r📤 ${this.message} |${filledBar}${emptyBar}| ${percent}% [${this.formatTime(elapsed)}]   `);
    }

    // Complete the progress bar successfully
    completeProgressBar(finalMessage) {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }

        this.progress = 100;
        const elapsed = this.startTime ? Math.floor((Date.now() - this.startTime) / 1000) : 0;
        const filledBar = '█'.repeat(this.barWidth);

        process.stdout.write(`\r✅ ${finalMessage} |${filledBar}| 100% [${this.formatTime(elapsed)}]          \n`);
    }

    // Complete with error
    errorProgressBar(errorMessage) {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }

        const elapsed = this.startTime ? Math.floor((Date.now() - this.startTime) / 1000) : 0;
        const filled = Math.floor((this.progress / 100) * this.barWidth);
        const empty = this.barWidth - filled;

        process.stdout.write(`\r❌ ${errorMessage} |${'█'.repeat(filled)}${'░'.repeat(empty)}| ${Math.floor(this.progress)}% [${this.formatTime(elapsed)}]          \n`);
    }

    // Format seconds to mm:ss
    formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    // Create a progress bar for batch operations
    createBatchProgressBar(total, title = 'Processing') {
        return new cliProgress.SingleBar({
            format: `${title} |{bar}| {percentage}% | {value}/{total} | ETA: {eta}s`,
            barCompleteChar: '█',
            barIncompleteChar: '░',
            hideCursor: true
        }, cliProgress.Presets.shades_classic);
    }
}

const progressIndicator = new ProgressIndicator();


// Queue System for high traffic handling
class MessageQueue {
    constructor() {
        this.queue = [];
        this.isProcessing = false;
        this.minDelay = 1500;  // 1.5 seconds minimum delay (was 5s)
        this.maxDelay = 3000;  // 3 seconds maximum delay (was 10s)
    }

    /**
     * Add a task to the queue
     * @param {Function} task - Async function to execute
     * @param {Object} metadata - Task metadata for logging
     */
    add(task, metadata = {}) {
        this.queue.push({ task, metadata, addedAt: Date.now() });
        console.log(`📥 Queue: Added task. Queue size: ${this.queue.length}`);
        this.process();
    }

    /**
     * Get random delay between min and max
     */
    getRandomDelay() {
        return Math.floor(Math.random() * (this.maxDelay - this.minDelay + 1)) + this.minDelay;
    }

    /**
     * Process queue items sequentially with delay
     */
    async process() {
        if (this.isProcessing || this.queue.length === 0) {
            return;
        }

        this.isProcessing = true;

        while (this.queue.length > 0) {
            const { task, metadata, addedAt } = this.queue.shift();
            const waitTime = Date.now() - addedAt;

            console.log(`⚙️ Queue: Processing task. Waited: ${Math.round(waitTime / 1000)}s. Remaining: ${this.queue.length}`);

            try {
                await task();
            } catch (error) {
                console.error(`❌ Queue: Task failed:`, error.message);
            }

            // Add random delay between tasks (only if more items in queue)
            if (this.queue.length > 0) {
                const delay = this.getRandomDelay();
                console.log(`⏳ Queue: Waiting ${delay / 1000}s before next task...`);
                await new Promise(resolve => setTimeout(resolve, delay));
            }
        }

        this.isProcessing = false;
        console.log(`✅ Queue: All tasks completed`);
    }

    /**
     * Get queue status
     */
    getStatus() {
        return {
            size: this.queue.length,
            isProcessing: this.isProcessing
        };
    }
}

// Initialize queue
const messageQueue = new MessageQueue();

// Auto Refresher for Cache
// Auto Refresher for Cache
class AutoRefresher {
    constructor() {
        // Use bot-specific cache group ID
        const botId = process.env.BOT_INSTANCE_ID || '1';
        this.cacheGroupId = process.env[`CACHE_GROUP_ID_BOT${botId}`] || process.env.CACHE_GROUP_ID;
        // Interval in milliseconds (default 72 hours)
        this.refreshInterval = (process.env.REFRESH_INTERVAL_HOURS || 72) * 60 * 60 * 1000;
        this.isRefreshing = false;
        this.timerId = null;
    }

    start() {
        if (!this.cacheGroupId) {
            console.log('⚠️ AutoRefresher: CACHE_GROUP_ID not set. Cache refreshing disabled.');
            return;
        }

        console.log(`🔄 AutoRefresher: Enabled. Cycled every ${process.env.REFRESH_INTERVAL_HOURS || 72} hours.`);
        console.log('🕒 Scheduling constraint: Must start at 12:30 AM (00:30).');

        this.scheduleNextRun();
    }

    scheduleNextRun() {
        if (this.timerId) clearTimeout(this.timerId);

        const now = new Date();
        const target = new Date(now);

        // precise target: 00:30:00 (12:30 AM)
        target.setHours(0, 30, 0, 0);

        // If target time has already passed for today, schedule for tomorrow
        // Note: Even if we are IN the window (e.g. 1:00 AM), we schedule for tomorrow 
        // to strictly ensure we always start at the beginning of the window for maximum time.
        if (now > target) {
            target.setDate(target.getDate() + 1);
        }

        const delay = target.getTime() - now.getTime();
        const hoursUntil = (delay / (1000 * 60 * 60)).toFixed(2);

        console.log(`📅 Next Auto-Refresh scheduled for: ${target.toLocaleString()} (in ~${hoursUntil} hours)`);

        this.timerId = setTimeout(() => this.refresh(), delay);
    }

    async refresh(manualTrigger = false, notifyChat = null) {
        if (this.isRefreshing) {
            if (notifyChat) await notifyChat.sendMessage('⚠️ Refresh already in progress!');
            return;
        }

        // Time window safety check (only for automatic runs)
        if (!manualTrigger) {
            const now = new Date();
            const currentHour = now.getHours();
            const currentMinute = now.getMinutes();

            // Check if we're in the window: 00:30 to 06:00
            const isInWindow = (currentHour === 0 && currentMinute >= 30) || (currentHour > 0 && currentHour < 6);

            if (!isInWindow) {
                console.log(`⚠️ AutoRefresher: Outside time window (${currentHour}:${currentMinute}). Rescheduling...`);
                this.scheduleNextRun();
                return;
            }

            console.log(`✅ In time window (${currentHour}:${currentMinute}). Starting refresh...`);
        }

        this.isRefreshing = true;
        const startTime = Date.now();
        console.log('🔄 AutoRefresher: Starting cache refresh cycle...');

        if (notifyChat) await notifyChat.sendMessage('🔄 Starting cache refresh cycle...\nFetching ALL media files from storage groups...');

        let totalRefreshedCount = 0;
        let totalSkippedCount = 0;
        let totalFailedCount = 0;
        let totalMediaCount = 0;

        try {
            const cacheChat = await client.getChatById(this.cacheGroupId);

            // Get all storage groups to refresh
            const groupsToRefresh = STORAGE_GROUP_IDS.length > 0
                ? STORAGE_GROUP_IDS
                : (process.env.STORAGE_GROUP_ID ? [process.env.STORAGE_GROUP_ID] : []);

            if (groupsToRefresh.length === 0) {
                throw new Error('No storage groups configured');
            }

            console.log(`📦 Refreshing ${groupsToRefresh.length} storage group(s)...`);

            // Process each storage group
            for (let groupIndex = 0; groupIndex < groupsToRefresh.length; groupIndex++) {
                const storageGroupId = groupsToRefresh[groupIndex];
                console.log(`\n📂 Processing storage group ${groupIndex + 1}/${groupsToRefresh.length}: ${storageGroupId}`);

                let storageChat;
                try {
                    storageChat = await client.getChatById(storageGroupId);
                } catch (err) {
                    console.error(`❌ Failed to access group ${storageGroupId}: ${err.message}`);
                    continue;
                }

                // Fetch messages from this group
                console.log('📥 Fetching media messages...');
                let allMediaMessages = [];

                try {
                    const messages = await storageChat.fetchMessages({ limit: 500 });
                    allMediaMessages = messages.filter(m => m.hasMedia && (m.type === 'video' || m.type === 'document' || m.type === 'image'));
                    console.log(`📦 Found ${allMediaMessages.length} media files in this group`);
                } catch (err) {
                    console.error(`❌ Failed to fetch messages: ${err.message}`);
                    continue;
                }

                totalMediaCount += allMediaMessages.length;

                // Refresh each media file
                for (let i = 0; i < allMediaMessages.length; i++) {
                    const msg = allMediaMessages[i];
                    const messageId = msg.id._serialized;
                    const fileName = msg._data?.filename || msg._data?.caption || `file_${i + 1}`;

                    try {
                        // Check if this file needs refresh
                        const shouldRefresh = await needsRefresh(messageId);

                        if (!shouldRefresh) {
                            console.log(`⏭️  Skipped [${i + 1}/${allMediaMessages.length}]: ${fileName} (refreshed recently)`);
                            totalSkippedCount++;
                            continue;
                        }

                        // Forward to cache group
                        await msg.forward(this.cacheGroupId);

                        // Log the refresh in database
                        await logRefresh(messageId, fileName, msg.type);

                        totalRefreshedCount++;
                        console.log(`✅ Refreshed [${totalRefreshedCount}]: ${fileName}`);

                        // Random delay between 3-5 seconds
                        const delay = 3000 + Math.floor(Math.random() * 2000);
                        await new Promise(resolve => setTimeout(resolve, delay));

                    } catch (err) {
                        console.error(`❌ Failed: ${fileName} - ${err.message}`);
                        totalFailedCount++;
                    }
                }
            }

            // Get database statistics
            const stats = await getRefreshStats();
            const duration = ((Date.now() - startTime) / 1000 / 60).toFixed(2);

            // Print summary report
            console.log(`\n╔════════════════════════════════════════╗`);
            console.log(`║      REFRESH CYCLE COMPLETED           ║`);
            console.log(`╠════════════════════════════════════════╣`);
            console.log(`║ Storage Groups: ${groupsToRefresh.length.toString().padEnd(22)}║`);
            console.log(`║ Total Files: ${totalMediaCount.toString().padEnd(25)}║`);
            console.log(`║ Refreshed: ${totalRefreshedCount.toString().padEnd(27)}║`);
            console.log(`║ Skipped: ${totalSkippedCount.toString().padEnd(29)}║`);
            console.log(`║ Failed: ${totalFailedCount.toString().padEnd(30)}║`);
            console.log(`║ Duration: ${duration} minutes${' '.repeat(22 - duration.length)}║`);
            console.log(`╚════════════════════════════════════════╝\n`);

            if (notifyChat) {
                await notifyChat.sendMessage(
                    `✅ *Refresh Completed*\n\n` +
                    `📦 Storage Groups: ${groupsToRefresh.length}\n` +
                    `📊 Total Files: ${totalMediaCount}\n` +
                    `🔄 Refreshed: ${totalRefreshedCount}\n` +
                    `⏭️ Skipped: ${totalSkippedCount}\n` +
                    `❌ Failed: ${totalFailedCount}\n` +
                    `⏱️ Duration: ${duration} min\n\n` +
                    `📅 Next cycle in ${process.env.REFRESH_INTERVAL_HOURS || 72} hours`
                );
            }

        } catch (error) {
            console.error('❌ AutoRefresher Error:', error.message);
            if (notifyChat) await notifyChat.sendMessage(`❌ Error: ${error.message}`);
        } finally {
            this.isRefreshing = false;

            // If this was an automatic run (not manual), schedule the next one
            if (!manualTrigger) {
                const daysToAdd = Math.round(this.refreshInterval / (24 * 60 * 60 * 1000));
                const nextTarget = new Date();
                nextTarget.setDate(nextTarget.getDate() + daysToAdd);
                nextTarget.setHours(0, 30, 0, 0);

                const delay = nextTarget.getTime() - Date.now();
                console.log(`📅 Cycle complete. Sleeping for ${daysToAdd} days.`);
                console.log(`📅 Next Auto-Refresh: ${nextTarget.toLocaleString()}`);

                this.timerId = setTimeout(() => this.refresh(), delay);
            }
        }
    }
}

const autoRefresher = new AutoRefresher();

// Global sock instance
let sock = null;
let store = null;

// WhatsApp Client Setup with Baileys
const BOT_INSTANCE_ID = process.env.BOT_INSTANCE_ID || '1';
const sessionName = `bot${BOT_INSTANCE_ID}`;

console.log(`🤖 Bot Instance: #${BOT_INSTANCE_ID}`);

// Ready Event
client.on('ready', async () => {
    console.log('╔════════════════════════════════════════════════════════╗');
    console.log('║  ✅ WhatsApp Bot is READY! (Queue System Enabled)       ║');
    console.log('╠════════════════════════════════════════════════════════╣');
    console.log(`║  📌 Logged in as: ${client.info.pushname}`);
    console.log(`║  📞 Phone: ${client.info.wid.user}`);
    console.log('║  💡 Commands:                                           ║');
    console.log(`║     ${TOKEN_PREFIX} <TOKEN> - Get video with token       ║`);
    console.log('║     !help - Show help message                           ║');
    console.log('║     !queue - Show queue status                          ║');
    console.log('╚════════════════════════════════════════════════════════╝\n');

    await testConnection();

    // Display all storage groups
    const allStorageGroups = STORAGE_GROUP_IDS.length > 0
        ? STORAGE_GROUP_IDS
        : (STORAGE_GROUP_ID ? [STORAGE_GROUP_ID] : []);

    if (allStorageGroups.length > 0) {
        console.log(`📦 Storage Groups (${allStorageGroups.length}):`);
        for (const groupId of allStorageGroups) {
            try {
                const chat = await client.getChatById(groupId);
                console.log(`   ✅ ${chat.name}`);
            } catch (error) {
                console.warn(`   ⚠️ Not found: ${groupId}`);
            }
        }
    } else {
        console.log('\n⚠️  No storage groups configured!');
        console.log('📝 Send "!groupid" in your storage group to get the ID.\n');
    }

    // Note: Token cleanup is handled by the server-side API

    // Start Auto Refresher
    autoRefresher.start();

    // Start Health Monitor (sends heartbeat every 30 seconds)
    const BOT_ID = parseInt(process.env.BOT_INSTANCE_ID) || 1;
    healthMonitor = new HealthMonitor(BOT_ID, apiClient, client);
    healthMonitor.start(30); // 30 second intervals
    console.log(`💓 Health monitoring started for Bot #${BOT_ID}`);

    // Initialize Ultimate System: Local File Manager and Multi-Tier Forwarder
    try {
        fileManager = new FileManager(LOCAL_MEDIA_PATH);
        global.fileManager = fileManager; // Make accessible globally
        console.log(`📁 File Manager initialized: ${LOCAL_MEDIA_PATH}`);

        const cacheGroupId = STORAGE_GROUP_IDS[0] || STORAGE_GROUP_ID || null;
        console.log(`📍 Cache Group ID: ${cacheGroupId}`);
        multiTierForwarder = new MultiTierForwarder(client, fileManager, apiClient, cacheGroupId);
        global.multiTierForwarder = multiTierForwarder;
        console.log(`🚀 Multi-Tier Forwarder initialized (Ultimate System ACTIVE)`);

        // Log local files count
        fileManager.listAllFiles().then(files => {
            console.log(`📊 Local files available: ${files.length}`);
        });

        // Count cached videos in WhatsApp group
        if (cacheGroupId) {
            client.getChatById(cacheGroupId).then(async (chat) => {
                try {
                    const messages = await chat.fetchMessages({ limit: 200 });
                    const cachedVideos = messages.filter(m => m.hasMedia).length;
                    console.log(`📦 Cached videos in group: ${cachedVideos}`);
                } catch (e) {
                    console.log(`📦 Cached videos: Could not fetch (${e.message})`);
                }
            }).catch(() => { });
        }

        // Initialize Cache Warmer with startup warming
        const CacheWarmer = require('./config/cache-warmer');
        const cacheWarmer = new CacheWarmer(client, fileManager, cacheGroupId);
        cacheWarmer.setApiClient(apiClient);
        global.cacheWarmer = cacheWarmer;

        // Start with auto-warm on startup - DISABLED by default
        // whatsapp-web.js cannot reliably upload files >50MB due to Puppeteer memory limits
        // Use !addmsgid after manual upload instead
        const WARM_ON_STARTUP = process.env.WARM_ON_STARTUP === 'true';
        cacheWarmer.start(WARM_ON_STARTUP);
        console.log(`🔥 Cache Warmer initialized (Auto-warm on startup: ${WARM_ON_STARTUP})`);

    } catch (err) {
        console.error(`❌ Failed to initialize Ultimate System: ${err.message}`);
    }
});

// Authentication Events
client.on('authenticated', () => {
    console.log('🔐 Authentication successful!');
});

client.on('auth_failure', (msg) => {
    console.error('❌ Authentication failed:', msg);
    console.log('💡 Try deleting the whatsapp-session folder and restart.');
});

// Disconnected Event
client.on('disconnected', (reason) => {
    console.log('❌ Client was disconnected:', reason);
    console.log('📱 Attempting to reconnect...');
    setTimeout(() => client.initialize(), 5000);
});

// Error Event
client.on('error', (error) => {
    console.error('⚠️ WhatsApp Client Error:', error.message);
});

// Global error handlers
process.on('unhandledRejection', (reason, promise) => {
    console.error('⚠️ Unhandled Rejection:', reason);
});

process.on('uncaughtException', (error) => {
    console.error('⚠️ Uncaught Exception:', error.message);
});

// Message Handler
client.on('message_create', async (message) => {
    if (message.isStatus) return;

    const body = message.body.trim().toLowerCase();

    let chat, senderNumber, senderName;

    try {
        chat = await message.getChat();
    } catch (err) {
        console.error('Error getting chat:', err.message);
        return;
    }


    try {
        // For group messages, try multiple sources; for private messages, use from
        const fromId = message.author || message._data?.participant || message.id?.participant || message.from || '';
        senderNumber = fromId.split('@')[0].split('-')[0];
        senderName = message._data?.notifyName || message.notifyName || senderNumber;
    } catch (err) {
        senderNumber = 'unknown';
        senderName = 'Unknown';
    }

    console.log(`📩 [${new Date().toLocaleTimeString()}] Message from ${senderName} (${senderNumber}): ${message.body.substring(0, 50)}...`);

    try {
        // Help Command
        if (body === '!help') {
            await handleHelpCommand(message, chat);
            return;
        }

        // Queue Status Command
        if (body === '!queue') {
            const status = messageQueue.getStatus();
            await message.reply(`📊 *Queue Status*\n\n📥 Pending: ${status.size}\n⚙️ Processing: ${status.isProcessing ? 'Yes' : 'No'}`);
            return;
        }

        // Get Group ID Command
        if (body === '!groupid' && chat.isGroup) {
            await message.reply(`📋 Group ID: \`${chat.id._serialized}\`\n\nAdd this to your .env file as STORAGE_GROUP_ID`);
            return;
        }

        // Get Message ID Command - works in any storage group or for admins
        if (body === '!msgid' && message.hasQuotedMsg) {
            // Check if in any storage group or is admin
            const isInStorageGroup = chat.isGroup && (
                chat.id._serialized === STORAGE_GROUP_ID ||
                STORAGE_GROUP_IDS.includes(chat.id._serialized)
            );

            if (isInStorageGroup || isAdmin(senderNumber)) {
                const quotedMsg = await message.getQuotedMessage();
                await message.reply(
                    `📋 *Message ID Details:*\n\n` +
                    `🆔 Message ID: \`${quotedMsg.id._serialized}\`\n` +
                    `📝 Type: ${quotedMsg.type}\n` +
                    `📎 Has Media: ${quotedMsg.hasMedia ? 'Yes' : 'No'}\n` +
                    `📂 Group: ${chat.name || chat.id._serialized}`
                );
            }
            return;
        }

        // Manual Refresh Command (works from any chat)
        if (body === '!refresh') {
            await message.reply('🔄 Manual refresh triggered...');
            autoRefresher.refresh(true, chat);
            return;
        }

        // Admin Commands
        if (isAdmin(senderNumber)) {
            // Get Cache Group ID
            if (body === '!cacheid' && chat.isGroup) {
                await message.reply(`📋 Cache Group ID: \`${chat.id._serialized}\`\n\nAdd this to your .env as CACHE_GROUP_ID`);
                return;
            }

            // Manual Cache Warming Command
            // Usage: !warm movie 1-50 OR !warm episode 100-200 OR !warm all
            if (body.startsWith('!warm')) {
                const parts = body.split(' ');

                if (parts.length === 1 || parts[1] === 'all') {
                    // Warm all local files
                    await message.reply(`🔥 Starting full cache warm of all local files...\nThis may take a while.`);
                    if (global.cacheWarmer) {
                        global.cacheWarmer.warmAllLocalFiles();
                        // Don't await - runs in background
                    } else {
                        await message.reply(`❌ Cache Warmer not initialized`);
                    }
                    return;
                }

                if (parts.length >= 3) {
                    const contentType = parts[1].toLowerCase(); // movie or episode
                    const range = parts[2]; // e.g., 1-50

                    if (!['movie', 'episode'].includes(contentType)) {
                        await message.reply(`❌ Invalid type. Use: movie or episode\n\nExamples:\n!warm movie 1-50\n!warm episode 100-200\n!warm all`);
                        return;
                    }

                    const rangeParts = range.split('-');
                    if (rangeParts.length !== 2) {
                        await message.reply(`❌ Invalid range format. Use: start-end\n\nExamples:\n!warm movie 1-50\n!warm episode 100-200`);
                        return;
                    }

                    const startId = parseInt(rangeParts[0]);
                    const endId = parseInt(rangeParts[1]);

                    if (isNaN(startId) || isNaN(endId) || startId > endId) {
                        await message.reply(`❌ Invalid range. Start must be less than end.`);
                        return;
                    }

                    await message.reply(`🔥 Starting cache warm for ${contentType}s ${startId} to ${endId}...\nThis may take a while.`);

                    if (global.cacheWarmer) {
                        global.cacheWarmer.warmIdRange(contentType, startId, endId);
                        // Don't await - runs in background
                    } else {
                        await message.reply(`❌ Cache Warmer not initialized`);
                    }
                    return;
                }

                await message.reply(`🔥 *Cache Warm Commands:*\n\n!warm all - Warm all local files\n!warm movie 1-50 - Warm movies 1 to 50\n!warm episode 100-200 - Warm episodes 100 to 200\n!warmstats - View warming statistics`);
                return;
            }

            // Cache Warming Statistics
            if (body === '!warmstats') {
                if (global.cacheWarmer) {
                    const stats = global.cacheWarmer.getStats();
                    await message.reply(
                        `📊 *Cache Warmer Stats:*\n\n` +
                        `🔄 Total Runs: ${stats.totalRuns}\n` +
                        `✅ Videos Warmed: ${stats.videosWarmed}\n` +
                        `❌ Errors: ${stats.errors}\n` +
                        `⏰ Last Run: ${stats.lastRunTime || 'Never'}\n` +
                        `🌙 Next Scheduled: ${stats.nextRun || 'Not scheduled'}\n` +
                        `🔥 Currently Running: ${stats.isRunning ? 'Yes' : 'No'}`
                    );
                } else {
                    await message.reply(`❌ Cache Warmer not initialized`);
                }
                return;
            }

            // Health Check Command - Verify message IDs
            // Usage: !checkhealth movie 92 OR !checkhealth episode 68 OR !checkhealth all
            if (body.startsWith('!checkhealth')) {
                const parts = body.split(' ');

                // Check ALL content
                if (parts.length >= 2 && parts[1].toLowerCase() === 'all') {
                    await message.reply(`🔍 Checking health of ALL linked content...\nThis may take a while.`);

                    try {
                        const response = await apiClient.request('get-all-message-ids.php', {});

                        if (!response.success || !response.data || response.data.length === 0) {
                            await message.reply(`⚠️ No message IDs found in database.\n\n👉 You need to upload and use !addmsgid first.`);
                            return;
                        }

                        let healthy = 0;
                        let broken = 0;
                        let brokenList = [];

                        for (const item of response.data) {
                            try {
                                const msg = await client.getMessageById(item.message_id);
                                if (msg && msg.hasMedia) {
                                    healthy++;
                                } else {
                                    broken++;
                                    const partLabel = item.part_number ? `part${item.part_number}` : 'full';
                                    brokenList.push(`${item.content_type} ${item.content_id} ${partLabel}`);
                                }
                            } catch (err) {
                                broken++;
                                const partLabel = item.part_number ? `part${item.part_number}` : 'full';
                                brokenList.push(`${item.content_type} ${item.content_id} ${partLabel}`);
                            }
                        }

                        let resultMsg = `🏥 *Full Health Check Complete*\n\n` +
                            `✅ Healthy: ${healthy}\n` +
                            `❌ Broken: ${broken}\n` +
                            `📊 Total: ${response.data.length}\n`;

                        if (broken > 0) {
                            resultMsg += `\n⚠️ *Need Re-upload:*\n`;
                            // Show max 10 broken items
                            const showList = brokenList.slice(0, 10);
                            resultMsg += showList.map(b => `• ${b}`).join('\n');
                            if (brokenList.length > 10) {
                                resultMsg += `\n... and ${brokenList.length - 10} more`;
                            }
                            resultMsg += `\n\n👉 Re-upload broken items and use !addmsgid`;
                        } else {
                            resultMsg += `\n🎉 All content is healthy!`;
                        }

                        await message.reply(resultMsg);

                    } catch (error) {
                        await message.reply(`❌ Health check failed: ${error.message}`);
                    }
                    return;
                }

                if (parts.length < 3) {
                    await message.reply(
                        `🏥 *Health Check Commands:*\n\n` +
                        `!checkhealth all - Check ALL linked content\n` +
                        `!checkhealth movie <id> - Check specific movie\n` +
                        `!checkhealth episode <id> - Check specific episode\n\n` +
                        `Example: !checkhealth all`
                    );
                    return;
                }

                const contentType = parts[1].toLowerCase();
                const contentId = parseInt(parts[2]);

                if (!['movie', 'episode'].includes(contentType) || isNaN(contentId)) {
                    await message.reply(`❌ Invalid format. Use: !checkhealth all or !checkhealth movie <id>`);
                    return;
                }

                await message.reply(`🔍 Checking health of ${contentType} ${contentId}...`);

                try {
                    // Get message IDs from API
                    const response = await apiClient.request('get-message-ids.php', {
                        content_type: contentType,
                        content_id: contentId
                    });

                    if (!response.success || !response.data || response.data.length === 0) {
                        await message.reply(`⚠️ No message IDs found for ${contentType} ${contentId}.\n\n👉 You need to upload and use !addmsgid first.`);
                        return;
                    }

                    let results = [];
                    let healthy = 0;
                    let broken = 0;

                    for (const item of response.data) {
                        const messageId = item.message_id;
                        const partLabel = item.part_number ? `Part ${item.part_number}` : 'Full';

                        try {
                            // Try to get the message
                            const msg = await client.getMessageById(messageId);
                            if (msg && msg.hasMedia) {
                                results.push(`✅ ${partLabel}: Working`);
                                healthy++;
                            } else {
                                results.push(`❌ ${partLabel}: Found but no media`);
                                broken++;
                            }
                        } catch (err) {
                            results.push(`❌ ${partLabel}: ${err.message.includes('not found') ? 'Expired/Deleted' : 'Error'}`);
                            broken++;
                        }
                    }

                    const status = broken === 0 ? '✅ All Healthy!' : `⚠️ ${broken} need re-upload`;
                    await message.reply(
                        `🏥 *Health Check: ${contentType} ${contentId}*\n\n` +
                        `${status}\n\n` +
                        `${results.join('\n')}\n\n` +
                        (broken > 0 ? `👉 Re-upload broken parts and use !addmsgid to fix` : `👍 All parts working!`)
                    );

                } catch (error) {
                    await message.reply(`❌ Health check failed: ${error.message}`);
                }
                return;
            }
        }

        // Add Message ID Command (Admin) - works in any storage group
        const isInAnyStorageGroup = chat.isGroup && (
            chat.id._serialized === STORAGE_GROUP_ID ||
            STORAGE_GROUP_IDS.includes(chat.id._serialized)
        );
        if (body.startsWith('!addmsgid ') && (isInAnyStorageGroup || isAdmin(senderNumber))) {
            await handleAddMessageId(message, chat);
            return;
        }

        // Main Token Request Handler
        if (body.startsWith(TOKEN_PREFIX.toLowerCase())) {
            const token = message.body.substring(TOKEN_PREFIX.length).trim().toUpperCase();

            if (!token) {
                await message.reply(`❌ Please provide a token.\n\nUsage: ${TOKEN_PREFIX} <TOKEN>\nExample: ${TOKEN_PREFIX} ABC123XYZ789`);
                return;
            }

            await handleTokenRequest(message, chat, token, senderNumber);
        }

    } catch (error) {
        console.error('Error handling message:', error);
        try {
            await message.reply('❌ An error occurred. Please try again later.');
        } catch (replyError) {
            console.error('Failed to send error reply:', replyError.message);
        }
    }
});

// Handle Help Command
async function handleHelpCommand(message, chat) {
    const helpText = `
🎬 *WhatsApp Video Bot*

*Commands:*
━━━━━━━━━━━━━━━━━
📥 *${TOKEN_PREFIX} <TOKEN>*
   Request a video using your unique token
   from zinema.lk

ℹ️ *!help*
   Show this help message

📊 *!queue*
   Check queue status
━━━━━━━━━━━━━━━━━

💡 *How it works:*
1. Visit zinema.lk and click "Watch via WhatsApp"
2. You'll receive a unique token (valid for 10 minutes)
3. The video will be forwarded to you

⚠️ *Notes:*
• Each token can only be used ONCE
• Tokens expire after 10 minutes
• Large files may take time to forward
    `.trim();

    try {
        await message.reply(helpText);
    } catch (error) {
        console.error('Failed to send help message:', error.message);
    }
}

// Handle Token Request with Queue
async function handleTokenRequest(message, chat, token, senderNumber) {
    // Validate token first
    const tokenResult = await getTokenData(token);

    if (!tokenResult.valid) {
        // Send appropriate error message
        let errorMsg = '';
        switch (tokenResult.error) {
            case 'not_found':
                errorMsg = `❌ Token *${token}* not found.\n\nPlease check your token and try again. Make sure you copied it correctly from zinema.lk`;
                break;
            case 'already_used':
                errorMsg = `❌ This token has already been used.\n\nEach token can only be used once. Please visit zinema.lk to get a new token.`;
                break;
            case 'expired':
                errorMsg = `❌ This token has expired.\n\nTokens are valid for 10 minutes only. Please visit and *Refresh* the zinema.lk to get a new token.`;
                break;
            case 'inactive':
                errorMsg = `❌ This token is no longer active.\n\nPlease visit zinema.lk to get a new token.`;
                break;
            case 'no_message_id':
                errorMsg = `❌ This video is not yet available via WhatsApp.\n\nPlease contact admin or try again later.`;
                break;
            default:
                errorMsg = `❌ Token validation failed.\n\nPlease try again or contact admin.`;
        }

        try {
            await message.reply(errorMsg);
        } catch (error) {
            console.error('Failed to send error reply:', error.message);
        }

        await logForward(
            tokenResult.data?.id || null,
            senderNumber,
            chat.id._serialized,
            'failed',
            tokenResult.error
        );
        return;
    }

    // Mark token as used IMMEDIATELY (before queuing)
    const tokenData = tokenResult.data;
    const marked = await markTokenAsUsed(tokenData.id);

    if (!marked) {
        await message.reply(`❌ This token was just used by someone else.\n\nPlease visit zinema.lk to get a new token.`);
        return;
    }

    console.log(`🎯 Token validated and marked: ${token} -> Message ID: ${tokenData.message_id}`);

    // Get content title from API response (already included in tokenData)
    const contentTitle = tokenData.title || tokenData.content_type;
    const partNumber = tokenData.part_number || null;
    const partInfo = partNumber ? ` (Part ${partNumber})` : '';

    // Send confirmation and add to queue
    const queueStatus = messageQueue.getStatus();
    let queueMsg = '';
    if (queueStatus.size > 0) {
        queueMsg = `\n⏳ *Queue Position: ${queueStatus.size + 1}*\n(There are ${queueStatus.size} requests ahead of you)`;
    }

    try {
        await chat.sendMessage(
            `✅ *Token Verified!*\n\n` +
            `🎬 ${contentTitle || tokenData.content_type}${partInfo}\n` +
            `📁 Preparing to send...\n` +
            queueMsg
        );
    } catch (error) {
        console.error('Failed to send confirmation:', error.message);
    }

    // Add forwarding task to queue
    messageQueue.add(async () => {
        await forwardVideo(message, chat, tokenData, senderNumber, contentTitle);
    }, { token, senderNumber });
}

// Forward Video (called from queue)
async function forwardVideo(message, chat, tokenData, senderNumber, contentTitle) {
    try {
        // ============================================
        // ULTIMATE SYSTEM: Use Multi-Tier Forwarder
        // ============================================
        if (global.multiTierForwarder) {
            console.log(`\n🚀 Using Ultimate System Multi-Tier Forwarder...`);

            const result = await global.multiTierForwarder.forwardVideo(
                chat.id._serialized,
                tokenData
            );

            if (result.success) {
                console.log(`✅ Video forwarded via Tier ${result.tier}: ${result.message}`);

                // Log success
                await logForward(tokenData.id, senderNumber, chat.id._serialized, 'success');
                if (healthMonitor) healthMonitor.updateDailyStats(true, false);

                // Send success message
                const partInfo = tokenData.part_number ? ` - Part ${tokenData.part_number}` : '';
                try {
                    await chat.sendMessage(
                        `✅ *${tokenData.part_number ? 'Part' : 'Video'} Sent successfully!*\n\n` +
                        `🎬 ${contentTitle || 'Video'}${partInfo}\n` +
                        `⚡ Delivered via Tier ${result.tier}\n` +
                        `💡 Enjoy your ${tokenData.part_number ? 'download!' : 'video!'}\n\n` +
                        `🌐 Visit zinema.lk for more movies!`
                    );
                } catch (error) {
                    console.error('Failed to send success message:', error.message);
                }
                return;
            } else if (result.queued) {
                // Video queued for nightly delivery (daytime restriction)
                console.log(`📋 Video queued for nightly delivery`);

                const partInfo = tokenData.part_number ? ` - Part ${tokenData.part_number}` : '';
                try {
                    await chat.sendMessage(
                        `⏰ *Video Queued for Tonight!*\n\n` +
                        `🎬 ${contentTitle || 'Video'}${partInfo}\n\n` +
                        `📋 This video is currently not in cache.\n` +
                        `🌙 It will be delivered to you automatically at *12:30 AM* tonight!\n\n` +
                        `💡 No action needed - just wait for your video.\n\n` +
                        `🌐 Visit zinema.lk for more movies!`
                    );
                } catch (error) {
                    console.error('Failed to send queued message:', error.message);
                }

                // Log as pending
                await logForward(tokenData.id, senderNumber, chat.id._serialized, 'queued');
                return;
            } else {
                // Ultimate System failed completely - show error to user
                console.log(`❌ Multi-Tier failed: ${result.error}`);

                // Log failure
                await logForward(tokenData.id, senderNumber, chat.id._serialized, 'failed', result.error);
                if (healthMonitor) healthMonitor.updateDailyStats(false, true);

                // Notify user
                const partInfo = tokenData.part_number ? ` - Part ${tokenData.part_number}` : '';
                try {
                    await chat.sendMessage(
                        `❌ *Video Not Available*\n\n` +
                        `🎬 ${contentTitle || 'Video'}${partInfo}\n\n` +
                        `📋 This video is not yet configured or unavailable.\n` +
                        `💡 Please try again later or contact support.\n\n` +
                        `🌐 Visit zinema.lk for assistance`
                    );
                } catch (error) {
                    console.error('Failed to send error message:', error.message);
                }
                return;
            }
        }

        // This code should never be reached if Ultimate System is initialized
        // But if multiTierForwarder is not available, show error
        console.error('⚠️ Ultimate System not initialized!');
        try {
            await chat.sendMessage(
                `❌ *System Error*\n\n` +
                `The video forwarding system is not properly initialized.\n` +
                `Please contact admin for assistance.`
            );
        } catch (error) {
            console.error('Failed to send error notification:', error.message);
        }

    } catch (error) {
        console.error('Error in forwardVideo:', error.message);

        // Log failure
        await logForward(tokenData.id, senderNumber, chat.id._serialized, 'failed', error.message);
        if (healthMonitor) healthMonitor.updateDailyStats(false, true);

        // Notify user
        try {
            await chat.sendMessage(
                `❌ *Failed to forward video*\n\n` +
                `Error: ${error.message}\n\n` +
                `Please contact admin for assistance.`
            );
        } catch (replyError) {
            console.error('Failed to send error notification:', replyError.message);
        }
    }
}

// Handle Add Message ID (Admin command)
async function handleAddMessageId(message, chat) {
    // Format 1: !addmsgid movie 123 true_xxx@g.us_yyy [filename]            (full text)
    // Format 2: !addmsgid movie 123                                         (reply to video)
    const parts = message.body.substring(10).trim().split(' ');
    const hasQuotedMsg = message.hasQuotedMsg;

    // Validation: 
    // If reply: needs 2 parts (type, id)
    // If text: needs 3 parts (type, id, msgid)
    if (parts.length < 2 || (!hasQuotedMsg && parts.length < 3)) {
        await message.reply(
            `❌ Invalid format.\n\n` +
            `*Option 1 (Reply to video):*\n` +
            `Reply to the video with: !addmsgid movie <id>\n\n` +
            `*Option 2 (Full text):*\n` +
            `!addmsgid movie <id> <message_id> [filename]\n\n` +
            `*Movie Part:*\n` +
            `!addmsgid movie <id> part1`
        );
        return;
    }

    const contentType = parts[0].toLowerCase();
    const contentId = parseInt(parts[1]);

    // Check if third argument is a part number (e.g., "part1", "part2")
    let partNumber = null;
    let messageIdIndex = 2;

    if (parts[2] && parts[2].toLowerCase().startsWith('part')) {
        const partMatch = parts[2].match(/^part(\d+)$/i);
        if (partMatch) {
            partNumber = parseInt(partMatch[1]);
            messageIdIndex = 3;
        }
    }

    // Get Message ID
    let messageId = null;
    let fileName = null;

    if (hasQuotedMsg) {
        // Get from reply
        const quotedMsg = await message.getQuotedMessage();
        messageId = quotedMsg.id._serialized;
        // Try to get filename from quoted message
        fileName = quotedMsg._data?.filename || quotedMsg.body || null;

        // If user provided a filename in text, overwrite it
        const userProvidedFilename = parts.slice(messageIdIndex).join(' ');
        if (userProvidedFilename) fileName = userProvidedFilename;

    } else {
        // Get from text
        messageId = parts[messageIdIndex];
        fileName = parts.slice(messageIdIndex + 1).join(' ') || null;
    }

    if (!messageId) {
        await message.reply(`❌ Missing message ID.`);
        return;
    }

    if (!['movie', 'episode'].includes(contentType)) {
        await message.reply(`❌ Invalid content type. Must be 'movie' or 'episode'.`);
        return;
    }

    if (isNaN(contentId) || contentId <= 0) {
        await message.reply(`❌ Invalid content ID. Must be a positive number.`);
        return;
    }

    if (partNumber !== null && partNumber <= 0) {
        await message.reply(`❌ Invalid part number. Must be a positive number (e.g., part1, part2).`);
        return;
    }

    try {
        // Use API to save message ID to remote database
        const result = await addMessageId(contentType, contentId, messageId, fileName, partNumber);

        if (result.success) {
            if (partNumber) {
                await message.reply(
                    `✅ Movie Part saved (via API)!\n\n` +
                    `📝 Type: ${contentType}\n` +
                    `🆔 Content ID: ${contentId}\n` +
                    `📦 Part: ${partNumber}\n` +
                    `📎 Message ID: ${messageId}\n` +
                    `📁 File: ${fileName || 'N/A'}`
                );
            } else {
                await message.reply(
                    `✅ Message ID saved (via API)!\n\n` +
                    `📝 Type: ${contentType}\n` +
                    `🆔 Content ID: ${contentId}\n` +
                    `📎 Message ID: ${messageId}\n` +
                    `📁 File: ${fileName || 'N/A'}`
                );
            }
        } else {
            await message.reply(`❌ Failed to save: ${result.error}`);
        }
    } catch (error) {
        await message.reply(`❌ Failed to save: ${error.message}`);
    }
}

// Note: isAdmin is already defined at the top of the file (line 31)

// Graceful Shutdown
process.on('SIGINT', async () => {
    console.log('\n🛑 Shutting down gracefully...');
    await client.destroy();
    process.exit(0);
});

process.on('SIGTERM', async () => {
    console.log('\n🛑 Received SIGTERM, shutting down...');
    await client.destroy();
    process.exit(0);
});

// Initialize the bot
console.log('═══════════════════════════════════════════════════════════');
console.log('  🤖  WhatsApp Video Forwarder Bot (Queue System)           ');
console.log('  📦  Using whatsapp-web.js (100% Free)                     ');
console.log('  🔄  High-Traffic Ready with Rate Limiting                 ');
console.log('═══════════════════════════════════════════════════════════\n');
console.log('⏳ Initializing WhatsApp client...\n');

client.initialize();

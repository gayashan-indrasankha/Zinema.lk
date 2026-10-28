// Fail before loading Baileys, authentication stores or timers.
const recoveryConfig = require('./config/environment').readConfig();
require('./config/environment').assertLiveWhatsAppEnabled(recoveryConfig);
const workerLog=require('./config/safe-logger').install(console);
const workerLease=require('./config/worker-lease').acquire(recoveryConfig.sessionPath);
let shuttingDown=false,shutdownPromise=null;
let pairingReviewTimer=null;

/**
 * WhatsApp Video Forwarder Bot with Baileys
 * Reliable 200MB+ file streaming using Baileys socket protocol
 * 
 * Key Features:
 * - 4-Tier forwarding system (Hot cache → Cache group → Local disk →Queue)
 * - Large file streaming via fs.createReadStream (no memory issues)
 * - Request coalescing for efficiency
 * - Night-time upload restrictions (12:30 AM - 6:00 AM)
 */

const makeWASocket = require('@whiskeysockets/baileys').default;
const { useMultiFileAuthState, DisconnectReason, delay, fetchLatestBaileysVersion, Browsers, downloadMediaMessage } = require('@whiskeysockets/baileys');
const pino = require('pino');
const fs = require('fs');
const path = require('path');

// Try to import makeInMemoryStore - it might not exist in all versions
let makeInMemoryStore;
try {
    makeInMemoryStore = require('@whiskeysockets/baileys').makeInMemoryStore;
} catch (e) {
    console.log('⚠️ makeInMemoryStore not available in this Baileys version, using fallback');
}

// Process environment is explicit; preserved dotenv files are never auto-loaded.

// API Client for database access
const {
    testConnection,
    logForward,
    addMessageId,
    needsRefresh,
    logRefresh,
    getRefreshStats
} = require('./config/api-client');

const HealthMonitor = require('./config/health-monitor');
const apiClient = require('./config/api-client');
const FileManager = require('./config/file-manager');
const MultiTierForwarder = require('./config/multi-tier-forwarder');
const CacheWarmer = require('./config/cache-warmer');
const AutoRefresher = require('./config/auto-refresher');

// Configuration
const BOT_INSTANCE_ID = recoveryConfig.botInstanceId;
const SESSION_NAME = `bot${BOT_INSTANCE_ID}`;
const LOCAL_MEDIA_PATH = recoveryConfig.mediaPath;
const STORAGE_GROUP_ID = process.env.STORAGE_GROUP_ID;
const TOKEN_PREFIX = process.env.TOKEN_PREFIX || '!get';
const ADMIN_NUMBERS = (process.env.ADMIN_NUMBERS || '').split(',').filter(n => n);

const STORAGE_GROUP_IDS = (process.env.STORAGE_GROUP_IDS || STORAGE_GROUP_ID || '')
    .split(',')
    .map(id => id.trim())
    .filter(id => id);

// ============================================
// BAD MAC AUTO-RECOVERY SYSTEM
// ============================================
// Track Bad MAC errors per sender for auto-recovery
const badMacErrors = {};
const BAD_MAC_THRESHOLD = 3; // Clear session after this many consecutive errors
const BAD_MAC_COOLDOWN = 5 * 60 * 1000; // 5 minute cooldown before retry
const SESSION_PATH = recoveryConfig.sessionPath;

/**
 * Preserve corrupted-session evidence for explicit operator review.
 * Automatic reconnect/error handling must not delete authentication state.
 */
function clearCorruptedSenderSession(_senderNumber) {
    console.error('Session repair requires operator review; all session evidence retained.');
    return false;
}

/**
 * Handle Bad MAC errors - track and trigger recovery when threshold is reached
 */
function handleBadMacError(senderNumber) {
    if (!senderNumber) return;

    const now = Date.now();

    // Initialize tracking for this sender if needed
    if (!badMacErrors[senderNumber]) {
        badMacErrors[senderNumber] = {
            count: 0,
            firstError: now,
            lastRecovery: 0
        };
    }

    const tracker = badMacErrors[senderNumber];

    // Check if we're in cooldown period after a recovery
    if (tracker.lastRecovery && (now - tracker.lastRecovery) < BAD_MAC_COOLDOWN) {
        console.log(`   ⏳ Sender ${senderNumber} in cooldown until ${new Date(tracker.lastRecovery + BAD_MAC_COOLDOWN).toLocaleTimeString()}`);
        return;
    }

    // Increment error count
    tracker.count++;
    console.log(`⚠️ Bad MAC error #${tracker.count}/${BAD_MAC_THRESHOLD} for sender ${senderNumber}`);

    // Check if we've hit the threshold
    if (tracker.count >= BAD_MAC_THRESHOLD) {
        console.log(`🚨 Threshold reached! Triggering auto-recovery for ${senderNumber}...`);

        if (clearCorruptedSenderSession(senderNumber)) {
            // Reset counter and set cooldown
            tracker.count = 0;
            tracker.lastRecovery = now;
            console.log(`✅ Recovery complete. Session will be re-established automatically.`);
        }
    }
}

/**
 * Parse stderr/log output to detect Bad MAC errors and extract sender
 * Called from the custom logger hook
 */
function parseBadMacError(message) {
    if (!message || typeof message !== 'string') return null;

    // Look for "Bad MAC" in the message
    if (!message.includes('Bad MAC')) return null;

    // Try to extract sender ID from the message or stack trace
    // The sender ID appears in the async queue name like "37495366524979.0"
    const senderMatch = message.match(/(\d{10,20})\.(\d+)\s*\[as awaitable\]/);
    if (senderMatch) {
        return senderMatch[1];
    }

    // Alternative: extract from session_cipher path
    const altMatch = message.match(/session-(\d{10,20})/);
    if (altMatch) {
        return altMatch[1];
    }

    return null;
}

// Intercept console.error to catch Bad MAC errors
const originalConsoleError = console.error;
console.error = function (...args) {
    // Call original console.error
    originalConsoleError.apply(console, args);

    // Check each argument for Bad MAC errors
    for (const arg of args) {
        const str = typeof arg === 'string' ? arg : (arg?.message || arg?.toString?.() || '');
        const sender = parseBadMacError(str);
        if (sender) {
            handleBadMacError(sender);
        }
    }
};

// Also intercept console.log for "Session error:" messages from libsignal
const originalConsoleLog = console.log;
console.log = function (...args) {
    // Call original console.log
    originalConsoleLog.apply(console, args);

    // Check for Session error: messages that contain Bad MAC
    if (args.length > 0) {
        const firstArg = String(args[0]);
        if (firstArg.includes('Session error:') || firstArg.includes('Failed to decrypt')) {
            // Combine all args into a single string for parsing
            const fullMessage = args.map(a => String(a)).join(' ');
            const sender = parseBadMacError(fullMessage);
            if (sender) {
                handleBadMacError(sender);
            }
        }
    }
};

// Also watch for unhandled errors that might contain Bad MAC
process.on('warning', (warning) => {
    const sender = parseBadMacError(warning.message);
    if (sender) {
        handleBadMacError(sender);
    }
});

// ============================================
// END BAD MAC AUTO-RECOVERY
// ============================================

// Global instances
let sock = null;
let activeSocket=null,workerReady=false;
let store = null;

// Initialize store if available
if (makeInMemoryStore) {
    store = makeInMemoryStore({ logger: pino({ level: 'silent' }) });
} else {
    // Fallback: Create basic store object
    store = {
        messages: {},
        bind: () => { },
        loadMessage: () => null,
        readFromFile: () => { },
        writeToFile: () => { }
    };
    console.log('⚠️ Using basic store fallback (Tier 1 disabled)');
}

// Make store globally accessible for CacheWarmer
global.store = store;

let healthMonitor = null;
let fileManager = null;
let multiTierForwarder = null;
let cacheWarmer = null;
let autoRefresher = null;
let storeSaveTimer = null;
const ReconnectController = require('./config/reconnect-controller');
const reconnectController = new ReconnectController(connectToWhatsApp, {
    onExhausted: () => console.error('Reconnect budget exhausted; session retained for operator review.'),
    onError: () => console.error('Reconnect failed; retry budget retained.')
});

// Message deduplication cache
const processedMessages = new Set();
const MAX_PROCESSED_MESSAGES = 1000;

// Helper functions
function isAdmin(phoneNumber) {
    return require('./config/admin-identity').isAdminIdentity(phoneNumber, ADMIN_NUMBERS);
}

function extractGroupFromMessageId(messageId) {
    if (!messageId) return null;
    const match = messageId.match(/true_(\\d+@g\\.us)/);
    return match ? match[1] : null;
}

// Message Queue for rate limiting
const MessageQueue = require('./config/message-queue');

const messageQueue = new MessageQueue({maxQueueSize:30});
global.messageQueue = messageQueue;

/**
 * Main Baileys Connection Function
 */
async function connectToWhatsApp() {
    if(shuttingDown)return;
    const sessionPath = recoveryConfig.sessionPath;

    // Ensure session directory exists
    if (!fs.existsSync(sessionPath)) {
        fs.mkdirSync(sessionPath, { recursive: true });
    }

    console.log(`📁 Session folder: whatsapp-session/${SESSION_NAME}`);

    const { state, saveCreds } = await useMultiFileAuthState(sessionPath);

    // Pairing belongs to an explicitly reviewed separate owner operation.
    // Never let the normal delivery worker turn a fresh/fake store into a login.
    if(state?.creds?.registered!==true||state.creds.zinemaLocalFake===true) {
        workerLog.event('pairing_required');await shutdownWorker(1);return;
    }
    const pairingReview=require('./config/pairing-approval').assertReviewedPairing(state,sessionPath);
    if(pairingReviewTimer)clearTimeout(pairingReviewTimer);
    pairingReviewTimer=setTimeout(()=>{pairingReviewTimer=null;workerLog.event('pairing_required');void shutdownWorker(1);},Math.max(1,pairingReview.validUntil-Date.now()));

    const { version } = await fetchLatestBaileysVersion();
    if(shuttingDown)return;

    sock = makeWASocket({
        version,
        logger: pino({ level: 'silent' }),
        printQRInTerminal: false,
        browser: Browsers.ubuntu('Chrome'),
        auth: state
    });

    const connectionSocket = sock;
    // Bind store to socket
    store.bind(sock.ev);

    // Load store from file if exists
    const storePath = path.join(sessionPath, 'store.json');
    if (fs.existsSync(storePath)) {
        store.readFromFile(storePath);
    }

    // Save store periodically
    if (storeSaveTimer) clearInterval(storeSaveTimer);
    storeSaveTimer = setInterval(() => {
        store.writeToFile(storePath);
    }, 10000);

    // Save credentials when updated
    sock.ev.on('creds.update', saveCreds);

    /**
     * Helper: Fetch messages from a chat (Baileys compatible)
     * Tries in-memory store first, then falls back to store.loadMessages (persistent DB)
     */
    sock.fetchMessagesFromChat = async (jid, count = 50) => {
        try {
            console.log(`🔍 fetchMessagesFromChat: Querying ${jid} for last ${count} messages`);

            // Check if store has messages in memory
            if (store && store.messages && store.messages[jid]) {
                const allMessages = store.messages[jid];
                let messages = [];

                // Store structure varies - handle both array and object
                if (Array.isArray(allMessages)) {
                    messages = allMessages.slice(-count);
                } else if (allMessages.array) {
                    messages = allMessages.array.slice(-count);
                } else {
                    messages = Object.values(allMessages).slice(-count);
                }

                if (messages.length > 0) {
                    console.log(`📦 Returning ${messages.length} messages from in-memory store`);
                    return messages;
                }
            }

            // In-memory store is empty — try store.loadMessages (reads from Baileys' persistent DB)
            if (store && typeof store.loadMessages === 'function') {
                try {
                    console.log(`🔄 In-memory store empty, trying store.loadMessages for ${jid}...`);
                    const loaded = await store.loadMessages(jid, count, undefined, sock);
                    if (loaded && loaded.messages && loaded.messages.length > 0) {
                        console.log(`📦 Returning ${loaded.messages.length} messages from store.loadMessages`);
                        return loaded.messages;
                    }
                    if (Array.isArray(loaded) && loaded.length > 0) {
                        console.log(`📦 Returning ${loaded.length} messages from store.loadMessages`);
                        return loaded;
                    }
                } catch (loadErr) {
                    console.log(`⚠️ store.loadMessages failed: ${loadErr.message}`);
                }
            }

            console.log(`⚠️ Store has no messages for this chat`);
            return [];
        } catch (err) {
            console.error(`❌ Error fetching messages for ${jid}:`, err.message);
            return [];
        }
    };

    // Connection updates (QR, status, errors)


    sock.ev.on('connection.update', async (update) => {
        if (shuttingDown || sock !== connectionSocket) return;
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            workerLog.event('pairing_required');
            await shutdownWorker(1);return;
        }

        if (connection === 'close') {
            activeSocket=null;workerReady=false;workerLog.event('connection_closed');
            const statusCode = lastDisconnect?.error?.output?.statusCode;
            const errorMessage = lastDisconnect?.error?.message || 'Unknown';

            console.log(`❌ Connection closed: ${errorMessage} (Code: ${statusCode})`);

            // Notify multiTierForwarder that socket is gone so it can pause waiters
            if (multiTierForwarder) {
                multiTierForwarder.socketReady = false;
            }

            if (storeSaveTimer) {clearInterval(storeSaveTimer);storeSaveTimer=null;}
            if (healthMonitor) healthMonitor.stop();
            if (cacheWarmer) cacheWarmer.socketReady=false;
            if (autoRefresher) autoRefresher.socketReady=false;
            reconnectController.disconnected(statusCode !== DisconnectReason.loggedOut);
        } else if (connection === 'open') {
            if(activeSocket===connectionSocket)return;
            activeSocket=connectionSocket;workerReady=false;
            reconnectController.connected();
            await onConnectionOpen(connectionSocket);
        }
    });

    // Message events - WITH DEBUG LOGGING
    sock.ev.on('messages.upsert', async ({ messages, type }) => {
        if (shuttingDown || !workerReady || sock !== connectionSocket) return;
        console.log(`🔔 Message event: type=${type}, count=${messages.length}`);
        if (type !== 'notify') return;

        for (const msg of messages) {
            console.log(`📨 Processing message from: ${msg.key.remoteJid}`);
            await handleMessage(msg);
        }
    });
}

/**
 * Called when connection is established
 */
async function onConnectionOpen(connectionSocket) {
    const current=()=>!shuttingDown && activeSocket===connectionSocket && sock===connectionSocket;
    if(!current())return;
    console.log('╔════════════════════════════════════════════════════════╗');
    console.log('║  ✅ WhatsApp Bot READY! (Baileys + Large File Support) ║');
    console.log('╠════════════════════════════════════════════════════════╣');
    console.log(`║  📌 Logged in as: ${sock.user?.name || 'Unknown'}`);
    console.log(`║  📞 Phone: ${sock.user?.id.split(':')[0] || 'Unknown'}`);
    console.log('║  💡 Commands:                                           ║');
    console.log(`║     ${TOKEN_PREFIX} <TOKEN> - Get video with token       ║`);
    console.log('║     !help - Show help message                           ║');
    console.log('║     !queue - Show queue status                          ║');
    console.log('╚════════════════════════════════════════════════════════╝\\n');

    const bridgeReady=await testConnection();
    if(!current())return;
    if(bridgeReady!==true){workerLog.event('worker_error');await shutdownWorker(1);return;}

    // Display storage groups
    if (STORAGE_GROUP_IDS.length > 0) {
        console.log(`📦 Storage Groups (${STORAGE_GROUP_IDS.length}):`);
        for (const groupId of STORAGE_GROUP_IDS) {
            console.log(`   • ${groupId}`);
        }
    } else {
        console.log('\\n⚠️  No storage groups configured!');
        console.log('📝 Send \"!groupid\" in your storage group to get the ID.\\n');
    }

    // Initialize Ultimate System (or hot-swap sockets on reconnect)
    try {
        const cacheGroupId = STORAGE_GROUP_IDS[0] || STORAGE_GROUP_ID || null;

        // If components already exist, just update their socket references
        // This preserves in-flight warm/refresh processes across reconnections
        if (multiTierForwarder && cacheWarmer && autoRefresher) {
            console.log(`🔌 Reconnected! Updating socket references on existing components...`);
            multiTierForwarder.updateSocket(sock);  // properly notifies reconnect waiters
            multiTierForwarder.setStore(store);
            global.multiTierForwarder = multiTierForwarder;
            cacheWarmer.updateSocket(sock);
            autoRefresher.updateSocket(sock);
            console.log(`✅ All components updated with new socket`);
        } else {
            // First connection — initialize everything
            fileManager = new FileManager(LOCAL_MEDIA_PATH);
            global.fileManager = fileManager;
            console.log(`📁 File Manager initialized: ${LOCAL_MEDIA_PATH}`);

            multiTierForwarder = new MultiTierForwarder(sock, fileManager, apiClient, cacheGroupId, STORAGE_GROUP_IDS);
            multiTierForwarder.setStore(store);
            global.multiTierForwarder = multiTierForwarder;

            autoRefresher = new AutoRefresher(sock, apiClient, cacheGroupId, STORAGE_GROUP_IDS);
            autoRefresher.start();
            console.log(`🚀 Multi-Tier Forwarder & Auto-Refresher initialized`);

            const files = await fileManager.listAllFiles();
            if(!current())return;
            console.log(`📊 Local files available: ${files.length}`);

            cacheWarmer = new CacheWarmer(sock, fileManager, cacheGroupId);
            cacheWarmer.setApiClient(apiClient);
            autoRefresher.setWarmer(cacheWarmer);
            await cacheWarmer.start(false);
            if(!current()){cacheWarmer.stop();autoRefresher.stop();return;}
            console.log('Cache schedules installed; startup uploads remain disabled.');
        }

        // Preload cache group messages for Tier 2 forwarding
        if (STORAGE_GROUP_IDS.length > 0) {
            console.log(`📥 Preloading cache group messages for Tier 2...`);
            for (const groupId of STORAGE_GROUP_IDS) {
                if(!current())return;
                try {
                    // Send presence to trigger message sync
                    await sock.sendPresenceUpdate('available', groupId);
                    await delay(1000);

                    // Try to read the group chat history
                    // This populates the store automatically via the event listener
                    await sock.chatModify({ markRead: false }, groupId);

                    console.log(`✅ Triggered message sync for ${groupId}`);
                } catch (err) {
                    console.log(`⚠️ Could not preload from ${groupId}: ${err.message}`);
                }
            }
            console.log(`📥 Cache groups synced. Tier 2 should work after messages load.`);
        }

    } catch (err) {
        console.error(`❌ Failed to initialize Ultimate System: ${err.message}`);
        await shutdownWorker(1);return;
    }

    // Start Health Monitor
    if(!current() || !multiTierForwarder || !cacheWarmer || !autoRefresher)return;
    workerReady=true;workerLog.event('connection_open');
    const BOT_ID = parseInt(BOT_INSTANCE_ID) || 1;
    if (healthMonitor) healthMonitor.stop();
    healthMonitor = new HealthMonitor(BOT_ID, apiClient, sock);
    healthMonitor.start(30);
    console.log(`💓 Health monitoring started for Bot #${BOT_ID}`);
}

/**
 * Handle incoming messages - FIXED FOR BAILEYS
 */
async function handleMessage(msg) {
    try {
        // Skip messages sent by the bot itself
        if (msg.key.fromMe) return;

        // Skip duplicate message delivery
        if (msg.key.id) {
            if (processedMessages.has(msg.key.id)) {
                console.log(`🚫 Skipping already processed message: ${msg.key.id}`);
                return;
            }
            processedMessages.add(msg.key.id);
            // Prune cache if it gets too large
            if (processedMessages.size > MAX_PROCESSED_MESSAGES) {
                const firstItem = processedMessages.values().next().value;
                processedMessages.delete(firstItem);
            }
        }

        // Skip status broadcasts
        if (msg.key.remoteJid === 'status@broadcast') return;

        // Extract message content - FIXED for Baileys structure
        // unwrapping to handle ephemeral/viewOnce
        let message = msg.message;
        if (message?.ephemeralMessage) {
            message = message.ephemeralMessage.message;
        }
        if (message?.viewOnceMessage) {
            message = message.viewOnceMessage.message;
        }
        if (message?.viewOnceMessageV2) {
            message = message.viewOnceMessageV2.message;
        }

        if (!message) {
            console.log('⚠️ No message content after unwrapping');
            return;
        }

        // Handle different Baileys message types
        let body = null;
        if (message.conversation) {
            body = message.conversation;
        } else if (message.extendedTextMessage) {
            body = message.extendedTextMessage.text;
        } else if (message.imageMessage?.caption) {
            body = message.imageMessage.caption;
        } else if (message.videoMessage?.caption) {
            body = message.videoMessage.caption;
        } else if (message.documentMessage?.caption) {
            body = message.documentMessage.caption;
        }

        if (!body || body.trim() === '') {
            console.log('⚠️ No text content in message');
            return;
        }

        const bodyLower = body.trim().toLowerCase();
        const chatId = msg.key.remoteJid;
        const isGroup = chatId.endsWith('@g.us');
        const senderId = msg.key.participant || msg.key.remoteJid;
        const senderNumber = senderId.split('@')[0].split(':')[0];
        const senderName = msg.pushName || senderNumber;

        console.log('Processing text command; content suppressed.');

        // Help command
        if (bodyLower === '!help') {
            console.log('✅ Help command matched');
            await sendMessage(chatId,
                `🤖 *WhatsApp Video Bot Commands*\n\n` +
                `📥 *Get Video:*\n${TOKEN_PREFIX} <TOKEN> - Request video\n\n` +
                `📊 *Info:*\n!queue - Show queue status\n!help - This message\n\n` +
                `${isGroup && isAdmin(senderNumber) ? '👑 *Admin Commands:*\n!groupid - Get group ID\n!msgid - Get message ID (reply to message)\n!stats - Show bot statistics\n!process - Manually trigger nightly process\n!refresh - Manually trigger auto-refresh cycle\n' : ''}` +
                `🌐 Visit: zinema.lk`
            );
            return;
        }

        // Queue status
        if (bodyLower === '!queue') {
            const status = messageQueue.getStatus();
            await sendMessage(chatId,
                `📊 *Queue Status*\n\n📥 Pending: ${status.size}\n⚙️ Processing: ${status.isProcessing ? 'Yes' : 'No'}`
            );
            return;
        }

        // Comprehensive status command
        if (bodyLower === '!status') {
            let statusMsg = `📊 *Bot System Status*\n\n`;

            // Message Queue
            const queueStatus = messageQueue.getStatus();
            statusMsg += `📥 *Message Queue:* ${queueStatus.size} pending\n`;

            // Upload Queue (if available)
            if (multiTierForwarder?.uploadQueue) {
                const uploadStatus = multiTierForwarder.uploadQueue.getStatus();
                statusMsg += `📤 *Upload Queue:* ${uploadStatus.queueLength} pending, ${uploadStatus.activeCount}/${uploadStatus.maxConcurrent} active\n`;
                statusMsg += `   ↳ Processed: ${uploadStatus.stats.processed}, Failed: ${uploadStatus.stats.failed}\n`;
            }

            // Forwarder Stats
            if (multiTierForwarder) {
                const stats = multiTierForwarder.getStats();
                statusMsg += `\n📈 *Forwarder Stats:*\n`;
                statusMsg += `   Total: ${stats.totalRequests} | T1: ${stats.tier1Hits} | T2: ${stats.tier2Hits} | T3: ${stats.tier3Hits}\n`;
            }

            // Scheduled Jobs
            statusMsg += `\n⏰ *Scheduled Refresh:*\n`;
            statusMsg += `   📅 Proactive: Daily 3 AM\n`;
            statusMsg += `   📅 Weekly: Sunday 2 AM\n`;
            statusMsg += `   📅 Monthly: 1st at 4 AM\n`;

            // Local Files
            if (fileManager) {
                const files = await fileManager.listAllFiles();
                statusMsg += `\n📁 *Local Files:* ${files.length}`;
            }

            await sendMessage(chatId, statusMsg);
            return;
        }

        // Group ID command
        if (bodyLower === '!groupid' && isGroup) {
            await sendMessage(chatId,
                `📋 *Group ID:*\n\`${chatId}\`\n\nAdd this to your .env file as STORAGE_GROUP_ID`
            );
            return;
        }

        // Message ID command
        if (bodyLower === '!msgid' && msg.message?.extendedTextMessage?.contextInfo?.quotedMessage) {
            const quotedMsgId = msg.message.extendedTextMessage.contextInfo.stanzaId;
            await sendMessage(chatId,
                `📋 *Message ID:*\n\`${quotedMsgId}\`\n\nUse this with !addtoken command`
            );
            return;
        }

        // Token request (!get TOKEN)
        if (bodyLower.startsWith(TOKEN_PREFIX.toLowerCase())) {
            const parts = body.trim().split(/\s+/);
            const token = parts[1];

            console.log('Token command received; values suppressed.');

            if (!token) {
                await sendMessage(chatId, `⚠️ Usage: ${TOKEN_PREFIX} <TOKEN>`);
                return;
            }

            // Add to queue
            const queued = messageQueue.add(async () => {
                await handleTokenRequest(chatId, token.toUpperCase(), senderNumber);
            }, { type: 'video_request', token });
            if (!queued) await sendMessage(chatId, 'Service is busy. Please try again later.');

            return;
        }

        // Admin-only commands
        if (isAdmin(senderNumber)) {
            // Stats command
            if (bodyLower === '!stats' && multiTierForwarder) {
                const stats = multiTierForwarder.getStats();
                await sendMessage(chatId,
                    `📊 *Bot Statistics*\n\n` +
                    `📈 Total Requests: ${stats.totalRequests}\n` +
                    `⚡ Tier 1 (Hot): ${stats.tier1Hits} (${stats.tier1Rate})\n` +
                    `🔥 Tier 2 (Cache): ${stats.tier2Hits} (${stats.tier2Rate})\n` +
                    `📁 Tier 3 (Disk): ${stats.tier3Hits} (${stats.tier3Rate})\n` +
                    `🌙 Tier 4 (Queued): ${stats.tier4Queued} (${stats.queuedRate})\n\n` +
                    `⏳ Pending: ${stats.pendingVideos} videos, ${stats.pendingUsers} users\n` +
                    `🌙 Night Mode: ${stats.isNightTime ? 'Active ✅' : 'Inactive (6AM-12:30AM)'}`
                );
                return;
            }

            // Manual Refresh Command
            // Usage: !refresh OR !refresh movie 96-100 OR !refresh episode 1-50
            if (bodyLower.startsWith('!refresh')) {
                console.log(`✅ !refresh command matched, autoRefresher=${!!autoRefresher}`);
                const parts = body.trim().split(/\s+/);

                if (parts.length === 1) {
                    // Full refresh cycle
                    await sendMessage(chatId, '🔄 *Manual Auto-Refresh triggered...*');
                    if (autoRefresher) {
                        autoRefresher.runRefreshCycle(chatId);
                    } else {
                        await sendMessage(chatId, `❌ Auto Refresher not initialized`);
                    }
                    return;
                }

                if (parts.length >= 3) {
                    const contentType = parts[1].toLowerCase(); // movie or episode
                    const range = parts[2]; // e.g., 96-100

                    if (!['movie', 'episode'].includes(contentType)) {
                        await sendMessage(chatId, `❌ Invalid type. Use: movie or episode\n\nExamples:\n!refresh movie 96-100\n!refresh episode 1-50\n!refresh (full cycle)`);
                        return;
                    }

                    const rangeParts = range.split('-');
                    if (rangeParts.length !== 2) {
                        await sendMessage(chatId, `❌ Invalid range. Use: start-end\n\nExamples:\n!refresh movie 96-100`);
                        return;
                    }

                    const startId = parseInt(rangeParts[0]);
                    const endId = parseInt(rangeParts[1]);

                    if (isNaN(startId) || isNaN(endId) || startId > endId) {
                        await sendMessage(chatId, `❌ Invalid range. Start must be ≤ end.`);
                        return;
                    }

                    await sendMessage(chatId, `🔄 Refreshing ${contentType}s ${startId} to ${endId}...\nThis may take a while.`);

                    if (autoRefresher) {
                        autoRefresher.refreshIdRange(contentType, startId, endId, chatId);
                    } else {
                        await sendMessage(chatId, `❌ Auto Refresher not initialized`);
                    }
                    return;
                }

                await sendMessage(chatId, `🔄 *Refresh Commands:*\n\n!refresh - Full auto-refresh cycle\n!refresh movie 96-100 - Refresh movies 96 to 100\n!refresh episode 1-50 - Refresh episodes 1 to 50`);
                return;
            }

            // Manual Process command
            if (bodyLower === '!process' && multiTierForwarder) {
                await sendMessage(chatId, `🌙 *Starting manual nightly processing...*`);
                try {
                    const result = await multiTierForwarder.processPendingRequests();
                    await sendMessage(chatId, result.reason==='legacy_queue_review_required' ? 'Legacy deliveries are held for operator review; no queue was replayed.' : `Processed: ${result.processed}; failed: ${result.failed}`);
                } catch (err) {
                    await sendMessage(chatId, `❌ *Process Failed:* ${err.message}`);
                }
                return;
            }

            // Manual Cache Warming Command
            // Usage: !warm all OR !warm movie 1-50 OR !warm episode 100-200
            if (bodyLower.startsWith('!warm')) {
                console.log(`✅ !warm command matched, cacheWarmer=${!!cacheWarmer}`);
                const parts = body.trim().split(/\s+/);

                if (parts.length === 1 || (parts.length === 2 && parts[1].toLowerCase() === 'all')) {
                    // Warm all local files
                    await sendMessage(chatId, `🔥 Starting full cache warm...\nThis may take a while.`);
                    if (cacheWarmer) {
                        cacheWarmer.warmAllLocalFiles(false, true); // forceRefresh=false, ignoreTimeWindow=true
                    } else {
                        await sendMessage(chatId, `❌ Cache Warmer not initialized`);
                    }
                    return;
                }

                if (parts.length >= 3) {
                    const contentType = parts[1].toLowerCase(); // movie or episode
                    const range = parts[2]; // e.g., 1-50

                    if (!['movie', 'episode'].includes(contentType)) {
                        await sendMessage(chatId, `❌ Invalid type. Use: movie or episode\n\nExamples:\n!warm movie 1-50\n!warm episode 100-200\n!warm all`);
                        return;
                    }

                    const rangeParts = range.split('-');
                    if (rangeParts.length !== 2) {
                        await sendMessage(chatId, `❌ Invalid range. Use: start-end\n\nExamples:\n!warm movie 1-50`);
                        return;
                    }

                    const startId = parseInt(rangeParts[0]);
                    const endId = parseInt(rangeParts[1]);

                    if (isNaN(startId) || isNaN(endId) || startId > endId) {
                        await sendMessage(chatId, `❌ Invalid range. Start must be ≤ end.`);
                        return;
                    }

                    await sendMessage(chatId, `🔥 Warming ${contentType}s ${startId} to ${endId}...\nThis may take a while.`);

                    if (cacheWarmer) {
                        cacheWarmer.warmIdRange(contentType, startId, endId, true, chatId); // Pass chatId for progress updates
                    } else {
                        await sendMessage(chatId, `❌ Cache Warmer not initialized`);
                    }
                    return;
                }

                await sendMessage(chatId, `🔥 *Cache Warm Commands:*\n\n!warm all - Warm all local files\n!warm movie 1-50 - Warm movies 1 to 50\n!warm episode 100-200 - Warm episodes 100 to 200`);
                return;
            }
        }

    } catch (error) {
        console.error('Error handling message:', error.message);
    }
}

/**
 * Handle video token request
 */
async function handleTokenRequest(chatId, token, senderNumber) {
    try {
        const { deliverClaimedToken } = require('./config/token-delivery');
        const { claimToken, transitionToken } = require('./config/api-client');
        const result = await deliverClaimedToken({
            claim: claimToken, transition: transitionToken,
            forward: (recipient, data) => multiTierForwarder.forwardVideo(recipient, data)
        }, token, chatId);
        if (result.success) {
            await logForward(result.data.id, senderNumber, chatId, 'success', null);
            await sendMessage(chatId, 'Video delivered successfully.');
        } else {
            await sendMessage(chatId, result.status === 'unavailable' ?
                'Token unavailable. Request a new token on the website.' :
                'Delivery is held for review. Please contact support before retrying.');
        }
    } catch {
        console.error('Token delivery could not be confirmed; details suppressed.');
        await sendMessage(chatId, 'Delivery could not be confirmed. Please contact support before retrying.');
    }
}

/**
 * Send message helper
 */
async function sendMessage(jid, text, options = {}) {
    try {
        console.log('Sending response; content suppressed.');
        await sock.sendMessage(jid, { text }, options);
        console.log(`✅ Message sent successfully`);
    } catch (error) {
        console.error(`❌ Send message error to ${jid}:`, error.message);
        console.error('Send diagnostics suppressed.');
    }
}

async function shutdownWorker(exitCode=0) {
    if(shutdownPromise)return shutdownPromise;
    shuttingDown=true;workerReady=false;activeSocket=null;
    if(pairingReviewTimer){clearTimeout(pairingReviewTimer);pairingReviewTimer=null;}
    workerLog.event('shutdown_started');
    reconnectController.stop();messageQueue.stop();
    if(storeSaveTimer){clearInterval(storeSaveTimer);storeSaveTimer=null;}
    healthMonitor?.stop();cacheWarmer?.stop();autoRefresher?.stop();
    shutdownPromise=(async()=>{
        let deadline;
        try{await Promise.race([messageQueue.whenIdle(),new Promise(resolve=>{deadline=setTimeout(resolve,5000);})]);}
        finally{clearTimeout(deadline);}
        try{sock?.end?.(Error('Worker shutdown'));}catch{}
        try{workerLease.release();}catch{exitCode=1;}
        workerLog.event('shutdown_complete');process.exit(exitCode);
    })();
    return shutdownPromise;
}
for(const signal of ['SIGINT','SIGTERM'])process.once(signal,()=>{void shutdownWorker(0);});

// Failure details are not safe log data; preserve private session/claim evidence.
process.on('unhandledRejection', (reason) => {
    console.error('⚠️ Unhandled Rejection:', reason);

    // Check for Bad MAC errors
    const errorStr = reason?.message || reason?.toString?.() || String(reason);
    const sender = parseBadMacError(errorStr);
    if (sender) {
        handleBadMacError(sender);
    }
    void shutdownWorker(1);
});

process.on('uncaughtException', (error) => {
    console.error('⚠️ Uncaught Exception:', error.message);

    // Check for Bad MAC errors in the stack trace
    const fullError = error.stack || error.message;
    const sender = parseBadMacError(fullError);
    if (sender) {
        handleBadMacError(sender);
    }
    void shutdownWorker(1);
});

// Start the bot
console.log(`🤖 Bot Instance: #${BOT_INSTANCE_ID}`);
console.log(`🚀 Starting Baileys WhatsApp Bot...`);

connectToWhatsApp().catch(err => {
    console.error('Failed to connect:', err);
    void shutdownWorker(1);
});

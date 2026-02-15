/**
 * WhatsApp Video Forwarder Bot with Baileys
 * Reliable 200MB+ file streaming using Baileys socket protocol
 * 
 * Key Features:
 * - 4-Tier forwarding system (Hot cache → Cache group → Local disk → Queue)
 * - Large file streaming via fs.createReadStream (no memory issues)
 * - Request coalescing for efficiency
 * - Night-time upload restrictions (12:30 AM - 6:00 AM)
 */

const makeWASocket = require('@whiskeysockets/baileys').default;
const { useMultiFileAuthState, DisconnectReason, delay, fetchLatestBaileysVersion, Browsers, downloadMediaMessage } = require('@whiskeysockets/baileys');
const pino = require('pino');
const qrcode = require('qrcode-terminal');
const cliProgress = require('cli-progress');
const fs = require('fs');
const path = require('path');

// Load environment variables
const envFile = process.env.ENV_FILE || '.env';
require('dotenv').config({ path: envFile });
console.log(`📋 Loading config from: ${envFile}`);

// API Client for database access
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

const HealthMonitor = require('./config/health-monitor');
const apiClient = require('./config/api-client');
const FileManager = require('./config/file-manager');
const MultiTierForwarder = require('./config/multi-tier-forwarder');

// Configuration
const BOT_INSTANCE_ID = process.env.BOT_INSTANCE_ID || '1';
const SESSION_NAME = `bot${BOT_INSTANCE_ID}`;
const LOCAL_MEDIA_PATH = process.env.LOCAL_MEDIA_PATH || 'D:\\\\bot-media';
const STORAGE_GROUP_ID = process.env.STORAGE_GROUP_ID;
const TOKEN_PREFIX = process.env.TOKEN_PREFIX || '!get';
const ADMIN_NUMBERS = (process.env.ADMIN_NUMBERS || '').split(',').filter(n => n);

const STORAGE_GROUP_IDS = (process.env.STORAGE_GROUP_IDS || STORAGE_GROUP_ID || '')
    .split(',')
    .map(id => id.trim())
    .filter(id => id);

// Global instances
let sock = null;
let store = null;
let healthMonitor = null;
let fileManager = null;
let multiTierForwarder = null;

// Helper functions
function isAdmin(phoneNumber) {
    return ADMIN_NUMBERS.some(admin => phoneNumber.includes(admin));
}

function extractGroupFromMessageId(messageId) {
    if (!messageId) return null;
    const match = messageId.match(/true_(\\d+@g\\.us)/);
    return match ? match[1] : null;
}

// Message Queue for rate limiting
class MessageQueue {
    constructor() {
        this.queue = [];
        this.isProcessing = false;
        this.minDelay = 1500;
        this.maxDelay = 3000;
    }

    add(task, metadata = {}) {
        this.queue.push({ task, metadata, addedAt: Date.now() });
        console.log(`📥 Queue: Added task. Queue size: ${this.queue.length}`);
        this.process();
    }

    getRandomDelay() {
        return Math.floor(Math.random() * (this.maxDelay - this.minDelay + 1)) + this.minDelay;
    }

    async process() {
        if (this.isProcessing || this.queue.length === 0) return;
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

            if (this.queue.length > 0) {
                const delay = this.getRandomDelay();
                console.log(`⏳ Queue: Waiting ${delay / 1000}s before next task...`);
                await new Promise(resolve => setTimeout(resolve, delay));
            }
        }

        this.isProcessing = false;
        console.log(`✅ Queue: All tasks completed`);
    }

    getStatus() {
        return {
            size: this.queue.length,
            isProcessing: this.isProcessing
        };
    }
}

const messageQueue = new MessageQueue();

/**
 * Main Baileys Connection Function
 */
async function connectToWhatsApp() {
    const sessionPath = `./whatsapp-session/${SESSION_NAME}`;

    // Ensure session directory exists
    if (!fs.existsSync('./whatsapp-session')) {
        fs.mkdirSync('./whatsapp-session', { recursive: true });
    }

    console.log(`📁 Session folder: whatsapp-session/${SESSION_NAME}`);

    const { state, saveCreds } = await useMultiFileAuthState(sessionPath);

    const { version } = await fetchLatestBaileysVersion();

    sock = makeWASocket({
        version,
        logger: pino({ level: 'silent' }),
        printQRInTerminal: false,
        browser: Browsers.ubuntu('Chrome'),
        auth: state
    });

    // Save credentials when updated
    sock.ev.on('creds.update', saveCreds);

    // Connection updates (QR, status, errors)
    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            console.log('\\n📱 Scan this QR code with your WhatsApp:\\n');
            qrcode.generate(qr, { small: true });
            console.log('\\n⏳ Waiting for QR code scan...\\n');
        }

        if (connection === 'close') {
            const shouldReconnect = lastDisconnect?.error?.output?.statusCode !== DisconnectReason.loggedOut;
            console.log('❌ Connection closed:', lastDisconnect?.error?.message || 'Unknown');

            if (shouldReconnect) {
                console.log('🔄 Reconnecting in 5 seconds...');
                setTimeout(() => connectToWhatsApp(), 5000);
            } else {
                console.log('🔐 Logged out. Delete session folder and restart.');
            }
        } else if (connection === 'open') {
            await onConnectionOpen();
        }
    });

    // Message events
    sock.ev.on('messages.upsert', async ({ messages, type }) => {
        if (type !== 'notify') return;

        for (const msg of messages) {
            await handleMessage(msg);
        }
    });
}

/**
 * Called when connection is established
 */
async function onConnectionOpen() {
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

    await testConnection();

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

    // Initialize Ultimate System
    try {
        fileManager = new FileManager(LOCAL_MEDIA_PATH);
        global.fileManager = fileManager;
        console.log(`📁 File Manager initialized: ${LOCAL_MEDIA_PATH}`);

        const cacheGroupId = STORAGE_GROUP_IDS[0] || STORAGE_GROUP_ID || null;
        multiTierForwarder = new MultiTierForwarder(sock, fileManager, apiClient, cacheGroupId);
        global.multiTierForwarder = multiTierForwarder;
        console.log(`🚀 Multi-Tier Forwarder initialized (Baileys Streaming ACTIVE)`);

        const files = await fileManager.listAllFiles();
        console.log(`📊 Local files available: ${files.length}`);

    } catch (err) {
        console.error(`❌ Failed to initialize Ultimate System: ${err.message}`);
    }

    // Start Health Monitor
    const BOT_ID = parseInt(BOT_INSTANCE_ID) || 1;
    healthMonitor = new HealthMonitor(BOT_ID, apiClient, sock);
    healthMonitor.start(30);
    console.log(`💓 Health monitoring started for Bot #${BOT_ID}`);
}

/**
 * Handle incoming messages
 */
async function handleMessage(msg) {
    try {
        // Skip status broadcasts
        if (msg.key.remoteJid === 'status@broadcast') return;

        // Extract message content
        const messageType = Object.keys(msg.message || {})[0];
        const messageContent = msg.message?.[messageType];
        const body = messageContent?.text || messageContent?.conversation || messageContent?.caption || '';

        if (!body) return; // No text content

        const bodyLower = body.trim().toLowerCase();
        const chatId = msg.key.remoteJid;
        const isGroup = chatId.endsWith('@g.us');
        const senderId = msg.key.participant || msg.key.remoteJid;
        const senderNumber = senderId.split('@')[0].split(':')[0];
        const senderName = msg.pushName || senderNumber;

        console.log(`📩 [${new Date().toLocaleTimeString()}] From ${senderName}: ${body.substring(0, 50)}...`);

        // Help command
        if (bodyLower === '!help') {
            await sendMessage(chatId,
                `🤖 *WhatsApp Video Bot Commands*\\n\\n` +
                `📥 *Get Video:*\\n${TOKEN_PREFIX} <TOKEN> - Request video\\n\\n` +
                `📊 *Info:*\\n!queue - Show queue status\\n!help - This message\\n\\n` +
                `${isGroup && isAdmin(senderNumber) ? '👑 *Admin Commands:*\\n!groupid - Get group ID\\n!msgid - Get message ID (reply to message)\\n' : ''}` +
                `🌐 Visit: zinema.lk`
            );
            return;
        }

        // Queue status
        if (bodyLower === '!queue') {
            const status = messageQueue.getStatus();
            await sendMessage(chatId,
                `📊 *Queue Status*\\n\\n📥 Pending: ${status.size}\\n⚙️ Processing: ${status.isProcessing ? 'Yes' : 'No'}`
            );
            return;
        }

        // Group ID command
        if (bodyLower === '!groupid' && isGroup) {
            await sendMessage(chatId,
                `📋 *Group ID:*\\n\`${chatId}\`\\n\\nAdd this to your .env file as STORAGE_GROUP_ID`
            );
            return;
        }

        // Message ID command
        if (bodyLower === '!msgid' && msg.message?.extendedTextMessage?.contextInfo?.quotedMessage) {
            const quotedMsgId = msg.message.extendedTextMessage.contextInfo.stanzaId;
            await sendMessage(chatId,
                `📋 *Message ID:*\\n\`${quotedMsgId}\`\\n\\nUse this with !addtoken command`
            );
            return;
        }

        // Token request (!get TOKEN)
        if (bodyLower.startsWith(TOKEN_PREFIX.toLowerCase())) {
            const token = body.trim().split(/\\s+/)[1];

            if (!token) {
                await sendMessage(chatId, `⚠️ Usage: ${TOKEN_PREFIX} <TOKEN>`);
                return;
            }

            // Add to queue
            messageQueue.add(async () => {
                await handleTokenRequest(chatId, token.toUpperCase(), senderNumber);
            }, { type: 'video_request', token });

            return;
        }

        // Admin-only commands
        if (isAdmin(senderNumber)) {
            // Stats command
            if (bodyLower === '!stats' && multiTierForwarder) {
                const stats = multiTierForwarder.getStats();
                await sendMessage(chatId,
                    `📊 *Bot Statistics*\\n\\n` +
                    `📈 Total Requests: ${stats.totalRequests}\\n` +
                    `⚡ Tier 1 (Hot): ${stats.tier1Hits} (${stats.tier1Rate})\\n` +
                    `🔥 Tier 2 (Cache): ${stats.tier2Hits} (${stats.tier2Rate})\\n` +
                    `📁 Tier 3 (Disk): ${stats.tier3Hits} (${stats.tier3Rate})\\n` +
                    `🌙 Tier 4 (Queued): ${stats.tier4Queued} (${stats.queuedRate})\\n\\n` +
                    `⏳ Pending: ${stats.pendingVideos} videos, ${stats.pendingUsers} users\\n` +
                    `🌙 Night Mode: ${stats.isNightTime ? 'Active ✅' : 'Inactive (6AM-12:30AM)'}`
                );
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
        console.log(`\\n🎫 Processing token: ${token} for ${senderNumber}`);

        // Get token data from API
        const tokenResponse = await getTokenData(token);

        if (!tokenResponse.success) {
            await sendMessage(chatId, `❌ Invalid token: ${token}`);
            return;
        }

        const tokenData = tokenResponse.data;
        console.log(`✅ Token valid: ${tokenData.title || 'Unknown'}`);

        // Send processing message
        await sendMessage(chatId,
            `⏳ Processing your request...\\n📁 ${tokenData.title || token}\\n\\nPlease wait...`
        );

        // Use Multi-Tier Forwarder
        const result = await multiTierForwarder.forwardVideo(chatId, tokenData);

        if (result.success) {
            console.log(`✅ Video delivered via TIER ${result.tier}`);

            // Log to database
            await logForward(tokenData.id, senderNumber, chatId, 'success', null);

            // Send success message
            const tierName = {
                1: 'Hot Cache (Fast!)',
                2: 'Cache Group',
                3: 'Local Disk (Streamed)'
            }[result.tier] || 'Unknown';

            await sendMessage(chatId,
                `✅ *Video Delivered!*\\n\\n` +
                `🎬 ${tokenData.title || token}\\n` +
                `⚡ Via: ${tierName}\\n\\n` +
                `🌐 Visit zinema.lk for more!`
            );

        } else if (result.queued) {
            // Queued for night
            await sendMessage(chatId,
                `🌙 *Video Queued for Night Delivery*\\n\\n` +
                `📁 ${tokenData.title || token}\\n` +
                `⏰ Will be sent at 12:30 AM automatically\\n\\n` +
                `This prevents WhatsApp rate limits during daytime. Thank you for your patience!`
            );

            await logForward(tokenData.id, senderNumber, chatId, 'queued', 'Daytime restriction');

        } else {
            // Failed
            await sendMessage(chatId,
                `❌ *Video Unavailable*\\n\\n` +
                `${result.message || 'Please try again later.'}\\n\\n` +
                `Contact support if this persists.`
            );

            await logForward(tokenData.id, senderNumber, chatId, 'failed', result.error);
        }

    } catch (error) {
        console.error('Error processing token:', error.message);
        await sendMessage(chatId, `❌ Error: ${error.message}`);
    }
}

/**
 * Send message helper
 */
async function sendMessage(jid, text, options = {}) {
    try {
        await sock.sendMessage(jid, { text }, options);
    } catch (error) {
        console.error('Send message error:', error.message);
    }
}

// Global error handlers
process.on('unhandledRejection', (reason) => {
    console.error('⚠️ Unhandled Rejection:', reason);
});

process.on('uncaughtException', (error) => {
    console.error('⚠️ Uncaught Exception:', error.message);
});

// Start the bot
console.log(`🤖 Bot Instance: #${BOT_INSTANCE_ID}`);
console.log(`🚀 Starting Baileys WhatsApp Bot...`);

connectToWhatsApp().catch(err => {
    console.error('Failed to connect:', err);
    process.exit(1);
});

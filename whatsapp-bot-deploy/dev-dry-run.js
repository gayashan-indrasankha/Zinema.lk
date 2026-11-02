'use strict';
/**
 * Local dry-run bot (development only)
 * Uses the real API client + HealthMonitor against the local PHP API,
 * with a fake WhatsApp socket. Never connects to WhatsApp, never sends messages.
 *
 * Usage: node dev-dry-run.js
 * Then type "!get <TOKEN>" to simulate an incoming WhatsApp message.
 */
const fs = require('node:fs');
const path = require('node:path');
const readline = require('node:readline');

// Explicit local environment; production .env is never read.
const keyFile = path.join(__dirname, '.local-api-key');
if (!fs.existsSync(keyFile)) {
    console.error('❌ Missing .local-api-key (must match WHATSAPP_API_KEY in httpd-xampp.conf)');
    process.exit(1);
}
const runtimeDir = path.join(__dirname, 'dev-runtime');
Object.assign(process.env, {
    APP_ENV: 'development',
    API_URL: 'http://127.0.0.1/zinema/api/whatsapp',
    API_KEY: fs.readFileSync(keyFile, 'utf8').trim(),
    BOT_INSTANCE_ID: '1',
    SESSION_PATH: path.join(runtimeDir, 'session'),
    LOCAL_MEDIA_PATH: path.join(runtimeDir, 'media'),
    ALLOW_PRODUCTION_API: 'false',
    ALLOW_LIVE_WHATSAPP: 'false'
});

const recoveryConfig = require('./config/environment').readConfig();
if (recoveryConfig.liveWhatsApp) throw new Error('dry-run must not run with live WhatsApp');

const apiClient = require('./config/api-client');
const HealthMonitor = require('./config/health-monitor');

const TOKEN_PREFIX = '!get';
const FAKE_CHAT_ID = 'dry-run-user@local';
const FAKE_SENDER = 'dry-run-user';
const fakeSock = { info: { wid: { user: 'dry-run' } } };

function sendMessage(chatId, text) {
    console.log(`📤 [DRY-RUN → ${chatId}]\n${text}\n`);
}

// Mirrors handleTokenRequest() in index.js, with forwarding simulated.
async function handleTokenRequest(chatId, token, senderNumber) {
    console.log(`\n🎫 Processing token: ${token} for ${senderNumber}`);
    const tokenResponse = await apiClient.getTokenData(token);

    if (!tokenResponse.data) {
        sendMessage(chatId, `❌ Token ${token} not found / expired (${tokenResponse.error || 'unknown'})`);
        return;
    }
    if (tokenResponse.error === 'already_used') {
        sendMessage(chatId, `❌ Token ${token} already used`);
        return;
    }

    const tokenData = tokenResponse.data;
    if (!tokenResponse.valid) {
        sendMessage(chatId, `❌ Token ${token} invalid: ${tokenResponse.error}`);
        await apiClient.logForward(tokenData.id, senderNumber, chatId, 'failed', `dry-run: ${tokenResponse.error}`);
        return;
    }

    console.log(`🎬 [DRY-RUN] Would forward message_id=${tokenData.message_id} (${tokenData.file_name || 'no file name'}) "${tokenData.title}"`);
    await apiClient.markTokenAsUsed(tokenData.id);
    await apiClient.logForward(tokenData.id, senderNumber, chatId, 'success', null);
    await apiClient.updateDailyStats(recoveryConfig.botInstanceId, 1, 0);
    sendMessage(chatId, `🎬 *${tokenData.token}* : ${tokenData.content_type}_${tokenData.content_id}`);
}

(async () => {
    console.log(`🧪 Zinema dry-run bot → ${recoveryConfig.apiUrl}`);
    if (!(await apiClient.testConnection())) process.exit(1);

    const healthMonitor = new HealthMonitor(recoveryConfig.botInstanceId, apiClient, fakeSock);
    healthMonitor.start(30);

    console.log(`\nType "${TOKEN_PREFIX} <TOKEN>" to simulate a WhatsApp message, Ctrl+C to quit.\n`);
    const rl = readline.createInterface({ input: process.stdin });
    rl.on('line', async (line) => {
        const parts = line.trim().split(/\s+/);
        if (parts[0].toLowerCase() !== TOKEN_PREFIX) return;
        if (!parts[1]) return sendMessage(FAKE_CHAT_ID, `⚠️ Usage: ${TOKEN_PREFIX} <TOKEN>`);
        await handleTokenRequest(FAKE_CHAT_ID, parts[1].toUpperCase(), FAKE_SENDER);
    });
    rl.on('close', () => { healthMonitor.stop(); process.exit(0); });
    process.on('SIGINT', () => rl.close());
})();

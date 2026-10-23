'use strict';
// Read only explicitly supplied process environment. Never load preserved .env files.
const path = require('node:path');
function fail(key) { throw new Error(`Recovery configuration unavailable: ${key}.`); }
function readConfig(env = process.env) {
    const required = key => {
        const value = (env[key] || '').trim();
        if (!value || value.startsWith('REPLACE_')) fail(key);
        return value;
    };
    const appEnv = required('APP_ENV');
    if (!['development', 'test', 'production'].includes(appEnv)) fail('APP_ENV');
    const apiUrl = required('API_URL');
    let parsed;
    try { parsed = new URL(apiUrl); } catch { fail('API_URL'); }
    if (parsed.username || parsed.password || parsed.search || parsed.hash) fail('API_URL');
    if (appEnv !== 'production' ? (parsed.hostname !== '127.0.0.1' || parsed.protocol !== 'http:') : (parsed.protocol !== 'https:' || env.ALLOW_PRODUCTION_API !== 'true')) fail('API_URL');
    const apiKey = required('API_KEY');
    const sessionPath = required('SESSION_PATH');
    const mediaPath = required('LOCAL_MEDIA_PATH');
    for (const [key, value] of [['SESSION_PATH', sessionPath], ['LOCAL_MEDIA_PATH', mediaPath]]) {
        if (!path.isAbsolute(value)) fail(key);
        if (appEnv !== 'production' && /(?:^|[\\/])(?:whatsapp-session|\.wwebjs_auth)(?:[\\/]|$)/i.test(path.resolve(value))) fail(key);
    }
    const botInstanceId = required('BOT_INSTANCE_ID');
    if (!/^\d+$/.test(botInstanceId)) fail('BOT_INSTANCE_ID');
    return Object.freeze({appEnv, apiUrl:apiUrl.replace(/\/$/, ''), apiKey, sessionPath, mediaPath, botInstanceId,
        liveWhatsApp: appEnv === 'production' && env.ALLOW_LIVE_WHATSAPP === 'true'});
}
function assertLiveWhatsAppEnabled(config) {
    if (!config.liveWhatsApp) fail('live WhatsApp startup disabled; use a fake adapter in recovery');
}
module.exports = {readConfig, assertLiveWhatsAppEnabled};

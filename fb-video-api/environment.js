'use strict';
// Pure configuration only: importing this file does not start Express or Chromium.
function readConfig(env = process.env) {
    const fail = key => { throw new Error(`Recovery configuration unavailable: ${key}.`); };
    if (!['development', 'test', 'production'].includes(env.APP_ENV)) fail('APP_ENV');
    const mode = env.EXTRACTOR_MODE || 'disabled';
    if (!['disabled', 'local', 'private-fixture'].includes(mode)) fail('EXTRACTOR_MODE: live extraction quarantined pending SSRF remediation');
    if(mode==='private-fixture'&&(env.APP_ENV!=='production'||env.ALLOW_LIVE_INTEGRATIONS!=='false'||env.ZINEMA_PROMOTION_FIXTURES!=='1'||env.EXTRACTOR_SOCKET!=='/run/zinema/extractor.sock'))fail('private fixture boundary');
    const port = Number(env.PORT);
    if (!Number.isInteger(port) || port < 1 || port > 65535) fail('PORT');
    let fakeMediaUrl = null;
    const apiKey=env.EXTRACTOR_API_KEY || null;
    if (apiKey!==null && !/^[a-f0-9]{64}$/.test(apiKey)) fail('EXTRACTOR_API_KEY');
    if (mode === 'local' || mode==='private-fixture') {
        if (!apiKey) fail('EXTRACTOR_API_KEY');
        try { const url = new URL(env.FAKE_MEDIA_URL); if (url.hostname !== '127.0.0.1' || url.protocol !== (mode==='local'?'http:':'https:') || url.username || url.password || url.search || url.hash || mode==='private-fixture'&&(url.port!=='8443'||!/^\/uploads\/(?:trailers\/)?[a-f0-9]{32}\.mp4$/.test(url.pathname))) fail('FAKE_MEDIA_URL'); fakeMediaUrl = url.href; }
        catch { fail('FAKE_MEDIA_URL'); }
    }
    return Object.freeze({mode, port, host:'127.0.0.1',...(mode==='private-fixture'?{socket:env.EXTRACTOR_SOCKET}:{}),fakeMediaUrl,apiKey});
}
module.exports = {readConfig};

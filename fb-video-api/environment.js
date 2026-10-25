'use strict';
// Pure configuration only: importing this file does not start Express or Chromium.
function readConfig(env = process.env) {
    const fail = key => { throw new Error(`Recovery configuration unavailable: ${key}.`); };
    if (!['development', 'test', 'production'].includes(env.APP_ENV)) fail('APP_ENV');
    const mode = env.EXTRACTOR_MODE || 'disabled';
    if (!['disabled', 'local'].includes(mode)) fail('EXTRACTOR_MODE: live extraction quarantined pending SSRF remediation');
    const port = Number(env.PORT);
    if (!Number.isInteger(port) || port < 1 || port > 65535) fail('PORT');
    let fakeMediaUrl = null;
    const apiKey=env.EXTRACTOR_API_KEY || null;
    if (apiKey!==null && !/^[a-f0-9]{64}$/.test(apiKey)) fail('EXTRACTOR_API_KEY');
    if (mode === 'local') {
        if (!apiKey) fail('EXTRACTOR_API_KEY');
        try { const url = new URL(env.FAKE_MEDIA_URL); if (url.hostname !== '127.0.0.1' || url.protocol !== 'http:' || url.username || url.password || url.search || url.hash) fail('FAKE_MEDIA_URL'); fakeMediaUrl = url.href; }
        catch { fail('FAKE_MEDIA_URL'); }
    }
    return Object.freeze({mode, port, host:'127.0.0.1', fakeMediaUrl,apiKey});
}
module.exports = {readConfig};

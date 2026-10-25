'use strict';
const { test } = require('node:test');
const assert = require('node:assert/strict');
const { deliverClaimedToken } = require('../../../whatsapp-bot-deploy/config/token-delivery');

function fixture(options = {}) {
    const calls = [];
    const dependencies = {
        claim: async () => ({ success: true, receipt: 'fictional-proof', data: { id: 1 } }),
        transition: async action => { calls.push(action); return { success: true }; },
        forward: async () => { calls.push('send'); return { success: true }; },
        ...options
    };
    return { calls, run: () => deliverClaimedToken(dependencies, 'fictional-token', 'fictional-recipient') };
}

test('claim denial never reaches delivery', async () => {
    const f = fixture({ claim: async () => ({ success: false }) });
    assert.equal((await f.run()).status, 'unavailable'); assert.deepEqual(f.calls, []);
});
test('begin denial never reaches delivery', async () => {
    const f = fixture({ transition: async () => ({ success: false }) });
    assert.equal((await f.run()).status, 'held'); assert.deepEqual(f.calls, []);
});
test('acknowledged send completes ownership in order', async () => {
    const f = fixture(); assert.equal((await f.run()).success, true);
    assert.deepEqual(f.calls, ['begin', 'send', 'complete']);
});
test('unacknowledged and throwing sends are held without completing', async () => {
    for (const forward of [async () => undefined, async () => { throw Error('fictional transport failure'); }]) {
        const f = fixture({ forward });
        assert.equal((await f.run()).status, 'uncertain'); assert.deepEqual(f.calls, ['begin', 'fail']);
    }
});
test('completion rejection and lost response never report success or retry', async () => {
    for (const throwing of [false, true]) {
        let sends = 0;
        const f = fixture({ forward: async () => { sends++; return { success: true }; },
            transition: async action => { if (action === 'complete') { if (throwing) throw Error('lost acknowledgement'); return { success: false }; } return { success: true }; } });
        assert.equal((await f.run()).status, 'uncertain'); assert.equal(sends, 1);
    }
});
test('API client exports claim protocol and binds recipient and configured bot', async () => {
    const http = require('node:http');
    const server = http.createServer((req, res) => {
        let body = ''; req.on('data', chunk => body += chunk);
        req.on('end', () => {
            const data = JSON.parse(body);
            assert.equal(req.url, '/api/whatsapp/claim-token.php');
            assert.equal(data.bot_id, '7');
            assert.equal(data.recipient_hash, require('node:crypto').createHash('sha256').update('fictional-recipient').digest('hex'));
            assert.equal(data.action, 'claim');
            res.setHeader('Content-Type', 'application/json'); res.end('{"success":true}');
        });
    });
    await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
    try {
        Object.assign(process.env, { APP_ENV: 'test', API_URL: `http://127.0.0.1:${server.address().port}/api/whatsapp`,
            API_KEY: 'fictional-local-key', SESSION_PATH: require('node:path').resolve('.runtime/claim-unit/sessions'), LOCAL_MEDIA_PATH: require('node:path').resolve('.runtime/claim-unit/media'), BOT_INSTANCE_ID: '7', ALLOW_LIVE_WHATSAPP: 'false' });
        const api = require('../../../whatsapp-bot-deploy/config/api-client');
        assert.equal(typeof api.transitionToken, 'function');
        assert.equal((await api.claimToken('fictional-token', 'fictional-recipient')).success, true);
    } finally { await new Promise(resolve => server.close(resolve)); }
});

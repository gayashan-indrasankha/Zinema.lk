'use strict';

// Database ownership precedes external side effects. A crash never releases ownership.
async function deliverClaimedToken({ claim, transition, forward }, token, recipient) {
    const claimed = await claim(token, recipient);
    if (claimed?.success !== true || !claimed.data || !claimed.receipt) return { success: false, status: 'unavailable' };
    const proof = { token_id: claimed.data.id, receipt: claimed.receipt };
    if ((await transition('begin', proof, recipient))?.success !== true) return { success: false, status: 'held' };
    let result;
    try { result = await forward(recipient, claimed.data); }
    catch { result = { success: false }; }
    if (result?.success !== true) {
        await transition('fail', proof, recipient).catch(() => {});
        return { success: false, status: 'uncertain', data: claimed.data };
    }
    let completed;
    try { completed = await transition('complete', proof, recipient); } catch { completed = null; }
    if (completed?.success !== true) return { success: false, status: 'uncertain', data: claimed.data };
    return { success: true, status: 'completed', data: claimed.data, tier: result.tier };
}
module.exports = { deliverClaimedToken };

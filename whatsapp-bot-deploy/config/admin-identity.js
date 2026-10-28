'use strict';
function normalized(value) {
    if (typeof value!=='string') return null;
    const number=value.trim().replace(/^\+/,'');
    return /^\d{7,15}$/.test(number) ? number : null;
}
function isAdminIdentity(sender,admins) {
    const number=normalized(sender);
    return number!==null && Array.isArray(admins) && admins.some(admin=>normalized(admin)===number);
}
module.exports={isAdminIdentity};

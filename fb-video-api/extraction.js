'use strict';
// Pure parsing only. Transport/DNS/browser isolation must be verified before live use.
const hosts=new Set(['facebook.com','www.facebook.com','m.facebook.com','web.facebook.com','touch.facebook.com','fb.com','www.fb.com','fb.watch']);
function normalizeUrl(value) {
    if(typeof value!=='string'||value.length>2048||/[\s\\]/.test(value))throw Error('Invalid Facebook URL');
    const url=new URL(value);
    if(url.protocol!=='https:'||!hosts.has(url.hostname)||url.username||url.password||url.port)throw Error('Invalid Facebook URL');
    if(['m.facebook.com','web.facebook.com','touch.facebook.com'].includes(url.hostname))url.hostname='www.facebook.com';
    url.hash='';return url.href;
}
function cleanUrl(value) {
    if(typeof value!=='string')return null;
    const text=value.replace(/\\u0025/g,'%').replace(/\\u003C/g,'<').replace(/\\u003E/g,'>').replace(/\\u0026/g,'&').replace(/\\\//g,'/').replace(/\\"/g,'"').replace(/&amp;/g,'&');
    try {
        const url=new URL(text);
        if(url.protocol!=='https:'||url.username||url.password||url.port||!(url.hostname==='fbcdn.net'||url.hostname.endsWith('.fbcdn.net')))return null;
        return url.href;
    }catch{return null;}
}
function extractVideoUrlFromHtml(html) {
    if(typeof html!=='string'||html.length>2097152)return null;
    for(const key of ['playable_url_quality_hd','browser_native_hd_url','hd_src','playable_url','browser_native_sd_url']) {
        const match=html.match(new RegExp('"?'+key+'"?\\s*:\\s*"([^"\\n]+)"'));
        const url=match&&cleanUrl(match[1]);if(url)return url;
    }
    return null;
}
module.exports={normalizeUrl,cleanUrl,extractVideoUrlFromHtml};

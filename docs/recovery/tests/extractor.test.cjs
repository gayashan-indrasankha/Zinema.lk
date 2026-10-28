'use strict';
const {test}=require('node:test');const assert=require('node:assert/strict');
const {normalizeUrl,extractVideoUrlFromHtml}=require('../../../fb-video-api/extraction');
test('Facebook host normalization preserves the original shared path',()=>{
 assert.equal(normalizeUrl('https://m.facebook.com/share/v/fictional/#x'),'https://www.facebook.com/share/v/fictional/');
 assert.equal(normalizeUrl('https://fb.watch/fictional/'),'https://fb.watch/fictional/');
});
test('Deceptive, credential-bearing and non-HTTPS Facebook URLs are rejected',()=>{
 for(const url of ['http://facebook.com/a','https://facebook.com.example.invalid/a','https://example.invalid/?facebook.com','https://user@facebook.com/a','https://facebook.com:444/a','https://facebook.com\\@127.0.0.1/a','http://127.0.0.1/'])assert.throws(()=>normalizeUrl(url));
});
for(const key of ['playable_url_quality_hd','browser_native_hd_url','hd_src','playable_url','browser_native_sd_url'])test('Original HTML fixture extraction '+key,()=>{
 assert.equal(extractVideoUrlFromHtml('"'+key+'":"https:\\/\\/video.example.fbcdn.net\\/original.mp4?x=1\\u0026y=2"'),'https://video.example.fbcdn.net/original.mp4?x=1&y=2');
});
test('HD is preferred over SD; unsafe candidates do not become media URLs',()=>{
 assert.match(extractVideoUrlFromHtml('"playable_url":"https://video.example.fbcdn.net/sd.mp4", "playable_url_quality_hd":"https://video.example.fbcdn.net/hd.mp4"'),/hd\.mp4/);
 for(const url of ['http://video.example.fbcdn.net/a','https://fbcdn.net.example.invalid/a','https://user@video.example.fbcdn.net/a','javascript:alert(1)','http://127.0.0.1/a'])assert.equal(extractVideoUrlFromHtml('"playable_url":"'+url+'"'),null);
});
test('Empty, private/login and oversized HTML produce no result',()=>{
 for(const value of ['', '<html>Login required</html>',null,'x'.repeat(2097153)])assert.equal(extractVideoUrlFromHtml(value),null);
});

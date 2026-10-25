'use strict';
// Pure guard tests: never load Baileys, saved sessions, network clients or timers.
const {test} = require('node:test');
const assert = require('node:assert/strict');
const path = require('node:path');
const {spawnSync} = require('node:child_process');
const root = path.resolve(__dirname, '../../..');
const bot = require(path.join(root, 'whatsapp-bot-deploy/config/environment'));
const extractor = require(path.join(root, 'fb-video-api/environment'));
const sample = {
  APP_ENV:'test', API_URL:'http://127.0.0.1:8088/api/whatsapp', API_KEY:'fictional-local-key',
  SESSION_PATH:path.join(root,'.runtime','fictional-guard-sessions'),
  LOCAL_MEDIA_PATH:path.join(root,'.runtime','fictional-guard-media'), BOT_INSTANCE_ID:'1'
};
test('bot accepts explicit local configuration',()=>assert.equal(bot.readConfig(sample).liveWhatsApp,false));
test('bot rejects missing configuration',()=>assert.throws(()=>bot.readConfig({}),/APP_ENV/));
test('bot rejects non-loopback development API',()=>assert.throws(()=>bot.readConfig({...sample,API_URL:'https://example.invalid'}),/API_URL/));
test('bot rejects preserved WhatsApp session path',()=>assert.throws(()=>bot.readConfig({...sample,SESSION_PATH:path.join(root,'whatsapp-session','bot1')}),/SESSION_PATH/));
test('bot cannot enable live access merely through local flag',()=>assert.throws(()=>bot.assertLiveWhatsAppEnabled(bot.readConfig({...sample,ALLOW_LIVE_WHATSAPP:'true'})),/live WhatsApp startup disabled/));
test('bot entry point exits at the live startup guard',()=>{
  const result=spawnSync(process.execPath,[path.join(root,'whatsapp-bot-deploy/index.js')],{env:{...process.env,...sample,ALLOW_LIVE_WHATSAPP:'false'},encoding:'utf8'});
  assert.notEqual(result.status,0);
  assert.match(result.stderr,/live WhatsApp startup disabled/);
  assert.doesNotMatch(result.stdout,/QR|Connected|Baileys/);
});
test('extractor disabled mode binds only loopback',()=>assert.deepEqual(extractor.readConfig({APP_ENV:'test',PORT:'3334'}),{mode:'disabled',port:3334,host:'127.0.0.1',fakeMediaUrl:null}));
test('extractor rejects live mode',()=>assert.throws(()=>extractor.readConfig({APP_ENV:'production',PORT:'3334',EXTRACTOR_MODE:'live'}),/quarantined/));
test('extractor rejects non-local fixture URL',()=>assert.throws(()=>extractor.readConfig({APP_ENV:'test',PORT:'3334',EXTRACTOR_MODE:'local',FAKE_MEDIA_URL:'https://example.invalid/movie.mp4'}),/FAKE_MEDIA_URL/));
test('extractor accepts local fixture',()=>assert.equal(extractor.readConfig({APP_ENV:'test',PORT:'3334',EXTRACTOR_MODE:'local',FAKE_MEDIA_URL:'http://127.0.0.1:8088/demo.mp4'}).fakeMediaUrl,'http://127.0.0.1:8088/demo.mp4'));

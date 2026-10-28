'use strict';
const {test}=require('node:test'),assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path'),crypto=require('node:crypto');
const {transform}=require('../../../dev/backup_crypto.cjs');
const base=path.resolve(__dirname,'../../../.runtime');fs.mkdirSync(base,{recursive:true});
test('Local encrypted backup boundaries and authenticated recovery',()=>{
 const dir=fs.mkdtempSync(path.join(base,'crypto-test-')),at=name=>path.join(dir,name);
 try {
  const raw=crypto.randomBytes(4096);fs.writeFileSync(at('source'),raw);fs.writeFileSync(at('key'),crypto.randomBytes(32).toString('hex'));fs.writeFileSync(at('wrong-key'),crypto.randomBytes(32).toString('hex'));
  transform('encrypt',at('source'),at('encrypted'),at('key'));transform('decrypt',at('encrypted'),at('restored'),at('key'));assert.deepEqual(fs.readFileSync(at('restored')),raw);
  assert.throws(()=>transform('encrypt',at('source'),at('restored'),at('key')));assert.deepEqual(fs.readFileSync(at('restored')),raw);
  assert.throws(()=>transform('decrypt',at('encrypted'),at('wrong-output'),at('wrong-key')));assert.equal(fs.existsSync(at('wrong-output')),false);
  const bad=fs.readFileSync(at('encrypted'));bad[bad.length-1]^=1;fs.writeFileSync(at('bad'),bad);assert.throws(()=>transform('decrypt',at('bad'),at('partial'),at('key')));assert.equal(fs.existsSync(at('partial')),false);
  assert.throws(()=>transform('decrypt',at('source'),at('format'),at('key')));assert.equal(fs.existsSync(at('format')),false);
  assert.throws(()=>transform('encrypt',path.resolve(__dirname,'../../../README.md'),at('outside'),at('key')));
  assert.throws(()=>transform('encrypt',at('source'),path.resolve(__dirname,'../../../must-not-write'),at('key')));
  fs.writeFileSync(at('invalid-key'),'fictional');assert.throws(()=>transform('encrypt',at('source'),at('invalid'),at('invalid-key')));
  fs.writeFileSync(at('oversized'),'');fs.truncateSync(at('oversized'),67108865);assert.throws(()=>transform('encrypt',at('oversized'),at('too-large'),at('key')));assert.equal(fs.existsSync(at('too-large')),false);
  assert.throws(()=>transform('unrecognised',at('source'),at('unknown'),at('key')));
 } finally {if(!path.resolve(dir).startsWith(base+path.sep))throw Error('cleanup boundary');fs.rmSync(dir,{recursive:true});}
});

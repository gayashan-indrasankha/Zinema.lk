'use strict';
// Import factories and serialize fictional data only. Never create a socket or
// load a WhatsApp authentication store.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {createRequire} = require('node:module');
const directory = path.resolve(process.argv[2]);
const local = createRequire(path.join(directory,'package.json'));
const baileys = local('@whiskeysockets/baileys');
const protobuf = local('protobufjs');
assert.equal(typeof baileys.default,'function');
assert.equal(typeof baileys.useMultiFileAuthState,'function');
const message = protobuf.parse('syntax="proto3"; message Probe { string value = 1; }').root.lookupType('Probe');
assert.equal(message.decode(message.encode({value:'fictional'}).finish()).value,'fictional');
const lock = JSON.parse(fs.readFileSync(path.join(directory,'package-lock.json'),'utf8'));
const protobufVersions = [...new Set(Object.entries(lock.packages).filter(([name])=>name.endsWith('/protobufjs')).map(([,item])=>item.version))];
assert.ok(protobufVersions.length && protobufVersions.every(version=>version.startsWith('7.6.') && Number(version.split('.')[2]) >= 6));
console.log(JSON.stringify({passed:true,baileysVersion:lock.packages['node_modules/@whiskeysockets/baileys'].version,protobufVersions,socketStarted:false,authStoresLoaded:false}));

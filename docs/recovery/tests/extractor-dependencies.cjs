'use strict';
// Import-only check. Never launches a browser, server or provider request.
const fs=require('node:fs');const path=require('node:path');const {createRequire}=require('node:module');const {pathToFileURL}=require('node:url');const assert=require('node:assert/strict');
const root=path.resolve(__dirname,'../../..'),folder=path.resolve(process.argv[2]||'');
if(!folder.startsWith(path.join(root,'.runtime')+path.sep))throw Error('Use an isolated owned dependency directory');
(async()=>{
 const localRequire=createRequire(path.join(folder,'package.json'));
 const puppeteer=await import(pathToFileURL(localRequire.resolve('puppeteer-core')).href);
 assert.equal(typeof puppeteer.launch,'function');assert.equal(typeof localRequire('express'),'function');assert.equal(typeof localRequire('cors'),'function');
 const version=JSON.parse(fs.readFileSync(path.join(folder,'node_modules/puppeteer-core/package.json'),'utf8')).version;
 const expected=JSON.parse(fs.readFileSync(path.join(root,'fb-video-api/package.json'),'utf8')).dependencies['puppeteer-core'];assert.equal(version,expected);
 console.log(JSON.stringify({imports_passed:3,puppeteer:version,node:process.versions.node,browser_launched:false,live_provider_tested:false}));
})().catch(()=>{console.error('Extractor dependency imports failed; private dependencies retained.');process.exitCode=1;});

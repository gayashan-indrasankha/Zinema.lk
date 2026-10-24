'use strict';
// Actual browser protocol checks against the isolated identity fixture, not the legacy application DB.
const assert=require('node:assert/strict'),path=require('node:path'),fs=require('node:fs');
const {chromium}=require('../playback/tools/node_modules/playwright-core'),{start}=require('./server.cjs');
(async()=>{const server=await start();let browser;let assertions=0;const check=(value)=>{assert.ok(value);assertions++;};
try {
 const executable=process.env.ZL17_CHROME||(process.platform==='win32'?'C:/Program Files/Google/Chrome/Application/chrome.exe':undefined);
 browser=await chromium.launch({headless:true,...(executable&&fs.existsSync(executable)?{executablePath:executable}:{}),args:['--disable-background-networking','--disable-component-update','--no-first-run','--no-default-browser-check']});
 const context=await browser.newContext();const page=await context.newPage();let unexpected=0;
 await context.route('**/*',route=>{if(!route.request().url().startsWith(server.origin+'/')){unexpected++;return route.abort();}return route.continue();});
 await page.goto(server.origin+'/form');
 const request=async(route,data,token=true)=>page.evaluate(async({route,data,token})=>{const form=await (await fetch('/form')).json();const r=await fetch(route,{method:data?'POST':'GET',headers:data?{'Content-Type':'application/json',...(token?{'X-CSRF-Token':form.csrf}:{})}:{},...(data?{body:JSON.stringify(data)}:{})});return {status:r.status,body:await r.json()};},{route,data,token});
 check((await request('/admin')).status===401);
 check((await page.evaluate(()=>document.cookie)).indexOf('ZL19SID')===-1);
 const initial=(await context.cookies()).find(c=>c.name==='ZL19SID');check(initial.httpOnly&&initial.sameSite==='Lax'&&!initial.secure);
 check((await request('/login',{email:'viewer-a@example.invalid',password:'fictional horse galaxy 47'},false)).status===403);
 check((await request('/register',{email:'new-viewer@example.invalid',password:'fictional horse galaxy 47',role:'admin',is_verified:true})).status===200);
 check((await request('/login',{email:'new-viewer@example.invalid',password:'fictional horse galaxy 47'})).status===401);
 check((await request('/verify/request',{email:'new-viewer@example.invalid'})).status===200);
 const token=server.state().mail.at(-1).token;
 check((await request('/verify/consume',{token})).status===200);
 check((await request('/me')).status===401);
 check((await request('/verify/consume',{token})).status===400);
 check((await request('/login',{email:'new-viewer@example.invalid',password:'fictional horse galaxy 47'})).status===200);
 const authenticated=(await context.cookies()).find(c=>c.name==='ZL19SID');check(authenticated.value!==initial.value);
 check((await request('/admin')).status===403);
 check((await request('/account?owner='+'a'.repeat(32))).status===403);
 check((await request('/logout',{})).status===200);
 check(!(await context.cookies()).some(c=>c.name==='ZL19SID'));
 check((await request('/me')).status===401);
 check(unexpected===0);
 console.log(JSON.stringify({browser:'Chromium',identityFixtureAssertions:assertions,unexpectedRequests:unexpected,legacyDatabaseVerified:false,adminMfaImplemented:false}));
}finally{if(browser)await browser.close();await server.close();}
})().catch(()=>{console.error(JSON.stringify({error:'IDENTITY_BROWSER_CHECK_FAILED',diagnosticsWithheld:true}));process.exitCode=1;});

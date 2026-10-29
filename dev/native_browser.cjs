'use strict';
// Automated browser demonstration only. All destinations stay on owned localhost ports.
const fs=require('node:fs'),path=require('node:path'),crypto=require('node:crypto'),assert=require('node:assert/strict'),{execFileSync}=require('node:child_process');
const root=path.resolve(__dirname,'..'),active=JSON.parse(fs.readFileSync(path.join(root,'.runtime/native-local/active.json'),'utf8')),home=path.resolve(active.home);
if(active.root!==root||path.dirname(home)!==path.join(root,'.runtime'))throw Error('native_owner_denied');
if(fs.existsSync(path.join(home,'native/whatsapp-live.json')))throw Error('simulator_browser_test_refuses_live_whatsapp');
execFileSync('python',['-c',"import sys;sys.path.insert(0,'dev');import native;h,c=native.current();native.verify_processes(h);native.audit.sql(h,'SELECT 1;')"],{cwd:root,stdio:'pipe'});
const {chromium}=require(process.argv[2]),password=fs.readFileSync(path.join(root,'docs/recovery/tests/audit.py'),'utf8').match(/^SAMPLE_PASSWORD = '([^']+)'/m)[1],base='http://127.0.0.1:8088';
const out=path.join(home,'native/browser-'+crypto.randomBytes(5).toString('hex'));fs.mkdirSync(out);const rows=[];let external=0,externalCompleted=0,externalFailed=0,oauthSeen=false;const errors=[];
(async()=>{
 const browser=await chromium.launch({channel:'chrome',headless:true,args:['--disable-background-networking','--disable-component-update','--no-first-run','--host-resolver-rules=MAP * ~NOTFOUND, EXCLUDE 127.0.0.1']});
 try {
  const context=await browser.newContext({viewport:{width:1280,height:900},serviceWorkers:'block'});
  context.on('request',request=>{const u=new URL(request.url());if(u.origin==='http://127.0.0.1:9081')oauthSeen=true;else if(u.origin!==base)external++;});
  const local=url=>[base,'http://127.0.0.1:9081'].includes(new URL(url).origin);
  context.on('response',response=>{if(!local(response.url()))externalCompleted++;});
  context.on('requestfailed',request=>{if(!local(request.url()))externalFailed++;});
  await context.route('**/*',route=>{const u=new URL(route.request().url());if(u.origin===base||u.origin==='http://127.0.0.1:9081')return route.continue();return route.abort();});
  const page=await context.newPage();page.setDefaultTimeout(8000);page.on('pageerror',e=>errors.push(e.name));
  async function visit(route){return page.goto(base+route,{waitUntil:'domcontentloaded'});}
  async function test(name,fn){try{await fn();rows.push({name,passed:true});}catch(e){rows.push({name,passed:false,errorType:e.name});await page.screenshot({path:path.join(out,'failure-'+rows.length+'.png'),fullPage:true});}}
  await test('Administrator opens the whole-local console',async()=>{await visit('/admin/login.php');await page.locator('[name=username]').fill('audit_admin');await page.locator('[name=password]').fill(password);await page.getByRole('button',{name:'Login',exact:true}).click();await page.waitForURL(/\/admin\/(?:index\.php)?$/);await visit('/__local/index.php');await page.getByRole('heading',{name:'Whole local system',exact:true}).waitFor();await page.screenshot({path:path.join(out,'console.png'),fullPage:true});});
  await test('Console controls online/offline simulator state',async()=>{await page.getByRole('button',{name:'Simulator offline',exact:true}).click();assert.equal(await page.locator('#bot-status').innerText(),'Offline');await page.getByRole('button',{name:'Simulator online',exact:true}).click();assert.equal(await page.locator('#bot-status').innerText(),'Online');});
  await test('Website request opens a local simulated chat and completes',async()=>{await page.getByRole('link',{name:'Request original demo movie',exact:true}).click();const popupPromise=context.waitForEvent('page');await page.locator('a[href*="/__local/chat.php?token="]').first().click();const chat=await popupPromise;await chat.waitForLoadState();await chat.getByRole('button',{name:'Send to local simulator'}).click();await chat.getByRole('heading',{name:'Simulated delivery complete'}).waitFor();await chat.screenshot({path:path.join(out,'simulated-chat.png'),fullPage:true});await chat.close();});
  const suffix=crypto.randomBytes(4).toString('hex'),address='browser_'+suffix+'@example.invalid';
  await test('Signup creates a fictional local verification message',async()=>{await visit('/signup.php');await page.locator('[name=username]').fill('browser_'+suffix);await page.locator('[name=email]').fill(address);await page.locator('[name=password]').fill(password);await page.getByRole('button',{name:'Sign Up',exact:true}).click();assert.ok(/check your email|verify your account/i.test(await page.locator('body').innerText()));});
  await test('Fake inbox verification link activates the fictional account',async()=>{await visit('/__local/inbox.php');const article=page.locator('article').filter({hasText:address});await article.getByRole('link',{name:'Verify fictional account'}).first().click();assert.ok(/verified|verification successful/i.test(await page.locator('body').innerText()));});
  await test('Fake OAuth navigates only to the local provider',async()=>{await visit('/oauth-start.php');await page.waitForURL(/profile\.php/);assert.equal(oauthSeen,true);});
  await test('Administrator extractor button checks the original-media fake',async()=>{await visit('/admin/manage_shots.php');await page.locator('#fb_share_url').fill('https://www.facebook.com/share/v/fictional/');await page.locator('button[onclick="testVideoUrl()"]').click();await page.getByText('Local fake connection verified',{exact:false}).waitFor();await page.screenshot({path:path.join(out,'extractor.png'),fullPage:true});});
  await test('Trailer editor loads its own exact title description and status without undefined fields',async()=>{
   const title='Original editor "clip" <fictional> '+suffix,description='Trailer only & සිංහල\nSecond line with "quotes".';
   await visit('/admin/trailers.php');const form=page.locator('form:has(input[name="action"][value="add"])');
   await form.locator('[name="movie_id"]').selectOption('1');await form.locator('[name="title"]').fill(title);await form.locator('[name="description"]').fill(description);await form.locator('[name="trailer_video"]').setInputFiles(path.join(home,'web/demo.mp4'));
   await form.getByRole('button',{name:'Upload Trailer',exact:true}).click();await page.getByText('Trailer added successfully!',{exact:true}).waitFor();
   const card=page.locator('.trailer-card').filter({hasText:title});await card.getByRole('button',{name:'Edit',exact:true}).click();
   assert.equal(await page.locator('#edit_title').inputValue(),title);assert.equal(await page.locator('#edit_description').inputValue(),description);assert.equal(await page.locator('#edit_status').inputValue(),'active');
   const box=await page.locator('#editModal .modal-content').boundingBox();assert.ok(box&&box.y>=0&&box.y+box.height<=900&&box.x>=0&&box.x+box.width<=1280);
   await page.screenshot({path:path.join(out,'trailer-editor.png'),fullPage:true});await page.locator('#editModal').getByRole('button',{name:'Cancel',exact:true}).click();assert.equal(await page.locator('#editModal').isVisible(),false);assert.equal(await card.locator('h3').innerText(),title);
  });
  await test('Legacy nonlocal asset attempts are blocked',async()=>{assert.equal(externalCompleted,0);assert.equal(externalFailed,external);});
 } finally{await browser.close();}
 const report={scope:'Automated whole-local browser journeys; all provider operations simulated',tests:rows,summary:{total:rows.length,passed:rows.filter(r=>r.passed).length,failed:rows.filter(r=>!r.passed).length},nonlocal_asset_attempts:external,nonlocal_failed:externalFailed,nonlocal_responses:externalCompleted,page_errors:errors};
 fs.writeFileSync(path.join(out,'results.json'),JSON.stringify(report,null,2));fs.writeFileSync(path.join(root,'docs/recovery/LOCAL_WHOLE_BROWSER.json'),JSON.stringify(report,null,2)+'\n');console.log(JSON.stringify({output:out,summary:report.summary,failures:rows.filter(r=>!r.passed).map(r=>r.name),external,pageErrors:errors}));process.exitCode=report.summary.failed?1:0;
})().catch(()=>{console.error('Native browser check aborted; private evidence retained.');process.exitCode=1;});

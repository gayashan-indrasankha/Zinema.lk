'use strict';
// Automated browser acceptance, not an interactive/manual test. Local fictional data only.
const fs=require('node:fs'),path=require('node:path'),crypto=require('node:crypto'),assert=require('node:assert/strict');
const root=path.resolve(__dirname,'../../..'),runtime=path.resolve(process.argv[2]||''),packagePath=process.argv[3];
if(path.dirname(runtime)!==path.join(root,'.runtime')||!path.basename(runtime).startsWith('audit-')||!fs.existsSync(path.join(runtime,'ownership.json'))||!packagePath||!path.isAbsolute(packagePath))throw Error('Owned runtime and installed Playwright directory required');
const ownership=JSON.parse(fs.readFileSync(path.join(runtime,'ownership.json'),'utf8'));
if(ownership.base!=='http://127.0.0.1:8088')throw Error('Local preview identity denied');
const {chromium}=require(packagePath),base=ownership.base;
const caseId=crypto.randomBytes(5).toString('hex'),output=path.join(root,'.runtime','local-acceptance','case-'+caseId);fs.mkdirSync(output,{recursive:true});
const fixtureSource=fs.readFileSync(path.join(__dirname,'audit.py'),'utf8'),password=fixtureSource.match(/^SAMPLE_PASSWORD = '([^']+)'/m)?.[1];if(!password)throw Error('Fictional fixture password unavailable');
const report={scope:'Automated headless Chrome plus separate visual screenshot review; fictional localhost only',tests:[],externalConnections:false,productionAccess:false,publishing:false,blockedExternalRequests:0,pageErrors:[],screenshots:[]};
async function run(){
 const browser=await chromium.launch({channel:'chrome',headless:true,args:['--disable-background-networking','--disable-component-update','--no-first-run','--host-resolver-rules=MAP * ~NOTFOUND, EXCLUDE 127.0.0.1']});
 try {
  const context=await browser.newContext({viewport:{width:1280,height:900},serviceWorkers:'block'});
  await context.route('**/*',route=>{if(new URL(route.request().url()).origin===base)return route.continue();report.blockedExternalRequests++;return route.abort();});
  const page=await context.newPage();page.setDefaultTimeout(8000);page.on('pageerror',error=>report.pageErrors.push(error.name));
  async function visit(route){assert.ok(route.startsWith('/')&&!route.startsWith('//'));return page.goto(base+route,{waitUntil:'domcontentloaded'});}
  async function screenshot(name){await page.screenshot({path:path.join(output,name+'.png'),fullPage:true});report.screenshots.push(name+'.png');}
  async function test(name,fn){try{await fn();report.tests.push({name,passed:true});}catch(error){report.tests.push({name,passed:false,errorType:error.name});await screenshot('failure-'+report.tests.length);}}
  await test('Catalogue renders tracked local logo and fictional cards',async()=>{
   assert.equal((await visit('/movies')).status(),200);await page.locator('.top-nav-logo img').waitFor();
   await page.waitForFunction(()=>document.querySelector('.top-nav-logo img')?.naturalWidth>0);
   for(const title of ['Fictional Audit Movie','Fictional Other Movie'])assert.equal(await page.locator('.simple-movie-card').filter({hasText:title}).count(),1);
   assert.ok((await page.locator('meta[name="description"]').getAttribute('content')).includes('delivered through WhatsApp'));
   await screenshot('catalogue-desktop');
  });
  await test('Search popup opens, searches and follows a rewritten detail route',async()=>{
   await page.locator('.top-nav-search-icon').click();await page.locator('#search-popup-input').fill('Fictional Audit');
   const result=page.locator('#search-popup-results a').filter({hasText:'Fictional Audit Movie'}).first();await result.waitFor();await result.click();
   await page.waitForURL(/\/movie\/1\//);assert.ok((await page.locator('body').innerText()).includes('Fictional Audit Movie'));
  });
  await test('Full movie detail offers WhatsApp handoff without a full-content player',async()=>{
   await visit('/movie/1/fictional-audit-movie');assert.equal(await page.locator('video').count(),0);
   await page.locator('a[href*="whatsapp-watch.php"]').first().click();await page.waitForURL(/whatsapp-watch/);
   await page.getByRole('heading',{name:'Delivery unavailable',exact:true}).waitFor();assert.ok((await page.locator('body').innerText()).includes('No delivery request has been created'));
   await screenshot('delivery-unavailable');await page.getByRole('link',{name:'Browse movies'}).click();await page.waitForURL(base+'/movies');
  });
  await test('Signup honestly reports disabled verification without signing in',async()=>{
   await visit('/signup.php');await page.locator('[name="username"]').fill('demo_'+caseId);await page.locator('[name="email"]').fill('demo'+caseId+'@example.invalid');await page.locator('[name="password"]').fill(password);
   await page.getByRole('button',{name:'Sign Up',exact:true}).click();await page.getByText('Registration is unavailable while verification email is disabled.',{exact:true}).waitFor();await screenshot('signup-disabled');
  });
  await test('Missing reset token displays an invalid-link message',async()=>{
   await visit('/reset-password.php');assert.ok((await page.locator('body').innerText()).includes('No reset token provided.'));
  });
  await test('Verified fictional member signs in and reaches profile',async()=>{
   await visit('/login.php');await page.locator('[name="email"]').fill('verified@example.invalid');await page.locator('[name="password"]').fill(password);await page.getByRole('button',{name:'Login',exact:true}).click();
   await page.waitForURL(/profile\.php/);await page.getByRole('heading',{name:/^audit_verified$/i}).waitFor();await screenshot('member-profile');
  });
  await test('Administrator login reaches the dashboard',async()=>{
   await visit('/admin/login.php');await page.locator('[name="username"]').fill('audit_admin');await page.locator('[name="password"]').fill(password);await page.getByRole('button',{name:'Login',exact:true}).click();await page.waitForURL(/\/admin\/(?:index\.php)?$/);await screenshot('admin-dashboard');
  });
  const title='Local Acceptance '+caseId;let coverUrl;
  await test('Administrator creates a fictional movie with a generated PNG',async()=>{
   await visit('/admin/movies.php');await page.locator('#title').fill(title);await page.locator('#release_date').fill('2026-01-01');await page.locator('#genre').fill('Drama');await page.locator('#rating').fill('7');await page.locator('#video_url').fill('');
   await page.locator('#cover_image').setInputFiles(path.join(runtime,'generated-acceptance.png'));await page.locator('[name="add_movie"]').click();await page.getByText('Movie added successfully!',{exact:true}).waitFor();
   const row=page.locator('tr').filter({hasText:title});assert.equal(await row.count(),1);coverUrl=await row.locator('img').getAttribute('src');assert.match(coverUrl,/uploads\/[a-f0-9]{32}\.png$/);
   await page.waitForFunction(src=>[...document.images].some(img=>img.getAttribute('src')===src&&img.naturalWidth>0),coverUrl);await screenshot('admin-movie-created');
  });
  await test('Administrator edits the new movie while retaining its cover',async()=>{
   await visit('/admin/movies.php');await page.locator('tr').filter({hasText:title}).getByRole('link',{name:'Edit',exact:true}).click();await page.locator('#title').fill(title+' Edited');await page.locator('[name="edit_movie"]').click();await page.getByText('Movie updated successfully!',{exact:true}).waitFor();
   assert.equal(await page.locator('tr').filter({hasText:title+' Edited'}).locator('img').getAttribute('src'),coverUrl);
  });
  await test('An invalid image is rejected before creating a movie',async()=>{
   await visit('/admin/movies.php');await page.locator('#title').fill(title+' Rejected');await page.locator('#release_date').fill('2026-01-01');await page.locator('#genre').fill('Drama');await page.locator('#rating').fill('7');
   await page.locator('#cover_image').setInputFiles({name:'fictional.png',mimeType:'image/png',buffer:Buffer.from('<svg xmlns="http://www.w3.org/2000/svg"/>')});
   const response=page.waitForResponse(r=>r.url()===base+'/admin/movies.php'&&r.request().method()==='POST');await page.locator('[name="add_movie"]').click();assert.equal((await response).status(),422);
   assert.ok((await page.locator('body').innerText()).includes('Upload rejected'));await visit('/admin/movies.php');assert.equal(await page.locator('tr').filter({hasText:title+' Rejected'}).count(),0);
  });
  let trailerSource,trailerId;
  await test('Administrator uploads the original generated MP4 with honest limits',async()=>{
   await visit('/admin/trailers.php');const form=page.locator('form:has(input[name="action"][value="add"])');
   assert.ok((await form.innerText()).includes('up to 20 MiB'));assert.equal(await form.locator('[name="trailer_video"]').getAttribute('accept'),'video/mp4,video/webm');
   await form.locator('[name="movie_id"]').selectOption('1');await form.locator('[name="title"]').fill('Original Acceptance Clip '+caseId);await form.locator('[name="description"]').fill('Original generated test pattern; fictional preview.');await form.locator('[name="trailer_video"]').setInputFiles(path.join(runtime,'web','demo.mp4'));
   await form.getByRole('button',{name:'Upload Trailer'}).click();await page.getByText('Trailer added successfully!',{exact:true}).waitFor();
   const card=page.locator('.trailer-card').filter({hasText:'Original Acceptance Clip '+caseId});trailerSource=await card.locator('video').getAttribute('src');assert.match(trailerSource,/uploads\/trailers\/[a-f0-9]{32}\.mp4$/);
   trailerId=(await card.getByRole('button',{name:'Edit',exact:true}).getAttribute('onclick')).match(/^editTrailer\((\d+)\)$/)[1];await screenshot('admin-clip-upload');
  });
  await test('Uploaded original preview decodes and advances without external media',async()=>{
   await visit('/admin/trailers.php');const video=page.locator('.trailer-card').filter({hasText:'Original Acceptance Clip '+caseId}).locator('video');
   await video.evaluate(async element=>{element.muted=true;await element.play();});await page.waitForTimeout(700);const state=await video.evaluate(element=>({time:element.currentTime,ready:element.readyState,error:!!element.error}));assert.ok(state.time>0.2&&state.ready>=2&&!state.error);
  });
  await test('Public trailer feed selects and plays the uploaded short preview',async()=>{
   await visit('/trailers.php?id='+trailerId);const video=page.locator('[data-trailer-id="'+trailerId+'"] video');await video.waitFor();
   await video.evaluate(async element=>{element.muted=true;await element.play();});await page.waitForTimeout(700);
   const state=await video.evaluate(element=>({time:element.currentTime,ready:element.readyState,source:element.currentSrc,error:!!element.error}));
   assert.ok(state.time>0.2&&state.ready>=2&&!state.error);assert.equal(new URL(state.source).origin,base);await screenshot('public-generated-clip');
  });
  await test('Mobile administration fits the viewport',async()=>{
   await page.setViewportSize({width:390,height:844});await visit('/admin/movies.php');const size=await page.evaluate(()=>({width:innerWidth,scroll:document.documentElement.scrollWidth}));assert.ok(size.scroll<=size.width+1);await screenshot('admin-mobile');
  });
  await test('Mobile catalogue fits the viewport with a working uploaded cover',async()=>{
   await visit('/movies');const size=await page.evaluate(()=>({width:innerWidth,scroll:document.documentElement.scrollWidth}));assert.ok(size.scroll<=size.width+1);await screenshot('catalogue-mobile');
  });
  await test('Administrator logout returns to login and retains separate member session',async()=>{
   await page.setViewportSize({width:1280,height:900});await visit('/admin/index.php');await page.getByRole('button',{name:'Logout',exact:true}).click();await page.waitForURL(/\/admin\/login\.php/);
   await visit('/profile.php');await page.getByRole('heading',{name:/^audit_verified$/i}).waitFor();
  });
 } finally{await browser.close();}
 report.summary={total:report.tests.length,passed:report.tests.filter(t=>t.passed).length,failed:report.tests.filter(t=>!t.passed).length};
 report.limitations=['Interactive computer-use tools could not initialize; no manual interaction pass claimed.','Real hosting, provider extraction, email, WhatsApp, speed tests and independent backups not exercised.','Only generated small PNG/MP4 fixtures, not arbitrary codecs, real media or sustained load.','CSP blocks legacy external assets; no CDN requests or external page destinations are permitted.'];
 fs.writeFileSync(path.join(output,'results.json'),JSON.stringify(report,null,2)+'\n');fs.writeFileSync(path.join(root,'docs/recovery/LOCAL_ACCEPTANCE_BROWSER.json'),JSON.stringify(report,null,2)+'\n');
 console.log(JSON.stringify({output,summary:report.summary,failures:report.tests.filter(t=>!t.passed).map(t=>t.name),pageErrors:report.pageErrors,blockedExternalRequests:report.blockedExternalRequests}));process.exitCode=report.summary.failed?1:0;
}
run().catch(error=>{fs.writeFileSync(path.join(output,'aborted.json'),JSON.stringify({errorType:error.name}));console.error('Owned browser acceptance aborted; private evidence retained.');process.exitCode=1;});

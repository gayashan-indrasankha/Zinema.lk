'use strict';
const {test}=require('node:test'),assert=require('node:assert/strict'),cp=require('node:child_process'),path=require('node:path'),fs=require('node:fs'),os=require('node:os');
test('ZL-19 actual legacy signup cannot delete an expired existing synthetic account',()=>{
 const root=path.resolve(__dirname,'../..'),directory=fs.mkdtempSync(path.join(os.tmpdir(),'zinema-zl19-legacy-'));
 try {fs.mkdirSync(path.join(directory,'admin'));fs.mkdirSync(path.join(directory,'includes'));
 fs.writeFileSync(path.join(directory,'admin/config.php'),'<?php // Synthetic bootstrap supplied by auto_prepend_file.');
 fs.writeFileSync(path.join(directory,'includes/csrf_helper.php'),'<?php function validate_csrf_token($token) { return $token === str_repeat("a",64); }');
 fs.writeFileSync(path.join(directory,'includes/mailer.php'),'<?php function sendVerificationEmail(...$args) { fwrite(STDERR,"MAIL_MUST_NOT_RUN");exit(4); }');
 const env={ZL19_LEGACY_ROOT:directory,...(process.platform==='win32'?{SystemRoot:process.env.SystemRoot||'C:\\Windows'}:{})};
 const r=cp.spawnSync(process.env.PHP_BINARY||'php',['-n','-d','auto_prepend_file='+path.join(__dirname,'legacy-prepend.php'),path.join(root,'Zinema.lk Website/ajax_signup.php')],{cwd:root,env,encoding:'utf8',timeout:5000});assert.equal(r.status,0,r.stderr);assert.equal(r.stderr,'');assert.deepEqual(JSON.parse(r.stdout),{success:false,message:'Registration could not be completed. Use account recovery if needed.'});
 }finally{if(path.dirname(directory)!==os.tmpdir()||!path.basename(directory).startsWith('zinema-zl19-legacy-')||fs.lstatSync(directory).isSymbolicLink())throw Error('UNSAFE_CLEANUP');for(const file of ['admin/config.php','includes/csrf_helper.php','includes/mailer.php'])fs.unlinkSync(path.join(directory,file));fs.rmdirSync(path.join(directory,'admin'));fs.rmdirSync(path.join(directory,'includes'));fs.rmdirSync(directory);}
});

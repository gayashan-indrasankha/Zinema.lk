'use strict';
const {test}=require('node:test'),assert=require('node:assert/strict'),path=require('node:path');
const {spawnSync}=require('node:child_process');
const subject=require('../../../ops/bot-readiness.cjs');
function deps(platform='linux',changes={}) {
    const calls=[];
    const filesystem={
        readFileSync:(p)=>{calls.push(['read',p]);assert.equal(p,'/proc/self/mountinfo');return '1 0 8:1 / / rw - ext4 /dev/fake rw\n';},
        lstatSync:(p)=>{calls.push(['lstat',p]);return {isSymbolicLink:()=>false,isDirectory:()=>true};},
        statfsSync:(p)=>{calls.push(['statfs',p]);return {blocks:1024n,bavail:512n,bsize:4096n};},
    };
    for(const name of ['writeFileSync','mkdirSync','unlinkSync','renameSync','readdirSync','openSync','readlinkSync'])filesystem[name]=()=>{throw Error('Forbidden file operation');};
    const result={fs:filesystem,nodeVersion:'v24.22.1',os:{platform:()=>platform,arch:()=>'x64',release:()=>'fictional-release',cpus:()=>[{},{}],totalmem:()=>8*1024**3,freemem:()=>2*1024**3,uptime:()=>3600,hostname:()=>{throw Error('No hostname');},userInfo:()=>{throw Error('No identity');}},execFileSync:(file,args,options)=>{calls.push(['command',file,args,options]);return '3\r\n';},...changes};
    return {result,calls};
}
test('Default collection never reads directories/files or invokes a child command',()=>{
    const f=deps(),r=subject.collect({},f.result);assert.deepEqual(f.calls,[]);assert.equal(r.mediaStorage.status,'unknown');assert.equal(r.network.uploadMbps,null);assert.equal(r.availability.alwaysOn,'unknown');assert.equal(r.decision,'needs_review');
});
test('Report omits host/user/env/path/model and session details',()=>{
    const f=deps(),r=subject.collect({},f.result);const text=JSON.stringify(r);
    for(const forbidden of ['hostname','username','serialNumber','macAddress','mediaRoot','process.env','SESSION_PATH','API_KEY'])assert.equal(text.includes(forbidden),false);
    assert.equal(r.safety.filesWritten,false);assert.equal(r.safety.networkApisUsed,false);assert.equal(r.safety.applicationLoaded,false);assert.equal(r.safety.credentialsOrSessionFilesRead,false);
});
test('Logical CPU, memory and boot-age snapshot are typed metadata only',()=>{
    const r=subject.collect({},deps().result);assert.equal(r.system.logicalCpuCount,2);assert.equal(r.system.totalMemoryBytes,8589934592);assert.equal(r.system.currentUptimeSeconds,3600);assert.equal(r.availability.continuousUptime,'not_verified');
});
test('Owner upload estimate is labelled unverified and uses exact binary 2 GiB',()=>{
    const r=subject.collect(subject.parseArgs(['--upload-mbps','10','--always-on','yes']),deps().result);
    assert.equal(r.network.idealTwoGiBUploadSeconds,1718);assert.equal(r.network.source,'owner_provided_unverified');assert.equal(r.network.speedTestExecuted,false);assert.equal(r.availability.source,'owner_provided_unverified');
});
test('Runtime baseline match is not patch/dependency certification',()=>{
    for(const version of ['v20.1.0','v22.0.0','v26.0.0']){const r=subject.collect({},deps('linux',{nodeVersion:version}).result);assert.equal(r.runtime.matchesTestedMajor,false);assert.equal(r.runtime.securityPatchStatus,'not_verified');}
});
test('Local Linux storage uses only mount metadata/lstat/statfs and omits path',()=>{
    const f=deps(),r=subject.collect({mediaRoot:'/fictional/private-media'},f.result);
    assert.equal(r.mediaStorage.availableBytes,2097152);assert.equal(r.mediaStorage.writability,'not_tested');assert.equal(JSON.stringify(r).includes('/fictional'),false);assert.equal(f.calls.filter(x=>x[0]==='command').length,0);
});
test('Remote Linux mount is rejected before lstat/statfs on that mount',()=>{
    const f=deps();f.result.fs.readFileSync=()=> '1 0 8:1 / / rw - ext4 /dev/fake rw\n2 1 0:2 / /network rw - cifs //private-server/share rw\n';
    const r=subject.collect({mediaRoot:'/network/media'},f.result);assert.equal(r.mediaStorage.reason,'local_filesystem_required');assert.equal(f.calls.some(x=>x[0]==='statfs'||x[1]==='/network'),false);
});
test('Automount and unknown Linux filesystems are not probed',()=>{
    for(const type of ['autofs','nfs','fuse.sshfs','unknown']){const f=deps();f.result.fs.readFileSync=()=>`1 0 0:1 / / rw - ${type} private-source rw\n`;assert.equal(subject.collect({mediaRoot:'/data'},f.result).mediaStorage.reason,'local_filesystem_required');assert.deepEqual(f.calls,[]);}
});
test('Linux symlink components are not followed',()=>{
    const f=deps();f.result.fs.lstatSync=(p)=>({isSymbolicLink:()=>p==='/link',isDirectory:()=>true});const r=subject.collect({mediaRoot:'/link/media'},f.result);assert.equal(r.mediaStorage.reason,'plain_directory_required');assert.equal(f.calls.some(x=>x[0]==='statfs'),false);
});
test('Mount parser chooses longest mount and decodes escaped spaces',()=>{
    const mounts='1 0 8:1 / / rw - ext4 local rw\n2 1 8:2 / /media/with\\040space rw - xfs local rw\n';assert.equal(subject.mountType(mounts,'/media/with space/a'),'xfs');assert.equal(subject.mountType(mounts,'/media/with spacer/a'),'ext4');
});
test('Windows fixed-drive query is bounded, local, noninteractive and hides command window',()=>{
    const f=deps('win32'),r=subject.collect({mediaRoot:'C:\\fictional\\media'},f.result);assert.equal(r.mediaStorage.status,'collected');
    const command=f.calls.find(x=>x[0]==='command');assert.equal(command[1],'C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe');assert.equal(command[3].windowsHide,true);assert.equal(command[3].timeout,5000);assert.ok(command[2].includes('-NoProfile'));assert.equal(command[2].join(' ').includes('fictional'),false);assert.equal(Object.keys(command[3].env).length,2);
});
test('Windows network-mapped drive is rejected before filesystem access',()=>{
    const f=deps('win32');f.result.execFileSync=()=> '4';const r=subject.collect({mediaRoot:'Z:\\media'},f.result);assert.equal(r.mediaStorage.reason,'local_fixed_volume_required');assert.deepEqual(f.calls,[]);
});
test('UNC, extended UNC and relative paths cause no disk probe',()=>{
    for(const root of ['\\\\server\\share','\\\\?\\C:\\media','C:relative','relative']){const f=deps('win32');assert.equal(subject.collect({mediaRoot:root},f.result).mediaStorage.status,'unavailable');assert.deepEqual(f.calls,[]);}
});
test('Unsupported OS and failed metadata remain unknown without raw errors',()=>{
    const f=deps('darwin');assert.equal(subject.collect({mediaRoot:'/media'},f.result).mediaStorage.status,'unknown');assert.deepEqual(f.calls,[]);
    const g=deps();g.result.fs.readFileSync=()=>{throw Error('secret-fixture-do-not-print');};assert.equal(JSON.stringify(subject.collect({mediaRoot:'/data'},g.result)).includes('secret-fixture'),false);
});
test('Missing, duplicate, unknown or secret-shaped arguments are rejected without echo',()=>{
    for(const args of [['--media-root'],['--url','https://example.invalid'],['--upload-mbps','0'],['--upload-mbps','Infinity'],['--always-on','perhaps'],['--always-on','yes','--always-on','no'],['--media-root','bad\ninput']])assert.throws(()=>subject.parseArgs(args),/invalid_arguments/);
});
test('CLI errors never echo supplied values or inherited credentials',()=>{
    const sentinel='fictional-private-input-do-not-print';const env={...process.env,API_KEY:sentinel};delete env.NODE_OPTIONS;delete env.NODE_COMPILE_CACHE;delete env.NODE_V8_COVERAGE;
    const r=spawnSync(process.execPath,[path.resolve(__dirname,'../../../ops/bot-readiness.cjs'),'--unknown',sentinel],{env,encoding:'utf8'});assert.equal(r.status,2);assert.equal((r.stdout+r.stderr).includes(sentinel),false);
});
test('Default CLI runs standalone without app/session loading and prints valid sanitized JSON',()=>{
    const env={...process.env};delete env.NODE_OPTIONS;delete env.NODE_COMPILE_CACHE;delete env.NODE_V8_COVERAGE;
    const r=spawnSync(process.execPath,[path.resolve(__dirname,'../../../ops/bot-readiness.cjs')],{env,encoding:'utf8'});assert.equal(r.status,0);const report=JSON.parse(r.stdout);assert.equal(report.decision,'needs_review');assert.equal(report.mediaStorage.status,'unknown');assert.equal(report.machineRole,'unconfirmed_until_owner_identifies_actual_bot_machine');
});

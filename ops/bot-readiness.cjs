'use strict';
// Standalone, read-only metadata collection. Never load the application or auth stores.
const fs=require('node:fs'),os=require('node:os'),path=require('node:path');
const {execFileSync}=require('node:child_process');
const LOCAL_LINUX_FILESYSTEMS=new Set(['ext2','ext3','ext4','xfs','btrfs','zfs','tmpfs','overlay','vfat','exfat','ntfs','ntfs3']);
function parseArgs(args) {
    const result={alwaysOn:'unknown',uploadMbps:null,mediaRoot:null};
    if(args.length===1&&args[0]==='--help')return {help:true};
    const seen=new Set();
    for(let i=0;i<args.length;i+=2) {
        const key=args[i],value=args[i+1];
        if(!['--media-root','--upload-mbps','--always-on'].includes(key)||seen.has(key)||typeof value!=='string'||value.length>4096||/[\r\n\0]/.test(value))throw Error('invalid_arguments');
        seen.add(key);
        if(key==='--media-root')result.mediaRoot=value;
        if(key==='--always-on'){if(!['yes','no','unknown'].includes(value))throw Error('invalid_arguments');result.alwaysOn=value;}
        if(key==='--upload-mbps') {
            if(!/^[0-9]{1,6}(?:\.[0-9]{1,3})?$/.test(value)||Number(value)<=0||Number(value)>100000)throw Error('invalid_arguments');
            result.uploadMbps=Number(value);
        }
    }
    return result;
}
function finite(value){return typeof value==='number'&&Number.isFinite(value)&&value>=0?value:null;}
function bytes(value){const n=Number(value);return Number.isSafeInteger(n)&&n>=0?n:null;}
function mountType(text,target) {
    let match=null;
    for(const line of text.split('\n')) {
        const sides=line.split(' - '),fields=sides[0].split(' ');
        if(sides.length!==2||fields.length<6)continue;
        const folder=fields[4].replace(/\\([0-7]{3})/g,(_,code)=>String.fromCharCode(parseInt(code,8)));
        if((target===folder||target.startsWith(folder==='/'?'/':folder+'/'))&&(!match||folder.length>match.folder.length))match={folder,type:sides[1].split(' ')[0]};
    }
    return match?.type||null;
}
function diskMetadata(mediaRoot,deps) {
    if(mediaRoot===null)return {status:'unknown',reason:'media_root_not_supplied'};
    const platform=deps.os.platform(),p=platform==='win32'?path.win32:path.posix;
    if(!p.isAbsolute(mediaRoot)||mediaRoot.startsWith('\\\\')||mediaRoot.startsWith('//'))return {status:'unavailable',reason:'local_absolute_path_required'};
    const target=p.resolve(mediaRoot);
    try {
        if(platform==='win32') {
            const drive=/^([A-Za-z]):\\/.exec(target);
            if(!drive)return {status:'unavailable',reason:'local_fixed_volume_required'};
            // Query local drive TYPE before touching a possibly mapped network path.
            // Only one validated drive letter enters this constant read-only command.
            const command="$ErrorActionPreference='Stop'; Get-CimInstance -ClassName Win32_LogicalDisk -Filter \"DeviceID='"+drive[1].toUpperCase()+":'\" | Select-Object -ExpandProperty DriveType | ConvertTo-Json -Compress";
            const powershell='C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe';
            const raw=deps.execFileSync(powershell,['-NoLogo','-NoProfile','-NonInteractive','-Command',command],{encoding:'utf8',windowsHide:true,timeout:5000,maxBuffer:4096,env:{SystemRoot:'C:\\Windows',WINDIR:'C:\\Windows'}});
            if(JSON.parse(raw.replace(/^\uFEFF/,''))!==3)return {status:'unavailable',reason:'local_fixed_volume_required'};
        } else if(platform!=='linux')return {status:'unknown',reason:'storage_probe_not_supported_on_this_os'};
        let current=p.parse(target).root;
        const parts=target.slice(current.length).split(p.sep).filter(Boolean);
        const mounts=platform==='linux'?deps.fs.readFileSync('/proc/self/mountinfo','utf8'):null;
        for(const part of ['',...parts]) {
            if(part)current=p.join(current,part);
            if(platform==='linux'&&!LOCAL_LINUX_FILESYSTEMS.has(mountType(mounts,current)))return {status:'unavailable',reason:'local_filesystem_required'};
            const stat=deps.fs.lstatSync(current);
            if(stat.isSymbolicLink()||!stat.isDirectory())return {status:'unavailable',reason:'plain_directory_required'};
        }
        const stat=deps.fs.statfsSync(target,{bigint:true});
        return {status:'collected',scope:'volume_not_application_quota',totalBytes:bytes(stat.blocks*stat.bsize),availableBytes:bytes(stat.bavail*stat.bsize),writability:'not_tested'};
    } catch {return {status:'unknown',reason:'local_storage_metadata_unavailable'};}
}
function collect(options={},deps={fs,os,execFileSync,nodeVersion:process.version}) {
    const args={mediaRoot:null,alwaysOn:'unknown',uploadMbps:null,...options};
    const major=Number(/^v?(\d+)/.exec(deps.nodeVersion)?.[1]);
    const storage=diskMetadata(args.mediaRoot,deps);
    return {
        schemaVersion:1,purpose:'Read-only bot-machine inventory; not launch approval',
        decision:'needs_review',machineRole:'unconfirmed_until_owner_identifies_actual_bot_machine',
        system:{platform:deps.os.platform(),architecture:deps.os.arch(),osRelease:deps.os.release(),logicalCpuCount:deps.os.cpus().length,totalMemoryBytes:bytes(deps.os.totalmem()),currentlyFreeMemoryBytes:bytes(deps.os.freemem()),currentUptimeSeconds:finite(deps.os.uptime())},
        runtime:{nodeVersion:deps.nodeVersion,testedMajorBaseline:24,matchesTestedMajor:major===24,securityPatchStatus:'not_verified',dependencies:'not_inspected'},
        mediaStorage:storage,
        network:{speedTestExecuted:false,uploadMbps:args.uploadMbps,source:args.uploadMbps===null?'unknown':'owner_provided_unverified',idealTwoGiBUploadSeconds:args.uploadMbps===null?null:Math.ceil(2147483648*8/(args.uploadMbps*1000000))},
        availability:{alwaysOn:args.alwaysOn,source:args.alwaysOn==='unknown'?'unknown':'owner_provided_unverified',sleepPowerAndRestartPolicy:'not_inspected',continuousUptime:'not_verified'},
        safety:{readOnly:true,networkApisUsed:false,applicationLoaded:false,credentialsOrSessionFilesRead:false,mediaContentsRead:false,directoryContentsEnumerated:false,filesWritten:false,configurationChanged:false},
        pending:['Identify this as the actual bot machine','Verify patched runtime and locked dependencies','Measure sustained upload only under a separately approved network test','Verify power/sleep/supervisor and restart recovery','Verify local disk quotas and independent encrypted recovery','Approve and verify real SDK/account/recipient and 2 GiB attachment limits']
    };
}
if(require.main===module) {
    try {
        const args=parseArgs(process.argv.slice(2));
        if(args.help)process.stdout.write('Read-only local metadata only. No network speed test, file writes or application/session loading.\nUsage: node bot-readiness.cjs [--media-root ABSOLUTE_LOCAL_DIRECTORY] [--upload-mbps OWNER_ESTIMATE] [--always-on yes|no|unknown]\n');
        else process.stdout.write(JSON.stringify(collect(args),null,2)+'\n');
    } catch {process.stderr.write('Readiness collection unavailable or arguments invalid; no private details printed.\n');process.exitCode=2;}
}
module.exports={parseArgs,mountType,diskMetadata,collect};

'use strict';
// Original generated test pattern + quiet synthetic tone; never reads media input.
const fs=require('node:fs'),path=require('node:path'),cp=require('node:child_process'),crypto=require('node:crypto');
function generate(output,executable='ffmpeg'){
  const absolute=path.resolve(output), privateRoot=path.resolve(__dirname,'../.recovery-private');
  if(absolute!== '/out'&&!absolute.startsWith(privateRoot+path.sep))throw Error('PRIVATE_FIXTURE_TARGET_REQUIRED');
  fs.mkdirSync(absolute,{recursive:true});if(fs.readdirSync(absolute).length)throw Error('EMPTY_FIXTURE_TARGET_REQUIRED');
  const run=args=>{const r=cp.spawnSync(executable,args,{cwd:absolute,encoding:'utf8',timeout:60000,maxBuffer:1e6});if(r.error||r.status!==0)throw Error('SYNTHETIC_ENCODING_FAILED');};
  for(const [name,size,rate] of [['high','320x180','180k'],['low','160x90','70k']])run(['-hide_banner','-loglevel','error','-f','lavfi','-i','testsrc2=size='+size+':rate=24','-f','lavfi','-i','sine=frequency=440:sample_rate=48000','-t','12','-map_metadata','-1','-c:v','libx264','-threads','1','-preset','ultrafast','-pix_fmt','yuv420p','-profile:v','baseline','-level:v','1.3','-b:v',rate,'-g','48','-keyint_min','48','-sc_threshold','0','-c:a','aac','-b:a','32k','-af','volume=0.02','-f','hls','-hls_time','4','-hls_playlist_type','vod','-hls_flags','independent_segments','-hls_segment_filename',name+'%03d.ts',name+'.m3u8']);
  fs.writeFileSync(path.join(absolute,'master.m3u8'),'#EXTM3U\n#EXT-X-VERSION:3\n#EXT-X-STREAM-INF:BANDWIDTH=240000,RESOLUTION=320x180,CODECS="avc1.42c00d,mp4a.40.2"\nhigh.m3u8\n#EXT-X-STREAM-INF:BANDWIDTH=120000,RESOLUTION=160x90,CODECS="avc1.42c00d,mp4a.40.2"\nlow.m3u8\n');
  fs.writeFileSync(path.join(absolute,'captions.vtt'),'WEBVTT\n\n00:00:00.000 --> 00:00:05.000\nOriginal synthetic Zinema playback fixture.\n\n00:00:05.000 --> 00:00:12.000\nNo movie footage or real speech.\n');
  const files={};for(const name of fs.readdirSync(absolute).sort())files[name]=crypto.createHash('sha256').update(fs.readFileSync(path.join(absolute,name))).digest('hex');
  fs.writeFileSync(path.join(absolute,'fixture.json'),JSON.stringify({dataClass:'synthetic-original',durationSeconds:12,files},null,2)+'\n');
  return {originalFixture:true,durationSeconds:12,files:Object.keys(files).length,bytes:Object.keys(files).reduce((n,f)=>n+fs.statSync(path.join(absolute,f)).size,0)};
}
module.exports={generate};if(require.main===module){try{console.log(JSON.stringify(generate(process.argv[2]||'.recovery-private/zl17-fixture',process.env.FFMPEG_BINARY||'ffmpeg')));}catch{console.error('SYNTHETIC_FIXTURE_GENERATION_FAILED');process.exitCode=1;}}

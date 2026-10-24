/* Zinema authorized HLS player. No arbitrary URL, MP4 or embed fallback. */
(function(root,factory){const api=factory();if(typeof module==='object'&&module.exports)module.exports=api;else root.ZinemaPlayback=api;})(typeof globalThis!=='undefined'?globalThis:this,function(){
'use strict';
const MANIFEST='/api/playback/media.php?file=master.m3u8',CAPTIONS='/api/playback/media.php?file=captions.vtt';
function reference(type,id){if(!['movie','episode','series'].includes(type)||!/^[1-9][0-9]{0,17}$/.test(id))throw Error('INVALID_CONTENT');return {type,id};}
function descriptor(d,now=Date.now()/1000){if(!d||d.status!=='ready'||d.mode!=='local_fixture'||d.manifest!==MANIFEST||d.captions!==CAPTIONS||!Number.isInteger(d.expiresAt)||d.expiresAt<=now||d.expiresAt>now+65||!/^[a-f0-9]{32}$/.test(d.progressScope||'')||!Number.isInteger(d.assetVersion)||d.assetVersion<1)throw Error('INVALID_SOURCE');return d;}
class Progress {
  constructor(storage,scope,type,id,version,now=()=>Date.now()){this.storage=storage;this.now=now;this.enabled=false;this.last=0;this.key='zinema.progress.v1.'+scope+'.'+type+'.'+id+'.'+version;}
  enable(value){this.enabled=!!value;if(!this.enabled)this.clear();}
  read(duration){if(!this.enabled)return 0;try{const r=JSON.parse(this.storage.getItem(this.key));if(!r||!Number.isFinite(r.position)||!Number.isFinite(r.saved)||r.saved>this.now()||this.now()-r.saved>86400000||r.position<0||!Number.isFinite(duration)||duration<=0||r.position>=duration-2)return 0;return r.position;}catch{return 0;}}
  save(position,duration,force=false){if(!this.enabled||!Number.isFinite(position)||!Number.isFinite(duration)||duration<=0)return;if(position>=duration-2){this.clear();return;}if(!force&&this.now()-this.last<5000)return;this.last=this.now();try{const keys=[];for(let i=0;i<this.storage.length;i++){const k=this.storage.key(i);if(k&&k.startsWith('zinema.progress.v1.'))keys.push(k);}while(keys.length>=32)this.storage.removeItem(keys.shift());this.storage.setItem(this.key,JSON.stringify({position:Math.max(0,Math.min(position,duration-2)),saved:this.now()}));}catch{}}
  clear(){try{this.storage.removeItem(this.key);}catch{}}
}
class Player {
  constructor(video,status,options={}){
    this.video=video;this.status=status;this.fetch=options.fetch||globalThis.fetch.bind(globalThis);this.Hls=options.Hls||globalThis.Hls;this.storage=options.storage||null;this.onState=options.onState||(()=>{});this.events=[];this.listeners=[];this.retries=0;this.generation=0;this.remember=false;this.destroyed=false;this.state='idle';
    video.controls=true;video.autoplay=false;video.playsInline=true;
    this.listen('playing',()=>{this.clearWatchdog();this.set('playing','Playing.');});
    this.listen('pause',()=>{if(!['error','expired','unsupported','completed','idle','authorizing'].includes(this.state)){this.save(true);this.set('paused','Paused.');}});
    for(const event of ['waiting','stalled'])this.listen(event,()=>{if(!['error','expired','idle','completed'].includes(this.state)){this.set('buffering','Buffering...');this.watchdog('Playback stalled. Retry to authorize again.');}});
    this.listen('timeupdate',()=>{this.save(false);if(this.state==='buffering'&&!video.paused){this.clearWatchdog();this.set('playing','Playing.');}});
    this.listen('loadedmetadata',()=>{const time=this.progress?.read(video.duration)||0;if(time>0)video.currentTime=time;});
    this.listen('canplay',()=>{this.clearWatchdog();if(this.state==='loading')this.set('ready','Ready. Use Play to start.');});
    this.listen('ended',()=>{this.progress?.clear();this.clearWatchdog();this.set('completed','Playback complete. Replay or browse another title.');});
    this.listen('error',()=>{if(!this.destroyed&&!['idle','expired'].includes(this.state))this.fail('The video could not be loaded. Retry or return to details.');});
  }
  listen(name,handler){this.video.addEventListener(name,handler);this.listeners.push([name,handler]);}
  set(state,message){if(this.destroyed)return;this.state=state;this.status.textContent=message;this.events.push({event:state,at:Date.now()});if(this.events.length>30)this.events.shift();this.onState(state);}
  clearWatchdog(){clearTimeout(this.loadingTimer);}
  watchdog(message){this.clearWatchdog();this.loadingTimer=setTimeout(()=>this.fail(message),10000);}
  save(force){this.progress?.save(this.video.currentTime,this.video.duration,force);}
  stop(){this.clearWatchdog();clearTimeout(this.expiryTimer);this.abort?.abort();this.hls?.destroy();this.hls=null;this.video.pause();this.video.removeAttribute('src');for(const t of Array.from(this.video.querySelectorAll('track')))t.remove();this.video.load();}
  fail(message){this.set('error',message);this.stop();}
  async start(type,id){
    if(this.destroyed)return;const ref=reference(type,id);this.ref=ref;this.generation++;const generation=this.generation;this.stop();this.set('authorizing','Checking playback access...');this.abort=new AbortController();const timeout=setTimeout(()=>this.abort.abort(),8000);
    try{
      const r=await this.fetch('/api/playback/session.php',{method:'POST',credentials:'same-origin',cache:'no-store',headers:{'Content-Type':'application/json','X-Zinema-Playback':'1'},body:JSON.stringify(ref),signal:this.abort.signal});
      if(!r.ok)throw Error('ACCESS_UNAVAILABLE');const data=await r.json();const d=descriptor(data.data);if(this.destroyed||generation!==this.generation)return;
      this.progress=this.storage?new Progress(this.storage,d.progressScope,type,id,d.assetVersion):null;this.progress?.enable(this.remember);
      const track=this.video.ownerDocument.createElement('track');track.kind='subtitles';track.label='English fixture captions';track.srclang='en';track.src=d.captions;this.video.appendChild(track);
      this.set('loading','Loading authorized video...');this.watchdog('The video took too long to load. Retry or return to details.');
      this.expiryTimer=setTimeout(()=>{this.set('expired','Playback access expired. Retry to check access again.');this.stop();},Math.max(0,d.expiresAt*1000-Date.now()));
      if(this.video.canPlayType('application/vnd.apple.mpegurl'))this.video.src=d.manifest;
      else if(this.Hls&&this.Hls.isSupported()){
        const retry={maxNumRetry:1,retryDelayMs:300,maxRetryDelayMs:1000};const load={maxTimeToFirstByteMs:4000,maxLoadTimeMs:8000,timeoutRetry:retry,errorRetry:retry};
        this.hls=new this.Hls({enableWorker:false,maxBufferLength:12,maxMaxBufferLength:24,backBufferLength:8,manifestLoadPolicy:{default:load},playlistLoadPolicy:{default:load},fragLoadPolicy:{default:load}});
        this.hls.on(this.Hls.Events.ERROR,(_event,error)=>{if(error.fatal)this.fail('Playback failed. Retry to check access again.');});
        this.hls.loadSource(d.manifest);this.hls.attachMedia(this.video);
      }else{this.set('unsupported','This browser cannot play HLS. Try a supported browser.');this.stop();return;}
      // This follows an explicit Watch action. Browser autoplay policy may still require Play.
      const play=this.video.play();if(play&&play.catch)play.catch(()=>{if(!['error','expired','unsupported'].includes(this.state)){this.clearWatchdog();this.set('paused','Ready. Press Play to start.');}});
    }catch{if(!this.destroyed&&generation===this.generation)this.fail('Playback is unavailable or access was denied. Retry or return to details.');}
    finally{clearTimeout(timeout);}
  }
  retry(){if(!this.ref||this.retries>=2){this.set('error','Retry limit reached. Return to details and try later.');this.stop();return;}this.retries++;return this.start(this.ref.type,this.ref.id);}
  restart(){this.progress?.clear();if(Number.isFinite(this.video.duration))this.video.currentTime=0;}
  rememberProgress(value){this.remember=!!value;this.progress?.enable(this.remember);}
  destroy(){if(this.destroyed)return;this.save(true);this.generation++;this.destroyed=true;this.stop();for(const [name,handler] of this.listeners)this.video.removeEventListener(name,handler);this.listeners=[];}
}
async function mount(document){
  const root=document.querySelector('[data-zinema-playback]');if(!root)return;
  const status=root.querySelector('[role=status]'),watch=root.querySelector('[data-watch]'),retry=root.querySelector('[data-retry]'),video=root.querySelector('video');
  const query=new URLSearchParams(globalThis.location.search);let ref;
  try{ref=reference(root.dataset.contentType,query.get('id')||'');}catch{status.textContent='Content unavailable.';return;}
  let storage=null;try{storage=globalThis.sessionStorage;}catch{}
  const player=new Player(video,status,{storage,onState:state=>{retry.hidden=!['error','expired'].includes(state);root.querySelector('[data-restart]').hidden=!['playing','paused','completed','ready'].includes(state);}});root.player=player;
  watch.addEventListener('click',()=>{watch.hidden=true;video.hidden=false;player.start(ref.type,ref.id);});
  retry.addEventListener('click',()=>player.retry());root.querySelector('[data-restart]').addEventListener('click',()=>player.restart());root.querySelector('[data-remember]').addEventListener('change',e=>player.rememberProgress(e.target.checked));
  globalThis.addEventListener('pagehide',()=>player.destroy(),{once:true});
  try{const response=await fetch('/api/playback/details.php?'+new URLSearchParams(ref),{credentials:'same-origin',cache:'no-store',signal:AbortSignal.timeout(8000)});if(!response.ok)throw Error();const {data}=await response.json();if(data.id!==ref.id||data.contentType!==ref.type)throw Error();for(const field of ['title','synopsis','language','releaseYear','runtimeSeconds']){const el=root.querySelector('[data-field='+field+']');if(el)el.textContent=String(data[field]);}document.title=data.title+' - Zinema';watch.disabled=!data.watchable;status.textContent=data.watchable?'Ready to watch.':'Playback is unavailable for this title.';}
  catch{status.textContent='Content is unavailable. Return to browsing or try later.';}
}
return {Player,Progress,descriptor,reference,mount};
});
if(typeof document!=='undefined')document.addEventListener('DOMContentLoaded',()=>ZinemaPlayback.mount(document));

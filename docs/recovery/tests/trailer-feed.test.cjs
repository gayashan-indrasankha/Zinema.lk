'use strict';
const {test}=require('node:test');const assert=require('node:assert/strict');const fs=require('node:fs');const path=require('node:path');const vm=require('node:vm');
const source=fs.readFileSync(path.resolve(__dirname,'../../../js/trailer-feed.js'),'utf8');
function context(replies=[]) {
 let requests=0;
 const scope={URL,URLSearchParams,Map,setTimeout,clearTimeout,navigator:{},window:{location:{origin:'http://127.0.0.1:8088',search:''}},console:{error(){},log(){}},fetch:async()=>{requests++;return {ok:true,json:async()=>replies.shift()};}};
 vm.createContext(scope);vm.runInContext(source+'\nglobalThis.Subject=TrailerFeed;',scope);
 const feed=Object.create(scope.Subject.prototype);Object.assign(feed,{loading:false,hasMore:true,page:1,trailers:[],apiEndpoint:'/api/trailers.php',viewTimers:new Map(),currentUserId:1});
 return {scope,feed,requests:()=>requests};
}
test('Trailer markup supports muted playback and explicit browser controls',()=>{
 const {feed}=context();feed.escapeHtml=value=>String(value||'');
 const html=feed.createTrailerHTML({id:1,movie_id:1,trailer_url:'uploads/sample.mp4',thumbnail_url:'uploads/sample.png',movie_title:'Original',description:'Fictional'});
 assert.match(html,/<video[^>]*\bmuted\b/);assert.match(html,/<video[^>]*\bcontrols\b/);
});
test('Overlapping API pages append each trailer once',async()=>{
 const f=context([{trailers:[{id:1},{id:2}]},{trailers:[{id:2},{id:3}]}]);const renders=[];
 f.feed.renderTrailers=(append,batch)=>renders.push({append,ids:batch?.map(row=>row.id)});
 await f.feed.loadTrailers();await f.feed.loadTrailers(true);
 assert.deepEqual(Array.from(f.feed.trailers,row=>row.id),[1,2,3]);assert.deepEqual(Array.from(renders[1].ids),[3]);
});
test('An empty final page stops repeated infinite-scroll requests',async()=>{
 const f=context([{trailers:[]},{trailers:[]}]);f.feed.renderTrailers=()=>{};
 await f.feed.loadTrailers();await f.feed.loadTrailers(true);assert.equal(f.requests(),1);assert.equal(f.feed.hasMore,false);
});
test('Autoplay denial exposes controls instead of an endless spinner',async()=>{
 const {feed}=context();const overlay={style:{display:'flex'}};
 const video={controls:false,parentElement:{querySelector:()=>overlay},play:async()=>{throw Error('fictional autoplay denial');}};
 await feed.playVideo(video);assert.equal(overlay.style.display,'none');assert.equal(video.controls,true);
});
test('Trailer share links target the real feed route',()=>{
 const {feed}=context();assert.equal(feed.shareUrl(7),'http://127.0.0.1:8088/trailers.php?id=7');
});

function button(active,text) {
 const span={textContent:text};let selected=active;
 return {dataset:{},disabled:false,span,classList:{contains:()=>selected,toggle:(_name,value)=>{selected=value===undefined?!selected:value;}},querySelector:()=>span};
}
test('Failed trailer actions restore the exact prior count and saved state',async()=>{
 const {feed}=context([{error:'fixture rejection'},{error:'fixture rejection'}]);
 const like=button(false,'7');await feed.handleLike(1,like);
 assert.equal(like.classList.contains('active'),false);assert.equal(Number(like.span.textContent),7);assert.equal(like.disabled,false);
 const saved=button(true,'Saved');await feed.handleFavorite(1,saved);
 assert.equal(saved.classList.contains('active'),true);assert.equal(saved.span.textContent,'Saved');assert.equal(saved.disabled,false);
});
test('Guest reactions open sign-in without a phantom state or POST',async()=>{
 const f=context();f.feed.currentUserId=0;const like=button(false,'4');
 await f.feed.handleLike(1,like);await f.feed.handleFavorite(1,like);
 assert.equal(f.requests(),0);assert.equal(like.span.textContent,'4');assert.equal(f.scope.window.location.href,'/profile.php');
});
test('Appending initializes and observes new videos without resetting existing playback',()=>{
 const {feed}=context();let loads=0,observations=0,initializations=0;
 const old={dataset:{initialized:'1'},load:()=>{throw Error('Existing playback reset');}};
 const fresh={dataset:{},load:()=>loads++};
 const items=[{dataset:{observed:'1'},querySelector:()=>old},{dataset:{},querySelector:()=>fresh}];
 feed.createTrailerHTML=()=>'<div></div>';feed.setupVideoEvents=()=>initializations++;
 feed.observer={observe:()=>observations++};feed.container={querySelector:()=>({insertAdjacentHTML(){}}),querySelectorAll:()=>items};
 feed.renderTrailers(true,[{id:2}]);feed.renderTrailers(true,[]);
 assert.equal(loads,1);assert.equal(initializations,1);assert.equal(observations,1);
});
test('A shared trailer beyond page one is loaded and scrolled into view',async()=>{
 const f=context([{trailer:{id:77}}]);f.scope.window.location.search='?id=77';let present=false,scrolled=0;
 const target={scrollIntoView:()=>scrolled++};
 f.feed.loadTrailers=async()=>{};f.feed.container={querySelector:()=>present?target:null};
 f.feed.renderTrailers=()=>{present=true;};f.feed.setupIntersectionObserver=()=>{};f.feed.attachEventListeners=()=>{};f.feed.showSwipeInstruction=()=>{};
 await f.feed.init();assert.equal(f.requests(),1);assert.equal(f.feed.trailers[0].id,77);assert.equal(scrolled,1);
});
test('Fully buffered loop transitions do not cover a playable frame with a spinner',()=>{
 const {feed}=context();const events={};const overlay={style:{display:'flex'}};
 const video={duration:4,buffered:{length:1,end:()=>4},parentElement:{querySelector:()=>overlay},addEventListener:(name,callback)=>{events[name]=callback;}};
 feed.setupVideoEvents(video);events.waiting();assert.equal(overlay.style.display,'none');
 video.buffered={length:0};events.waiting();assert.equal(overlay.style.display,'flex');events.loadeddata();assert.equal(overlay.style.display,'none');
});

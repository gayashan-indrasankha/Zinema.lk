'use strict';
const {test}=require('node:test');const assert=require('node:assert/strict');
const fs=require('node:fs');const path=require('node:path');const vm=require('node:vm');
const source=fs.readFileSync(path.resolve(__dirname,'../../../assets/js/search-popup.js'),'utf8');
function fixture(route,reply=[]) {
 const node=()=>({children:[],dataset:{baseUrl:'http://127.0.0.1:8088/'},textContent:'',append(...items){this.children.push(...items);},replaceChildren(){this.children=[];this.textContent='';},addEventListener(){},set innerHTML(_){throw Error('Unsafe HTML sink');}});
 const results=node(),overlay=node(),requests=[];let timer;
 const context={URL,window:{location:{pathname:route}},document:{getElementById:id=>id==='search-popup-overlay'?overlay:results,createElement:node},closeSearchPopup(){},clearTimeout(){},setTimeout:fn=>{timer=fn;},fetch:async url=>{requests.push(url);return {ok:true,json:async()=>reply};}};
 vm.createContext(context);vm.runInContext(source,context);
 return {context,results,requests,run:async query=>{context.handlePopupSearch(query);await timer?.();}};
}
for(const [route,type,prefix] of [['/movie/1/demo','movies','movie'],['/series/1/demo','series','series'],['/collection/1/demo','collections','collection']]) {
 test('Rewritten '+type+' search resolves root API and detail links',async()=>{
  const f=fixture(route,[{id:7,title:'Fictional',cover_image:'uploads/sample.png',release_date:'2026-01-01'}]);await f.run('demo');
  assert.equal(f.requests[0].pathname,'/api/live_search.php');assert.equal(f.requests[0].searchParams.get('type'),type);
  assert.equal(f.results.children[0].children[0].href,'http://127.0.0.1:8088/'+prefix+'/7/watch');
 });
}
test('No-results query and API title stay inert text',async()=>{
 const f=fixture('/movies.php');await f.run('<img src=x onerror=alert(1)>');assert.match(f.results.textContent,/<img/);
 const g=fixture('/movies.php',[{id:1,title:'<svg onload=alert(1)>',cover_image:'javascript:alert(1)'}]);await g.run('demo');
 const card=g.results.children[0].children[0];assert.equal(card.children[1].children[0].textContent,'<svg onload=alert(1)>');assert.equal(card.children[0].src,undefined);
});
test('Clearing search invalidates an older response',async()=>{
 const f=fixture('/movies.php');let release;f.context.fetch=()=>new Promise(resolve=>{release=resolve;});
 const pending=f.run('old');f.context.handlePopupSearch('');release({ok:true,json:async()=>[{id:1,title:'Stale'}]});await pending;
 assert.equal(f.results.children.length,0);assert.equal(f.results.textContent,'');
});

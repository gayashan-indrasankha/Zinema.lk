'use strict';
const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs'),path=require('node:path'),vm=require('node:vm');
const source=fs.readFileSync(path.resolve(__dirname,'../../../admin/trailers.php'),'utf8');
const script=source.match(/<script>([\s\S]*?)<\/script>/)[1];
function editor(data){
 const fields=Object.fromEntries(['edit_id','edit_title','edit_description','edit_status','editModal'].map(id=>[id,{value:'prior',style:{display:'none'}}]));
 const scope={window:{},document:{querySelectorAll:()=>[],getElementById:id=>fields[id],querySelector:selector=>selector==='[data-trailer-id="1"]'&&data?{dataset:data}:null},fetch:()=>{throw Error('Editor must use the authenticated row, not the public feed');}};
 vm.createContext(scope);vm.runInContext(script,scope);return {fields,scope};
}
test('Trailer editor preserves exact trailer metadata and inactive status as inert values',()=>{
 const title='Original "clip" <script>fictional</script> & සිංහල';
 const description='Trailer description, not movie description.\nSecond line and \'quote\'.';
 const {fields,scope}=editor({trailerTitle:title,trailerDescription:description,trailerStatus:'inactive'});
 scope.editTrailer(1);assert.equal(fields.edit_id.value,'1');assert.equal(fields.edit_title.value,title);assert.equal(fields.edit_description.value,description);assert.equal(fields.edit_status.value,'inactive');assert.equal(fields.editModal.style.display,'block');
});
test('Empty optional description and active status populate without undefined text',()=>{
 const {fields,scope}=editor({trailerTitle:'Original sample',trailerDescription:'',trailerStatus:'active'});
 scope.editTrailer(1);assert.equal(fields.edit_description.value,'');assert.equal(fields.edit_status.value,'active');
});
test('Unknown, malformed or incomplete row references close the editor without changing fields',()=>{
 for(const data of [null,{}, {trailerTitle:'Clip',trailerStatus:'active'}, {trailerTitle:'Clip',trailerDescription:'',trailerStatus:'invalid'}]){
  const {fields,scope}=editor(data);fields.editModal.style.display='block';scope.editTrailer(1);assert.equal(fields.editModal.style.display,'none');assert.equal(fields.edit_title.value,'prior');
 }
 for(const id of [0,-1,'1"]','missing']){const {fields,scope}=editor({trailerTitle:'Clip',trailerDescription:'',trailerStatus:'active'});scope.editTrailer(id);assert.equal(fields.editModal.style.display,'none');assert.equal(fields.edit_title.value,'prior');}
});
test('Cancel and outside click close the modal without altering metadata',()=>{
 const {fields,scope}=editor({trailerTitle:'Original',trailerDescription:'',trailerStatus:'active'});
 scope.editTrailer(1);scope.closeEditModal();assert.equal(fields.editModal.style.display,'none');assert.equal(fields.edit_title.value,'Original');scope.editTrailer(1);scope.window.onclick({target:fields.editModal});assert.equal(fields.editModal.style.display,'none');
});

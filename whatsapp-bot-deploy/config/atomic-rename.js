'use strict';
const fs=require('node:fs'),os=require('node:os');
// Windows scanners/readers can briefly deny atomic replacement. Never unlink the
// old checkpoint as a workaround. Six attempts, at most 115 ms of waiting.
function replace(source,target,{rename=fs.renameSync,platform=os.platform(),pause=ms=>Atomics.wait(new Int32Array(new SharedArrayBuffer(4)),0,0,ms)}={}) {
 for(let attempt=0;;attempt++) {
  try{return rename(source,target);}
  catch(error){if(platform!=='win32'||!['EPERM','EACCES','EBUSY'].includes(error.code)||attempt>=5)throw error;pause(Math.min(5*(2**attempt),40));}
 }
}
module.exports={replace};

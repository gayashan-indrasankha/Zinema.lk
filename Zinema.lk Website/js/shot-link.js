'use strict';
document.addEventListener('DOMContentLoaded',async()=>{
 const status=document.querySelector('[role=status]');const id=new URLSearchParams(location.search).get('id')||'';
 try{if(!/^[1-9][0-9]{0,17}$/.test(id))throw Error();const response=await fetch('/api/playback/link.php?shot_id='+encodeURIComponent(id),{credentials:'same-origin',cache:'no-store',signal:AbortSignal.timeout(8000)});if(!response.ok)throw Error();const {data}=await response.json();if(!/^(movie|series)-details\.php\?catalogue=1&id=[1-9][0-9]{0,17}$/.test(data.href))throw Error();location.replace('/'+data.href);}
 catch{status.textContent='Linked content is unavailable. Return to Shots or browse another title.';}
});

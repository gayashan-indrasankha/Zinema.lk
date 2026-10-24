/* Capability UI only; PHP/Node independently deny all live operations. */
(function(root){'use strict';
const messages={whatsapp:'WhatsApp delivery is currently unavailable.',payments:'Payments are currently unavailable.',extraction:'External video extraction is disabled.',external_media:'This delivery method is currently unavailable.'};
function available(){return false;} // No browser setting or server status can approve a provider.
function disable(document){
 for(const el of document.querySelectorAll('a[href], [data-integration], #submitBtnModal')){
  let feature=el.dataset.integration;const href=el.getAttribute('href')||'';
  if(/(?:^|\/)whatsapp-watch\.php(?:\?|$)|(?:^|\/)download\.php(?:\?|$)|^(?:https?:\/\/wa\.me\/|whatsapp:)/i.test(href))feature='whatsapp';
  if(el.id==='submitBtnModal')feature='payments';if(!Object.hasOwn(messages,feature))continue;
  el.setAttribute('aria-disabled','true');el.setAttribute('title',messages[feature]);el.textContent=messages[feature];if('disabled' in el)el.disabled=true;if(el.tagName==='A')el.removeAttribute('href');
 }
 const form=document.getElementById('subscribeFormModal');if(form)form.addEventListener('submit',event=>event.preventDefault());
}
root.ZinemaIntegrations=Object.freeze({available,disable});
if(typeof document!=='undefined'){document.addEventListener('DOMContentLoaded',()=>disable(document));document.addEventListener('click',event=>{if(event.target.closest('[aria-disabled="true"]')){event.preventDefault();event.stopImmediatePropagation();}},true);}
})(typeof window!=='undefined'?window:globalThis);

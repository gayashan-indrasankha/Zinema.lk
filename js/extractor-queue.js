(function(root){
    'use strict';
    async function waitForExtraction(request,source,notify=()=>{},sleep=ms=>new Promise(resolve=>setTimeout(resolve,ms))) {
        let result=await request('extract',source);
        for(let attempt=0;result.mode==='account-queue' && result.state!=='done';attempt++) {
            notify(result.state);
            if(result.state==='held')throw Error('extractor_held_for_review');
            if(attempt>=40)throw Error('extractor_still_pending');
            await sleep(1500);result=await request('status',result.job_id);
        }
        if(typeof result.url!=='string'||!result.url)throw Error('extractor_unavailable');
        return result;
    }
    if(typeof module==='object' && module.exports)module.exports={waitForExtraction};
    else root.ZinemaExtractor={waitForExtraction};
})(typeof window==='object'?window:globalThis);

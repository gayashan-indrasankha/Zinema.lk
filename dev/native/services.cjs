'use strict';
// Only localhost substitutes. Does not import Baileys, accounts, pairing or upstream fetchers.
const fs=require('node:fs'),path=require('node:path'),http=require('node:http'),crypto=require('node:crypto'),{createRequire}=require('node:module');
const root=path.resolve(__dirname,'../..'),role=process.argv[2],home=path.resolve(process.argv[3]||'');
if(!['bot','extractor'].includes(role)||path.dirname(home)!==path.join(root,'.runtime')||!/^audit-[0-9]{8}-[0-9]{6}$/.test(path.basename(home)))throw Error('native_fixture_denied');
if(role==='bot'&&fs.existsSync(path.join(home,'native/whatsapp-live.json')))throw Error('simulator_refuses_live_configuration');
const c=JSON.parse(fs.readFileSync(path.join(home,'native-config.json'),'utf8')),owner=JSON.parse(fs.readFileSync(path.join(home,'ownership.json'),'utf8'));
if(c.schema!==1||c.external_connections!==false||c.publishing!==false||owner.native_owner!==c.owner||c.environment.APP_ENV!=='test'||c.environment.BASE_URL!=='http://127.0.0.1:8088'||c.environment.DB_PORT!=='3307'||!/^[a-f0-9]{64}$/.test(c.control_key))throw Error('native_fixture_denied');
console.log=()=>{};console.error=()=>{};
let server,closing=false;
if(role==='extractor') {
    const deps=path.resolve(c.express_deps);
    if(!deps.startsWith(path.join(root,'.runtime')+path.sep)||!/^http:\/\/127\.0\.0\.1:8088\/uploads\/trailers\/[a-f0-9]{32}\.mp4$/.test(c.fake_media_url))throw Error('native_fixture_denied');
    const installed=createRequire(path.join(deps,'package.json')),factory=fs.readFileSync(path.join(root,'fb-video-api/app.js'),'utf8'),vm=require('node:vm'),box={module:{exports:{}},process,require:id=>id==='express'?installed(id):id.startsWith('.')?require(path.resolve(root,'fb-video-api',id)):require(id)};
    vm.runInNewContext(factory,box,{filename:'fb-video-api/app.js'});
    server=box.module.exports.createApp({mode:'local',apiKey:c.environment.EXTRACTOR_API_KEY,fakeMediaUrl:c.fake_media_url}).listen(3334,'127.0.0.1');
} else {
    Object.assign(process.env,{APP_ENV:'test',API_URL:'http://127.0.0.1:8088/api/whatsapp',API_KEY:c.environment.WHATSAPP_API_KEY,
        SESSION_PATH:path.join(home,'native/bot'),LOCAL_MEDIA_PATH:path.join(home,'native/bot/media'),BOT_INSTANCE_ID:'910000001',ALLOW_LIVE_WHATSAPP:'false'});
    const api=require('../../whatsapp-bot-deploy/config/api-client'),{deliverClaimedToken}=require('../../whatsapp-bot-deploy/config/token-delivery');
    const {ResourcePressure}=require('../../whatsapp-bot-deploy/config/resource-pressure'),FileManager=require('../../whatsapp-bot-deploy/config/file-manager'),{inspectMedia}=require('../../whatsapp-bot-deploy/config/media-policy');
    const guard=new ResourcePressure(process.env.LOCAL_MEDIA_PATH,{minFreeDiskBytes:128*1024*1024}),files=new FileManager(process.env.LOCAL_MEDIA_PATH,{resourceGuard:guard});
    const store=path.join(home,'native/bot/state.json');let state={schema:1,owner:c.owner,online:true,outbox:[],intents:[]},busy=false;
    if(fs.existsSync(store)) {
        if(fs.lstatSync(store).isSymbolicLink()||fs.statSync(store).size>262144)throw Error('native_state_denied');
        state=JSON.parse(fs.readFileSync(store,'utf8'));
        if(state.schema!==1||state.owner!==c.owner||!Array.isArray(state.outbox)||state.outbox.length>200||!Array.isArray(state.intents)||state.intents.length>200)throw Error('native_state_denied');
        if(state.intents.some(item=>item.state==='intent')) {for(const item of state.intents)if(item.state==='intent')item.state='held';state.online=false;}
    }
    function save(){const temp=store+'.'+crypto.randomBytes(8).toString('hex')+'.tmp';try{const fd=fs.openSync(temp,'wx',0o600);try{fs.writeFileSync(fd,JSON.stringify(state));fs.fsyncSync(fd);}finally{fs.closeSync(fd);}fs.renameSync(temp,store);}finally{if(fs.existsSync(temp))fs.unlinkSync(temp);}}
    const ready=()=>!closing&&state.online&&guard.sample().allowed&&!state.intents.some(item=>item.state==='held');
    async function heartbeat(){try{await api.request('bot-heartbeat.php',{bot_id:910000001,bot_phone:'000000000001',status:ready()?'online':'offline',queue_size:busy?1:0,uptime_seconds:Math.floor(process.uptime()),memory_usage_mb:Math.floor(process.memoryUsage().rss/1048576)});}catch{}}
    function answer(response,status,value){response.writeHead(status,{'Content-Type':'application/json','Cache-Control':'no-store'});response.end(JSON.stringify(value));}
    const project=()=>({success:true,online:ready(),busy,outbox:state.outbox.map(item=>({...item})),held:state.intents.filter(item=>item.state==='held').map(item=>({job_id:item.job_id,state:'held'})),mode:'local-simulator'});
    server=http.createServer(async(request,response)=>{
        const supplied=request.headers['x-local-control'];
        if(typeof supplied!=='string'||!/^[a-f0-9]{64}$/.test(supplied)||!crypto.timingSafeEqual(Buffer.from(supplied),Buffer.from(c.control_key)))return answer(response,403,{success:false,error:'local_control_required'});
        if(request.method!=='POST')return answer(response,405,{success:false,error:'post_required'});
        let raw='';try{
            for await(const chunk of request){raw+=chunk;if(Buffer.byteLength(raw)>2048)return answer(response,413,{success:false,error:'request_too_large'});}
            const input=JSON.parse(raw||'{}');
            if(request.url==='/status')return answer(response,200,project());
            if(request.url==='/control'&&['online','offline'].includes(input.action)) {state.online=input.action==='online';save();await heartbeat();return answer(response,200,project());}
            if(request.url==='/shutdown') {if(busy)return answer(response,409,{success:false,error:'busy'});closing=true;save();await heartbeat();answer(response,200,{success:true});clearInterval(timer);server.close();return;}
            if(request.url!=='/deliver'||typeof input.token!=='string'||!/^[A-Z0-9]{12}$/.test(input.token)||Object.keys(input).some(k=>k!=='token'))return answer(response,400,{success:false,error:'invalid_request'});
            if(!ready()||busy||state.intents.length>=200||state.outbox.length>=200)return answer(response,409,{success:false,error:'offline_busy_or_review_required'});
            const available=await api.getTokenData(input.token);
            if(available.valid!==true||!available.data||!files.getFilePath(available.data.content_type,available.data.content_id,available.data.part_number))return answer(response,404,{success:false,error:'token_or_demo_media_unavailable'});
            if(busy)return answer(response,409,{success:false,error:'busy'});
            busy=true;const tokenHash=crypto.createHash('sha256').update(input.token).digest('hex');
            if(state.intents.some(item=>item.token_hash===tokenHash)) {busy=false;return answer(response,409,{success:false,error:'already_attempted'});}
            const intent={job_id:crypto.randomBytes(16).toString('hex'),token_hash:tokenHash,state:'intent'};state.intents.push(intent);save();
            let result;
            try {
                result=await deliverClaimedToken({claim:api.claimToken,transition:api.transitionToken,forward:async(_recipient,data)=>{
                    const file=files.getFilePath(data.content_type,data.content_id,data.part_number),media=file?inspectMedia(file):null;
                    if(!media)return {success:false};guard.assertReady(media.bytes);
                    const digest=crypto.createHash('sha256');for await(const chunk of fs.createReadStream(file,{highWaterMark:65536}))digest.update(chunk);
                    state.outbox.push({job_id:intent.job_id,content_type:data.content_type,content_id:Number(data.content_id),bytes:media.bytes,
                        sha256:digest.digest('hex'),attachment:'original-demo.mp4',simulated:true});save();return {success:true,tier:'local-fake'};
                }},input.token,'fictional-local-owner');
                intent.state=result.success?'completed':result.status==='uncertain'||result.status==='held'?'held':'rejected';save();
                answer(response,200,{success:result.success,state:intent.state,job_id:intent.job_id,simulated:true});
            } catch {intent.state='held';save();answer(response,503,{success:false,error:'held_for_review'});}
            finally {busy=false;await heartbeat();}
        } catch{if(!response.writableEnded)answer(response,400,{success:false,error:'invalid_local_request'});}
    });
    server.listen(9090,'127.0.0.1',()=>void heartbeat());const timer=setInterval(()=>void heartbeat(),5000);
    const stop=async()=>{if(closing)return;closing=true;save();clearInterval(timer);await heartbeat();server.close();};
    process.on('SIGTERM',()=>void stop());process.on('SIGINT',()=>void stop());
}
if(role==='extractor'){process.on('SIGTERM',()=>server.close());process.on('SIGINT',()=>server.close());}

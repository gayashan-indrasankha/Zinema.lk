'use strict';
// One persistent retry budget across socket instances. Never edits session files.
class ReconnectController {
    constructor(connect,{schedule=setTimeout,cancel=clearTimeout,maxAttempts=10,onExhausted=()=>{},onError=()=>{}}={}) {
        Object.assign(this,{connect,schedule,cancel,maxAttempts,onExhausted,onError});
        this.attempts=0;this.timer=null;
        this.connecting=false;this.retryRequested=false;this.epoch=0;
    }
    disconnected(retry=true) {
        if (!retry) {this.stop();return false;}
        if(this.connecting){this.retryRequested=true;return false;}
        if (this.timer!==null) return false;
        if (this.attempts>=this.maxAttempts) {this.onExhausted();return false;}
        const delay=Math.min(5000*2**this.attempts++,60000);
        const epoch=this.epoch;
        this.timer=this.schedule(async()=>{
            this.timer=null;this.connecting=true;this.retryRequested=false;
            try {await this.connect();}catch {this.onError();this.retryRequested=true;}
            finally{this.connecting=false;if(epoch===this.epoch&&this.retryRequested)this.disconnected();}
        },delay);
        return true;
    }
    connected() {this.stop();this.attempts=0;}
    stop() {if(this.timer!==null)this.cancel(this.timer);this.timer=null;this.retryRequested=false;this.epoch++;}
}
module.exports=ReconnectController;

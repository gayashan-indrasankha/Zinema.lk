'use strict';
// Private request file, not CLI secret values. Every operation is local/fake only.
const {read}=require('./private-files.cjs');
async function run(input) {
 require('./policy.cjs').validate(require('./promotion-policy.json'));
 const pairing=require('./pairing.cjs'),review=require('./maintenance-review.cjs'),backup=require('./stream-backup.cjs');
 switch(input.action) {
  case 'pairing-prepare':return pairing.prepare(input.session,input.keyFile,input.review);
  case 'pairing-review':return pairing.review(input.session,input.keyFile);
  case 'pairing-approve':return pairing.approve(input.session,input.keyFile,input.review);
  case 'maintenance-review':return review.review(input.session);
  case 'maintenance-resolve':return review.resolve(input.session,input.review,()=>{
   const proof=JSON.parse(read(input.metadataEvidenceFile,16384));if(proof.fixture!==true)throw Error();return proof;
  });
  case 'maintenance-recover':return review.recover(input.session,input.review);
  case 'backup-encrypt':return backup.transform('encrypt',input.source,input.target,input.keyFile,{writersQuiesced:input.writersQuiesced===true});
  case 'backup-decrypt':return backup.transform('decrypt',input.source,input.target,input.keyFile);
  case 'backup-mirror':return backup.mirror(input.source,input.target,input.expectedSha256);
  case 'monitor':return require('./monitor.cjs').record(input.directory,input.metrics,require('./promotion-policy.json'));
  default:throw Error();
 }
}
module.exports={run};
if(require.main===module)(async()=>{
 try{if(process.argv.length!==3)throw Error();const input=JSON.parse(read(process.argv[2],16384));console.log(JSON.stringify(await run(input)));}
 catch{console.log('{"success":false,"error":"local_promotion_operation_denied"}');process.exitCode=1;}
})();

'use strict';
const policy=require('../config/integration-policy.json');
class IntegrationDisabled extends Error {constructor(){super('Integration unavailable.');this.code='INTEGRATION_DISABLED';}}
function definition(feature){if(!Object.prototype.hasOwnProperty.call(policy.features,feature))throw new IntegrationDisabled();return policy.features[feature];}
function status(feature,env=process.env){const d=definition(feature);const simulation=d.simulation&&env.APP_ENV==='test'&&env[d.flag]==='local';return Object.freeze({available:false,state:simulation?'simulation_only':'disabled',message:d.message});}
function assertLive(feature){definition(feature);throw new IntegrationDisabled();}
function assertSimulation(feature,env=process.env){if(status(feature,env).state!=='simulation_only')throw new IntegrationDisabled();}
/** Guard an existing callback at invocation, not only at process startup. */
function protect(feature,callback){definition(feature);if(typeof callback!=='function')throw new TypeError('CALLBACK_REQUIRED');return function(...args){assertLive(feature);return Reflect.apply(callback,this,args);};}
function blockEntry(feature,entry){if(require.main===entry){process.stderr.write(JSON.stringify({error:'INTEGRATION_DISABLED',available:false})+'\n');process.exit(1);}assertLive(feature);}
module.exports=Object.freeze({status,assertLive,blockEntry,assertSimulation,protect,IntegrationDisabled});

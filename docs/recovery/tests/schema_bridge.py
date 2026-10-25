"""Rebuild canonical structure and test SQL-only delivery adapters with a fake key.
No WhatsApp library, saved session, real recipient or external service is used.
"""
import hashlib
import json
import os
from pathlib import Path
import secrets
import subprocess
import sys
import time
import urllib.error
import urllib.request
sys.dont_write_bytecode = True
from audit import ROOT, PHP, DB_PORT, checked_runtime, port_open, sql, wait_port, NO_WINDOW, RESULT_PREFIX

folder = checked_runtime(sys.argv[1])
assert not port_open(8089)
ddl = (ROOT/'database/schema.sql').read_text(encoding='utf-8')
probe = 'zinema_recovery_probe_' + str(time.time_ns())
sql(folder, 'CREATE DATABASE '+probe+' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; USE '+probe+';\n'+ddl)
tables = sql(folder,"SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='"+probe+"' AND table_type='BASE TABLE';")
views = sql(folder,"SELECT COUNT(*) FROM information_schema.views WHERE table_schema='"+probe+"';")
assert tables == '38' and views == '2'
rows = [{'name':'Canonical schema rebuilds in a new empty database','passed':True}]
password = secrets.token_hex(24)
username = 'zinema_recovery_bridge_' + secrets.token_hex(4)
sql(folder,"CREATE USER '"+username+"'@'127.0.0.1' IDENTIFIED BY '"+password+"'; GRANT ALL ON zinema_recovery_test.* TO '"+username+"'@'127.0.0.1';")
sql(folder,"""INSERT INTO whatsapp_tokens(id,token,content_type,content_id,part_number,expires_at) VALUES
 (101,'AUDITFULL001','movie',1,NULL,DATE_ADD(NOW(),INTERVAL 1 HOUR)),
 (102,'AUDITPART001','movie',1,1,DATE_ADD(NOW(),INTERVAL 1 HOUR)),
 (103,'AUDITOLD0001','movie',1,NULL,DATE_SUB(NOW(),INTERVAL 1 HOUR))
 ON DUPLICATE KEY UPDATE is_used=0,is_active=1,claim_hash=NULL,claim_owner=NULL,claim_state=NULL,claimed_at=NULL,expires_at=VALUES(expires_at);""",True)
key = secrets.token_hex(24)
sessions = folder/('bridge-sessions-'+secrets.token_hex(4)); sessions.mkdir()
env = dict(os.environ, APP_ENV='test', BASE_URL='http://127.0.0.1:8089', DB_HOST='127.0.0.1', DB_PORT=str(DB_PORT), DB_NAME='zinema_recovery_test', DB_USER=username, DB_PASS=password, SESSION_SAVE_PATH=str(sessions), ALLOW_LIVE_INTEGRATIONS='false', WHATSAPP_API_KEY=key)
for name in ['MAIL','OAUTH','MEDIA','EXTRACTOR','PAYMENT']: env[name+'_MODE']='disabled'
env['WHATSAPP_MODE']='local'
allowed = ['bot-heartbeat','add-message-id','get-message-id','get-message-ids','update-cache-message-id','update-forward-stats','get-aging-messages','get-popular-files','get-popular-content','mark-used','validate-token','claim-token']
router = folder/'bridge-router.php'
router.write_text("<?php\n$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);\n$allowed = "+repr(['/api/whatsapp/'+name+'.php' for name in allowed]).replace('[','[').replace(']',']')+";\nif (!in_array($path, $allowed, true)) { http_response_code(403); exit; }\nrequire __DIR__ . '/web' . $path;\n",encoding='utf-8')
log = open(folder/'bridge-console.log','w')
child = subprocess.Popen([str(PHP),'-d','display_errors=0','-d','log_errors=1','-d','error_log='+str(folder/'bridge-php.log'),'-S','127.0.0.1:8089',str(router)],env=env,stdout=log,stderr=log,creationflags=NO_WINDOW)

def request(name, data, api_key=key):
    body = json.dumps(data).encode()
    req = urllib.request.Request('http://127.0.0.1:8089/api/whatsapp/'+name+'.php', data=body, headers={'Content-Type':'application/json','X-Api-Key':api_key})
    try: response=urllib.request.urlopen(req,timeout=10)
    except urllib.error.HTTPError as error: response=error
    try: parsed=json.loads(response.read())
    except ValueError: parsed={}
    return response.status,parsed

def record(name, passed):
    rows.append({'name':name,'passed':bool(passed)})

try:
    wait_port(8089,child)
    record('Bridge rejects an incorrect fake API key',request('bot-heartbeat',{'bot_id':1},'wrong-key')[0]==401)
    record('Fictional heartbeat uses canonical health columns',request('bot-heartbeat',{'bot_id':1,'status':'online'})[1].get('success') is True)
    for kind,part in [('movie',0),('movie',1),('episode',1)]:
        record(f'{kind} part {part} mapping is writable',request('add-message-id',{'content_type':kind,'content_id':1,'part_number':part,'message_id':'fictional-message','file_name':'demo.mp4','file_size':100})[1].get('success') is True)
        record(f'{kind} part {part} cache metadata uses the same mapping',request('update-cache-message-id',{'content_type':kind,'content_id':1,'part_number':part,'cache_message_id':'fictional-cache'})[1].get('success') is True)
    record('Full mapping can be read back',request('get-message-id',{'content_type':'movie','content_id':1})[1].get('found') is True)
    record('Multipart mapping can be read back',request('get-message-ids',{'content_type':'movie','content_id':1})[1].get('count')==2)
    for token in [101,102]:
        record(f'Statistics token fixture {token} resolves canonical content',request('update-forward-stats',{'token_id':token,'hit_type':'cache_hit'})[1].get('success') is True)
    sql(folder,"UPDATE whatsapp_message_ids SET local_file_path='fictional-local-path', updated_at=DATE_SUB(NOW(),INTERVAL 20 DAY) WHERE content_type='movie' AND content_id=1;",True)
    record('Popular-content view executes against populated mappings',request('get-popular-files',{})[1].get('count',0)>0)
    record('Recent content uses series and canonical token fields',request('get-popular-content',{})[1].get('success') is True)
    record('Aging read model finds the full-file mapping',request('get-aging-messages',{'days_old':10})[1].get('count',0)>0)
    record('Legacy mark-used cannot bypass ownership',request('mark-used',{'token_id':101})[1].get('success') is False)
    owner = hashlib.sha256(b'fictional-bridge-recipient').hexdigest()
    claim = request('claim-token',{'token':'AUDITFULL001','recipient_hash':owner,'bot_id':1})[1]
    record('Unused active token grants a delivery claim',claim.get('success') is True)
    proof = {'token_id':101,'recipient_hash':owner,'bot_id':1,'receipt':claim.get('receipt','')}
    record('Claim owner may begin delivery',request('claim-token',dict(proof,action='begin'))[1].get('success') is True)
    record('Acknowledged owner consumes token',request('claim-token',dict(proof,action='complete'))[1].get('success') is True)
    record('Repeated token consumption is rejected',request('claim-token',dict(proof,action='complete'))[1].get('success') is False)
    record('Expired token cannot be claimed',request('claim-token',{'token':'AUDITOLD0001','recipient_hash':owner,'bot_id':1})[1].get('success') is False)

finally:
    child.terminate(); child.wait(timeout=5); log.close()
result={'schema_sha256':hashlib.sha256((ROOT/'database/schema.sql').read_bytes()).hexdigest(),'tables':38,'views':2,'legacy_rows_imported':0,'bridge_environment':'test; local SQL adapter only; fake API key','bridge_stopped':not port_open(8089),'tests':rows,'summary':{'total':len(rows),'passed':sum(row['passed'] for row in rows),'failed':sum(not row['passed'] for row in rows)}}
(ROOT/f'docs/recovery/{RESULT_PREFIX}_SCHEMA_RESULTS.json').write_text(json.dumps(result,indent=2)+'\n',encoding='utf-8')
print(json.dumps(result,indent=2))
raise SystemExit(1 if result['summary']['failed'] else 0)

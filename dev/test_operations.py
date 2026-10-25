"""CLI/HTTP regressions on fictional claims and separate owned fixture media volumes."""
import concurrent.futures
import hashlib
import json
from pathlib import Path
import secrets
import subprocess
import sys
import urllib.error
import urllib.request
from local import BASE, ENV, ROOT, STATE, operator_request, require_local_docker, require_owned_gateway

RUN=secrets.token_hex(6)
KEY=(STATE/'control_key').read_text().strip()
APIKEY=(STATE/'adapter_key').read_text().strip()
ROWS=[]


def check(name,condition):
    ROWS.append({'name':name,'passed':bool(condition),'evidence':'runtime verified'})
    print(('PASS ' if condition else 'FAIL ')+name,flush=True)


def post(path,data,bridge=False):
    assert path.startswith('/__dev/')
    req=urllib.request.Request('http://127.0.0.1:8088'+path,data=json.dumps(data).encode(),
        headers={'Content-Type':'application/json','X-Test-Key':KEY,**({'X-Api-Key':APIKEY} if bridge else {})})
    try: response=urllib.request.urlopen(req,timeout=15)
    except urllib.error.HTTPError as error: response=error
    return json.loads(response.read())


def control(action,**values): return post('/__dev/control.php',dict(values,action=action))


def bridge(action,token=None,**values):
    data={'action':action,'recipient_hash':hashlib.sha256(b'fictional-recipient').hexdigest(),'bot_id':1,**values}
    if token: data['token']=token
    return post('/__dev/bridge.php?endpoint=claim-token',data,True)


def held_fixture(state='claimed',old=True):
    token='T'+secrets.token_hex(6).upper()[:11]
    token_id=control('token',token=token,active=True)['id']
    claim=bridge('claim',token=token)
    if claim.get('success') is not True: raise RuntimeError('Fictional claim unavailable')
    proof={'token_id':token_id,'receipt':claim['receipt']}
    if state in {'sending','uncertain'}: bridge('begin',**proof)
    if state=='uncertain': bridge('fail',**proof)
    if old: control('age_claim',token=token)
    return token,proof


def review(proof): return operator_request({'action':'review','token_id':proof['token_id']})['review']


def decision(proof,row,**extra):
    return dict(action='resolve',token_id=proof['token_id'],revision=row['claim_revision'],outcome='not_delivered',
        actor='demo_fixture',evidence_ref='fixture:case-'+RUN,workers_stopped=True,**extra)


def fixture(action,**values):
    result=subprocess.run(BASE+['exec','-T','operator-fixtures','php','/opt/zinema/dev/fixture_worker.php'],
        cwd=ROOT,env=ENV,input=json.dumps(dict(values,action=action,run=RUN)),capture_output=True,text=True,timeout=30)
    try: return json.loads(result.stdout)
    except ValueError: raise RuntimeError('Fixture response unavailable') from None


def inspect_inventory(): return operator_request({'action':'inventory'},'operator-fixtures')


def status(inventory,path): return next((item['status'] for item in inventory.get('managed',[]) if item['path']==path),None)


def operator_tests():
    pasted='fictional-secret-should-not-echo'
    invalid=subprocess.run([sys.executable,str(ROOT/'dev/local.py'),'operator','review','--token-id',pasted],cwd=ROOT,env=ENV,capture_output=True,text=True)
    check('CLI argument errors do not echo accidentally pasted bearer material',invalid.returncode==2 and pasted not in invalid.stdout+invalid.stderr)
    token,proof=held_fixture('sending');row=review(proof)
    check('Old sending claim is reviewable with an integer revision',row['eligible'] is True and row['claim_revision']==2)
    listing=operator_request({'action':'held','after_id':proof['token_id']-1})
    serialized=json.dumps(listing)
    check('Held listing exposes no bearer token, receipt or recipient hash',
        listing.get('success') is True and all(secret not in serialized for secret in [token,proof['receipt'],hashlib.sha256(b'fictional-recipient').hexdigest()]))
    payload=decision(proof,row)
    check('Resolution requires explicit stopped-worker attestation',operator_request(dict(payload,workers_stopped=False)).get('error')=='review_acknowledgement_required')
    check('Resolution accepts only fictional operator/evidence references',operator_request(dict(payload,actor='arbitrary')).get('error')=='review_acknowledgement_required' and operator_request(dict(payload,evidence_ref='unreviewed')).get('error')=='review_acknowledgement_required')
    denied=operator_request(dict(payload,revision=row['claim_revision']-1))
    check('Stale operator revision cannot resolve a claim',denied.get('error')=='stale_review' and review(proof)['reconciled_at'] is None)
    closed=operator_request(payload)
    check('Confirmed non-delivery atomically closes and audits the old claim',closed.get('success') is True and closed['audit']['outcome']=='not_delivered' and review(proof)['is_active']==0 and review(proof)['is_used']==0)
    check('An identical repeat returns the original audit without another mutation',operator_request(payload).get('replayed') is True and review(proof)['claim_revision']==row['claim_revision']+1)
    check('A contradictory decision is rejected',operator_request(dict(payload,outcome='delivered')).get('error')=='decision_conflict')
    check('Closed non-delivery never releases the old token or worker receipt',bridge('claim',token=token).get('success') is False and bridge('complete',**proof).get('success') is False)
    token,proof=held_fixture('uncertain');row=review(proof)
    resolved=operator_request(dict(decision(proof,row),outcome='delivered'))
    after=review(proof)
    check('Confirmed prior delivery consumes an uncertain claim with audit',resolved.get('success') is True and after['claim_state']=='completed' and after['is_used']==1 and after['is_active']==0)
    check('A late bot acknowledgement cannot overwrite operator resolution',bridge('complete',**proof).get('success') is False)
    token,proof=held_fixture(old=False);row=review(proof)
    check('Recent claims stay held',row['eligible'] is False and operator_request(decision(proof,row)).get('error')=='claim_not_eligible')
    token,proof=held_fixture();row=review(proof);bridge('begin',**proof)
    check('A worker transition invalidates an earlier operator review',operator_request(decision(proof,row)).get('error')=='stale_review')
    token,proof=held_fixture();row=review(proof);closed=operator_request(decision(proof,row))
    check('A stopped-before-send claim can close without allowing a late begin',closed.get('success') is True and bridge('begin',**proof).get('success') is False)
    token,proof=held_fixture('sending');row=review(proof);payload=decision(proof,row)
    with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
        same=list(pool.map(operator_request,[payload,payload]))
    check('Concurrent identical operators produce one audit and one replay',all(r.get('success') is True for r in same) and sorted(r['replayed'] for r in same)==[False,True] and len({r['audit']['id'] for r in same})==1)
    token,proof=held_fixture('uncertain');row=review(proof);payload=decision(proof,row)
    with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
        conflict=list(pool.map(operator_request,[payload,dict(payload,outcome='delivered')]))
    check('Concurrent conflicting decisions cannot both succeed',sum(r.get('success') is True for r in conflict)==1 and sum(r.get('error')=='decision_conflict' for r in conflict)==1)
    for name,value in [('APP_ENV','production'),('DB_NAME','unexpected_target'),('WHATSAPP_MODE','local'),('UPLOAD_STORAGE_OWNER','zinema-dev-00000000')]:
        result=subprocess.run(BASE+['exec','-T','-e',name+'='+value,'web','php','/opt/zinema/dev/operator.php'],cwd=ROOT,env=ENV,input='{"action":"held"}',capture_output=True,text=True)
        try: parsed=json.loads(result.stdout)
        except ValueError: parsed={}
        check('Operator rejects unexpected '+name,parsed.get('success') is False)
    check('Inventory cannot accept a deletion/apply flag',operator_request({'action':'inventory','apply':True}).get('error')=='invalid_request')
    check('Cleanup mutation is not an operator action',operator_request({'action':'cleanup','token_id':proof['token_id']}).get('error')=='invalid_action')
    for path,expected in [('/dev/operator.php',403),('/__dev/operator.php',404),('/api/whatsapp/claim-token.php',503)]:
        try: response=urllib.request.urlopen('http://127.0.0.1:8088'+path,timeout=10)
        except urllib.error.HTTPError as error: response=error
        check('Operator/production HTTP boundary '+path,response.status==expected)


def storage_tests():
    check('Separate fictional storage initializes without importing app media',fixture('init').get('success') is True)
    cleanup=fixture('token_cleanup')
    check('Expiry cleanup retains held and reconciled evidence in a temporary fixture table',cleanup.get('deleted')==1 and [int(value) for value in cleanup.get('remaining_ids',[])]==[2,3])
    base=fixture('stats')
    first=fixture('http_upload',max_files=base['files']+1)
    check('HTTP catalogue upload registers media under the local quota',first.get('success') is True and first.get('http')==200 and bool(first.get('record')))
    if not first.get('record'): raise RuntimeError('Expected fictional HTTP media absent')
    replacement=fixture('http_upload',max_files=base['files']+1,movie_id=int(first['record']['id']))
    check('HTTP quota denial is 507 and preserves the original database path',replacement.get('http')==507 and replacement.get('record',{}).get('cover_image')==first['record']['cover_image'])
    after=fixture('stats');check('Denied replacement writes neither media nor a manifest entry',after['files']==base['files']+1 and after['registered']==base['registered']+1)
    with concurrent.futures.ThreadPoolExecutor(max_workers=3) as pool:
        initialized=list(pool.map(lambda _:fixture('init'),range(3)))
    check('Concurrent initialization preserves populated storage accounting',all(r.get('success') is True for r in initialized) and fixture('stats')==after)
    base=after
    with concurrent.futures.ThreadPoolExecutor(max_workers=8) as pool:
        results=list(pool.map(lambda _:fixture('store',max_files=base['files']+1),range(8)))
    winners=[r for r in results if r.get('success') is True]
    check('Independent upload processes cannot oversubscribe a one-file remainder',len(winners)==1 and sum(r.get('error')=='quota_exceeded' for r in results)==7)
    if not winners: raise RuntimeError('Expected fictional upload winner absent')
    size=winners[0]['bytes'];base=fixture('stats')
    denied=fixture('store',max_bytes=base['bytes']+size-1)
    check('Byte quota denial leaves physical usage unchanged',denied.get('error')=='quota_exceeded' and fixture('stats')==base)
    base=fixture('stats')
    with concurrent.futures.ThreadPoolExecutor(max_workers=4) as pool:
        results=list(pool.map(lambda _:fixture('store',max_bytes=base['bytes']+size),range(4)))
    check('Concurrent byte accounting allows only the available bytes',sum(r.get('success') is True for r in results)==1 and fixture('stats')['bytes']==base['bytes']+size)
    check('Storage commit rejects path traversal before creating a file',fixture('invalid_path').get('error')=='storage_input_denied')
    orphan=fixture('store')['path']
    check('New unreferenced media is held through its grace period',status(inspect_inventory(),orphan)=='young_hold')
    fixture('age',path=orphan)
    check('Old registered unchanged orphan becomes a review candidate',status(inspect_inventory(),orphan)=='review_candidate')
    limited=fixture('reference_limit')
    check('Bounded/truncated reference scans hold all otherwise eligible files',limited.get('reference_scan_complete') is False and status(limited,orphan)=='inventory_incomplete_hold')
    referenced=fixture('store')['path'];fixture('age',path=referenced);fixture('reference',path=referenced)
    check('A referenced old asset is retained',status(inspect_inventory(),referenced)=='referenced_hold')
    fixture('reference',path=referenced,encoded=True)
    check('Shared and URL-encoded references retain the asset',status(inspect_inventory(),referenced)=='shared_hold')
    delivery=fixture('store')['path'];fixture('age',path=delivery);fixture('reference',path=delivery,source='delivery')
    check('Derived SQL views do not double-count a single stored delivery reference',status(inspect_inventory(),delivery)=='referenced_hold')
    changed=fixture('store')['path'];fixture('age',path=changed);fixture('change',path=changed)
    check('Manifest/content mismatch blocks cleanup candidacy',status(inspect_inventory(),changed)=='changed_hold')
    missing=fixture('store')['path'];fixture('missing',path=missing)
    check('Missing registered media remains visible as held evidence',status(inspect_inventory(),missing)=='missing_or_unsafe')
    fixture('unregistered');inventory=inspect_inventory()
    check('Unknown filenames are held and redacted, not adopted',any(r['status']=='unregistered_hold' for r in inventory['unregistered']) and 'unknown.txt' not in json.dumps(inventory))
    check('Every physical retained/unknown file consumes quota',inventory['quota']['used_files']==fixture('stats')['files'] and inventory['quota']['used_bytes']==fixture('stats')['bytes'])
    before=fixture('stats');inspect_inventory();after=fixture('stats')
    check('Cleanup inventory performs no deletion or accounting mutation',before==after and inventory['read_only'] is True and inventory['deletion_supported'] is False)
    try:
        fixture('links');inventory=inspect_inventory()
        check('Symlinks and hard links are not followed or cleanup candidates',len(inventory.get('unsafe',[]))>=2 and 'FICTIONAL-OUTSIDE-NOT-READ' not in json.dumps(inventory) and status(inventory,orphan)=='inventory_incomplete_hold')
        check('Unsafe links make upload admission fail closed',fixture('store').get('error')=='storage_unsafe_entries')
    finally: fixture('remove_links')
    try:
        fixture('damage_manifest')
        check('Corrupt manifest blocks writes without silently rebuilding provenance',fixture('store').get('error')=='storage_manifest_invalid')
        check('Corrupt manifest also blocks advisory inventory',inspect_inventory().get('error')=='storage_manifest_invalid')
    finally: fixture('restore_manifest')
    before=fixture('stats')
    try:
        fixture('damage_manifest',missing=True)
        check('Missing provenance is not silently recreated by initialization',fixture('init').get('error')=='storage_manifest_invalid')
        check('Missing manifest blocks new uploads',fixture('store').get('error')=='storage_manifest_invalid')
    finally: fixture('restore_manifest')
    check('Missing-manifest fixture restoration preserves all accounting',fixture('stats')==before)
    check('Fixture restoration preserves accounting and enables inventory',inspect_inventory().get('success') is True)


def main():
    started=False
    try:
        require_local_docker()
        require_owned_gateway()
        check('Owned loopback gateway is verified before sending fixture keys',True)
        result=subprocess.run(BASE+['--profile','tools','up','--build','--no-deps','-d','operator-fixtures'],cwd=ROOT,env=ENV,capture_output=True,text=True)
        (STATE/'results/operations-fixture-build.txt').write_text(result.stdout+result.stderr)
        if result.returncode: raise RuntimeError('Fixture service unavailable')
        started=True
        fixture('init')
        operator_tests();storage_tests()
    except Exception as error:
        # No exception text, URLs, tokens or subprocess diagnostics go into public evidence.
        check('Operations harness completed ('+type(error).__name__+')',False)
    finally:
        if started:
            result=subprocess.run(BASE+['rm','--force','--stop','operator-fixtures'],cwd=ROOT,env=ENV,capture_output=True,text=True)
            check('Owned fixture container stopped; named media/state volumes retained',result.returncode==0)
    result={'environment':'owned local Docker database; fictional claims; separate fixture storage; no live transports',
        'tests':ROWS,'summary':{'total':len(ROWS),'passed':sum(row['passed'] for row in ROWS),'failed':sum(not row['passed'] for row in ROWS)}}
    (STATE/'results/operations.json').write_text(json.dumps(result,indent=2)+'\n')
    print(json.dumps(result['summary']))
    sys.exit(1 if result['summary']['failed'] else 0)


if __name__=='__main__': main()

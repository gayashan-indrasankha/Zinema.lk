"""Recoverable cleanup regressions. Separate fictional media; no irreversible purge."""
import concurrent.futures
import json
import secrets
import subprocess
import sys
import time
import urllib.error
import urllib.request
from local import BASE, ENV, ROOT, STATE, operator_request, require_local_docker, require_owned_gateway

RUN=secrets.token_hex(6)
ROWS=[]
SERVICE='quarantine-fixtures'
CASE=0


def check(name,condition):
    ROWS.append({'name':name,'passed':bool(condition),'evidence':'runtime verified'})
    print(('PASS ' if condition else 'FAIL ')+name,flush=True)


def rpc(script,payload):
    result=subprocess.run(BASE+['exec','-T',SERVICE,'php','/opt/zinema/dev/'+script],cwd=ROOT,env=ENV,
        input=json.dumps(dict(payload,run=RUN)),capture_output=True,text=True,timeout=30)
    try:return json.loads(result.stdout)
    except ValueError:raise RuntimeError('Fixture response unavailable') from None


def fixture(action,**values):return rpc('fixture_worker.php',dict(values,action=action))
def qfixture(action,**values):return rpc('quarantine_fixture.php',dict(values,action=action))
def operator(action,path,**values):return operator_request(dict(values,action=action,path=path),SERVICE)
def review(path):return operator('storage-review',path)['review']
def new_asset(old=True):
    value=fixture('store')
    if value.get('success') is not True:raise RuntimeError('Fixture store failed')
    path=value['path']
    if old:fixture('age',path=path)
    return path


def command(action,path,**extra):
    global CASE
    CASE+=1
    return dict(action=action,path=path,revision=review(path)['revision'],actor='demo_quarantine',
        evidence_ref='fixture:q-review-'+RUN,case_ref='fixture:q-'+RUN+'-'+str(CASE),reviewed=True,**extra)


def execute(payload,**fixture_values):
    if not fixture_values:return operator_request(payload,SERVICE)
    return qfixture('execute',request=payload,**fixture_values)
def recover(path,op,decision='finish',**extra):
    payload=command('storage-recover',path,operation_id=op,decision=decision)
    return execute(payload,**extra)


def wait_signal(signal):
    for _ in range(25):
        if qfixture('signal',signal=signal).get('ready') is True:return
        time.sleep(.05)
    raise RuntimeError('Fixture signal absent')


def suite():
    path=new_asset();before=fixture('stats');payload=command('storage-quarantine',path)
    check('Explicit review acknowledgement is mandatory',execute(dict(payload,reviewed=False)).get('error')=='cleanup_review_required')
    check('Stale reviewed revision cannot mutate media',execute(dict(payload,revision=payload['revision']-1)).get('error')=='cleanup_stale_review')
    check('Invalid fictional actor/evidence is rejected',execute(dict(payload,actor='unreviewed')).get('error')=='cleanup_review_required')
    check('Traversal cannot address storage outside the owned root',execute(dict(payload,path='uploads/../secret.png')).get('error')=='cleanup_path_denied')
    unknown=qfixture('unregistered_generated',path=path)['path']
    check('Generated-looking unregistered files are never adopted',operator('storage-review',unknown).get('error')=='cleanup_not_owned')
    result=execute(payload);op=result.get('operation',{}).get('id')
    check('Reviewed orphan is copied, verified and withdrawn into quarantine',result.get('success') is True and review(path)['status']=='restorable' and not qfixture('probe',path=path)['public']['present'])
    probe=qfixture('probe',path=path)
    check('Private archive retains exact registered content',probe['archive']['intact'] is True)
    after=fixture('stats')
    check('Public quota is released while private quarantine usage remains counted',after['files']==before['files'] and review(path)['quarantine_usage']['files']>=1)
    history=operator('storage-history',path);audit_count=len(history['audit']);revision=review(path)['revision']
    reordered=dict(reversed(list(payload.items())))
    check('An identical reordered case is an idempotent replay',execute(reordered).get('replayed') is True and len(operator('storage-history',path)['audit'])==audit_count and review(path)['revision']==revision)
    check('Conflicting reuse of a case is rejected',execute(dict(payload,evidence_ref='fixture:different-review')).get('error')=='cleanup_case_conflict')
    blocked=qfixture('reference',path=path)
    check('Cooperative reference writers reject quarantined assets',blocked.get('error')=='storage_reference_unavailable')
    before=fixture('stats');http=fixture('http_upload',video_url=path)
    check('Browser reference writes are rejected before catalogue/upload mutation',http.get('http')==409 and fixture('stats')==before)
    try:response=urllib.request.urlopen('http://127.0.0.1:8088/uploads/'+op+'.blob',timeout=10)
    except urllib.error.HTTPError as error:response=error
    check('Private quarantine object names are not served by the public app',response.status in {403,404})
    qfixture('collision',path=path)
    check('Restore cannot overwrite a colliding public file',execute(command('storage-restore',path)).get('error')=='cleanup_restore_denied')
    qfixture('remove_collision',path=path)
    qfixture('tamper_archive',path=path)
    try:check('Changed quarantine content is held rather than restored',execute(command('storage-restore',path)).get('error')=='cleanup_restore_denied')
    finally:qfixture('repair_archive',path=path)
    restore=execute(command('storage-restore',path));probe=qfixture('probe',path=path)
    check('Restore republishes verified bytes without losing the rescue archive',restore.get('success') is True and probe['public']['intact'] is True and probe['archive']['intact'] is True)
    check('Restored files receive a fresh cleanup grace period',review(path)['status']=='young_hold')
    check('Audit contains reviewed actors, cases and strictly increasing sequences',all(e['actor'].startswith('demo_') and e['case_ref'].startswith('fixture:') for e in operator('storage-history',path)['audit']) and len({e['sequence'] for e in operator('storage-history',path)['audit']})==len(operator('storage-history',path)['audit']))
    check('Permanent purge is not an exposed action',operator('storage-purge',path).get('error')=='invalid_action')

    young=new_asset(False)
    check('Young assets remain in place',execute(command('storage-quarantine',young)).get('error')=='cleanup_not_eligible')
    referenced=new_asset();payload=command('storage-quarantine',referenced);qfixture('reference',path=referenced)
    check('A reference added after review is rechecked at execution',execute(payload).get('error')=='cleanup_not_eligible')
    qfixture('clear_reference',path=referenced)
    changed=new_asset();payload=command('storage-quarantine',changed);fixture('change',path=changed)
    check('Content drift after review cannot be quarantined',execute(payload).get('error')=='cleanup_not_eligible')
    quota_path=new_asset();payload=command('storage-quarantine',quota_path);quota=review(quota_path)['quarantine_usage']
    check('Quarantine file quota is enforced without removing the source',execute(payload,quarantine_files=max(1,quota['files'])).get('error')=='quarantine_quota_exceeded' and qfixture('probe',path=quota_path)['public']['intact'] is True)
    check('Quarantine byte quota is enforced independently',execute(payload,quarantine_bytes=max(1,quota['bytes'])).get('error')=='quarantine_quota_exceeded')

    race=new_asset();payload=command('storage-quarantine',race)
    with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:results=list(pool.map(execute,[payload,payload]))
    check('Concurrent identical cleanup creates one move and one replay',all(r.get('success') is True for r in results) and sorted(r['replayed'] for r in results)==[False,True] and len({r['operation']['id'] for r in results})==1)
    race=new_asset();a=command('storage-quarantine',race);b=command('storage-quarantine',race)
    with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:results=list(pool.map(execute,[a,b]))
    check('Different reviewed cases cannot both withdraw the same asset',sum(r.get('success') is True for r in results)==1 and any(r.get('error')=='cleanup_stale_review' for r in results))

    race=new_asset();payload=command('storage-quarantine',race);signal=secrets.token_hex(6)
    with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
        writer=pool.submit(qfixture,'hold_reference',path=race,signal=signal);wait_signal(signal)
        result=execute(payload);written=writer.result()
    check('A reference writer already holding the gate wins before cleanup',written.get('success') is True and result.get('error')=='cleanup_not_eligible')
    qfixture('clear_reference',path=race)
    race=new_asset();payload=command('storage-quarantine',race);signal=secrets.token_hex(6)
    with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
        moving=pool.submit(execute,payload,pause_phase='copied',signal=signal);wait_signal(signal)
        writer=pool.submit(qfixture,'reference',path=race)
        result=moving.result();written=writer.result()
    check('Reference creation waits for cleanup then rejects the withdrawn asset',result.get('success') is True and written.get('error')=='storage_reference_unavailable')

    for phase in ['prepared','copied','source_removed']:
        interrupted=new_asset();payload=command('storage-quarantine',interrupted);result=execute(payload,fail_phase=phase)
        op=result['operation']['id'];probe=qfixture('probe',path=interrupted)
        check('Interruption at '+phase+' retains at least one verified copy',result.get('success') is False and result.get('error')=='cleanup_pending_review' and (probe['public']['intact'] or probe['archive']['intact']))
        check('Pending '+phase+' pauses new upload admission',fixture('store').get('error')=='storage_recovery_required')
        if phase=='prepared':qfixture('partial_copy',path=interrupted)
        recovery=command('storage-recover',interrupted,operation_id=op,decision='finish')
        result=execute(recovery)
        check('Reviewed recovery finishes '+phase+' idempotently and safely',result.get('success') is True and review(interrupted)['status']=='restorable')
        if phase=='prepared':
            count=len(operator('storage-history',interrupted)['audit'])
            check('Completed recovery replays without adding audit or moving data',execute(recovery).get('replayed') is True and len(operator('storage-history',interrupted)['audit'])==count)

    interrupted=new_asset();result=execute(command('storage-quarantine',interrupted),fail_phase='copied');op=result['operation']['id']
    qfixture('reference',path=interrupted,bypass=True)
    check('Recovery rechecks references added through a trusted bypass',recover(interrupted,op).get('error')=='cleanup_references_changed')
    result=recover(interrupted,op,'rollback')
    check('Reviewed rollback preserves newly referenced public data',result.get('success') is True and qfixture('probe',path=interrupted)['public']['intact'] is True)
    qfixture('clear_reference',path=interrupted)

    interrupted=new_asset();result=execute(command('storage-quarantine',interrupted),fail_phase='source_removed');op=result['operation']['id']
    result=recover(interrupted,op,'rollback')
    check('Rollback can restore a source already removed after verified copy',result.get('success') is True and qfixture('probe',path=interrupted)['public']['intact'] is True)

    for phase in ['prepared','published','restore_published']:
        interrupted=new_asset();execute(command('storage-quarantine',interrupted))
        result=execute(command('storage-restore',interrupted),fail_phase=phase);op=result['operation']['id']
        check('Interrupted restore at '+phase+' preserves the archive',result.get('error')=='cleanup_pending_review' and qfixture('probe',path=interrupted)['archive']['intact'] is True)
        result=recover(interrupted,op)
        check('Restore recovery finishes '+phase+' without overwriting data',result.get('success') is True and qfixture('probe',path=interrupted)['public']['intact'] is True)

    interrupted=new_asset();execute(command('storage-quarantine',interrupted))
    result=execute(command('storage-restore',interrupted),fail_phase='prepared')
    result=recover(interrupted,result['operation']['id'],'rollback')
    check('Reviewed rollback of an unpublished restore keeps the private copy',result.get('success') is True and review(interrupted)['status']=='restorable' and not qfixture('probe',path=interrupted)['public']['present'])

    restored=new_asset();execute(command('storage-quarantine',restored));usage=fixture('stats')
    result=execute(command('storage-restore',restored),max_files=max(1,usage['files']))
    check('Restore respects active quota and keeps its private rescue copy',result.get('error')=='quota_exceeded' and qfixture('probe',path=restored)['archive']['intact'] is True)
    payload=command('storage-restore',restored)
    with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
        one=pool.submit(execute,payload,max_files=usage['files']+1)
        two=pool.submit(fixture,'store',max_files=usage['files']+1)
        results=[one.result(),two.result()]
    check('Restore and upload cannot oversubscribe the last public slot',sum(r.get('success') is True for r in results)==1 and fixture('stats')['files']==usage['files']+1)
    if review(restored)['location']=='quarantined':execute(command('storage-restore',restored))


def main():
    started=False
    try:
        require_local_docker();require_owned_gateway()
        result=subprocess.run(BASE+['--profile','tools','up','--build','--no-deps','-d',SERVICE],cwd=ROOT,env=ENV,capture_output=True,text=True)
        (STATE/'results/quarantine-fixture-build.txt').write_text(result.stdout+result.stderr)
        if result.returncode:raise RuntimeError('Owned fixture service unavailable')
        started=True
        if fixture('init').get('success') is not True:raise RuntimeError('Fixture initialization failed')
        suite()
    except Exception as error:
        check('Quarantine harness completed ('+type(error).__name__+')',False)
    finally:
        if started:
            result=subprocess.run(BASE+['rm','--force','--stop',SERVICE],cwd=ROOT,env=ENV,capture_output=True,text=True)
            check('Owned quarantine fixture stopped; media and audit volumes retained',result.returncode==0)
    result={'scope':'Owned development DB and separate fictional quarantine media/state volumes; no permanent purge or live integration',
        'tests':ROWS,'summary':{'total':len(ROWS),'passed':sum(r['passed'] for r in ROWS),'failed':sum(not r['passed'] for r in ROWS)}}
    (STATE/'results/quarantine.json').write_text(json.dumps(result,indent=2)+'\n')
    print(json.dumps(result['summary']));sys.exit(1 if result['summary']['failed'] else 0)


if __name__=='__main__':main()

"""Export a disabled shared-host review bundle under ignored runtime only. Never deploy."""
import argparse,hashlib,json,secrets,shutil,subprocess,sys
from pathlib import Path
from build_stage_artifact import build
from local import ROOT
TEMPLATES=ROOT/'ops/staging/stellar'
def checked_new_target(destination):
    boundary=ROOT/'.runtime';boundary.mkdir(exist_ok=True)
    raw=Path(destination)
    if not raw.is_absolute():raise RuntimeError('private_absolute_target_required')
    target=raw.resolve()
    if boundary.resolve() not in target.parents or raw.exists() or raw.is_symlink():raise RuntimeError('new_private_target_required')
    current=raw
    while current!=ROOT:
        if current.is_symlink():raise RuntimeError('plain_private_target_required')
        if current==current.parent:raise RuntimeError('private_boundary_required')
        current=current.parent
    if shutil.disk_usage(ROOT).free<64*1024*1024:raise RuntimeError('insufficient_local_scratch_space')
    return target
def prepare(destination,working_copy=False):
    target=checked_new_target(destination);target.parent.mkdir(parents=True,exist_ok=True)
    proof=ROOT/'.runtime/stellar-source-proofs'/secrets.token_hex(8)
    source=build(proof)
    target.mkdir();public=target/'public_html';private=target/'zinema-private/staging';tools=target/'bot-tools'
    private.mkdir(parents=True,mode=0o700);tools.mkdir()
    # Copy only the committed website; backend/runtime/dev/source-proof stay outside.
    shutil.copytree(proof/'public',public)
    overlays=[]
    if working_copy:
        # Explicit local rehearsal of current PHP/JS adapter changes, not a release export.
        # Only public source paths already allowed by the committed exporter qualify.
        changed=subprocess.check_output(['git','ls-files','--modified','--others','--exclude-standard','-z'],cwd=ROOT).decode().split('\0')
        for name in sorted(set(changed)):
            item=Path(name)
            if not name or item.parts[0] not in {'includes','admin','api','js'} or item.suffix not in {'.php','.js'}:continue
            source_path=ROOT/item
            if source_path.is_symlink() or not source_path.is_file():raise RuntimeError('source_overlay_denied')
            output=public/item;output.parent.mkdir(parents=True,exist_ok=True);shutil.copyfile(source_path,output)
            overlays.append({'path':name,'sha256':hashlib.sha256(source_path.read_bytes()).hexdigest()})
    existing=(public/'.htaccess').read_bytes()
    (public/'.htaccess').write_bytes((TEMPLATES/'guard.htaccess').read_bytes()+b'\n'+existing)
    (public/'.user.ini').write_bytes((TEMPLATES/'user.ini.template').read_bytes())
    for name in ['candidate.php','bootstrap.php','runtime.php','runtime-config.example.php','operator.php','extractor-review.php','check.php','host-capabilities.php','cron.disabled.txt']:
        shutil.copyfile(TEMPLATES/name,private/name)
    shutil.copyfile(proof/'database/schema.sql',private/'schema.sql')
    shutil.copyfile(ROOT/'ops/bot-readiness.cjs',tools/'bot-readiness.cjs')
    (target/'README.txt').write_text('DISABLED LOCAL REVIEW BUNDLE. DO NOT UPLOAD OR DEPLOY.\npublic_html has an unconditional deny rule. PHP bootstrap returns 503.\nPrivate paths, accounts, credentials, private-stage authentication, quotas, backups,\nmonitoring and actual hosting compatibility need separate reviewed authorization.\nThe bot-tools readiness script is standalone/read-only and belongs on the actual\nbot machine, not the website. Source-proof files are local evidence, not deployment.\n',encoding='utf-8')
    rows=[]
    for file in sorted(target.rglob('*')):
        if file.is_symlink():raise RuntimeError('bundle_link_denied')
        if file.is_file():rows.append({'path':file.relative_to(target).as_posix(),'sha256':hashlib.sha256(file.read_bytes()).hexdigest()})
    forbidden=[r['path'] for r in rows if any(p in {'node_modules','.runtime','.git','uploads','dev','pages','sessions','backups'} for p in Path(r['path']).parts) or Path(r['path']).suffix in {'.pem','.key','.mp4','.mkv','.log'} or Path(r['path']).name in {'.env','Database.sql','creds.json','store.json'}]
    if forbidden:raise RuntimeError('bundle_boundary_failed')
    manifest={'purpose':'disabled-stellar-private-staging-review-only','source_commit':source['source_commit'],'source_kind':'commit plus explicit hashed local source overlays' if working_copy else 'committed website plus hashed current candidate templates','source_overlays':overlays,'deployment_enabled':False,'external_connections':False,'production_access':False,'publishing':False,'files':rows,'forbidden_paths':0,'limitations':['No activation, account access, dependency install, schema execution, scheduler install or deployment.','PHP handler/quotas/account paths and credentials remain unverified.','No bot-machine capacity claim follows from generating or testing this bundle.']}
    (target/'manifest.json').write_text(json.dumps(manifest,indent=2)+'\n')
    return manifest
if __name__=='__main__':
    parser=argparse.ArgumentParser(description='Prepare disabled local review files, never deploy.')
    parser.add_argument('--destination',type=Path,default=ROOT/'.runtime/stellar-candidates'/secrets.token_hex(8))
    parser.add_argument('--local-working-copy',action='store_true',help='Hash explicit current public-source overlays for local rehearsal only.')
    args=parser.parse_args()
    try:
        result=prepare(args.destination,args.local_working_copy)
        print(json.dumps({'bundle':str(args.destination),'source_commit':result['source_commit'],'files':len(result['files']),'deployment_enabled':False}))
    except Exception:
        print('Disabled candidate preparation stopped; preserve local evidence and review. No private diagnostics printed.',file=sys.stderr);sys.exit(1)

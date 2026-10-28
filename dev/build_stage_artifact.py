"""Export a committed allowlist, never the workspace or private data. Local only."""
import hashlib,io,json,secrets,subprocess,tarfile
from pathlib import Path
from local import ROOT,PROJECT

def build(destination,reviewed_tree=None):
    destination=Path(destination).resolve()
    boundary=(ROOT/'.runtime').resolve()
    if boundary not in destination.parents or destination.exists():raise RuntimeError('Artifact target must be new and private')
    destination.mkdir(parents=True)
    revision=subprocess.run(['git','rev-parse','HEAD'],cwd=ROOT,capture_output=True,text=True,check=True).stdout.strip()
    if reviewed_tree is not None and (not isinstance(reviewed_tree,str) or not __import__('re').fullmatch('[a-f0-9]{40}',reviewed_tree) or subprocess.run(['git','cat-file','-t',reviewed_tree],cwd=ROOT,capture_output=True,text=True,check=True).stdout.strip()!='tree'):raise RuntimeError('Reviewed tree required')
    tree=reviewed_tree or subprocess.run(['git','rev-parse',revision+'^{tree}'],cwd=ROOT,capture_output=True,text=True,check=True).stdout.strip()
    raw_archive=subprocess.run(['git','archive','--format=tar',tree],cwd=ROOT,capture_output=True,check=True).stdout
    archive=tarfile.open(fileobj=io.BytesIO(raw_archive));files=[entry.name for entry in archive if entry.isfile()]
    blocked={'download.php','find_movies.php','admin/run_migration.php','api/server-check.php','api/subscribe.php','api/payment-notify.php'}
    copied=[]
    for name in files:
        path=Path(name);public=False;target=None
        if name in blocked or name.startswith('admin/cron/'):continue
        if len(path.parts)==1 and (path.suffix=='.php' or name in {'.htaccess','favicon.ico'}):public=True
        if path.parts[0] in {'admin','api','includes','assets','css','js'}:public=True
        if public:target=destination/'public'/path
        elif name=='database/schema.sql':target=destination/'database/schema.sql'
        elif path.parts[0] in {'whatsapp-bot-deploy','fb-video-api'} and path.suffix in {'.js','.cjs','.json'} and name!='whatsapp-bot-deploy/config/database.js':target=destination/'backends'/path
        elif name in {'THIRD_PARTY.md','LICENSE'}:target=destination/path
        if target:
            raw=archive.extractfile(name).read()
            target.parent.mkdir(parents=True,exist_ok=True);target.write_bytes(raw);copied.append({'path':target.relative_to(destination).as_posix(),'sha256':hashlib.sha256(raw).hexdigest()})
    archive.close()
    for source,target in [('app.Dockerfile','Dockerfile'),('apache.conf','apache.conf'),('php.ini','php.ini'),('entrypoint.sh','entrypoint.sh')]:
        raw=(ROOT/'dev/staging'/source).read_bytes();(destination/target).write_bytes(raw);copied.append({'path':target,'sha256':hashlib.sha256(raw).hexdigest()})
    (destination/'.dockerignore').write_text('**\n!Dockerfile\n!apache.conf\n!php.ini\n!entrypoint.sh\n!public/\n!public/**\n')
    forbidden=[row['path'] for row in copied if any(part in {'.git','.runtime','.recovery-private','node_modules','dev','uploads','sessions','pages'} for part in Path(row['path']).parts) or Path(row['path']).name in {'.env','Database.sql','store.json','worker.lock','message-cache.json'}]
    if forbidden:raise RuntimeError('Artifact boundary violation')
    manifest={'purpose':'isolated-local-staging-rehearsal-not-a-release','source_commit':revision,'source_tree':tree,'source_kind':'reviewed-index-tree' if reviewed_tree else 'commit','owner':PROJECT,'files':copied,'forbidden_paths':len(forbidden),'limitations':['All providers disabled; no deployment authorization.','Temporary TLS keys/DB credentials/fixture data live separately and are not in this artifact.','The rehearsal entrypoint deliberately pins loopback and the fictional database; real hosting needs a reviewed configuration.']}
    (destination/'manifest.json').write_text(json.dumps(manifest,indent=2)+'\n');return manifest

if __name__=='__main__':
    target=ROOT/'.runtime/staging-artifacts'/secrets.token_hex(8)
    manifest=build(target);print(json.dumps({'artifact':str(target),'source_commit':manifest['source_commit'],'files':len(manifest['files']),'forbidden_paths':0}))

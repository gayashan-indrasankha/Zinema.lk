"""Artifact/archive security boundaries. No daemon, provider or application DB."""
import io,json,shutil,subprocess,sys,tarfile,tempfile,unittest
from pathlib import Path
from build_stage_artifact import build
from rehearse_staging import Rehearsal,ROOT
class StagingBoundaries(unittest.TestCase):
    def setUp(self):
        base=ROOT/'.runtime';base.mkdir(exist_ok=True);self.home=Path(tempfile.mkdtemp(prefix='staging-boundaries-',dir=base)).resolve()
    def tearDown(self):
        if self.home.parent!=(ROOT/'.runtime').resolve() or not self.home.name.startswith('staging-boundaries-'):raise RuntimeError('cleanup_boundary')
        shutil.rmtree(self.home)
    def test_committed_artifact_is_reproducible_and_private(self):
        a=build(self.home/'one');b=build(self.home/'two');self.assertEqual(a,b);self.assertGreater(len(a['files']),100)
        public=self.home/'one/public'
        for name in ['.env','.git','dev','pages','Database.sql','download.php','admin/run_migration.php','api/server-check.php','fb-video-api','uploads']:self.assertFalse((public/name).exists(),name)
        self.assertTrue((public/'admin/extractor.php').is_file());self.assertTrue((self.home/'one/database/schema.sql').is_file())
    def test_output_cannot_overwrite_or_escape_private_boundary(self):
        with self.assertRaises(RuntimeError):build(self.home)
        with self.assertRaises(RuntimeError):build(ROOT/'must-not-create-artifact')
    def test_streamed_command_io_is_private_and_never_overwrites(self):
        subject=object.__new__(Rehearsal);subject.home=self.home;subject.diag=io.BytesIO()
        source=self.home/'stream-in';target=self.home/'stream-out';source.write_bytes(b'fictional-stream')
        command=[sys.executable,'-c','import sys;sys.stdout.buffer.write(sys.stdin.buffer.read())']
        subject.command(command,data_file=source,output_file=target);self.assertEqual(target.read_bytes(),source.read_bytes())
        with self.assertRaises(FileExistsError):subject.command(command,data_file=source,output_file=target)
        with self.assertRaises(RuntimeError):subject.command(command,data_file=source,output_file=ROOT/'must-not-write-stream')
        with self.assertRaises(RuntimeError):subject.command(command,data=b'fictional',data_file=source)
    def test_shell_template_survives_windows_checkout(self):
        result=subprocess.run(['git','check-attr','eol','--','dev/staging/entrypoint.sh'],cwd=ROOT,capture_output=True,text=True,check=True)
        self.assertTrue(result.stdout.strip().endswith(': lf'));self.assertNotIn(b'\r\n',(ROOT/'dev/staging/entrypoint.sh').read_bytes())
    def test_archive_accepts_only_regular_owned_roots(self):
        data=io.BytesIO()
        with tarfile.open(fileobj=data,mode='w') as output:
            info=tarfile.TarInfo('uploads/fictional.png');info.size=3;output.addfile(info,io.BytesIO(b'abc'))
        Rehearsal.valid_tar(data.getvalue())
    def test_archive_rejects_traversal_links_and_unknown_roots(self):
        for name,kind in [('uploads/../../escape',tarfile.REGTYPE),('/uploads/escape',tarfile.REGTYPE),('secrets/file',tarfile.REGTYPE),('uploads/link',tarfile.SYMTYPE),('uploads/link',tarfile.LNKTYPE)]:
            data=io.BytesIO()
            with tarfile.open(fileobj=data,mode='w') as output:
                info=tarfile.TarInfo(name);info.type=kind;info.linkname='../sessions/example';output.addfile(info)
            with self.assertRaises(RuntimeError):Rehearsal.valid_tar(data.getvalue())
if __name__=='__main__':unittest.main()

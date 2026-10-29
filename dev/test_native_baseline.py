"""Retained disabled-profile HTTP tests beside the full local demo, on separate ports."""
import contextlib,io,json,socket,sys
from pathlib import Path
ROOT=Path(__file__).resolve().parent.parent
sys.path.insert(0,str(ROOT/'docs/recovery/tests'))
import audit

def main():
    ports=[]
    for _ in range(2):
        with socket.socket() as available:available.bind(('127.0.0.1',0));ports.append(available.getsockname()[1])
    audit.PORT,audit.DB_PORT=ports;audit.BASE=f'http://127.0.0.1:{audit.PORT}';audit.RESULT_PREFIX='WHOLE_LOCAL_BASELINE'
    folder=None;status=1;capture=io.StringIO()
    def owned(created):
        nonlocal folder
        folder=created
    try:
        with contextlib.redirect_stdout(capture):
            audit.prepare(owned)
            try:audit.check(folder)
            except SystemExit as result:status=int(result.code or 0)
    finally:
        if folder:
            (folder/'baseline-console.log').write_text(capture.getvalue())
            with contextlib.redirect_stdout(io.StringIO()):audit.stop(folder)
    report=json.loads((ROOT/'docs/recovery/WHOLE_LOCAL_BASELINE_RETAINED_HTTP.json').read_text())
    print(json.dumps({'summary':report['summary'],'php_diagnostics':report['new_php_diagnostics'],'owned_ports_closed':not any(audit.port_open(p) for p in ports)}))
    return status
if __name__=='__main__':sys.exit(main())

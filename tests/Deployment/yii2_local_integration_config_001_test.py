#!/usr/bin/env python3
"""YII2-LOCAL-DATA-BOOTSTRAP-001: private integration config grammar."""
import json, os, subprocess, tempfile
from pathlib import Path
root=Path(__file__).resolve().parents[2]; tool=root/'tools/delivery/local-integration-config'
assert tool.is_file(),'INTENDED_RED: native private config validator missing'
with tempfile.TemporaryDirectory() as raw:
    d=Path(raw); effect=d/'effect'; record=d/'record.json'; legacy=d/'legacy.env'; bitrix=d/'bitrix.json'
    secret='SECRET_CANARY_MUST_NOT_LEAK'
    valid_legacy=f"FMONITOR_SOURCE_HOST=source.example\nFMONITOR_SOURCE_PORT=3306\nFMONITOR_SOURCE_NAME=legacy\nFMONITOR_SOURCE_USER=reader\nFMONITOR_SOURCE_PASSWORD='{secret}'\nFMONITOR_MIGRATION_CUTOFF=2026-09-14 23:59:59\n"
    valid_bitrix=json.dumps({'baseUrl':f'https://example.invalid/rest/7/{secret}','departments':[71,72]})
    spy=d/'spy.py';spy.write_text("#!/usr/bin/env python3\nimport json,os,sys\nopen(sys.argv[2],'w').write(json.dumps({'argv':sys.argv[1:],'environment':dict(os.environ)}))\nopen(sys.argv[1],'w').write('effect')\nprint('compose-config-redacted')\n");spy.chmod(0o755)
    def call(kind,path):
        record.unlink(missing_ok=True)
        return subprocess.run([str(tool),kind,str(path),'--',str(spy),str(effect),str(record)],text=True,capture_output=True,env={**os.environ,'FMONITOR_SOURCE_PASSWORD':'AMBIENT_CANARY','FMONITOR_BITRIX_WEBHOOK_URL':'AMBIENT_BITRIX_CANARY'})
    def rejected(kind,path):
        effect.unlink(missing_ok=True);r=call(kind,path);assert r.returncode==64,(kind,path,r);assert not effect.exists();assert 'LOCAL_INTEGRATION_CONFIG_INVALID' in r.stdout+r.stderr;assert secret not in r.stdout+r.stderr and 'AMBIENT_CANARY' not in r.stdout+r.stderr
    rejected('legacy',d/'missing');rejected('bitrix',d/'missing')
    for kind,path,valid,bad in (
        ('legacy',legacy,valid_legacy,[valid_legacy.replace('3306','0',1),valid_legacy.replace('3306','01',1),valid_legacy.replace('3306','abc',1),valid_legacy.replace('3306','65536',1),valid_legacy.replace('2026-09-14 23:59:59','bad'),valid_legacy+'EXTRA=x\n',valid_legacy+'FMONITOR_SOURCE_USER=again\n',valid_legacy.replace('FMONITOR_SOURCE_HOST=source.example','FMONITOR_SOURCE_HOST='),valid_legacy.replace('FMONITOR_SOURCE_NAME=legacy','FMONITOR_SOURCE_NAME='),valid_legacy.replace('FMONITOR_SOURCE_USER=reader','FMONITOR_SOURCE_USER='),valid_legacy.replace("FMONITOR_SOURCE_PASSWORD='SECRET_CANARY_MUST_NOT_LEAK'","FMONITOR_SOURCE_PASSWORD=''")]),
        ('bitrix',bitrix,valid_bitrix,['{}','not-json',json.dumps({'baseUrl':'http://example.invalid/rest/7/x','departments':[71]}),json.dumps({'baseUrl':'https://example.invalid/rest/7/x','departments':[]}),json.dumps({'baseUrl':'https://example.invalid/rest/7/x','departments':[71,71]})])):
        path.write_text(valid);path.chmod(0o600);effect.unlink(missing_ok=True);ok=call(kind,path);assert ok.returncode==0 and effect.exists();assert secret not in ok.stdout+ok.stderr
        captured=record.read_text();assert secret not in captured and 'AMBIENT_CANARY' not in captured and 'AMBIENT_BITRIX_CANARY' not in captured;assert 'compose-config-redacted' in ok.stdout
        if kind=='legacy':path.write_text(valid.replace('2026-09-14 23:59:59',''));effect.unlink(missing_ok=True);assert call(kind,path).returncode==0 and effect.exists()
        link=d/(kind+'.link');link.symlink_to(path);rejected(kind,link);link.unlink();path.chmod(0o644);rejected(kind,path);path.chmod(0o600)
        for value in bad:path.write_text(value);rejected(kind,path)
print('PASS: YII2-LOCAL-DATA-BOOTSTRAP-001 private config')

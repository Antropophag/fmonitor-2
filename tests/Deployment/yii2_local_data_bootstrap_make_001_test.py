#!/usr/bin/env python3
"""YII2-LOCAL-DATA-BOOTSTRAP-001: execute public Make orchestration."""
import json, os, shutil, subprocess, tempfile
from pathlib import Path

root = Path(__file__).resolve().parents[2]
with tempfile.TemporaryDirectory() as raw:
    box = Path(raw); checkout = box / "checkout"; checkout.mkdir()
    for rel in ("Makefile", ".env.example", "tools/delivery/local-runtime-env"):
        src = root / rel
        assert src.exists(), f"MISSING:{rel}"
        dst = checkout / rel; dst.parent.mkdir(parents=True, exist_ok=True); shutil.copy2(src, dst)
    validator = checkout / "tools/delivery/local-integration-config"
    validator.write_text("#!/bin/sh\nexit 0\n"); validator.chmod(0o755)
    for rel in ("deploy/runtime/compose.yaml", "deploy/runtime/Dockerfile"):
        dst = checkout / rel; dst.parent.mkdir(parents=True, exist_ok=True); shutil.copy2(root / rel, dst)
    values = {
        "COMPOSE_PROJECT_NAME":"fm2-local-bootstrap-test", "FMONITOR_RUNTIME_IMAGE":"fmonitor2-runtime:test",
        "FMONITOR_HTTP_PORT":"18093", "FMONITOR_DB_NAME":"fmonitor2", "FMONITOR_DB_USER":"runtime",
        "FMONITOR_DB_PASSWORD":"DB_SECRET_CANARY", "FMONITOR_MIGRATION_DB_USER":"root",
        "FMONITOR_MIGRATION_DB_PASSWORD":"ROOT_SECRET_CANARY", "FMONITOR_PROCESS_TABLE_PREFIX":"fm2_",
        "FMONITOR_LEGACY_TABLE_PREFIX":"fm2_", "FMONITOR_SESSION_INSTANCE":"test",
        "FMONITOR_YII_COOKIE_VALIDATION_KEY":"c"*32, "FMONITOR_YII_IDENTITY_KEY":"i"*32,
        "FMONITOR_TRUSTED_REQUEST_HOST":"127.0.0.1:18093", "FMONITOR_TRUSTED_REQUEST_SCHEME":"http",
        "FMONITOR_INITIAL_OWNER_EMAIL":"owner@example.test", "FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD":"OWNER_SECRET_CANARY",
    }
    (checkout / ".env").write_text("".join(f"{k}={v}\n" for k,v in values.items())); (checkout / ".env").chmod(0o600)
    private = checkout / ".local"; private.mkdir(mode=0o700)
    (private / "legacy-source.env").write_text("FMONITOR_SOURCE_HOST=source.example\nFMONITOR_SOURCE_PORT=3306\nFMONITOR_SOURCE_NAME=legacy\nFMONITOR_SOURCE_USER=reader\nFMONITOR_SOURCE_PASSWORD='LEGACY_SECRET_CANARY'\nFMONITOR_MIGRATION_CUTOFF=\n")
    (private / "bitrix-workforce.json").write_text('{"baseUrl":"https://example.invalid/rest/7/BITRIX_SECRET_CANARY","departments":[71]}')
    for path in private.iterdir(): path.chmod(0o600)
    fake = box / "bin"; fake.mkdir(); trace = box / "trace.jsonl"
    docker = fake / "docker"
    docker.write_text('''#!/usr/bin/env python3
import json,os,sys
a=sys.argv[1:]; text=' '.join(a); stage='ordinary'
if 'legacy-import/run' in text: stage='legacy'
elif 'workforce-sync/run' in text: stage='workforce'
elif text=='info': stage='up'
open(os.environ['TRACE'],'a').write(json.dumps({'stage':stage,'argv':a})+'\\n')
if os.environ.get('FAIL_STAGE')==stage: sys.exit(42)
sys.exit(0)
'''); docker.chmod(0o755)
    curl = fake / "curl"; curl.write_text("#!/bin/sh\nexit 0\n"); curl.chmod(0o755)
    env = {**os.environ, "PATH":f"{fake}:{os.environ['PATH']}", "TRACE":str(trace)}
    def run(target, fail=""):
        trace.write_text(""); return subprocess.run(["make","--no-print-directory",target],cwd=checkout,env={**env,"FAIL_STAGE":fail},text=True,capture_output=True)
    def stages(): return [json.loads(x)["stage"] for x in trace.read_text().splitlines()]
    plain = run("up"); assert plain.returncode == 0 and "legacy" not in stages() and "workforce" not in stages()
    legacy = run("import-legacy"); assert legacy.returncode == 0 and stages().count("legacy") == 1 and "workforce" not in stages()
    workforce = run("sync-workforce"); assert workforce.returncode == 0 and stages().count("workforce") == 1 and "legacy" not in stages()
    combined = run("up-with-data"); assert combined.returncode == 0
    order = stages(); assert order.count("up") == 1 and order.count("legacy") == 1 and order.count("workforce") == 1
    assert order.index("up") < order.index("legacy") < order.index("workforce"), order
    expected = {"up": ["up"], "legacy": ["up","legacy"], "workforce": ["up","legacy","workforce"]}
    for failed, forbidden in (("up",("legacy","workforce")), ("legacy",("workforce",)), ("workforce",())):
        result = run("up-with-data", failed); assert result.returncode != 0
        observed = stages()
        assert [x for x in observed if x in ("up","legacy","workforce")] == expected[failed], (failed, observed)
        for stage in forbidden: assert stage not in observed, (failed, observed)
        assert "FMonitor Yii2 with production data:" not in result.stdout + result.stderr
        retry = run("up-with-data"); assert retry.returncode == 0
        retried = stages(); assert retried.count("legacy") == 1 and retried.count("workforce") == 1
    output = plain.stdout + plain.stderr + legacy.stdout + legacy.stderr + workforce.stdout + workforce.stderr
    for secret in ("DB_SECRET_CANARY","ROOT_SECRET_CANARY","OWNER_SECRET_CANARY","LEGACY_SECRET_CANARY","BITRIX_SECRET_CANARY"):
        assert secret not in output
print("PASS: YII2-LOCAL-DATA-BOOTSTRAP-001 public Make orchestration")

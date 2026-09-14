#!/usr/bin/env python3
import copy,hashlib,json,os,pathlib,subprocess,tempfile,yaml
ROOT=pathlib.Path(__file__).resolve().parents[2]; support=ROOT/"tests/Support/clean-stand"; override=support/"compose.acceptance.yaml"
adapters=[support/"setup.php",support/"enqueue.php",support/"include-probe.php"]
ini=support/"99-acceptance-probe.ini"
assert override.is_file() and ini.is_file() and all(p.is_file() for p in adapters),"INTENDED_RED: isolated acceptance ini topology is missing"

base=yaml.safe_load((ROOT/"deploy/runtime/compose.yaml").read_text()); extra=yaml.safe_load(override.read_text())
assert set(extra)=={"services"} and set(extra["services"])=={"php","jobs-worker","jobs-scheduler","acceptance-setup","acceptance-enqueue"}
probe_mount={"type":"bind","source":"${FMONITOR_ACCEPTANCE_PROBE_HOST_FILE:?Set acceptance probe}","target":"/run/fmonitor-acceptance/include-probe.php","read_only":True}
context_mount={"type":"bind","source":"${FMONITOR_ACCEPTANCE_CONTEXT_HOST_FILE:?Set acceptance context}","target":"/run/fmonitor-acceptance/context.json","read_only":True}
ini_mount={"type":"bind","source":"${FMONITOR_ACCEPTANCE_PROBE_INI_HOST_FILE:?Set acceptance probe ini}","target":"/run/fmonitor-acceptance-ini/99-acceptance-probe.ini","read_only":True}
for name in ("php","jobs-worker","jobs-scheduler"):
    delta=extra["services"][name]
    assert set(delta)=={"environment","volumes"} and delta["volumes"]==[probe_mount,context_mount,ini_mount]
    assert delta["environment"]=={"FMONITOR_ACCEPTANCE_CONTEXT_FILE":"/run/fmonitor-acceptance/context.json","FMONITOR_ACCEPTANCE_TRACE_ROOT":"/home/fmonitor/.local/state/fmonitor2/acceptance-traces","PHP_INI_SCAN_DIR":"/usr/local/etc/php/conf.d:/run/fmonitor-acceptance-ini"}
    # Every non-instrumentation property remains owned solely by production Compose.
    for forbidden in ("image","build","command","entrypoint","ports","depends_on","healthcheck","security_opt","read_only","networks","secrets","user"):
        assert forbidden not in delta,(name,forbidden)
common_env={"FMONITOR_ACCEPTANCE_CONTEXT_FILE":"/run/fmonitor-acceptance/context.json","FMONITOR_ACCEPTANCE_CONTEXT_DIGEST":"${FMONITOR_ACCEPTANCE_CONTEXT_DIGEST:?Set context digest}","FMONITOR_ACCEPTANCE_OPERATION_ID":"${FMONITOR_ACCEPTANCE_OPERATION_ID:?Set operation UUID}","FMONITOR_ACCEPTANCE_TARGET_DIGEST":"${FMONITOR_ACCEPTANCE_TARGET_DIGEST:?Set target digest}","FMONITOR_DB_HOST":"127.0.0.1","FMONITOR_DB_PORT":"3306","FMONITOR_DB_NAME":"${FMONITOR_DB_NAME:?Set database name}","FMONITOR_DB_USER":"${FMONITOR_ACCEPTANCE_DB_USER:?Set private setup principal}","FMONITOR_DB_PASSWORD_FILE":"/run/fmonitor-acceptance/database-password","FMONITOR_PROCESS_TABLE_PREFIX":"${FMONITOR_PROCESS_TABLE_PREFIX:?Set process prefix}"}
credential_mount={"type":"bind","source":"${FMONITOR_ACCEPTANCE_DB_PASSWORD_HOST_FILE:?Set private credential file}","target":"/run/fmonitor-acceptance/database-password","read_only":True}
for name,script,dependencies in (("acceptance-setup","setup.php",{"db":{"condition":"service_healthy"},"migrate":{"condition":"service_completed_successfully"}}),("acceptance-enqueue","enqueue.php",{"db":{"condition":"service_healthy"},"migrate":{"condition":"service_completed_successfully"},"jobs-worker":{"condition":"service_healthy"},"jobs-scheduler":{"condition":"service_healthy"}})):
    script_mount={"type":"bind","source":"${FMONITOR_ACCEPTANCE_SUPPORT_ROOT:?Set acceptance support root}/"+script,"target":"/run/fmonitor-acceptance/"+script,"read_only":True}
    expected={"image":"${FMONITOR_RUNTIME_IMAGE:?Set explicit runtime image reference}","command":["php","/run/fmonitor-acceptance/"+script],"profiles":["acceptance"],"environment":common_env,"volumes":[script_mount,context_mount,credential_mount],"depends_on":dependencies,"network_mode":"service:db","restart":"no","read_only":True,"security_opt":["no-new-privileges:true"]}
    assert extra["services"][name]==expected,(name,extra["services"][name])

production=[ROOT/"deploy/runtime/compose.yaml",ROOT/"deploy/runtime/Dockerfile",ROOT/"tools/delivery/Dockerfile.runtime.in",ROOT/"config/yii/web.php",ROOT/"config/yii/console.php",ROOT/"bin/fmonitor2-yii.php"]
for path in production:
    source=path.read_text()
    for marker in ("acceptance-setup","acceptance-enqueue","include-probe","FMONITOR_ACCEPTANCE_","FMONITOR_CLEAN_STAND"):
        assert marker not in source,(path,marker)
assert "COPY tests" not in production[1].read_text() and "tests/Support" not in production[1].read_text()
assert ini.read_text()=="auto_prepend_file=/run/fmonitor-acceptance/include-probe.php\n"

with tempfile.TemporaryDirectory(prefix="fm2-acceptance-isolation-") as tmp:
    context=pathlib.Path(tmp)/"context.json"; operation="11111111-1111-4111-8111-111111111111"; target="a"*64
    context.write_text(json.dumps({"operationId":operation,"targetDigest":target},sort_keys=True,separators=(",",":"))+"\n")
    digest=hashlib.sha256(context.read_bytes()).hexdigest(); clean={k:v for k,v in os.environ.items() if not k.startswith("FMONITOR_ACCEPTANCE_")}
    cases=[({},"ACCEPTANCE_CONTEXT_REQUIRED"),({"FMONITOR_ACCEPTANCE_CONTEXT_FILE":str(context),"FMONITOR_ACCEPTANCE_CONTEXT_DIGEST":digest,"FMONITOR_ACCEPTANCE_OPERATION_ID":"22222222-2222-4222-8222-222222222222","FMONITOR_ACCEPTANCE_TARGET_DIGEST":target},"ACCEPTANCE_CONTEXT_INVALID"),({"FMONITOR_ACCEPTANCE_CONTEXT_FILE":str(context),"FMONITOR_ACCEPTANCE_CONTEXT_DIGEST":digest,"FMONITOR_ACCEPTANCE_OPERATION_ID":operation,"FMONITOR_ACCEPTANCE_TARGET_DIGEST":"b"*64},"ACCEPTANCE_CONTEXT_INVALID"),({"FMONITOR_ACCEPTANCE_CONTEXT_FILE":str(context),"FMONITOR_ACCEPTANCE_CONTEXT_DIGEST":"c"*64,"FMONITOR_ACCEPTANCE_OPERATION_ID":operation,"FMONITOR_ACCEPTANCE_TARGET_DIGEST":target},"ACCEPTANCE_CONTEXT_INVALID")]
    for adapter in adapters:
        for additions,reason in cases:
            env=clean|additions; p=subprocess.run(["php",str(adapter)],cwd=ROOT,env=env,text=True,stdout=subprocess.PIPE,stderr=subprocess.PIPE)
            assert p.returncode!=0 and json.loads(p.stdout)["reason"]==reason and p.stderr=="",(adapter,reason,p.stdout,p.stderr)
print("PASS: YII2-CLEAN-STAND-CUTOVER-001 acceptance topology isolation")

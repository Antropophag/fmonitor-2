#!/usr/bin/env python3
from pathlib import Path
import json,os,secrets,shutil,subprocess,tempfile
ROOT=Path(__file__).resolve().parents[2]
required=["app/YiiRuntime/Commands/CaseImportController.php","app/YiiRuntime/CaseImportConsole.php","app/InstallationProcess/PilotCaseImporter.php","bin/yii","bin/fmonitor2-yii.php","config/yii/console.php"]
for relative in required: assert (ROOT/relative).is_file(),f"INTENDED_RED: package source contains {relative}"
assert "case-import" in (ROOT/"config/yii/console.php").read_text(encoding="utf-8"),"Yii route registered"
for relative in ["config/yii/web.php","public/runtime.php","app/YiiRuntime/Commands/JobsController.php"]: assert "case-import/run" not in (ROOT/relative).read_text(encoding="utf-8"),f"ordinary runtime does not invoke import: {relative}"
with tempfile.TemporaryDirectory(prefix="fm2-yci-load-")as directory:
 trace=Path(directory)/"trace.json";wrapper=Path(directory)/"trace.php";wrapper.write_text("<?php register_shutdown_function(static function(){file_put_contents("+repr(str(trace))+",json_encode(get_included_files(),JSON_THROW_ON_ERROR));});require "+repr(str(ROOT/'bin/yii'))+";",encoding="utf-8")
 env=dict(os.environ);[env.pop(name,None)for name in ["FMONITOR_DB_HOST","FMONITOR_DB_PORT","FMONITOR_DB_NAME","FMONITOR_DB_USER","FMONITOR_DB_PASSWORD","FMONITOR_PROCESS_TABLE_PREFIX","FMONITOR_LEGACY_TABLE_PREFIX"]]
 result=subprocess.run(["php",str(wrapper),"case-import/run","--object-id=7","--interactive=0"],cwd=ROOT,env=env,text=True,stdout=subprocess.PIPE,stderr=subprocess.PIPE,timeout=20);assert(result.returncode,result.stdout,result.stderr)==(64,'{"ok":false,"reason":"CONFIGURATION_INVALID"}\n',''),"closed package outcome"
 loaded=[Path(value).as_posix()for value in json.loads(trace.read_text())]
 for marker in ["/rapid-pilot/","/app/demo/","/app/YiiRuntime/Controllers/","/app/Jobs/","/config/yii/web.php","/yii/web/","/app/YiiRuntime/ReliableSession.php"]:assert not any(marker in path for path in loaded),f"forbidden load {marker}"
 for suffix in ["/bin/fmonitor2-yii.php","/app/YiiRuntime/Commands/CaseImportController.php","/app/YiiRuntime/CaseImportConsole.php","/app/InstallationProcess/PilotCaseImporter.php"]:assert any(path.endswith(suffix)for path in loaded),f"required load {suffix}"
 autoloaders=[path for path in loaded if path.endswith('/autoload.php')];assert sorted(path for path in autoloaders if '/app/autoload.php' in path or '/vendor/autoload.php' in path)==sorted(autoloaders),f"no independent legacy autoloader: {autoloaders}"
 startup_trace=Path(directory)/"startup.json";startup=Path(directory)/"startup.php";startup.write_text("<?php register_shutdown_function(static function(){file_put_contents("+repr(str(startup_trace))+",json_encode(get_included_files(),JSON_THROW_ON_ERROR));});require "+repr(str(ROOT/'public/runtime.php'))+";",encoding="utf-8");subprocess.run(["php",str(startup)],cwd=ROOT,env=env,text=True,stdout=subprocess.PIPE,stderr=subprocess.PIPE,timeout=20);startup_loaded=[Path(value).as_posix()for value in json.loads(startup_trace.read_text())];assert not any(path.endswith('/CaseImportController.php')or path.endswith('/CaseImportConsole.php')or path.endswith('/PilotCaseImporter.php')for path in startup_loaded),"ordinary web startup does not load or execute case import"
assert shutil.which("docker"),"SETUP_FAILURE: Docker required for artifact inventory"
tag="fmonitor2-yci-test:"+secrets.token_hex(5)
try:
 built=subprocess.run(["docker","build","--quiet","--file","deploy/runtime/Dockerfile","--tag",tag,"."],cwd=ROOT,text=True,stdout=subprocess.PIPE,stderr=subprocess.PIPE,timeout=300);assert built.returncode==0,"SETUP_FAILURE: image build failed\n"+built.stderr[-2000:]
 presence=subprocess.run(["docker","run","--rm","--entrypoint","sh",tag,"-c","test -x bin/yii && test -f app/YiiRuntime/Commands/CaseImportController.php && test -f app/YiiRuntime/CaseImportConsole.php && test -f app/InstallationProcess/PilotCaseImporter.php && test -f vendor/autoload.php"],text=True,stdout=subprocess.PIPE,stderr=subprocess.PIPE,timeout=30);assert presence.returncode==0,"built artifact lacks case-import closure"
 run=subprocess.run(["docker","run","--rm",tag,"php","bin/yii","case-import/run","--object-id=7","--interactive=0"],text=True,stdout=subprocess.PIPE,stderr=subprocess.PIPE,timeout=30);assert(run.returncode,run.stdout,run.stderr)==(64,'{"ok":false,"reason":"CONFIGURATION_INVALID"}\n',''),"built command is closed"
finally:subprocess.run(["docker","image","rm","--force",tag],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL,timeout=30)
print("PASS: YII2-IMPORTS-WORKFORCE-001 package")

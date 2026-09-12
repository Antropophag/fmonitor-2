#!/usr/bin/env python3
from pathlib import Path
import json,os,subprocess,tempfile
ROOT=Path(__file__).resolve().parents[2]
required=['app/YiiRuntime/Commands/WorkforceSyncController.php','app/YiiRuntime/WorkforceSyncConsole.php','app/InstallationProcess/MariaDbWorkforceSynchronization.php','bin/yii','bin/fmonitor2-yii.php','config/yii/console.php']
for relative in required: assert (ROOT/relative).is_file(),f'INTENDED_RED: package source contains {relative}'
assert 'workforce-sync' in (ROOT/'config/yii/console.php').read_text(),'route registered'
for relative in ['config/yii/web.php','public/runtime.php']:assert 'workforce-sync/run' not in (ROOT/relative).read_text(),f'ordinary runtime does not invoke sync: {relative}'
with tempfile.TemporaryDirectory(prefix='fm2-yws-load-')as directory:
 trace=Path(directory)/'trace.json';wrapper=Path(directory)/'trace.php';wrapper.write_text("<?php register_shutdown_function(static function(){file_put_contents("+repr(str(trace))+",json_encode(get_included_files(),JSON_THROW_ON_ERROR));});require "+repr(str(ROOT/'bin/yii'))+";")
 env=dict(os.environ);[env.pop(n,None)for n in list(env)if n.startswith('FMONITOR_')]
 result=subprocess.run(['php',str(wrapper),'workforce-sync/run','--interactive=0'],cwd=ROOT,env=env,text=True,capture_output=True,timeout=20);assert(result.returncode,result.stdout,result.stderr)==(64,'{"ok":false,"reason":"CONFIGURATION_INVALID"}\n',''),'closed command'
 loaded=[Path(v).as_posix()for v in json.loads(trace.read_text())]
 for marker in ['/rapid-pilot/','/app/demo/','/app/Otiz/','/config/yii/web.php','/yii/web/','/app/YiiRuntime/ReliableSession.php']:assert not any(marker in p for p in loaded),f'forbidden load {marker}'
 for suffix in ['/app/YiiRuntime/Commands/WorkforceSyncController.php','/app/YiiRuntime/WorkforceSyncConsole.php','/app/InstallationProcess/MariaDbWorkforceSynchronization.php']:assert any(p.endswith(suffix)for p in loaded),f'required load {suffix}'
 autoloaders=[p for p in loaded if p.endswith('/autoload.php')];assert all('/app/autoload.php' in p or '/vendor/autoload.php' in p for p in autoloaders),autoloaders
print('PASS: YII2-WORKFORCE-SYNC-CONSOLE-001 package')

#!/usr/bin/env python3
"""YII2-LOCAL-QUICKSTART-001: explicitly admitted disposable real-Docker slot."""
import json, os, pathlib, subprocess

root=pathlib.Path(__file__).resolve().parents[2];authorization=os.getenv('FMONITOR_LOCAL_QUICKSTART_REAL_AUTHORIZATION')
if not authorization:
    print('PASS: YII2-LOCAL-QUICKSTART-001 real Docker execution requires explicit authorization');raise SystemExit(0)
package=pathlib.Path(authorization);assert package.is_absolute() and package.is_file() and not package.is_symlink(),'AUTHORIZATION_REQUIRED'
value=json.loads(package.read_text());required={'source','project','port','env','expectedAbsent','allowReset'}
assert set(value)==required and value['source']==subprocess.check_output(['git','rev-parse','HEAD'],cwd=root,text=True).strip(),'AUTHORIZATION_SOURCE_MISMATCH'
project=value['project'];assert isinstance(project,str) and project.startswith('fm2-quickstart-test-'),'TARGET_NOT_DISPOSABLE'
assert value['expectedAbsent'] is True and value['allowReset'] is True,'DESTRUCTIVE_AUTHORIZATION_REQUIRED'
env_path=pathlib.Path(value['env']);assert env_path.is_absolute() and env_path.is_file() and not env_path.is_symlink(),'PRIVATE_ENV_REQUIRED'
environment={**os.environ,'FMONITOR_LOCAL_ENV_FILE':str(env_path)}
listed=subprocess.run(['docker','compose','ls','--all','--format','json'],cwd=root,text=True,capture_output=True,check=True);assert project not in listed.stdout,'TARGET_NOT_ABSENT'
try:
    first=subprocess.run(['make','up'],cwd=root,env=environment,text=True,capture_output=True);assert first.returncode==0,(first.stdout,first.stderr)
    before=subprocess.check_output(['docker','compose','--env-file',str(env_path),'-f','deploy/runtime/compose.yaml','ps','--format','json'],cwd=root,text=True)
    second=subprocess.run(['make','up'],cwd=root,env=environment,text=True,capture_output=True);assert second.returncode==0,(second.stdout,second.stderr)
    after=subprocess.check_output(['docker','compose','--env-file',str(env_path),'-f','deploy/runtime/compose.yaml','ps','--format','json'],cwd=root,text=True);assert before and after
    stopped=subprocess.run(['make','down'],cwd=root,env=environment,text=True,capture_output=True);assert stopped.returncode==0
finally:
    reset=subprocess.run(['make','reset'],cwd=root,env=environment,text=True,capture_output=True);assert reset.returncode==0,(reset.stdout,reset.stderr)
    listed=subprocess.run(['docker','compose','ls','--all','--format','json'],cwd=root,text=True,capture_output=True,check=True);assert project not in listed.stdout,'DISPOSABLE_CLEANUP_FAILED'
print('PASS: YII2-LOCAL-QUICKSTART-001 real disposable clean/repeat/down/reset')

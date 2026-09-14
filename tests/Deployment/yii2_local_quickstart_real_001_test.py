#!/usr/bin/env python3
"""YII2-LOCAL-QUICKSTART-001: explicitly admitted disposable real-Docker slot."""
import json, os, pathlib, subprocess

root=pathlib.Path(__file__).resolve().parents[2];authorization=os.getenv('FMONITOR_LOCAL_QUICKSTART_REAL_AUTHORIZATION')
if not authorization:
    print('PASS: YII2-LOCAL-QUICKSTART-001 real Docker execution requires explicit authorization');raise SystemExit(0)
package=pathlib.Path(authorization);assert package.is_absolute() and package.is_file() and not package.is_symlink() and package.stat().st_mode&0o777==0o600,'AUTHORIZATION_REQUIRED'
value=json.loads(package.read_text());required={'source','project','port','env','expectedAbsent','allowReset'}
assert set(value)==required and value['source']==subprocess.check_output(['git','rev-parse','HEAD'],cwd=root,text=True).strip(),'AUTHORIZATION_SOURCE_MISMATCH'
project=value['project'];assert isinstance(project,str) and project.startswith('fm2-local-quickstart-test-'),'TARGET_NOT_DISPOSABLE'
assert value['expectedAbsent'] is True and value['allowReset'] is True,'DESTRUCTIVE_AUTHORIZATION_REQUIRED'
env_path=pathlib.Path(value['env']);assert env_path.is_absolute() and env_path.is_file() and not env_path.is_symlink() and env_path.stat().st_mode&0o777==0o600,'PRIVATE_ENV_REQUIRED'
env_values={}
for line in env_path.read_text().splitlines():
    if line and not line.lstrip().startswith('#') and '=' in line:
        key,item=line.split('=',1);env_values[key]=item.strip().strip("'\"")
assert env_values.get('COMPOSE_PROJECT_NAME')==project,'AUTHORIZATION_PROJECT_MISMATCH'
assert str(env_values.get('FMONITOR_HTTP_PORT'))==str(value['port']),'AUTHORIZATION_PORT_MISMATCH'
environment={**os.environ,'FMONITOR_LOCAL_ENV_FILE':str(env_path)}
listed=subprocess.run(['docker','compose','ls','--all','--format','json'],cwd=root,text=True,capture_output=True,check=True);assert project not in listed.stdout,'TARGET_NOT_ABSENT'
try:
    first=subprocess.run(['make','up'],cwd=root,env=environment,text=True,capture_output=True);assert first.returncode==0,(first.stdout,first.stderr)
    compose=['docker','compose','--env-file',str(env_path),'-f','deploy/runtime/compose.yaml'];host=env_values['FMONITOR_TRUSTED_REQUEST_HOST'];port=str(value['port'])
    for path in ('/health/live','/health/ready'):
        checked=subprocess.run(['curl','--fail','--silent','--show-error','--header','Host: '+host,f'http://127.0.0.1:{port}{path}'],text=True,capture_output=True);assert checked.returncode==0,(path,checked.stderr)
    query="SELECT CONCAT((SELECT COUNT(*) FROM fm2_fm2_pilot_users),'|',(SELECT COALESCE(MIN(user_id),0) FROM fm2_fm2_pilot_users),'|',(SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()),'|',(SELECT marker FROM quickstart_acceptance_sentinel))"
    subprocess.run([*compose,'exec','-T','db','sh','-c','MYSQL_PWD="$MARIADB_ROOT_PASSWORD" exec mariadb -uroot "$MARIADB_DATABASE"'],cwd=root,input='CREATE TABLE quickstart_acceptance_sentinel(marker VARCHAR(64) NOT NULL); INSERT INTO quickstart_acceptance_sentinel VALUES ("domain-preserved-001");\n',text=True,check=True)
    before_db=subprocess.check_output([*compose,'exec','-T','db','sh','-c','MYSQL_PWD="$MARIADB_ROOT_PASSWORD" exec mariadb -N -uroot "$MARIADB_DATABASE" -e "$FMONITOR_TEST_QUERY"'],cwd=root,text=True,env={**environment,'FMONITOR_TEST_QUERY':query})
    subprocess.run([*compose,'exec','-T','php','sh','-c','printf session-preserved > /home/fmonitor/.local/state/fmonitor2/yii-sessions/quickstart-real-sentinel; printf artifact-preserved > /home/fmonitor/.local/state/fmonitor2/artifacts/quickstart-real-sentinel'],cwd=root,check=True)
    before=subprocess.check_output([*compose,'ps','--format','json'],cwd=root,text=True)
    second=subprocess.run(['make','up'],cwd=root,env=environment,text=True,capture_output=True);assert second.returncode==0,(second.stdout,second.stderr)
    after=subprocess.check_output([*compose,'ps','--format','json'],cwd=root,text=True);after_db=subprocess.check_output([*compose,'exec','-T','db','sh','-c','MYSQL_PWD="$MARIADB_ROOT_PASSWORD" exec mariadb -N -uroot "$MARIADB_DATABASE" -e "$FMONITOR_TEST_QUERY"'],cwd=root,text=True,env={**environment,'FMONITOR_TEST_QUERY':query})
    session=subprocess.check_output([*compose,'exec','-T','php','cat','/home/fmonitor/.local/state/fmonitor2/yii-sessions/quickstart-real-sentinel'],cwd=root,text=True);artifact=subprocess.check_output([*compose,'exec','-T','php','cat','/home/fmonitor/.local/state/fmonitor2/artifacts/quickstart-real-sentinel'],cwd=root,text=True)
    assert before and after and before_db==after_db and session=='session-preserved' and artifact=='artifact-preserved','REAL_REPLAY_MUTATED_STATE'
    stopped=subprocess.run(['make','down'],cwd=root,env=environment,text=True,capture_output=True);assert stopped.returncode==0
    restarted=subprocess.run(['make','up'],cwd=root,env=environment,text=True,capture_output=True);assert restarted.returncode==0
    assert subprocess.check_output([*compose,'exec','-T','php','cat','/home/fmonitor/.local/state/fmonitor2/yii-sessions/quickstart-real-sentinel'],cwd=root,text=True)=='session-preserved','DOWN_REMOVED_SESSION'
    assert subprocess.check_output([*compose,'exec','-T','php','cat','/home/fmonitor/.local/state/fmonitor2/artifacts/quickstart-real-sentinel'],cwd=root,text=True)=='artifact-preserved','DOWN_REMOVED_ARTIFACT'
    assert subprocess.check_output([*compose,'exec','-T','db','sh','-c','MYSQL_PWD="$MARIADB_ROOT_PASSWORD" exec mariadb -N -uroot "$MARIADB_DATABASE" -e "$FMONITOR_TEST_QUERY"'],cwd=root,text=True,env={**environment,'FMONITOR_TEST_QUERY':query})==before_db,'DOWN_REMOVED_DOMAIN'
finally:
    reset=subprocess.run(['make','reset'],cwd=root,env=environment,text=True,capture_output=True);assert reset.returncode==0,(reset.stdout,reset.stderr)
    listed=subprocess.run(['docker','compose','ls','--all','--format','json'],cwd=root,text=True,capture_output=True,check=True);assert project not in listed.stdout,'DISPOSABLE_CLEANUP_FAILED'
print('PASS: YII2-LOCAL-QUICKSTART-001 real disposable clean/repeat/down/reset')

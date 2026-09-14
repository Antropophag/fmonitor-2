#!/usr/bin/env python3
"""YII2-LOCAL-QUICKSTART-001: stateful public Make lifecycle contract."""
import hashlib, json, os, shutil, subprocess, tempfile
from pathlib import Path

root=Path(__file__).resolve().parents[2]
with tempfile.TemporaryDirectory() as raw:
    sandbox=Path(raw);checkout=sandbox/'checkout';checkout.mkdir()
    for relative in ('Makefile','.env.example'): shutil.copy2(root/relative,checkout/relative)
    for relative in ('deploy/runtime/compose.yaml','deploy/runtime/Dockerfile'):
        target=checkout/relative;target.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(root/relative,target)
    helper=root/'tools/delivery/local-runtime-env'
    if helper.is_file():
        target=checkout/'tools/delivery/local-runtime-env';target.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(helper,target)
    values={'COMPOSE_PROJECT_NAME':'fm2-local-contract','FMONITOR_RUNTIME_IMAGE':'fmonitor2-runtime:contract','FMONITOR_HTTP_PORT':'18093','FMONITOR_DB_NAME':'fmonitor2','FMONITOR_DB_USER':'fmonitor_runtime','FMONITOR_DB_PASSWORD':'db-secret-contract','FMONITOR_MIGRATION_DB_USER':'root','FMONITOR_MIGRATION_DB_PASSWORD':'migration-secret-contract','FMONITOR_PROCESS_TABLE_PREFIX':'fm2_','FMONITOR_LEGACY_TABLE_PREFIX':'fm2_','FMONITOR_SESSION_INSTANCE':'local-contract','FMONITOR_YII_COOKIE_VALIDATION_KEY':'c'*32,'FMONITOR_YII_IDENTITY_KEY':'i'*32,'FMONITOR_TRUSTED_REQUEST_HOST':'127.0.0.1:18093','FMONITOR_TRUSTED_REQUEST_SCHEME':'http','FMONITOR_INITIAL_OWNER_EMAIL':'owner@example.test','FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD':'owner-secret-contract'}
    def write_env(changes=None):
        current={**values,**(changes or {})};(checkout/'.env').write_text(''.join(f'{k}={v}\n' for k,v in current.items()))
    write_env();fake_bin=sandbox/'bin';fake_bin.mkdir();trace=sandbox/'trace.jsonl';state=sandbox/'state.json';state.write_text('{}')
    driver=fake_bin/'docker';driver.write_text('''#!/usr/bin/env python3
import hashlib,json,os,sys
trace=os.environ['FMONITOR_TEST_TRACE'];state_path=os.environ['FMONITOR_TEST_STATE'];argv=sys.argv[1:];project=os.environ.get('COMPOSE_PROJECT_NAME','')
open(trace,'a').write(json.dumps({'tool':'docker','argv':argv,'project':project,'dbPasswordDigest':hashlib.sha256(os.environ.get('FMONITOR_DB_PASSWORD','').encode()).hexdigest()})+'\\n');joined=' '.join(argv);fail=os.environ.get('FMONITOR_TEST_FAIL_STAGE','')
stages={'build':'build ','db':'up --detach --wait db','provision-db':'local-runtime/provision-database','prepare':'run --rm prepare','migrate':'run --rm migrate','runtime-check':'fmonitor2-runtime-check.php','owner':'provision-initial-admin.php','services':'up --detach --wait php web'}
if fail in stages and stages[fail] in joined:sys.exit(42)
if '--volumes' in argv and not ('compose' in argv and '-f' in argv and 'deploy/runtime/compose.yaml' in argv and project.startswith('fm2-local-') and 'down' in argv and '--remove-orphans' in argv):sys.exit(43)
allowed=('info','build ','config --quiet','up --detach --wait db','local-runtime/provision-database','run --rm prepare','run --rm migrate','fmonitor2-runtime-check.php','provision-initial-admin.php','up --detach --wait php web',' down',' logs',' ps')
if not any(token in ' '+joined for token in allowed):sys.exit(44)
data=json.load(open(state_path));resources=data.get(project)
if 'up --detach --wait db' in joined and resources is None:resources={'database':'domain-sentinel-001','owner':'owner-001','session':'session-sentinel-001','artifact':'artifact-sentinel-001','volumes':True,'running':False};data[project]=resources
if resources is None and not ('info'==joined or joined.startswith('build ') or 'config --quiet' in joined):sys.exit(45)
if 'up --detach --wait php web' in joined:resources['running']=True
if ' down' in ' '+joined:
 resources['running']=False
 if '--volumes' in argv:data.pop(project,None)
open(state_path,'w').write(json.dumps(data));sys.exit(0)
''');driver.chmod(0o755)
    curl=fake_bin/'curl';curl.write_text('''#!/usr/bin/env python3
import json,os,sys
open(os.environ['FMONITOR_TEST_TRACE'],'a').write(json.dumps({'tool':'curl','argv':sys.argv[1:],'project':os.environ.get('COMPOSE_PROJECT_NAME','')})+'\\n')
kind='ready' if '/ready' in ' '.join(sys.argv) else 'live';sys.exit(42 if os.environ.get('FMONITOR_TEST_FAIL_STAGE')==kind else 0)
''');curl.chmod(0o755)
    base_env={**os.environ,'PATH':f'{fake_bin}:{os.environ["PATH"]}','FMONITOR_TEST_TRACE':str(trace),'FMONITOR_TEST_STATE':str(state),'COMPOSE_PROJECT_NAME':'hostile-production','FMONITOR_DB_PASSWORD':'hostile-secret'}
    def make(target,extra=None):return subprocess.run(['make','--no-print-directory',target],cwd=checkout,env={**base_env,**(extra or {})},text=True,capture_output=True)
    def events():return [json.loads(line) for line in trace.read_text().splitlines()]
    def clear():trace.write_text('')

    first=make('up');assert first.returncode==0,(first.stdout,first.stderr,'LEGACY_MAKE_UP_TRACE')
    observed=events();joined=[' '.join(e['argv']) for e in observed];assert all(e['project']=='fm2-local-contract' for e in observed),('ENV_PROJECT_NOT_BOUND',observed)
    required=['build ','config --quiet','up --detach --wait db','local-runtime/provision-database','run --rm prepare','run --rm migrate','fmonitor2-runtime-check.php','provision-initial-admin.php','up --detach --wait php web','/health/live','/health/ready'];positions=[]
    for needle in required:positions.append(next(i for i,value in enumerate(joined) if needle in value))
    assert positions==sorted(positions),('LIFECYCLE_ORDER_WRONG',joined)
    assert len(joined)==len(required)+1 and joined[0]=='info',('UNEXPECTED_OR_MISSING_OPERATION',joined)
    combined=first.stdout+first.stderr+trace.read_text()
    for secret in ('db-secret-contract','migration-secret-contract','owner-secret-contract','hostile-secret'):assert secret not in combined,('SECRET_EXPOSED',secret)
    assert 'http://127.0.0.1:18093' in first.stdout;before=json.loads(state.read_text())['fm2-local-contract'].copy()
    clear();second=make('up');assert second.returncode==0;after=json.loads(state.read_text())['fm2-local-contract'];assert before==after and all('--volumes' not in e['argv'] for e in events()),'REPLAY_MUTATED_STATE'
    clear();assert make('ps').returncode==0;assert any('deploy/runtime/compose.yaml' in ' '.join(e['argv']) and 'ps' in e['argv'] for e in events())
    clear();assert make('logs').returncode==0;assert any('logs' in e['argv'] for e in events())
    clear();assert make('down').returncode==0;down_state=json.loads(state.read_text())['fm2-local-contract'];assert down_state['volumes'] and not down_state['running']
    all_state=json.loads(state.read_text());all_state['fm2-local-neighbor']={'database':'neighbor','volumes':True,'running':True};state.write_text(json.dumps(all_state))
    clear();assert make('reset').returncode==0;remaining=json.loads(state.read_text());assert 'fm2-local-contract' not in remaining and remaining['fm2-local-neighbor']['running'];assert any('down --volumes --remove-orphans' in ' '.join(e['argv']) for e in events())
    marker=sandbox/'ENV_MUST_NOT_EXECUTE';literal=f'$(shell touch {marker})';write_env({'FMONITOR_DB_PASSWORD':literal});clear();literal_run=make('ps');assert literal_run.returncode==0 and not marker.exists(),'DOTENV_EXECUTED_AS_MAKE';literal_observation=literal_run.stdout+literal_run.stderr+trace.read_text();assert literal not in literal_observation and str(marker) not in literal_observation,'DOTENV_SECRET_DISCLOSED';assert events()[0]['dbPasswordDigest']==hashlib.sha256(literal.encode()).hexdigest(),'DOTENV_VALUE_CHANGED';write_env()
    mandatory=tuple(values);invalid_cases=[{key:None} for key in mandatory]+[{key:'replace_me'} for key in mandatory]+[{'FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD':'replace_with_a_strong_unique_password'},{'FMONITOR_YII_COOKIE_VALIDATION_KEY':'short'},{'FMONITOR_YII_IDENTITY_KEY':'short'},{'FMONITOR_TRUSTED_REQUEST_SCHEME':'ftp'},{'COMPOSE_PROJECT_NAME':''},{'COMPOSE_PROJECT_NAME':'bad project'},{'COMPOSE_PROJECT_NAME':'fmonitor2-production'},{'COMPOSE_PROJECT_NAME':'fm2-local-production'},{'COMPOSE_PROJECT_NAME':'fm2-local-prod'},{'FMONITOR_INITIAL_OWNER_EMAIL':''}]
    for changes in invalid_cases:
        current={k:v for k,v in {**values,**changes}.items() if v is not None};(checkout/'.env').write_text(''.join(f'{k}={v}\n' for k,v in current.items()));clear();rejected=make('up');assert rejected.returncode!=0,('INVALID_ACCEPTED',changes);assert events()==[],('INVALID_EFFECT',changes);assert 'replace_with_a_strong_unique_password' not in rejected.stdout+rejected.stderr
    for project in ('','replace_me','bad project','fmonitor2-production','fm2-local-production','fm2-local-prod','fm2-local'):
        write_env({'COMPOSE_PROJECT_NAME':project});clear();rejected=make('reset');assert rejected.returncode!=0 and events()==[],('UNBOUNDED_RESET',project)
    (checkout/'.env').unlink();clear();assert make('up').returncode!=0 and events()==[],'MISSING_ENV_EFFECT';assert make('reset').returncode!=0 and events()==[],'MISSING_ENV_RESET';write_env()
    for stage in ('build','db','provision-db','prepare','migrate','runtime-check','owner','services','live','ready'):
        clear();failed=make('up',{'FMONITOR_TEST_FAIL_STAGE':stage});assert failed.returncode!=0,('STAGE_ACCEPTED',stage);stage_events=events();text=failed.stdout+failed.stderr+trace.read_text();assert 'FMonitor Yii2:' not in text
        for secret in ('db-secret-contract','migration-secret-contract','owner-secret-contract'):assert secret not in text
        assert all(e['project']=='fm2-local-contract' for e in stage_events)
        failed_needle=({'live':'/health/live','ready':'/health/ready'}[stage] if stage in ('live','ready') else {'build':'build ','db':'up --detach --wait db','provision-db':'local-runtime/provision-database','prepare':'run --rm prepare','migrate':'run --rm migrate','runtime-check':'fmonitor2-runtime-check.php','owner':'provision-initial-admin.php','services':'up --detach --wait php web'}[stage])
        failed_index=next(i for i,e in enumerate(stage_events) if failed_needle in ' '.join(e['argv']))
        assert failed_index==len(stage_events)-1,('EFFECT_AFTER_FAILURE',stage,stage_events)
print('PASS: YII2-LOCAL-QUICKSTART-001 stateful Make lifecycle')

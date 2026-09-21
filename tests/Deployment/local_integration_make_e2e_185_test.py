#!/usr/bin/env python3
"""LOCAL-INTEGRATION-ENV-001: real Make/Compose/importer synthetic acceptance."""
import json, os, re, shutil, subprocess, tempfile, time
from pathlib import Path

ROOT=Path(__file__).resolve().parents[2]
WRAPPER=ROOT/'bin/fmonitor2-run-with-local-integration-config'
assert WRAPPER.is_file(),'INTENDED_RED: real Make/Compose delivery seam missing'
dockerfile=(ROOT/'deploy/runtime/Dockerfile').read_text()
assert 'fmonitor2-run-with-local-integration-config' in dockerfile
makefile=(ROOT/'Makefile').read_text()
assert 'FMONITOR_BITRIX_CA_FILE_HOST' in makefile,'INTENDED_RED: local verified-TLS endpoint CA is not delivered'
for name in ('docker','openssl'):
    assert shutil.which(name),f'SETUP_FAILURE: {name} required'

token=os.urandom(5).hex(); project='fm2-local-i185-'+token; image='fmonitor2-i185:'+token
endpoint='fm2-i185-bitrix-'+token
env_path=ROOT/'.env'; local=ROOT/'.local'
assert not env_path.exists(),'SETUP_FAILURE: task checkout already has .env'
fixture=Path(tempfile.mkdtemp(prefix='fm2-i185-e2e-'))
http_port=str(28000+(int(token[:4],16)%20000))
legacy_secret='LEGACY_E2E_SECRET'; bitrix_old='BITRIX_E2E_OLD'; bitrix_new='BITRIX_E2E_NEW'
base=f"""COMPOSE_PROJECT_NAME={project}
FMONITOR_RUNTIME_IMAGE={image}
FMONITOR_HTTP_PORT={http_port}
FMONITOR_DB_NAME=fmonitor2
FMONITOR_DB_USER=fmonitor_runtime
FMONITOR_DB_PASSWORD=DB_E2E_SECRET
FMONITOR_MIGRATION_DB_USER=root
FMONITOR_MIGRATION_DB_PASSWORD=MIGRATION_E2E_SECRET
FMONITOR_PROCESS_TABLE_PREFIX=fm2_
FMONITOR_LEGACY_TABLE_PREFIX=fm2_
FMONITOR_SESSION_INSTANCE=i185
FMONITOR_YII_COOKIE_VALIDATION_KEY=cccccccccccccccccccccccccccccccc
FMONITOR_YII_IDENTITY_KEY=iiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii
FMONITOR_TRUSTED_REQUEST_HOST=127.0.0.1:{http_port}
FMONITOR_TRUSTED_REQUEST_SCHEME=http
FMONITOR_INITIAL_OWNER_EMAIL=issue185@shlz.ru
FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD=Owner-Issue-185-Synthetic!
FMONITOR_ERP_HOST=erp.example.invalid
FMONITOR_ERP_DATABASE=legacy-stage
FMONITOR_ERP_USER=reader
FMONITOR_ERP_PASSWORD=ERP_E2E_SECRET
FMONITOR_ERP_EQUIPMENT_FACTS_HMAC_KEY=hhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhh
FMONITOR_ERP_EQUIPMENT_FACTS_MAX_ROWS=500
FMONITOR_ERP_EQUIPMENT_FACTS_TIMEOUT_SECONDS=5
FMONITOR_ERP_EQUIPMENT_FACTS_CHUNK_SIZE=100
FMONITOR_SOURCE_HOST=db
FMONITOR_SOURCE_PORT=3306
FMONITOR_SOURCE_NAME=legacy_fixture
FMONITOR_SOURCE_USER=legacy_reader
FMONITOR_SOURCE_PASSWORD={legacy_secret}
FMONITOR_MIGRATION_CUTOFF=2026-09-18 23:59:59
FMONITOR_BITRIX_WEBHOOK_URL='https://bitrix-fixture:8443/rest/7/{bitrix_old}/'
FMONITOR_BITRIX_DEPARTMENT_IDS_JSON='[71]'
FMONITOR_BITRIX_CA_FILE_HOST={fixture}/ca.crt
"""

def run(argv, *, input_text=None, ok=True):
    r=subprocess.run(argv,cwd=ROOT,input=input_text,text=True,capture_output=True,timeout=600)
    combined=r.stdout+r.stderr
    for secret in (legacy_secret,bitrix_old,bitrix_new):
        assert secret not in combined,(argv,secret,combined[-2000:])
    if ok and r.returncode!=0:
        diagnostic=''
        if argv[-1:] == ['sync-workforce']:
            endpoint_logs=subprocess.run(['docker','logs',endpoint],text=True,capture_output=True)
            connections=len((fixture/'connections.jsonl').read_text().splitlines()) if (fixture/'connections.jsonl').exists() else 0
            requests=len((fixture/'requests.jsonl').read_text().splitlines()) if (fixture/'requests.jsonl').exists() else 0
            verify=subprocess.run(['openssl','verify','-CAfile',str(fixture/'ca.crt'),str(fixture/'server.crt')],text=True,capture_output=True)
            curl_probe=subprocess.run(['docker','run','--rm','--network',project+'_default','-v',str(fixture)+':/fixture:ro','--entrypoint','curl',image,'--silent','--show-error','--cacert','/fixture/ca.crt','https://bitrix-fixture:8443/fixture-health'],text=True,capture_output=True)
            diagnostic=f' endpoint_logs={endpoint_logs.stdout+endpoint_logs.stderr!r} connections={connections} requests={requests} cert_verify={verify.returncode} curl_probe={curl_probe.returncode}:{curl_probe.stderr!r}'
        raise AssertionError((argv,r.returncode,combined[-4000:]+diagnostic))
    return r

try:
    env_path.write_text(base);env_path.chmod(0o600)
    run(['make','--no-print-directory','up'])
    seed="""CREATE DATABASE legacy_fixture CHARACTER SET utf8mb4;\nCREATE USER 'legacy_reader'@'%' IDENTIFIED BY 'LEGACY_E2E_SECRET';\nGRANT SELECT ON legacy_fixture.* TO 'legacy_reader'@'%';\nUSE legacy_fixture;\nCREATE TABLE fm_maintable(id BIGINT PRIMARY KEY,ordadr_address VARCHAR(100),entrance VARCHAR(20),regnumber VARCHAR(40),workdatestart DATETIME NULL,workdatestartadjusted DATETIME NULL,workdateendadjusted DATETIME NULL,plan_finish_date DATETIME NULL,workdatefinish DATETIME NULL,ptoactdate DATETIME NULL,responsstroicontrol BIGINT NULL,factworkstartdate DATETIME NULL,object_status VARCHAR(40),fact_percent INT,workstarted INT,floors VARCHAR(40),weight VARCHAR(40),speed VARCHAR(40),pittype VARCHAR(40),pitmaterial VARCHAR(40),paired VARCHAR(40),zavnumber VARCHAR(120) NULL);\nCREATE TABLE users_roles(id BIGINT PRIMARY KEY,name VARCHAR(120),status TINYINT NOT NULL);
CREATE TABLE users(id BIGINT PRIMARY KEY,name VARCHAR(200),email VARCHAR(200),status TINYINT NOT NULL,role_id BIGINT NOT NULL);
INSERT INTO users_roles VALUES(16,'Строительный контроль',1);
INSERT INTO users VALUES(77,'Инженер Импорт','engineer.import@shlz.ru',1,16);
CREATE TABLE fm_install_checklists_values_log(value_id BIGINT,ctime DATETIME);\nCREATE TABLE fm_install_checklists_values(id BIGINT PRIMARY KEY,value_id BIGINT);\nCREATE TABLE fm_install_checklists_values_installators_log(checklist_value_id BIGINT,ctime DATETIME);\nCREATE TABLE fm_install_checklist_parts(id INT PRIMARY KEY,name VARCHAR(100),rang INT);\nCREATE TABLE fm_install_checklist(id INT PRIMARY KEY,part_id INT,name VARCHAR(100),share INT,rang INT,needphoto INT);\nINSERT INTO fm_install_checklist_parts VALUES(1,'Part 1',1);\nINSERT INTO fm_install_checklist_parts VALUES(2,'Part 2',2);\nINSERT INTO fm_install_checklist_parts VALUES(3,'Part 3',3);\nINSERT INTO fm_install_checklist_parts VALUES(4,'Part 4',4);\nINSERT INTO fm_install_checklist_parts VALUES(5,'Part 5',5);\nINSERT INTO fm_install_checklist_parts VALUES(6,'Part 6',6);\nINSERT INTO fm_install_checklist_parts VALUES(7,'Part 7',7);\nINSERT INTO fm_install_checklist_parts VALUES(8,'Part 8',8);\nINSERT INTO fm_install_checklist VALUES(1,1,'Item 1',45,1,0);\nINSERT INTO fm_install_checklist VALUES(2,1,'Item 2',1,2,0);\nINSERT INTO fm_install_checklist VALUES(3,1,'Item 3',1,3,0);\nINSERT INTO fm_install_checklist VALUES(4,1,'Item 4',1,4,0);\nINSERT INTO fm_install_checklist VALUES(5,1,'Item 5',1,5,0);\nINSERT INTO fm_install_checklist VALUES(6,1,'Item 6',1,6,0);\nINSERT INTO fm_install_checklist VALUES(7,2,'Item 7',1,7,0);\nINSERT INTO fm_install_checklist VALUES(8,2,'Item 8',1,8,0);\nINSERT INTO fm_install_checklist VALUES(9,2,'Item 9',1,9,0);\nINSERT INTO fm_install_checklist VALUES(10,2,'Item 10',1,10,0);\nINSERT INTO fm_install_checklist VALUES(11,2,'Item 11',1,11,0);\nINSERT INTO fm_install_checklist VALUES(12,2,'Item 12',1,12,0);\nINSERT INTO fm_install_checklist VALUES(13,3,'Item 13',1,13,0);\nINSERT INTO fm_install_checklist VALUES(14,3,'Item 14',1,14,0);\nINSERT INTO fm_install_checklist VALUES(15,3,'Item 15',1,15,0);\nINSERT INTO fm_install_checklist VALUES(16,3,'Item 16',1,16,0);\nINSERT INTO fm_install_checklist VALUES(17,3,'Item 17',1,17,0);\nINSERT INTO fm_install_checklist VALUES(18,3,'Item 18',1,18,0);\nINSERT INTO fm_install_checklist VALUES(19,4,'Item 19',1,19,0);\nINSERT INTO fm_install_checklist VALUES(20,4,'Item 20',1,20,0);\nINSERT INTO fm_install_checklist VALUES(21,4,'Item 21',1,21,0);\nINSERT INTO fm_install_checklist VALUES(22,4,'Item 22',1,22,0);\nINSERT INTO fm_install_checklist VALUES(23,4,'Item 23',1,23,0);\nINSERT INTO fm_install_checklist VALUES(24,4,'Item 24',1,24,0);\nINSERT INTO fm_install_checklist VALUES(25,5,'Item 25',1,25,0);\nINSERT INTO fm_install_checklist VALUES(26,5,'Item 26',1,26,0);\nINSERT INTO fm_install_checklist VALUES(27,5,'Item 27',1,27,0);\nINSERT INTO fm_install_checklist VALUES(28,5,'Item 28',1,28,0);\nINSERT INTO fm_install_checklist VALUES(29,5,'Item 29',1,29,0);\nINSERT INTO fm_install_checklist VALUES(30,5,'Item 30',1,30,0);\nINSERT INTO fm_install_checklist VALUES(31,6,'Item 31',1,31,0);\nINSERT INTO fm_install_checklist VALUES(32,6,'Item 32',1,32,0);\nINSERT INTO fm_install_checklist VALUES(33,6,'Item 33',1,33,0);\nINSERT INTO fm_install_checklist VALUES(34,6,'Item 34',1,34,0);\nINSERT INTO fm_install_checklist VALUES(35,6,'Item 35',1,35,0);\nINSERT INTO fm_install_checklist VALUES(36,6,'Item 36',1,36,0);\nINSERT INTO fm_install_checklist VALUES(37,7,'Item 37',1,37,0);\nINSERT INTO fm_install_checklist VALUES(38,7,'Item 38',1,38,0);\nINSERT INTO fm_install_checklist VALUES(39,7,'Item 39',1,39,0);\nINSERT INTO fm_install_checklist VALUES(40,7,'Item 40',1,40,0);\nINSERT INTO fm_install_checklist VALUES(41,7,'Item 41',1,41,0);\nINSERT INTO fm_install_checklist VALUES(42,7,'Item 42',15,42,0);\nINSERT INTO fm_maintable VALUES(18501,'Synthetic issue 185','1','REG-18501','2026-10-01',NULL,'2026-10-20','2026-10-20',NULL,NULL,77,NULL,'',0,0,'10','1000','1.0','dry','brick','0','ORDER-18501');\n"""
    run(['bash','tools/delivery/local-runtime-env','--','docker','compose','--env-file','@env-file','-f','deploy/runtime/compose.yaml','exec','-T','db','sh','-c','MYSQL_PWD="$MARIADB_ROOT_PASSWORD" mariadb -uroot'],input_text=seed)
    legacy=run(['make','--no-print-directory','import-legacy'])
    assert 'LEGACY_IMPORT_COMPLETED' in legacy.stdout
    count=run(['bash','tools/delivery/local-runtime-env','--','docker','compose','--env-file','@env-file','-f','deploy/runtime/compose.yaml','exec','-T','db','sh','-c','MYSQL_PWD="$MARIADB_ROOT_PASSWORD" mariadb -N -uroot fmonitor2 -e "SELECT COUNT(*) FROM fm2_fm2_installation_cases WHERE legacy_installation_object_id=18501"'])
    assert count.stdout.strip()=='1',count.stdout

    (fixture/'ca.cnf').write_text('[req]\nprompt=no\ndistinguished_name=dn\nx509_extensions=ca\n[dn]\nCN=Issue185 CA\n[ca]\nbasicConstraints=critical,CA:TRUE\nkeyUsage=critical,keyCertSign,cRLSign\n')
    run(['openssl','req','-new','-x509','-newkey','rsa:2048','-nodes','-days','2','-config',str(fixture/'ca.cnf'),'-keyout',str(fixture/'ca.key'),'-out',str(fixture/'ca.crt')])
    run(['openssl','req','-new','-newkey','rsa:2048','-nodes','-subj','/CN=bitrix-fixture','-addext','subjectAltName=DNS:bitrix-fixture','-addext','extendedKeyUsage=serverAuth','-keyout',str(fixture/'server.key'),'-out',str(fixture/'server.csr')])
    run(['openssl','x509','-req','-in',str(fixture/'server.csr'),'-CA',str(fixture/'ca.crt'),'-CAkey',str(fixture/'ca.key'),'-CAcreateserial','-days','2','-copy_extensions','copy','-out',str(fixture/'server.crt')])
    run(['openssl','x509','-in',str(fixture/'server.crt'),'-noout','-checkhost','bitrix-fixture'])
    (fixture/'scenario.json').write_text('{"mode":"full"}')
    server=(ROOT/'tests/Support/bitrix_delivery_https_server.py').read_text().replace("server=Server(('127.0.0.1',0),Handler)","server=Server(('0.0.0.0',8443),Handler)")
    (fixture/'server.py').write_text(server)
    network=project+'_default'
    run(['docker','run','-d','--name',endpoint,'--network',network,'--network-alias','bitrix-fixture','-v',str(fixture)+':/fixture','python:3.13-alpine','python','/fixture/server.py','/fixture'])
    deadline=time.time()+20
    while not (fixture/'ready.json').exists() and time.time()<deadline: time.sleep(.05)
    assert (fixture/'ready.json').exists(),'SETUP_FAILURE: Bitrix endpoint readiness'

    first=run(['make','--no-print-directory','sync-workforce'])
    assert json.loads(first.stdout.splitlines()[-1])['status']=='completed'
    workforce=run(['bash','tools/delivery/local-runtime-env','--','docker','compose','--env-file','@env-file','-f','deploy/runtime/compose.yaml','exec','-T','db','sh','-c','MYSQL_PWD="$MARIADB_ROOT_PASSWORD" mariadb -N -uroot fmonitor2 -e "SELECT COUNT(*) FROM fm2_fm2_workforce_catalog"'])
    assert workforce.stdout.strip()=='51'
    env_path.write_text(base.replace(bitrix_old,bitrix_new));env_path.chmod(0o600)
    second=run(['make','--no-print-directory','sync-workforce'])
    assert json.loads(second.stdout.splitlines()[-1])['status']=='completed'
    paths=[json.loads(x)['path'] for x in (fixture/'requests.jsonl').read_text().splitlines()]
    assert any('/'+bitrix_old+'/' in x for x in paths) and any('/'+bitrix_new+'/' in x for x in paths)
    assert not any(p.name.startswith('.') and '.tmp-' in p.name for p in local.iterdir())
    rendered=run(['bash','tools/delivery/local-runtime-env','--','docker','compose','--env-file','@env-file','-f','deploy/runtime/compose.yaml','config'])
    for secret in (legacy_secret,bitrix_old,bitrix_new):assert secret not in rendered.stdout+rendered.stderr
    app_logs=run(['bash','tools/delivery/local-runtime-env','--','docker','compose','--env-file','@env-file','-f','deploy/runtime/compose.yaml','logs','--no-color'])
    for secret in (legacy_secret,bitrix_old,bitrix_new):assert secret not in app_logs.stdout+app_logs.stderr
finally:
    subprocess.run(['docker','rm','-f',endpoint],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
    if env_path.exists():
        subprocess.run(['make','--no-print-directory','reset'],cwd=ROOT,stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
        env_path.unlink(missing_ok=True)
    shutil.rmtree(local,ignore_errors=True);shutil.rmtree(fixture,ignore_errors=True)
    subprocess.run(['docker','image','rm','-f',image],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
print('PASS: LOCAL-INTEGRATION-ENV-001 real public Make synthetic acceptance')

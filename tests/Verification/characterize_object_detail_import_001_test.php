<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__, 2) . '/app/InstallationProcess/ObjectDetailSnapshotSchemaMigration.php';

use FMonitor2\InstallationProcess\ObjectDetailSnapshotSchemaMigration;

/** CHARACTERIZE-OBJECT-DETAIL-IMPORT-001 v0.2, approved Gate 2 oracle. */
const ODCI_SPEC_SHA256 = 'a2e9f65a20bd6e33c740c774094508b32a33faa6e0bdf4ace498e31f024e24c9';
const ODCI_IMAGE_ID = 'sha256:39596f079862334be04f4231664862e55d4febe54309cc62f750f2297de85b06';
const ODCI_IMAGE = 'mariadb:11.4.7-noble';
const ODCI_PASSWORD = 'odci_private_fixture_only';
const ODCI_CAPTURE_FIRST = '2026-09-01T10:15:00+03:00';
const ODCI_CAPTURE_REPEAT = '2026-09-02T11:45:00+03:00';
const ODCI_EXPECTED_HASH = '5fbb37587f0bd1dff238fd1e97972b4e74d9ac4583c875961d9639e6022e0d15';
const ODCI_MISSING_HASH = '5f3d14bbdc7708430233092240bc82fe3ab3e28867ef1e4b7bc14fbf542e2b89';

function odciFail(string $category, string $message): never
{
    throw new TestFailure($category . ': ' . $message);
}

/** @return array{status:int,stdout:string,stderr:string} */
function odciProcess(array $argv, array $environment = [], float $seconds = 30.0): array
{
    $pipes = [];
    $process = proc_open($argv, [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes, dirname(__DIR__, 2), $environment === [] ? null : $environment);
    if (!is_resource($process)) odciFail('SETUP_FAILURE', 'process did not start');
    fclose($pipes[0]); stream_set_blocking($pipes[1], false); stream_set_blocking($pipes[2], false);
    $stdout = ''; $stderr = ''; $deadline = hrtime(true) + (int)($seconds * 1e9); $status = null;
    while (true) {
        $stdout .= stream_get_contents($pipes[1]); $stderr .= stream_get_contents($pipes[2]);
        if (strlen($stdout) > 262144 || strlen($stderr) > 262144) { proc_terminate($process, SIGTERM); odciFail('REGRESSION_FAILURE', 'child output exceeded 256 KiB'); }
        $state = proc_get_status($process);
        if (!($state['running'] ?? false)) { $status = (int)$state['exitcode']; break; }
        if (hrtime(true) >= $deadline) {
            proc_terminate($process, SIGTERM); $grace = hrtime(true) + 2000000000;
            while ((proc_get_status($process)['running'] ?? false) && hrtime(true) < $grace) usleep(20000);
            if (proc_get_status($process)['running'] ?? false) proc_terminate($process, SIGKILL);
            odciFail('REGRESSION_FAILURE', 'importer child exceeded deadline');
        }
        usleep(20000);
    }
    stream_set_blocking($pipes[1], true); stream_set_blocking($pipes[2], true);
    $stdout .= stream_get_contents($pipes[1]); $stderr .= stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]); $closed = proc_close($process);
    if ($status < 0) $status = $closed;
    return ['status'=>$status,'stdout'=>$stdout,'stderr'=>$stderr];
}

function odciDocker(array $arguments, float $seconds = 30.0): array
{
    return odciProcess(array_merge(['docker'], $arguments), [], $seconds);
}

function odciSqlName(string $name): string
{
    if (preg_match('/^[a-z0-9_]+$/D', $name) !== 1) odciFail('SETUP_FAILURE', 'unsafe SQL name');
    return '`' . $name . '`';
}

function odciConnect(int $port, string $user, string $password, ?string $database = null): mysqli
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db = mysqli_init();
    if (!$db) odciFail('SETUP_FAILURE', 'mysqli initialization failed');
    $db->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
    try { @$db->real_connect('127.0.0.1', $user, $password, $database, $port); $db->set_charset('utf8mb4'); return $db; }
    catch (Throwable $e) { odciFail('SETUP_FAILURE', 'private MariaDB connection failed'); }
}

function odciWait(int $port): mysqli
{
    $deadline = hrtime(true) + 90000000000;
    do { try { return odciConnect($port, 'root', ODCI_PASSWORD); } catch (TestFailure) { usleep(100000); } } while (hrtime(true) < $deadline);
    odciFail('SETUP_FAILURE', 'private MariaDB readiness timeout');
}

function odciSnapshot(mysqli $db, string $prefix): array
{
    $out = [];
    foreach (['fm2_installation_cases','fm2_pilot_generation_sentinel','fm2_pilot_object_details','fm2_pilot_object_detail_quarantine','ambient_sql_decoy'] as $suffix) {
        $table = $prefix . $suffix;
        $create = $db->query('SHOW CREATE TABLE ' . odciSqlName($table))->fetch_row()[1];
        $rows = $db->query('SELECT * FROM ' . odciSqlName($table))->fetch_all(MYSQLI_ASSOC);
        usort($rows, static fn(array $a,array $b):int => strcmp(json_encode($a, JSON_THROW_ON_ERROR), json_encode($b, JSON_THROW_ON_ERROR)));
        $out[$suffix] = ['ddl'=>$create,'rows'=>$rows];
    }
    return $out;
}

function odciExpectedPayload(int $objectId = 451301, string $floors = '12'): string
{
    $fields = [
        'floors'=>[$floors,$floors,101,1,null,null], 'weight'=>['1000','1000',102,1,null,null],
        'speed'=>['1.6','1.6',103,1,null,null], 'pittype'=>['7','Глухая',104,4,'fm_fields_values','7'],
        'pitmaterial'=>['9','Железобетон',105,4,'fm_fields_values','9'], 'paired'=>['0','0',106,1,null,null],
    ];
    $material = ['schemaVersion'=>'technical-object-detail-v1','objectId'=>$objectId,'fields'=>[]];
    foreach ($fields as $name=>$v) $material['fields'][$name] = ['raw'=>$v[0],'display'=>$v[1],'provenance'=>[
        'sourceTable'=>'fm_maintable','sourceColumn'=>$name,'fieldId'=>$v[2],'fieldType'=>$v[3],
        'dictionaryTable'=>$v[4],'dictionaryId'=>$v[5],
    ]];
    $hash = hash('sha256', json_encode($material, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
    return json_encode($material + ['contentSha256'=>$hash,'capturedAt'=>ODCI_CAPTURE_FIRST], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
}

function odciImporter(string $manifest, int $port, string $sourceDb, string $targetUser, string $sourceUser, string $capture, bool $apply, ?int $sourcePort = null): array
{
    $argv = [PHP_BINARY, dirname(__DIR__, 2) . '/rapid-pilot/import-production-object-details.php', '--captured-at=' . $capture, '--page-size=1'];
    if ($apply) $argv[] = '--apply';
    $env = getenv(); if (!is_array($env)) $env = $_ENV;
    $env += ['PATH'=>(string)getenv('PATH')];
    foreach (['FMONITOR_DB_HOST'=>'127.0.0.1','FMONITOR_DB_PORT'=>(string)$port,'FMONITOR_DB_NAME'=>'fmonitor2_demo',
        'FMONITOR_DB_USER'=>$targetUser,'FMONITOR_DB_PASSWORD'=>ODCI_PASSWORD,'FMONITOR_SOURCE_HOST'=>'127.0.0.1',
        'FMONITOR_SOURCE_PORT'=>(string)($sourcePort ?? $port),'FMONITOR_SOURCE_NAME'=>$sourceDb,'FMONITOR_SOURCE_USER'=>$sourceUser,
        'FMONITOR_SOURCE_PASSWORD'=>ODCI_PASSWORD,'FMONITOR_PILOT_ACTIVE_MANIFEST'=>$manifest,'FMONITOR_LOCAL_PILOT_ACK'=>'local-pilot-only'] as $key=>$value) $env[$key]=$value;
    return odciProcess($argv, $env, 20.0) + ['argv'=>$argv];
}

function odciFamilyState(mysqli $db, string $prefix): array
{
    $state=[];
    foreach(['fm2_pilot_object_details','fm2_pilot_object_detail_quarantine'] as $suffix){$table=$prefix.$suffix;$q=$db->prepare('SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA,COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION');$q->bind_param('s',$table);$q->execute();$columns=$q->get_result()->fetch_all(MYSQLI_ASSOC);$rows=$columns===[]?[]:$db->query('SELECT * FROM '.odciSqlName($table).' ORDER BY object_id')->fetch_all(MYSQLI_ASSOC);$state[$suffix]=['columns'=>$columns,'rows'=>$rows];}
    return $state;
}

function odciSchemaRefusal(mysqli $target,string $prefix,string $manifest,int $port,string $sourceDb,string $targetUser,string $sourceUser,string $member,bool $drift,bool $apply): void
{
    $table=$prefix.$member;
    if($drift)$target->query('ALTER TABLE '.odciSqlName($table).' MODIFY captured_at VARCHAR(41) NOT NULL');else$target->query('DROP TABLE '.odciSqlName($table));
    $before=odciFamilyState($target,$prefix);
    $listener=stream_socket_server('tcp://127.0.0.1:0',$errno,$error);if(!is_resource($listener))odciFail('SETUP_FAILURE','source listener unavailable');stream_set_blocking($listener,false);
    $address=stream_socket_get_name($listener,false);if(!is_string($address)||preg_match('/:(\d+)$/',$address,$m)!==1)odciFail('SETUP_FAILURE','listener endpoint unavailable');$listenerPort=(int)$m[1];
    $probe=stream_socket_client('tcp://127.0.0.1:'.$listenerPort,$errno,$error,2);$accepted=null;$deadline=hrtime(true)+2000000000;do{$accepted=@stream_socket_accept($listener,0);if(is_resource($accepted))break;usleep(10000);}while(hrtime(true)<$deadline);
    if(!is_resource($probe)||!is_resource($accepted))odciFail('SETUP_FAILURE','positive listener control failed');fclose($probe);fclose($accepted);
    $result=odciImporter($manifest,$port,$sourceDb,$targetUser,$sourceUser,ODCI_CAPTURE_REPEAT,$apply,$listenerPort);
    odciAssertResult($result,2,"{\"ok\":false,\"reason\":\"OBJECT_DETAIL_SCHEMA_REQUIRED\"}\n",'');
    $unexpected=@stream_socket_accept($listener,0);if(is_resource($unexpected)){fclose($unexpected);odciFail('REGRESSION_FAILURE','schema refusal connected to source');}fclose($listener);
    assertSameValue($before,odciFamilyState($target,$prefix),'schema refusal preserves exact family state');
    if($drift)$target->query('ALTER TABLE '.odciSqlName($table).' MODIFY captured_at VARCHAR(40) NOT NULL');else ObjectDetailSnapshotSchemaMigration::apply($target,$prefix);
}

function odciAssertResult(array $actual, int $status, string $stdout, string $stderr = ''): void
{
    assertSameValue($status, $actual['status'], 'real importer exit status');
    assertSameValue($stdout, $actual['stdout'], 'real importer stdout');
    assertSameValue($stderr, $actual['stderr'], 'real importer stderr');
}

function odciRun(string $token, string $artifactRoot): array
{
    if (preg_match('/^[a-f0-9]{12}$/D', $token) !== 1) odciFail('SETUP_FAILURE', 'invalid run token');
    $root = realpath($artifactRoot); $repo = realpath(dirname(__DIR__, 2));
    if ($root === false || $repo === false || is_link($artifactRoot) || !str_starts_with($root, $repo . DIRECTORY_SEPARATOR)) odciFail('SETUP_FAILURE', 'artifact root is not an exact repository-owned directory');
    $child = $root . '/object-detail-' . $token; $container = 'fm2-odci-' . $token;
    if (@lstat($child) !== false) odciFail('SETUP_FAILURE', 'artifact namespace occupied');
    $occupied = odciDocker(['container','inspect',$container]);
    if ($occupied['status'] === 0) odciFail('SETUP_FAILURE', 'container namespace occupied');
    $inspect = odciDocker(['image','inspect','--format','{{.Id}}',ODCI_IMAGE]);
    if ($inspect['status'] !== 0 || trim($inspect['stdout']) !== ODCI_IMAGE_ID) odciFail('SETUP_FAILURE', 'immutable MariaDB image is unavailable');
    $created = false; $db = null;
    try {
        mkdir($child, 0700); chmod($child, 0700);
        $create = odciDocker(['create','--name',$container,'--label','fmonitor2.object-detail-token='.$token,'-e','MARIADB_ROOT_PASSWORD='.ODCI_PASSWORD,'-p','127.0.0.1::3306',ODCI_IMAGE_ID]);
        if ($create['status'] !== 0) odciFail('SETUP_FAILURE', 'private MariaDB container create failed'); $containerId = trim($create['stdout']); $created = true;
        $start = odciDocker(['start',$container]); if ($start['status'] !== 0) odciFail('SETUP_FAILURE', 'private MariaDB start failed');
        $portResult = odciDocker(['port',$container,'3306/tcp']);
        if ($portResult['status'] !== 0 || preg_match('/127\.0\.0\.1:(\d+)/', $portResult['stdout'], $m) !== 1) odciFail('SETUP_FAILURE', 'private MariaDB endpoint unavailable');
        $port = (int)$m[1]; $identity = odciDocker(['inspect','--format','{{.Id}} {{index .Config.Labels "fmonitor2.object-detail-token"}}',$container]);
        if ($identity['status'] !== 0 || trim($identity['stdout']) !== $containerId . ' ' . $token) odciFail('SETUP_FAILURE', 'container identity proof failed');
        $db = odciWait($port); $sourceDb = 'fm2_odci_' . $token; $prefix = 'odci_' . $token . '_';
        $targetUser = 'odcit_' . $token; $sourceUser = 'odcis_' . $token;
        $db->query('CREATE DATABASE fmonitor2_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $db->query('CREATE DATABASE ' . odciSqlName($sourceDb) . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $target = odciConnect($port,'root',ODCI_PASSWORD,'fmonitor2_demo'); $source = odciConnect($port,'root',ODCI_PASSWORD,$sourceDb);
        $target->query('CREATE TABLE '.odciSqlName($prefix.'fm2_installation_cases').'(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,legacy_installation_object_id BIGINT UNSIGNED NOT NULL UNIQUE) ENGINE=InnoDB');
        $target->query('INSERT INTO '.odciSqlName($prefix.'fm2_installation_cases').'(legacy_installation_object_id) VALUES(451301),(451302)');
        $target->query('CREATE TABLE '.odciSqlName($prefix.'fm2_pilot_generation_sentinel').'(singleton_id TINYINT UNSIGNED PRIMARY KEY,generation INT UNSIGNED NOT NULL,fingerprint CHAR(64) NOT NULL,manifest_nonce CHAR(64) NOT NULL) ENGINE=InnoDB');
        $target->query("INSERT INTO ".odciSqlName($prefix.'fm2_pilot_generation_sentinel')." VALUES(1,1,'".str_repeat('a',64)."','".str_repeat('b',64)."')");
        $target->query('CREATE TABLE '.odciSqlName($prefix.'ambient_sql_decoy').'(decoy_key VARCHAR(20) PRIMARY KEY,decoy_value VARCHAR(80) NOT NULL) ENGINE=InnoDB');
        $target->query("INSERT INTO ".odciSqlName($prefix.'ambient_sql_decoy')." VALUES('fixed','OBJECT_DETAIL_SQL_DECOY')");
        ObjectDetailSnapshotSchemaMigration::apply($target, $prefix);
        $source->query('CREATE TABLE fm_fields(id INT PRIMARY KEY,sysname VARCHAR(80),name VARCHAR(80),type INT) ENGINE=InnoDB');
        $source->query('CREATE TABLE fm_view_fields(fields_id INT,views_id INT,status INT,showname VARCHAR(80)) ENGINE=InnoDB');
        $source->query('CREATE TABLE fm_fields_values(field_id INT,id INT,name VARCHAR(80)) ENGINE=InnoDB');
        $source->query('CREATE TABLE fm_maintable(id BIGINT PRIMARY KEY,floors VARCHAR(40),weight VARCHAR(40),speed VARCHAR(40),pittype VARCHAR(40),pitmaterial VARCHAR(40),paired VARCHAR(40)) ENGINE=InnoDB');
        $source->query("INSERT INTO fm_fields VALUES(101,'floors','Floors',1),(102,'weight','Weight',1),(103,'speed','Speed',1),(104,'pittype','Pit type',4),(105,'pitmaterial','Pit material',4),(106,'paired','Paired',1)");
        $source->query("INSERT INTO fm_fields_values VALUES(104,7,'Глухая'),(105,9,'Железобетон')");
        $source->query("INSERT INTO fm_maintable VALUES(451301,' 12 ',' 1000 ',' 1.6 ',' 7 ',' 9 ',' 0 ')");
        foreach ([$targetUser,$sourceUser] as $user) $db->query("CREATE USER '$user'@'%' IDENTIFIED BY '".ODCI_PASSWORD."'");
        foreach ([$prefix.'fm2_installation_cases',$prefix.'fm2_pilot_generation_sentinel',$prefix.'fm2_pilot_object_details',$prefix.'fm2_pilot_object_detail_quarantine',$prefix.'ambient_sql_decoy'] as $table) $db->query('GRANT SELECT ON fmonitor2_demo.'.odciSqlName($table)." TO '$targetUser'@'%'");
        foreach ([$prefix.'fm2_pilot_object_details',$prefix.'fm2_pilot_object_detail_quarantine'] as $table) $db->query('GRANT INSERT ON fmonitor2_demo.'.odciSqlName($table)." TO '$targetUser'@'%'");
        foreach (['fm_fields','fm_view_fields','fm_fields_values','fm_maintable'] as $table) $db->query('GRANT SELECT ON '.odciSqlName($sourceDb).'.'.odciSqlName($table)." TO '$sourceUser'@'%'");
        $db->query('FLUSH PRIVILEGES');
        $targetChild = odciConnect($port,$targetUser,ODCI_PASSWORD,'fmonitor2_demo'); $sourceChild = odciConnect($port,$sourceUser,ODCI_PASSWORD,$sourceDb);
        assertSameValue($targetUser.'@%',(string)$targetChild->query('SELECT CURRENT_USER()')->fetch_column(),'target least-privilege identity');
        assertSameValue($sourceUser.'@%',(string)$sourceChild->query('SELECT CURRENT_USER()')->fetch_column(),'source least-privilege identity');
        $targetGrants = array_column($targetChild->query('SHOW GRANTS')->fetch_all(MYSQLI_NUM),0); $sourceGrants = array_column($sourceChild->query('SHOW GRANTS')->fetch_all(MYSQLI_NUM),0);
        foreach (array_merge($targetGrants,$sourceGrants) as $grant) assertSameValue(false,preg_match('/\b(?:CREATE|ALTER|DROP|UPDATE|DELETE)\b/i',$grant)===1,'runtime principals have no DDL/update/delete grants');
        $hostname = (string)$target->query('SELECT @@hostname')->fetch_column();
        $manifest = ['generation'=>1,'fingerprint'=>str_repeat('a',64),'manifestNonce'=>str_repeat('b',64),'processPrefix'=>$prefix,'dbEndpoint'=>['host'=>'127.0.0.1','port'=>$port,'name'=>'fmonitor2_demo'],'dbServerIdentity'=>$hostname];
        $manifestPath = $child.'/manifest.json'; $bytes = json_encode($manifest,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        if (file_put_contents($manifestPath,$bytes,LOCK_EX)!==strlen($bytes) || !chmod($manifestPath,0600)) odciFail('SETUP_FAILURE','manifest construction failed');
        assertSameValue(['manifest.json'],array_values(array_diff(scandir($child) ?: [],['.','..'])),'manifest is the only run artifact');
        $before = odciSnapshot($target,$prefix);
        $clean = odciImporter($manifestPath,$port,$sourceDb,$targetUser,$sourceUser,ODCI_CAPTURE_FIRST,true);
        if ($clean['status'] !== 0 && preg_match('/CREATE command denied|command denied.*CREATE/i',$clean['stderr']) === 1) {
            odciFail('REGRESSION_FAILURE','real importer attempted runtime CREATE on the exact precreated v12 family under the verified DDL-denied principal');
        }
        odciAssertResult($clean,0,"{\"mode\":\"apply\",\"activeCases\":2,\"sourceRows\":1,\"missingSource\":1,\"schemaVersion\":\"technical-object-detail-v1\",\"created\":1,\"alreadyPresent\":0,\"quarantineCreated\":1,\"quarantinePresent\":0}\n");
        $details = $target->query('SELECT * FROM '.odciSqlName($prefix.'fm2_pilot_object_details').' ORDER BY object_id')->fetch_all(MYSQLI_ASSOC);
        $quarantine = $target->query('SELECT * FROM '.odciSqlName($prefix.'fm2_pilot_object_detail_quarantine').' ORDER BY object_id')->fetch_all(MYSQLI_ASSOC);
        assertSameValue(1,count($details),'one exact detail row'); assertSameValue(1,count($quarantine),'one exact quarantine row');
        assertSameValue(ODCI_EXPECTED_HASH,$details[0]['content_sha256'],'fixed detail hash'); assertSameValue(odciExpectedPayload(),$details[0]['payload_json'],'fixed six-field payload');
        assertSameValue(ODCI_MISSING_HASH,$quarantine[0]['content_sha256'],'fixed missing-source hash');
        assertSameValue(ODCI_CAPTURE_FIRST,$details[0]['captured_at'],'first detail capture'); assertSameValue(ODCI_CAPTURE_FIRST,$quarantine[0]['captured_at'],'first quarantine capture');
        $accepted = odciSnapshot($target,$prefix);
        $repeat = odciImporter($manifestPath,$port,$sourceDb,$targetUser,$sourceUser,ODCI_CAPTURE_REPEAT,true);
        odciAssertResult($repeat,0,"{\"mode\":\"apply\",\"activeCases\":2,\"sourceRows\":1,\"missingSource\":1,\"schemaVersion\":\"technical-object-detail-v1\",\"created\":0,\"alreadyPresent\":1,\"quarantineCreated\":0,\"quarantinePresent\":1}\n");
        assertSameValue($accepted,odciSnapshot($target,$prefix),'serial replay preserves every stored byte');
        $dry = odciImporter($manifestPath,$port,$sourceDb,$targetUser,$sourceUser,ODCI_CAPTURE_FIRST,false);
        odciAssertResult($dry,0,"{\"mode\":\"dry-run\",\"activeCases\":2,\"sourceRows\":1,\"missingSource\":1,\"schemaVersion\":\"technical-object-detail-v1\",\"writes\":0}\n");
        assertSameValue($accepted,odciSnapshot($target,$prefix),'dry-run preserves schema and rows');

        $target->query('INSERT INTO '.odciSqlName($prefix.'fm2_installation_cases').'(legacy_installation_object_id) VALUES(451300)');
        $source->query("INSERT INTO fm_maintable VALUES(451300,' 12 ',' 1000 ',' 1.6 ',' 7 ',' 9 ',' 0 ')");
        $source->query("UPDATE fm_maintable SET floors=' 13 ' WHERE id=451301");
        $conflictBefore=odciSnapshot($target,$prefix);$conflict=odciImporter($manifestPath,$port,$sourceDb,$targetUser,$sourceUser,ODCI_CAPTURE_REPEAT,true);
        assertSameValue(true,$conflict['status']!==0,'changed detail must fail');assertSameValue(true,str_contains($conflict['stderr'],'DETAIL_PROJECTION_CONFLICT'),'changed detail stable category');
        assertSameValue($conflictBefore,odciSnapshot($target,$prefix),'detail conflict rolls back whole target batch');
        $target->query('DELETE FROM '.odciSqlName($prefix.'fm2_installation_cases').' WHERE legacy_installation_object_id=451300');
        $source->query('DELETE FROM fm_maintable WHERE id=451300');$source->query("UPDATE fm_maintable SET floors=' 12 ' WHERE id=451301");

        $rejectionBefore=odciSnapshot($target,$prefix);$source->query("DELETE FROM fm_fields WHERE sysname='paired'");
        $metadata=odciImporter($manifestPath,$port,$sourceDb,$targetUser,$sourceUser,ODCI_CAPTURE_REPEAT,true);
        assertSameValue(true,$metadata['status']!==0,'incomplete metadata must fail');assertSameValue(true,str_contains($metadata['stderr'],'SOURCE_METADATA_INCOMPLETE'),'metadata stable category');
        assertSameValue($rejectionBefore,odciSnapshot($target,$prefix),'metadata rejection occurs before target DML');
        $source->query("INSERT INTO fm_fields VALUES(106,'paired','Paired',1)");$source->query("UPDATE fm_maintable SET pittype=' 999 ' WHERE id=451301");
        $dictionary=odciImporter($manifestPath,$port,$sourceDb,$targetUser,$sourceUser,ODCI_CAPTURE_REPEAT,true);
        assertSameValue(true,$dictionary['status']!==0,'unknown dictionary must fail');assertSameValue(true,str_contains($dictionary['stderr'],'SOURCE_DICTIONARY_VALUE_UNKNOWN'),'dictionary stable category');
        assertSameValue($rejectionBefore,odciSnapshot($target,$prefix),'dictionary rejection occurs before target DML');
        $source->query("UPDATE fm_maintable SET pittype=' 7 ' WHERE id=451301");
        foreach ([false,true] as $applyMode) {
            foreach ([['fm2_pilot_object_details',false],['fm2_pilot_object_detail_quarantine',false],['fm2_pilot_object_details',true],['fm2_pilot_object_detail_quarantine',true]] as [$member,$drift]) {
                odciSchemaRefusal($target,$prefix,$manifestPath,$port,$sourceDb,$targetUser,$sourceUser,$member,$drift,$applyMode);
            }
        }
        return [
            'clean'=>'OBJECT_DETAIL_IMPORT clean details=1 quarantine=1 fields=6 hashes=exact',
            'replay'=>'OBJECT_DETAIL_IMPORT replay details_present=1 quarantine_present=1 mutations=0',
            'conflict'=>'OBJECT_DETAIL_IMPORT detail-conflict category=DETAIL_PROJECTION_CONFLICT mutations=0',
            'rejections'=>'OBJECT_DETAIL_IMPORT source-rejections metadata=SOURCE_METADATA_INCOMPLETE dictionary=SOURCE_DICTIONARY_VALUE_UNKNOWN mutations=0',
            'schema'=>'OBJECT_DETAIL_IMPORT schema-precondition modes=2 cases=4 source_connections=0 ddl_privileges=0 dry_run_writes=0',
        ];
    } finally {
        if ($db instanceof mysqli) $db->close();
        if ($created) {
            $proof = odciDocker(['inspect','--format','{{index .Config.Labels "fmonitor2.object-detail-token"}}',$container]);
            if ($proof['status'] !== 0 || trim($proof['stdout']) !== $token) odciFail('SETUP_FAILURE','cleanup ownership proof failed');
            $remove = odciDocker(['rm','-f',$container]); if ($remove['status'] !== 0) odciFail('SETUP_FAILURE','owned container cleanup failed');
            if (odciDocker(['container','inspect',$container])['status'] === 0) odciFail('SETUP_FAILURE','owned container survived cleanup');
        }
        if (is_file($child.'/manifest.json')) unlink($child.'/manifest.json');
        if (is_dir($child) && !rmdir($child)) odciFail('SETUP_FAILURE','owned artifact cleanup failed');
    }
}

$suppliedToken=getenv('FMONITOR_OBJECT_DETAIL_VERIFY_RUN_TOKEN');
$suppliedRoot=getenv('FMONITOR_OBJECT_DETAIL_VERIFY_ARTIFACT_ROOT');
if ($suppliedToken !== false || $suppliedRoot !== false) {
    try {
        if (!is_string($suppliedToken)||!is_string($suppliedRoot)||$suppliedToken===''||$suppliedRoot==='') odciFail('SETUP_FAILURE','worker requires exactly one token and artifact root');
        foreach(odciRun($suppliedToken,$suppliedRoot) as $line) echo $line,"\n";
        exit(0);
    } catch(Throwable $e) {
        fwrite(STDERR,$e->getMessage()."\n");
        exit(str_starts_with($e->getMessage(),'SETUP_FAILURE:')?2:1);
    }
}

$spec = dirname(__DIR__,2).'/specs/CHARACTERIZE-OBJECT-DETAIL-IMPORT-001.md';
$artifactRoot = dirname(__DIR__,2).'/.test-artifacts/object-detail-import';
$failure = null; $exit = 0;
try {
    assertSameValue(ODCI_SPEC_SHA256,hash_file('sha256',$spec),'approved spec bytes remain pinned');
    if (!is_dir(dirname($artifactRoot)) && !mkdir(dirname($artifactRoot),0700)) odciFail('SETUP_FAILURE','artifact parent unavailable');
    if (@lstat($artifactRoot)!==false) odciFail('SETUP_FAILURE','exact artifact root collision');
    if (!mkdir($artifactRoot,0700)) odciFail('SETUP_FAILURE','artifact root unavailable'); chmod($artifactRoot,0700);
    file_put_contents($artifactRoot.'/ambient-decoy.txt',"OBJECT_DETAIL_AMBIENT_DECOY\n",LOCK_EX);
    $decoyHash=hash_file('sha256',$artifactRoot.'/ambient-decoy.txt'); $results=[];
    foreach ([bin2hex(random_bytes(6)),bin2hex(random_bytes(6))] as $token) {
        $environment=getenv();if(!is_array($environment))$environment=$_ENV;
        $environment['FMONITOR_OBJECT_DETAIL_VERIFY_RUN_TOKEN']=$token;
        $environment['FMONITOR_OBJECT_DETAIL_VERIFY_ARTIFACT_ROOT']=$artifactRoot;
        $run=odciProcess([PHP_BINARY,__FILE__],$environment,300.0);
        if($run['status']!==0)throw new TestFailure(trim($run['stderr'])!==''?trim($run['stderr']):'REGRESSION_FAILURE: per-token verifier failed without category');
        assertSameValue('', $run['stderr'], 'per-token normalized stderr is empty');
        $results[]=$run['stdout'];
        assertSameValue($decoyHash,hash_file('sha256',$artifactRoot.'/ambient-decoy.txt'),'ambient artifact decoy survives run');
    }
    assertSameValue($results[0],$results[1],'two normalized runs are deterministic');
    echo $results[0];
    echo "CHARACTERIZATION_OK CHARACTERIZE-OBJECT-DETAIL-IMPORT-001\n";
} catch (Throwable $e) { $failure=$e; $exit=str_starts_with($e->getMessage(),'SETUP_FAILURE:')?2:1; }
finally {
    if (is_file($artifactRoot.'/ambient-decoy.txt')) unlink($artifactRoot.'/ambient-decoy.txt');
    if (is_dir($artifactRoot)) rmdir($artifactRoot);
}
if ($failure) { fwrite(STDERR,$failure->getMessage()."\n"); exit($exit); }

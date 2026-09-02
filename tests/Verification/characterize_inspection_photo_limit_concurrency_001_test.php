<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

/** CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001 v0.1, Gate 2 RED. */
const CIPLC_SPEC_SHA256 = 'e9481cf5239c407c52383a91289c4d17779ef32b6dd3da82d1aff9a1c6dfd820';
const CIPLC_TIMEOUT_SECONDS = 15.0;
const CIPLC_CONTROLLED_TIMEOUT_SECONDS = 3.0;

function ciplcConfig(): array
{
    $config = [];
    foreach (['HOST'=>'127.0.0.1','PORT'=>'23306','NAME'=>'fmonitor2_test','USER'=>'fmonitor2_test','PASSWORD'=>'fmonitor2_test_local'] as $suffix=>$default) {
        $verify = getenv("FMONITOR_VERIFY_DB_$suffix");
        $test = getenv("FMONITOR_TEST_DB_$suffix");
        $config[$suffix] = $verify !== false && $verify !== '' ? $verify : ($test !== false && $test !== '' ? $test : $default);
    }
    return $config;
}

function ciplcDb(array $config): mysqli
{
    try {
        $db = new mysqli($config['HOST'], $config['USER'], $config['PASSWORD'], $config['NAME'], (int)$config['PORT']);
        $db->set_charset('utf8mb4');
        return $db;
    } catch (mysqli_sql_exception $e) {
        throw new TestFailure('SETUP_FAILURE: disposable verification database is unavailable: '.$e->getMessage());
    }
}

function ciplcToken(string $token): string
{
    if (preg_match('/\A[a-f0-9]{12}\z/D', $token) !== 1) {
        throw new TestFailure('SETUP_FAILURE: unsafe photo-limit verifier run token');
    }
    return $token;
}

/** @return array{status:int,stdout:string,stderr:string,timed_out:bool,pid:int,process_group_id:?int} */
function ciplcRun(string $root, array $environment, float $timeoutSeconds = CIPLC_TIMEOUT_SECONDS): array
{
    $process = proc_open([PHP_BINARY,'rapid-pilot/verify-checklist-photo-limit-concurrency.php'], [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes, $root, $environment);
    if (!is_resource($process)) {
        throw new TestFailure('SETUP_FAILURE: photo-limit verifier process did not start');
    }
    fclose($pipes[0]);
    $initialStatus = proc_get_status($process);
    $pid = (int)($initialStatus['pid'] ?? 0);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $stdout = $stderr = '';
    $deadline = microtime(true) + $timeoutSeconds;
    $timedOut = false;
    $processGroupId = null;
    while (true) {
        $status = proc_get_status($process);
        $stdout .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);
        if (!$status['running']) {
            break;
        }
        $remaining = $deadline - microtime(true);
        if ($remaining <= 0) {
            $timedOut = true;
            if ($pid > 0 && function_exists('posix_getpgid')) {
                $group = @posix_getpgid($pid);
                $processGroupId = is_int($group) ? $group : null;
            }
            proc_terminate($process, 15);
            if ($processGroupId === $pid && function_exists('posix_kill')) {
                @posix_kill(-$processGroupId, 15);
            }
            usleep(100000);
            if (proc_get_status($process)['running']) {
                proc_terminate($process, 9);
                if ($processGroupId === $pid && function_exists('posix_kill')) {
                    @posix_kill(-$processGroupId, 9);
                }
            }
            break;
        }
        $read = [$pipes[1],$pipes[2]];
        $write = $except = null;
        $wait = min(0.2, $remaining);
        @stream_select($read, $write, $except, (int)$wait, (int)($wait * 1000000));
    }
    stream_set_blocking($pipes[1], true);
    stream_set_blocking($pipes[2], true);
    $stdout .= stream_get_contents($pipes[1]);
    $stderr .= stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($process);
    return ['status'=>$timedOut ? 124 : $status,'stdout'=>$stdout,'stderr'=>$stderr,'timed_out'=>$timedOut,'pid'=>$pid,'process_group_id'=>$processGroupId];
}

function ciplcProcessExists(int $pid): bool
{
    if ($pid <= 1 || !function_exists('posix_kill')) {
        throw new TestFailure('SETUP_FAILURE: POSIX process probing is unavailable');
    }
    return @posix_kill($pid, 0);
}

/** @param list<int> $pids */
function ciplcAssertProcessesReaped(array $pids, ?int $processGroupId, string $message): void
{
    $deadline = microtime(true) + 1.0;
    do {
        $live = array_values(array_filter($pids, static fn(int $pid): bool => ciplcProcessExists($pid)));
        $groupLive = $processGroupId !== null && $processGroupId > 1 && function_exists('posix_kill') && @posix_kill(-$processGroupId, 0);
        if ($live === [] && !$groupLive) return;
        usleep(20000);
    } while (microtime(true) < $deadline);
    assertSameValue([], $live, "$message must reap every recorded process");
    assertSameValue(false, $groupLive, "$message must reap its isolated process group");
}

/** @return array{parent_pid:int,process_group_id:int,child_pids:list<int>} */
function ciplcReadTimeoutProcessEvidence(string $path): array
{
    assertSameValue(true, is_file($path), 'Controlled timeout must record process evidence outside its owned cleanup namespace');
    $bytes = file_get_contents($path);
    assertSameValue(true, is_string($bytes), 'Controlled timeout process evidence must be readable');
    unlink($path);
    $evidence = json_decode($bytes, true, 8, JSON_THROW_ON_ERROR);
    assertSameValue(true, is_array($evidence), 'Controlled timeout process evidence must be an object');
    assertSameValue(['parent_pid','process_group_id','child_pids'], array_keys($evidence), 'Controlled timeout process evidence shape must remain exact');
    assertSameValue(true, is_int($evidence['parent_pid']) && $evidence['parent_pid'] > 1, 'Controlled timeout must record its parent PID');
    assertSameValue($evidence['parent_pid'], $evidence['process_group_id'], 'Controlled timeout verifier must own an isolated process group');
    assertSameValue(2, count($evidence['child_pids']), 'Controlled timeout must record both race child PIDs');
    assertSameValue(2, count(array_unique($evidence['child_pids'])), 'Controlled timeout child PIDs must be distinct');
    foreach ($evidence['child_pids'] as $pid) assertSameValue(true, is_int($pid) && $pid > 1, 'Controlled timeout child PID must be valid');
    return $evidence;
}

function ciplcOwnedTables(mysqli $db, string $token, array &$discovered): array
{
    $prefix = 'photo_limit_'.ciplcToken($token).'_';
    $escaped = str_replace(['\\','_','%'], ['\\\\','\\_','\\%'], $prefix);
    $statement = $db->prepare("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE CONCAT(?, '%') ESCAPE '\\\\' ORDER BY TABLE_NAME");
    $statement->bind_param('s', $escaped);
    $statement->execute();
    $tables = array_column($statement->get_result()->fetch_all(MYSQLI_ASSOC), 'TABLE_NAME');
    foreach ($tables as $table) {
        if (!is_string($table) || preg_match('/\Aphoto_limit_[a-f0-9]{12}_[A-Za-z0-9_]+\z/D', $table) !== 1) {
            throw new TestFailure('REGRESSION_FAILURE: unsafe owned photo-limit table discovered');
        }
        $discovered[$table] = true;
    }
    return $tables;
}

function ciplcTableState(mysqli $db, string $table): array
{
    $quoted = '`'.str_replace('`','``',$table).'`';
    return ['definition'=>$db->query("SHOW CREATE TABLE $quoted")->fetch_row()[1], 'rows'=>$db->query("SELECT decoy_key,decoy_value FROM $quoted ORDER BY decoy_key")->fetch_all(MYSQLI_ASSOC)];
}

function ciplcArtifactState(string $root): array
{
    if (!is_dir($root)) return [];
    $state = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($iterator as $entry) {
        $relative = substr($entry->getPathname(), strlen($root)+1);
        $state[$relative] = $entry->isDir() ? ['type'=>'directory'] : ['type'=>'file','sha256'=>hash_file('sha256',$entry->getPathname()),'size'=>$entry->getSize()];
    }
    ksort($state);
    return $state;
}

function ciplcRemoveTree(string $root): void
{
    if (is_link($root) || is_file($root)) { unlink($root); return; }
    if (!is_dir($root)) return;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $entry) $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    rmdir($root);
}

function ciplcFixtures(): array
{
    return [
        'A'=>['bytes_base64'=>'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=','mime'=>'image/png','size'=>68,'sha256'=>'431ced6916a2a21a156e38701afe55bbd7f88969fbbfc56d7fe099d47f265460','operation_id'=>'0199a100-0000-4000-8000-00000000000a','filename'=>'contender-a.png','actor_id'=>'photo-limit-actor','device_time'=>'2026-08-31T10:00:00+03:00','server_time'=>'2026-08-31 07:00:01.000000','section'=>3,'base_revision'=>9],
        'B'=>['bytes_base64'=>'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl2nS8AAAAASUVORK5CYII=','mime'=>'image/png','size'=>68,'sha256'=>'24d410d14e37401e8565fae033bea64e068e1e9207db8b9c5903c53dd163f1b6','operation_id'=>'0199a100-0000-4000-8000-00000000000b','filename'=>'contender-b.png','actor_id'=>'photo-limit-actor','device_time'=>'2026-08-31T10:00:00+03:00','server_time'=>'2026-08-31 07:00:01.000000','section'=>3,'base_revision'=>9],
    ];
}

function ciplcAssertFixtures(): array
{
    $fixtures = ciplcFixtures();
    foreach ($fixtures as $label=>$fixture) {
        $bytes = base64_decode($fixture['bytes_base64'], true);
        assertSameValue(true, is_string($bytes), "Contender $label bytes must decode");
        assertSameValue($fixture['size'], strlen($bytes), "Contender $label size must remain literal");
        assertSameValue($fixture['sha256'], hash('sha256',$bytes), "Contender $label hash must remain literal");
    }
    return $fixtures;
}

function ciplcReadAudit(string $path): array
{
    assertSameValue(true, is_file($path), 'Verifier must produce test-owned audit evidence; echo-only stdout is insufficient');
    $bytes = file_get_contents($path);
    assertSameValue(true, is_string($bytes), 'Audit evidence must be readable');
    unlink($path);
    $audit = json_decode($bytes, true, 32, JSON_THROW_ON_ERROR);
    assertSameValue(true, is_array($audit), 'Audit evidence must be an object');
    return $audit;
}

function ciplcAssertAudit(array $audit, string $token, array $fixtures): void
{
    assertSameValue(1, $audit['protocol_version']??null, 'Audit protocol version must remain exact');
    assertSameValue($token, $audit['run_token']??null, 'Audit must identify its isolated run');
    assertSameValue($fixtures, $audit['fixtures']??null, 'Verifier must consume the literal test-owned fixtures unchanged');
    $race = $audit['race']??null;
    assertSameValue(true, is_array($race), 'Audit must contain race evidence');
    $pids = $race['child_pids']??[];
    $connections = $race['connection_ids']??[];
    assertSameValue(2, count($pids), 'Race must use exactly two child processes');
    assertSameValue(2, count(array_unique($pids)), 'Race child PIDs must be distinct');
    assertSameValue(false, in_array(getmypid(),$pids,true), 'Race must not run in the meta-test process');
    assertSameValue(2, count($connections), 'Race must use exactly two MariaDB connections');
    assertSameValue(2, count(array_unique($connections)), 'Race connection IDs must be distinct');
    assertSameValue(['A','B'], $race['ready_contenders']??null, 'Both contenders must reach the barrier');
    assertSameValue(true, $race['barrier_released']??null, 'Parent must release one shared barrier');
    assertSameValue(['revision'=>9,'active'=>9,'operation_rows'=>0,'photo_rows'=>9,'contender_blobs'=>0], $race['aggregate_before']??null, 'Owned SQL/blob audit must pin the exact pre-race aggregate');
    $results = $race['results']??[];
    assertSameValue(2, count($results), 'Race must audit two terminal results');
    $statuses = array_column($results,'status'); sort($statuses);
    assertSameValue(['accepted','rejected'], $statuses, 'Race must yield one accepted and one rejected result');
    $winner = ($results['A']['status']??null)==='accepted' ? 'A' : 'B';
    $loser = $winner==='A' ? 'B' : 'A';
    assertSameValue(['status'=>'accepted','revision'=>10], $results[$winner]??null, 'Winner result must be exact');
    assertSameValue(['status'=>'rejected'], $results[$loser]??null, 'Loser result must be exact');
    assertSameValue(['revision'=>10,'active'=>10,'operation_rows'=>1,'photo_rows'=>10,'contender_blobs'=>1,'operations_added'=>1,'photos_added'=>1,'blobs_added'=>1,'loser_mutations'=>0,'tenth_operation_id'=>$fixtures[$winner]['operation_id'],'tenth_sha256'=>$fixtures[$winner]['sha256'],'blob_sha256s'=>[$fixtures[$winner]['sha256']]], $race['aggregate_after']??null, 'Fresh projection and owned-state audit must prove exact aggregate mutation');
    assertSameValue(['contender'=>$winner,'operation_id'=>'0199a100-0000-4000-8000-00000000000c','content_sha256'=>$fixtures[$winner]['sha256'],'status'=>'duplicate','revision'=>10,'active'=>10,'operation_rows'=>1,'photo_rows'=>10,'contender_blobs'=>1,'operations_added'=>0,'photos_added'=>0,'blobs_added'=>0], $audit['same_content']??null, 'Same accepted bytes with a new operation id must audit as duplicate with exact unchanged aggregate state');
}

$root = dirname(__DIR__,2);
$spec = $root.'/specs/CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001.md';
$verifier = $root.'/rapid-pilot/verify-checklist-photo-limit-concurrency.php';
$artifactRoot = $root.'/.local/test-artifacts/characterize-photo-limit-'.bin2hex(random_bytes(8));
$tokens = ['first'=>bin2hex(random_bytes(6)),'second'=>bin2hex(random_bytes(6))];
$decoyTable = 'characterize_photo_limit_decoy_'.bin2hex(random_bytes(8));
$decoyBytes = "CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001\0ambient\xffdecoy";
$db = null;
$discovered = [];
$failureMessage = null;
$exitStatus = 0;

try {
    assertSameValue(true,is_file($spec),'SETUP_FAILURE: approved executable specification is absent');
    assertSameValue(CIPLC_SPEC_SHA256,hash_file('sha256',$spec),'Approved executable specification hash must remain pinned');
    assertSameValue(true,str_starts_with($artifactRoot,$root.'/.local/test-artifacts/'),'SETUP_FAILURE: artifact root must remain repository-private');
    $fixtures = ciplcAssertFixtures();
    foreach ($tokens as $token) ciplcToken($token);
    if (!mkdir($artifactRoot,0700,true) || file_put_contents($artifactRoot.'/ambient-decoy.bin',$decoyBytes,LOCK_EX)!==strlen($decoyBytes)) {
        throw new TestFailure('SETUP_FAILURE: cannot create private artifact fixture');
    }
    $db = ciplcDb(ciplcConfig());
    $db->query("CREATE TABLE `$decoyTable`(decoy_key VARCHAR(80) PRIMARY KEY,decoy_value VARBINARY(255) NOT NULL)");
    $statement = $db->prepare("INSERT INTO `$decoyTable`(decoy_key,decoy_value) VALUES(?,?)");
    $decoyKey = 'ambient-state';
    $statement->bind_param('ss',$decoyKey,$decoyBytes); $statement->execute();
    $beforeDb = ciplcTableState($db,$decoyTable);
    $beforeArtifacts = ciplcArtifactState($artifactRoot);

    $environment = getenv(); if (!is_array($environment)) $environment = $_ENV;
    foreach (ciplcConfig() as $suffix=>$value) $environment["FMONITOR_VERIFY_DB_$suffix"] = (string)$value;
    $environment['FMONITOR_PHOTO_LIMIT_VERIFY_ARTIFACT_ROOT'] = $artifactRoot;
    $environment['FMONITOR_PHOTO_LIMIT_VERIFY_FIXTURES_JSON'] = json_encode($fixtures,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);

    $runs = [];
    foreach ($tokens as $name=>$token) {
        $ownedStorage = $artifactRoot.'/photo-limit-'.$token;
        $auditPath = $artifactRoot.'/audit-'.$token.'.json';
        assertSameValue([],ciplcOwnedTables($db,$token,$discovered),'SETUP_FAILURE: owned SQL namespace must initially be empty');
        assertSameValue(false,file_exists($ownedStorage),'SETUP_FAILURE: owned storage namespace must initially be absent');
        $environment['FMONITOR_PHOTO_LIMIT_VERIFY_RUN_TOKEN'] = $token;
        $environment['FMONITOR_PHOTO_LIMIT_VERIFY_AUDIT_FILE'] = $auditPath;
        unset($environment['FMONITOR_PHOTO_LIMIT_VERIFY_TEST_FAILURE']);
        $runs[$name] = ciplcRun($root,$environment);
        $evidence = json_encode($runs[$name],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        if ($runs[$name]['timed_out']) {
            if (is_file($auditPath)) unlink($auditPath);
            $cleanupEvidence = [
                'owned_tables'=>ciplcOwnedTables($db,$token,$discovered),
                'owned_storage_exists'=>file_exists($ownedStorage),
                'ambient_sql_preserved'=>$beforeDb===ciplcTableState($db,$decoyTable),
                'ambient_storage_preserved'=>$beforeArtifacts===ciplcArtifactState($artifactRoot),
                'parent_reaped'=>!ciplcProcessExists($runs[$name]['pid']),
                'isolated_process_group_reaped'=>$runs[$name]['process_group_id']!==$runs[$name]['pid'] || !@posix_kill(-$runs[$name]['pid'],0),
            ];
            throw new TestFailure('SETUP_FAILURE: verifier exceeded the meta-test deadline; cleanup_evidence='.json_encode($cleanupEvidence,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."; process_evidence=$evidence");
        }
        assertSameValue(0,$runs[$name]['status'],"RED_ASSERTION: missing public photo-limit concurrency verifier must become a successful $name run; evidence=$evidence");
        assertSameValue('',$runs[$name]['stderr'],ucfirst($name)." verifier run must keep stderr empty; evidence=$evidence");
        ciplcAssertAudit(ciplcReadAudit($auditPath),$token,$fixtures);
        assertSameValue([],ciplcOwnedTables($db,$token,$discovered),ucfirst($name).' run must remove its exact owned SQL namespace');
        assertSameValue(false,file_exists($ownedStorage),ucfirst($name).' run must remove its exact owned storage namespace');
        assertSameValue($beforeDb,ciplcTableState($db,$decoyTable),ucfirst($name).' run must preserve ambient SQL');
        assertSameValue($beforeArtifacts,ciplcArtifactState($artifactRoot),ucfirst($name).' run must preserve ambient storage');
    }
    $expectedStdout = "PHOTO_LIMIT race accepted=1 rejected=1 revision=10 active=10 operations_added=1 photos_added=1 blobs_added=1 loser_mutations=0\n"
        ."PHOTO_LIMIT same-content-at-cap duplicate revision=10 active=10 operations_added=0 photos_added=0 blobs_added=0\n"
        ."CHARACTERIZATION_OK CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001\n";
    assertSameValue($expectedStdout,$runs['first']['stdout'],'First run must emit exact stable stdout');
    assertSameValue($runs['first']['stdout'],$runs['second']['stdout'],'Distinct-token stdout must be byte-identical');

    // Occupied SQL namespace must be rejected before mutation and must remain untouched.
    $collisionToken = bin2hex(random_bytes(6));
    $collisionTable = 'photo_limit_'.$collisionToken.'_occupied';
    $db->query("CREATE TABLE `$collisionTable`(decoy_key VARCHAR(80) PRIMARY KEY,decoy_value VARBINARY(255) NOT NULL)");
    $db->query("INSERT INTO `$collisionTable` VALUES('occupied',X'010203')");
    $collisionBefore = ciplcTableState($db,$collisionTable);
    $environment['FMONITOR_PHOTO_LIMIT_VERIFY_RUN_TOKEN'] = $collisionToken;
    $environment['FMONITOR_PHOTO_LIMIT_VERIFY_AUDIT_FILE'] = $artifactRoot.'/audit-'.$collisionToken.'.json';
    $sqlCollision = ciplcRun($root,$environment);
    assertSameValue(2,$sqlCollision['status'],'Occupied SQL namespace must be SETUP_FAILURE');
    assertSameValue('',$sqlCollision['stdout'],'Occupied SQL namespace must emit no stdout');
    assertSameValue(1,preg_match('/\ASETUP_FAILURE: [^\r\n]+\n\z/D',$sqlCollision['stderr']),'Occupied SQL namespace must emit one setup line');
    assertSameValue($collisionBefore,ciplcTableState($db,$collisionTable),'Occupied SQL namespace must remain exact');
    assertSameValue(false,file_exists($environment['FMONITOR_PHOTO_LIMIT_VERIFY_AUDIT_FILE']),'SQL collision must not write audit');
    $db->query("DROP TABLE `$collisionTable`");

    // Occupied storage namespace must be rejected before SQL mutation and remain untouched.
    $storageToken = bin2hex(random_bytes(6));
    $occupiedStorage = $artifactRoot.'/photo-limit-'.$storageToken;
    mkdir($occupiedStorage,0700); file_put_contents($occupiedStorage.'/occupied.bin',"occupied\0storage",LOCK_EX);
    $storageBefore = ciplcArtifactState($occupiedStorage);
    $environment['FMONITOR_PHOTO_LIMIT_VERIFY_RUN_TOKEN'] = $storageToken;
    $environment['FMONITOR_PHOTO_LIMIT_VERIFY_AUDIT_FILE'] = $artifactRoot.'/audit-'.$storageToken.'.json';
    $storageCollision = ciplcRun($root,$environment);
    assertSameValue(2,$storageCollision['status'],'Occupied storage namespace must be SETUP_FAILURE');
    assertSameValue('',$storageCollision['stdout'],'Occupied storage namespace must emit no stdout');
    assertSameValue(1,preg_match('/\ASETUP_FAILURE: [^\r\n]+\n\z/D',$storageCollision['stderr']),'Occupied storage namespace must emit one setup line');
    assertSameValue($storageBefore,ciplcArtifactState($occupiedStorage),'Occupied storage namespace must remain exact');
    assertSameValue([],ciplcOwnedTables($db,$storageToken,$discovered),'Storage collision must create no SQL fixture');
    ciplcRemoveTree($occupiedStorage);

    // Test-only controlled failures occur after mutation and must clean only exact owned state.
    foreach (['child_crash_after_mutation'=>2,'regression_after_mutation'=>1] as $mode=>$expectedStatus) {
        $failureToken = bin2hex(random_bytes(6));
        $environment['FMONITOR_PHOTO_LIMIT_VERIFY_RUN_TOKEN'] = $failureToken;
        $environment['FMONITOR_PHOTO_LIMIT_VERIFY_AUDIT_FILE'] = $artifactRoot.'/audit-'.$failureToken.'.json';
        $environment['FMONITOR_PHOTO_LIMIT_VERIFY_TEST_FAILURE'] = $mode;
        $failed = ciplcRun($root,$environment);
        assertSameValue(false,$failed['timed_out'],"$mode must be bounded by the verifier");
        assertSameValue($expectedStatus,$failed['status'],"$mode must retain its failure classification");
        assertSameValue('',$failed['stdout'],"$mode must emit no success stdout");
        $classification = $expectedStatus===2 ? 'SETUP_FAILURE' : 'REGRESSION_FAILURE';
        assertSameValue(1,preg_match("/\\A$classification: [^\\r\\n]+\\n\\z/D",$failed['stderr']),"$mode must emit one classified line");
        assertSameValue([],ciplcOwnedTables($db,$failureToken,$discovered),"$mode must clean SQL state");
        assertSameValue(false,file_exists($artifactRoot.'/photo-limit-'.$failureToken),"$mode must clean storage state");
        assertSameValue($beforeDb,ciplcTableState($db,$decoyTable),"$mode must preserve ambient SQL");
        if (is_file($environment['FMONITOR_PHOTO_LIMIT_VERIFY_AUDIT_FILE'])) unlink($environment['FMONITOR_PHOTO_LIMIT_VERIFY_AUDIT_FILE']);
        assertSameValue($beforeArtifacts,ciplcArtifactState($artifactRoot),"$mode must preserve ambient storage");
    }
    unset($environment['FMONITOR_PHOTO_LIMIT_VERIFY_TEST_FAILURE']);

    // A verifier-owned child timeout after fixture mutation is a bounded setup failure,
    // and its parent must reap the isolated process group before returning.
    $timeoutToken = bin2hex(random_bytes(6));
    $timeoutAuditPath = $artifactRoot.'/audit-'.$timeoutToken.'.json';
    $timeoutProcessPath = $artifactRoot.'/timeout-processes-'.$timeoutToken.'.json';
    $environment['FMONITOR_PHOTO_LIMIT_VERIFY_RUN_TOKEN'] = $timeoutToken;
    $environment['FMONITOR_PHOTO_LIMIT_VERIFY_AUDIT_FILE'] = $timeoutAuditPath;
    $environment['FMONITOR_PHOTO_LIMIT_VERIFY_TEST_FAILURE'] = 'timeout_after_mutation';
    $environment['FMONITOR_PHOTO_LIMIT_VERIFY_TEST_TIMEOUT_SECONDS'] = '0.25';
    $environment['FMONITOR_PHOTO_LIMIT_VERIFY_TEST_PROCESS_FILE'] = $timeoutProcessPath;
    $timedChild = ciplcRun($root,$environment,CIPLC_CONTROLLED_TIMEOUT_SECONDS);
    assertSameValue(false,$timedChild['timed_out'],'timeout_after_mutation must be bounded by the verifier rather than the meta-test emergency deadline');
    assertSameValue(2,$timedChild['status'],'timeout_after_mutation must be SETUP_FAILURE');
    assertSameValue('',$timedChild['stdout'],'timeout_after_mutation must emit no stdout');
    assertSameValue("SETUP_FAILURE: controlled race child timeout after mutation\n",$timedChild['stderr'],'timeout_after_mutation must emit the exact setup diagnostic');
    $timeoutProcesses = ciplcReadTimeoutProcessEvidence($timeoutProcessPath);
    assertSameValue($timedChild['pid'],$timeoutProcesses['parent_pid'],'Controlled timeout process evidence must identify the invoked verifier');
    ciplcAssertProcessesReaped(
        array_merge([$timeoutProcesses['parent_pid']],$timeoutProcesses['child_pids']),
        $timeoutProcesses['process_group_id'],
        'timeout_after_mutation'
    );
    if (is_file($timeoutAuditPath)) unlink($timeoutAuditPath);
    assertSameValue([],ciplcOwnedTables($db,$timeoutToken,$discovered),'timeout_after_mutation must clean SQL state');
    assertSameValue(false,file_exists($artifactRoot.'/photo-limit-'.$timeoutToken),'timeout_after_mutation must clean storage state');
    assertSameValue($beforeDb,ciplcTableState($db,$decoyTable),'timeout_after_mutation must preserve ambient SQL');
    assertSameValue($beforeArtifacts,ciplcArtifactState($artifactRoot),'timeout_after_mutation must preserve ambient storage');
    unset(
        $environment['FMONITOR_PHOTO_LIMIT_VERIFY_TEST_FAILURE'],
        $environment['FMONITOR_PHOTO_LIMIT_VERIFY_TEST_TIMEOUT_SECONDS'],
        $environment['FMONITOR_PHOTO_LIMIT_VERIFY_TEST_PROCESS_FILE']
    );

    $invalid = $environment; $invalid['FMONITOR_PHOTO_LIMIT_VERIFY_RUN_TOKEN'] = 'ABCDEF012345';
    $invalidRun = ciplcRun($root,$invalid);
    assertSameValue(2,$invalidRun['status'],'Invalid token must be SETUP_FAILURE');
    assertSameValue('', $invalidRun['stdout'],'Invalid token must emit no stdout');
    assertSameValue(1,preg_match('/\ASETUP_FAILURE: [^\r\n]+\n\z/D',$invalidRun['stderr']),'Invalid token must emit one setup line');
    $unsafe = $environment; $unsafe['FMONITOR_PHOTO_LIMIT_VERIFY_RUN_TOKEN']=bin2hex(random_bytes(6)); $unsafe['FMONITOR_PHOTO_LIMIT_VERIFY_ARTIFACT_ROOT']='/tmp';
    $unsafeRun = ciplcRun($root,$unsafe);
    assertSameValue(2,$unsafeRun['status'],'Unsafe root must be SETUP_FAILURE');
    assertSameValue('', $unsafeRun['stdout'],'Unsafe root must emit no stdout');
    assertSameValue(1,preg_match('/\ASETUP_FAILURE: [^\r\n]+\n\z/D',$unsafeRun['stderr']),'Unsafe root must emit one setup line');
    assertSameValue(true,is_file($verifier),'Implemented verifier must exist before Gate 4 is GREEN');
    assertSameValue($beforeDb,ciplcTableState($db,$decoyTable),'All probes must preserve ambient SQL');
    assertSameValue($beforeArtifacts,ciplcArtifactState($artifactRoot),'All probes must preserve ambient storage');
    echo "ok - CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001 oracle is deterministic and isolated\n";
} catch (TestFailure $e) {
    $failureMessage = $e->getMessage();
    $exitStatus = str_starts_with($failureMessage,'SETUP_FAILURE:') ? 2 : 1;
} catch (Throwable $e) {
    $failureMessage = 'REGRESSION_FAILURE: '.$e->getMessage(); $exitStatus = 1;
} finally {
    if ($db instanceof mysqli) {
        foreach (array_keys($discovered) as $table) if (is_string($table) && preg_match('/\Aphoto_limit_[a-f0-9]{12}_[A-Za-z0-9_]+\z/D',$table)===1) $db->query("DROP TABLE IF EXISTS `$table`");
        $db->query("DROP TABLE IF EXISTS `$decoyTable`"); $db->close();
    }
    ciplcRemoveTree($artifactRoot);
}
if ($failureMessage !== null) { fwrite(STDERR,$failureMessage."\n"); exit($exitStatus); }

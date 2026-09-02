<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

/** CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001 v0.1, Gate 2 RED. */

const CCQ_SPEC_SHA256 = 'f0dadc560979970968e265e8e26f0bfed42101a00dcaaed2f8cc7cf311c0243d';
const CCQ_TIMEOUT_SECONDS = 25.0;
const CCQ_PHASE_TIMEOUT_SECONDS = 6.0;
const CCQ_OWNED_TABLE_SUFFIXES = [
    'fm2_pilot_users', 'fm2_pilot_roles', 'fm2_pilot_role_permissions',
    'fm2_pilot_user_roles', 'fm2_installation_cases', 'fm2_process_events',
    'fm2_checklist_operations', 'fm_maintable', 'write_sentinel',
];

function ccqConfig(): array
{
    $config = [];
    foreach (['HOST'=>'127.0.0.1', 'PORT'=>'23306', 'NAME'=>'fmonitor2_test', 'USER'=>'fmonitor2_test', 'PASSWORD'=>'fmonitor2_test_local'] as $suffix=>$default) {
        $verify = getenv("FMONITOR_VERIFY_DB_$suffix");
        $test = getenv("FMONITOR_TEST_DB_$suffix");
        $config[$suffix] = is_string($verify) && $verify !== '' ? $verify : (is_string($test) && $test !== '' ? $test : $default);
    }
    return $config;
}

function ccqDb(array $config): mysqli
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $db = new mysqli($config['HOST'], $config['USER'], $config['PASSWORD'], $config['NAME'], (int)$config['PORT']);
        $db->set_charset('utf8mb4');
        return $db;
    } catch (Throwable $error) {
        throw new TestFailure('SETUP_FAILURE: disposable verification MariaDB is unavailable: ' . $error->getMessage());
    }
}

function ccqAdminDb(array $config): mysqli
{
    $user = getenv('FMONITOR_VERIFY_DB_ADMIN_USER');
    $password = getenv('FMONITOR_VERIFY_DB_ADMIN_PASSWORD');
    $admin = $config;
    $admin['USER'] = is_string($user) && $user !== '' ? $user : 'root';
    $admin['PASSWORD'] = is_string($password) && $password !== '' ? $password : 'fmonitor2_test_root_local';
    return ccqDb($admin);
}

function ccqToken(string $token): string
{
    if (preg_match('/\A[a-f0-9]{12}\z/D', $token) !== 1) {
        throw new TestFailure('SETUP_FAILURE: unsafe construction-control queue run token');
    }
    return $token;
}

function ccqPrefix(string $token): string
{
    $prefix = 'ccq_' . ccqToken($token) . '_';
    assertSameValue(true, strlen($prefix . 'fm2_pilot_checklist_operations') <= 64, 'SETUP_FAILURE: longest owned SQL name must fit MariaDB');
    return $prefix;
}

function ccqOwnedTables(mysqli $db, string $token): array
{
    $prefix = ccqPrefix($token);
    $escaped = str_replace(['\\', '_', '%'], ['\\\\', '\\_', '\\%'], $prefix);
    $query = $db->prepare("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE CONCAT(?, '%') ESCAPE '\\\\' ORDER BY TABLE_NAME");
    $query->bind_param('s', $escaped);
    $query->execute();
    $tables=array_column($query->get_result()->fetch_all(MYSQLI_ASSOC), 'TABLE_NAME');sort($tables,SORT_STRING);return$tables;
}

function ccqOwnedAccounts(mysqli $db, string $token): array
{
    $stem = 'fm2_ccq_' . ccqToken($token) . '_';
    $like = $stem . '%';
    $query = $db->prepare('SELECT USER AS runtime_user,HOST AS runtime_host FROM mysql.user WHERE USER LIKE ? ORDER BY USER,HOST');
    $query->bind_param('s', $like);
    $query->execute();
    return array_map(static fn(array$row):array=>['USER'=>(string)$row['runtime_user'],'HOST'=>(string)$row['runtime_host']],$query->get_result()->fetch_all(MYSQLI_ASSOC));
}

function ccqPerformanceSchemaPreflight(mysqli $db): void
{
    $enabled = $db->query("SELECT @@performance_schema")->fetch_row()[0] ?? null;
    if ((string)$enabled !== '1') {
        throw new TestFailure('SETUP_FAILURE: performance_schema must already be enabled');
    }
    $consumer = $db->query("SELECT ENABLED FROM performance_schema.setup_consumers WHERE NAME='events_statements_history_long'")->fetch_row()[0] ?? null;
    if ($consumer !== 'YES') {
        throw new TestFailure('SETUP_FAILURE: events_statements_history_long consumer must already be enabled');
    }
    $instrument = $db->query("SELECT COUNT(*) FROM performance_schema.setup_instruments WHERE NAME LIKE 'statement/%' AND ENABLED='YES' AND TIMED='YES'")->fetch_row()[0] ?? 0;
    if ((int)$instrument < 1) {
        throw new TestFailure('SETUP_FAILURE: timed statement instruments must already be enabled');
    }
    $capacity = $db->query('SELECT @@performance_schema_events_statements_history_long_size')->fetch_row()[0] ?? 0;
    if ((int)$capacity < 1000) {
        throw new TestFailure('SETUP_FAILURE: statement history capacity is insufficient for bounded request audit');
    }
}

function ccqTableState(mysqli $db, string $table): array
{
    return [
        'definition'=>(string)$db->query("SHOW CREATE TABLE `$table`")->fetch_row()[1],
        'rows'=>$db->query("SELECT decoy_key,decoy_value FROM `$table` ORDER BY decoy_key")->fetch_all(MYSQLI_ASSOC),
    ];
}

function ccqFixtureState(mysqli $db,array $tables): array
{
    $state=[];foreach($tables as$table){$create=(string)$db->query("SHOW CREATE TABLE `$table`")->fetch_row()[1];$rows=$db->query("SELECT * FROM `$table`")->fetch_all(MYSQLI_ASSOC);usort($rows,static fn(array$a,array$b):int=>strcmp(json_encode($a,JSON_THROW_ON_ERROR),json_encode($b,JSON_THROW_ON_ERROR)));$q=$db->prepare('SELECT ENGINE,AUTO_INCREMENT,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');$q->bind_param('s',$table);$q->execute();$meta=$q->get_result()->fetch_assoc();$state[$table]=['definition'=>$create,'rows'=>$rows,'engine'=>$meta['ENGINE']??null,'auto_increment'=>$meta['AUTO_INCREMENT']??null,'collation'=>$meta['TABLE_COLLATION']??null];}return$state;
}

function ccqTree(string $root): array
{
    if (!is_dir($root)) return [];
    $state = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($it as $entry) {
        $relative = substr($entry->getPathname(), strlen($root) + 1);
        $stat=$entry->getPathname()!==''?lstat($entry->getPathname()):false;$identity=['uid'=>$stat['uid']??null,'gid'=>$stat['gid']??null,'mode'=>is_array($stat)?($stat['mode']&07777):null,'dev'=>$stat['dev']??null,'ino'=>$stat['ino']??null,'nlink'=>$stat['nlink']??null,'atime'=>$stat['atime']??null,'mtime'=>$stat['mtime']??null,'ctime'=>$stat['ctime']??null,'size'=>$stat['size']??null];
        $state[$relative] = $entry->isDir() ? ['type'=>'directory','sha256'=>null]+$identity : ['type'=>'file','sha256'=>hash_file('sha256',$entry->getPathname())]+$identity;
    }
    ksort($state);
    return $state;
}

function ccqRemoveTree(string $root): void
{
    if (!is_dir($root)) return;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $entry) $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    rmdir($root);
}

function ccqRun(string $root, string $verifier, array $environment): array
{
    $process = proc_open(['setsid','--wait',PHP_BINARY,$verifier], [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes, $root, $environment);
    if (!is_resource($process)) throw new TestFailure('SETUP_FAILURE: test-only queue verifier did not start');
    fclose($pipes[0]); stream_set_blocking($pipes[1],false); stream_set_blocking($pipes[2],false);
    $pid = (int)(proc_get_status($process)['pid'] ?? 0); $deadline = microtime(true) + CCQ_TIMEOUT_SECONDS; $stdout=''; $stderr=''; $status=null;
    while (true) {
        $stdout .= stream_get_contents($pipes[1]); $stderr .= stream_get_contents($pipes[2]); $state = proc_get_status($process);
        if (!($state['running'] ?? false)) { $status = (int)$state['exitcode']; break; }
        if (microtime(true) >= $deadline) {
            if ($pid > 0 && function_exists('posix_kill')) @posix_kill(-$pid, SIGTERM); else proc_terminate($process,15);
            usleep(100000);
            if (proc_get_status($process)['running'] ?? false) { if ($pid > 0 && function_exists('posix_kill')) @posix_kill(-$pid,SIGKILL); else proc_terminate($process,9); }
            $status = 124; break;
        }
        usleep(20000);
    }
    stream_set_blocking($pipes[1],true); stream_set_blocking($pipes[2],true); $stdout .= stream_get_contents($pipes[1]); $stderr .= stream_get_contents($pipes[2]);
    foreach ($pipes as $pipe) if (is_resource($pipe)) fclose($pipe); $closed=proc_close($process); if ($status === null || $status < 0) $status=$closed;
    return ['status'=>$status,'stdout'=>$stdout,'stderr'=>$stderr];
}

function ccqExpectedTables(string $token): array
{
    $tables=array_map(static fn(string $suffix): string => ccqPrefix($token) . $suffix, CCQ_OWNED_TABLE_SUFFIXES);sort($tables);return $tables;
}

function ccqExpectedAccounts(string $token): array
{
    return array_map(static fn(string $slot): array => ['USER'=>'fm2_ccq_' . ccqToken($token) . '_' . $slot, 'HOST'=>'127.0.0.1'], ['a','b','s','x']);
}

function ccqThreads(mysqli $db, string $token): array
{
    $accounts = array_column(ccqExpectedAccounts($token), 'USER');
    $quoted = implode(',', array_fill(0,count($accounts),'?'));
    $query=$db->prepare("SELECT PROCESSLIST_USER,PROCESSLIST_ID,THREAD_ID FROM performance_schema.threads WHERE PROCESSLIST_USER IN ($quoted) AND PROCESSLIST_ID IS NOT NULL ORDER BY PROCESSLIST_USER");
    $query->bind_param(str_repeat('s',count($accounts)),...$accounts); $query->execute();
    return $query->get_result()->fetch_all(MYSQLI_ASSOC);
}

function ccqStatementHistory(mysqli $db, array $threadIds): array
{
    if ($threadIds===[]) return [];
    $quoted=implode(',',array_fill(0,count($threadIds),'?'));
    $query=$db->prepare("SELECT THREAD_ID,EVENT_ID,EVENT_NAME,SQL_TEXT FROM performance_schema.events_statements_history_long WHERE THREAD_ID IN ($quoted) ORDER BY THREAD_ID,EVENT_ID");
    $query->bind_param(str_repeat('i',count($threadIds)),...$threadIds); $query->execute();
    return $query->get_result()->fetch_all(MYSQLI_ASSOC);
}

function ccqGrantProfile(mysqli $db, string $token): array
{
    $out=[];
    foreach (ccqExpectedAccounts($token) as $account) {
        $rows=$db->query("SHOW GRANTS FOR `{$account['USER']}`@`{$account['HOST']}`")->fetch_all(MYSQLI_NUM);
        $out[$account['USER']]=array_column($rows,0);
    }
    return $out;
}

function ccqAssertSelectOnlyGrants(array $profiles, string $token): void
{
    assertSameValue(array_column(ccqExpectedAccounts($token),'USER'),array_keys($profiles),'Outer observer must see four exact grant profiles');
    $database=ccqConfig()['NAME'];$normalizedProfiles=[];
    foreach ($profiles as $user=>$grants) {
        $seen=[];$usage=0;
        foreach($grants as$grant){if(preg_match('/^GRANT USAGE ON \*\.\* TO /',$grant)===1){$usage++;continue;}if(preg_match('/^GRANT SELECT ON `((?:``|[^`])+)`\.`((?:``|[^`])+)` TO /',$grant,$m)===1){$grantDb=str_replace('``','`',$m[1]);$grantTable=str_replace('``','`',$m[2]);assertSameValue($database,$grantDb,"$user SELECT database must be exact configured schema");$seen[]=$grantTable;continue;}throw new TestFailure("Unexpected effective grant for $user: $grant");}
        sort($seen);assertSameValue(1,$usage,"$user must have exactly one global USAGE grant");assertSameValue(ccqExpectedTables($token),$seen,"$user must have all-and-only one SELECT grant per owned fixture table");assertSameValue(1+count(CCQ_OWNED_TABLE_SUFFIXES),count($grants),"$user must have no additional grant row");
        $normalizedProfiles[$user]=['usage'=>$usage,'database'=>$database,'tables'=>$seen];
    }
    $first=reset($normalizedProfiles);foreach($normalizedProfiles as$user=>$profile)assertSameValue($first,$profile,"$user grant profile must be identical to every other runtime account");
}

function ccqConnectionAttributes(mysqli $db,array $processIds): array
{
    if($processIds===[])return[];$marks=implode(',',array_fill(0,count($processIds),'?'));$q=$db->prepare("SELECT PROCESSLIST_ID,ATTR_NAME,ATTR_VALUE FROM performance_schema.session_connect_attrs WHERE PROCESSLIST_ID IN ($marks) AND ATTR_NAME IN ('_fm2_pid','_fm2_slot','_fm2_nonce') ORDER BY PROCESSLIST_ID,ATTR_NAME");$q->bind_param(str_repeat('i',count($processIds)),...$processIds);$q->execute();$out=[];foreach($q->get_result()->fetch_all(MYSQLI_ASSOC)as$row)$out[(int)$row['PROCESSLIST_ID']][(string)$row['ATTR_NAME']]=(string)$row['ATTR_VALUE'];return$out;
}

function ccqCreateResponseFakeFixture(mysqli $db,string $token): void
{
    foreach(ccqExpectedTables($token)as$table){if(str_ends_with($table,'fm_maintable'))$db->query("CREATE TABLE `$table`(id BIGINT PRIMARY KEY,ordadr_address VARCHAR(255) NOT NULL) ENGINE=InnoDB");else$db->query("CREATE TABLE `$table`(id BIGINT AUTO_INCREMENT PRIMARY KEY,value_text VARCHAR(255) NOT NULL) ENGINE=InnoDB");if(str_ends_with($table,'fm_maintable'))$db->query("INSERT INTO `$table` VALUES(451201,'ул. <Тестовая & 1>')");else$db->query("INSERT INTO `$table`(value_text) VALUES('response-only-fake')");}
    foreach(ccqExpectedAccounts($token)as$account){$db->query("CREATE USER `{$account['USER']}`@`{$account['HOST']}` IDENTIFIED BY 'ccq_test_pass'");foreach(ccqExpectedTables($token)as$table)$db->query("GRANT SELECT ON `".ccqConfig()['NAME']."`.`$table` TO `{$account['USER']}`@`{$account['HOST']}`");}
}

function ccqReadExchangeLog(string $path): array
{
    assertSameValue(true,is_file($path),'Outer observer must receive raw HTTP exchange log');
    $rows=[];
    foreach (file($path,FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $row=json_decode($line,true,512,JSON_THROW_ON_ERROR);
        assertSameValue(true,is_array($row),'Every exchange record must be JSON object');
        foreach (['case','nonce','method','raw_target','slot','raw_response_base64'] as $field) assertSameValue(true,isset($row[$field])&&is_string($row[$field]),"Exchange must contain $field");
        $raw=base64_decode($row['raw_response_base64'],true); assertSameValue(true,is_string($raw),'Raw response must be strict base64'); $row['raw_response']=$raw; $rows[]=$row;
    }
    $required=['unauthenticated-get','unauthenticated-head','inactive-get','inactive-head','denied-get','denied-head','allowed-get','allowed-head','pagination-page-1','pagination-page-2','failure-page-zero','failure-page-text','failure-page-range','failure-sql-denied','failure-malformed-row','failure-malformed-event','repeat-1','repeat-2','concurrent-a','concurrent-b'];
    $byCase=[];foreach($rows as$row){assertSameValue(false,isset($byCase[$row['case']]),'Each approved exchange case must occur once');$byCase[$row['case']]=$row;}
    $cases=array_keys($byCase);sort($cases);$sortedRequired=$required;sort($sortedRequired);assertSameValue($sortedRequired,$cases,'Raw log must contain exact full approved exchange matrix');
    $nonces=array_column($rows,'nonce'); assertSameValue(count($nonces),count(array_unique($nonces)),'Each real exchange must have independent nonce');
    $slots=array_values(array_unique(array_column($rows,'slot'))); sort($slots); assertSameValue(['concurrent-a','concurrent-b','serial'],$slots,'Raw log must independently contain serial and two concurrent slots');
    $parse=static function(array$row):array{$parts=preg_split("/\r?\n\r?\n/",$row['raw_response'],2);assertSameValue(2,count($parts),'Raw HTTP response must contain header/body boundary');$lines=preg_split('/\r?\n/',$parts[0]);assertSameValue(1,preg_match('#^HTTP/1\.[01] ([0-9]{3}) #',(string)array_shift($lines),$m),'Raw HTTP status line must be valid');$headers=[];foreach($lines as$line){[$name,$value]=array_pad(explode(':',$line,2),2,'');$headers[strtolower(trim($name))]=trim($value);}return['status'=>(int)$m[1],'headers'=>$headers,'body'=>$parts[1]];};
    $parsed=[];foreach($byCase as$case=>$row)$parsed[$case]=$parse($row);
    $baseHeaders=['x-content-type-options'=>'nosniff','referrer-policy'=>'no-referrer','x-frame-options'=>'DENY','permissions-policy'=>'camera=(), microphone=(), geolocation=()','cross-origin-opener-policy'=>'same-origin','cache-control'=>'no-store'];
    foreach($parsed as$case=>$response){foreach($baseHeaders as$name=>$value)assertSameValue($value,$response['headers'][$name]??null,"$case must preserve exact $name");if(($byCase[$case]['method']??'')!=='HEAD')assertSameValue((string)strlen($response['body']),$response['headers']['content-length']??null,"$case GET body length must be exact");}
    foreach(['unauthenticated'=>[401,"Authentication required.\n",25],'inactive'=>[403,"Access denied.\n",15],'denied'=>[403,"Access denied.\n",15]]as$stem=>$expected){assertSameValue($expected[0],$parsed[$stem.'-get']['status'],"$stem GET status");assertSameValue($expected[1],$parsed[$stem.'-get']['body'],"$stem GET body");assertSameValue($expected[0],$parsed[$stem.'-head']['status'],"$stem HEAD status");assertSameValue('',$parsed[$stem.'-head']['body'],"$stem HEAD body");assertSameValue((string)$expected[2],$parsed[$stem.'-head']['headers']['content-length']??null,"$stem HEAD representation length");}
    assertSameValue(200,$parsed['allowed-get']['status'],'Allowed GET status');assertSameValue(200,$parsed['allowed-head']['status'],'Allowed HEAD status');assertSameValue('',$parsed['allowed-head']['body'],'Allowed HEAD body');assertSameValue((string)strlen($parsed['allowed-get']['body']),$parsed['allowed-head']['headers']['content-length']??null,'Allowed HEAD must preserve GET representation length');
    $small=$parsed['allowed-get']['body'];preg_match_all('/data-object-id="([0-9]+)"/',$small,$ids);assertSameValue(['451201','451202','451203','451204'],$ids[1],'Small projection exact ordered identities');assertSameValue(false,str_contains($small,'451299'),'Non-working case must be absent');foreach(['ул. &lt;Тестовая &amp; 1&gt;','REG-&amp;-001','Инженер &lt;Событие&gt;','Инженер Фолбэк','Инженер не назначен']as$literal)assertSameValue(true,str_contains($small,$literal),"Projection must contain literal $literal");foreach(['451201','451202','451203','451204']as$id)assertSameValue(1,substr_count($small,'href="/pilot/construction-control/objects/'.$id.'/checklist"'),"Canonical checklist href $id must occur once");
    foreach(['pagination-page-1'=>range(452001,452050),'pagination-page-2'=>[452051]]as$case=>$expectedIds){preg_match_all('/data-object-id="([0-9]+)"/',$parsed[$case]['body'],$found);assertSameValue(array_map('strval',$expectedIds),$found[1],"$case exact identities");}assertSameValue(true,str_contains($parsed['pagination-page-1']['body'],'Показано 50 из 51'),'Page 1 footer');assertSameValue(true,str_contains($parsed['pagination-page-2']['body'],'Показано 1 из 51'),'Page 2 footer');
    foreach(['failure-page-zero','failure-page-text','failure-page-range','failure-sql-denied','failure-malformed-row','failure-malformed-event']as$case){assertSameValue(503,$parsed[$case]['status'],"$case status");assertSameValue("Service unavailable.\n",$parsed[$case]['body'],"$case body");assertSameValue('60',$parsed[$case]['headers']['retry-after']??null,"$case Retry-After");}
    foreach(['repeat-1','repeat-2','concurrent-a','concurrent-b']as$case){assertSameValue(200,$parsed[$case]['status'],"$case status");preg_match_all('/data-object-id="([0-9]+)"/',$parsed[$case]['body'],$found);assertSameValue(['451201','451202','451203','451204'],$found[1],"$case equivalent projection");}
    return $rows;
}

function ccqRunWitnessed(string $root,string $verifier,array $environment,mysqli $observer,string $token,string $artifactChild): array
{
    $process=null;$pipes=[];$pid=0;$workerPids=[];$result=null;$primary=null;
    $groups=['response-sensitivity','unauthenticated-get','unauthenticated-head','inactive-get','inactive-head','denied-get','denied-head','allowed-get','allowed-head','pagination-page-1','pagination-page-2','failure-page-zero','failure-page-text','failure-page-range','failure-sql-denied','failure-malformed-row','failure-malformed-event','repeat-1','repeat-2','concurrent'];
    try{
        $environment['FMONITOR_CONTROL_QUEUE_VERIFY_PROTOCOL_ROOT']=$artifactChild.'/protocol';
        $process=proc_open(['setsid','--wait',PHP_BINARY,$verifier],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root,$environment);if(!is_resource($process))throw new TestFailure('SETUP_FAILURE: verifier process did not start');fclose($pipes[0]);stream_set_blocking($pipes[1],false);stream_set_blocking($pipes[2],false);$pid=(int)(proc_get_status($process)['pid']??0);
        $ready=$artifactChild.'/fixture-ready';$deadline=microtime(true)+CCQ_PHASE_TIMEOUT_SECONDS;while(!is_file($ready)){if(!(proc_get_status($process)['running']??false))break;if(microtime(true)>=$deadline)break;usleep(20000);}
        $tables=ccqOwnedTables($observer,$token);$accounts=ccqOwnedAccounts($observer,$token);if(!is_file($ready)||$tables!==ccqExpectedTables($token)||$accounts!==ccqExpectedAccounts($token))throw new TestFailure('RED_ASSERTION: production HTTP verifier must expose exact positive fixture/accounts before requests; evidence='.json_encode(['tables'=>$tables,'accounts'=>$accounts,'stderr'=>stream_get_contents($pipes[2])],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        ccqAssertSelectOnlyGrants(ccqGrantProfile($observer,$token),$token);$rowsByTable=[];foreach($tables as$table)$rowsByTable[$table]=(int)$observer->query("SELECT COUNT(*) FROM `$table`")->fetch_row()[0];assertSameValue(true,min($rowsByTable)>0,'Every declared fixture table must be non-empty');
        $baseline=ccqFixtureState($observer,$tables);$ambientRoot=dirname($artifactChild);$ambientDecoy=$ambientRoot.'/ambient-decoy.bin';$ambientBefore=['sha256'=>hash_file('sha256',$ambientDecoy),'stat'=>lstat($ambientDecoy)];$session=$artifactChild.'/sessions';assertSameValue(true,is_dir($session),'Exact owned session subtree must positively exist');
        foreach($groups as$index=>$group){
            $phase=$artifactChild.'/protocol/'.sprintf('%02d',$index).'-'.$group;$readyFile=$phase.'.ready.json';$deadline=microtime(true)+CCQ_PHASE_TIMEOUT_SECONDS;while(!is_file($readyFile)){if(!(proc_get_status($process)['running']??false))break;if(microtime(true)>=$deadline)throw new TestFailure('SETUP_FAILURE: request readiness deadline exceeded for '.$group);usleep(20000);}assertSameValue(true,is_file($readyFile),'Request group must reach composition barrier: '.$group);
            $manifest=json_decode((string)file_get_contents($readyFile),true,512,JSON_THROW_ON_ERROR);$workers=$manifest['workers']??[];assertSameValue(true,is_array($workers)&&$workers!==[],'Request group must declare worker PID/slot/nonce bindings');$pids=[];foreach($workers as$worker){assertSameValue(true,is_array($worker),'Worker binding must be an object');$workerPid=$worker['pid']??null;$slot=$worker['slot']??null;$nonce=$worker['nonce']??null;assertSameValue(true,is_int($workerPid)&&$workerPid>1&&is_dir('/proc/'.$workerPid),'Worker PID must be alive at composition barrier');assertSameValue(true,is_string($slot)&&in_array($slot,['s','a','b'],true),'Worker slot must be exact');assertSameValue(1,preg_match('/\A[a-f0-9]{32}\z/D',(string)$nonce),'Worker nonce must be unpredictable 32-hex');$pids[]=$workerPid;$workerPids[$workerPid]=true;}assertSameValue(count($pids),count(array_unique($pids)),'Every request slot must declare a distinct worker PID');
            $expectedSlots=$group==='concurrent'?['a','b']:['s'];$threads=ccqThreads($observer,$token);$activeSlots=array_map(static fn(array$t):string=>substr((string)$t['PROCESSLIST_USER'],-1),$threads);sort($activeSlots);$sortedExpected=$expectedSlots;sort($sortedExpected);assertSameValue($sortedExpected,$activeSlots,'Full owned active connection population must equal exact request slots');$wanted=$threads;assertSameValue(count($expectedSlots),count($wanted),'Exact request slots must have one active connection each');
            $attrs=ccqConnectionAttributes($observer,array_map('intval',array_column($wanted,'PROCESSLIST_ID')));foreach($wanted as$thread){$processId=(int)$thread['PROCESSLIST_ID'];$slot=substr((string)$thread['PROCESSLIST_USER'],-1);$binding=array_values(array_filter($workers,static fn(array$worker):bool=>($worker['slot']??null)===$slot));assertSameValue(1,count($binding),'Each exact DB slot must bind one declared worker');assertSameValue((string)$binding[0]['pid'],$attrs[$processId]['_fm2_pid']??null,'DB connect attributes must independently bind worker PID');assertSameValue($slot,$attrs[$processId]['_fm2_slot']??null,'DB connect attributes must independently bind slot');assertSameValue($binding[0]['nonce'],$attrs[$processId]['_fm2_nonce']??null,'DB connect attributes must independently bind nonce');}
            $threadIds=array_map('intval',array_column($wanted,'THREAD_ID'));assertSameValue(count($threadIds),count(array_unique($threadIds)),'Request group thread mappings must be distinct');$bounds=[];foreach($threadIds as$threadId){$q=$observer->prepare('SELECT COALESCE(MAX(EVENT_ID),0) FROM performance_schema.events_statements_history_long WHERE THREAD_ID=?');$q->bind_param('i',$threadId);$q->execute();$bounds[$threadId]=(int)$q->get_result()->fetch_row()[0];}
            $beforeDb=ccqFixtureState($observer,$tables);$beforeFs=ccqTree($artifactChild);$beforeSession=ccqTree($session);
            $challenge=null;if($group==='response-sensitivity'){$challenge='SENSITIVITY-'.bin2hex(random_bytes(16));$legacy=ccqPrefix($token).'fm_maintable';$update=$observer->prepare("UPDATE `$legacy` SET ordadr_address=? WHERE id=451201");$update->bind_param('s',$challenge);$update->execute();assertSameValue(1,$update->affected_rows,'Outer-only unpredictable sensitivity must change one row after worker readiness');}
            file_put_contents($phase.'.dispatch',"dispatch\n",LOCK_EX);$response=$phase.'.response';$deadline=microtime(true)+CCQ_PHASE_TIMEOUT_SECONDS;while(!is_file($response)){if(microtime(true)>=$deadline)throw new TestFailure('SETUP_FAILURE: response barrier deadline exceeded for '.$group);usleep(20000);}
            $exchangeFile=$phase.'.exchange.json';assertSameValue(true,is_file($exchangeFile),'Independent router exchange record must exist before audit');$exchangeBindings=json_decode((string)file_get_contents($exchangeFile),true,512,JSON_THROW_ON_ERROR);assertSameValue(true,is_array($exchangeBindings),'Exchange bindings must be an array');foreach($workers as$worker){$matches=array_values(array_filter($exchangeBindings,static fn(array$entry):bool=>($entry['nonce']??null)===$worker['nonce']&&($entry['slot']??null)===$worker['slot']));assertSameValue(1,count($matches),'Request log must bind each worker nonce and slot exactly once');}
            if($challenge!==null){$raw=(string)file_get_contents($response);assertSameValue(true,str_contains($raw,$challenge),'Sensitivity HTTP response must contain outer-only post-readiness challenge');$legacy=ccqPrefix($token).'fm_maintable';$restore=$observer->prepare("UPDATE `$legacy` SET ordadr_address='ул. <Тестовая & 1>' WHERE id=451201");$restore->execute();assertSameValue(1,$restore->affected_rows,'Sensitivity fixture must restore once before post-group fingerprint');}
            $history=ccqStatementHistory($observer,$threadIds);foreach($threadIds as$threadId){$window=array_values(array_filter($history,static fn(array$r):bool=>(int)$r['THREAD_ID']===$threadId&&(int)$r['EVENT_ID']>$bounds[$threadId]));assertSameValue(true,$window!==[],'Every request thread must have a non-empty bounded statement window');$sql=implode("\n",array_map(static fn(array$r):string=>(string)$r['SQL_TEXT'],$window));assertSameValue(0,preg_match('/\b(?:INSERT|UPDATE|DELETE|REPLACE|CREATE|ALTER|DROP|TRUNCATE|RENAME|GRANT|REVOKE|CALL)\b/i',$sql),'Request window must contain no write/stored-program attempt');if(in_array($group,['response-sensitivity','allowed-get','allowed-head','pagination-page-1','pagination-page-2','repeat-1','repeat-2','concurrent'],true)){assertSameValue(true,str_contains($sql,ccqPrefix($token).'fm2_installation_cases'),'Allowed production window must read exact installation-case fixture');assertSameValue(true,str_contains($sql,ccqPrefix($token).'fm_maintable'),'Allowed production window must read exact legacy-object fixture');}}
            assertSameValue($beforeDb,ccqFixtureState($observer,$tables),'DB definition/rows/allocator must be exact around '.$group);assertSameValue($beforeSession,ccqTree($session),'Session identity/content must be exact around '.$group);$afterFs=ccqTree($artifactChild);foreach(array_keys($afterFs)as$key)if(str_starts_with($key,'protocol/')||$key==='request.log')unset($afterFs[$key]);foreach(array_keys($beforeFs)as$key)if(str_starts_with($key,'protocol/')||$key==='request.log')unset($beforeFs[$key]);assertSameValue($beforeFs,$afterFs,'Filesystem identity/content must be exact around '.$group);
            file_put_contents($phase.'.teardown',"teardown\n",LOCK_EX);$deadline=microtime(true)+CCQ_PHASE_TIMEOUT_SECONDS;do{$active=ccqThreads($observer,$token);$remaining=array_filter($active,static fn(array$t):bool=>in_array(substr((string)$t['PROCESSLIST_USER'],-1),$expectedSlots,true));if($remaining===[])break;if(microtime(true)>=$deadline)throw new TestFailure('SETUP_FAILURE: connection teardown deadline exceeded for '.$group);usleep(20000);}while(true);if($group!=='concurrent')assertSameValue([],array_values(array_filter(ccqThreads($observer,$token),static fn(array$t):bool=>str_ends_with((string)$t['PROCESSLIST_USER'],'_s'))),'_s must be absent before next serial group');
        }
        // Dedicated _x sensitivity group, independently audited before teardown.
        $xReady=$artifactChild.'/protocol/sensitivity-x.ready.json';$deadline=microtime(true)+CCQ_PHASE_TIMEOUT_SECONDS;while(!is_file($xReady)){if(microtime(true)>=$deadline)throw new TestFailure('SETUP_FAILURE: write sensitivity readiness deadline exceeded');usleep(20000);}$xManifest=json_decode((string)file_get_contents($xReady),true,512,JSON_THROW_ON_ERROR);$xWorker=$xManifest['worker']??null;assertSameValue(true,is_array($xWorker),'Sensitivity must declare worker PID/slot/nonce binding');assertSameValue('x',$xWorker['slot']??null,'Sensitivity worker slot must be exact');assertSameValue(true,is_int($xWorker['pid']??null)&&$xWorker['pid']>1&&is_dir('/proc/'.$xWorker['pid']),'Sensitivity worker PID must be alive');assertSameValue(1,preg_match('/\A[a-f0-9]{32}\z/D',(string)($xWorker['nonce']??'')),'Sensitivity nonce must be unpredictable 32-hex');$workerPids[$xWorker['pid']]=true;$x=ccqThreads($observer,$token);assertSameValue(['x'],array_map(static fn(array$t):string=>substr((string)$t['PROCESSLIST_USER'],-1),$x),'Full owned active connection population must contain only sensitivity slot');assertSameValue(1,count($x),'Sensitivity must expose exactly one _x thread');$xAttrs=ccqConnectionAttributes($observer,[(int)$x[0]['PROCESSLIST_ID']]);assertSameValue((string)$xWorker['pid'],$xAttrs[(int)$x[0]['PROCESSLIST_ID']]['_fm2_pid']??null,'Sensitivity DB thread must bind exact worker PID');assertSameValue('x',$xAttrs[(int)$x[0]['PROCESSLIST_ID']]['_fm2_slot']??null,'Sensitivity DB thread must bind exact slot');assertSameValue($xWorker['nonce'],$xAttrs[(int)$x[0]['PROCESSLIST_ID']]['_fm2_nonce']??null,'Sensitivity DB thread must bind exact nonce');$xId=(int)$x[0]['THREAD_ID'];$q=$observer->prepare('SELECT COALESCE(MAX(EVENT_ID),0) FROM performance_schema.events_statements_history_long WHERE THREAD_ID=?');$q->bind_param('i',$xId);$q->execute();$bound=(int)$q->get_result()->fetch_row()[0];file_put_contents($artifactChild.'/protocol/sensitivity-x.dispatch',"dispatch\n",LOCK_EX);$xResponse=$artifactChild.'/protocol/sensitivity-x.response';$deadline=microtime(true)+CCQ_PHASE_TIMEOUT_SECONDS;while(!is_file($xResponse)){if(microtime(true)>=$deadline)throw new TestFailure('SETUP_FAILURE: write sensitivity response deadline exceeded');usleep(20000);}$xHistory=array_values(array_filter(ccqStatementHistory($observer,[$xId]),static fn(array$r):bool=>(int)$r['EVENT_ID']>$bound));$xSql=implode("\n",array_map(static fn(array$r):string=>(string)$r['SQL_TEXT'],$xHistory));assertSameValue(1,preg_match('/\bINSERT\s+INTO\b/i',$xSql),'Outer observer must see denied _x INSERT in bounded window');assertSameValue($baseline,ccqFixtureState($observer,$tables),'Denied sensitivity must preserve exact fixture');file_put_contents($artifactChild.'/protocol/sensitivity-x.teardown',"teardown\n",LOCK_EX);
        $exchanges=ccqReadExchangeLog($artifactChild.'/request.log');assertSameValue($baseline,ccqFixtureState($observer,$tables),'All request groups must preserve exact fixture');assertSameValue($ambientBefore,['sha256'=>hash_file('sha256',$ambientDecoy),'stat'=>lstat($ambientDecoy)],'Ambient filesystem decoy identity/content must remain exact before cleanup');file_put_contents($artifactChild.'/cleanup-release',"cleanup\n",LOCK_EX);
        $deadline=microtime(true)+CCQ_TIMEOUT_SECONDS;while(proc_get_status($process)['running']??false){if(microtime(true)>=$deadline)throw new TestFailure('SETUP_FAILURE: verifier cleanup/exit deadline exceeded');usleep(20000);}stream_set_blocking($pipes[1],true);stream_set_blocking($pipes[2],true);$stdout=stream_get_contents($pipes[1]);$stderr=stream_get_contents($pipes[2]);$status=proc_close($process);$process=null;$result=['status'=>$status,'stdout'=>$stdout,'stderr'=>$stderr,'exchanges'=>$exchanges];
    }catch(Throwable$error){$primary=$error;}
    finally{
        $cleanupErrors=[];
        if(is_resource($process)){$deadline=microtime(true)+1.0;if(proc_get_status($process)['running']??false){if($pid>0&&function_exists('posix_kill'))@posix_kill(-$pid,SIGTERM);else proc_terminate($process,15);while((proc_get_status($process)['running']??false)&&microtime(true)<$deadline)usleep(20000);if(proc_get_status($process)['running']??false){if($pid>0&&function_exists('posix_kill'))@posix_kill(-$pid,SIGKILL);else proc_terminate($process,9);}}foreach($pipes as$pipe)if(is_resource($pipe))fclose($pipe);proc_close($process);}
        foreach(array_keys($workerPids)as$workerPid)if(is_dir('/proc/'.$workerPid)&&function_exists('posix_kill')){@posix_kill($workerPid,SIGTERM);$deadline=microtime(true)+1.0;while(is_dir('/proc/'.$workerPid)&&microtime(true)<$deadline)usleep(20000);if(is_dir('/proc/'.$workerPid))@posix_kill($workerPid,SIGKILL);}
        $ownedTables=ccqOwnedTables($observer,$token);$expectedTables=ccqExpectedTables($token);$ownedAccounts=ccqOwnedAccounts($observer,$token);$expectedAccounts=ccqExpectedAccounts($token);$namespaceAbsent=$ownedTables===[]&&$ownedAccounts===[];$namespaceComplete=$ownedTables===$expectedTables&&$ownedAccounts===$expectedAccounts;$ownershipValid=$namespaceAbsent||$namespaceComplete;
        if($ownershipValid)foreach($ownedTables as$table){$create=(string)$observer->query("SHOW CREATE TABLE `$table`")->fetch_row()[1];if(!str_contains($create,"CREATE TABLE `$table`")||!str_contains($create,'ENGINE=InnoDB')){$ownershipValid=false;$cleanupErrors[]='owned table schema marker mismatch: '.$table;}}
        if($ownershipValid&&$namespaceComplete)try{ccqAssertSelectOnlyGrants(ccqGrantProfile($observer,$token),$token);}catch(Throwable$error){$ownershipValid=false;$cleanupErrors[]='owned account grant validation failed: '.$error->getMessage();}
        if(!$ownershipValid){$cleanupErrors[]='unexpected or unvalidated token-owned namespace blocks destructive cleanup: '.json_encode(['tables'=>$ownedTables,'expected_tables'=>$expectedTables,'accounts'=>$ownedAccounts,'expected_accounts'=>$expectedAccounts],JSON_UNESCAPED_SLASHES);}
        else{
            foreach(array_reverse($ownedTables)as$table)try{$observer->query("DROP TABLE `$table`");}catch(Throwable$error){$cleanupErrors[]='DROP TABLE failed for '.$table.': '.$error->getMessage();}
            foreach($ownedAccounts as$account)try{$observer->query("DROP USER `{$account['USER']}`@`{$account['HOST']}`");}catch(Throwable$error){$cleanupErrors[]='DROP USER failed for '.$account['USER'].'@'.$account['HOST'].': '.$error->getMessage();}
            $expectedParent=realpath((string)($environment['FMONITOR_CONTROL_QUEUE_VERIFY_ARTIFACT_ROOT']??''));$actualParent=realpath(dirname($artifactChild));$expectedBasename='construction-control-queue-'.$token;if(is_dir($artifactChild)&&!is_link($artifactChild)&&$expectedParent!==false&&$actualParent===$expectedParent&&basename($artifactChild)===$expectedBasename)try{ccqRemoveTree($artifactChild);}catch(Throwable$error){$cleanupErrors[]='artifact cleanup failed: '.$error->getMessage();}elseif(file_exists($artifactChild))$cleanupErrors[]='artifact child ownership validation failed';
        }
        if($cleanupErrors!==[])$primary=new TestFailure('SETUP_FAILURE: attempt-all cleanup failed: '.implode('; ',$cleanupErrors));
    }
    if($primary instanceof TestFailure&&str_starts_with($primary->getMessage(),'SETUP_FAILURE: attempt-all cleanup failed:'))throw $primary;
    assertSameValue([],ccqOwnedTables($observer,$token),'Attempt-all cleanup must remove exact tables');assertSameValue([],ccqOwnedAccounts($observer,$token),'Attempt-all cleanup must remove exact accounts');assertSameValue(false,is_dir($artifactChild),'Attempt-all cleanup must remove exact artifact/session child');foreach(array_keys($workerPids)as$workerPid)assertSameValue(false,is_dir('/proc/'.$workerPid),'Attempt-all cleanup must reap exact workers');
    if($primary instanceof Throwable)throw $primary;return$result;
}

function ccqAssertAudit(array $audit, string $token): void
{
    assertSameValue('ProductionPilotHttpEntrypointFactory', $audit['composition'] ?? null, 'Every observation must use the production factory');
    assertSameValue(['/pilot/construction-control','/pilot/construction-control?page=1','/pilot/construction-control?page=2'], $audit['public_paths'] ?? null, 'Audit must pin only approved queue paths');
    assertSameValue(['s','a','b','x'], $audit['runtime_slots'] ?? null, 'Audit must contain four exact runtime slots');
    assertSameValue(['SELECT'], $audit['effective_statement_privileges'] ?? null, 'Runtime principals must be SELECT-only');
    assertSameValue(true, $audit['performance_schema_preflight'] ?? null, 'Audit must prove immutable performance_schema preflight');
    assertSameValue(true, $audit['sensitivity_write_attempt_observed'] ?? null, 'Sensitivity DML attempt must be independently observed');
    assertSameValue(true, $audit['sensitivity_rejected'] ?? null, 'Sensitivity run must be rejected despite rollback/denial');
    assertSameValue(['a','b'], $audit['concurrent_threads'] ?? null, 'Concurrent slots must map to two distinct audited threads');
    assertSameValue(true, $audit['concurrent_barrier_overlap'] ?? null, 'Concurrent HTTP requests must overlap at the test barriers');
    assertSameValue(0, $audit['production_write_attempts'] ?? null, 'Production request threads must contain no write attempts');
    assertSameValue(0, $audit['db_mutations'] ?? null, 'Owned database must remain immutable');
    assertSameValue(0, $audit['file_mutations'] ?? null, 'Owned filesystem must remain immutable');
    assertSameValue(0, $audit['session_mutations'] ?? null, 'Owned session namespace must remain immutable');
    assertSameValue(true, $audit['ambient_decoy_preserved'] ?? null, 'Ambient decoy must be preserved');
    assertSameValue(ccqToken($token), $audit['run_token'] ?? null, 'Private audit must identify its exact run token');
}

$root = dirname(__DIR__,2);
$spec = $root . '/specs/CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001.md';
$verifier = $root . '/tests/Support/characterize_construction_control_queue_verifier.php';
$artifactRoot = $root . '/.local/test-artifacts/characterize-construction-control-queue-' . bin2hex(random_bytes(8));
$tokens = ['first'=>bin2hex(random_bytes(6)), 'second'=>bin2hex(random_bytes(6))];
$decoyTable = 'ccq_ambient_decoy_' . bin2hex(random_bytes(8));
$decoyBytes = "CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001\0ambient\xffdecoy";
$db = null; $failure = null; $exit = 0;

try {
    assertSameValue(CCQ_SPEC_SHA256, hash_file('sha256',$spec), 'Approved executable specification hash must remain pinned');
    $db = ccqAdminDb(ccqConfig()); ccqPerformanceSchemaPreflight($db);
    if (!mkdir($artifactRoot,0700,true) || file_put_contents($artifactRoot.'/ambient-decoy.bin',$decoyBytes,LOCK_EX)!==strlen($decoyBytes)) throw new TestFailure('SETUP_FAILURE: cannot create private artifact decoy');
    $db->query("CREATE TABLE `$decoyTable`(decoy_key VARCHAR(80) PRIMARY KEY,decoy_value VARBINARY(255) NOT NULL)");
    $insert=$db->prepare("INSERT INTO `$decoyTable` VALUES(?,?)"); $key='ambient-state'; $insert->bind_param('ss',$key,$decoyBytes); $insert->execute();
    $beforeDb=ccqTableState($db,$decoyTable); $beforeTree=ccqTree($artifactRoot);
    $environment=getenv(); if (!is_array($environment)) $environment=$_ENV;
    foreach (ccqConfig() as $suffix=>$value) $environment["FMONITOR_VERIFY_DB_$suffix"]=(string)$value;
    $environment['FMONITOR_CONTROL_QUEUE_VERIFY_ARTIFACT_ROOT']=$artifactRoot;
    // A helper that only prints the expected transcript/JSON and creates no real
    // resources must be rejected by the independent positive-existence witness.
    $fake=$artifactRoot.'/literal-fake.php';
    $fakeSource="<?php file_put_contents((string)getenv('FMONITOR_CONTROL_QUEUE_VERIFY_AUDIT_FILE'),'{}'); echo " . var_export("CHARACTERIZATION_OK CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001\n",true) . ";\n";
    if(file_put_contents($fake,$fakeSource,LOCK_EX)!==strlen($fakeSource))throw new TestFailure('SETUP_FAILURE: cannot create fake-helper sensitivity');
    $fakeToken=bin2hex(random_bytes(6));$environment['FMONITOR_CONTROL_QUEUE_VERIFY_RUN_TOKEN']=$fakeToken;$environment['FMONITOR_CONTROL_QUEUE_VERIFY_AUDIT_FILE']=$artifactRoot.'/fake-audit.json';
    $fakeRejected=false;try{ccqRunWitnessed($root,$fake,$environment,$db,$fakeToken,$artifactRoot.'/construction-control-queue-'.$fakeToken);}catch(TestFailure $error){$fakeRejected=str_starts_with($error->getMessage(),'RED_ASSERTION: production HTTP verifier must expose exact positive fixture/accounts');}
    assertSameValue(true,$fakeRejected,'Literal stdout/audit JSON fake must fail independent positive-existence witness');
    if(is_file($environment['FMONITOR_CONTROL_QUEUE_VERIFY_AUDIT_FILE']))unlink($environment['FMONITOR_CONTROL_QUEUE_VERIFY_AUDIT_FILE']);unlink($fake);
    assertSameValue([],ccqOwnedTables($db,$fakeToken),'Fake helper must create no owned tables');assertSameValue([],ccqOwnedAccounts($db,$fakeToken),'Fake helper must create no accounts');
    // The reviewed helper must carry an explicit response-only broken mode.
    // Outer-owned per-request history windows and the unpredictable post-ready
    // challenge, not helper status/stdout, decide that this fake is rejected.
    $responseFakeToken=bin2hex(random_bytes(6));$environment['FMONITOR_CONTROL_QUEUE_VERIFY_RUN_TOKEN']=$responseFakeToken;ccqCreateResponseFakeFixture($db,$responseFakeToken);$responseFake=$root.'/tests/Support/construction_control_queue_response_only_fake.php';
    $responseFakeRejected=false;$responseFakeEvidence='none';try{ccqRunWitnessed($root,$responseFake,$environment,$db,$responseFakeToken,$artifactRoot.'/construction-control-queue-'.$responseFakeToken);}catch(TestFailure$error){$responseFakeEvidence=$error->getMessage();$responseFakeRejected=str_contains($responseFakeEvidence,'Request group must declare worker PID/slot/nonce bindings')||str_contains($responseFakeEvidence,'Exact request slots must have one active connection each')||str_contains($responseFakeEvidence,'Sensitivity HTTP response must contain outer-only post-readiness challenge')||str_contains($responseFakeEvidence,'production HTTP verifier must expose exact positive fixture/accounts');}assertSameValue(true,$responseFakeRejected,'Concrete resource-owning response-only fake must fail outer-owned thread/challenge evidence; evidence='.$responseFakeEvidence);
    assertSameValue([],ccqOwnedTables($db,$responseFakeToken),'Response-only fake cleanup must remove tables');assertSameValue([],ccqOwnedAccounts($db,$responseFakeToken),'Response-only fake cleanup must remove accounts');
    $runs=[];
    foreach ($tokens as $name=>$token) {
        assertSameValue([],ccqOwnedTables($db,$token),'SETUP_FAILURE: owned SQL namespace must begin empty');
        assertSameValue([],ccqOwnedAccounts($db,$token),'SETUP_FAILURE: four owned accounts must begin absent');
        assertSameValue(false,file_exists($artifactRoot.'/construction-control-queue-'.$token),'SETUP_FAILURE: owned artifact child must begin absent');
        $audit=$artifactRoot.'/audit-'.$token.'.json'; $environment['FMONITOR_CONTROL_QUEUE_VERIFY_RUN_TOKEN']=$token; $environment['FMONITOR_CONTROL_QUEUE_VERIFY_AUDIT_FILE']=$audit;
        $child=$artifactRoot.'/construction-control-queue-'.$token;
        $runs[$name]=ccqRunWitnessed($root,$verifier,$environment,$db,$token,$child); $evidence=json_encode(['status'=>$runs[$name]['status'],'stdout'=>$runs[$name]['stdout'],'stderr'=>$runs[$name]['stderr']],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        assertSameValue(0,$runs[$name]['status'],"RED_ASSERTION: production-HTTP queue behavior must become a successful $name run; evidence=$evidence");
        assertSameValue('',$runs[$name]['stderr'],ucfirst($name).' run must keep stderr empty');
        if(is_file($audit))unlink($audit);
        assertSameValue([],ccqOwnedTables($db,$token),ucfirst($name).' run must remove exact SQL namespace'); assertSameValue([],ccqOwnedAccounts($db,$token),ucfirst($name).' run must remove four exact runtime accounts');
        assertSameValue(false,file_exists($artifactRoot.'/construction-control-queue-'.$token),ucfirst($name).' run must remove exact artifact/session child');
        assertSameValue($beforeDb,ccqTableState($db,$decoyTable),ucfirst($name).' run must preserve ambient SQL decoy'); assertSameValue($beforeTree,ccqTree($artifactRoot),ucfirst($name).' run must preserve ambient filesystem decoy');
    }
    $expected="CONSTRUCTION_CONTROL_QUEUE admission unauthenticated=401 inactive=403 denied=403 allowed_get=200 allowed_head=200 mutations=0 PILOT_ONLY\n"
        ."CONSTRUCTION_CONTROL_QUEUE projection working=4 excluded_nonworking=1 order=451201,451202,451203,451204 engineer=event,fallback,absent activity=none,none,max,max pto=false,true,false,false escaped=1 mutations=0 PILOT_ONLY\n"
        ."CONSTRUCTION_CONTROL_QUEUE pagination total=51 page1=50 page2=1 assigned_only_on_page2=8101 browser_filter=not_executed PILOT_ONLY\n"
        ."CONSTRUCTION_CONTROL_QUEUE failures page_zero=503 page_text=503 page_range=503 sql_denied=503 malformed_row=503 malformed_event=503 body_sha256=38c9439b9ab2abf40304675451d0fae7069809a7e3c8fe0ef96274c8680f21eb mutations=0 PILOT_ONLY\n"
        ."CONSTRUCTION_CONTROL_QUEUE reads sequential=2 concurrent=2 equivalent=1 db_mutations=0 file_mutations=0 session_mutations=0\n"
        ."CHARACTERIZATION_OK CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001\n";
    assertSameValue($expected,$runs['first']['stdout'],'First run must emit exact approved transcript'); assertSameValue($runs['first']['stdout'],$runs['second']['stdout'],'Distinct-token transcripts must be byte-identical');
    echo "ok - CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001 RED contract is deterministic and isolated\n";
} catch (TestFailure $error) { $failure=$error->getMessage(); $exit=str_starts_with($failure,'SETUP_FAILURE:')?2:1; }
catch (Throwable $error) { $failure='REGRESSION_FAILURE: '.$error->getMessage(); $exit=1; }
finally {
    if ($db instanceof mysqli) { try { if ($db->query("SHOW TABLES LIKE '$decoyTable'")->fetch_row() !== null) $db->query("DROP TABLE `$decoyTable`"); } catch (Throwable) {} $db->close(); }
    ccqRemoveTree($artifactRoot);
}
if ($failure !== null) fwrite(STDERR,$failure."\n");
exit($exit);

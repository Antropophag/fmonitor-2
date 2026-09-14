#!/usr/bin/env php
<?php
declare(strict_types=1);

const REQUIRED_EFFECTS = [
    'CREATE_DISPOSABLE_PROJECT', 'CREATE_FRESH_DATABASE', 'PREPARE_RUNTIME',
    'RUN_CANONICAL_MIGRATIONS', 'PROVISION_INITIAL_USERS', 'START_RUNTIME',
    'RUN_ACCEPTANCE',
];
const ORDERED_CALLS = [
    'precreate.inspect', 'compose.create', 'postcreate.inspect', 'database.create',
    'runtime.prepare', 'schema.migrate', 'users.provision', 'users.provision.replay',
    'runtime.start', 'health.live', 'health.ready', 'jobs.health',
    'golden.login-access', 'golden.fkr', 'golden.construction-control',
    'golden.checklist', 'golden.otiz', 'jobs.enqueue', 'jobs.observe',
    'runtime.image-inventory', 'runtime.process-inspect', 'runtime.include-traces',
    'evidence.accept',
];

function emit(string $reason, array $extra = [], int $exit = 64): never
{
    echo json_encode(['reason' => $reason] + $extra, JSON_UNESCAPED_SLASHES) . "\n";
    exit($exit);
}
function options(array $argv): array
{
    $result = [];
    foreach (array_slice($argv, 2) as $arg) {
        if (str_starts_with($arg, '--') && str_contains($arg, '=')) {
            [$key, $value] = explode('=', substr($arg, 2), 2);
            $result[$key] = $value;
        }
    }
    return $result;
}
function readDocument(?string $path, string $malformed): array
{
    if ($path === null || !is_file($path)) emit($malformed);
    try { $value = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    catch (Throwable) { emit($malformed); }
    if (!is_array($value)) emit($malformed);
    return $value;
}
function digest(string $path): string { return hash_file('sha256', $path); }
function canonical(array $value): string
{
    ksortRecursive($value);
    return json_encode($value, JSON_UNESCAPED_SLASHES) . "\n";
}
function ksortRecursive(array &$value): void
{
    ksort($value);
    foreach ($value as &$item) if (is_array($item)) ksortRecursive($item);
}
function appendRecord(string $path, array $record): void
{
    $handle = fopen($path, 'ab');
    if ($handle === false) emit('EVIDENCE_WRITE_FAILED', [], 70);
    fwrite($handle, canonical($record)); fflush($handle);
    if (function_exists('fsync')) fsync($handle);
    fclose($handle);
}
function records(string $path): array
{
    if (!is_file($path)) return [];
    $rows = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $row = json_decode($line, true);
        if (is_array($row)) $rows[] = $row;
    }
    return $rows;
}
function sameNames(array $a, array $b): bool { return $a == $b; }
function uuidValid(mixed $value): bool
{
    return is_string($value) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value) === 1;
}
function admission(array $manifest, array $auth, array $opts, array $driver): void
{
    if (($manifest['version'] ?? null) !== 'fmonitor-clean-stand-target-v2') emit('MANIFEST_VERSION_INVALID');
    if (($auth['version'] ?? null) !== 'fmonitor-clean-stand-authorization-v2') emit('AUTHORIZATION_VERSION_INVALID');
    if (!uuidValid($auth['authorizationId'] ?? null)) emit('AUTHORIZATION_ID_INVALID');
    if (!uuidValid($auth['operationId'] ?? null)) emit('OPERATION_ID_INVALID');
    if (($auth['intent'] ?? null) !== 'CLEAN_STAND_PROVISION_AND_ACCEPT') emit('AUTHORIZATION_INTENT_INVALID');
    if (strtotime((string) ($auth['expiresAtUtc'] ?? '')) <= time()) emit('AUTHORIZATION_EXPIRED');
    if (($auth['effects'] ?? null) !== REQUIRED_EFFECTS) emit('AUTHORIZATION_EFFECTS_INVALID');
    if (!hash_equals((string) ($opts['authorization-digest'] ?? ''), digest($opts['authorization']))) emit('AUTHORIZATION_DIGEST_MISMATCH');
    if (($manifest['source'] ?? null) !== ($auth['source'] ?? null)) emit('SOURCE_MISMATCH');
    if (!preg_match('/^.+@sha256:[0-9a-f]{64}$/', (string) ($auth['image'] ?? ''))) emit('IMAGE_INVALID');
    if (($manifest['image'] ?? null) !== ($auth['image'] ?? null)) emit('IMAGE_MISMATCH');
    $mp = $manifest['compose']['path'] ?? null; $ap = $auth['compose']['path'] ?? null;
    if (!is_string($mp) || !is_string($ap) || $mp !== $ap || !str_starts_with($mp, '/') || !is_file($mp)) emit('COMPOSE_PATH_MISMATCH');
    if (is_link($mp)) emit('COMPOSE_SYMLINK_INVALID');
    if (($manifest['compose']['sha256'] ?? null) !== digest($mp) || ($auth['compose']['sha256'] ?? null) !== digest($mp)) emit('COMPOSE_DIGEST_MISMATCH');
    if (($manifest['cleanDisposable'] ?? null) !== true) emit('TARGET_NOT_DISPOSABLE');
    if (($manifest['expectedAbsent'] ?? null) !== true) emit('EXPECTED_ABSENCE_REQUIRED');
    if (!sameNames($manifest['names'] ?? [], $auth['names'] ?? [])) emit('TARGET_NAMES_MISMATCH');
    $names = $manifest['names'] ?? [];
    if (($names['project'] ?? null) === ($names['database'] ?? null)) emit('TARGET_ALIAS_INVALID');
    $expectedDatabase = str_replace('-', '_', (string) ($names['project'] ?? ''));
    if (($names['database'] ?? null) !== $expectedDatabase) emit('TARGET_NAMES_MISMATCH');
    $credentials = $auth['credentialFiles'] ?? [];
    if (array_keys($credentials) !== ['databasePassword']) emit('CREDENTIAL_KEYS_INVALID');
    $credential = $credentials['databasePassword'] ?? null;
    if (!is_string($credential) || !str_starts_with($credential, '/')) emit('CREDENTIAL_REFERENCE_INVALID');
    if (!file_exists($credential)) emit('CREDENTIAL_MISSING');
    if (is_link($credential)) emit('CREDENTIAL_SYMLINK_INVALID');
    if ((fileperms($credential) & 0777) !== 0600) emit('CREDENTIAL_MODE_INVALID');
    $pre = $driver['precreate'] ?? [];
    if (($pre['projectExists'] ?? null) !== false) emit('TARGET_ALREADY_EXISTS');
    foreach (($pre['conflicts'] ?? []) as $conflict) {
        $scope = $conflict['scope'] ?? '';
        if ($scope === 'production') emit('PRODUCTION_OVERLAP');
        if ($scope === 'neighbor') emit('NEIGHBOR_OVERLAP');
        emit(match ($conflict['class'] ?? '') { 'container' => 'UNEXPECTED_CONTAINER', 'network' => 'UNEXPECTED_NETWORK', 'volume' => 'UNEXPECTED_VOLUME', default => 'TARGET_ALREADY_EXISTS' });
    }
}
function authorizationReplayGuard(string $ledger, array $auth, string $action): void
{
    foreach (records($ledger) as $row) {
        if (($row['operationId'] ?? null) === $auth['operationId'] && ($row['authorizationId'] ?? null) !== $auth['authorizationId']) emit($action === 'run' ? 'REPLAY_CONFLICT' : 'AUTHORIZATION_REPLAY_CONFLICT');
    }
}
function traceRow(string $call, array $driver, array $manifest, array $auth, int $sequence): array
{
    $o = $driver['observations'] ?? []; $post = $driver['postcreate'] ?? [];
    $row = ['call' => $call, 'sequence' => $sequence, 'stateChanging' => in_array($call, ['compose.create','database.create','runtime.prepare','schema.migrate','users.provision','runtime.start'], true)];
    if ($call === 'postcreate.inspect') $row['observedIds'] = $post['ids'] ?? null;
    if ($call === 'runtime.start') {
        $row['containers'] = [];
        foreach (($o['containers'] ?? []) as $name => $container) $row['containers'][] = ['name'=>$name,'containerId'=>$container['id']??null,'running'=>$container['running']??false,'healthy'=>$container['healthy']??false];
    }
    if ($call === 'health.live') $row['httpStatus'] = $o['http']['live']['status'] ?? null;
    if ($call === 'health.ready') $row['httpStatus'] = $o['http']['ready']['status'] ?? null;
    if ($call === 'jobs.health') { $row['exit']=$o['jobsHealth']['exit']??null; $row['heartbeats']=$o['heartbeats']??[]; }
    if (str_starts_with($call, 'golden.')) $row += $o['golden'][substr($call, 7)] ?? [];
    if ($call === 'jobs.observe') $row += ['tablePrefix'=>$o['jobs']['prefix']??null,'tables'=>$o['jobs']['tables']??null,'jobId'=>$o['jobs']['jobId']??null,'leaseJobId'=>$o['jobs']['leaseJobId']??null,'historyJobId'=>$o['jobs']['historyJobId']??null,'outboxId'=>$o['jobs']['outboxId']??null,'attemptOutboxId'=>$o['jobs']['attemptOutboxId']??null,'historyOutboxId'=>$o['jobs']['historyOutboxId']??null];
    if ($call === 'runtime.image-inventory') $row += $o['imageInventory'] ?? [];
    if ($call === 'runtime.process-inspect') $row['processes'] = $o['processes'] ?? [];
    if ($call === 'runtime.include-traces') $row['traces'] = $o['includeTraces'] ?? [];
    return $row;
}
function validatePostcreate(array $driver, array $manifest): void
{
    $p=$driver['postcreate']??[]; $ids=$p['ids']??[];
    if (($p['labels']['project']??null)!==($manifest['names']['project']??null) || ($p['labels']['disposable']??null)!=='true') emit('POSTCREATE_LABEL_MISMATCH');
    if (($p['image']??null)!==($manifest['image']??null)) emit('POSTCREATE_IMAGE_MISMATCH');
    foreach (['db','php','web','jobs-worker','jobs-scheduler'] as $n) {
        if (!preg_match('/^[0-9a-f]{64}$/',(string)($ids['containers'][$n]??''))) emit('POSTCREATE_CONTAINER_MISMATCH');
        if ($n !== 'db' && ($driver['observations']['containers'][$n]['id'] ?? null) !== ($ids['containers'][$n] ?? null)) emit('POSTCREATE_CONTAINER_MISMATCH');
    }
    if (($p['networkAttachments']??null)!==($ids['network']??null)) emit('POSTCREATE_NETWORK_MISMATCH');
    if (($p['volumeMounts']??null)!==($ids['volumes']??null)) emit('POSTCREATE_VOLUME_MISMATCH');
}
function deriveFacts(array $driver, array $manifest, array $auth): array
{
    $o=$driver['observations']??[]; $db=$o['database']??[];
    if (($db['schemaBefore']??null)!==[] || ($db['schemaVersion']??null)!==24 || count($db['migrationLedger']??[])!==24) emit('PROVISIONING_INVALID');
    $provision=$o['provision']??[];
    if (($provision['replay']['createdIds']??null)!==[] || ($provision['firstFactDigest']??null)!==($provision['replay']['userFactDigest']??null)) emit('PROVISIONING_INVALID');
    $containers=$o['containers']??[];
    foreach (['php','web','jobs-worker','jobs-scheduler'] as $name) if (($containers[$name]['running']??false)!==true || ($containers[$name]['healthy']??false)!==true) emit('RUNTIME_NOT_READY');
    if (($o['http']['live']['status']??null)!==200 || ($o['http']['ready']['status']??null)!==200 || ($o['jobsHealth']['exit']??null)!==0 || ($o['jobsHealth']['body']['ok']??null)!==true) emit('RUNTIME_NOT_READY');
    $heartbeats=array_column($o['heartbeats']??[],'workerId'); if (count(array_unique($heartbeats))!==2 || max(array_column($o['heartbeats']??[],'ageSeconds'))>30) emit('RUNTIME_NOT_READY');
    $golden=[];
    foreach (['login-access','fkr','construction-control','checklist','otiz'] as $name) {
        $g=$o['golden'][$name]??[];
        if (($g['status']??null)!==200 || ($g['factCountDelta']??null)!==1 || ($g['unauthorizedStatus']??null)!==403 || ($g['unauthorizedProjectionBefore']??null)!==($g['unauthorizedProjectionAfter']??null)) emit('GOLDEN_FLOW_INVALID');
        $golden[$name]=['authorized'=>true,'factAppended'=>true,'unauthorizedRejected'=>true,'rejectedFactsUnchanged'=>true];
    }
    $j=$o['jobs']??[]; $expected=['jobs'=>'fm2_jobs','jobHistory'=>'fm2_job_events','outbox'=>'fm2_outbox_intents','outboxHistory'=>'fm2_outbox_attempt_events','heartbeats'=>'fm2_worker_heartbeats'];
    foreach ($expected as $k=>$suffix) if (($j['tables'][$k]??null)!==($j['prefix']??'').$suffix) emit('JOBS_ACCEPTANCE_INVALID');
    if (count(array_unique([$j['jobId']??null,$j['leaseJobId']??null,$j['historyJobId']??null]))!==1 || count(array_unique([$j['outboxId']??null,$j['attemptOutboxId']??null,$j['historyOutboxId']??null]))!==1) emit('JOBS_ACCEPTANCE_INVALID');
    if (($j['statuses']??null)!==['ready','claimed','completed'] || ($j['blockingRecovery']??null)!==[]) emit('JOBS_ACCEPTANCE_INVALID');
    $inventory=$o['imageInventory']??[]; $processes=$o['processes']??[]; $traces=$o['includeTraces']??[];
    $legacy=static fn(string $s):bool=>str_contains($s,'rapid-pilot')||str_contains($s,'RuntimeRecovery');
    foreach (($inventory['paths']??[]) as $path) if ($legacy($path)) emit('LEGACY_RUNTIME_REACHABLE');
    foreach ($processes as $process) foreach (($process['argv']??[]) as $arg) if ($legacy((string)$arg)) emit('LEGACY_RUNTIME_REACHABLE');
    $wanted=['http.health','http.login','http.golden','migration','jobs']; $seen=[];
    foreach ($traces as $trace) {
        $seen[]=$trace['invocation']??null; $expectedContainer=($trace['invocation']??null)==='jobs' ? ($driver['postcreate']['ids']['containers']['jobs-worker']??null) : ($driver['postcreate']['ids']['containers']['php']??null);
        if (($trace['source']??null)!==$manifest['source'] || ($trace['image']??null)!==$manifest['image'] || ($trace['operationId']??null)!==$auth['operationId'] || !preg_match('/^[0-9a-f]{64}$/',(string)($trace['artifactSha256']??'')) || ($trace['containerId']??null)!==$expectedContainer) emit('LEGACY_RUNTIME_REACHABLE');
        foreach (($trace['files']??[]) as $file) if ($legacy($file)) emit('LEGACY_RUNTIME_REACHABLE');
    }
    sort($seen); sort($wanted); if ($seen!==$wanted || ($inventory['image']??null)!==$manifest['image']) emit('LEGACY_RUNTIME_REACHABLE');
    $commands=[]; foreach ($processes as $p) { $id=$p['containerId']??''; foreach (($driver['postcreate']['ids']['containers']??[]) as $name=>$cid) if ($id===$cid && in_array($name,['php','web','jobs-worker','jobs-scheduler'],true)) $commands[$name]=match($name){'php'=>'php-fpm','web'=>'nginx','jobs-worker'=>'php bin/yii jobs/worker','jobs-scheduler'=>'php bin/yii jobs/scheduler'}; }
    if (count($commands)!==4) emit('LEGACY_RUNTIME_REACHABLE');
    return ['database'=>['fresh'=>true,'schemaVersion'=>24,'migrationCount'=>24], 'provisioning'=>['replayCreated'=>[],'sameFacts'=>true], 'legacyInputs'=>[], 'runtime'=>['healthy'=>array_keys($containers),'live'=>200,'ready'=>200,'jobsHealth'=>['ok'=>true,'counters'=>$o['jobsHealth']['body']['counters']??[]],'heartbeats'=>$heartbeats], 'golden'=>$golden, 'jobs'=>['chain'=>['enqueue','claim','complete','outbox-attempt','outbox-history'],'stableRead'=>true,'blockingRecovery'=>[]], 'closure'=>['imageHasRapidPilot'=>false,'loadedLegacy'=>[],'commands'=>$commands]];
}

function execute(array $argv, array $environment, bool $allowFailure=false, ?string $input=null): array
{
    $pipes=[];$process=proc_open($argv,[0=>$input===null?['file','/dev/null','r']:['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,$environment);
    if(!is_resource($process))emit('PROCESS_START_FAILED',[],70);
    if($input!==null){fwrite($pipes[0],$input);fclose($pipes[0]);}
    $stdout=stream_get_contents($pipes[1]);$stderr=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($process);
    if(!$allowFailure&&$exit!==0)emit('STEP_FAILED',['step'=>$argv[0],'exit'=>$exit],70);
    return [$exit,(string)$stdout,(string)$stderr];
}
function privateFile(array $files,string $key): string
{
    $path=$files[$key]??null;if(!is_string($path)||!str_starts_with($path,'/')||!is_file($path)||is_link($path)||(fileperms($path)&0777)!==0600)emit('CREDENTIAL_REFERENCE_INVALID');
    $mode=fileperms($path)&0777;if(!in_array($mode,[0400,0600],true))emit('CREDENTIAL_REFERENCE_INVALID');$value=trim((string)file_get_contents($path));if($value==='')emit('CREDENTIAL_REFERENCE_INVALID');return $value;
}
function realRun(string $packagePath,string $evidence,array $opts): never
{
    $package=readDocument($packagePath,'AUTHORIZATION_PACKAGE_MALFORMED');
    $expected=['acceptanceContext','acceptanceOverride','allowedEffects','authorizationDecisionDigest','credentialFiles','evidenceRoot','expectedAbsent','expiresAtUtc','forbiddenResourceIds','image','names','operationId','productionCompose','runtime','source','version'];$keys=array_keys($package);sort($keys);if($keys!==$expected)emit('AUTHORIZATION_PACKAGE_MALFORMED');
    $supplied=$opts['authorization-package-digest']??getenv('FMONITOR_CLEAN_STAND_AUTHORIZATION_PACKAGE_SHA256');if(!is_string($supplied)||!preg_match('/^[0-9a-f]{64}$/',$supplied)||!hash_equals($supplied,digest($packagePath)))emit('AUTHORIZATION_DIGEST_MISMATCH');
    if(($package['version']??null)!=='fmonitor-clean-stand-outer-authorization-v1'||!uuidValid($package['operationId']??null)||strtotime((string)$package['expiresAtUtc'])<=time()||!preg_match('/^[0-9a-f]{64}$/',(string)$package['authorizationDecisionDigest']))emit('AUTHORIZATION_PACKAGE_INVALID');
    if(($package['expectedAbsent']??null)!==true||($package['evidenceRoot']??null)!==$evidence||!str_starts_with((string)($package['names']['project']??''),'fm2-clean-'))emit('TARGET_NOT_DISPOSABLE');
    if(!preg_match('/^[0-9a-f]{40}$/',(string)$package['source'])||!preg_match('/^.+@sha256:[0-9a-f]{64}$/',(string)$package['image']))emit('CANDIDATE_INVALID');
    foreach(['productionCompose','acceptanceOverride','acceptanceContext'] as $key){$item=$package[$key];$path=$item['path']??null;if(!is_string($path)||!str_starts_with($path,'/')||!is_file($path)||is_link($path)||!hash_equals((string)($item['sha256']??''),digest($path)))emit(strtoupper($key).'_INVALID');}
    $boundContext=readDocument($package['acceptanceContext']['path'],'ACCEPTANCE_CONTEXT_INVALID');if(($boundContext['operationId']??null)!==$package['operationId']||($boundContext['targetDigest']??null)!==$package['authorizationDecisionDigest'])emit('ACCEPTANCE_CONTEXT_INVALID');
    $effects=['CREATE_DISPOSABLE_PROJECT','CREATE_FRESH_DATABASE','PREPARE_RUNTIME','RUN_CANONICAL_MIGRATIONS','PROVISION_INITIAL_USERS','START_RUNTIME','RUN_ACCEPTANCE'];if($package['allowedEffects']!==$effects)emit('AUTHORIZATION_EFFECTS_INVALID');
    $files=$package['credentialFiles'];$dbPassword=privateFile($files,'databasePassword');$migrationPassword=privateFile($files,'migrationDatabasePassword');$bootstrapPassword=privateFile($files,'bootstrapPassword');$acceptancePassword=privateFile($files,'acceptanceDatabasePassword');$cookie=privateFile($files,'yiiCookieValidationKey');$identity=privateFile($files,'yiiIdentityKey');$bitrixConfig=privateFile($files,'bitrixConfig');
    $support=__DIR__.'/clean-stand';$env=array_merge(getenv(),[
      'COMPOSE_PROJECT_NAME'=>$package['names']['project'],'FMONITOR_RUNTIME_IMAGE'=>$package['image'],'FMONITOR_DB_NAME'=>$package['names']['database'],'FMONITOR_DB_USER'=>$package['runtime']['databaseUser'],'FMONITOR_DB_PASSWORD'=>$dbPassword,'FMONITOR_MIGRATION_DB_USER'=>$package['runtime']['migrationDatabaseUser'],'FMONITOR_MIGRATION_DB_PASSWORD'=>$migrationPassword,'FMONITOR_PROCESS_TABLE_PREFIX'=>$package['runtime']['processTablePrefix'],'FMONITOR_LEGACY_TABLE_PREFIX'=>$package['runtime']['legacyTablePrefix'],'FMONITOR_SESSION_INSTANCE'=>$package['runtime']['sessionInstance'],'FMONITOR_YII_COOKIE_VALIDATION_KEY'=>$cookie,'FMONITOR_YII_IDENTITY_KEY'=>$identity,'FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD'=>$bootstrapPassword,'FMONITOR_TRUSTED_REQUEST_HOST'=>$package['runtime']['trustedHost'],'FMONITOR_TRUSTED_REQUEST_SCHEME'=>$package['runtime']['trustedScheme'],'FMONITOR_HTTP_PORT'=>(string)$package['runtime']['httpPort'],'FMONITOR_BITRIX_CONFIG'=>'/run/fmonitor-secrets/bitrix-config.json','FMONITOR_ACCEPTANCE_SUPPORT_ROOT'=>$support,'FMONITOR_ACCEPTANCE_PROBE_HOST_FILE'=>$support.'/include-probe.php','FMONITOR_ACCEPTANCE_PROBE_INI_HOST_FILE'=>$support.'/99-acceptance-probe.ini','FMONITOR_ACCEPTANCE_CONTEXT_HOST_FILE'=>$package['acceptanceContext']['path'],'FMONITOR_ACCEPTANCE_CONTEXT_DIGEST'=>$package['acceptanceContext']['sha256'],'FMONITOR_ACCEPTANCE_OPERATION_ID'=>$package['operationId'],'FMONITOR_ACCEPTANCE_TARGET_DIGEST'=>$package['authorizationDecisionDigest'],'FMONITOR_ACCEPTANCE_DB_USER'=>$package['runtime']['acceptanceDatabaseUser'],'FMONITOR_ACCEPTANCE_DB_PASSWORD_HOST_FILE'=>$files['acceptanceDatabasePassword']]);
    $compose=['docker','compose','--project-name',$package['names']['project'],'--file',$package['productionCompose']['path'],'--file',$package['acceptanceOverride']['path']];
    [$psExit,$ps]=execute(['docker','ps','-aq','--filter','label=com.docker.compose.project='.$package['names']['project']],$env,true);if($psExit!==0||trim($ps)!=='')emit('TARGET_ALREADY_EXISTS');
    foreach(array_merge([$package['names']['network']],$package['names']['volumes']) as $name){[$x]=execute(['docker',str_contains($name,'_default')?'network':'volume','inspect',$name],$env,true);if($x===0)emit('TARGET_ALREADY_EXISTS');}
    appendRecord($evidence.'/clean-stand-operations.jsonl',['event'=>'PREFLIGHT_OK','operationId'=>$package['operationId'],'authorizationDigest'=>$supplied]);
    execute([...$compose,'--profile','acceptance','--profile','jobs','create'],$env);
    $observed=[];foreach(['db','php','web','jobs-worker','jobs-scheduler'] as $service){[, $id]=execute([...$compose,'ps','-q',$service],$env);$id=trim($id);if(!preg_match('/^[0-9a-f]{64}$/',$id))emit('POSTCREATE_CONTAINER_MISMATCH',[],70);[, $inspect]=execute(['docker','inspect',$id],$env);$row=json_decode($inspect,true);$observed[$service]=$row[0]??[];if(($observed[$service]['Config']['Labels']['com.docker.compose.project']??null)!==$package['names']['project'])emit('POSTCREATE_LABEL_MISMATCH',[],70);$expectedName=$package['names']['containers'][$service]??null;if(!is_string($expectedName)||ltrim((string)($observed[$service]['Name']??''),'/')!==$expectedName)emit('POSTCREATE_CONTAINER_MISMATCH',[],70);if($service!=='db'&&($observed[$service]['Config']['Image']??null)!==$package['image'])emit('POSTCREATE_IMAGE_MISMATCH',[],70);foreach(($observed[$service]['Mounts']??[]) as $mount){if(isset($package['names']['volumes'][$mount['Destination']??''])&&($package['names']['volumes'][$mount['Destination']]??null)!==($mount['Name']??null))emit('POSTCREATE_VOLUME_MISMATCH',[],70);}}
    [, $net]=execute(['docker','network','inspect',$package['names']['network'],'--format','{{.Id}}'],$env);$networkId=trim($net);$mounted=[];foreach($observed as $row){$attached=array_column($row['NetworkSettings']['Networks']??[],'NetworkID');if(!in_array($networkId,$attached,true))emit('POSTCREATE_NETWORK_MISMATCH',[],70);foreach(($row['Mounts']??[])as$mount)if(($mount['Type']??null)==='volume')$mounted[]=$mount['Name']??'';}foreach($package['names']['volumes'] as $volume){[, $vid]=execute(['docker','volume','inspect',$volume,'--format','{{.Name}}'],$env);if(trim($vid)!==$volume||!in_array($volume,$mounted,true))emit('POSTCREATE_VOLUME_MISMATCH',[],70);}
    $actualIds=array_merge(array_map(fn($x)=>$x['Id'],$observed),[$networkId]);if(array_intersect($actualIds,$package['forbiddenResourceIds'])!==[])emit('PRODUCTION_OVERLAP',[],70);
    appendRecord($evidence.'/clean-stand-operations.jsonl',['event'=>'TARGET_ATTESTED','operationId'=>$package['operationId'],'containers'=>array_map(fn($x)=>$x['Id'],$observed),'networkId'=>$networkId]);
    execute([...$compose,'up','--detach','--wait','db'],$env);
    foreach(['databaseUser','migrationDatabaseUser','acceptanceDatabaseUser']as$key)if(preg_match('/^[A-Za-z0-9_]{1,32}$/D',(string)$package['runtime'][$key])!==1)emit('AUTHORIZATION_PACKAGE_INVALID');
    $quote=static fn(string$value):string=>str_replace("'","''",$value);$database=$package['names']['database'];if(preg_match('/^[A-Za-z0-9_]{1,64}$/D',$database)!==1)emit('AUTHORIZATION_PACKAGE_INVALID');
    $principalSql="CREATE USER IF NOT EXISTS '".$package['runtime']['databaseUser']."'@'%' IDENTIFIED BY '".$quote($dbPassword)."';GRANT SELECT,INSERT,UPDATE,DELETE ON `{$database}`.* TO '".$package['runtime']['databaseUser']."'@'%';CREATE USER IF NOT EXISTS '".$package['runtime']['acceptanceDatabaseUser']."'@'%' IDENTIFIED BY '".$quote($acceptancePassword)."';GRANT SELECT,INSERT,UPDATE,DELETE ON `{$database}`.* TO '".$package['runtime']['acceptanceDatabaseUser']."'@'%';FLUSH PRIVILEGES;";
    execute([...$compose,'exec','-T','-e','MYSQL_PWD','db','mariadb','-u',$package['runtime']['migrationDatabaseUser']],array_merge($env,['MYSQL_PWD'=>$migrationPassword]),false,$principalSql);
    execute([...$compose,'--profile','deployment','run','--rm','prepare'],$env);execute([...$compose,'--profile','deployment','run','--rm','migrate'],$env);
    execute([...$compose,'--profile','deployment','run','--rm','--no-deps','--user','0','--volume',$files['bitrixConfig'].':/run/fmonitor-acceptance/bitrix-config.json:ro','--entrypoint','sh','prepare','-c','install -o 10001 -g 10001 -m 0400 /run/fmonitor-acceptance/bitrix-config.json /run/fmonitor-secrets/bitrix-config.json'],$env);
    execute([...$compose,'--profile','deployment','run','--rm','-e','FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD','--entrypoint','php','prepare','bin/fmonitor2-provision-initial-admin.php','--email',$package['runtime']['initialAdminEmail']],$env);
    execute([...$compose,'--profile','acceptance','run','--rm','acceptance-setup'],$env);execute([...$compose,'--profile','jobs','up','--detach','--wait','php','web','jobs-worker','jobs-scheduler'],$env);
    foreach(['live','ready'] as $health){[$exit,$body]=execute(['curl','--fail','--silent','--show-error','--header','Host: '.$package['runtime']['trustedHost'],'http://127.0.0.1:'.$package['runtime']['httpPort'].'/health/'.$health],$env,true);$json=json_decode($body,true);if($exit!==0||($json['ok']??null)!==true)emit('RUNTIME_NOT_READY',[],70);}
    execute([...$compose,'exec','-T','jobs-worker','php','bin/yii','jobs/health','--interactive=0'],$env);execute([...$compose,'--profile','acceptance','run','--rm','acceptance-enqueue'],$env);
    $context=$boundContext;$cookieJar=tempnam(sys_get_temp_dir(),'fm2-clean-cookie-');if(!is_string($cookieJar))emit('STEP_FAILED',[],70);chmod($cookieJar,0600);$sensitive=[$cookieJar];register_shutdown_function(static function()use(&$sensitive):void{foreach($sensitive as $path)if(is_string($path)&&is_file($path))@unlink($path);});$captures=[];foreach(($context['httpRequests']??[]) as $request){$bodyFile=tempnam(sys_get_temp_dir(),'fm2-clean-http-');if(!is_string($bodyFile))emit('STEP_FAILED',[],70);chmod($bodyFile,0600);$sensitive[]=$bodyFile;$args=['curl','--silent','--show-error','--output',$bodyFile,'--write-out','%{http_code}','--cookie',$cookieJar,'--cookie-jar',$cookieJar,'--request',$request['method'],'--header','Host: '.$package['runtime']['trustedHost']];foreach(($request['headers']??[])as$header){$args[]='--header';$args[]=$header;}foreach(($request['form']??[])as$key=>$value){$encoded=$key.'='.$value;if(is_string($value)&&str_starts_with($value,'@credential:')){$credentialKey=substr($value,12);privateFile($files,$credentialKey);$encoded=$key.'@'.$files[$credentialKey];}elseif(is_string($value)&&str_starts_with($value,'@capture:')){$capture=substr($value,9);if(!array_key_exists($capture,$captures))emit('GOLDEN_FLOW_INVALID',[],70);$encoded=$key.'='.$captures[$capture];}$args[]='--data-urlencode';$args[]=$encoded;}$args[]='http://127.0.0.1:'.$package['runtime']['httpPort'].$request['path'];[, $status]=execute($args,$env);$body=(string)file_get_contents($bodyFile);@unlink($bodyFile);if((int)$status!==(int)$request['expectedStatus'])emit('GOLDEN_FLOW_INVALID',[],70);foreach(($request['captures']??[])as$name=>$pattern){if(!is_string($name)||!is_string($pattern)||@preg_match($pattern,$body,$match)!==1||!isset($match[1]))emit('GOLDEN_FLOW_INVALID',[],70);$captures[$name]=(string)$match[1];}}
    foreach(($context['validationQueries']??[]) as $query){[, $rows]=execute([...$compose,'exec','-T','-e','MYSQL_PWD','db','mariadb','-N','-B','-u',$package['runtime']['acceptanceDatabaseUser'],$package['names']['database'],'-e',$query['sql']],array_merge($env,['MYSQL_PWD'=>$acceptancePassword]));if(!hash_equals((string)($query['expectedSha256']??''),hash('sha256',$rows)))emit('ACCEPTANCE_FACT_INVALID',[],70);}
    [, $inventory]=execute([...$compose,'exec','-T','php','find','/workspace/fmonitor-2','-path','*/rapid-pilot/*','-print'],$env);if(trim($inventory)!=='')emit('LEGACY_RUNTIME_REACHABLE',[],70);
    // Long-running PHP processes publish their complete include set at graceful
    // shutdown. Restart them afterwards and re-prove readiness before success.
    execute([...$compose,'stop','web','php','jobs-worker','jobs-scheduler'],$env);
    [, $traces]=execute([...$compose,'run','--rm','--no-deps','--entrypoint','sh','prepare','-c','find /home/fmonitor/.local/state/fmonitor2/acceptance-traces -type f -maxdepth 1 -print -exec cat {} \\;'],$env);if(str_contains($traces,'rapid-pilot')||str_contains($traces,'RuntimeRecovery')||substr_count($traces,'"files"')<3)emit('LEGACY_RUNTIME_REACHABLE',[],70);
    execute([...$compose,'--profile','jobs','up','--detach','--wait','php','web','jobs-worker','jobs-scheduler'],$env);
    foreach(['live','ready'] as $health){[$exit,$body]=execute(['curl','--fail','--silent','--show-error','--header','Host: '.$package['runtime']['trustedHost'],'http://127.0.0.1:'.$package['runtime']['httpPort'].'/health/'.$health],$env,true);if($exit!==0||(json_decode($body,true)['ok']??null)!==true)emit('RUNTIME_NOT_READY',[],70);}
    $final=['reason'=>'CLEAN_STAND_ACCEPTED','operationId'=>$package['operationId'],'source'=>$package['source'],'image'=>$package['image'],'authorizationDigest'=>$supplied,'target'=>['project'=>$package['names']['project'],'database'=>$package['names']['database']]];$tmp=$evidence.'/accepted.json.tmp';file_put_contents($tmp,canonical($final));rename($tmp,$evidence.'/accepted.json');emit('CLEAN_STAND_ACCEPTED',array_diff_key($final,['reason'=>true]),0);
}

$action=$argv[1]??''; $opts=options($argv);
if (isset($opts['authorization-package'])) {
    if (getenv('FMONITOR_CLEAN_STAND_TEST_MODE')==='1' || getenv('FMONITOR_CLEAN_STAND_RECORDING_DRIVER')!==false) emit('RECORDING_DRIVER_FORBIDDEN');
    if(!isset($opts['evidence-root']))emit('AUTHORIZATION_REQUIRED');realRun($opts['authorization-package'],$opts['evidence-root'],$opts);
}
if (!isset($opts['manifest'],$opts['authorization'],$opts['evidence-root'])) emit('AUTHORIZATION_REQUIRED');
if (getenv('FMONITOR_CLEAN_STAND_TEST_MODE')!=='1') emit('AUTHORIZATION_REQUIRED');
$driverPath=getenv('FMONITOR_CLEAN_STAND_RECORDING_DRIVER');
if (!is_string($driverPath) || $driverPath==='') emit('AUTHORIZATION_REQUIRED');
$manifest=readDocument($opts['manifest'],'MANIFEST_MALFORMED');
$auth=readDocument($opts['authorization'],'AUTHORIZATION_MALFORMED');
$driver=readDocument($driverPath,'RECORDING_DRIVER_MALFORMED');
$evidence=$opts['evidence-root']; if (!is_dir($evidence)) mkdir($evidence,0700,true);
$ledger=$evidence.'/clean-stand-operations.jsonl'; $tracePath=$evidence.'/external-effects.jsonl';
admission($manifest,$auth,$opts,$driver); authorizationReplayGuard($ledger,$auth,$action);
if ($action==='preflight') { appendRecord($ledger,['event'=>'PREFLIGHT_OK','operationId'=>$auth['operationId'],'authorizationId'=>$auth['authorizationId']]); emit('CLEAN_STAND_PREFLIGHT_OK',[],0); }
if ($action!=='run') emit('ACTION_INVALID');
$final=$evidence.'/contract-verified.json';
if (is_file($final)) { $fact=readDocument($final,'EVIDENCE_MALFORMED'); emit('CLEAN_STAND_CONTRACT_VERIFIED',['facts'=>$fact['facts']],0); }
$existing=records($tracePath); $completed=array_column($existing,'call');
foreach (ORDERED_CALLS as $index=>$call) {
    if (($driver['interruptAt']??null)===$call) emit('OUTCOME_UNKNOWN');
    if (!in_array($call,$completed,true)) appendRecord($tracePath,traceRow($call,$driver,$manifest,$auth,$index+1));
    if ($call==='postcreate.inspect') validatePostcreate($driver,$manifest);
    $event = match ($call) { 'postcreate.inspect'=>'TARGET_ATTESTED', 'database.create'=>'DATABASE_CREATED', 'schema.migrate'=>'MIGRATIONS_COMPLETED', 'users.provision'=>'USERS_PROVISIONED', default=>null };
    if ($event !== null && !in_array($event,array_column(records($ledger),'event'),true)) appendRecord($ledger,['event'=>$event,'operationId'=>$auth['operationId'],'authorizationId'=>$auth['authorizationId']]);
    if (($driver['failAt']??null)===$call) emit(in_array($call,['compose.create','database.create','runtime.prepare','schema.migrate','users.provision','runtime.start'],true)?'OUTCOME_UNKNOWN':'STEP_FAILED');
    if ($call==='evidence.accept') {
        $facts=deriveFacts($driver,$manifest,$auth);
        $record=['operationId'=>$auth['operationId'],'authorizationId'=>$auth['authorizationId'],'authorizationDigest'=>digest($opts['authorization']),'source'=>$manifest['source'],'image'=>$manifest['image'],'facts'=>$facts];
        if (($driver['interruptAt']??null)==='evidence.accept.before-rename') emit('OUTCOME_UNKNOWN');
        $tmp=$final.'.tmp'; file_put_contents($tmp,canonical($record)); $h=fopen($tmp,'rb'); if ($h!==false) { if(function_exists('fsync'))fsync($h); fclose($h); } rename($tmp,$final);
        if (($driver['interruptAt']??null)==='evidence.accept.after-rename') emit('OUTCOME_UNKNOWN');
        appendRecord($ledger,['event'=>'CONTRACT_VERIFIED','operationId'=>$auth['operationId'],'authorizationId'=>$auth['authorizationId']]);
        emit('CLEAN_STAND_CONTRACT_VERIFIED',['facts'=>$facts],0);
    }
}
emit('OUTCOME_UNKNOWN',[],70);

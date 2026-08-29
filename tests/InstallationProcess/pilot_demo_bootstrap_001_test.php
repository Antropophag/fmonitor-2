<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

// Specification: PILOT-DEMO-BOOTSTRAP-001 v0.1.
// Public seams: bin/fmonitor2-pilot-demo.php in a separate process, its printed
// loopback URL, and browser-shaped HTTP requests. SQL below only creates and
// removes the isolated database supplied to the bootstrap; business facts are
// observed exclusively through HTTP.

function pdbPort(): int
{
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
    if ($socket === false) throw new TestFailure('reserve loopback port: ' . $error);
    $name = stream_socket_get_name($socket, false);
    fclose($socket);
    if (!is_string($name) || preg_match('/:(\d+)$/D', $name, $match) !== 1) throw new TestFailure('reserved port');
    return (int) $match[1];
}

/** @return array{process:resource,pipes:array<int,resource>,lines:list<string>} */
function pdbStart(array $environment, ?string $verb = 'start'): array
{
    $command = ['/usr/bin/env', '-i'];
    foreach ($environment as $name => $value) $command[] = $name . '=' . $value;
    $command = [...$command, PHP_BINARY, 'bin/fmonitor2-pilot-demo.php'];
    if ($verb !== null) $command[] = $verb;
    $pipes = [];
    $process = proc_open($command, [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes, dirname(__DIR__, 2));
    if (!is_resource($process)) throw new TestFailure('demo CLI must start');
    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $lines = [];
    $buffer = '';
    $deadline = microtime(true) + 12;
    while (microtime(true) < $deadline && count($lines) < 3) {
        $read = [$pipes[1]]; $write = null; $except = null;
        if (@stream_select($read, $write, $except, 0, 100000) > 0) {
            $buffer .= (string) fread($pipes[1], 8192);
            while (($newline = strpos($buffer, "\n")) !== false) {
                $lines[] = substr($buffer, 0, $newline);
                $buffer = substr($buffer, $newline + 1);
            }
        }
        $status = proc_get_status($process);
        if (!$status['running']) break;
    }
    if (count($lines) !== 3) {
        $stderr = stream_get_contents($pipes[2]);
        pdbStop(compact('process', 'pipes', 'lines'));
        throw new TestFailure('ready banner within timeout; stdout=' . json_encode($lines) . ' stderr=' . $stderr);
    }
    return compact('process', 'pipes', 'lines');
}

function pdbStop(array $server): void
{
    if (is_resource($server['process'])) {
        $status = proc_get_status($server['process']);
        if ($status['running']) proc_terminate($server['process']);
        $deadline = microtime(true) + 3;
        while (proc_get_status($server['process'])['running'] && microtime(true) < $deadline) usleep(20000);
        if (proc_get_status($server['process'])['running']) proc_terminate($server['process'], 9);
    }
    foreach ([1,2] as $fd) if (isset($server['pipes'][$fd]) && is_resource($server['pipes'][$fd])) fclose($server['pipes'][$fd]);
    if (is_resource($server['process'])) proc_close($server['process']);
}

function pdbRun(array $environment, array $arguments, string $stdin = ''): array
{
    $command = ['/usr/bin/env', '-i'];
    foreach ($environment as $name => $value) $command[] = $name . '=' . $value;
    $command = [...$command, PHP_BINARY, 'bin/fmonitor2-pilot-demo.php', ...$arguments];
    $pipes=[]; $process=proc_open($command,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__,2));
    if (!is_resource($process)) throw new TestFailure('demo command starts');
    fwrite($pipes[0], $stdin); fclose($pipes[0]);
    stream_set_timeout($pipes[1], 8); stream_set_timeout($pipes[2], 8);
    $stdout=stream_get_contents($pipes[1]); $stderr=stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    return ['exitCode'=>proc_close($process),'stdout'=>$stdout,'stderr'=>$stderr];
}

function pdbRequest(int $port, string $method, string $path, array $headers = [], string $body = ''): array
{
    $socket=stream_socket_client("tcp://127.0.0.1:$port",$errno,$error,2);
    if ($socket===false) throw new TestFailure("HTTP connect: $error");
    $request="$method $path HTTP/1.1\r\nHost: 127.0.0.1:$port\r\nConnection: close\r\n";
    foreach($headers as$name=>$value)$request.="$name: $value\r\n";
    fwrite($socket,$request."\r\n".$body); stream_set_timeout($socket,5); $raw='';
    while(!feof($socket))$raw.=(string)fread($socket,65536); fclose($socket);
    [$head,$responseBody]=array_pad(explode("\r\n\r\n",$raw,2),2,'');
    $lines=explode("\r\n",$head); $statusLine=array_shift($lines);
    if (!is_string($statusLine)||preg_match('#^HTTP/1\.[01] (\d{3})#',$statusLine,$match)!==1) throw new TestFailure('HTTP response status');
    $responseHeaders=[]; foreach($lines as$line){if(!str_contains($line,':'))continue;[$name,$value]=explode(':',$line,2);$responseHeaders[strtolower($name)][]=trim($value);}
    return ['status'=>(int)$match[1],'headers'=>$responseHeaders,'body'=>$method==='HEAD'?'':$responseBody];
}

function pdbHeader(array $response,string $name):?string{return $response['headers'][strtolower($name)][0]??null;}
function pdbXpath(string $html):DOMXPath{$document=new DOMDocument();$prior=libxml_use_internal_errors(true);$ok=$document->loadHTML($html,LIBXML_NONET);libxml_clear_errors();libxml_use_internal_errors($prior);if(!$ok)throw new TestFailure('valid HTML');return new DOMXPath($document);}
function pdbValue(DOMXPath $xpath,string $name):string{$nodes=$xpath->query("//input[@name=".json_encode($name)."]");if($nodes===false||$nodes->length!==1)throw new TestFailure("one $name input");return $nodes->item(0)?->getAttribute('value')??'';}
function pdbText(array $response,string $literal,string $why):void{assertSameValue(true,str_contains(html_entity_decode(strip_tags($response['body']),ENT_QUOTES|ENT_HTML5,'UTF-8'),$literal),$why.' contains '.$literal);}
function pdbPost(int $port,string $path,string $cookie,array $fields):array{$body=http_build_query($fields,'','&',PHP_QUERY_RFC3986);return pdbRequest($port,'POST',$path,['Cookie'=>$cookie,'Origin'=>"http://127.0.0.1:$port",'Sec-Fetch-Site'=>'same-origin','Content-Type'=>'application/x-www-form-urlencoded; charset=UTF-8','Content-Length'=>(string)strlen($body)],$body);}
function pdbFollow303(int $port,array $response,string $cookie,string $expected):array{assertSameValue(303,$response['status'],'command uses PRG');assertSameValue($expected,pdbHeader($response,'location'),'command redirect');return pdbRequest($port,'GET',$expected,['Cookie'=>$cookie]);}
function pdbRows(mysqli $db,string $sql):array{$result=$db->query($sql);$rows=[];while($row=$result->fetch_assoc())$rows[]=$row;return $rows;}
function pdbTree(string $root):array{if(!is_dir($root))return[];$rows=[];$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));foreach($iterator as$item){$relative=substr($item->getPathname(),strlen($root)+1);$rows[$relative]=[$item->isLink()?'link':'file',$item->isLink()?readlink($item->getPathname()):hash_file('sha256',$item->getPathname())];}ksort($rows);return$rows;}
function pdbFailure(array $result,array $secrets,string $why):void{assertSameValue(true,$result['exitCode']!==0,$why.' fails');assertSameValue(false,str_contains($result['stdout'],"FMonitor 2.0 pilot:"),$why.' prints no ready banner');foreach($secrets as$secret)assertSameValue(false,str_contains($result['stdout'].$result['stderr'],$secret),$why.' redacts secret');}

$token=bin2hex(random_bytes(6));
$database='t_pdb001_'.$token;
$adminHost=getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1';
$adminPort=(int)(getenv('FMONITOR_TEST_DB_PORT')?:'23306');
$adminUser=getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root';
$adminPassword=getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_demo_local';
$demoUser=getenv('FMONITOR_TEST_DB_USER')?:$adminUser;
$demoPassword=getenv('FMONITOR_TEST_DB_PASSWORD')?:$adminPassword;
$home=dirname(__DIR__,3).'/.pilot-demo-test-homes/'.$token;
$port=pdbPort(); $server=null; $db=null;

try {
    if (!mkdir($home,0700,true)) throw new TestFailure('task-owned test home');
    $db=new mysqli($adminHost,$adminUser,$adminPassword,'',$adminPort);$db->query('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_bin');
    $environment=['HOME'=>$home,'PATH'=>'/usr/bin:/bin','FMONITOR_DEMO_PORT'=>(string)$port,'FMONITOR_DEMO_DB_HOST'=>$adminHost,'FMONITOR_DEMO_DB_PORT'=>(string)$adminPort,'FMONITOR_DEMO_DB_NAME'=>$database,'FMONITOR_DEMO_DB_USER'=>$demoUser,'FMONITOR_DEMO_DB_PASSWORD'=>$demoPassword];

    foreach([[['start','extra'],'extra'],[['unknown'],'unknown'],[['start','start'],'repeated']]as[$arguments,$label]){$invalid=pdbRun($environment,$arguments,"ignored\n");assertSameValue([64,"{\"ok\":false,\"reason\":\"CONFIGURATION_INVALID\"}\n",''],[$invalid['exitCode'],$invalid['stdout'],$invalid['stderr']],$label.' CLI argument exact redacted rejection');}
    foreach(['01024','1023','65536',' 8092','8092x']as$invalidPort){$bad=$environment;$bad['FMONITOR_DEMO_PORT']=$invalidPort;$result=pdbRun($bad,['status']);assertSameValue([64,"{\"ok\":false,\"reason\":\"CONFIGURATION_INVALID\"}\n",''],[$result['exitCode'],$result['stdout'],$result['stderr']],'noncanonical/range port '.$invalidPort);}

    $occupied=stream_socket_server("tcp://127.0.0.1:$port",$errno,$error);if($occupied===false)throw new TestFailure('occupy selected demo port');$bindFailure=pdbRun($environment,['start']);fclose($occupied);pdbFailure($bindFailure,[$database,$demoPassword,$home],'occupied port');

    $server=pdbStart($environment,null);
    assertSameValue(["FMonitor 2.0 pilot: http://127.0.0.1:$port/pilot/objects",'User: sidorov@shlz.ru · business time: 2026-08-29T12:00:00+03:00','Stop: Ctrl+C · reset: php bin/fmonitor2-pilot-demo.php reset'],$server['lines'],'exact ready banner after public smoke');
    $status=pdbRun($environment,['status']);$statusJson=json_decode(trim($status['stdout']),true);
    assertSameValue(0,$status['exitCode'],'status succeeds');
    assertSameValue(['ok','running','url','generation','state'],array_keys($statusJson??[]),'status exact keys');
    assertSameValue([true,true,"http://127.0.0.1:$port/pilot/objects",1,'ready'],array_values($statusJson),'running ready status');
    $duplicate=pdbRun($environment,['start']);assertSameValue(true,$duplicate['exitCode']!==0,'second start is rejected');assertSameValue(false,str_contains($duplicate['stdout'].$duplicate['stderr'],$demoPassword),'already-running response is redacted');assertSameValue(true,str_contains($duplicate['stdout'],'ALREADY_RUNNING'),'exact already-running reason');
    foreach(['reset','cleanup']as$verb)pdbFailure(pdbRun($environment,[$verb]),[$database,$demoPassword,$home],$verb.' while server runs');

    $queue=pdbRequest($port,'GET','/pilot/objects');assertSameValue(200,$queue['status'],'queue available after banner');
    $queueXpath=pdbXpath($queue['body']);assertSameValue(1,(int)$queueXpath->evaluate("count(//a[@href='/pilot/objects/4512'])"),'exactly one imported object link');
    assertSameValue(1,(int)$queueXpath->evaluate("count(//link[@rel='stylesheet' and @href='/pilot/assets/shlz.css'])"),'queue links actual shlz stylesheet');assertSameValue(1,(int)$queueXpath->evaluate("count(//link[@rel='stylesheet' and @href='/pilot/assets/pilot.css'])"),'queue links pilot stylesheet');
    assertSameValue(404,pdbRequest($port,'GET','/pilot/objects/4999')['status'],'non-pilot legacy object is not imported');
    assertSameValue(false,str_contains($queue['body'],'app/demo'),'public shell never exposes app/demo');
    foreach(['/pilot/assets/shlz.css'=>dirname(__DIR__,3).'/shlz-ui/packages/styles/shlz.css','/pilot/assets/pilot.css'=>dirname(__DIR__,2).'/app/PilotHttp/pilot.css']as$css=>$source){$asset=pdbRequest($port,'GET',$css);assertSameValue(200,$asset['status'],'configured CSS '.$css);assertSameValue(hash_file('sha256',$source),hash('sha256',$asset['body']),'serves exact production CSS '.$css);}
    $cardInitial=pdbRequest($port,'GET','/pilot/objects/4512');pdbText($cardInitial,'Требуется распоряжение','initial card');assertSameValue(1,(int)pdbXpath($cardInitial['body'])->evaluate("count(//a[@href='/pilot/objects/4512/assignment-order/prepare'])"),'canonical prepare link');
    assertSameValue($queue['body'],pdbRequest($port,'GET','/pilot/objects')['body'],'repeated launch-smoke GET is read-only');

    $tables=array_column(pdbRows($db,"SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME"),'TABLE_NAME');$processTables=array_values(array_filter($tables,static fn(string $name):bool=>str_contains($name,'fm2_')));assertSameValue(8,count($processTables),'production migrations create exact v4 process table count');assertSameValue(3,count(array_filter($tables,static fn(string $name):bool=>preg_match('/_(fm_maintable|users|users_roles)$/D',$name)===1)),'legacy-shaped importer source tables');$caseTable=array_values(array_filter($processTables,static fn(string $name):bool=>str_ends_with($name,'fm2_installation_cases')))[0]??'';$prefix=substr($caseTable,0,-strlen('fm2_installation_cases'));assertSameValue(true,preg_match('/^fm2d_[0-9a-f]{8}_g1_$/D',$prefix)===1,'generation process prefix contract');foreach(['fm2_assignment_orders','fm2_order_installers','fm2_order_artifacts','fm2_process_tasks','fm2_process_events','fm2_workforce_catalog','fm2_process_user_capabilities']as$suffix)assertSameValue(true,in_array($prefix.$suffix,$tables,true),'production v4 table '.$suffix);assertSameValue([['n'=>'1']],pdbRows($db,"SELECT COUNT(*) n FROM `{$prefix}fm2_installation_cases`"),'production importer creates one initial case');assertSameValue([['n'=>'0']],pdbRows($db,"SELECT COUNT(*) n FROM `{$prefix}fm2_process_events`"),'bootstrap does not synthesize process events');

    $preparePath='/pilot/objects/4512/assignment-order/prepare';$prepare=pdbRequest($port,'GET',$preparePath);$px=pdbXpath($prepare['body']);
    assertSameValue(1,(int)$px->evaluate("count(//input[@name='installerTabIds[]' and @value='1042'])"),'Ivanov selectable');
    assertSameValue(1,(int)$px->evaluate("count(//input[@name='installerTabIds[]' and @value='2088'])"),'Petrov selectable');
    assertSameValue(1,(int)$px->evaluate("count(//input[@name='controlEngineerUserId' and @value='73' and @checked])"),'engineer 73 prefilled');
    assertSameValue(1,(int)$px->evaluate("count(//button[@type='submit' and not(@disabled)] | //input[@type='submit' and not(@disabled)])"),'prepare submit enabled');
    $spoof=pdbRequest($port,'GET',$preparePath,['Remote-User'=>'attacker@example.test','X-Remote-User'=>'attacker@example.test','Authorization'=>'Basic ZmFrZTpmYWtl']);assertSameValue(200,$spoof['status'],'spoof identity headers do not replace configured actor');pdbText($spoof,'Сидоров Сергей Сергеевич','configured actor survives spoofed headers');
    $setCookie=pdbHeader($prepare,'set-cookie');if(!is_string($setCookie))throw new TestFailure('browser receives session cookie');$cookie=explode(';',$setCookie,2)[0];
    $card=pdbFollow303($port,pdbPost($port,$preparePath,$cookie,['csrfToken'=>pdbValue($px,'csrfToken'),'processRevision'=>pdbValue($px,'processRevision'),'installerTabIds'=>['1042'],'controlEngineerUserId'=>'73','controlEngineerConfirmed'=>'yes']),$cookie,'/pilot/objects/4512');
    foreach(['Распоряжение подготовлено','Версия 1','Иванов Иван Иванович','Анна Волкова','Скачать распоряжение','Скачать приложение']as$text)pdbText($card,$text,'prepared card');
    $artifacts=[];foreach(['order','appendix']as$kind){$path="/pilot/objects/4512/assignment-orders/1/artifacts/$kind";$download=pdbRequest($port,'GET',$path,['Cookie'=>$cookie]);assertSameValue(200,$download['status'],'artifact download '.$kind);assertSameValue('text/html',pdbHeader($download,'content-type'),'production HTML artifact media type '.$kind);assertSameValue(true,strlen($download['body'])>0,'artifact nonempty '.$kind);assertSameValue(true,str_starts_with((string)pdbHeader($download,'content-disposition'),'attachment;'),'artifact attachment '.$kind);$artifacts[$kind]=$download['body'];}
    $cx=pdbXpath($card['body']);$registration='/pilot/objects/4512/assignment-orders/1/registration';
    $card=pdbFollow303($port,pdbPost($port,$registration,$cookie,['csrfToken'=>pdbValue($cx,'csrfToken'),'processRevision'=>pdbValue($cx,'processRevision'),'assignmentOrderVersion'=>'1','registrationNumber'=>'12-Р']),$cookie,'/pilot/objects/4512');
    foreach(['12-Р','Зарегистрировано в 1С ДО','Открыть работы']as$text)pdbText($card,$text,'registered card');
    foreach($artifacts as$kind=>$bytes)assertSameValue($bytes,pdbRequest($port,'GET',"/pilot/objects/4512/assignment-orders/1/artifacts/$kind",['Cookie'=>$cookie])['body'],'registration immediately preserves artifact '.$kind);
    $cx=pdbXpath($card['body']);$card=pdbFollow303($port,pdbPost($port,'/pilot/objects/4512/open',$cookie,['csrfToken'=>pdbValue($cx,'csrfToken'),'processRevision'=>pdbValue($cx,'processRevision'),'assignmentOrderVersion'=>'1','actualStartDate'=>'2026-08-29']),$cookie,'/pilot/objects/4512');
    foreach(['В работе','2026-08-29','Сидоров Сергей Сергеевич','Чек-лист: Доступен','Инженеру строительного контроля: провести первую инспекцию объекта.','Ответственный: Анна Волкова']as$text)pdbText($card,$text,'opened card');
    $queue=pdbRequest($port,'GET','/pilot/objects',['Cookie'=>$cookie]);foreach(['В работе','Инженеру: провести первую инспекцию']as$text)pdbText($queue,$text,'updated queue');

    pdbStop($server);$server=null;$server=pdbStart($environment);
    $persisted=pdbRequest($port,'GET','/pilot/objects/4512');foreach(['В работе','12-Р','2026-08-29','Ответственный: Анна Волкова']as$text)pdbText($persisted,$text,'restart persistence');
    foreach($artifacts as$kind=>$bytes)assertSameValue($bytes,pdbRequest($port,'GET',"/pilot/objects/4512/assignment-orders/1/artifacts/$kind")['body'],'restart artifact persistence '.$kind);

    pdbStop($server);$server=null;$stateRoot=$home.'/.local/state/fmonitor2/pilot-demo';$checkoutRoots=glob($stateRoot.'/*',GLOB_ONLYDIR)?:[];assertSameValue(1,count($checkoutRoots),'one checkout fingerprint state root');$partial=$checkoutRoots[0].'/generations/2';if(!mkdir($partial,0700,true))throw new TestFailure('interrupted inactive generation fixture');file_put_contents($partial.'/partial','INTERRUPTED-'.$token);$generationOneTree=pdbTree($stateRoot);$generationOneTables=[];foreach($tables as$table)$generationOneTables[$table]=pdbRows($db,'SELECT * FROM `'.$table.'`');$server=pdbStart($environment,'reset');
    $resetCard=pdbRequest($port,'GET','/pilot/objects/4512');pdbText($resetCard,'Требуется распоряжение','reset restores initial projection');assertSameValue(false,str_contains($resetCard['body'],'12-Р'),'reset does not carry final process facts');
    $statusJson=json_decode(trim(pdbRun($environment,['status'])['stdout']),true);assertSameValue(3,$statusJson['generation']??null,'reset skips interrupted inactive generation number');assertSameValue('INTERRUPTED-'.$token,file_get_contents($partial.'/partial'),'reset neither activates nor mistakes partial generation for success');
    foreach($generationOneTables as$table=>$rows)assertSameValue($rows,pdbRows($db,'SELECT * FROM `'.$table.'`'),'reset preserves old generation table '.$table);foreach($generationOneTree as$path=>$fingerprint)if(str_contains($path,'generations/1/'))assertSameValue($fingerprint,pdbTree($stateRoot)[$path]??null,'reset preserves old generation artifact/marker '.$path);
    pdbStop($server);$server=null;
    $foreignTable='fm2d_deadbeef_g99_foreign';$db->query("CREATE TABLE `$foreignTable`(sentinel VARCHAR(30) NOT NULL)");$db->query("INSERT INTO `$foreignTable` VALUES ('FOREIGN-ROW-$token')");$foreignPath=$stateRoot.'/foreign-'.$token;if(!mkdir($foreignPath,0700,true))throw new TestFailure('foreign containment fixture');file_put_contents($foreignPath.'/marker','FOREIGN-PATH-'.$token);$cleanup=pdbRun($environment,['cleanup']);assertSameValue(0,$cleanup['exitCode'],'cleanup succeeds');assertSameValue(true,str_contains($cleanup['stdout'],'2'),'cleanup reports both generations');assertSameValue(true,str_contains(mb_strtolower($cleanup['stdout']),'восстанов'),'material cleanup states bootstrap recovery is impossible');assertSameValue([['sentinel'=>'FOREIGN-ROW-'.$token]],pdbRows($db,"SELECT * FROM `$foreignTable`"),'cleanup refuses foreign prefix/rows');assertSameValue('FOREIGN-PATH-'.$token,file_get_contents($foreignPath.'/marker'),'cleanup refuses foreign path/marker');
    $absent=json_decode(trim(pdbRun($environment,['status'])['stdout']),true);assertSameValue([false,'absent'],[$absent['running']??null,$absent['state']??null],'cleanup leaves absent state');
    assertSameValue(true,is_dir($home),'cleanup containment preserves task home');
    echo "PASS PILOT-DEMO-BOOTSTRAP-001 public launch, walkthrough, persistence, reset and cleanup\n";
} finally {
    if (is_array($server)) pdbStop($server);
    if ($db instanceof mysqli) { try{$db->query('DROP DATABASE IF EXISTS `'.$database.'`');}catch(Throwable){} $db->close(); }
    if (is_dir($home)) { $iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($home,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);foreach($iterator as$item){$path=$item->getPathname();$item->isLink()||$item->isFile()?unlink($path):rmdir($path);}rmdir($home);$parent=dirname($home);if(is_dir($parent)&&count(scandir($parent)?:[])===2)rmdir($parent); }
}

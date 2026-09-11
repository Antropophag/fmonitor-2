<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
/** YII2-CANONICAL-MIGRATIONS-001 A4: real Yii process observes canonical DB lock. */
$root = dirname(__DIR__, 2); $token = bin2hex(random_bytes(5)); $database = 't_ycm_lock_' . $token; $prefix = 'lock_';
$host = getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1'; $port = (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306); $user = getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root'; $password = getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local';
$admin = new mysqli($host, $user, $password, '', $port); $admin->set_charset('utf8mb4');
function ycmLockRun(array $environment, string $root): array { $pipes=[]; $process=proc_open([PHP_BINARY,$root.'/bin/yii','schema-migrate/run','--interactive=0'],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root,$environment); if(!is_resource($process))throw new TestFailure('SETUP_FAILURE process'); $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);return[proc_close($process),$out,$err]; }
try {
    $admin->query("CREATE DATABASE `{$database}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"); $holder = new mysqli($host,$user,$password,$database,$port); $holder->set_charset('utf8mb4');
    $lockName=hash('sha256',$database."\0".$prefix."\0canonical-migrations");$statement=$holder->prepare('SELECT GET_LOCK(?,0)');$statement->bind_param('s',$lockName);$statement->execute();assertSameValue('1',(string)$statement->get_result()->fetch_column(),'fixture owns exact canonical lock');$statement->close();
    $environment=['PATH'=>(string)getenv('PATH'),'FMONITOR_DB_HOST'=>$host,'FMONITOR_DB_PORT'=>(string)$port,'FMONITOR_DB_NAME'=>$database,'FMONITOR_DB_USER'=>$user,'FMONITOR_DB_PASSWORD'=>$password,'FMONITOR_PROCESS_TABLE_PREFIX'=>$prefix];
    assertSameValue([75,"{\"ok\":false,\"reason\":\"MIGRATION_LOCK_UNAVAILABLE\"}\n",''],ycmLockRun($environment,$root),'INTENDED_RED: contended real Yii process returns canonical bounded lock outcome.');
    assertSameValue([], $holder->query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()')->fetch_all(MYSQLI_ASSOC), 'Contended Yii process creates no tables.');
    $holder->query('SELECT RELEASE_LOCK('."'".$holder->real_escape_string($lockName)."'".')');$holder->close();
    [$exit,, $stderr]=ycmLockRun($environment,$root);assertSameValue([0,''],[$exit,$stderr],'Same Yii command succeeds after lock release.');
    echo "PASS: YII2-CANONICAL-MIGRATIONS-001 Yii lock serialization\n";
} finally { try{$admin->query("DROP DATABASE IF EXISTS `{$database}`");}finally{$admin->close();} }

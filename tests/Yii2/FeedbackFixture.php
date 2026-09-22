<?php
declare(strict_types=1);
require_once __DIR__.'/UserAccessFixture.php';

function feedbackBuildFile(UserAccessFixture $f, string $identity, string $name = ''): string
{
    $path = $f->artifacts.'/feedback-build-'.($name !== '' ? $name : hash('sha256', $identity));
    if (is_file($path)) chmod($path, 0644);
    file_put_contents($path, $identity."\n");
    chmod($path, 0444);
    return $path;
}
final class FeedbackIdentityFilesystemProbe
{
    public array $calls=[];
    private int $statCount=0;
    public function __construct(private bool $rebind, private bool $handleMutation = false) {}
    public function open(string $path): mixed {$this->calls[]=['open',$path];$h=fopen('php://temp','w+b');fwrite($h,str_repeat('e',64)."\n");rewind($h);return$h;}
    public function stat(mixed $handle): array {$this->calls[]=['fstat',++$this->statCount];$changed=$this->handleMutation&&$this->statCount===2;return['mode'=>$changed?0100644:0100444,'nlink'=>$changed?2:1,'size'=>$changed?66:65,'dev'=>7,'ino'=>$changed?12:11,'mtime'=>$changed?14:13,'ctime'=>$changed?18:17];}
    public function read(mixed $handle,int $length): string {$this->calls[]=['read',$length];return fread($handle,$length);}
    public function pathStat(string $path): array {$this->calls[]=['lstat',$path];return['mode'=>0100444,'nlink'=>1,'size'=>65,'dev'=>7,'ino'=>$this->rebind?12:11,'mtime'=>13,'ctime'=>17];}
    public function close(mixed $handle): void {$this->calls[]=['close'];fclose($handle);}
}
function feedbackOwner(UserAccessFixture $f, string $identity = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', ?string $buildFile = null, ?object $filesystem = null): object
{
    assertSameValue(true, class_exists(FMonitor2\YiiRuntime\FeedbackApplication::class), 'INTENDED_RED FEEDBACK-001 application seam missing');
    $e = $f->environment();
    $db = new yii\db\Connection(['dsn'=>'mysql:host='.$e['FMONITOR_DB_HOST'].';port='.$e['FMONITOR_DB_PORT'].';dbname='.$e['FMONITOR_DB_NAME'], 'username'=>$e['FMONITOR_DB_USER'], 'password'=>$e['FMONITOR_DB_PASSWORD'], 'charset'=>'utf8mb4']);
    $config=['db'=>$db, 'tablePrefix'=>$f->p, 'buildIdentityFile'=>$buildFile ?? feedbackBuildFile($f, $identity)];if($filesystem!==null)$config['buildIdentityFilesystem']=$filesystem;
    return new FMonitor2\YiiRuntime\FeedbackApplication($config);
}
function feedbackFacts(UserAccessFixture $f): array
{
    return [$f->rows('fm2_feedback'), $f->rows('fm2_feedback_results')];
}
function feedbackInput(string $html, string $name): string
{
    $dom = new DOMDocument(); @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
    foreach ($dom->getElementsByTagName('input') as $input) if ($input->getAttribute('name') === $name) return $input->getAttribute('value');
    throw new TestFailure('Missing feedback input '.$name);
}
function feedbackUuid(int $n): string { return sprintf('00000000-0000-4000-8000-%012d', $n); }
function feedbackParallel(UserAccessFixture $f, array $calls): array
{
    $jobs=[];
    $buildFile=feedbackBuildFile($f, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'parallel');
    // Test-owned table lock forces both public calls to wait at their first feedback persistence access.
    $f->db->query("LOCK TABLES {$f->p}fm2_feedback WRITE, {$f->p}fm2_feedback_results WRITE");
    try {
        foreach ($calls as $call) {
            $process=proc_open([PHP_BINARY,__DIR__.'/feedback_worker.php'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$f->root);
            if (!is_resource($process)) throw new TestFailure('SETUP_FAILURE feedback worker');
            fwrite($pipes[0],json_encode(['environment'=>$f->environment(),'prefix'=>$f->p,'buildIdentityFile'=>$buildFile,'call'=>$call],JSON_THROW_ON_ERROR));fclose($pipes[0]);
            $jobs[]=[$process,$pipes];
        }
        $deadline=microtime(true)+10;$waiting=0;
        do {
            $rows=$f->db->query('SHOW PROCESSLIST')->fetch_all(MYSQLI_ASSOC);
            $waiting=count(array_filter($rows,fn($r)=>$r['db']===$f->auth->database&&str_contains((string)$r['State'],'Waiting')&&str_contains((string)$r['Info'],'fm2_feedback')));
            if ($waiting===count($calls)) break;
            usleep(10000);
        } while (microtime(true)<$deadline);
        assertSameValue(count($calls),$waiting,'both public calls overlap at locked feedback persistence boundary');
    } finally {$f->db->query('UNLOCK TABLES');}
    $out=[];
    foreach ($jobs as [$process,$pipes]) {
        $stdout=stream_get_contents($pipes[1]);$stderr=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
        assertSameValue(0,proc_close($process),'concurrent owner worker succeeds: '.$stderr);
        $out[]=json_decode($stdout,true,flags:JSON_THROW_ON_ERROR);
    }
    return $out;
}

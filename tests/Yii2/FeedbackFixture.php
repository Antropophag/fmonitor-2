<?php
declare(strict_types=1);
require_once __DIR__.'/UserAccessFixture.php';

function feedbackOwner(UserAccessFixture $f, string $version = '2.0'): object
{
    assertSameValue(true, class_exists(FMonitor2\YiiRuntime\FeedbackApplication::class), 'INTENDED_RED FEEDBACK-001 application seam missing');
    $e = $f->environment();
    $db = new yii\db\Connection(['dsn'=>'mysql:host='.$e['FMONITOR_DB_HOST'].';port='.$e['FMONITOR_DB_PORT'].';dbname='.$e['FMONITOR_DB_NAME'], 'username'=>$e['FMONITOR_DB_USER'], 'password'=>$e['FMONITOR_DB_PASSWORD'], 'charset'=>'utf8mb4']);
    return new FMonitor2\YiiRuntime\FeedbackApplication(['db'=>$db, 'tablePrefix'=>$f->p, 'appVersion'=>$version]);
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
    // Test-owned table lock forces both public calls to wait at their first feedback persistence access.
    $f->db->query("LOCK TABLES {$f->p}fm2_feedback WRITE, {$f->p}fm2_feedback_results WRITE");
    try {
        foreach ($calls as $call) {
            $process=proc_open([PHP_BINARY,__DIR__.'/feedback_worker.php'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$f->root);
            if (!is_resource($process)) throw new TestFailure('SETUP_FAILURE feedback worker');
            fwrite($pipes[0],json_encode(['environment'=>$f->environment(),'prefix'=>$f->p,'call'=>$call],JSON_THROW_ON_ERROR));fclose($pipes[0]);
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

<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/autoload.php';

use FMonitor2\InstallationProcess\JobsSchemaMigration;
use FMonitor2\Jobs\{JobHandlerRuntime,JobsRuntimeConfiguration,MariaDbOrderDocumentLinksScheduler};

$host=getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1';$port=(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306);
$user=getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root';$pass=getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local';
$name='t_doc_schedule_'.bin2hex(random_bytes(5));$p='ds_';$admin=new mysqli($host,$user,$pass,'',$port);$db=null;
try{
    $admin->query("CREATE DATABASE `$name` DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");$db=new mysqli($host,$user,$pass,$name,$port);JobsSchemaMigration::apply($db,$p);
    assertSameValue(true,class_exists(MariaDbOrderDocumentLinksScheduler::class),'INTENDED_RED hourly document scheduler missing');
    $scheduler=new MariaDbOrderDocumentLinksScheduler($db,$p);
    $one=$scheduler->tick('2026-09-15T07:00:00.000000Z');$repeat=$scheduler->tick('2026-09-15T07:59:59.000000Z');
    assertSameValue(['2026-09-15T10',true],[$one['slot'],$one['created']],'one Moscow hourly slot');
    assertSameValue([$one['jobId'],false],[$repeat['jobId'],$repeat['created']],'repeat same slot no duplicate');
    $later=$scheduler->tick('2026-09-15T11:05:00.000000Z');
    assertSameValue(['2026-09-15T14',true,3],[$later['slot'],$later['created'],$later['skippedSlots']],'pause schedules only current slot');
    assertSameValue(2,(int)$db->query("SELECT COUNT(*) FROM {$p}fm2_jobs WHERE job_type='bitrix.order-document-links.sync'")->fetch_column(),'no backlog jobs');
    $job=$db->query("SELECT payload_json,actor_json FROM {$p}fm2_jobs WHERE job_id=".(int)$later['jobId'])->fetch_assoc();
    assertSameValue([['scheduleSlot'=>'2026-09-15T14'],['id'=>'bitrix-order-document-links-hourly-v1','type'=>'system']],[json_decode($job['payload_json'],true,512,JSON_THROW_ON_ERROR),json_decode($job['actor_json'],true,512,JSON_THROW_ON_ERROR)],'minimal safe job payload');

    $syncCalls=0;$configuration=(new ReflectionClass(JobsRuntimeConfiguration::class))->newInstanceWithoutConstructor();
    $outcome=JobHandlerRuntime::handle(['jobType'=>'bitrix.order-document-links.sync','payloadVersion'=>1,'payload'=>['scheduleSlot'=>'2026-09-15T14']],$configuration,static function()use(&$syncCalls):array{$syncCalls++;return['status'=>'published','count'=>2];});
    assertSameValue(['completed',['published'=>2],1],[$outcome['status'],$outcome['result'],$syncCalls],'existing worker dispatches same sync composition once');
    $handler=(string)file_get_contents(dirname(__DIR__,2).'/app/Jobs/JobHandlerRuntime.php');foreach(['INSERT ','UPDATE ','DELETE ','->query(']as$forbidden)assertSameValue(false,str_contains($handler,$forbidden),'worker handler owns no links persistence');
    echo"PASS: BITRIX-ORDER-DOCUMENT-LINKS-001 hourly schedule\n";
}finally{if($db instanceof mysqli)$db->close();$admin->query("DROP DATABASE IF EXISTS `$name`");$admin->close();}

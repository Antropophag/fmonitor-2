<?php

declare(strict_types=1);

require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalDatabaseSetupV1.php';

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalEvidenceReaderConfig;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalEvidenceReaderFactory;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalSchemaMigration;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalVerificationDatabaseFixture;
use FMonitor2\Tests\Support\AssignmentOrderOriginalDatabaseSetupV1 as Contract;

// Specification: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v14, independent evidence construction.
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$host=getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1';$port=(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306);$user=getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root';$password=getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local';$token=bin2hex(random_bytes(6));$database='t_aoou_reader_'.$token;$prefix='reader_';$admin=new mysqli($host,$user,$password,'',$port);$control=sys_get_temp_dir().'/aoou-reader-'.$token;$root=$control.'/private';$passwordFile=$control.'/password';$safeLog=$control.'/safe.log';$db=null;$reader=null;
try{$admin->query("CREATE DATABASE `{$database}` CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci");mkdir($control,0700,true);mkdir($root,0700,true);file_put_contents($passwordFile,$password."\n");chmod($passwordFile,0600);file_put_contents($safeLog,'');chmod($safeLog,0600);$db=new mysqli($host,$user,$password,$database,$port);$db->set_charset('utf8mb4');FMonitor2\InstallationProcess\ProductionProcessSchemaMigration::apply($db,$prefix);FMonitor2\InstallationProcess\IdentityAccessSchemaMigration::apply($db,$prefix);FMonitor2\InstallationProcess\ProcessUserCapabilitiesSchemaMigration::apply($db,$prefix);FMonitor2\InstallationProcess\ProcessCommandCapabilitiesSchemaMigration::apply($db,$prefix);AssignmentOrderOriginalSchemaMigration::apply($db,$prefix);AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA($db,$prefix);
    if(!class_exists(AssignmentOrderOriginalEvidenceReaderFactory::class))throw new TestFailure('INTENDED_RED: approved AssignmentOrderOriginalEvidenceReaderFactory production seam is absent.');
    $reader=AssignmentOrderOriginalEvidenceReaderFactory::create(new AssignmentOrderOriginalEvidenceReaderConfig($host,$port,$database,$user,$passwordFile,$prefix,$root,$safeLog));
    assertSameValue('{"schema":"aoou-evidence-v1","caseId":4512,"orderId":81,"roots":[]}',$reader->domainCanonicalJson(4512,81),'Fresh seeded setup has no original facts.');
    foreach(['requestsCanonicalJson'=>['aoou-requests-v1','items'],'fingerprintsCanonicalJson'=>['aoou-fingerprints-v1','items'],'eventsCanonicalJson'=>['aoou-events-v1','items'],'safeAuditsCanonicalJson'=>['aoou-audits-v1','items'],'safeLogsCanonicalJson'=>['aoou-logs-v1','items']]as$method=>[$schema,$items])assertSameValue(json_encode(['schema'=>$schema,$items=>[]],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),$reader->{$method}(),"{$method} exact empty shape.");
    assertSameValue('{"schema":"aoou-blobs-v1","stages":[],"finalized":[]}',$reader->privateBlobsCanonicalJson(),'Fresh private storage has no blobs.');
    $process=['schema'=>'aoou-process-v1'];foreach(Contract::PROJECTIONS as$name=>[$digest])$process[$name]=$digest;assertSameValue(json_encode($process,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),$reader->unchangedProcessCanonicalJson(4512,81),'Reader derives all six independent seeded process digests.');
    $reader->close();$reader->close();$reader=null;fwrite(STDOUT,"ASSIGNMENT_ORDER_ORIGINAL_EVIDENCE_READER_OK\n");
}finally{if($reader!==null)try{$reader->close();}catch(Throwable){}if($db instanceof mysqli){try{AssignmentOrderOriginalVerificationDatabaseFixture::cleanupExampleA($db,$prefix);}catch(Throwable){}$db->close();}try{$admin->query("DROP DATABASE IF EXISTS `{$database}`");}catch(Throwable){}$admin->close();foreach([$safeLog,$passwordFile]as$file)if(is_file($file))unlink($file);if(is_dir($root))rmdir($root);if(is_dir($control))rmdir($control);}

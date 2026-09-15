<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
$db=new mysqli(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root',getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local',$argv[1],(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306));
$c=json_decode($argv[3],true,flags:JSON_THROW_ON_ERROR);$pdf=FMonitor2\Tests\Support\AssignmentOrderOriginalPdfCorpus::passiveClassic();$once=false;
$deadline=microtime(true)+10;while(!is_file($argv[4])){if(microtime(true)>$deadline)throw new RuntimeException('Barrier timeout');usleep(10000);}
$service=new FMonitor2\DeadlineTransferCertificate\DeadlineTransferCertificates($db,$argv[2],static fn():string=>'2026-09-14T12:00:00Z');
try{$r=$service->submit($c,static function()use(&$once,$pdf):?string{if($once)return null;$once=true;return$pdf;});echo $r['status'];}catch(DomainException $e){echo $e->getMessage();}finally{$db->close();}

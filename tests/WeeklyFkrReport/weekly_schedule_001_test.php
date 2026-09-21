<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require dirname(__DIR__,2).'/app/autoload.php';
use FMonitor2\Jobs\MariaDbWeeklyFkrScheduler;

if(getenv('FMONITOR_FIXTURE_REACHABILITY')==='weekly-schedule-fixture'){assertSameValue(3,count(array_unique(['2026-09-21T05:59:59.999999Z','2026-09-21T06:00:00.000000Z','2026-09-28T06:00:00.000000Z'])),'schedule boundary fixture distinct');echo "FIXTURE_REACHABLE: weekly-schedule-fixture\n";exit(0);}
assertSameValue(true,class_exists(MariaDbWeeklyFkrScheduler::class),'INTENTIONAL_RED: WEEKLY-FKR-ATTENTION-EMAIL-001 public weekly scheduler exists');
$calls=[];$scheduler=new MariaDbWeeklyFkrScheduler(static function(array$job)use(&$calls):array{$calls[]=$job;return['jobId'=>count($calls),'created'=>count($calls)===1];});
assertSameValue(['due'=>false],$scheduler->tick('2026-09-21T05:59:59.999999Z'),'A1 before Monday 09:00 Moscow is not due');
$at=$scheduler->tick('2026-09-21T06:00:00.000000Z');
assertSameValue(['due'=>true,'reportWeek'=>'2026-09-21','planStart'=>'2026-09-21','planEnd'=>'2026-09-27','progressStart'=>'2026-09-14','progressEnd'=>'2026-09-20','jobId'=>1,'created'=>true],$at,'A1 exact Moscow periods at due time');
$repeat=$scheduler->tick('2026-09-21T12:45:00.000000Z');
assertSameValue([$at['reportWeek'],false,1],[$repeat['reportWeek'],$repeat['created'],count($calls)],'A1 same-week repeat returns one logical enqueue');
assertSameValue(['weekly-fkr-report.generate',1,'weekly-fkr-report/v1/2026-09-21',['reportWeek'=>'2026-09-21','generatedAtUtc'=>'2026-09-21T06:00:00.000000Z']],[$calls[0]['jobType'],$calls[0]['payloadVersion'],$calls[0]['idempotencyKey'],$calls[0]['payload']],'A1 exact durable job identity/payload');
assertSameValue(['due'=>false],$scheduler->tick('2026-09-22T06:00:00.000000Z'),'A1 non-Monday does not create a report');
assertSameValue('2026-09-28',$scheduler->tick('2026-09-28T06:00:00.000000Z')['reportWeek'],'A1 next Monday is a distinct report week');
echo "PASS: WEEKLY-FKR-ATTENTION-EMAIL-001 weekly schedule\n";

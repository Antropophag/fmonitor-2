<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use FMonitor2\PilotHttp\ChecklistSync;
$db=mysqli_init();
foreach(['bad-prefix',str_repeat('a',26),'не-ascii_']as$prefix){try{new ChecklistSync($db,$prefix,'','2026-09-01T09:05:00+03:00');throw new TestFailure('Invalid production prefix must fail configuration before DB access: '.$prefix);}catch(InvalidArgumentException){}}
echo"PASS: INSPECTION-ITEM-COMPLETE-001 prefix validation before DB access\n";

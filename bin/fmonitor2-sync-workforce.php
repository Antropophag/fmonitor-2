<?php
declare(strict_types=1);

$argv = [__FILE__, 'workforce-sync/run', '--interactive=0'];
$_SERVER['argv'] = $argv;
ob_start();
$exit = require __DIR__.'/fmonitor2-yii.php';
$output = (string) ob_get_clean();
if ($exit === 0) echo $output;
else echo "{\"status\":\"failed\",\"reason\":\"SYNC_UNAVAILABLE\"}\n";
exit($exit === 0 ? 0 : 1);

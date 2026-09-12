<?php
declare(strict_types=1);

$argv = [__FILE__, 'workforce-sync/run', '--interactive=0'];
$_SERVER['argv'] = $argv;
exit(require __DIR__.'/fmonitor2-yii.php');

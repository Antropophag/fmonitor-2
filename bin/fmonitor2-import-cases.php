<?php
declare(strict_types=1);

$argv = [__DIR__ . '/yii', 'case-import/run', ...array_slice($argv, 1), '--interactive=0'];
$_SERVER['argv'] = $argv;
exit(require __DIR__ . '/fmonitor2-yii.php');

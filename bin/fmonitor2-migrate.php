<?php
declare(strict_types=1);

$argv = [$argv[0], 'schema-migrate/run', '--interactive=0'];
$_SERVER['argv'] = $argv;
exit(require __DIR__ . '/fmonitor2-yii.php');

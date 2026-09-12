<?php
declare(strict_types=1);
$argv=[dirname(__DIR__,2).'/bin/yii','case-import/run',...array_slice($argv,1),'--interactive=0'];
$_SERVER['argv']=$argv;
exit(require dirname(__DIR__,2).'/bin/fmonitor2-yii.php');

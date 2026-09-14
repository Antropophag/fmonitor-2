<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/vendor/autoload.php';
require dirname(__DIR__,2).'/vendor/yiisoft/yii2/Yii.php';
try {
    $input=json_decode(stream_get_contents(STDIN),true,flags:JSON_THROW_ON_ERROR);$e=$input['environment'];
    $db=new yii\db\Connection(['dsn'=>'mysql:host='.$e['FMONITOR_DB_HOST'].';port='.$e['FMONITOR_DB_PORT'].';dbname='.$e['FMONITOR_DB_NAME'],'username'=>$e['FMONITOR_DB_USER'],'password'=>$e['FMONITOR_DB_PASSWORD'],'charset'=>'utf8mb4']);
    $owner=new FMonitor2\YiiRuntime\FeedbackApplication(['db'=>$db,'tablePrefix'=>$input['prefix'],'appVersion'=>'2.0']);
    [$method,$args]=$input['call'];
    echo json_encode($owner->$method(...$args),JSON_THROW_ON_ERROR);
} catch (Throwable $e) { fwrite(STDERR,get_class($e)); exit(1); }

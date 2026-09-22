<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/vendor/autoload.php';
require dirname(__DIR__,2).'/vendor/yiisoft/yii2/Yii.php';
try {
    $input=json_decode(stream_get_contents(STDIN),true,flags:JSON_THROW_ON_ERROR);$e=$input['environment'];
    $db=new yii\db\Connection(['dsn'=>'mysql:host='.$e['FMONITOR_DB_HOST'].';port='.$e['FMONITOR_DB_PORT'].';dbname='.$e['FMONITOR_DB_NAME'],'username'=>$e['FMONITOR_DB_USER'],'password'=>$e['FMONITOR_DB_PASSWORD'],'charset'=>'utf8mb4']);
    if (($input['mode']??'') === 'dependencyProbe') {
        class_exists(FMonitor2\YiiRuntime\FeedbackApplication::class);class_exists(FMonitor2\InstallationProcess\MariaDbFeedback::class);class_exists(FMonitor2\IdentityAccess\MariaDbPilotAccessPolicy::class);class_exists(yii\base\UnknownPropertyException::class);
        $db->open();class_exists(yii\db\mysql\Schema::class);class_exists(yii\db\mysql\QueryBuilder::class);$db->getSchema();$db->createCommand('SELECT 1')->queryScalar();$warmTransaction=$db->beginTransaction();$warmTransaction->rollBack();
        foreach (['http','https','ftp'] as $wrapper) if (in_array($wrapper,stream_get_wrappers(),true)) stream_wrapper_unregister($wrapper);
        if (ini_set('open_basedir',$input['allowedRoot'].PATH_SEPARATOR.$input['dependencyRoot'])===false) throw new RuntimeException('probe open_basedir');
        set_error_handler(static function(int $severity,string $message):never{throw new ErrorException($message,0,$severity);});
        $owner=new FMonitor2\YiiRuntime\FeedbackApplication(['db'=>$db,'tablePrefix'=>$input['prefix'],'buildIdentityFile'=>$input['allowedRoot'].'/absent-build']);
        $saved=$owner->submit(9401,$input['requestId'],'Dependency probe','/pilot/calendar');$item=$owner->listing(9101)['items'][0];
        restore_error_handler();echo json_encode(['saved'=>$saved,'item'=>$item],JSON_THROW_ON_ERROR);exit(0);
    }
    $owner=new FMonitor2\YiiRuntime\FeedbackApplication(['db'=>$db,'tablePrefix'=>$input['prefix'],'buildIdentityFile'=>$input['buildIdentityFile']]);
    [$method,$args]=$input['call'];
    echo json_encode($owner->$method(...$args),JSON_THROW_ON_ERROR);
} catch (Throwable $e) { fwrite(STDERR,get_class($e).':'.$e->getMessage()); exit(1); }

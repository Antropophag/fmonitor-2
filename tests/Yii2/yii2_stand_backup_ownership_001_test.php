<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
$root=dirname(__DIR__,2);
$required=['app/YiiRuntime/Commands/StandBackupController.php','app/RuntimeRestore/StandBackupApplication.php','app/RuntimeRestore/StandBackupFilesystem.php'];
foreach($required as$path){assertSameValue(true,is_file($root.'/'.$path),'INTENTIONAL_RED: PHP owner '.$path);$source=file_get_contents($root.'/'.$path);assertSameValue(false,str_contains($source,'rapid-pilot'),'no rapid-pilot '.$path);assertSameValue(false,preg_match('/python(?:3)?\b|\.py\b/i',$source)===1,'no Python runtime '.$path);}
assertSameValue(false,is_file($root.'/tools/delivery/stand-backup.py'),'Python production seam absent');
$config=file_get_contents($root.'/config/yii/console.php');
assertSameValue(true,str_contains($config,"'stand-backup'"),'Yii2 command registered');
echo "PASS: YII2-STAND-BACKUP-CONSOLE-001 ownership\n";

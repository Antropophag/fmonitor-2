<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/autoload.php';

use FMonitor2\InstallationProcess\CanonicalMigrationApplication;

[$script,$host,$port,$database,$user,$password,$prefix,$role,$marker,$release] = $argv + array_fill(0,10,null);
if(!is_string($host)||!is_string($port)||!is_string($database)||!is_string($user)||!is_string($password)||!is_string($prefix)||!is_string($role)||!is_string($marker)||!is_string($release))exit(64);
$db=new mysqli($host,$user,$password,$database,(int)$port);$db->set_charset('utf8mb4');$preflight=0;$migration=0;
$result=CanonicalMigrationApplication::run($db,$prefix,[1=>static function(mysqli$connection,string$tablePrefix)use(&$migration,$role):array{$migration++;$statement=$connection->prepare('INSERT INTO migration_parallel_effects(actor) VALUES(?)');$statement->bind_param('s',$role);$statement->execute();return['applied'=>true];}],databasePreflight:static function()use(&$preflight,$role,$marker,$release):int{$preflight++;file_put_contents($marker,$role."\n",LOCK_EX);if($role==='winner'){$deadline=microtime(true)+8;while(!is_file($release)&&microtime(true)<$deadline)usleep(10000);if(!is_file($release))throw new RuntimeException('barrier timeout');}return 1;});
echo json_encode(['outcome'=>$result,'preflight'=>$preflight,'migration'=>$migration],JSON_THROW_ON_ERROR),"\n";$db->close();exit($result['exitCode']);

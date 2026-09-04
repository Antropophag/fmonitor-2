<?php
declare(strict_types=1);

const PROCESS_GROUP_EXEC_FAILURE="SETUP_FAILURE: process-group exec failed.\n";
if($argc<2||!function_exists('posix_setsid')||!function_exists('pcntl_exec')){fwrite(STDERR,PROCESS_GROUP_EXEC_FAILURE);exit(70);}
$command=(string)$argv[1];if($command===''||str_contains($command,"\0")){fwrite(STDERR,PROCESS_GROUP_EXEC_FAILURE);exit(70);}
$session=@posix_setsid();if(!is_int($session)||$session<=0){fwrite(STDERR,PROCESS_GROUP_EXEC_FAILURE);exit(70);}
$ready=@fopen('php://fd/3','wb');$release=@fopen('php://fd/4','rb');$pid=getmypid();
if(!is_resource($ready)||!is_resource($release)||$pid<=0||fwrite($ready,"READY $pid\n")===false||!fflush($ready)){fwrite(STDERR,PROCESS_GROUP_EXEC_FAILURE);exit(70);}
$line=fgets($release);fclose($ready);fclose($release);if($line!=="RELEASE $pid\n"){fwrite(STDERR,PROCESS_GROUP_EXEC_FAILURE);exit(70);}
@pcntl_exec($command,array_slice($argv,2));
fwrite(STDERR,PROCESS_GROUP_EXEC_FAILURE);exit(70);

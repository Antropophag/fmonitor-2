<?php
declare(strict_types=1);
$port=(int)($argv[1]??0);$ready=(string)($argv[2]??'');$hit=(string)($argv[3]??'');
$server=stream_socket_server("tcp://127.0.0.1:{$port}",$errno,$error);if($server===false)exit(70);
file_put_contents($ready,'ready',LOCK_EX);$client=@stream_socket_accept($server,8);if(is_resource($client)){file_put_contents($hit,'SOURCE_CONNECTION_ATTEMPTED',LOCK_EX);fclose($client);}fclose($server);

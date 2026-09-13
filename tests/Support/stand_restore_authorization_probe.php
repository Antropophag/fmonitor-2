<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/vendor/autoload.php';
use FMonitor2\RuntimeRestore\StandRestoreAuthorization;
if(!class_exists(StandRestoreAuthorization::class)){echo "{\"ok\":false,\"reason\":\"IMPLEMENTATION_MISSING\"}\n";exit(78);}
try{$a=StandRestoreAuthorization::load($argv[1]??'',$argv[2]??'',$argv[3]??'',$argv[4]??'');echo json_encode(['authorization_digest'=>$a->digest(),'ok'=>true],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";exit(0);}catch(Throwable){echo "{\"ok\":false,\"reason\":\"TARGET_INVALID\"}\n";exit(64);}

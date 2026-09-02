<?php
declare(strict_types=1);
spl_autoload_register(static function(string$c):void{$p='FMonitor\\IdentityAccess\\';if(str_starts_with($c,$p)){$f=dirname(__DIR__).'/app/IdentityAccess/'.substr($c,strlen($p)).'.php';if(is_file($f))require_once$f;}});
use FMonitor\IdentityAccess\{NativePilotSessionFilesystem,PilotSessionInspectorCliApplication,PilotSessionInspectorCliArguments,PilotSessionInspectorCliOutput,PilotSessionStorageInspector};
$arguments=new class(array_slice($argv,1))implements PilotSessionInspectorCliArguments{public function __construct(private array$v){}public function values():array{return$this->v;}};
$output=new class implements PilotSessionInspectorCliOutput{public function writeStdout(string$b):void{fwrite(STDOUT,$b);}public function writeStderr(string$b):void{fwrite(STDERR,$b);}};
exit((new PilotSessionInspectorCliApplication(new PilotSessionStorageInspector(),new NativePilotSessionFilesystem(),$arguments,$output))->run());

<?php
declare(strict_types=1);namespace FMonitor2\Tests\Support;
final class InspectorArgs implements \FMonitor\IdentityAccess\PilotSessionInspectorCliArguments{/** @param list<string> $v */public function __construct(private array$v){}public function values():array{return$this->v;}}
final class InspectorOutput implements \FMonitor\IdentityAccess\PilotSessionInspectorCliOutput{public string$stdout='';public string$stderr='';public function writeStdout(string$b):void{$this->stdout.=$b;}public function writeStderr(string$b):void{$this->stderr.=$b;}}
final class ThrowingInspection implements \FMonitor\IdentityAccess\PilotSessionStorageInspection{public int$calls=0;public function inspect(\FMonitor\IdentityAccess\PilotSessionStorageConfig$c,\FMonitor\IdentityAccess\PilotSessionFilesystemPrimitives$f):\FMonitor\IdentityAccess\PilotSessionInspectionResult{$this->calls++;throw new \RuntimeException('secret path /must/not/leak');}}

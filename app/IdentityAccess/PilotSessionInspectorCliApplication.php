<?php
declare(strict_types=1);
namespace FMonitor\IdentityAccess;
require_once __DIR__.'/PilotSessionInspectionTypes.php';
final class PilotSessionInspectorCliApplication
{
 public function __construct(private PilotSessionStorageInspection$inspector,private PilotSessionFilesystemPrimitives$filesystem,private PilotSessionInspectorCliArguments$arguments,private PilotSessionInspectorCliOutput$output){}
 public function run():int{$a=$this->arguments->values();if(count($a)!==4||$a[0]!=='--state-root'||$a[2]!=='--instance'||!is_string($a[1])||!is_string($a[3])||$a[1]===''||$a[1][0]!=='/'||!preg_match('/^[a-z0-9][a-z0-9_-]{0,31}$/D',$a[3])){$this->output->writeStderr("Usage: pilot-session-storage-inspect --state-root <absolute-root> --instance <valid-instance>\n");return 64;}try{$r=$this->inspector->inspect(new PilotSessionStorageConfig($a[1],$a[3]),$this->filesystem);}catch(\Throwable){$this->output->writeStderr("Inspection unavailable.\n");return 70;}if($r->status()!==PilotSessionInspectionStatus::OK){$this->output->writeStderr("Inspection unavailable.\n");return 65;}$this->output->writeStdout((string)$r->canonicalJson()."\n");return 0;}
}

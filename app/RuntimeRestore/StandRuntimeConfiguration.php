<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;

/** Canonical identifiers shared by the production stand backup and restore drivers. */
final readonly class StandRuntimeConfiguration
{
    private const JOB_TABLES=['jobs'=>'fm2_jobs','jobEvents'=>'fm2_job_events','outboxIntents'=>'fm2_outbox_intents','outboxAttempts'=>'fm2_outbox_attempt_events','workerHeartbeats'=>'fm2_worker_heartbeats'];
    private function __construct(private string $prefix,private string $artifactPath,private string $sessionPath){}
    public static function fromEnvironment(?array $environment=null): self
    {
        if($environment===null){$environment=getenv();if(!is_array($environment))throw new \InvalidArgumentException();}
        $prefix=$environment['FMONITOR_PROCESS_TABLE_PREFIX']??null;
        if(!is_string($prefix)||preg_match('/^[A-Za-z0-9_]{0,25}$/D',$prefix)!==1)throw new \InvalidArgumentException();
        $state=self::path($environment['FMONITOR_SESSION_STATE_ROOT']??null);
        $artifact=self::relative($state,self::path($environment['FMONITOR_ARTIFACT_STORAGE_ROOT']??null));
        $session=self::relative($state,self::path($environment['FMONITOR_YII_SESSION_PATH']??null));
        if($artifact===$session)throw new \InvalidArgumentException();
        return new self($prefix,$artifact,$session);
    }
    public function jobsTables(): array{$tables=[];foreach(self::JOB_TABLES as$name=>$logical)$tables[$name]=$this->prefix.$logical;return$tables;}
    public function artifactVolumePath(): string{return $this->artifactPath;}
    public function sessionVolumePath(): string{return $this->sessionPath;}
    public function restoreProbeTable(): string{return $this->prefix.'fm2_restore_rehearsal_probe';}
    private static function path(mixed $path): string
    {
        if(!is_string($path)||$path===''||$path[0]!=='/'||str_contains($path,"\0")||preg_match('#(?:^|/)(?:\.|\.\.)(?:/|$)#D',$path)===1||str_contains($path,'//')||($path!=='/'&&str_ends_with($path,'/')))throw new \InvalidArgumentException();
        return $path;
    }
    private static function relative(string $root,string $path): string
    {
        $prefix=rtrim($root,'/').'/';if(!str_starts_with($path,$prefix))throw new \InvalidArgumentException();
        $relative=substr($path,strlen($prefix));if($relative===''||str_contains($relative,'/'))throw new \InvalidArgumentException();return $relative;
    }
}

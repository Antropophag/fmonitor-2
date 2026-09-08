<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\InstallationProcess as I;

/** Native selection + private, task-owned original resources. */
final class SelectedOriginalFixture implements O\AssignmentOrderOriginalClock
{
    public readonly SelectionNativeFixture $selection;
    public string $control='';public string $privateRoot='';public string $safeLog='';
    public int $clockCalls=0;private bool $ownsControl=false;
    public function __construct(public readonly string $prefix = '')
    {
        $this->selection=new SelectionNativeFixture($prefix);
        try {
            $db=$this->selection->db;
            I\OriginalAttemptAuditSchemaMigration::apply($db,$prefix);
            foreach(['assignment_order.original.upload','assignment_order.original.correct'] as $cap)$this->selection->schema->insert($prefix.'fm2_process_user_capabilities',['user_id'=>18,'capability'=>$cap,'position_snapshot'=>null]);
            $directory=rtrim(sys_get_temp_dir(),DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'selected-original-fixture-'.bin2hex(random_bytes(12));
            if(!mkdir($directory,0700))throw new \RuntimeException('Private fixture creation failed.');$this->ownsControl=true;$this->control=(string)realpath($directory);
            $this->privateRoot=$this->control.'/private';if(!mkdir($this->privateRoot,0700))throw new \RuntimeException();
            $this->safeLog=$this->control.'/safe.log';file_put_contents($this->safeLog,'');chmod($this->safeLog,0600);
            file_put_contents($this->control.'/password',getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local');chmod($this->control.'/password',0600);
            O\AssignmentOrderOriginalFileStorage::validateRoot($this->privateRoot);
            \assertSameValue(O\AssignmentOrderOriginalPdfStatus::PASSIVE_PDF,(new O\FMonitorPassivePdfInspector())->inspect(self::pdf())->status,'approved real PDF parser prerequisite');
            \assertSameValue(O\AssignmentOrderOriginalAuthorizationStatus::ALLOWED,(new O\AssignmentOrderOriginalMariaDbAuthorizer($db,$prefix))->authorize(18,'assignment_order.original.upload'),'real original authorization prerequisite');
            $fresh=$this->fresh()->open();
            try{\assertSameValue(O\AssignmentOrderOriginalFreshReaderOpenStatus::OPENED,$fresh->status,'real fresh reader prerequisite');}
            finally{if($fresh->reader!==null)\assertSameValue(O\AssignmentOrderOriginalFreshReaderCloseStatus::CLOSED,$fresh->reader->close(),'fresh prerequisite closes');}
        }catch(\Throwable $e){$this->close();throw $e;}
    }
    public static function pdf():string {return AssignmentOrderOriginalPdfCorpus::passiveClassic();}
    public function nowUtc():string {$this->clockCalls++;return '2026-09-05T09:00:00Z';}
    public function config():O\AssignmentOrderOriginalProductionConfig {return new O\AssignmentOrderOriginalProductionConfig($this->privateRoot,$this->prefix,$this->safeLog);}
    public function fresh():O\AssignmentOrderOriginalFreshTerminalReaderFactory {
        return new O\AssignmentOrderOriginalMariaDbFreshTerminalReaderFactory(new O\AssignmentOrderOriginalFreshReaderConfig(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1',(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306),$this->selection->schema->source->name,getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root',$this->control.'/password',$this->prefix));
    }
    public function app(bool $production=false,?O\AssignmentOrderOriginalPersistenceObserver $observer=null):O\AssignmentOrderOriginalApplication {
        $factory=$production?O\ProductionAssignmentOrderOriginalFactory::class:O\AssignmentOrderSelectedOriginalVerificationFactory::class;$method=$production?'createForSelections':'create';
        \assertSameValue(true,is_callable([$factory,$method]),'RED_ASSERTION: selected-original constructor missing after native selection/storage/parser setup');
        return $production?$factory::$method($this->selection->db,$this->config(),$this->fresh()):$factory::$method($this->selection->db,$this->config(),$this->fresh(),$this,$observer);
    }
    public static function command(O\AssignmentOrderOriginalByteStream $stream,string $date='2026-09-04'):O\SubmitAssignmentOrderOriginalCommand {
        return new O\SubmitAssignmentOrderOriginalCommand('22222222-2222-4222-8222-000000000001',O\AssignmentOrderOriginalMode::INITIAL,4512,81,18,$date,true,null,null,null,null,new O\AssignmentOrderOriginalUpload($stream,'signed.pdf','application/pdf'));
    }
    public function privateFiles():array {
        $files=[];$walk=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->privateRoot,\FilesystemIterator::SKIP_DOTS));
        foreach($walk as $file){if($file->isFile())$files[substr($file->getPathname(),strlen($this->privateRoot)+1)]=hash_file('sha256',$file->getPathname());}ksort($files);return $files;
    }
    public function close():void {
        $errors=[];try{$this->selection->close();}catch(\Throwable $e){$errors[]=$e->getMessage();}
        if($this->ownsControl){try{
            $walk=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->control,\FilesystemIterator::SKIP_DOTS),\RecursiveIteratorIterator::CHILD_FIRST);
            foreach($walk as $file){$ok=$file->isDir()&&!$file->isLink()?rmdir($file->getPathname()):unlink($file->getPathname());if(!$ok)throw new \RuntimeException('Private cleanup failed.');}
            if(!rmdir($this->control))throw new \RuntimeException('Private fixture cleanup failed.');$this->ownsControl=false;
        }catch(\Throwable $e){$errors[]=$e->getMessage();}}
        if($errors!==[])throw new \TestFailure(implode(' | ',$errors));
    }
}

<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;
use FMonitor2\AssignmentOrderOriginal as O;
require_once dirname(__DIR__).'/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
final class OriginalUploadResources
{
    public readonly FreshOrderHttpResources $native;
    public readonly O\AssignmentOrderOriginalSubmissionQuery $query;
    public function __construct(private readonly EnvironmentSource $environment)
    {
        $this->native=new FreshOrderHttpResources($environment);
        $this->query=O\ProductionAssignmentOrderOriginalSubmissionFactory::create($this->native->db,$this->native->prefix);
    }
    private function value(string $name):string { return (string)($this->environment->read($name)?:''); }
    public function application():O\AssignmentOrderOriginalApplication
    {
        $c=new O\AssignmentOrderOriginalFreshReaderConfig($this->value('FMONITOR_DB_HOST'),(int)$this->value('FMONITOR_DB_PORT'),$this->value('FMONITOR_DB_NAME'),
            $this->value('FMONITOR_DB_USER'),$this->value('FMONITOR_ORIGINAL_DB_PASSWORD_FILE'),$this->native->prefix);
        $fresh=new O\AssignmentOrderOriginalMariaDbFreshTerminalReaderFactory($c);
        $probe=$fresh->open();if($probe->status!==O\AssignmentOrderOriginalFreshReaderOpenStatus::OPENED||$probe->reader===null)throw new \RuntimeException();
        if($probe->reader->close()!==O\AssignmentOrderOriginalFreshReaderCloseStatus::CLOSED)throw new \RuntimeException();
        return O\ProductionAssignmentOrderOriginalFactory::createForSelections($this->native->db,
            new O\AssignmentOrderOriginalProductionConfig($this->value('FMONITOR_ARTIFACT_STORAGE_ROOT'),$this->native->prefix,$this->value('FMONITOR_ORIGINAL_SAFE_LOG_FILE')),$fresh);
    }
    public function close():void { $this->native->close(); }
}

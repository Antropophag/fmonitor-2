<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime;

use FMonitor2\AssignmentOrderComposition as C;
use FMonitor2\AssignmentOrderOriginal as O;

require_once dirname(__DIR__).'/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';

final class PreopeningResources
{
    public readonly \mysqli $db;
    public readonly string $prefix;

    public function __construct(private readonly ?\yii\db\Connection $yiiDb = null)
    {
        $this->prefix=(string)(getenv('FMONITOR_PROCESS_TABLE_PREFIX')?:'');
        if((string)(getenv('FMONITOR_LEGACY_TABLE_PREFIX')?:'')!==$this->prefix)throw new \RuntimeException();
        $this->db=$this->connect();
    }
    public function connect():\mysqli
    {
        $port=filter_var(getenv('FMONITOR_DB_PORT'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>65535]]);
        $host=(string)getenv('FMONITOR_DB_HOST');$name=(string)getenv('FMONITOR_DB_NAME');
        if($port===false||$host===''||$name==='')throw new \RuntimeException();
        $db=new \mysqli($host,(string)getenv('FMONITOR_DB_USER'),(string)getenv('FMONITOR_DB_PASSWORD'),$name,$port);
        if(!$db->set_charset('utf8mb4')){$db->close();throw new \RuntimeException();}return$db;
    }
    public function portal():C\AssignmentOrderSelectionPortalQuery{return C\ProductionAssignmentOrderSelectionPortalFactory::create($this->db,$this->prefix);}
    public function selectAssignmentOrderComposition(C\SelectAssignmentOrderCompositionCommand $command): array
    {
        $application = C\ProductionAssignmentOrderCompositionFactory::create($this->db, $this->connect(...), $this->prefix);
        return (new C\SelectionResultSerializer())->serialize($application->selectAssignmentOrderComposition($command));
    }
    public function template(int$case,int$order,int$actor):array{return C\ProductionAssignmentOrderTemplateFactory::create($this->db,$this->prefix)->generateAssignmentOrderTemplate($case,$order,$actor);}
    public function submission():O\AssignmentOrderOriginalSubmissionQuery{return O\ProductionAssignmentOrderOriginalSubmissionFactory::create($this->db,$this->prefix);}
    public function originalAccess():O\AssignmentOrderOriginalAccessQuery
    {
        if ($this->yiiDb === null) throw new \RuntimeException('Yii database connection required for original admission.');
        return new O\MariaDbAssignmentOrderOriginalAccessQuery($this->db,$this->yiiDb,$this->prefix,$this->submission());
    }
    public function history():O\AssignmentOrderOriginalHistoryReader{return O\AssignmentOrderOriginalHistoryReaderFactory::create($this->db,(string)getenv('FMONITOR_ARTIFACT_STORAGE_ROOT'),$this->prefix);}
    public function original():O\AssignmentOrderOriginalApplication
    {
        $config=new O\AssignmentOrderOriginalFreshReaderConfig((string)getenv('FMONITOR_DB_HOST'),(int)getenv('FMONITOR_DB_PORT'),(string)getenv('FMONITOR_DB_NAME'),(string)getenv('FMONITOR_DB_USER'),(string)getenv('FMONITOR_ORIGINAL_DB_PASSWORD_FILE'),$this->prefix);
        $fresh=new O\AssignmentOrderOriginalMariaDbFreshTerminalReaderFactory($config);$probe=$fresh->open();
        if($probe->status!==O\AssignmentOrderOriginalFreshReaderOpenStatus::OPENED||$probe->reader===null||$probe->reader->close()!==O\AssignmentOrderOriginalFreshReaderCloseStatus::CLOSED)throw new \RuntimeException();
        return O\ProductionAssignmentOrderOriginalFactory::createForSelections($this->db,new O\AssignmentOrderOriginalProductionConfig((string)getenv('FMONITOR_ARTIFACT_STORAGE_ROOT'),$this->prefix,(string)getenv('FMONITOR_ORIGINAL_SAFE_LOG_FILE')),$fresh);
    }
    public function close():void{$this->db->close();}
}

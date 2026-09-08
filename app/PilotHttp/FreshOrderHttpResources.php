<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;
use FMonitor2\AssignmentOrderComposition as C;
final class FreshOrderHttpResources
{
    public readonly \mysqli $db;
    public readonly string $prefix;
    public readonly C\AssignmentOrderSelectionPortalQuery $query;
    public function __construct(private readonly EnvironmentSource $environment)
    {
        $this->prefix=$this->value('FMONITOR_PROCESS_TABLE_PREFIX');
        $legacy=$environment->read('FMONITOR_LEGACY_TABLE_PREFIX');
        if($legacy!==false&&$legacy!==$this->prefix)throw new \RuntimeException();
        $this->db=$this->connect();
        $this->query=C\ProductionAssignmentOrderSelectionPortalFactory::create($this->db,$this->prefix);
    }
    private function value(string $name):string { return (string)($this->environment->read($name)?:''); }
    public function connect():\mysqli
    {
        $port=\filter_var($this->value('FMONITOR_DB_PORT'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>65535]]);
        if($port===false||$this->value('FMONITOR_DB_HOST')===''||$this->value('FMONITOR_DB_NAME')==='')throw new \RuntimeException();
        $db=new \mysqli($this->value('FMONITOR_DB_HOST'),$this->value('FMONITOR_DB_USER'),$this->value('FMONITOR_DB_PASSWORD'),$this->value('FMONITOR_DB_NAME'),$port);
        if(!$db->set_charset('utf8mb4')){$db->close();throw new \RuntimeException();}return $db;
    }
    public function saveComposition(C\SelectAssignmentOrderCompositionCommand $command):array
    { return (new C\SelectionResultSerializer())->serialize(C\ProductionAssignmentOrderCompositionFactory::create($this->db,$this->connect(...),$this->prefix)->selectAssignmentOrderComposition($command)); }
    public function template(int $case,int $order,int $actor):array
    { return C\ProductionAssignmentOrderTemplateFactory::create($this->db,$this->prefix)->generateAssignmentOrderTemplate($case,$order,$actor); }
    public function user(int $actor):HttpUser
    { return (new MariaDbLocalUserProfile($this->db,$this->prefix))->read($actor)??throw new \RuntimeException(); }
    public function close():void { $this->db->close(); }
}

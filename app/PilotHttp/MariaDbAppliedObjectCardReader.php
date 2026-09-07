<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;
use FMonitor2\AssignmentOrderComposition as C;
final readonly class MariaDbAppliedObjectCardReader implements ObjectCardReader
{
    public function __construct(private \mysqli $db,private string $legacyPrefix,private string $prefix,private ObjectCardReader $previous) {}
    public function read(int $id):?array
    {
        $s=$this->db->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
        $s->execute([$this->prefix.'fm2_assignment_order_applications']);$exists=$s->get_result()->fetch_row()!==null;$s->close();
        if(!$exists)return $this->previous->read($id);
        $lookup=C\AssignmentOrderApplicationReaderFactory::create($this->db,$this->prefix)->readCurrent($id);
        if($lookup->status==='not_found'){ $card=$this->previous->read($id);if($card!==null){$q=$this->db->prepare('SELECT 1 FROM `'.$this->prefix.'fm2_pilot_object_details` WHERE object_id=? AND content_sha256=SHA2(payload_json,256)');$q->execute([$id]);if($q->get_result()->fetch_row()!==null)$card['dataOrigin']='migration_native';$q->close();}return $card;}
        if($lookup->status!=='found'||$lookup->value===null)throw new PilotHttpInfrastructureUnavailable();
        $v=$lookup->value;$a=$v['application'];
        $object=(new \FMonitor2\InstallationProcess\MariaDbLegacyInstallationObject($this->db,$this->legacyPrefix))->getInstallationObjectSnapshot($id);
        $s=$this->db->prepare('SELECT process_state,actual_start_date,opened_at,opened_by_user_id FROM `'.$this->prefix.'fm2_installation_cases` WHERE id=?');
        $s->execute([$a['caseId']]);$case=$s->get_result()->fetch_assoc();$s->close();if($case===null)throw new PilotHttpInfrastructureUnavailable();
        $opened=$case['actual_start_date']!==null&&$case['opened_at']!==null&&$case['opened_by_user_id']!==null;
        $installers=array_map(static fn($person)=>$person+['status'=>'employed'],$v['selectedInstallers']);
        return ['id'=>$id,'address'=>$object['address'],'entrance'=>$object['entrance'],'registrationNumber'=>$object['objectRegistrationNumber'],
            'plannedStartDate'=>$object['plannedStartDate'],'plannedFinishDate'=>$object['plannedFinishDate'],
            'status'=>$opened?'В работе':'Готов к открытию','applicationId'=>$a['applicationId'],
            'order'=>['version'=>$a['orderVersion'],'status'=>'applied','orderDate'=>$a['documentDate'],'preparedAt'=>$a['appliedAt'],'registrationNumber'=>null,
                'organizationType'=>count($installers)===1?'individual':'brigade','engineer'=>$v['selectedEngineer'],'installers'=>$installers],
            'controlEngineer'=>$v['selectedEngineer'],'opened'=>$opened,'actualStartDate'=>$case['actual_start_date'],'openedAt'=>$case['opened_at'],
            'openedByUserId'=>$opened?(int)$case['opened_by_user_id']:null,'actualStartDateUnknownAtCutover'=>false,'activeCaseProvenance'=>null,'dataOrigin'=>'native',
            'events'=>[['type'=>'Состав применён','occurredAt'=>$a['appliedAt'],'actorId'=>$a['appliedBy']]],'hasPtoAct'=>$object['ptoActDate']!==null];
    }
}

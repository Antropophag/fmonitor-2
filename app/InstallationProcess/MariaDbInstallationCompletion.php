<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;
use FMonitor2\InspectionEvidence\ProductionChecklistProgressFactory;

final class MariaDbInstallationCompletion
{
    public function __construct(private readonly \mysqli $db, private readonly string $prefix)
    {
        if (preg_match('/^[A-Za-z0-9_]*$/D', $prefix) !== 1) throw new \InvalidArgumentException('Invalid table prefix.');
    }

    public function record(int $objectId,int $actor,string $type,string $date,string $details,string $now):void
    {
        $capability=$type==='pto_act'?'installation.completion.pto.record':'installation.completion.declaration.record';
        $this->transaction(function()use($objectId,$actor,$type,$date,$details,$now,$capability):void{
            $this->authorize($actor,$capability);$case=$this->workingCase($objectId);
            if(ProductionChecklistProgressFactory::create($this->db,$this->prefix)->forCase($case)<85)throw new \DomainException('CHECKLIST_INCOMPLETE');
            $facts=$this->facts($case,true);
            if(isset($facts[$type]))throw new \DomainException('FACT_ALREADY_RECORDED');
            if($type==='declaration'&&!isset($facts['pto_act']))throw new \DomainException('PTO_REQUIRED');
            $this->validate($type,$date,$details,$now);
            $s=$this->db->prepare('INSERT INTO '.$this->table('fm2_pilot_completion_facts').'(installation_case_id,fact_type,fact_date,details,recorded_at,recorded_by_user_id)VALUES(?,?,?,?,?,?)');
            $s->bind_param('issssi',$case,$type,$date,$details,$now,$actor);$s->execute();
        });
    }

    public function correct(int $objectId,int $actor,int $factId,string $type,string $date,string $details,string $reason,string $now):void
    {
        $capability=$type==='pto_act'?'installation.completion.pto.correct':'installation.completion.declaration.correct';
        $this->transaction(function()use($objectId,$actor,$factId,$type,$date,$details,$reason,$now,$capability):void{
            $this->authorize($actor,$capability);$case=$this->workingCase($objectId);
            $reason=trim($reason);if($reason===''||mb_strlen($reason)>1000)throw new \DomainException('REASON_REQUIRED');
            $this->validate('pto_act',$date,'',$now);if($type==='declaration'&&$details!==''&&mb_strlen(trim($details))>500)throw new \DomainException('INVALID_FACT');
            $s=$this->db->prepare('SELECT id FROM '.$this->table('fm2_pilot_completion_facts').' WHERE id=? AND installation_case_id=? AND fact_type=? FOR UPDATE');
            $s->bind_param('iis',$factId,$case,$type);$s->execute();if($s->get_result()->fetch_row()===null)throw new \DomainException('FACT_NOT_FOUND');
            $q=$this->db->prepare('SELECT id,version_no FROM '.$this->table('fm2_pilot_completion_fact_corrections').' WHERE root_fact_id=? ORDER BY version_no DESC LIMIT 1 FOR UPDATE');
            $q->bind_param('i',$factId);$q->execute();$leaf=$q->get_result()->fetch_assoc();$version=$leaf===null?1:(int)$leaf['version_no']+1;$previous=$leaf===null?null:(int)$leaf['id'];$previousVersion=$leaf===null?null:(int)$leaf['version_no'];
            $effectiveDetails=$details===''?null:$details;
            $i=$this->db->prepare('INSERT INTO '.$this->table('fm2_pilot_completion_fact_corrections').'(root_fact_id,version_no,previous_correction_id,previous_version_no,fact_date,details,reason,recorded_at,recorded_by_user_id)VALUES(?,?,?,?,?,?,?,?,?)');
            $i->bind_param('iiiissssi',$factId,$version,$previous,$previousVersion,$date,$effectiveDetails,$reason,$now,$actor);$i->execute();
        });
    }

    /** @return array<string,array<string,mixed>> */
    public function facts(int $caseId,bool $lock=false):array
    {
        $sql='SELECT f.id,f.fact_type,COALESCE(c.fact_date,f.fact_date) fact_date,COALESCE((SELECT d.details FROM '.$this->table('fm2_pilot_completion_fact_corrections').' d WHERE d.root_fact_id=f.id AND d.details IS NOT NULL ORDER BY d.version_no DESC LIMIT 1),f.details) details,f.fact_date original_date,f.details original_details,c.version_no,c.reason,f.recorded_at,f.recorded_by_user_id FROM '.$this->table('fm2_pilot_completion_facts').' f LEFT JOIN '.$this->table('fm2_pilot_completion_fact_corrections').' c ON c.root_fact_id=f.id AND NOT EXISTS(SELECT 1 FROM '.$this->table('fm2_pilot_completion_fact_corrections').' n WHERE n.root_fact_id=f.id AND n.version_no>c.version_no) WHERE f.installation_case_id=? ORDER BY f.id'.($lock?' FOR UPDATE':'');
        $s=$this->db->prepare($sql);$s->bind_param('i',$caseId);$s->execute();$facts=[];foreach($s->get_result()->fetch_all(MYSQLI_ASSOC)as$row)$facts[$row['fact_type']]=$row;return$facts;
    }

    private function validate(string$type,string$date,string$details,string$now):void
    {
        $valid=preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D',$date,$m)===1&&checkdate((int)($m[2]??0),(int)($m[3]??0),(int)($m[1]??0))&&$date<=substr($now,0,10);
        if(!$valid||($type==='declaration'&&(trim($details)===''||mb_strlen(trim($details))>500)))throw new \DomainException('INVALID_FACT');
    }
    private function authorize(int$user,string$permission):void
    {
        $s=$this->db->prepare('SELECT 1 FROM '.$this->table('fm2_pilot_users').' u JOIN '.$this->table('fm2_pilot_user_roles').' ur ON ur.user_id=u.user_id JOIN '.$this->table('fm2_pilot_roles').' r ON r.role_id=ur.role_id JOIN '.$this->table('fm2_pilot_role_permissions').' rp ON rp.role_id=r.role_id WHERE u.user_id=? AND u.status=1 AND u.activation_state=\'active\' AND r.status=1 AND rp.permission=? LIMIT 1');
        $s->bind_param('is',$user,$permission);$s->execute();if($s->get_result()->fetch_row()===null)throw new \DomainException('ACTOR_NOT_AUTHORIZED');
    }
    private function workingCase(int$object):int
    {
        $s=$this->db->prepare('SELECT id,process_state FROM '.$this->table('fm2_installation_cases').' WHERE legacy_installation_object_id=? LIMIT 2 FOR UPDATE');$s->bind_param('i',$object);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);if(count($rows)!==1)throw new \DomainException('CASE_NOT_FOUND');if($rows[0]['process_state']!=='working')throw new \DomainException('CASE_NOT_WORKING');return(int)$rows[0]['id'];
    }
    private function transaction(callable$work):void{try{$this->db->begin_transaction();$work();$this->db->commit();}catch(\Throwable$e){$this->db->rollback();throw$e;}}
    private function table(string$name):string{return'`'.$this->prefix.$name.'`';}
}

<?php
declare(strict_types=1);

namespace FMonitor2\InspectionEvidence;

final readonly class MariaDbChecklistMutationFacts
{
    public function __construct(private \mysqli $db, private string $prefix) {}

    public function sectionReady(int $caseId, int $section, array $itemIds): bool
    {
        $marks=$this->db->prepare('SELECT COUNT(*) n FROM '.$this->table('fm2_checklist_operations')." o WHERE o.installation_case_id=? AND o.operation_type='item_completed' AND o.item_id IN(".implode(',',array_map('intval',$itemIds)).") AND o.id=(SELECT MAX(last.id) FROM ".$this->table('fm2_checklist_operations')." last WHERE last.installation_case_id=o.installation_case_id AND last.item_id=o.item_id AND last.operation_type IN('item_completed','completion_retracted'))");
        $marks->bind_param('i',$caseId);$marks->execute();
        if((int)$marks->get_result()->fetch_assoc()['n']!==count($itemIds))return false;
        $photos=$this->db->prepare('SELECT COUNT(*) n FROM '.$this->table('fm2_checklist_photos').' WHERE installation_case_id=? AND section_id=? AND revoked_at IS NULL');
        $photos->bind_param('ii',$caseId,$section);$photos->execute();
        return (int)$photos->get_result()->fetch_assoc()['n']>0;
    }

    public function itemCompleted(int $caseId, int $itemId): bool
    {
        $s=$this->db->prepare('SELECT operation_type FROM '.$this->table('fm2_checklist_operations')." WHERE installation_case_id=? AND item_id=? AND operation_type IN('item_completed','completion_retracted') ORDER BY id DESC LIMIT 1");
        $s->bind_param('ii',$caseId,$itemId);$s->execute();
        return ($s->get_result()->fetch_assoc()['operation_type']??null)==='item_completed';
    }

    public function mayRetractCompletion(int $caseId,int $itemId,string $original,int $actorId,bool $assigned):bool
    {
        $s=$this->db->prepare('SELECT operation_type,client_operation_id,actor_user_id FROM '.$this->table('fm2_checklist_operations')." WHERE installation_case_id=? AND item_id=? AND operation_type IN('item_completed','completion_retracted') ORDER BY id DESC LIMIT 1");
        $s->bind_param('ii',$caseId,$itemId);$s->execute();$r=$s->get_result()->fetch_assoc();
        return $r!==null&&$r['operation_type']==='item_completed'&&hash_equals((string)$r['client_operation_id'],$original)&&((int)$r['actor_user_id']===$actorId||$assigned);
    }

    public function legacyAssignedEngineer(int $caseId,int $actorId):bool
    {
        $s=$this->db->prepare('SELECT 1 FROM '.$this->table('fm2_assignment_orders')." WHERE installation_case_id=? AND control_engineer_user_id=? AND status='registered' ORDER BY version_no DESC LIMIT 1");
        $s->bind_param('ii',$caseId,$actorId);$s->execute();
        return $s->get_result()->fetch_assoc()!==null;
    }

    public function lastPhotoOfCompletedSection(int $caseId,int $section):bool
    {
        $s=$this->db->prepare('SELECT (SELECT COUNT(*) FROM '.$this->table('fm2_checklist_photos').' WHERE installation_case_id=? AND section_id=? AND revoked_at IS NULL) photos,(SELECT operation_type FROM '.$this->table('fm2_checklist_operations')." WHERE installation_case_id=? AND section_id=? AND operation_type IN('section_completed','completion_retracted') ORDER BY id DESC LIMIT 1) state");
        $s->bind_param('iiii',$caseId,$section,$caseId,$section);$s->execute();$r=$s->get_result()->fetch_assoc();
        return (int)$r['photos']<=1&&$r['state']==='section_completed';
    }

    public function applicationStorageExists():bool
    {
        $name=$this->prefix.'fm2_assignment_order_applications';
        $s=$this->db->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
        $s->bind_param('s',$name);$s->execute();
        return $s->get_result()->fetch_row()!==null;
    }

    private function table(string $name):string{return '`'.$this->prefix.$name.'`';}
}

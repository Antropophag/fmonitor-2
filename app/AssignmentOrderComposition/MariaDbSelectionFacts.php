<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
use FMonitor2\InstallationProcess as I;

final readonly class MariaDbSelectionFacts implements SelectionDependencyReader,SelectionAuthorizer
{
    public function __construct(private MariaDbSelectionSql $sql) {}
    public function authorize(UserId $actor,SelectionCapability $capability): SelectionAuthorization
    {
        try {
            if(!$this->sql->idle())throw new \RuntimeException();
            $allowed=$this->sql->snapshot(function()use($actor,$capability){
                $s=$this->sql;$p=$s->prefix;
                $hasLocalFamily=false;
                foreach(I\IdentityAccessDefinitionSchemaMigration::tables() as $table) {
                    if(I\MariaDbSchemaInspector::tableExists($s->db,$p.$table)){$hasLocalFamily=true;break;}
                }
                if($hasLocalFamily) {
                    if(!I\IdentityAccessSchemaMigration::isCompleteCompatible($s->db,$p))throw new \RuntimeException();
                    $rows=$s->rows('SELECT u.user_id FROM '.$s->table('fm2_pilot_users').' u JOIN '.$s->table('fm2_pilot_user_roles').' ur ON ur.user_id=u.user_id JOIN '.$s->table('fm2_pilot_roles').' r ON r.role_id=ur.role_id JOIN '.$s->table('fm2_pilot_role_permissions')." rp ON rp.role_id=r.role_id WHERE u.user_id=? AND u.status=1 AND BINARY u.activation_state='active' AND r.status=1 AND BINARY r.code IN ('fkr_operator','manager') AND BINARY rp.permission=BINARY ?",[$actor->value,$capability->value]);
                } else {
                    $rows=$s->rows('SELECT u.id FROM '.$s->table('users').' u JOIN '.$s->table('users_roles').' r ON r.id=u.role_id JOIN '.$s->table('fm2_process_user_capabilities').' c ON c.user_id=u.id WHERE u.id=? AND u.status=1 AND r.status=1 AND BINARY c.capability=BINARY ?',[$actor->value,$capability->value]);
                }
                return $rows!==[];
            });
            return new SelectionAuthorization($allowed?SelectionAuthorizationStatus::ALLOWED:SelectionAuthorizationStatus::DENIED);
        }catch(\Throwable){return new SelectionAuthorization(SelectionAuthorizationStatus::UNAVAILABLE);}
    }
    public function findCaseByObject(InstallationObjectId $id): SelectionCaseLookup
    { try{return $this->sql->snapshot(fn()=>$this->caseRows($id->value));}catch(\Throwable){return SelectionCaseLookup::unavailable();} }
    public function caseRows(int $object,?int $case=null): SelectionCaseLookup
    {
        $s=$this->sql;$cases=$s->rows('SELECT id,legacy_installation_object_id FROM '.$s->table('fm2_installation_cases').' WHERE legacy_installation_object_id=?',[$object]);
        if($cases===[])return SelectionCaseLookup::notFound();if(count($cases)!==1)throw new \RuntimeException();
        $id=MariaDbSelectionSql::number($cases[0]['id']);if($case!==null&&$id!==$case)throw new \RuntimeException();
        $objects=$s->rows('SELECT workdatefinish,ptoactdate FROM '.$s->table('fm_maintable').' WHERE id=?',[$object]);if(count($objects)!==1)throw new \RuntimeException();
        $completed=self::date($objects[0]['workdatefinish'])!==null;$pto=self::date($objects[0]['ptoactdate']);
        foreach($s->rows('SELECT fact_type,fact_date FROM '.$s->table('fm2_pilot_completion_facts').' WHERE installation_case_id=?',[$id]) as $row) {
            if(!in_array($row['fact_type'],['pto_act','declaration'],true)||!SelectionScalar::date($row['fact_date']))throw new \RuntimeException();
            if($row['fact_type']==='declaration')$completed=true;else $pto=$row['fact_date'];
        }
        return SelectionCaseLookup::found(new SelectionCasePayload($id,$object,$completed,$pto));
    }
    private static function date(?string $value): ?string
    {
        if($value===null||trim($value)===''||preg_match('/^0+$/D',trim($value))||str_starts_with($value,'0000-00-00'))return null;
        if(!SelectionScalar::date($value))throw new \RuntimeException();return $value;
    }
    public function findInstallers(InstallerTabIdSet $ids,SelectionInstant $at): SelectionInstallerBatchLookup
    {
        try{return $this->sql->snapshot(function()use($ids){
            $found=[];$missing=[];$s=$this->sql;
            foreach($ids->ascendingUniqueIds as $id){$rows=$s->rows('SELECT * FROM '.$s->table('fm2_workforce_catalog').' WHERE installer_tab_id=?',[$id->value]);if($rows===[]){$missing[]=$id->value;continue;}if(count($rows)!==1)throw new \RuntimeException();$r=$rows[0];$proof=$this->fullProof($r);
                $found[]=new InstallerSnapshot((int)$r['installer_tab_id'],$r['fio'],$r['position'],$r['employment_status'],$r['employed_from'],$r['employed_to'],$r['workforce_source'],$r['workforce_source_updated_at'],$r['authority_system']??null,$r['delivery_system']??null,isset($r['delivery_person_id'])?(int)$r['delivery_person_id']:null,$r['reconciliation_state']??null,$proof);}
            return SelectionInstallerBatchLookup::found(new InstallerBatchPayload($found,$missing));
        });}catch(\Throwable){return SelectionInstallerBatchLookup::unavailable();}
    }
    private function fullProof(array$r):?array
    {
        if($r['employed_from']!==null)return null;foreach(['last_successful_sync_run_id','last_successful_sync_at']as$k)if(!isset($r[$k]))return null;$s=$this->sql;
        $runs=$s->rows('SELECT * FROM '.$s->table('fm2_workforce_sync_runs').' WHERE run_id=?',[$r['last_successful_sync_run_id']]);$meta=$s->rows('SELECT * FROM '.$s->table('fm2_workforce_sync_metadata').' WHERE singleton_id=1');if(count($runs)!==1||count($meta)!==1)return null;$run=$runs[0];$m=$meta[0];
        if($run['status']!=='completed'||$run['failure_code']!==null||$run['observed_at']!==$r['last_successful_sync_at']||$m['last_successful_run_id']!==$r['last_successful_sync_run_id']||$m['last_successful_at']!==$r['last_successful_sync_at'])return null;
        return['runId'=>$run['run_id'],'observedAt'=>$run['observed_at'],'normalizedChecksum'=>$run['normalized_checksum'],'deliveredCount'=>(int)$run['delivered_count'],'pageCount'=>(int)$run['page_count']];
    }
    public function findEngineer(UserId $id,SelectionInstant $at): SelectionEngineerLookup
    {
        try{return $this->sql->snapshot(function()use($id){
            $r=(new I\MariaDbProcessUserDirectory($this->sql->db,$this->sql->prefix,$this->sql->prefix))->findEngineerSnapshot($id->value);
            return $r===null?SelectionEngineerLookup::notFound():SelectionEngineerLookup::found(new EngineerSnapshot($r['userId'],$r['fullName'],$r['position']));
        });}catch(\Throwable){return SelectionEngineerLookup::unavailable();}
    }
}

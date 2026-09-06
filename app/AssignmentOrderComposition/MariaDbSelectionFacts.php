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
            $found=[];$missing=[];$catalog=new I\MariaDbWorkforceCatalog($this->sql->db,$this->sql->prefix);
            foreach($ids->ascendingUniqueIds as $id){$r=$catalog->findInstallerSnapshot($id->value);if($r===null){$missing[]=$id->value;continue;}
                $found[]=new InstallerSnapshot($r['tabId'],$r['fullName'],$r['position'],$r['status'],$r['employedFrom'],$r['employedTo'],$r['source'],$r['sourceUpdatedAt']);}
            return SelectionInstallerBatchLookup::found(new InstallerBatchPayload($found,$missing));
        });}catch(\Throwable){return SelectionInstallerBatchLookup::unavailable();}
    }
    public function findEngineer(UserId $id,SelectionInstant $at): SelectionEngineerLookup
    {
        try{return $this->sql->snapshot(function()use($id){
            $r=(new I\MariaDbProcessUserDirectory($this->sql->db,$this->sql->prefix,$this->sql->prefix))->findEngineerSnapshot($id->value);
            return $r===null?SelectionEngineerLookup::notFound():SelectionEngineerLookup::found(new EngineerSnapshot($r['userId'],$r['fullName'],$r['position']));
        });}catch(\Throwable){return SelectionEngineerLookup::unavailable();}
    }
}

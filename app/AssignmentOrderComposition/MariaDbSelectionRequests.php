<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
use FMonitor2\InstallationProcess\AssignmentOrderSelectionSchemaRequests as Contract;

final readonly class MariaDbSelectionRequests implements SelectionCloseableTerminalRequestReader
{
    public function __construct(private MariaDbSelectionSql $sql,private bool $ownsConnection=false) {}
    public function findTerminalRequest(SelectionRequestId $id): SelectionTerminalRequestLookup
    {
        try{if(!$this->sql->ready())return SelectionTerminalRequestLookup::unavailable();return $this->sql->snapshot(fn()=>$this->lockedFind($id));}
        catch(\Throwable){return SelectionTerminalRequestLookup::unavailable();}
    }
    public function lockedFind(SelectionRequestId $id): SelectionTerminalRequestLookup
    {
        $rows=$this->sql->rows('SELECT * FROM '.$this->sql->table('fm2_assignment_order_selection_requests').' WHERE request_id=?',[$id->value]);
        if($rows===[])return SelectionTerminalRequestLookup::notFound();if(count($rows)!==1)throw new \RuntimeException();$r=$rows[0];Contract::reason($r,false);
        $ids=json_decode($r['installer_tab_ids_json'],true,512,JSON_THROW_ON_ERROR);
        if(!is_array($ids)||!array_is_list($ids)||SelectionScalar::json($ids)!==$r['installer_tab_ids_json'])throw new \RuntimeException();
        $set=new InstallerTabIdSet(array_map(static function($n){if(!is_int($n))throw new \RuntimeException();return new InstallerTabId($n);},$ids));
        $intent=SelectionIntent::build(MariaDbSelectionSql::number($r['actor_user_id']),$r['control_engineer_user_id']===null?null:MariaDbSelectionSql::number($r['control_engineer_user_id']),MariaDbSelectionSql::number($r['expected_selection_revision'],0,4294967295),MariaDbSelectionSql::number($r['installation_object_id']),$set,AssignmentOrderCompositionMode::from($r['mode']));
        if(!SelectionIntent::valid($intent)||$intent->fingerprint!==$r['operation_fingerprint']||(string)$r['retryable']!=='0')throw new \RuntimeException();
        MariaDbSelectionSql::instant($r['terminal_at_utc']);
        if($r['status']==='selected') {
            $h=$this->sql->rows('SELECT * FROM '.$this->sql->table('fm2_assignment_order_selections').' WHERE assignment_order_id=?',[$r['assignment_order_id']]);
            if(count($h)!==1)throw new \RuntimeException();foreach(Contract::SUCCESS as $k=>$field)if((string)$r[$k]!== (string)$h[0][$field])throw new \RuntimeException();
            $p=new SelectionSuccessPayload(MariaDbSelectionSql::number($r['case_id']),MariaDbSelectionSql::number($r['assignment_order_id']),MariaDbSelectionSql::number($r['assignment_order_version'],1,65535),MariaDbSelectionSql::number($r['selection_revision'],1,4294967295),$r['composition_identity'],$r['composition_sha256'],$r['selection_date'],MariaDbSelectionSql::instant($r['selected_at_utc']));
            $result=SelectionResult::selected($id,$p);
        }else {
            foreach(Contract::SUCCESS as $k=>$_)if($r[$k]!==null)throw new \RuntimeException();
            $reason=AssignmentOrderCompositionReason::from($r['reason_code']);
            $result=$r['status']==='rejected'?SelectionResult::rejected($id,$reason):SelectionResult::conflict($id,$reason);
        }
        return SelectionTerminalRequestLookup::found(new SelectionTerminalRequestRecord($id,$intent,$result));
    }
    public function close(): void { if($this->ownsConnection)$this->sql->db->close(); }
}

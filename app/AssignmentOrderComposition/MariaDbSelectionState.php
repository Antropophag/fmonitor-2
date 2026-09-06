<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
use FMonitor2\InstallationProcess\AssignmentOrderSelectionSchemaComposition;

final readonly class MariaDbSelectionState
{
    public function __construct(private MariaDbSelectionSql $sql) {}
    public function read(int $case): SelectionStateLookup
    {
        try {
            $s=$this->sql;
            $registry=$s->rows('SELECT * FROM '.$s->table('fm2_assignment_order_identities').' WHERE installation_case_id=? ORDER BY order_version',[$case]);
            foreach($registry as $r)if($r['source_kind']!=='selection')throw new \RuntimeException();
            $headers=$s->rows('SELECT * FROM '.$s->table('fm2_assignment_order_selections').' WHERE installation_case_id=? ORDER BY selection_revision',[$case]);
            $members=$s->rows('SELECT m.* FROM '.$s->table('fm2_assignment_order_selection_members').' m JOIN '.$s->table('fm2_assignment_order_selections').' h ON h.assignment_order_id=m.assignment_order_id WHERE h.installation_case_id=? ORDER BY m.assignment_order_id,m.installer_tab_id',[$case]);
            // Shared schema proof expects lossless canonical string values, as its catalog reader supplies.
            $strings=static fn($rows)=>array_map(static fn($r)=>array_map(static fn($v)=>$v===null?null:(string)$v,$r),$rows);
            AssignmentOrderSelectionSchemaComposition::prove($strings($headers),$strings($members),$strings($registry));
            $latest=null;$accepted=null;
            foreach($headers as $h){
                $roots=$s->rows('SELECT * FROM '.$s->table('fm2_assignment_order_original_roots').' WHERE assignment_order_id=?',[$h['assignment_order_id']]);
                if(count($roots)>1)throw new \RuntimeException();$has=$roots!==[];
                if($has){$r=$roots[0];if((string)$r['installation_case_id']!==(string)$case||$r['composition_identity']!==$h['composition_identity']||$r['composition_sha256']!==$h['composition_sha256'])throw new \RuntimeException();
                    $leaf=$s->rows('SELECT root_original_id FROM '.$s->table('fm2_assignment_order_original_revisions').' WHERE revision_id=?',[$r['current_revision_id']]);if(count($leaf)!==1||$leaf[0]['root_original_id']!==$r['root_original_id'])throw new \RuntimeException();}
                $latest=new SelectionIdentitySummary(MariaDbSelectionSql::number($h['assignment_order_id']),MariaDbSelectionSql::number($h['order_version'],1,65535),MariaDbSelectionSql::number($h['selection_revision'],1,4294967295),$h['composition_identity'],$h['composition_sha256'],$has);if($has)$accepted=$latest;
            }
            return SelectionStateLookup::found(new SelectionStateSnapshot($latest,$latest!==null&&!$latest->hasAcceptedOriginal?$latest:null,$accepted,null,null));
        }catch(\Throwable){return SelectionStateLookup::unavailable();}
    }
}

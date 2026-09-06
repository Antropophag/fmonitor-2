<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\InstallationProcess as I;
require_once dirname(__DIR__).'/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';

/** Reads immutable composition and current object data within the owner's transaction. */
final readonly class MariaDbTemplateSource
{
    public function __construct(private MariaDbSelectionSql $sql) {}
    public function load(int $case,int $order):?array
    {
        $s=$this->sql;$config=$s->rows('SELECT DATABASE() name,@@character_set_connection charset');
        if(count($config)!==1||!is_string($config[0]['name'])||$config[0]['name']===''||$config[0]['charset']!=='utf8mb4')throw new \RuntimeException();
        $cases=$s->rows('SELECT id,legacy_installation_object_id FROM '.$s->table('fm2_installation_cases').' WHERE id=?',[$case]);if($cases===[])return null;if(count($cases)!==1)throw new \RuntimeException();
        $r=$s->rows('SELECT * FROM '.$s->table('fm2_assignment_order_identities').' WHERE assignment_order_id=?',[$order]);
        if($r===[]) {
            foreach(['fm2_assignment_order_selections'=>'assignment_order_id','fm2_assignment_orders'=>'id'] as $table=>$key)if($s->rows('SELECT '.$key.' FROM '.$s->table($table).' WHERE '.$key.'=?',[$order])!==[])throw new \RuntimeException();
            return null;
        }
        if(count($r)!==1||MariaDbSelectionSql::number($r[0]['assignment_order_id'])!==$order)throw new \RuntimeException();$registry=$r[0];
        if(MariaDbSelectionSql::number($registry['installation_case_id'])!==$case)return null;
        if($registry['source_kind']!=='selection')throw new \RuntimeException();$version=MariaDbSelectionSql::number($registry['order_version'],1,65535);
        $h=$s->rows('SELECT * FROM '.$s->table('fm2_assignment_order_selections').' WHERE assignment_order_id=?',[$order]);
        if(count($h)!==1||$s->rows('SELECT id FROM '.$s->table('fm2_assignment_orders').' WHERE id=?',[$order])!==[])throw new \RuntimeException();$header=$h[0];
        if(MariaDbSelectionSql::number($header['assignment_order_id'])!==$order||MariaDbSelectionSql::number($header['installation_case_id'])!==$case||MariaDbSelectionSql::number($header['order_version'],1,65535)!==$version)throw new \RuntimeException();
        $members=$s->rows('SELECT * FROM '.$s->table('fm2_assignment_order_selection_members').' WHERE assignment_order_id=? ORDER BY installer_tab_id',[$order]);
        O\AssignmentOrderRegisteredSelectionComposition::read($registry,$header,$members,$case,$order,$version);
        return ['objectId'=>MariaDbSelectionSql::number($cases[0]['legacy_installation_object_id']),'header'=>$header,'members'=>$members,'version'=>$version];
    }
    public function input(array $source,string $date):array
    {
        $object=(new I\MariaDbLegacyInstallationObject($this->sql->db,$this->sql->prefix))->getInstallationObjectSnapshot($source['objectId']);
        foreach(['address','entrance','objectRegistrationNumber'] as $key)if(!is_string($object[$key]??null)||!SelectionScalar::text($object[$key],500))throw new \RuntimeException();
        foreach(['plannedStartDate','plannedFinishDate'] as $key)if(!is_string($object[$key]??null)||!SelectionScalar::date($object[$key]))throw new \RuntimeException();
        $h=$source['header'];$members=array_map(static fn($m)=>['tabId'=>MariaDbSelectionSql::number($m['installer_tab_id']),'fullName'=>$m['fio_snapshot'],'position'=>$m['position_snapshot']],$source['members']);
        return ['assignmentOrderVersion'=>$source['version'],'assignmentOrderDate'=>$date,'organizationType'=>count($members)===1?'individual':'brigade','installationObjectSnapshot'=>$object,'installers'=>$members,
            'controlEngineer'=>['userId'=>MariaDbSelectionSql::number($h['control_engineer_user_id']),'fullName'=>$h['control_engineer_fio_snapshot'],'position'=>$h['control_engineer_position_snapshot']]];
    }
}

<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final class MariaDbAssignmentOrderCurrentEligibility
{
    public static function confirm(\mysqli $db,string $prefix,array $installerTabIds,int $engineerUserId,string $documentDate):string
    {
        try{$s=new MariaDbAssignmentOrderApplicationSql($db,$prefix);if($s->idle()||$installerTabIds===[]||$engineerUserId<1)return'dependency_unavailable';
            $local=self::exists($s,$prefix.'fm2_pilot_users');
            if($local)$engineer=$s->rows('SELECT u.user_id FROM '.$s->table('fm2_pilot_users').' u JOIN '.$s->table('fm2_pilot_user_roles').' ur ON ur.user_id=u.user_id JOIN '.$s->table('fm2_pilot_roles')." r ON r.role_id=ur.role_id WHERE u.user_id=? AND u.status=1 AND BINARY u.activation_state='active' AND r.status=1 AND BINARY r.code='construction_control_engineer' FOR UPDATE",[$engineerUserId]);
            else $engineer=$s->rows('SELECT u.id FROM '.$s->table('users').' u JOIN '.$s->table('users_roles').' r ON r.id=u.role_id JOIN '.$s->table('fm2_process_user_capabilities')." c ON c.user_id=u.id AND BINARY c.capability='construction_control_engineer' WHERE u.id=? AND u.status=1 AND r.status=1 FOR UPDATE",[$engineerUserId]);
            if($engineer===[])return'control_engineer_required';if(count($engineer)!==1)return'dependency_unavailable';
            $ids=array_values(array_unique(array_map('intval',$installerTabIds)));sort($ids,SORT_NUMERIC);if(count($ids)!==count($installerTabIds)||count($ids)>500||$ids[0]<1)return'dependency_unavailable';
            $marks=implode(',',array_fill(0,count($ids),'?'));$rows=$s->rows('SELECT * FROM '.$s->table('fm2_workforce_catalog').' WHERE installer_tab_id IN ('.$marks.') ORDER BY installer_tab_id FOR UPDATE',$ids);
            if(count($rows)!==count($ids))return'installer_not_employed';
            foreach($rows as$r){if($r['employment_status']!=='employed'||($r['reconciliation_state']??'delivered')!=='delivered'||($r['employed_from']!==null&&$r['employed_from']>$documentDate)||($r['employed_to']!==null&&$r['employed_to']<$documentDate))return'installer_not_employed';if($r['employed_from']===null&&!self::fullProof($s,$r))return'dependency_unavailable';}
            return'eligible';
        }catch(\Throwable){return'dependency_unavailable';}
    }
    private static function exists(MariaDbAssignmentOrderApplicationSql$s,string$t):bool{return(string)$s->rows('SELECT COUNT(*) n FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',[$t])[0]['n']==='1';}
    private static function fullProof(MariaDbAssignmentOrderApplicationSql$s,array$r):bool
    {foreach(['authority_system','delivery_system','delivery_person_id','last_successful_sync_run_id','last_successful_sync_at']as$k)if(!array_key_exists($k,$r)||$r[$k]===null)return false;if($r['authority_system']!=='1c_zup'||$r['delivery_system']!=='bitrix24'||(int)$r['delivery_person_id']<1)return false;$runs=$s->rows('SELECT * FROM '.$s->table('fm2_workforce_sync_runs').' WHERE run_id=? FOR UPDATE',[$r['last_successful_sync_run_id']]);$meta=$s->rows('SELECT * FROM '.$s->table('fm2_workforce_sync_metadata').' WHERE singleton_id=1 FOR UPDATE');if(count($runs)!==1||count($meta)!==1)return false;$run=$runs[0];return$meta[0]['last_successful_run_id']===$r['last_successful_sync_run_id']&&$meta[0]['last_successful_at']===$r['last_successful_sync_at']&&$run['status']==='completed'&&$run['failure_code']===null&&$run['observed_at']===$r['last_successful_sync_at']&&(int)$run['page_count']>=1&&(int)$run['delivered_count']>=1&&preg_match('/^[0-9a-f]{64}$/D',(string)$run['normalized_checksum'])===1;}
}

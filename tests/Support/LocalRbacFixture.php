<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;

use FMonitor2\InstallationProcess\IdentityAccessSchemaMigration;

final class LocalRbacFixture
{
    /** @param array<int,array{email:string,fullName?:string,status?:int,activation?:string,permissions?:list<string>,roleActive?:int}> $users */
    public static function install(\mysqli $db,array $users):void
    {
        $result=IdentityAccessSchemaMigration::apply($db,'');
        if(isset($result['reason']))throw new \RuntimeException('Local RBAC fixture schema conflict');
        foreach($users as$id=>$fixture){
            $status=$fixture['status']??1;$activation=$fixture['activation']??'active';$email=$db->real_escape_string($fixture['email']);$fullName=$db->real_escape_string($fixture['fullName']??'Fixture '.$id);
            $db->query("INSERT INTO fm2_pilot_users(user_id,full_name,email,phone,status,activation_state,session_version,source_updated_at)VALUES($id,'$fullName','$email','',$status,'$activation',1,'2026-09-02T00:00:00+03:00')");
            $role=900000+$id;$active=$fixture['roleActive']??1;$code='fixture_'.$id;
            $db->query("INSERT INTO fm2_pilot_roles(role_id,code,name,description,status,source_updated_at)VALUES($role,'$code','Fixture role $id','Integration fixture',$active,'2026-09-02T00:00:00+03:00')");
            $db->query("INSERT INTO fm2_pilot_user_roles(user_id,role_id,origin,assigned_at,assigned_by_user_id)VALUES($id,$role,'fixture','2026-09-02T00:00:00+03:00',NULL)");
            foreach($fixture['permissions']??[]as$permission){$permission=$db->real_escape_string($permission);$db->query("INSERT INTO fm2_pilot_role_permissions(role_id,permission)VALUES($role,'$permission')");}
        }
    }
    /** @return list<string> */
    public static function tables():array{return['fm2_pilot_users','fm2_pilot_roles','fm2_pilot_role_permissions','fm2_pilot_user_roles'];}
}

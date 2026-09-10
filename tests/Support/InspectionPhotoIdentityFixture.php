<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;

require_once dirname(__DIR__,2).'/app/autoload.php';

use FMonitor2\InstallationProcess\IdentityAccessDefinitionSchemaMigration;

/** Canonical identity prerequisites for private photo-oracle namespaces only. */
final class InspectionPhotoIdentityFixture
{
    public static function tables():array
    {
        return array_reverse(array_keys(IdentityAccessDefinitionSchemaMigration::definitions('', 'utf8mb4_unicode_ci')));
    }

    public static function seed(\mysqli $db,string $prefix,int $actor=901,bool $revoke=false):void
    {
        try {
            if($prefix===''||$actor<1)throw new \InvalidArgumentException('Private fixture prefix and actor required.');
            foreach(IdentityAccessDefinitionSchemaMigration::definitions($prefix,'utf8mb4_unicode_ci')as$definition)$db->query($definition['ddl']);
            $now='2026-08-01T00:00:00+03:00';
            $db->prepare("INSERT INTO `{$prefix}fm2_pilot_users`(user_id,full_name,email,phone,status,activation_state,session_version,source_updated_at) VALUES(?,?,?,'',1,'active',1,?)")->execute([$actor,'Photo fixture actor','photo-'.$actor.'@example.invalid',$now]);
            $db->prepare("INSERT INTO `{$prefix}fm2_pilot_roles`(role_id,code,name,description,status,source_updated_at) VALUES(902,'construction_control_engineer','Photo fixture engineer','Synthetic prerequisite',1,?)")->execute([$now]);
            $db->prepare("INSERT INTO `{$prefix}fm2_pilot_user_roles`(user_id,role_id,origin,assigned_at,assigned_by_user_id) VALUES(?,902,'fixture',?,NULL)")->execute([$actor,$now]);
            if($revoke)$db->query("INSERT INTO `{$prefix}fm2_pilot_role_permissions`(role_id,permission) VALUES(902,'inspection.photo.revoke')");
        } catch (\Throwable $failure) {
            throw new \UnexpectedValueException('SETUP_FAILURE: canonical photo identity fixture cannot be prepared',2,$failure);
        }
    }
}

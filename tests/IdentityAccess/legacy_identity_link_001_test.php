<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__).'/Yii2/PreopeningFixture.php';

use FMonitor2\IdentityAccess\LegacyIdentityLinkCommand;
use FMonitor2\IdentityAccess\ProductionLegacyIdentityLinkFactory;

$f=null;
try {
    $f=new PreopeningFixture(dirname(__DIR__,2));$p=$f->p;
    $f->db->query("CREATE TABLE IF NOT EXISTS {$p}users_roles(id BIGINT UNSIGNED PRIMARY KEY,name VARCHAR(100),status TINYINT NOT NULL)");
    $f->db->query("CREATE TABLE IF NOT EXISTS {$p}users(id BIGINT UNSIGNED PRIMARY KEY,name VARCHAR(300),email VARCHAR(300),role_id BIGINT UNSIGNED,status TINYINT NOT NULL,password VARCHAR(300) NULL)");
    $f->db->query("INSERT IGNORE INTO {$p}users_roles(id,name,status) VALUES(42,'Строительный контроль',1)");
    $f->db->query("INSERT IGNORE INTO {$p}users(id,name,email,role_id,status,password) VALUES(7301,'Legacy Engineer','legacy.engineer@example.test',42,1,'DO-NOT-READ-LEGACY-PASSWORD'),(7302,'Same Name','duplicate@example.test',42,1,'DO-NOT-READ-SECOND-PASSWORD'),(7303,'Inactive Legacy','inactive@example.test',42,0,'DO-NOT-READ-INACTIVE-PASSWORD')");
    $f->insert($p.'fm2_pilot_users',['user_id'=>7301,'full_name'=>'Local Engineer','email'=>'local.engineer@shlz.ru','status'=>1,'activation_state'=>'active','session_version'=>1,'source_updated_at'=>'2026-09-16T11:00:00+03:00']);
    $f->insert($p.'fm2_pilot_user_roles',['user_id'=>7301,'role_id'=>2,'origin'=>'fixture','assigned_at'=>'2026-09-16T11:00:00+03:00']);
    $f->insert($p.'fm2_pilot_users',['user_id'=>7302,'full_name'=>'Same Name','email'=>'duplicate@example.test','status'=>1,'activation_state'=>'active','session_version'=>1,'source_updated_at'=>'2026-09-16T11:00:00+03:00']);
    $f->insert($p.'fm2_pilot_user_roles',['user_id'=>7302,'role_id'=>2,'origin'=>'fixture','assigned_at'=>'2026-09-16T11:00:00+03:00']);
    foreach([[7304,'inactive.local@shlz.ru',0,2],[7305,'wrong.role@shlz.ru',1,5]] as[$id,$email,$active,$role]){$f->insert($p.'fm2_pilot_users',['user_id'=>$id,'full_name'=>'Local '.$id,'email'=>$email,'status'=>$active,'activation_state'=>'active','session_version'=>1,'source_updated_at'=>'2026-09-16T11:00:00+03:00']);$f->insert($p.'fm2_pilot_user_roles',['user_id'=>$id,'role_id'=>$role,'origin'=>'fixture','assigned_at'=>'2026-09-16T11:00:00+03:00']);}
    assertSameValue(true,class_exists(ProductionLegacyIdentityLinkFactory::class)&&interface_exists(FMonitor2\IdentityAccess\LegacyIdentityLinkClock::class),'INTENDED_RED LEGACY-CONTROL-ENGINEER-MIGRATION-001 owner absent');
    $clock=new class implements FMonitor2\IdentityAccess\LegacyIdentityLinkClock { public function now():string{return '2026-09-16T12:00:00Z';} };
    $owner=ProductionLegacyIdentityLinkFactory::create($f->db,$p,$p,$clock);
    $facts=static fn()=>[
        $f->rows('fm2_legacy_identity_links'),$f->rows('fm2_legacy_identity_link_events'),
        $f->db->query("SELECT * FROM {$p}users ORDER BY id")->fetch_all(MYSQLI_ASSOC),
    ];
    $request='20202020-0001-4020-8020-000000000001';
    $result=$owner->link(new LegacyIdentityLinkCommand($request,7301,7301,94,null));
    assertSameValue(['linked',null,7301,7301],[$result['status'],$result['reasonCode'],$result['link']['localUserId'],$result['link']['legacyUserId']],'exact ID link');
    $rows=$f->rows('fm2_legacy_identity_links');
    assertSameValue([7301,7301,94,'2026-09-16 12:00:00'],array_map(static fn($v)=>is_numeric($v)?(int)$v:$v,[$rows[0]['local_user_id'],$rows[0]['legacy_user_id'],$rows[0]['linked_by_user_id'],$rows[0]['linked_at_utc']]),'actor/time link row');
    assertSameValue('Legacy Engineer',$rows[0]['legacy_name_snapshot'],'bounded legacy snapshot');
    $after=$facts();assertSameValue('replayed',$owner->link(new LegacyIdentityLinkCommand($request,7301,7301,94,null))['status'],'exact replay');assertSameValue($after,$facts(),'replay no write');
    foreach([
        ['request conflict',new LegacyIdentityLinkCommand($request,7301,7302,94,null),['conflict','request_id_conflict']],
        ['duplicate legacy',new LegacyIdentityLinkCommand('20202020-0001-4020-8020-000000000002',7302,7301,94,null),['conflict','legacy_user_already_linked']],
        ['duplicate local',new LegacyIdentityLinkCommand('20202020-0001-4020-8020-000000000003',7301,7302,94,null),['conflict','local_user_already_linked']],
        ['missing legacy',new LegacyIdentityLinkCommand('20202020-0001-4020-8020-000000000004',7302,99999,94,null),['rejected','legacy_user_not_found']],
        ['inactive legacy',new LegacyIdentityLinkCommand('20202020-0001-4020-8020-000000000005',7302,7303,94,null),['rejected','legacy_user_inactive']],
        ['unauthorized',new LegacyIdentityLinkCommand('20202020-0001-4020-8020-000000000006',7302,7302,95,null),['rejected','authorization_denied']],
        ['inactive local',new LegacyIdentityLinkCommand('20202020-0001-4020-8020-000000000007',7304,7302,94,null),['rejected','local_engineer_ineligible']],
        ['wrong local role',new LegacyIdentityLinkCommand('20202020-0001-4020-8020-000000000008',7305,7302,94,null),['rejected','local_engineer_ineligible']],
    ] as [$label,$command,$expected]) {$before=$facts();$actual=$owner->link($command);assertSameValue($expected,[$actual['status'],$actual['reasonCode']],$label);assertSameValue($before,$facts(),$label.' atomic');}
    assertSameValue(1,count($f->rows('fm2_legacy_identity_links')),'names/emails do not auto-link');
    $beforeCorrection=$facts();assertSameValue(['rejected','correction_reason_required'],array_values(array_intersect_key($owner->link(new LegacyIdentityLinkCommand('20202020-0001-4020-8020-000000000009',7301,7302,94,'')),['status'=>1,'reasonCode'=>1])),'blank correction reason');assertSameValue($beforeCorrection,$facts(),'blank correction atomic');
    $corrected=$owner->link(new LegacyIdentityLinkCommand('20202020-0001-4020-8020-000000000010',7301,7302,94,'Исправлено подтверждённое сопоставление'));
    assertSameValue(['corrected',7302],[$corrected['status'],$corrected['link']['legacyUserId']],'explicit correction');$history=$f->rows('fm2_legacy_identity_links');assertSameValue(2,count($history),'correction append-only');assertSameValue((int)$history[0]['link_id'],(int)$history[1]['supersedes_link_id'],'correction lineage');assertSameValue('Исправлено подтверждённое сопоставление',$history[1]['correction_reason'],'mandatory correction reason');
    foreach(['DO-NOT-READ-LEGACY-PASSWORD','DO-NOT-READ-SECOND-PASSWORD','password_hash','session','users_rights'] as $forbidden)assertSameValue(false,str_contains(json_encode([$result,$corrected,$history],JSON_THROW_ON_ERROR),$forbidden),'no credential inheritance '.$forbidden);
    echo "PASS: LEGACY-CONTROL-ENGINEER-MIGRATION-001 identity link owner matrix\n";
} finally {if($f instanceof PreopeningFixture)$f->close();}

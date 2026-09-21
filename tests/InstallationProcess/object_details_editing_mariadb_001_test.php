<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require dirname(__DIR__).'/Yii2/PreopeningFixture.php';

use FMonitor2\InstallationProcess\ObjectDetailsEditCommand;
use FMonitor2\InstallationProcess\ProductionObjectDetailsEditFactory;

$f=null;try{
 $f=new PreopeningFixture(dirname(__DIR__,2));$p=$f->p;
 if(getenv('FMONITOR_FIXTURE_REACHABILITY')==='object-details-mariadb-fixture'){assertSameValue(1,(int)$f->db->query("SELECT COUNT(*) FROM {$p}fm2_installation_cases WHERE legacy_installation_object_id=4512")->fetch_column(),'case fixture');assertSameValue(1,(int)$f->db->query("SELECT COUNT(*) FROM {$p}fm2_pilot_users WHERE user_id=18 AND status=1")->fetch_column(),'actor fixture');echo"FIXTURE_REACHABLE: object-details-mariadb-fixture\n";exit(0);}
 assertSameValue(true,class_exists(ProductionObjectDetailsEditFactory::class),'INTENDED_RED OBJECT-DETAILS-EDITING-001 production owner factory exists');
 $f->insert($p.'fm2_pilot_role_permissions',['role_id'=>1,'permission'=>'objects.details.edit']);
 $owner=ProductionObjectDetailsEditFactory::create($f->db,$p,static fn():string=>'2026-09-21T15:42:00Z');
 $edit=static fn(string$id,int$actor,int$revision,array$patch)=>new ObjectDetailsEditCommand($id,4512,$actor,$revision,$patch);
 $first=$owner->edit($edit('22222222-2222-4222-8222-222222222221',18,0,['zavnumber'=>'00123-А','floors'=>'12','pitmaterial'=>'9']));
 assertSameValue(['applied',1],[$first['status'],$first['revision']],'A7 atomic multi-field winner');
 assertSameValue(1,(int)$f->db->query("SELECT COUNT(*) FROM {$p}fm2_object_detail_edit_events")->fetch_column(),'one grouped event');
 $event=json_decode((string)$f->db->query("SELECT changes_json FROM {$p}fm2_object_detail_edit_events")->fetch_column(),true,flags:JSON_THROW_ON_ERROR);
 assertSameValue(['zavnumber','floors','pitmaterial'],array_column($event,'field'),'event has complete ordered diff');
 assertSameValue('replayed',$owner->edit($edit('22222222-2222-4222-8222-222222222221',18,0,['zavnumber'=>'00123-А','floors'=>'12','pitmaterial'=>'9']))['status'],'A6 exact replay');
 assertSameValue('conflict',$owner->edit($edit('22222222-2222-4222-8222-222222222221',18,1,['floors'=>'13']))['status'],'request content conflict');
 assertSameValue('conflict',$owner->edit($edit('22222222-2222-4222-8222-222222222222',18,0,['floors'=>'13']))['status'],'stale revision');
 assertSameValue('noop',$owner->edit($edit('22222222-2222-4222-8222-222222222223',18,1,['floors'=>'12']))['status'],'normalized no-op');
 foreach([[95,['address'=>'Denied']], [18,['address'=>'Allowed','shaftBp'=>'11500']]]as$i=>$case){[$actor,$patch]=$case;$before=$f->facts();$result=$owner->edit($edit(sprintf('22222222-2222-4222-8222-%012d',300+$i),$actor,1,$patch));assertSameValue(in_array($result['status'],['rejected','invalid'],true),true,'A3/A5 denial');assertSameValue($before,$f->facts(),'denial writes no facts');}
 $f->db->query("CREATE TRIGGER {$p}object_edit_fault BEFORE INSERT ON {$p}fm2_object_detail_edit_events FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fault'");try{$before=$f->facts();assertSameValue('failed',$owner->edit($edit('22222222-2222-4222-8222-222222222399',18,1,['floors'=>'13']))['status'],'A7 persistence failure');assertSameValue($before,$f->facts(),'override and event rollback');}finally{$f->db->query("DROP TRIGGER {$p}object_edit_fault");}
 echo "PASS: OBJECT-DETAILS-EDITING-001 MariaDB authorization replay atomic history\n";
}finally{if($f instanceof PreopeningFixture)$f->close();}

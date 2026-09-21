<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

use FMonitor2\InstallationProcess\ObjectDetailsEditCommand;
use FMonitor2\InstallationProcess\ObjectDetailsFieldRegistry;

if(getenv('FMONITOR_FIXTURE_REACHABILITY')==='object-details-unit-fixture'){assertSameValue(13,count(['address','entrance','regnumber','zavnumber','floors','weight','speed','pittype','pitmaterial','lift_type','paired','workdatestart','plan_finish_date']),'field matrix fixture');echo"FIXTURE_REACHABLE: object-details-unit-fixture\n";exit(0);}

assertSameValue(true,class_exists(ObjectDetailsFieldRegistry::class),'INTENDED_RED OBJECT-DETAILS-EDITING-001 field registry exists');
$registry=new ObjectDetailsFieldRegistry();
assertSameValue(['address','entrance','regnumber','zavnumber','floors','weight','speed','pittype','pitmaterial','lift_type','paired','workdatestart','plan_finish_date'],array_keys($registry->definitions()),'A1 exact allowlist and no shaftBp');

$valid=['address'=>'Новый адрес','entrance'=>'02','regnumber'=>'00042','zavnumber'=>'00123-А','floors'=>'12','weight'=>'400','speed'=>'1,0','pittype'=>'7','pitmaterial'=>'9','lift_type'=>'1','paired'=>'0','workdatestart'=>'2026-10-01','plan_finish_date'=>'2026-12-20'];
foreach($valid as$field=>$value){$one=$registry->normalizePatch([$field=>$value]);assertSameValue([$field],array_keys($one),'A1 independently accepts '.$field);}

$normalized=$registry->normalizePatch(['regnumber'=>'00042','zavnumber'=>'00123-А','floors'=>'12','weight'=>'400','speed'=>'1,0','paired'=>'0']);
assertSameValue(['regnumber'=>'00042','zavnumber'=>'00123-А','floors'=>12,'weight'=>400,'speed'=>'1.0','paired'=>false],$normalized,'A4 typed values and leading zeros');
foreach([
 ['shaftBp'=>'1,15'],['ptoactdate'=>'2026-09-21'],['floors'=>'0'],['weight'=>'not-a-number'],['zavnumber'=>str_repeat('я',61)],['paired'=>''],['unknown'=>'x'],
]as$patch){$thrown=false;try{$registry->normalizePatch($patch);}catch(InvalidArgumentException){$thrown=true;}assertSameValue(true,$thrown,'A3 invalid/forbidden patch rejected');}
foreach([[['floors'=>['12']]],[['floors'=>new stdClass()]],[['floors'=>true]],[['address'=>"bad\0value"]]]as$case){$thrown=false;try{$registry->normalizePatch($case[0]);}catch(InvalidArgumentException){$thrown=true;}assertSameValue(true,$thrown,'A3 non-scalar/unsafe shape rejected');}
assertSameValue(['floors'=>12],$registry->normalizePatch(['floors'=>'12']),'omitted neighbors remain omitted');

$command=new ObjectDetailsEditCommand('22222222-2222-4222-8222-222222222222',4512,18,0,['address'=>'Новый адрес']);
assertSameValue([4512,18,0,['address'=>'Новый адрес']],[$command->objectId,$command->actorId,$command->expectedRevision,$command->patch],'typed public command');
echo "PASS: OBJECT-DETAILS-EDITING-001 field map and normalization\n";

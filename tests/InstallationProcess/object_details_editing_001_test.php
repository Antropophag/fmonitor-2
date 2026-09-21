<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

use FMonitor2\InstallationProcess\ObjectDetailsEditCommand;
use FMonitor2\InstallationProcess\ObjectDetailsFieldRegistry;

if(getenv('FMONITOR_FIXTURE_REACHABILITY')==='object-details-unit-fixture'){assertSameValue(11,count(['address','entrance','regnumber','zavnumber','floors','weight','speed','pittype','pitmaterial','lift_type','paired']),'field matrix fixture');echo"FIXTURE_REACHABLE: object-details-unit-fixture\n";exit(0);}

assertSameValue(true,class_exists(ObjectDetailsFieldRegistry::class),'INTENDED_RED OBJECT-DETAILS-EDITING-001 field registry exists');
$registry=new ObjectDetailsFieldRegistry();
assertSameValue(['address','entrance','regnumber','zavnumber','floors','weight','speed','pittype','pitmaterial','lift_type','paired'],array_keys($registry->definitions()),'A1 exact allowlist and no shaftBp');

$valid=['address'=>[' Новый адрес ','Новый адрес'],'entrance'=>['02','02'],'regnumber'=>['00042','00042'],'zavnumber'=>['00123-А','00123-А'],'floors'=>['12',12],'weight'=>['400',400],'speed'=>['1,0','1.0'],'pittype'=>['7','7'],'pitmaterial'=>['9','9'],'lift_type'=>['1','1'],'paired'=>['0',false]];
foreach($valid as$field=>[$value,$expected]){assertSameValue([$field=>$expected],$registry->normalizePatch([$field=>$value]),'A1/A4 independently normalizes '.$field);}

$normalized=$registry->normalizePatch(['regnumber'=>'00042','zavnumber'=>'00123-А','floors'=>'12','weight'=>'400','speed'=>'1,0','paired'=>'0']);
assertSameValue(['regnumber'=>'00042','zavnumber'=>'00123-А','floors'=>12,'weight'=>400,'speed'=>'1.0','paired'=>false],$normalized,'A4 typed values and leading zeros');
foreach([
 ['shaftBp'=>'1,15'],['ptoactdate'=>'2026-09-21'],['floors'=>'0'],['floors'=>'201'],['weight'=>'not-a-number'],['speed'=>'0'],['speed'=>'20.01'],['pittype'=>'999'],['pitmaterial'=>'999'],['lift_type'=>'999'],['zavnumber'=>str_repeat('я',61)],['address'=>str_repeat('a',501)],['entrance'=>str_repeat('a',81)],['regnumber'=>str_repeat('a',121)],['paired'=>''],['unknown'=>'x'],
]as$patch){$thrown=false;try{$registry->normalizePatch($patch);}catch(InvalidArgumentException){$thrown=true;}assertSameValue(true,$thrown,'A3 invalid/forbidden patch rejected');}
foreach([[['floors'=>['12']]],[['floors'=>new stdClass()]],[['floors'=>true]],[['address'=>"bad\0value"]]]as$case){$thrown=false;try{$registry->normalizePatch($case[0]);}catch(InvalidArgumentException){$thrown=true;}assertSameValue(true,$thrown,'A3 non-scalar/unsafe shape rejected');}
assertSameValue(['floors'=>12],$registry->normalizePatch(['floors'=>'12']),'omitted neighbors remain omitted');
assertSameValue(['value'=>'7','raw'=>'7','display'=>'7','unit'=>null,'referenceLabel'=>'7'],$registry->snapshot('pittype','7'),'unresolved legacy code remains truthful raw display');
assertSameValue(['value'=>'9','raw'=>'9','display'=>'9','unit'=>null,'referenceLabel'=>'9'],$registry->snapshot('pitmaterial','9'),'unresolved material code remains truthful raw display');
assertSameValue(['zavnumber'=>null],$registry->normalizePatch(['zavnumber'=>'']),'explicit nullable clear');
assertSameValue(['zavnumber'=>'0'],$registry->normalizePatch(['zavnumber'=>'0']),'factory zero stored as exact identity; consumers exclude it');

$command=new ObjectDetailsEditCommand('22222222-2222-4222-8222-222222222222',4512,18,0,['address'=>'Новый адрес']);
assertSameValue([4512,18,0,['address'=>'Новый адрес']],[$command->objectId,$command->actorId,$command->expectedRevision,$command->patch],'typed public command');
echo "PASS: OBJECT-DETAILS-EDITING-001 field map and normalization\n";

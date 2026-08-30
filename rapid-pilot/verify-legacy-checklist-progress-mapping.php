<?php

declare(strict_types=1);

require_once __DIR__.'/legacy-migration/LegacyChecklistProgressMapping.php';
function mappingCheck(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
$payload=['checklistEvents'=>[
    ['id'=>1,'checklist_definition_id'=>10,'part_id'=>1,'share'=>'40','value'=>'1','ctime'=>'2026-01-01 10:00:00'],
    ['id'=>2,'checklist_definition_id'=>20,'part_id'=>1,'share'=>'60','value'=>'1','ctime'=>'2026-01-01 11:00:00'],
    ['id'=>3,'checklist_definition_id'=>10,'part_id'=>1,'share'=>'40','value'=>'0','ctime'=>'2026-01-02 10:00:00']]];
$blocked=LegacyChecklistProgressMapping::profile($payload);
mappingCheck($blocked['candidateProgressBp']===6000,'latest event state drives candidate progress');
mappingCheck($blocked['eligibleForCalculation']===false&&$blocked['conflictCodes']===['DEFINITION_VERSION_UNPROVEN'],'unversioned definitions never enter calculation');
$versioned=LegacyChecklistProgressMapping::profile($payload+['checklistDefinitionVersion'=>'template-2026-01']);
mappingCheck($versioned['eligibleForCalculation']===true&&$versioned['candidateProgressBp']===6000,'versioned consistent definition set is eligible');
$changed=$payload;$changed['checklistDefinitionVersion']='template-2026-01';$changed['checklistEvents'][]=['id'=>4,'checklist_definition_id'=>10,'part_id'=>1,'share'=>'35','value'=>'1','ctime'=>'2026-01-03 10:00:00'];
mappingCheck(in_array('DEFINITION_CHANGED_WITHIN_SNAPSHOT',LegacyChecklistProgressMapping::profile($changed)['conflictCodes'],true),'definition conflict is detected');
echo json_encode(['ok'=>true,'mappingVersion'=>$blocked['mappingVersion'],'blockedBy'=>$blocked['conflictCodes'],'candidateProgressBp'=>$blocked['candidateProgressBp']],JSON_THROW_ON_ERROR),"\n";

<?php

declare(strict_types=1);

require_once __DIR__.'/legacy-migration/ChecklistTemplateAssociation.php';
function associationSame(mixed$e,mixed$a,string$m):void{if($e!==$a)throw new RuntimeException($m);}$hash=hash('sha256','fixture');$version=LegacyChecklistTemplateSnapshot::VERSION;
associationSame(['allowed'=>true,'conflictCode'=>null],ChecklistTemplateAssociationPolicy::validate('legacy_active_baseline','2026-08-30 12:00:00','2026-08-30 12:00:00',$version,$hash),'active baseline at cutover');
associationSame(['allowed'=>true,'conflictCode'=>null],ChecklistTemplateAssociationPolicy::validate('operational_case','2026-08-31 00:00:00','2026-08-30 12:00:00',$version,$hash),'future native case');
associationSame(['allowed'=>true,'conflictCode'=>null],ChecklistTemplateAssociationPolicy::validate('native_checklist_event','2026-09-01 10:00:00','2026-08-30 12:00:00',$version,$hash),'future native event');
foreach([['legacy_historical_event','2026-09-01 00:00:00'],['native_checklist_event','2026-08-30 11:59:59']]as[$kind,$at])associationSame(['allowed'=>false,'conflictCode'=>'DEFINITION_VERSION_UNPROVEN'],ChecklistTemplateAssociationPolicy::validate($kind,$at,'2026-08-30 12:00:00',$version,$hash),'historical/pre-capture remains unproven');
associationSame(['allowed'=>false,'conflictCode'=>'DEFINITION_VERSION_UNPROVEN'],ChecklistTemplateAssociationPolicy::validate('operational_case','2026-09-01 00:00:00','2026-08-30 12:00:00','wrong-version',$hash),'exact version required');
echo "PASS: checklist template associations start at cutover and never backdate legacy history\n";

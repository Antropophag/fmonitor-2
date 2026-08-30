<?php

declare(strict_types=1);

$source=(string)file_get_contents(__DIR__.'/link-operational-case-template.php');
foreach(["p.output_kind='operational_case'","category']!=='native_candidate'","p.classification_version","source_cutoff_at","DEFINITION_VERSION_UNPROVEN","ChecklistTemplateAssociationTarget"]as$required)if(!str_contains($source,$required))throw new RuntimeException('Missing gate '.$required);
if(str_contains($source,'UPDATE ')||str_contains($source,'DELETE '))throw new RuntimeException('Association command must remain append-only');
echo "PASS: operational case template link is clean, bounded and append-only\n";

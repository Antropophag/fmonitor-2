<?php

declare(strict_types=1);

$source=(string)file_get_contents(__DIR__.'/legacy-migration/HistoricalPremiumOperandProfiler.php');$cli=(string)file_get_contents(__DIR__.'/profile-historical-premium-operands.php');
foreach(['FORMULA_CONSTANT_NOT_SOURCE_FACT','CURRENT_MUTABLE_SHAFT_FIELD_WITHOUT_VALIDITY','PLAN_FIELD_WITHOUT_CONTRACT_BASIS','REPORT_DATE_COMMAND_OR_ARTIFACT_ABSENT']as$reason)if(!str_contains($source,$reason))throw new RuntimeException('missing exclusion '.$reason);
if(!str_contains($source,"'provenOperandCounts'")||!str_contains($source,"'provenOperands'=>[]"))throw new RuntimeException('profiler must admit no unproven operands');
foreach(['UPDATE ','DELETE ','INSERT ','REPLACE ','ALTER ','DROP ','TRUNCATE ']as$token)if(str_contains(strtoupper($source.$cli),$token))throw new RuntimeException('source mutation token '.$token);
if(!str_contains($source,"hash('sha256','legacy-object:'"))throw new RuntimeException('representatives must redact object identity');
echo "PASS: historical operand profiler is read-only and fail-closed\n";

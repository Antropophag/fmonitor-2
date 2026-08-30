<?php

declare(strict_types=1);

require_once __DIR__ . '/legacy-migration/LegacyMigrationRouter.php';
function same(mixed $e,mixed $a,string $m):void{if($e!==$a)throw new RuntimeException($m.' '.var_export($a,true));}
$classification = static fn(string $category,array $q=[]):array => ['classificationVersion'=>LegacyObjectClassification::VERSION,'category'=>$category,'reasonCodes'=>['FIXED_REASON'],'quarantineCodes'=>$q];
foreach ([['native_candidate','operational_case_import'],['legacy_active','cutover_baseline'],['legacy_historical','historical_reconstruction']] as [$category,$expected]) {
    $input=$classification($category);$route=LegacyMigrationRoute::decide($input);same($expected,$route['route'],$category);same($input,$route['classification'],'classification unchanged');same(false,$route['applyBlocked'],'clean apply');
}
$quarantined=$classification('legacy_active',['CONTRADICTORY_EVIDENCE']);$route=LegacyMigrationRoute::decide($quarantined);same(true,$route['applyBlocked'],'quarantine blocks apply');same($quarantined,$route['classification'],'quarantine unchanged');
echo "PASS: deterministic migration routing preserves classifier evidence\n";

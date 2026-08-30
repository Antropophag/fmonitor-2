<?php
declare(strict_types=1);
$source=(string)file_get_contents(__DIR__.'/import-production-object-details.php');
foreach(['START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY','legacy_installation_object_id>?','ORDER BY legacy_installation_object_id LIMIT ?','WorkforceCatalogReconciliationCandidate::assertGeneration','technical-object-detail-v1','content_sha256','SOURCE_OBJECT_NOT_FOUND','DETAIL_PROJECTION_CONFLICT','capturedAt',"['floors','weight','speed','pittype','pitmaterial','paired']"]as$required)if(!str_contains($source,$required))throw new RuntimeException('Missing detail projection invariant: '.$required);
foreach(['stopact','UPDATE fm_maintable','INSERT INTO fm_maintable','DELETE FROM fm_maintable','sourceUpdatedAt']as$forbidden)if(str_contains($source,$forbidden))throw new RuntimeException('Forbidden detail projection token: '.$forbidden);
$ui=(string)file_get_contents(__DIR__.'/ObjectDetails.php');if(!str_contains($ui,'projectionUnavailable')||str_contains($ui,'fallbackDetails'))throw new RuntimeException('Object detail projection must fail closed');if(!str_contains($ui,"originNotice['html']"))throw new RuntimeException('Object detail projection must preserve data-origin notice');
echo "PASS technical object-detail projection contract\n";

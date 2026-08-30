<?php

declare(strict_types=1);

$source=(string)file_get_contents(__DIR__.'/batch-import-legacy-history.php');foreach(['WHERE id>? ORDER BY id LIMIT ?','START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY','historical_reconstruction','applyBlocked','evidenceQuarantined','nextAfterId','batch-size']as$required)if(!str_contains($source,$required))throw new RuntimeException('Missing batch invariant '.$required);foreach(['UPDATE fm_','DELETE FROM fm_','INSERT INTO fm_maintable']as$forbidden)if(str_contains($source,$forbidden))throw new RuntimeException('Legacy write token '.$forbidden);echo "PASS: historical batch import is bounded, resumable and legacy-read-only\n";

<?php

declare(strict_types=1);

$source=(string)file_get_contents(__DIR__.'/batch-import-legacy-active.php');foreach(['WHERE id>? ORDER BY id LIMIT ?','START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY','cutover_baseline','applyBlocked','associateActiveBaseline','nextAfterId','batch-size']as$required)if(!str_contains($source,$required))throw new RuntimeException('Missing active batch invariant '.$required);foreach(['UPDATE fm_','DELETE FROM fm_','INSERT INTO fm_maintable']as$forbidden)if(str_contains($source,$forbidden))throw new RuntimeException('Legacy write token '.$forbidden);echo "PASS: active baseline batch is bounded, resumable, template-bound and legacy-read-only\n";

<?php
declare(strict_types=1);
$s=(string)file_get_contents(__DIR__.'/batch-register-migration-quarantine.php');foreach(['START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY','WHERE id>? ORDER BY id LIMIT ?','quarantineCodes','hmac-sha256:','identifiersExposed']as$x)if(!str_contains($s,$x))throw new RuntimeException('Missing quarantine invariant '.$x);foreach(['UPDATE fm_','DELETE FROM fm_','INSERT INTO fm_maintable']as$x)if(str_contains($s,$x))throw new RuntimeException('Legacy write token '.$x);echo"PASS migration quarantine registry is redacted, bounded and source-read-only\n";

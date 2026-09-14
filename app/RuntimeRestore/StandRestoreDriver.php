<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;

interface StandRestoreDriver
{
    public function preflight(StandRestoreAuthorization $authorization): string;
    /** @param array<string,string> $payloads */
    /** @return array{outcome:string,evidence:array<string,bool>} */
    public function restore(StandRestoreAuthorization $authorization, array $payloads): array;
}

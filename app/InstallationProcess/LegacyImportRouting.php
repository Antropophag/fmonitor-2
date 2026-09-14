<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

require_once dirname(__DIR__) . '/Otiz/LegacyObjectClassification.php';

final class LegacyImportRouting
{
    /** @param array<string,mixed> $row @return array<string,mixed> */
    public static function classify(array $row): array
    {
        return \LegacyObjectClassification::classify($row);
    }

    /** @param array<string,mixed> $classification */
    public static function importsOperationalCase(array $classification): bool
    {
        return ($classification['classificationVersion'] ?? null) === \LegacyObjectClassification::VERSION
            && ($classification['category'] ?? null) === 'native_candidate'
            && ($classification['quarantineCodes'] ?? null) === [];
    }
}

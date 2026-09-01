<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final class CanonicalMigrationApplication
{
    /**
     * @param array<int, class-string|callable(\mysqli,string):array<string,mixed>> $migrations
     * @param null|callable():?int $databasePreflight Returns an optional report boundary.
     * @return array{exitCode:int,result:array<string,mixed>}
     */
    public static function run(
        \mysqli $connection,
        string $tablePrefix,
        array $migrations,
        int $reportFromVersion = 1,
        ?callable $databasePreflight = null,
    ): array {
        $appliedVersions = [];
        try {
            $versions = array_keys($migrations);
            if ($versions !== [] && $versions !== range(1, max($versions))) {
                throw new \LogicException('Canonical migration catalogue must be contiguous from v1.');
            }
            if ($databasePreflight !== null) {
                $preflightReportFromVersion = $databasePreflight();
                if (is_int($preflightReportFromVersion)) {
                    $reportFromVersion = $preflightReportFromVersion;
                }
            }
            foreach ($migrations as $version => $migration) {
                $result = is_string($migration)
                    ? $migration::apply($connection, $tablePrefix)
                    : $migration($connection, $tablePrefix);
                if (($result['reason'] ?? null) === 'SCHEMA_MIGRATION_CONFLICT') {
                    return ['exitCode'=>2, 'result'=>['ok'=>false,'reason'=>'SCHEMA_MIGRATION_CONFLICT','schemaVersion'=>$version]];
                }
                if (($result['applied'] ?? false) === true && $version >= $reportFromVersion) {
                    $appliedVersions[] = $version;
                }
            }
        } catch (DatabaseUnavailable) {
            return ['exitCode'=>69, 'result'=>['ok'=>false,'reason'=>'DATABASE_UNAVAILABLE']];
        } catch (\Throwable) {
            return ['exitCode'=>70, 'result'=>['ok'=>false,'reason'=>'MIGRATION_FAILED']];
        }

        $finalVersion = $migrations === [] ? 0 : max(array_keys($migrations));
        return ['exitCode'=>0, 'result'=>['ok'=>true,'schemaVersion'=>$finalVersion,'appliedVersions'=>$appliedVersions]];
    }
}

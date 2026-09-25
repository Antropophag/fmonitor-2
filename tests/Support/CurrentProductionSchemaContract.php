<?php

declare(strict_types=1);

/**
 * Independent literal oracle for tests that only need the current schema.
 *
 * This contract deliberately does not inspect the production catalogue,
 * migration output, or a database.
 */
final class CurrentProductionSchemaContract
{
    public const CURRENT_VERSION = 35;

    /** @var list<int> */
    private const VERSIONS = [
        1, 2, 3, 4, 5, 6, 7, 8, 9, 10,
        11, 12, 13, 14, 15, 16, 17, 18, 19, 20,
        21, 22, 23, 24, 25, 26, 27, 28, 29, 30,
        31, 32, 33, 34, 35,
    ];

    /** @return list<int> */
    public static function versions(): array
    {
        return self::VERSIONS;
    }

    public static function assertInternallyConsistent(): void
    {
        if (self::VERSIONS !== range(1, count(self::VERSIONS))
            || self::VERSIONS[array_key_last(self::VERSIONS)] !== self::CURRENT_VERSION) {
            throw new LogicException('Current production schema test contract is internally inconsistent.');
        }
    }

    /** @return array{0:int,1:bool,2:int,3:list<int>} */
    public static function cleanApplicationResult(): array
    {
        return [0, true, self::CURRENT_VERSION, self::versions()];
    }

    /** @return array{0:int,1:bool,2:int,3:list<int>} */
    public static function replayApplicationResult(): array
    {
        return [0, true, self::CURRENT_VERSION, []];
    }

    /** @return array{ok:bool,schemaVersion:int,appliedVersions:list<int>} */
    public static function cleanResult(): array
    {
        return ['ok' => true, 'schemaVersion' => self::CURRENT_VERSION, 'appliedVersions' => self::versions()];
    }

    /** @return array{ok:bool,schemaVersion:int,appliedVersions:list<int>} */
    public static function replayResult(): array
    {
        return ['ok' => true, 'schemaVersion' => self::CURRENT_VERSION, 'appliedVersions' => []];
    }

    /** @return array{0:int,1:array{ok:bool,schemaVersion:int,appliedVersions:list<int>}} */
    public static function cleanNestedResult(): array
    {
        return [0, self::cleanResult()];
    }

    /** @return array{0:int,1:array{ok:bool,schemaVersion:int,appliedVersions:list<int>}} */
    public static function replayNestedResult(): array
    {
        return [0, self::replayResult()];
    }

    /** @return array{exitCode:int,stdout:string,stderr:string} */
    public static function cleanCliResult(): array
    {
        return self::cliResult(self::versions());
    }

    /** @return array{exitCode:int,stdout:string,stderr:string} */
    public static function replayCliResult(): array
    {
        return self::cliResult([]);
    }

    /** @param list<int> $appliedVersions
     *  @return array{exitCode:int,stdout:string,stderr:string}
     */
    private static function cliResult(array $appliedVersions): array
    {
        return [
            'exitCode' => 0,
            'stdout' => json_encode([
                'ok' => true,
                'schemaVersion' => self::CURRENT_VERSION,
                'appliedVersions' => $appliedVersions,
            ], JSON_THROW_ON_ERROR) . "\n",
            'stderr' => '',
        ];
    }
}

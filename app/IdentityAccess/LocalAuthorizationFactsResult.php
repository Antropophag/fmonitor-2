<?php

declare(strict_types=1);

namespace FMonitor2\IdentityAccess;

final readonly class LocalAuthorizationFactsResult
{
    private function __construct(public string $status)
    {
    }

    public static function granted(): self { return new self('GRANTED'); }
    public static function denied(): self { return new self('DENIED'); }
    public static function configurationInvalid(): self { return new self('CONFIGURATION_INVALID'); }
    public static function schemaInvalid(): self { return new self('SCHEMA_INVALID'); }
    public static function readFailed(): self { return new self('READ_FAILED'); }
}

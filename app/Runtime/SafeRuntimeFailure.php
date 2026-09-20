<?php

declare(strict_types=1);

namespace FMonitor2\Runtime;

final class SafeRuntimeFailure
{
    private const COMPONENTS = [
        'bootstrap',
        'yii_error_handler',
        'execution_controller',
        'original_controller',
        'checklist_controller',
    ];

    private const CATEGORIES = [
        'configuration',
        'database',
        'dependency',
        'storage',
        'unexpected',
    ];

    public static function report(?\Throwable $error, string $component, ?string $categoryHint = null): string
    {
        $errorId = bin2hex(random_bytes(16));
        $record = [
            'event' => 'http_failure',
            'errorId' => $errorId,
            'occurredAtUtc' => self::occurredAtUtc(),
            'component' => in_array($component, self::COMPONENTS, true) ? $component : 'yii_error_handler',
            'status' => 503,
            'category' => self::category($error, $categoryHint),
            'buildVersion' => self::buildVersion(),
        ];

        try {
            $line = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            @error_log($line);
        } catch (\Throwable) {
        }

        return $errorId;
    }

    private static function category(?\Throwable $error, ?string $hint): string
    {
        if (is_string($hint) && in_array($hint, self::CATEGORIES, true)) return $hint;
        if ($error instanceof \FMonitor2\InstallationProcess\ArtifactStorageException
            || $error instanceof \FMonitor2\InspectionEvidence\ChecklistInfrastructureUnavailable) return 'storage';
        if ($error instanceof \yii\db\Exception || $error instanceof \mysqli_sql_exception) return 'database';
        return 'unexpected';
    }

    private static function occurredAtUtc(): string
    {
        $now = \DateTimeImmutable::createFromFormat('U.u', sprintf('%.6F', microtime(true)), new \DateTimeZone('UTC'));
        return ($now ?: new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.u\Z');
    }

    private static function buildVersion(): string
    {
        return 'unknown';
    }
}

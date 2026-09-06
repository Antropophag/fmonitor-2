<?php

declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** @internal Exact schema successor sets; this grants no capabilities. */
final class ProcessCapabilityVersions
{
    public static function classify(array $capabilities): ?string
    {
        sort($capabilities, SORT_STRING);
        return match ($capabilities) {
            ['assignment_order.prepare','construction_control_engineer'] => 'v3',
            ['assignment_order.confirm_registration','assignment_order.prepare','construction_control_engineer','installation.open'] => 'v4',
            ['assignment_order.confirm_registration','assignment_order.original.correct','assignment_order.original.upload',
                'assignment_order.prepare','construction_control_engineer','installation.open'] => 'v5',
            default => null,
        };
    }
}

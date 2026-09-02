<?php
declare(strict_types=1);

namespace FMonitor2\PilotHttp;

final class PilotRouteAdmission
{
    public static function rejectIfUnknown(string $path, bool $knownAdapterRoute): void
    {
        if (!\str_starts_with($path, '/pilot/') || self::isKnown($path) || $knownAdapterRoute) return;
        \http_response_code(404);
        \header_remove('X-Powered-By');
        \header_remove('Server');
        foreach ([
            'Content-Type: text/plain; charset=UTF-8', 'Content-Length: 11',
            'Cache-Control: no-store', 'X-Content-Type-Options: nosniff',
            'Referrer-Policy: no-referrer', 'X-Frame-Options: DENY',
            'Permissions-Policy: camera=(), microphone=(), geolocation=()',
            'Cross-Origin-Opener-Policy: same-origin',
        ] as $header) \header($header);
        echo "Not found.\n";
        exit;
    }

    public static function isKnown(string $path): bool
    {
        return \in_array($path, [
            '/pilot', '/pilot/', '/pilot/login', '/pilot/logout', '/pilot/activate',
            '/pilot/objects', '/pilot/installers', '/pilot/construction-control',
            '/pilot/users', '/pilot/admin/users', '/pilot/admin/roles',
            '/pilot/admin/users/invite',
        ], true)
            || \preg_match('#^/pilot/objects/[1-9][0-9]*(?:/assignment-order/prepare|/assignment-orders/[1-9][0-9]*/(?:registration|artifacts/(?:order|appendix|signed_original))|/control-engineer|/open|/checklist(?:/operations|/photos)?)?$#D', $path) === 1
            || \preg_match('#^/pilot/construction-control/objects/[1-9][0-9]*(?:/sync-context|/checklist(?:/operations|/photos)?)?$#D', $path) === 1
            || \preg_match('#^/pilot/admin/users/[1-9][0-9]*/(?:status|roles(?:/[1-9][0-9]*)?)$#D', $path) === 1;
    }
}

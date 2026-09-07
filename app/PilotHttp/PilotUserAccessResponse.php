<?php
declare(strict_types=1);

namespace FMonitor2\PilotHttp;

final class PilotUserAccessResponse
{
    public static function create(PilotHttpRequest $request, int $status, string $body, array $extra = []): PilotHttpResponse
    {
        $type = (string) ($extra['Content-Type'] ?? 'text/plain; charset=UTF-8');
        $headers = $extra + [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
            'X-Frame-Options' => 'DENY',
            'Content-Security-Policy' => PilotRouteCsp::forResponse($request->method, $request->path, $status, $type, $body),
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cache-Control' => 'no-store',
        ];
        $headers['Content-Length'] = (string) \strlen($body);
        return new PilotHttpResponse($status, $headers, $request->method === 'HEAD' ? '' : $body);
    }

    public static function unavailable(PilotHttpRequest $request): PilotHttpResponse
    {
        return self::create($request, 503, "Service unavailable.\n", ['Retry-After' => '60']);
    }
}

<?php
declare(strict_types=1);

namespace FMonitor2\PilotHttp;

use FMonitor\IdentityAccess\PilotSessionStorage;

final class PilotUserAdminSession
{
    private ?PilotCommandSession $command = null;

    public function __construct(
        private readonly ?PilotSessionStorage $storage,
        private readonly ?EnvironmentSource $environment,
        private ?array $ownerState = null,
    ) {}

    public function open(PilotHttpRequest $request, HttpUser $user, bool $create): array
    {
        if ($this->ownerState !== null) return [$this->ownerState, []];
        $cookieName = self::cookieName($request);
        $incoming = null;
        if (\preg_match('/(?:^|;\s*)'.\preg_quote($cookieName, '/').'=([A-Za-z0-9,-]{16,128})(?:;|$)/', (string) ($request->server['HTTP_COOKIE'] ?? ''), $match) === 1) $incoming = $match[1];
        $this->command ??= new PilotCommandSession($this->storage);
        if (!$this->command->open($incoming, $cookieName, !self::trustedDemo($request), $user->id, $create)) return [null, []];
        return [$this->command->state(), $this->command->headers()];
    }

    public function token(array &$state, HttpUser $user, int $id): string
    {
        $token = \bin2hex(\random_bytes(16));
        $state['tokens'][$token] = ['actor' => $user->id, 'id' => $id, 'at' => \time()];
        $this->store($state);
        return $token;
    }

    public function consume(array &$state, string $token, HttpUser $user, int $id): bool
    {
        $value = $state['tokens'][$token] ?? null;
        unset($state['tokens'][$token]);
        $this->store($state);
        return \is_array($value) && $value['actor'] === $user->id && $value['id'] === $id && \time() - $value['at'] <= 1800;
    }

    public function validRequest(PilotHttpRequest $request, array $state, HttpUser $user): bool
    {
        $origin = $request->server['HTTP_ORIGIN'] ?? null;
        $fetch = $request->server['HTTP_SEC_FETCH_SITE'] ?? null;
        $demo = self::trustedDemo($request);
        $owner = $this->ownerState !== null;
        $scheme = $owner ? $this->environment?->read('FMONITOR_TRUSTED_REQUEST_SCHEME') : ($demo ? 'http' : 'https');
        if (!\in_array($scheme, ['http', 'https'], true)) return false;
        $originAllowed = $origin === null || $origin === $scheme.'://'.$request->host || ($demo && $origin === 'null') || ($owner && $origin === 'null' && $fetch === 'same-origin');
        return ($owner ? ($state['auth_user_id'] ?? null) : ($state['actor'] ?? null)) === $user->id
            && $originAllowed && ($fetch === null || $fetch === 'same-origin');
    }

    public function body(PilotHttpRequest $request, array $allowed): ?array
    {
        $type = (string) ($request->server['CONTENT_TYPE'] ?? '');
        $length = $request->server['CONTENT_LENGTH'] ?? null;
        if (!\preg_match('#^application/x-www-form-urlencoded(?:;\s*charset=UTF-8)?$#iD', $type) || !\is_string($length) || !\ctype_digit($length) || (int) $length > 16384 || (int) $length !== \strlen($request->body)) return null;
        $out = [];
        foreach (\explode('&', $request->body) as $part) {
            if ($part === '') continue;
            $pair = \explode('=', $part, 2);
            if (\preg_match('/%(?![0-9A-Fa-f]{2})/', ($pair[0] ?? '').($pair[1] ?? '')) === 1) return null;
            $key = \rawurldecode(\str_replace('+', ' ', $pair[0]));
            $value = \rawurldecode(\str_replace('+', ' ', $pair[1] ?? ''));
            if (!\in_array($key, $allowed, true) || !\mb_check_encoding($key, 'UTF-8') || !\mb_check_encoding($value, 'UTF-8')) return null;
            $out[$key][] = $value;
        }
        if (!isset($out['csrfToken']) || \count($out['csrfToken']) !== 1) throw new InvalidCsrfRequest();
        foreach ($out as $values) if (\count($values) !== 1) return null;
        return $out;
    }

    public function ownerState(): ?array { return $this->ownerState; }
    public function replaceOwnerState(array $state): void { $this->ownerState = $state; }

    private function store(array $state): void
    {
        if ($this->ownerState !== null) $this->ownerState = $state;
        else $this->command?->replace($state, true);
    }

    private static function trustedDemo(PilotHttpRequest $request): bool
    {
        $nonce = $request->server['FMONITOR_DEMO_LOOPBACK_NONCE'] ?? null;
        $host = $request->server['FMONITOR_DEMO_TRUSTED_REQUEST_HOST'] ?? null;
        return PHP_SAPI === 'cli-server' && \is_string($nonce) && \preg_match('/^[0-9a-f]{32}$/D', $nonce) === 1
            && \is_string($host) && \preg_match('/^127\.0\.0\.1:([1-9][0-9]{3,4})$/D', $host, $parts) === 1
            && (int) $parts[1] >= 1024 && (int) $parts[1] <= 65535 && $request->host === $host;
    }

    private static function cookieName(PilotHttpRequest $request): string
    {
        return self::trustedDemo($request) && \preg_match('/:(\d{1,5})$/D', $request->host, $match) === 1 ? 'fm2pilot_'.$match[1] : 'fm2pilot';
    }
}

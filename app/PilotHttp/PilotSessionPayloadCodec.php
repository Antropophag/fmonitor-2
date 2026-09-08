<?php
declare(strict_types=1);

namespace FMonitor2\PilotHttp;

final class PilotSessionPayloadCodec
{
    public function decode(string $payload): ?array
    {
        if ($payload === '') return [];
        \set_error_handler(static function (): never {
            throw new \ErrorException('session payload decode failed');
        });
        try {
            $decoded = \unserialize($payload, ['allowed_classes' => false]);
        } catch (\Throwable) {
            return null;
        } finally {
            \restore_error_handler();
        }
        if (!\is_array($decoded) || !$this->valid($decoded, 1, $entries) || \serialize($decoded) !== $payload) return null;
        return $decoded;
    }

    public function encode(array $state): ?string
    {
        if (!$this->valid($state, 1, $entries)) return null;
        return \serialize($state);
    }

    private function valid(array $state, int $depth, ?int &$entries): bool
    {
        $entries ??= 0;
        if ($depth > 16) return false;
        foreach (\array_keys($state) as $key) {
            if (++$entries > 4096 || \ReflectionReference::fromArrayElement($state, $key) !== null) return false;
            $value = $state[$key];
            if (\is_array($value)) { if (!$this->valid($value, $depth + 1, $entries)) return false; continue; }
            if ($value !== null && !\is_bool($value) && !\is_int($value) && !\is_string($value)) return false;
        }
        return true;
    }
}

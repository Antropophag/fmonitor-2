<?php
declare(strict_types=1);

namespace FMonitor2\PilotHttp;

final class PilotSessionPayloadCodec
{
    public function decode(string $payload): ?array
    {
        if ($payload === '') return [];
        set_error_handler(static function (): never {
            throw new \ErrorException('session payload decode failed');
        });
        try {
            $decoded = unserialize($payload, ['allowed_classes' => false]);
        } catch (\Throwable) {
            return null;
        } finally {
            restore_error_handler();
        }
        return is_array($decoded) && !$this->containsObject($decoded) ? $decoded : null;
    }

    private function containsObject(array $state): bool
    {
        foreach ($state as $value) {
            if (is_object($value)) return true;
            if (is_array($value) && $this->containsObject($value)) return true;
        }
        return false;
    }
}

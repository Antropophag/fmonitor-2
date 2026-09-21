<?php

declare(strict_types=1);

namespace FMonitor2\YiiRuntime\Assets;

final class AssetVersion
{
    public static function file(string $name): string
    {
        if (basename($name) !== $name) throw new \InvalidArgumentException('Invalid asset name.');
        $hash = hash_file('sha256', __DIR__ . '/' . $name);
        if ($hash === false) throw new \RuntimeException('Asset unavailable.');
        return $name . '?v=' . substr($hash, 0, 12);
    }
}

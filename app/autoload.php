<?php
declare(strict_types=1);

\spl_autoload_register(static function (string $class): void {
    foreach (['FMonitor2\\', 'FMonitor\\'] as $prefix) {
        if (!\str_starts_with($class, $prefix)) continue;
        $path = __DIR__ . '/' . \str_replace('\\', '/', \substr($class, \strlen($prefix))) . '.php';
        if (\is_file($path)) require_once $path;
        return;
    }
});

<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $testPrefix = 'FMonitor2\\Tests\\Support\\';
    if (str_starts_with($class, $testPrefix)) {
        $path = __DIR__ . '/Support/' . substr($class, strlen($testPrefix)) . '.php';
        if (is_file($path)) {
            require $path;
        }
        return;
    }

    $prefix = 'FMonitor2\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = dirname(__DIR__) . '/app/'
        . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

final class TestFailure extends RuntimeException
{
}

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($actual !== $expected) {
        throw new TestFailure(
            $message
            . "\nExpected: " . var_export($expected, true)
            . "\nActual: " . var_export($actual, true)
        );
    }
}

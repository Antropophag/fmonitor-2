<?php
declare(strict_types=1);
$target = (string) getenv('FMONITOR_TEST_INCLUDED_FILES_LOG');
if ($target !== '') {
    register_shutdown_function(static function () use ($target): void {
        file_put_contents($target, json_encode([
            'case' => $_SERVER['HTTP_X_FMONITOR_TEST_CASE'] ?? null,
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'path' => parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH),
            'files' => array_map(static fn (string $path): string => str_replace('\\', '/', $path), get_included_files()),
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n", FILE_APPEND | LOCK_EX);
    });
}

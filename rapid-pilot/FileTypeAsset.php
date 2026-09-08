<?php

declare(strict_types=1);

final class RapidPilotFileTypeAsset
{
    public static function matches(string $path): bool
    {
        return preg_match('#^/pilot/assets/file-types/[a-z0-9-]+\.svg$#D', $path) === 1;
    }

    public static function handle(string $path): never
    {
        if (preg_match('#^/pilot/assets/file-types/([a-z0-9-]+)\.svg$#D', $path, $match) !== 1) {
            throw new InvalidArgumentException('Unsupported file-type asset path.');
        }
        $root = dirname(__DIR__, 2) . '/shlz-ui/packages/icons/dist/file-types/';
        $asset = $root . $match[1] . '.svg';
        if (!is_file($asset)) {
            $asset = $root . 'file-generic.svg';
        }
        $bytes = file_get_contents($asset);
        if (!is_string($bytes)) {
            http_response_code(404);
            exit;
        }
        http_response_code(200);
        header('Content-Type: image/svg+xml');
        header('Content-Length: ' . strlen($bytes));
        header('Cache-Control: public, max-age=3600');
        header('X-Content-Type-Options: nosniff');
        echo $bytes;
        exit;
    }
}

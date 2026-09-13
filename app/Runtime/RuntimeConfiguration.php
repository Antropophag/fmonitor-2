<?php

declare(strict_types=1);

namespace FMonitor2\Runtime;

/** Explicit process configuration shared by production HTTP and deployment tools. */
final class RuntimeConfiguration
{
    private const REQUIRED = [
        'FMONITOR_DB_HOST', 'FMONITOR_DB_PORT', 'FMONITOR_DB_NAME', 'FMONITOR_DB_USER',
        'FMONITOR_DB_PASSWORD', 'FMONITOR_PROCESS_TABLE_PREFIX', 'FMONITOR_LEGACY_TABLE_PREFIX',
        'FMONITOR_SESSION_STATE_ROOT', 'FMONITOR_SESSION_INSTANCE', 'FMONITOR_ARTIFACT_STORAGE_ROOT',
        'FMONITOR_ORIGINAL_DB_PASSWORD_FILE', 'FMONITOR_ORIGINAL_SAFE_LOG_FILE',
        'FMONITOR_TRUSTED_REQUEST_HOST', 'FMONITOR_TRUSTED_REQUEST_SCHEME',
    ];
    private const PATHS = [
        'FMONITOR_SESSION_STATE_ROOT', 'FMONITOR_ARTIFACT_STORAGE_ROOT',
        'FMONITOR_ORIGINAL_DB_PASSWORD_FILE', 'FMONITOR_ORIGINAL_SAFE_LOG_FILE',
    ];

    private function __construct(private readonly array $values) {}

    public static function fromEnvironment(array $environment): self
    {
        $values = [];
        foreach (self::REQUIRED as $name) {
            if (!isset($environment[$name]) || !is_string($environment[$name])) self::fail();
            $values[$name] = $environment[$name];
        }
        if (isset($environment['FMONITOR_YII_SESSION_PATH']) && is_string($environment['FMONITOR_YII_SESSION_PATH'])) {
            $values['FMONITOR_YII_SESSION_PATH'] = $environment['FMONITOR_YII_SESSION_PATH'];
        }
        $patterns = [
            'FMONITOR_DB_HOST' => '/^[A-Za-z0-9.\x3a\x5b\x5d_-]{1,255}$/D',
            'FMONITOR_DB_NAME' => '/^[A-Za-z0-9_]{1,64}$/D',
            'FMONITOR_DB_USER' => '/^[A-Za-z0-9_.-]{1,32}$/D',
            'FMONITOR_DB_PASSWORD' => '/^[\x20-\x7e]{1,1024}$/D',
            'FMONITOR_DB_PORT' => '/^[1-9][0-9]{0,4}$/D',
            'FMONITOR_PROCESS_TABLE_PREFIX' => '/^[A-Za-z0-9_]{0,25}$/D',
            'FMONITOR_SESSION_INSTANCE' => '/^[a-z0-9][a-z0-9_-]{0,31}$/D',
            'FMONITOR_TRUSTED_REQUEST_HOST' => '/^[A-Za-z0-9.-]+(?::[1-9][0-9]{0,4})?$/D',
        ];
        foreach ($patterns as $name => $pattern) {
            if (preg_match($pattern, $values[$name]) !== 1) self::fail();
        }
        if ((int) $values['FMONITOR_DB_PORT'] > 65535
            || $values['FMONITOR_LEGACY_TABLE_PREFIX'] !== $values['FMONITOR_PROCESS_TABLE_PREFIX']
            || !in_array($values['FMONITOR_TRUSTED_REQUEST_SCHEME'], ['http', 'https'], true)) self::fail();
        $host = explode(':', $values['FMONITOR_TRUSTED_REQUEST_HOST']);
        if (isset($host[1]) && (int) $host[1] > 65535) self::fail();
        $validHost = preg_match('/^[0-9.]+$/D', $host[0]) === 1
            ? filter_var($host[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            : filter_var($host[0], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
        if ($validHost === false || strlen($values['FMONITOR_TRUSTED_REQUEST_HOST']) > 253) self::fail();
        $repository = dirname(__DIR__, 2);
        $pathNames = self::PATHS;
        if (isset($values['FMONITOR_YII_SESSION_PATH'])) $pathNames[] = 'FMONITOR_YII_SESSION_PATH';
        foreach ($pathNames as $name) {
            $path = $values[$name];
            if ($path === '' || $path[0] !== '/' || $path === '/'
                || preg_match('/[\x00-\x1f\x7f]/', $path) !== 0
                || in_array('..', explode('/', $path), true) || in_array('.', explode('/', $path), true)
                || str_contains($path, '//') || str_ends_with($path, '/')
                || $path === $repository || str_starts_with($path, $repository . '/')) self::fail();
        }
        if (count(array_unique(array_intersect_key($values, array_flip($pathNames)))) !== count($pathNames)) self::fail();
        foreach (['FMONITOR_ORIGINAL_DB_PASSWORD_FILE', 'FMONITOR_ORIGINAL_SAFE_LOG_FILE'] as $file) {
            foreach ($pathNames as $other) {
                if ($other !== $file && str_starts_with($values[$other], $values[$file] . '/')) self::fail();
            }
        }
        return new self($values);
    }

    public function value(string $name): string
    {
        return $this->values[$name] ?? throw new \LogicException('Unknown runtime configuration key.');
    }

    public function apply(): void
    {
        foreach ($this->values as $name => $value) putenv($name . '=' . $value);
        $root = dirname(__DIR__, 2);
        foreach ([
            'FMONITOR_FRESH_ORDER_FLOW' => '1', 'FMONITOR_LIVE_CLOCK' => '1',
            'FMONITOR_PILOT_CSS_PATH' => $root . '/app/YiiRuntime/Assets/pilot.css',
            'FMONITOR_SHLZ_CSS_PATH' => dirname($root) . '/shlz-ui/packages/styles/dist/shlz.css',
        ] as $name => $value) putenv($name . '=' . $value);
        putenv('FMONITOR_BUSINESS_DATE');
    }

    private static function fail(): never
    {
        throw new \RuntimeException('CONFIGURATION_INVALID');
    }
}

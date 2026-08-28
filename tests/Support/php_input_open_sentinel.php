<?php
declare(strict_types=1);

final class PhpInputOpenSentinelStream
{
    public mixed $context = null;

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        if ($path === 'php://input') {
            $sentinel = getenv('FMONITOR_TEST_PHP_INPUT_SENTINEL');
            if (is_string($sentinel) && $sentinel !== '') {
                file_put_contents($sentinel, "php://input opened\n", FILE_APPEND | LOCK_EX);
            }
        }

        return false;
    }
}

unset($GLOBALS['HTTP_RAW_POST_DATA']);
if (!stream_wrapper_unregister('php')) {
    throw new RuntimeException('Cannot unregister php stream wrapper for test instrumentation.');
}
if (!stream_wrapper_register('php', PhpInputOpenSentinelStream::class)) {
    throw new RuntimeException('Cannot register php stream wrapper test instrumentation.');
}

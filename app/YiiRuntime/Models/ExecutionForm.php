<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Models;

final readonly class ExecutionForm
{
    private const ALLOWED = [
        '_csrf', 'action', 'requestId', 'orderId', 'revisionId', 'sequence',
        'applicationId', 'actualStartDate',
    ];

    private function __construct(public array $fields) {}

    public static function parse(string $body): self
    {
        if (strlen($body) > 8192) {
            throw new \InvalidArgumentException();
        }
        $fields = [];
        foreach (explode('&', $body) as $part) {
            if ($part === '') throw new \InvalidArgumentException();
            $pair = explode('=', $part, 2);
            if (preg_match('/%(?![0-9A-Fa-f]{2})/', implode('', $pair))) {
                throw new \InvalidArgumentException();
            }
            $key = rawurldecode(str_replace('+', ' ', $pair[0]));
            $value = rawurldecode(str_replace('+', ' ', $pair[1] ?? ''));
            if (!in_array($key, self::ALLOWED, true) || array_key_exists($key, $fields)
                || str_contains($key, "\0") || str_contains($value, "\0")) {
                throw new \InvalidArgumentException();
            }
            $fields[$key] = $value;
        }
        return new self($fields);
    }
}

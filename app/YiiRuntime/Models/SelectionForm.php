<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Models;

final readonly class SelectionForm
{
    private const ALLOWED = [
        '_csrf', 'requestId', 'mode', 'expectedSelectionRevision',
        'controlEngineerUserId', 'controlEngineerConfirmed', 'installerTabIds[]',
    ];

    private function __construct(public array $fields) {}

    public static function parse(string $body): self
    {
        if (strlen($body) > 32768) {
            throw new \LengthException();
        }
        $fields = self::decode($body);
        foreach (['_csrf', 'requestId', 'mode', 'expectedSelectionRevision', 'controlEngineerUserId', 'controlEngineerConfirmed'] as $key) {
            if (!isset($fields[$key]) || !is_string($fields[$key])) {
                throw new \InvalidArgumentException();
            }
        }
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $fields['requestId'])) {
            throw new \InvalidArgumentException();
        }
        if (!in_array($fields['mode'], ['new_order', 'replace_pending'], true)
            || !preg_match('/^(0|[1-9][0-9]{0,9})$/D', $fields['expectedSelectionRevision'])
            || (int) $fields['expectedSelectionRevision'] > 4294967295) {
            throw new \InvalidArgumentException();
        }
        if (!preg_match('/^[1-9][0-9]*$/D', $fields['controlEngineerUserId'])
            || filter_var($fields['controlEngineerUserId'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false
            || $fields['controlEngineerConfirmed'] !== 'yes') {
            throw new \InvalidArgumentException();
        }
        foreach ($fields['installerTabIds[]'] ?? [] as $id) {
            if (!preg_match('/^[1-9][0-9]*$/D', $id)
                || filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                throw new \InvalidArgumentException();
            }
        }
        return new self($fields);
    }

    private static function decode(string $body): array
    {
        $fields = [];
        foreach (explode('&', $body) as $part) {
            if ($part === '') throw new \InvalidArgumentException();
            $pair = explode('=', $part, 2);
            if (preg_match('/%(?![0-9A-Fa-f]{2})/', implode('', $pair))) throw new \InvalidArgumentException();
            $key = rawurldecode(str_replace('+', ' ', $pair[0]));
            $value = rawurldecode(str_replace('+', ' ', $pair[1] ?? ''));
            if (!in_array($key, self::ALLOWED, true) || str_contains($key, "\0") || str_contains($value, "\0")) throw new \InvalidArgumentException();
            if ($key === 'installerTabIds[]') {
                $fields[$key][] = $value;
                if (count($fields[$key]) > 500) throw new \InvalidArgumentException();
            } elseif (array_key_exists($key, $fields)) {
                throw new \InvalidArgumentException();
            } else {
                $fields[$key] = $value;
            }
        }
        return $fields;
    }
}

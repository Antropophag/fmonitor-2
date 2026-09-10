<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Models;

final readonly class OriginalMetadata
{
    public const KEYS = [
        'csrfToken', 'requestId', 'mode', 'documentDate', 'compositionConfirmed',
        'rootOriginalId', 'targetRevisionId', 'expectedCurrentRevisionId',
        'correctionReason', 'originalFilename',
    ];

    private function __construct(public array $fields) {}

    public static function fromHeader(string $encoded): self
    {
        if ($encoded === '' || strlen($encoded) > 16384) {
            throw new \InvalidArgumentException();
        }
        $json = base64_decode($encoded, true);
        if (!is_string($json) || base64_encode($json) !== $encoded) {
            throw new \InvalidArgumentException();
        }
        try {
            $fields = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            throw new \InvalidArgumentException();
        }
        $canonical = is_array($fields)
            ? json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_LINE_TERMINATORS | JSON_THROW_ON_ERROR)
            : null;
        if (!is_array($fields) || array_keys($fields) !== self::KEYS || $canonical !== $json) {
            throw new \InvalidArgumentException();
        }
        foreach (['csrfToken', 'requestId', 'mode', 'documentDate', 'originalFilename'] as $key) {
            if (!is_string($fields[$key])) {
                throw new \InvalidArgumentException();
            }
        }
        if (!is_bool($fields['compositionConfirmed'])) {
            throw new \InvalidArgumentException();
        }
        foreach (['rootOriginalId', 'targetRevisionId', 'expectedCurrentRevisionId', 'correctionReason'] as $key) {
            if ($fields[$key] !== null && !is_string($fields[$key])) {
                throw new \InvalidArgumentException();
            }
        }
        if (!in_array($fields['mode'], ['initial', 'correction'], true)
            || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $fields['requestId'])) {
            throw new \InvalidArgumentException();
        }
        return new self($fields);
    }
}

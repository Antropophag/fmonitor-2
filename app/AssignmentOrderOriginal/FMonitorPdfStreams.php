<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Per-inspection physical stream cache and structural expansion budget. */
final class FMonitorPdfStreams
{
    private const OPAQUE_FILTERS = ['ASCIIHexDecode','ASCII85Decode','LZWDecode','FlateDecode','RunLengthDecode','CCITTFaxDecode','JBIG2Decode','DCTDecode','JPXDecode'];
    private array $decoded = [];
    private array $members = [];
    private int $expanded = 0;
    public bool $unsafe = false;

    public function __construct(private readonly string $bytes, private readonly array $physical) {}

    public function payload(int $offset): string
    {
        if (isset($this->decoded[$offset])) return $this->decoded[$offset];
        $object = $this->physical[$offset] ?? null;
        if ($object === null || $object['payload'] === null) throw new \UnexpectedValueException('Missing structural stream.');
        $dict = FMonitorPdfDictionary::entries($object['body']);
        if (isset($dict['Filter']) && FMonitorPdfDictionary::name($dict['Filter']) !== 'FlateDecode') throw new \UnexpectedValueException('Unsupported structural filter.');
        [$start, $length] = $object['payload']; $payload = substr($this->bytes, $start, $length);
        if (isset($dict['Filter'])) {
            $payload = @gzuncompress($payload, 67108865 - $this->expanded);
            if (!is_string($payload) || strlen($payload) > 67108864 - $this->expanded) throw new \UnexpectedValueException('Structural expansion limit.');
            $this->expanded += strlen($payload);
        }
        return $this->decoded[$offset] = $payload;
    }

    public function members(int $offset): array
    {
        if (isset($this->members[$offset])) return $this->members[$offset];
        $object = $this->physical[$offset] ?? null;
        if ($object === null) throw new \UnexpectedValueException('Missing object stream.');
        $dict = FMonitorPdfDictionary::entries($object['body']);
        if (FMonitorPdfDictionary::name($dict['Type'] ?? null) !== 'ObjStm') throw new \UnexpectedValueException('Wrong object stream type.');
        $payload = $this->payload($offset); $count = FMonitorPdfDictionary::uint($dict, 'N', 100000);
        $first = FMonitorPdfDictionary::uint($dict, 'First', strlen($payload));
        if ($count === null || $count < 1 || $first === null) throw new \UnexpectedValueException('Invalid object stream header.');
        $cursor = 0; $pairs = []; $seen = [];
        for ($i = 0; $i < $count; ++$i) {
            $n = FMonitorPdfLexical::next($payload, $cursor); $p = FMonitorPdfLexical::next($payload, $cursor);
            $number = FMonitorPdfDictionary::number($n['value'] ?? '', 100000);
            $position = FMonitorPdfDictionary::number($p['value'] ?? '', strlen($payload) - $first);
            if ($number === null || $number < 1 || $position === null || isset($seen[$number]) || $cursor > $first) throw new \UnexpectedValueException('Invalid object stream member.');
            $pairs[] = [$number, $position]; $seen[$number] = true;
        }
        if (trim(substr($payload, $cursor, $first - $cursor), "\x00\x09\x0A\x0C\x0D\x20") !== '') throw new \UnexpectedValueException('Extra object stream header.');
        $members = [];
        foreach ($pairs as $i => [$number, $position]) {
            $start = $first + $position; $end = isset($pairs[$i + 1]) ? $first + $pairs[$i + 1][1] : strlen($payload);
            if ($start >= $end) throw new \UnexpectedValueException('Invalid member offset.');
            $view = FMonitorPdfLexical::view(substr($payload, $start, $end - $start));
            $cursor = FMonitorPdfDictionary::valueEnd($view['body'], 0);
            if (FMonitorPdfLexical::next($view['body'], $cursor) !== null) throw new \UnexpectedValueException('Extra member values.');
            $this->unsafe = $this->unsafe || $view['unsafe']; $members[] = ['number'=>$number, 'body'=>$view['body']];
        }
        return $this->members[$offset] = $members;
    }

    public function validate(int $offset): void
    {
        $object = $this->physical[$offset]; $this->unsafe = $this->unsafe || $object['unsafe'];
        if ($object['payload'] === null) return;
        $dict = FMonitorPdfDictionary::entries($object['body']); $type = FMonitorPdfDictionary::name($dict['Type'] ?? null);
        if ($type === 'ObjStm') { $this->members($offset); return; }
        if ($type === 'XRef') { $this->payload($offset); return; }
        if (!isset($dict['Filter'])) return;
        $name = FMonitorPdfDictionary::name($dict['Filter']);
        $filters = $name !== null ? [$dict['Filter']] : FMonitorPdfDictionary::arrayValues($dict['Filter']);
        if (!$filters) throw new \UnexpectedValueException('Invalid opaque filter.');
        foreach ($filters as $filter) {
            $name = FMonitorPdfDictionary::name($filter);
            if ($name === 'Crypt') { $this->unsafe = true; continue; }
            if ($name === null || !in_array($name, self::OPAQUE_FILTERS, true)) throw new \UnexpectedValueException('Unsupported opaque filter.');
        }
    }
}

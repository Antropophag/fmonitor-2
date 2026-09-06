<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Validated xref sections; each entry retains physical identity. */
final class FMonitorPdfXref
{
    public function __construct(private readonly string $bytes, private readonly array $physical, private readonly FMonitorPdfStreams $streams) {}

    public function section(int $offset): array
    {
        if (substr($this->bytes, $offset, 4) === 'xref') return $this->classic($offset);
        $object = $this->physical[$offset] ?? null;
        if ($object === null) throw new \UnexpectedValueException('Missing xref stream.');
        $dict = FMonitorPdfDictionary::entries($object['body']);
        if (FMonitorPdfDictionary::name($dict['Type'] ?? null) !== 'XRef') throw new \UnexpectedValueException('Invalid xref stream type.');
        $size = FMonitorPdfDictionary::uint($dict, 'Size', 100001);
        $widths = $this->integers($dict['W'] ?? '', 8); $ranges = isset($dict['Index']) ? $this->integers($dict['Index'], 100001) : [0, $size];
        if ($size === null || $size < 1 || count($widths) !== 3 || array_sum($widths) < 1 || count($ranges) < 2 || count($ranges) % 2 || !isset($dict['Root'])) throw new \UnexpectedValueException('Invalid xref stream shape.');
        $rows = 0;
        foreach (array_chunk($ranges, 2) as [$first, $count]) {
            if ($count < 1 || $first + $count > $size) throw new \UnexpectedValueException('Invalid xref range.');
            $rows += $count;
        }
        $payload = $this->streams->payload($offset);
        if (strlen($payload) !== $rows * array_sum($widths)) throw new \UnexpectedValueException('Invalid xref stream length.');
        $cursor = 0; $entries = [];
        foreach (array_chunk($ranges, 2) as [$first, $count]) {
            for ($i = 0; $i < $count; ++$i) {
                $fields = [];
                foreach ($widths as $j => $width) {
                    $value = $width === 0 && $j === 0 ? 1 : 0;
                    for ($k = 0; $k < $width; ++$k) {
                        $byte = ord($payload[$cursor++]);
                        if ($value > intdiv(PHP_INT_MAX - $byte, 256)) throw new \UnexpectedValueException('Oversized xref integer.');
                        $value = $value * 256 + $byte;
                    }
                    $fields[] = $value;
                }
                $this->entry($entries, $first + $i, $fields[0], $fields[1], $fields[2]);
            }
        }
        return $this->result($entries, $dict, $object['unsafe']);
    }

    private function classic(int $offset): array
    {
        if (!preg_match('/\Gxref(?:\r\n|\n|\r)/A', $this->bytes, $header, 0, $offset)) throw new \UnexpectedValueException('Invalid xref header.');
        $cursor = $offset + strlen($header[0]); $entries = [];
        while (true) {
            $cursor += strspn($this->bytes, "\x00\x09\x0A\x0C\x0D\x20", $cursor);
            if (substr($this->bytes, $cursor, 7) === 'trailer') { $cursor += 7; break; }
            if (!preg_match('/\G([0-9]+)[ \t]+([0-9]+)[ \t]*(?:\r\n|\n|\r)/A', $this->bytes, $range, 0, $cursor)) throw new \UnexpectedValueException('Invalid classic xref range.');
            $cursor += strlen($range[0]); $first = FMonitorPdfDictionary::number($range[1], 100000); $count = FMonitorPdfDictionary::number($range[2], 100001);
            if ($first === null || $count === null || $count < 1 || $first + $count > 100001) throw new \UnexpectedValueException('Oversized classic xref.');
            for ($i = 0; $i < $count; ++$i) {
                if (!preg_match('/\G([0-9]{10})[ \t]+([0-9]{5})[ \t]+([nf])[ \t]*(?:\r\n|\n|\r)/A', $this->bytes, $row, 0, $cursor)) throw new \UnexpectedValueException('Invalid classic xref row.');
                $cursor += strlen($row[0]); $this->entry($entries, $first + $i, $row[3] === 'n' ? 1 : 0, (int)$row[1], (int)$row[2]);
            }
        }
        $view = FMonitorPdfLexical::view($this->bytes, $cursor, false, true);
        return $this->result($entries, FMonitorPdfDictionary::entries($view['body']), $view['unsafe']);
    }

    private function entry(array &$entries, int $number, int $type, int $first, int $second): void
    {
        if (isset($entries[$number]) || !in_array($type, [0,1,2], true) || $number > 100000 || ($type !== 0 && $number === 0)) throw new \UnexpectedValueException('Invalid xref entry.');
        if (($type === 1 && ($first >= strlen($this->bytes) || $second > 65535)) || ($type === 2 && ($first < 1 || $first > 100000 || $second > 100000)) || ($type === 0 && $second > 65535)) throw new \UnexpectedValueException('Invalid xref entry bounds.');
        $entries[$number] = [$type, $first, $second];
    }

    private function integers(string $array, int $maximum): array
    {
        $values = FMonitorPdfDictionary::arrayValues($array);
        if ($values === null) throw new \UnexpectedValueException('Missing xref integer array.');
        return array_map(static function(string $value) use ($maximum): int {
            return FMonitorPdfDictionary::number($value, $maximum) ?? throw new \UnexpectedValueException('Invalid xref integer.');
        }, $values);
    }

    private function result(array $entries, array $dict, bool $unsafe): array
    {
        $size = FMonitorPdfDictionary::uint($dict, 'Size', 100001);
        $root = FMonitorPdfDictionary::reference($dict['Root'] ?? null);
        $prev = FMonitorPdfDictionary::uint($dict, 'Prev', strlen($this->bytes) - 1);
        if ($size === null || $size < 1 || (isset($dict['Root']) && $root === null) || (isset($dict['Prev']) && $prev === null)) throw new \UnexpectedValueException('Invalid xref dictionary.');
        foreach ($entries as $number => $_) if ($number >= $size) throw new \UnexpectedValueException('Xref entry exceeds Size.');
        return ['entries'=>$entries,'root'=>$root,'prev'=>$prev,'unsafe'=>$unsafe || isset($dict['Encrypt'])];
    }
}

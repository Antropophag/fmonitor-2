<?php
declare(strict_types=1);

namespace FMonitor2\Workforce;

/** @internal Native syntax/depth validation followed by a linear duplicate-key scan. */
final class DeliveryJson
{
    private int $offset = 0;

    private function __construct(private readonly string $json) {}

    public static function object(string $json): \stdClass
    {
        try {
            $value = json_decode($json, false, 32, JSON_THROW_ON_ERROR);
            if (!$value instanceof \stdClass) self::fail();
            (new self($json))->value();
            return $value;
        } catch (\JsonException) {
            self::fail();
        }
    }

    private function value(): void
    {
        $this->space();
        $token = $this->json[$this->offset];
        if ($token === '"') { $this->string(); return; }
        if ($token !== '{' && $token !== '[') {
            $this->offset += strcspn($this->json, ",]} \r\n\t", $this->offset);
            return;
        }
        $this->offset++;
        $close = $token === '{' ? '}' : ']';
        $seen = [];
        $this->space();
        if ($this->json[$this->offset] === $close) { $this->offset++; return; }
        while (true) {
            if ($token === '{') {
                $this->space();
                $key = 'key:'.json_decode($this->string(), false, 32, JSON_THROW_ON_ERROR);
                if (isset($seen[$key])) self::fail();
                $seen[$key] = true;
                $this->space();
                $this->offset++; // colon: native decoder already proved syntax
            }
            $this->value();
            $this->space();
            if ($this->json[$this->offset++] === $close) return;
        }
    }

    private function string(): string
    {
        $start = $this->offset++;
        while (true) {
            $this->offset += strcspn($this->json, '"\\', $this->offset);
            if ($this->json[$this->offset++] === '"') return substr($this->json, $start, $this->offset - $start);
            $this->offset++; // skip escaped character; remaining hex digits are ordinary string bytes
        }
    }

    private function space(): void
    {
        $this->offset += strspn($this->json, " \r\n\t", $this->offset);
    }

    private static function fail(): never
    {
        throw new DeliveryFailure(BitrixWorkforceDeliveryReason::SchemaInvalid);
    }
}

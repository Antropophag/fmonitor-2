<?php

declare(strict_types=1);

namespace FMonitor2\Otiz;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/** Validates the evidence envelopes consumed by the pure Excel calculator. */
final class PremiumCalculationV2Evidence
{
    private const MAX_MONEY_CENTS = 1000000000000;

    public static function operand(array $operands, string $name, string $type): int|string|null
    {
        $envelope = $operands[$name] ?? null;
        if (!is_array($envelope) || !array_key_exists('value', $envelope)
            || !array_key_exists('effectiveDate', $envelope) || !array_key_exists('source', $envelope)) {
            throw new InvalidArgumentException("Missing operand {$name}");
        }
        self::date($envelope['effectiveDate']);
        self::source($envelope['source']);
        $value = $envelope['value'];
        if ($type === 'date') { self::date($value); return $value; }
        if ($type === 'nullableDate') { if ($value !== null) self::date($value); return $value; }
        if (!is_int($value)) throw new InvalidArgumentException("Operand {$name} must be an integer");
        $maximum = $type === 'money' ? self::MAX_MONEY_CENTS : ($type === 'shaftBp' ? 20000 : 10000);
        if ($value < 0 || $value > $maximum) throw new InvalidArgumentException("Operand {$name} is out of range");
        return $value;
    }

    public static function paymentTotal(array $evidence, string $collection, string $dateField): int
    {
        if (!array_key_exists($collection, $evidence) || !is_array($evidence[$collection]) || !array_is_list($evidence[$collection])) {
            throw new InvalidArgumentException("Invalid {$collection} collection");
        }
        $total = 0;
        foreach ($evidence[$collection] as $index => $row) {
            if (!is_array($row) || !array_key_exists('amountCents', $row) || !is_int($row['amountCents'])
                || $row['amountCents'] < -self::MAX_MONEY_CENTS || $row['amountCents'] > self::MAX_MONEY_CENTS
                || !array_key_exists($dateField, $row) || !array_key_exists('source', $row)) {
                throw new InvalidArgumentException("Invalid {$collection} row {$index}");
            }
            self::date($row[$dateField]); self::source($row['source']); $total += $row['amountCents'];
        }
        if (!is_int($total) || $total < 0 || $total > self::MAX_MONEY_CENTS) throw new InvalidArgumentException("Invalid {$collection} aggregate");
        return $total;
    }

    /** @param list<array<string,mixed>> $exclusions */
    public static function exclusions(array $exclusions): array
    {
        if (!array_is_list($exclusions)) throw new InvalidArgumentException('Exclusions must be a list');
        foreach ($exclusions as $index => $exclusion) {
            if (!is_array($exclusion) || !array_key_exists('code', $exclusion) || !is_string($exclusion['code'])
                || preg_match('/^[A-Z][A-Z0-9_]{2,79}$/D', $exclusion['code']) !== 1
                || !array_key_exists('effectiveDate', $exclusion) || !array_key_exists('source', $exclusion)) {
                throw new InvalidArgumentException("Invalid exclusion {$index}");
            }
            self::date($exclusion['effectiveDate']); self::source($exclusion['source']);
        }
        return $exclusions;
    }

    private static function source(mixed $source): void
    {
        if (!is_array($source) || !array_key_exists('label', $source) || !is_string($source['label']) || trim($source['label']) === ''
            || !array_key_exists('locator', $source) || !is_string($source['locator']) || trim($source['locator']) === ''
            || !array_key_exists('contentSha256', $source) || !is_string($source['contentSha256'])
            || preg_match('/^[a-f0-9]{64}$/D', $source['contentSha256']) !== 1) throw new InvalidArgumentException('Invalid source provenance');
    }

    private static function date(mixed $value): void
    {
        if (!is_string($value)) throw new InvalidArgumentException('Date must be a string');
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('UTC'));
        $errors = DateTimeImmutable::getLastErrors();
        if ($date === false || ($errors !== false && ($errors['warning_count'] !== 0 || $errors['error_count'] !== 0)) || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException('Invalid exact date');
        }
    }
}

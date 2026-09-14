<?php

declare(strict_types=1);

namespace FMonitor2\Otiz;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final class PremiumCalculationV2
{
    public const VERSION = 'premium-calculation-v2-excel';
    public const ALLOCATION_VERSION = 'largest-remainder-tab-asc-v1';

    private const MAX_MONEY_CENTS = 1000000000000;
    private const MAX_POOL_CENTS = 2000000000000;

    /**
     * @param array<string,array<string,mixed>> $operands
     * @param array{closures:list<array<string,mixed>>,actualPayouts:list<array<string,mixed>>} $paymentEvidence
     * @param list<array<string,mixed>> $exclusions
     * @return array<string,mixed>
     */
    public static function calculate(array $operands, array $paymentEvidence, array $exclusions = []): array
    {
        $premium = self::operand($operands, 'premiumCents', 'money');
        $shaftBp = self::operand($operands, 'shaftBp', 'shaftBp');
        $progressBp = self::operand($operands, 'progressBp', 'basisPoints');
        $deadlineDate = self::operand($operands, 'deadlineDate', 'date');
        $reportDate = self::operand($operands, 'reportDate', 'date');
        $completionDate = self::operand($operands, 'completionDate', 'nullableDate');

        $closedBefore = self::paymentTotal($paymentEvidence, 'closures', 'closedOn');
        self::paymentTotal($paymentEvidence, 'actualPayouts', 'paidOn');
        $normalizedExclusions = self::exclusions($exclusions);

        $comparisonDate = $completionDate ?? $reportDate;
        $deadline = new DateTimeImmutable($deadlineDate, new DateTimeZone('UTC'));
        $comparison = new DateTimeImmutable($comparisonDate, new DateTimeZone('UTC'));
        $daysLate = $comparison > $deadline ? (int) $deadline->diff($comparison)->days : 0;
        $kssBp = max(0, 10000 - 100 * $daysLate);

        $fund = self::roundBasisPoints($premium, $shaftBp);
        $progressAmount = self::roundBasisPoints($fund, $progressBp);
        $remainingBeforePenalty = max(0, $progressAmount - $closedBefore);
        $deadlinePenalty = self::roundBasisPoints($remainingBeforePenalty, 10000 - $kssBp);
        $pool = max(0, $remainingBeforePenalty - $deadlinePenalty);

        return [
            'calculationVersion' => self::VERSION,
            'allocationVersion' => self::ALLOCATION_VERSION,
            'operandEvidence' => $operands,
            'paymentEvidence' => $paymentEvidence,
            'exclusions' => $normalizedExclusions,
            'comparisonDate' => $comparisonDate,
            'daysLate' => $daysLate,
            'kssBp' => $kssBp,
            'amounts' => [
                'fundCents' => $fund,
                'progressAmountCents' => $progressAmount,
                'paidBeforeCents' => $closedBefore,
                'remainingBeforePenaltyCents' => $remainingBeforePenalty,
                'deadlinePenaltyCents' => $deadlinePenalty,
                'poolCents' => $pool,
                'distributableCents' => $normalizedExclusions === [] ? $pool : 0,
                'remainingFundCents' => max(0, $fund - $closedBefore),
            ],
            'formulaTrace' => [
                ['step' => 'fund', 'resultCents' => $fund],
                ['step' => 'progress', 'resultCents' => $progressAmount],
                ['step' => 'deadline', 'daysLate' => $daysLate, 'resultBp' => $kssBp],
                ['step' => 'paid', 'resultCents' => $closedBefore],
                ['step' => 'remaining', 'resultCents' => $remainingBeforePenalty],
                ['step' => 'penalty', 'resultCents' => $deadlinePenalty],
                ['step' => 'pool', 'resultCents' => $pool],
            ],
        ];
    }

    /**
     * @param list<array{tab:string,weight:int}> $participants
     * @return list<array{tab:string,amountCents:int}>
     */
    public static function allocate(int $poolCents, array $participants): array
    {
        if ($poolCents < 0 || $poolCents > self::MAX_POOL_CENTS || !array_is_list($participants)) {
            throw new InvalidArgumentException('Invalid allocation input');
        }
        if ($participants === []) {
            if ($poolCents === 0) {
                return [];
            }
            throw new InvalidArgumentException('A nonzero pool requires participants');
        }

        $seen = [];
        $totalWeight = 0;
        $shares = [];
        foreach ($participants as $index => $participant) {
            if (!is_array($participant)
                || !array_key_exists('tab', $participant)
                || !is_string($participant['tab'])
                || $participant['tab'] === ''
                || trim($participant['tab']) !== $participant['tab']
                || preg_match('//u', $participant['tab']) !== 1
                || mb_strlen($participant['tab'], 'UTF-8') > 120
                || isset($seen[$participant['tab']])
                || !array_key_exists('weight', $participant)
                || !is_int($participant['weight'])
                || $participant['weight'] < 1
                || $participant['weight'] > 10000
            ) {
                throw new InvalidArgumentException("Invalid allocation participant {$index}");
            }
            $seen[$participant['tab']] = true;
            $totalWeight += $participant['weight'];
            $shares[] = ['tab' => $participant['tab'], 'weight' => $participant['weight']];
        }

        $allocated = 0;
        foreach ($shares as &$share) {
            $numerator = $poolCents * $share['weight'];
            $share['amountCents'] = intdiv($numerator, $totalWeight);
            $share['remainder'] = $numerator % $totalWeight;
            $allocated += $share['amountCents'];
        }
        unset($share);

        usort($shares, static fn(array $left, array $right): int =>
            $right['remainder'] <=> $left['remainder'] ?: strcmp($left['tab'], $right['tab'])
        );
        $leftover = $poolCents - $allocated;
        for ($index = 0; $index < $leftover; $index++) {
            $shares[$index]['amountCents']++;
        }
        usort($shares, static fn(array $left, array $right): int => strcmp($left['tab'], $right['tab']));

        return array_map(
            static fn(array $share): array => ['tab' => $share['tab'], 'amountCents' => $share['amountCents']],
            $shares
        );
    }

    private static function operand(array $operands, string $name, string $type): int|string|null
    {
        $envelope = $operands[$name] ?? null;
        if (!is_array($envelope)
            || !array_key_exists('value', $envelope)
            || !array_key_exists('effectiveDate', $envelope)
            || !array_key_exists('source', $envelope)
        ) {
            throw new InvalidArgumentException("Missing operand {$name}");
        }
        self::date($envelope['effectiveDate']);
        self::source($envelope['source']);
        $value = $envelope['value'];

        if ($type === 'date') {
            self::date($value);
            return $value;
        }
        if ($type === 'nullableDate') {
            if ($value !== null) {
                self::date($value);
            }
            return $value;
        }
        if (!is_int($value)) {
            throw new InvalidArgumentException("Operand {$name} must be an integer");
        }
        $maximum = $type === 'money' ? self::MAX_MONEY_CENTS : ($type === 'shaftBp' ? 20000 : 10000);
        if ($value < 0 || $value > $maximum) {
            throw new InvalidArgumentException("Operand {$name} is out of range");
        }
        return $value;
    }

    private static function paymentTotal(array $evidence, string $collection, string $dateField): int
    {
        if (!array_key_exists($collection, $evidence)
            || !is_array($evidence[$collection])
            || !array_is_list($evidence[$collection])
        ) {
            throw new InvalidArgumentException("Invalid {$collection} collection");
        }
        $total = 0;
        foreach ($evidence[$collection] as $index => $row) {
            if (!is_array($row)
                || !array_key_exists('amountCents', $row)
                || !is_int($row['amountCents'])
                || $row['amountCents'] < -self::MAX_MONEY_CENTS
                || $row['amountCents'] > self::MAX_MONEY_CENTS
                || !array_key_exists($dateField, $row)
                || !array_key_exists('source', $row)
            ) {
                throw new InvalidArgumentException("Invalid {$collection} row {$index}");
            }
            self::date($row[$dateField]);
            self::source($row['source']);
            $total += $row['amountCents'];
        }
        if (!is_int($total) || $total < 0 || $total > self::MAX_MONEY_CENTS) {
            throw new InvalidArgumentException("Invalid {$collection} aggregate");
        }
        return $total;
    }

    /** @param list<array<string,mixed>> $exclusions */
    private static function exclusions(array $exclusions): array
    {
        if (!array_is_list($exclusions)) {
            throw new InvalidArgumentException('Exclusions must be a list');
        }
        foreach ($exclusions as $index => $exclusion) {
            if (!is_array($exclusion)
                || !array_key_exists('code', $exclusion)
                || !is_string($exclusion['code'])
                || preg_match('/^[A-Z][A-Z0-9_]{2,79}$/D', $exclusion['code']) !== 1
                || !array_key_exists('effectiveDate', $exclusion)
                || !array_key_exists('source', $exclusion)
            ) {
                throw new InvalidArgumentException("Invalid exclusion {$index}");
            }
            self::date($exclusion['effectiveDate']);
            self::source($exclusion['source']);
        }
        return $exclusions;
    }

    private static function source(mixed $source): void
    {
        if (!is_array($source)
            || !array_key_exists('label', $source)
            || !is_string($source['label'])
            || trim($source['label']) === ''
            || !array_key_exists('locator', $source)
            || !is_string($source['locator'])
            || trim($source['locator']) === ''
            || !array_key_exists('contentSha256', $source)
            || !is_string($source['contentSha256'])
            || preg_match('/^[a-f0-9]{64}$/D', $source['contentSha256']) !== 1
        ) {
            throw new InvalidArgumentException('Invalid source provenance');
        }
    }

    private static function date(mixed $value): void
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('Date must be a string');
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('UTC'));
        $errors = DateTimeImmutable::getLastErrors();
        if ($date === false
            || ($errors !== false && ($errors['warning_count'] !== 0 || $errors['error_count'] !== 0))
            || $date->format('Y-m-d') !== $value
        ) {
            throw new InvalidArgumentException('Invalid exact date');
        }
    }

    private static function roundBasisPoints(int $amount, int $basisPoints): int
    {
        return intdiv($amount * $basisPoints + 5000, 10000);
    }
}

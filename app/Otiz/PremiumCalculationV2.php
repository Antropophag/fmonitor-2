<?php

declare(strict_types=1);

namespace FMonitor2\Otiz;

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
        $premium = PremiumCalculationV2Evidence::operand($operands, 'premiumCents', 'money');
        $shaftBp = PremiumCalculationV2Evidence::operand($operands, 'shaftBp', 'shaftBp');
        $progressBp = PremiumCalculationV2Evidence::operand($operands, 'progressBp', 'basisPoints');
        $deadlineDate = PremiumCalculationV2Evidence::operand($operands, 'deadlineDate', 'date');
        $reportDate = PremiumCalculationV2Evidence::operand($operands, 'reportDate', 'date');
        $completionDate = PremiumCalculationV2Evidence::operand($operands, 'completionDate', 'nullableDate');

        $closedBefore = PremiumCalculationV2Evidence::paymentTotal($paymentEvidence, 'closures', 'closedOn');
        PremiumCalculationV2Evidence::paymentTotal($paymentEvidence, 'actualPayouts', 'paidOn');
        $normalizedExclusions = PremiumCalculationV2Evidence::exclusions($exclusions);

        $comparisonDate = $completionDate ?? $reportDate;
        $deadline = new \DateTimeImmutable($deadlineDate, new \DateTimeZone('UTC'));
        $comparison = new \DateTimeImmutable($comparisonDate, new \DateTimeZone('UTC'));
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

    private static function roundBasisPoints(int $amount, int $basisPoints): int
    {
        return intdiv($amount * $basisPoints + 5000, 10000);
    }
}

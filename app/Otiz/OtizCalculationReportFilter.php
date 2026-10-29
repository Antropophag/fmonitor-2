<?php

declare(strict_types=1);

namespace FMonitor2\Otiz;

/** Pure report-only filtering of one saved calculation projection. */
final class OtizCalculationReportFilter
{
    private const FILTERS = ['excludeFrame', 'excludeCompleted', 'excludeDismissed', 'onlyReserved'];

    /**
     * @param array<string,mixed> $calculation
     * @param array<string,mixed> $filters
     * @return array{calculation:array<string,mixed>,filters:array<string,bool>,totalCents:int,reservedCents:int,excludedRecipients:int}
     */
    public static function apply(array $calculation, array $filters): array
    {
        $selected = self::filters($filters);
        $objects = $calculation['objects'] ?? null;
        $recipients = $calculation['recipients'] ?? null;
        if (!is_array($objects) || !is_array($recipients)) throw new \DomainException('INVALID_CALCULATION');

        $objectsById = [];
        $allowedObjects = [];
        foreach ($objects as $object) {
            if (!is_array($object)) throw new \DomainException('INVALID_CALCULATION');
            $objectId = self::positiveId($object['objectId'] ?? null);
            if ($objectId === null || isset($objectsById[$objectId])) throw new \DomainException('INVALID_CALCULATION');
            $objectsById[$objectId] = $object;
            $excluded = ($selected['excludeFrame'] && ($object['shaftBp'] ?? null) === 12500)
                || ($selected['excludeCompleted'] && self::completed($object['objectStatus'] ?? null));
            if (!$excluded) $allowedObjects[$objectId] = true;
        }

        $filteredRecipients = [];
        $objectTotals = [];
        foreach ($recipients as $recipient) {
            if (!is_array($recipient)) throw new \DomainException('INVALID_CALCULATION');
            $objectId = self::positiveId($recipient['objectId'] ?? null);
            $employeeId = $recipient['employeeId'] ?? null;
            $amount = $recipient['amountCents'] ?? null;
            $reserved = $recipient['reservedCents'] ?? null;
            if ($objectId === null || !isset($objectsById[$objectId]) || !is_string($employeeId) || $employeeId === ''
                || !is_int($amount) || $amount < 0 || !is_int($reserved) || $reserved < 0) {
                throw new \DomainException('INVALID_CALCULATION');
            }
            if (!isset($allowedObjects[$objectId])) continue;
            if ($selected['excludeDismissed'] && ($recipient['employment'] ?? null) === 'dismissed') continue;
            if ($selected['onlyReserved'] && $reserved <= 0) continue;
            $filteredRecipients[] = $recipient;
            $objectTotals[$objectId] ??= ['payableCents'=>0, 'reservedCents'=>0];
            $objectTotals[$objectId]['payableCents'] += $amount;
            $objectTotals[$objectId]['reservedCents'] += $reserved;
        }

        $filteredObjects = [];
        foreach ($objects as $object) {
            $objectId = (int) $object['objectId'];
            if (!isset($allowedObjects[$objectId], $objectTotals[$objectId])) continue;
            $object['payableCents'] = $objectTotals[$objectId]['payableCents'];
            $object['reservedCents'] = $objectTotals[$objectId]['reservedCents'];
            $filteredObjects[] = $object;
        }

        $total = array_sum(array_column($filteredRecipients, 'amountCents'));
        $reservedTotal = array_sum(array_column($filteredRecipients, 'reservedCents'));
        $filtered = $calculation;
        $filtered['objects'] = $filteredObjects;
        $filtered['recipients'] = $filteredRecipients;
        $filtered['totalCents'] = $total;
        $filtered['reservedCents'] = $reservedTotal;
        return [
            'calculation'=>$filtered,
            'filters'=>$selected,
            'totalCents'=>$total,
            'reservedCents'=>$reservedTotal,
            'excludedRecipients'=>count($recipients) - count($filteredRecipients),
        ];
    }

    /** @return array<string,bool> */
    private static function filters(array $input): array
    {
        if (array_diff(array_keys($input), self::FILTERS) !== []) throw new \DomainException('INVALID_FILTER');
        $out = [];
        foreach (self::FILTERS as $name) {
            $value = $input[$name] ?? '0';
            if ($value === '0' || $value === 0 || $value === false) $out[$name] = false;
            elseif ($value === '1' || $value === 1 || $value === true) $out[$name] = true;
            else throw new \DomainException('INVALID_FILTER');
        }
        return $out;
    }

    private static function completed(mixed $status): bool
    {
        return is_string($status) && in_array($status, ['completed', 'Работы завершены', 'Завершён', 'Завершен'], true);
    }

    private static function positiveId(mixed $value): ?int
    {
        return is_int($value) && $value > 0 ? $value : null;
    }
}

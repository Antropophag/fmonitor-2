<?php
declare(strict_types=1);

namespace FMonitor2\Workforce;

/** @internal Validates raw selected values and exact full-page consistency. */
final class DeliveryPage
{
    public const FIELDS = ['ID', 'ACTIVE', 'LAST_NAME', 'NAME', 'SECOND_NAME', 'WORK_POSITION', 'EMAIL', 'UF_XING', 'UF_DEPARTMENT', 'UF_EMPLOYMENT_DATE'];

    public static function read(string $body, array $departments, int $start, ?int $boundTotal, int $previousId): array
    {
        $object = DeliveryJson::object($body);
        $envelope = get_object_vars($object);
        if (array_key_exists('time', $envelope) && !$envelope['time'] instanceof \stdClass) self::fail();
        if (array_key_exists('error', $envelope)) {
            if (array_diff(array_keys($envelope), ['error', 'error_description', 'time']) !== []
                || !is_string($envelope['error'])
                || (array_key_exists('error_description', $envelope) && !is_string($envelope['error_description']))) self::fail();
            self::fail(BitrixWorkforceDeliveryReason::ApiFailed);
        }
        if (array_diff(array_keys($envelope), ['result', 'total', 'next', 'time']) !== []
            || !array_key_exists('result', $envelope) || !array_key_exists('total', $envelope)
            || !is_array($envelope['result']) || !array_is_list($envelope['result'])
            || !is_int($envelope['total']) || $envelope['total'] < 0) self::fail();
        $total = $envelope['total'];
        if ($total > 20000) self::fail(BitrixWorkforceDeliveryReason::LimitExceeded);
        if (($boundTotal !== null && $total !== $boundTotal) || count($envelope['result']) !== min(50, $total - $start)) self::pagination();
        $more = $start + count($envelope['result']) < $total;
        if ($more ? (($envelope['next'] ?? null) !== $start + 50) : array_key_exists('next', $envelope)) self::pagination();
        $records = [];
        foreach ($envelope['result'] as $person) {
            if (!$person instanceof \stdClass) self::fail();
            $fields = get_object_vars($person);
            if (count($fields) !== count(self::FIELDS) || array_diff(self::FIELDS, array_keys($fields)) !== []) self::fail();
            $id = self::positive($fields['ID']);
            if (!is_bool($fields['ACTIVE']) || !is_array($fields['UF_DEPARTMENT']) || !array_is_list($fields['UF_DEPARTMENT'])
                || count($fields['UF_DEPARTMENT']) < 1 || count($fields['UF_DEPARTMENT']) > 100) self::fail();
            $inScope = false;
            foreach ($fields['UF_DEPARTMENT'] as $department) {
                if (in_array(self::positive($department), $departments, true)) $inScope = true;
            }
            $selected = [];
            foreach (self::FIELDS as $field) {
                $value = $fields[$field];
                if (!in_array($field, ['ID', 'ACTIVE', 'UF_DEPARTMENT'], true)
                    && $value !== null && (!is_string($value) || strlen($value) > 4096)) self::fail();
                $selected[$field] = $value;
            }
            if (!$inScope) self::fail(BitrixWorkforceDeliveryReason::ScopeInvalid);
            if ($id <= $previousId) self::pagination();
            $previousId = $id;
            $records[] = $selected;
        }
        return [$total, $records, $previousId, $more];
    }

    private static function positive(mixed $value): int
    {
        if (is_int($value) && $value > 0) return $value;
        if (is_string($value) && preg_match('/^[1-9][0-9]*$/D', $value) === 1
            && (strlen($value) < strlen((string)PHP_INT_MAX)
                || (strlen($value) === strlen((string)PHP_INT_MAX) && strcmp($value, (string)PHP_INT_MAX) <= 0))) return (int)$value;
        self::fail();
    }

    private static function pagination(): never
    {
        self::fail(BitrixWorkforceDeliveryReason::PaginationInvalid);
    }

    private static function fail(BitrixWorkforceDeliveryReason $reason = BitrixWorkforceDeliveryReason::SchemaInvalid): never
    {
        throw new DeliveryFailure($reason);
    }
}

<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final class MariaDbLegacyInstallationObject
{
    public function __construct(
        private readonly \mysqli $connection,
        private readonly string $tablePrefix = '',
    ) {
        MariaDbSchemaInspector::validateTablePrefix($this->tablePrefix);
    }

    /** @return array{address: string, entrance: string, objectRegistrationNumber: string, plannedStartDate: ?string, plannedFinishDate: ?string, ptoActDate: ?string} */
    public function getInstallationObjectSnapshot(int $installationObjectId): array
    {
        $statement = $this->connection->prepare(
            'SELECT id,ordadr_address,entrance,regnumber,workdatestart,workdateendadjusted,plan_finish_date,ptoactdate '
            . "FROM `{$this->tablePrefix}fm_maintable` WHERE id=? LIMIT 1",
        );
        $statement->bind_param('i', $installationObjectId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        if ($row === null) {
            throw new \RuntimeException('Legacy installation object not found.');
        }

        $adjustedFinishDate = self::optionalDate($row['workdateendadjusted']);

        return [
            'address' => trim((string) $row['ordadr_address']),
            'entrance' => trim((string) $row['entrance']),
            'objectRegistrationNumber' => trim((string) $row['regnumber']),
            'plannedStartDate' => self::optionalDate($row['workdatestart']),
            'plannedFinishDate' => $adjustedFinishDate ?? self::optionalDate($row['plan_finish_date']),
            'ptoActDate' => self::optionalDate($row['ptoactdate']),
        ];
    }

    private static function optionalDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized === '' || preg_match('/^0+$/D', $normalized) === 1 || str_starts_with($normalized, '0000-00-00')) {
            return null;
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:\D|$)/D', $normalized, $parts) !== 1
            || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
            throw new \RuntimeException('Unrecognized legacy calendar date.');
        }

        return $parts[1] . '-' . $parts[2] . '-' . $parts[3];
    }
}

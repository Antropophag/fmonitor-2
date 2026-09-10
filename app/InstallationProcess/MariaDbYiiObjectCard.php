<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

use yii\db\Connection;

final readonly class MariaDbYiiObjectCard
{
    public function __construct(
        private Connection $db,
        private string $prefix,
        private string $legacyPrefix,
        private MariaDbYiiObjectCardProjection $projection,
    ) {
        foreach ([$prefix, $legacyPrefix] as $value) {
            if (strlen($value) > 28 || preg_match('/^[A-Za-z0-9_]*$/D', $value) !== 1) {
                throw new \InvalidArgumentException('Invalid table prefix.');
            }
        }
    }

    public function authorized(int $actorId): bool
    {
        $p = $this->prefix;
        $sql = "SELECT 1 FROM `{$p}fm2_pilot_users` u JOIN `{$p}fm2_pilot_user_roles` ur ON ur.user_id=u.user_id JOIN `{$p}fm2_pilot_roles` r ON r.role_id=ur.role_id JOIN `{$p}fm2_pilot_role_permissions` rp ON rp.role_id=r.role_id WHERE u.user_id=:actor AND u.status=1 AND u.activation_state='active' AND r.status=1 AND BINARY rp.permission='objects.read' LIMIT 1";
        return (bool) $this->db->createCommand($sql, [':actor' => $actorId])->queryScalar();
    }

    public function read(int $actorId, int $objectId): ?array
    {
        try {
            $p = $this->prefix;
            $l = $this->legacyPrefix;
            $sql = "SELECT c.id case_id,c.process_state,c.actual_start_date,c.opened_at,c.opened_by_user_id,l.id legacy_id,l.ordadr_address,l.entrance,l.regnumber,l.workdatestart,l.workdateendadjusted,l.plan_finish_date,l.ptoactdate,d.schema_version detail_schema,d.payload_json detail_payload,d.content_sha256 detail_hash,m.category migration_category,EXISTS(SELECT 1 FROM `{$p}fm2_pilot_completion_facts` f WHERE f.installation_case_id=c.id AND f.fact_type='pto_act') has_pto FROM `{$p}fm2_installation_cases` c LEFT JOIN `{$l}fm_maintable` l ON l.id=c.legacy_installation_object_id LEFT JOIN `{$p}fm2_pilot_object_details` d ON d.object_id=c.legacy_installation_object_id LEFT JOIN `{$p}fm2_migration_classification_provenance` m ON m.output_kind='operational_case' AND m.output_id=c.id AND m.legacy_object_id=c.legacy_installation_object_id WHERE c.legacy_installation_object_id=:object LIMIT 2";
            $rows = $this->db->createCommand($sql, [':object' => $objectId])->queryAll();
            if ($rows === []) {
                return null;
            }
            if (count($rows) !== 1) {
                throw new \RuntimeException('Malformed object card identity.');
            }
            $row = $rows[0];
            if ((int) $row['legacy_id'] !== $objectId || trim((string) $row['ordadr_address']) === '' || trim((string) $row['entrance']) === '' || trim((string) $row['regnumber']) === '') {
                return null;
            }
            $opening = [$row['actual_start_date'], $row['opened_at'], $row['opened_by_user_id']];
            $present = count(array_filter($opening, static fn ($value): bool => $value !== null));
            if (!in_array($present, [0, 3], true)) {
                throw new \RuntimeException('Malformed opening tuple.');
            }
            if ($present === 3 && (YiiObjectCardValues::date($row['actual_start_date']) !== $row['actual_start_date'] || !YiiObjectCardValues::rfc3339($row['opened_at']) || YiiObjectCardValues::positiveId($row['opened_by_user_id']) === null)) {
                throw new \RuntimeException('Malformed opening tuple.');
            }
            if ($this->hasActiveCutoverProvenance((int) $row['case_id'])) {
                throw new \RuntimeException('Active cutover card requires its canonical provenance reader.');
            }
            if ($row['migration_category'] !== null && $row['migration_category'] !== 'native_candidate') {
                throw new \RuntimeException('Malformed object provenance.');
            }
            $detailValid = is_string($row['detail_payload']) && is_string($row['detail_hash']) && hash_equals($row['detail_hash'], hash('sha256', $row['detail_payload']));
            $detail = null;
            if ($detailValid) {
                try {
                    $detail = json_decode($row['detail_payload'], true, 512, JSON_THROW_ON_ERROR);
                    $detailValid = $this->validDetails($detail, $row['detail_schema'], $objectId);
                } catch (\JsonException) {
                    $detailValid = false;
                }
            }
            $card = [
                'id' => $objectId,
                'caseId' => (int) $row['case_id'],
                'address' => trim((string) $row['ordadr_address']),
                'entrance' => trim((string) $row['entrance']),
                'registrationNumber' => trim((string) $row['regnumber']),
                'plannedStartDate' => YiiObjectCardValues::date($row['workdatestart']),
                'plannedFinishDate' => YiiObjectCardValues::date($row['workdateendadjusted']) ?? YiiObjectCardValues::date($row['plan_finish_date']),
                'opened' => $present === 3,
                'actualStartDate' => $row['actual_start_date'],
                'openedAt' => $row['opened_at'],
                'openedByUserId' => $present === 3 ? (int) $row['opened_by_user_id'] : null,
                'actualStartDateUnknownAtCutover' => false,
                'activeCaseProvenance' => null,
                'dataOrigin' => $row['migration_category'] === 'native_candidate' ? 'migration_native' : 'native',
                'objectDetails' => $detailValid ? $detail : null,
                'objectDetailsStatus' => $row['detail_payload'] === null ? 'unavailable' : ($detailValid ? 'available' : 'corrupt'),
                'events' => [],
                'hasPtoAct' => (bool) $row['has_pto'] || $this->optionalLegacyDate($row['ptoactdate']) !== null,
            ];
            return $this->projection->decorate($card, $actorId, (int) $row['case_id'], (string) $row['process_state']);
        } catch (\DomainException | \RuntimeException $error) {
            throw $error;
        } catch (\Throwable $error) {
            throw new \RuntimeException('Object card unavailable.', 0, $error);
        }
    }

    private function hasActiveCutoverProvenance(int $caseId): bool
    {
        $table = $this->prefix . 'fm2_active_case_provenance';
        $exists = (bool) $this->db->createCommand(
            'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table LIMIT 1',
            [':table' => $table],
        )->queryScalar();
        if (!$exists) {
            return false;
        }
        return (bool) $this->db->createCommand(
            "SELECT 1 FROM `{$table}` WHERE installation_case_id=:case LIMIT 1",
            [':case' => $caseId],
        )->queryScalar();
    }

    private function validDetails(mixed $detail, mixed $schema, int $objectId): bool
    {
        if ($schema !== 'technical-object-detail-v1' || !is_array($detail)
            || ($detail['schemaVersion'] ?? null) !== $schema
            || ($detail['objectId'] ?? null) !== $objectId
            || !is_array($detail['fields'] ?? null)) {
            return false;
        }
        foreach ($detail['fields'] as $field) {
            if (!is_array($field) || (!array_key_exists('raw', $field) && !array_key_exists('display', $field))) {
                return false;
            }
        }
        return true;
    }

    private function optionalLegacyDate(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '' || preg_match('/^0+(?:-00-00)?(?:[ T].*)?$/D', trim((string) $value)) === 1) {
            return null;
        }
        $date = YiiObjectCardValues::date($value);
        if ($date === null) {
            throw new \RuntimeException('Malformed legacy PTO date.');
        }
        return $date;
    }
}

<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

use yii\db\Connection;
use yii\db\Transaction;

final readonly class MariaDbYiiInspectionPlanning
{
    public function __construct(private Connection $db, private string $prefix)
    {
        if (strlen($prefix) > 28 || preg_match('/^[A-Za-z0-9_]*$/D', $prefix) !== 1) {
            throw new \InvalidArgumentException();
        }
    }
    public function begin(): Transaction
    {
        return $this->db->beginTransaction();
    }
    public function assertReady(): void
    {
        if (!MariaDbYiiSchemaFingerprint::planningReady($this->db, $this->prefix)) {
            throw new \RuntimeException('Planning schema unavailable.');
        }
    }
    public function actorCanSchedule(int $actor): bool
    {
        $p = $this->prefix;
        $sql = "SELECT 1 FROM `{$p}fm2_pilot_users` u JOIN `{$p}fm2_pilot_user_roles` ur ON ur.user_id=u.user_id JOIN `{$p}fm2_pilot_roles` r ON r.role_id=ur.role_id JOIN `{$p}fm2_pilot_role_permissions` rp ON rp.role_id=r.role_id WHERE u.user_id=:id AND u.status=1 AND u.activation_state='active' AND r.status=1 AND BINARY rp.permission='inspection.schedule' LIMIT 1";
        return (bool)$this->db->createCommand($sql, [':id' => $actor])->queryScalar();
    }
    public function lockEligibleCase(int $object): ?array
    {
        $p = $this->prefix;
        $sql = "SELECT c.id,o.control_engineer_user_id FROM `{$p}fm2_installation_cases` c JOIN `{$p}fm2_assignment_orders` o ON o.installation_case_id=c.id AND o.version_no=(SELECT MAX(x.version_no) FROM `{$p}fm2_assignment_orders` x WHERE x.installation_case_id=c.id) WHERE c.legacy_installation_object_id=:object AND c.process_state IN('working','needs_assignment_change') AND o.status='registered' AND o.control_engineer_user_id>0 LIMIT 1 FOR UPDATE";
        $row = $this->db->createCommand($sql, [':object' => $object])->queryOne();
        return $row ? ['caseId' => (int)$row['id'],'engineerId' => (int)$row['control_engineer_user_id']] : null;
    }
    public function findSchedule(int $case, int $engineer, string $date): ?int
    {
        $p = $this->prefix;
        $sql = "SELECT id FROM `{$p}fm2_pilot_inspection_schedules` WHERE installation_case_id=:case AND control_engineer_user_id=:engineer AND inspection_date=:date";
        $id = $this->db->createCommand($sql, [':case' => $case,':engineer' => $engineer,':date' => $date])->queryScalar();
        return $id === false ? null : (int)$id;
    }
    public function appendSchedule(array $case, int $object, string $date, int $actor, string $stamp): int
    {
        $this->db->createCommand()->insert($this->prefix . 'fm2_pilot_inspection_schedules', ['installation_case_id' => $case['caseId'],'legacy_object_id' => $object,'control_engineer_user_id' => $case['engineerId'],'inspection_date' => $date,'scheduled_by_user_id' => $actor,'scheduled_at' => $stamp])->execute();
        return(int)$this->db->getLastInsertID();
    }
    public function appendEvent(int $id, array $case, string $date, int $actor, string $stamp): void
    {
        $payload = json_encode(['scheduleId' => $id,'inspectionDate' => $date,'controlEngineerUserId' => $case['engineerId']], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $table = $this->prefix . 'fm2_pilot_inspection_schedule_events';
        $sql = "INSERT INTO `{$table}`(schedule_id,installation_case_id,event_type,payload_json,actor_user_id,occurred_at) VALUES(:schedule,:case,'inspection_scheduled',:payload,:actor,:occurred)";
        $this->db->createCommand($sql, [':schedule' => $id,':case' => $case['caseId'],':payload' => $payload,':actor' => $actor,':occurred' => $stamp])->execute();
    }
}

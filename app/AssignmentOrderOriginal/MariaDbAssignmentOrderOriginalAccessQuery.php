<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

use FMonitor2\AssignmentOrderComposition\AssignmentOrderApplicationReaderFactory;

/** One read-only admission seam for original documents and submission forms. */
final readonly class MariaDbAssignmentOrderOriginalAccessQuery implements AssignmentOrderOriginalAccessQuery
{
    public function __construct(
        private \mysqli $db,
        private \yii\db\Connection $yiiDb,
        private string $prefix,
        private AssignmentOrderOriginalSubmissionQuery $submission,
    ) {
        if (strlen($prefix) > 28 || preg_match('/^[A-Za-z0-9_]*$/D', $prefix) !== 1) {
            throw new \InvalidArgumentException('Invalid table prefix.');
        }
    }

    public function readAccess(int $actorId, int $objectId): array
    {
        if ($actorId < 1 || $objectId < 1) return self::denied();
        try {
            $rows = $this->grants($actorId);
            $roles = array_values(array_unique(array_column($rows, 'code')));
            $permissions = array_values(array_unique(array_column($rows, 'permission')));
            if (!in_array('assignment_order.original.read', $permissions, true)) return self::denied();
            $operator = array_intersect($roles, ['fkr_operator', 'manager']) !== [];
            $documentReader = $operator || in_array('otiz_specialist', $roles, true);
            if (!$documentReader && in_array('construction_control_engineer', $roles, true)) {
                $application = AssignmentOrderApplicationReaderFactory::create($this->db, $this->prefix)->readCurrent($objectId);
                if ($application->status === 'unavailable') return self::unavailable();
                $documentReader = $application->status === 'found'
                    && (int) $application->value['application']['engineerUserId'] === $actorId;
            }
            if (!$documentReader) return self::denied();
            return [
                'status' => 'allowed', 'canRead' => true,
                'canUpload' => $operator && in_array('assignment_order.original.upload', $permissions, true),
                'canCorrect' => $operator && in_array('assignment_order.original.correct', $permissions, true),
            ];
        } catch (\Throwable) {
            return self::unavailable();
        }
    }

    public function readSubmissionForm(int $actorId, int $objectId, int $orderId): array
    {
        $access = $this->readAccess($actorId, $objectId);
        if ($access['status'] === 'unavailable') return ['status' => 'error', 'reasonCode' => 'SERVICE_UNAVAILABLE'];
        if (!$access['canRead']) return ['status' => 'error', 'reasonCode' => 'ACCESS_DENIED'];
        return $this->submission->readSubmissionForm($actorId, $objectId, $orderId);
    }

    private function grants(int $actorId): array
    {
        $p = $this->prefix;
        return $this->yiiDb->createCommand(
            "SELECT DISTINCT r.code,rp.permission FROM `{$p}fm2_pilot_users` u JOIN `{$p}fm2_pilot_user_roles` ur ON ur.user_id=u.user_id JOIN `{$p}fm2_pilot_roles` r ON r.role_id=ur.role_id JOIN `{$p}fm2_pilot_role_permissions` rp ON rp.role_id=r.role_id WHERE u.user_id=:actor AND u.status=1 AND BINARY u.activation_state=BINARY 'active' AND r.status=1",
            [':actor' => $actorId],
        )->queryAll();
    }

    private static function denied(): array { return ['status' => 'denied', 'canRead' => false, 'canUpload' => false, 'canCorrect' => false]; }
    private static function unavailable(): array { return ['status' => 'unavailable', 'canRead' => false, 'canUpload' => false, 'canCorrect' => false]; }
}

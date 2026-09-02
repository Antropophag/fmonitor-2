<?php

declare(strict_types=1);

namespace FMonitor2\InspectionEvidence;

final class MariaDbInspectionCaseDirectory
{
    private ?int $order = null;
    private array $snapshots = [];

    public function __construct(private readonly \mysqli $db, private readonly string $prefix)
    {
    }

    public function installationCase(int $id, int $revision): ?array
    {
        $query = 'SELECT c.process_state,o.id order_id,o.control_engineer_user_id'
            .' FROM '.$this->table('fm2_installation_cases').' c'
            .' LEFT JOIN '.$this->table('fm2_assignment_orders').' o ON o.id=('
            .'SELECT oo.id FROM '.$this->table('fm2_assignment_orders').' oo'
            ." WHERE oo.installation_case_id=c.id AND oo.status='registered'"
            .' ORDER BY oo.version_no DESC,oo.id DESC LIMIT 1) WHERE c.id=?';
        $statement = $this->db->prepare($query);
        $statement->bind_param('i', $id);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        if ($row === null) {
            return null;
        }

        $this->order = $row['order_id'] === null ? null : (int) $row['order_id'];
        $installerIds = [];
        if ($this->order !== null) {
            $installers = $this->db->prepare(
                'SELECT installer_tab_id FROM '.$this->table('fm2_order_installers')
                .' WHERE assignment_order_id=? AND valid_to IS NULL ORDER BY installer_tab_id'
            );
            $installers->bind_param('i', $this->order);
            $installers->execute();
            $installerIds = array_map(
                'intval',
                array_column($installers->get_result()->fetch_all(MYSQLI_ASSOC), 'installer_tab_id'),
            );
        }

        $kind = 'operational_case';
        $subject = (string) $id;
        $association = $this->db->prepare(
            'SELECT template_snapshot_id FROM '.$this->table('fm2_checklist_template_associations')
            .' WHERE subject_kind=? AND subject_id=?'
        );
        $association->bind_param('ss', $kind, $subject);
        $association->execute();
        $associationRow = $association->get_result()->fetch_assoc();

        return [
            'state' => $row['process_state'],
            'revision' => $revision,
            'templateId' => $associationRow === null ? 0 : (int) $associationRow['template_snapshot_id'],
            'assignedControlEngineerUserId' => $row['control_engineer_user_id'] === null
                ? null
                : (int) $row['control_engineer_user_id'],
            'registeredInstallerTabIds' => $installerIds,
        ];
    }

    public function installer(int $id): ?array
    {
        if ($this->order === null) {
            return null;
        }
        $statement = $this->db->prepare(
            'SELECT installer_tab_id,fio_snapshot,position_snapshot,employment_status_snapshot,'
            .'employed_to_snapshot,workforce_source_updated_at_snapshot'
            .' FROM '.$this->table('fm2_order_installers')
            .' WHERE assignment_order_id=? AND installer_tab_id=? AND valid_to IS NULL'
        );
        $statement->bind_param('ii', $this->order, $id);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        if ($row === null) {
            return null;
        }

        return $this->snapshots[$id] = [
            'tabId' => (int) $row['installer_tab_id'],
            'fullName' => (string) $row['fio_snapshot'],
            'position' => (string) $row['position_snapshot'],
            'employmentStatus' => (string) $row['employment_status_snapshot'],
            'dismissalEffectiveAt' => $row['employed_to_snapshot'],
            'sourceUpdatedAt' => (string) $row['workforce_source_updated_at_snapshot'],
        ];
    }

    public function snapshot(int $id): array
    {
        return $this->snapshots[$id];
    }

    private function table(string $name): string
    {
        return '`'.$this->prefix.$name.'`';
    }
}

<?php

declare(strict_types=1);

namespace FMonitor2\InspectionEvidence;

final class MariaDbInspectionEvidenceReader
{
    public function __construct(private readonly \mysqli $db, private readonly string $prefix)
    {
    }

    public function find(int $caseId, string $operationId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT o.*,r.revision_no current_revision FROM '.$this->table('fm2_checklist_operations').' o'
            .' JOIN '.$this->table('fm2_checklist_revisions')
            .' r ON r.installation_case_id=o.installation_case_id'
            .' WHERE o.installation_case_id=? AND o.client_operation_id=?'
        );
        $statement->bind_param('is', $caseId, $operationId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        if ($row === null) {
            return null;
        }

        $installerStatement = $this->db->prepare(
            'SELECT installer_tab_id,fio_snapshot,position_snapshot FROM '
            .$this->table('fm2_checklist_operation_installers')
            .' WHERE client_operation_id=? ORDER BY installer_tab_id'
        );
        $installerStatement->bind_param('s', $operationId);
        $installerStatement->execute();
        $installers = array_map(
            static fn (array $installer): InstallerEvidence => new InstallerEvidence(
                (int) $installer['installer_tab_id'],
                (string) $installer['fio_snapshot'],
                (string) $installer['position_snapshot'],
            ),
            $installerStatement->get_result()->fetch_all(MYSQLI_ASSOC),
        );
        $payload = json_decode($row['payload_json'], true, 512, JSON_THROW_ON_ERROR);

        return [
            'clientOperationId' => (string) $row['client_operation_id'],
            'installationCaseId' => (int) $row['installation_case_id'],
            'sectionId' => (int) $row['section_id'],
            'itemId' => (int) $row['item_id'],
            'actorUserId' => (int) $row['actor_user_id'],
            'assignedControlEngineerUserIdAtReceipt' => isset($payload['assignedControlEngineerUserIdAtReceipt'])
                ? (int) $payload['assignedControlEngineerUserIdAtReceipt']
                : null,
            'deviceTime' => (string) $row['device_time'],
            'serverReceivedAt' => (string) $row['server_received_at'],
            'baseRevision' => (int) $row['base_revision'],
            'acceptedRevision' => (int) $row['accepted_revision'],
            'templateId' => (int) $row['template_snapshot_id'],
            'templateVersion' => (string) $row['template_snapshot_version'],
            'templateSha256' => (string) $row['template_content_sha256'],
            'currentChecklistRevision' => (int) $row['current_revision'],
            'installerSnapshots' => $installers,
            'normalizedPayload' => (string) ($payload['normalizedPayload'] ?? ''),
        ];
    }

    private function table(string $name): string
    {
        return '`'.$this->prefix.$name.'`';
    }
}

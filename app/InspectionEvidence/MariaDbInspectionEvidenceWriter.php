<?php

declare(strict_types=1);

namespace FMonitor2\InspectionEvidence;

final class MariaDbInspectionEvidenceWriter
{
    public function __construct(
        private readonly \mysqli $db,
        private readonly string $prefix,
        private readonly MariaDbInspectionCaseDirectory $cases,
    ) {
    }

    public function append(int $caseId, array $evidence): void
    {
        $audit = json_encode([
            'installerTabIds' => array_map(
                static fn (InstallerEvidence $installer): int => $installer->tabId,
                $evidence['installerSnapshots'],
            ),
            'assignedControlEngineerUserIdAtReceipt' => $evidence['assignedControlEngineerUserIdAtReceipt'],
            'normalizedPayload' => $evidence['normalizedPayload'],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $statement = $this->db->prepare(
            'INSERT INTO '.$this->table('fm2_checklist_operations')
            ."(installation_case_id,client_operation_id,device_installation_id,operation_type,section_id,item_id,"
            .'actor_user_id,device_time,server_received_at,base_revision,accepted_revision,payload_json,'
            .'template_snapshot_id,template_snapshot_version,template_content_sha256)'
            ." VALUES (?,?,?,'item_completed',?,?,?,?,?,?,?,?,?,?,?)"
        );
        $normalized = json_decode($evidence['normalizedPayload'], true, 512, JSON_THROW_ON_ERROR);
        $deviceId = $normalized['deviceInstallationId'];
        $statement->bind_param(
            'issiiissiisiss',
            $caseId,
            $evidence['clientOperationId'],
            $deviceId,
            $evidence['sectionId'],
            $evidence['itemId'],
            $evidence['actorUserId'],
            $evidence['deviceTime'],
            $evidence['serverReceivedAt'],
            $evidence['baseRevision'],
            $evidence['acceptedRevision'],
            $audit,
            $evidence['templateId'],
            $evidence['templateVersion'],
            $evidence['templateSha256'],
        );
        $statement->execute();

        $installerStatement = $this->db->prepare(
            'INSERT INTO '.$this->table('fm2_checklist_operation_installers')
            .'(client_operation_id,installer_tab_id,fio_snapshot,position_snapshot,employment_status_snapshot,'
            .'dismissal_effective_at_snapshot,workforce_source_updated_at_snapshot,assignment_source)'
            ." VALUES (?,?,?,?,?,?,?,'completion')"
        );
        foreach ($evidence['installerSnapshots'] as $installer) {
            $raw = $this->cases->snapshot($installer->tabId);
            $operationId = $evidence['clientOperationId'];
            $tabId = $installer->tabId;
            $fullName = $installer->fullName;
            $position = $installer->position;
            $status = $raw['employmentStatus'];
            $dismissal = $raw['dismissalEffectiveAt'];
            $updated = $raw['sourceUpdatedAt'];
            $installerStatement->bind_param(
                'sisssss',
                $operationId,
                $tabId,
                $fullName,
                $position,
                $status,
                $dismissal,
                $updated,
            );
            $installerStatement->execute();
        }

        $revision = $this->db->prepare(
            'UPDATE '.$this->table('fm2_checklist_revisions')
            .' SET revision_no=?,updated_at=? WHERE installation_case_id=?'
        );
        $revision->bind_param('isi', $evidence['acceptedRevision'], $evidence['serverReceivedAt'], $caseId);
        $revision->execute();
        if ($revision->affected_rows !== 1) {
            throw new \RuntimeException('Inspection revision is unavailable.');
        }
    }

    private function table(string $name): string
    {
        return '`'.$this->prefix.$name.'`';
    }
}

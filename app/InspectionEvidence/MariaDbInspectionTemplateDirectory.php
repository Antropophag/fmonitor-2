<?php

declare(strict_types=1);

namespace FMonitor2\InspectionEvidence;

final class MariaDbInspectionTemplateDirectory
{
    public function __construct(private readonly \mysqli $db, private readonly string $prefix)
    {
    }

    public function template(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $statement = $this->db->prepare(
            'SELECT snapshot_version,content_sha256,payload_json FROM '
            .$this->table('fm2_checklist_template_snapshots').' WHERE id=?'
        );
        $statement->bind_param('i', $id);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        if ($row === null) {
            return null;
        }

        $payload = json_decode($row['payload_json'], true, 512, JSON_THROW_ON_ERROR);
        $items = [];
        foreach (($payload['sections'] ?? []) as $section) {
            $sectionId = (int) ($section['id'] ?? 0);
            $items[$sectionId] = array_map(
                static fn (array $item): int => (int) $item['id'],
                $section['items'] ?? [],
            );
        }

        return [
            'version' => (string) $row['snapshot_version'],
            'sha256' => (string) $row['content_sha256'],
            'items' => $items,
        ];
    }

    private function table(string $name): string
    {
        return '`'.$this->prefix.$name.'`';
    }
}

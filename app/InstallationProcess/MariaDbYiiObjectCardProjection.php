<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

use yii\db\Connection;

final readonly class MariaDbYiiObjectCardProjection
{
    public function __construct(private Connection $db, private string $prefix, private MariaDbYiiObjectQueueProjection $completion)
    {
        if (strlen($prefix) > 28 || preg_match('/^[A-Za-z0-9_]*$/D', $prefix) !== 1) {
            throw new \InvalidArgumentException('Invalid table prefix.');
        }
    }

    public function decorate(array $card, int $actorId, int $caseId, string $state,?string$historyCursor=null): array
    {
        $application = $this->one("SELECT * FROM `{$this->prefix}fm2_assignment_order_applications` WHERE installation_case_id=:case ORDER BY application_sequence DESC LIMIT 1", [':case' => $caseId]);
        $selection = $this->one("SELECT s.*,r.current_revision_id,v.revision_number,v.document_date,v.uploaded_at_utc,v.actor_user_id,v.byte_size FROM `{$this->prefix}fm2_assignment_order_selections` s LEFT JOIN `{$this->prefix}fm2_assignment_order_original_roots` r ON r.installation_case_id=s.installation_case_id AND r.assignment_order_id=s.assignment_order_id AND r.composition_identity=s.composition_identity AND r.composition_sha256=s.composition_sha256 LEFT JOIN `{$this->prefix}fm2_assignment_order_original_revisions` v ON v.root_original_id=r.root_original_id AND v.revision_id=r.current_revision_id WHERE s.installation_case_id=:case AND s.selection_revision=(SELECT MAX(x.selection_revision) FROM `{$this->prefix}fm2_assignment_order_selections` x WHERE x.installation_case_id=:case2) LIMIT 2", [':case' => $caseId, ':case2' => $caseId]);
        [$card['events'],$card['historyNext']] = $this->events($caseId,(int)$card['id'],$historyCursor);
        $card['pendingComposition'] = $selection !== null && $selection['current_revision_id'] === null
            ? $this->pendingComposition($card, $selection)
            : null;
        if ($card['opened'] && $application === null) {
            throw new \RuntimeException('Opened native card has no applied composition.');
        }
        if ($application !== null) {
            $card = $this->applied($card, $application);
            if (!$card['opened'] && $selection !== null && $selection['current_revision_id'] !== null) {
                $card = $this->confirmed($card, $selection, (int) $application['application_sequence']);
            } elseif (!$card['opened']) {
                $card['status'] = 'Требуется распоряжение';
                $card['order'] = null;
                $card['controlEngineer'] = null;
                unset($card['confirmedOriginal']);
            }
        } elseif ($selection !== null && $selection['current_revision_id'] !== null) {
            $card = $this->confirmed($card, $selection, 0);
        } else {
            $card += ['status' => 'Требуется распоряжение', 'applicationId' => null, 'order' => null, 'controlEngineer' => null];
        }
        if ($card['opened'] && !in_array($state, ['working', 'needs_assignment_change'], true)) {
            throw new \RuntimeException('Malformed process state.');
        }
        if (!$card['opened'] && !in_array($state, ['needs_assignment_order', 'assignment_order_prepared'], true)) {
            throw new \RuntimeException('Malformed process state.');
        }
        if ($card['opened']) {
            $card['status'] = 'В работе';
            $card = $this->completion->decorate([$card + ['nextStep' => 'Продолжить монтажные работы']], $actorId)[0];
            if ($state === 'needs_assignment_change') { $card['status'] = 'Требуется изменение'; }
        }
        $card['completionWritable'] = $state === 'working';
        $card['equipmentFacts'] = $this->equipmentFacts((int) $card['id']);
        unset($card['caseId'], $card['nextStep']);
        return $this->actorNames($card);
    }

    private function pendingComposition(array $card, array $row): array
    {
        $orderId = YiiObjectCardValues::positiveId($row['assignment_order_id']);
        $members = $orderId === null ? [] : $this->db->createCommand(
            "SELECT installer_tab_id,fio_snapshot,position_snapshot FROM `{$this->prefix}fm2_assignment_order_selection_members` WHERE assignment_order_id=:order ORDER BY installer_tab_id",
            [':order' => $orderId],
        )->queryAll();
        $installers = array_map(static fn (array $person): array => [
            'tabId' => (int) $person['installer_tab_id'],
            'fullName' => (string) $person['fio_snapshot'],
            'position' => (string) $person['position_snapshot'],
            'status' => 'employed',
        ], $members);
        $engineer = [
            'userId' => (int) $row['control_engineer_user_id'],
            'fullName' => (string) $row['control_engineer_fio_snapshot'],
            'position' => (string) $row['control_engineer_position_snapshot'],
        ];
        if ($orderId === null || $members === []) {
            throw new \RuntimeException('Malformed pending composition.');
        }
        YiiObjectCardApplicationIntegrity::validateComposition(
            $row + ['object_id' => $card['id']],
            ['selectedInstallers' => $installers, 'selectedEngineer' => $engineer],
        );
        return [
            'orderId' => $orderId,
            'version' => (int) $row['order_version'],
            'installers' => $installers,
            'engineer' => $engineer,
        ];
    }

    private function applied(array $card, array $row): array
    {
        $selected = json_decode((string) $row['selected_snapshot_json'], true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($selected) || array_keys($selected) !== ['selectedInstallers', 'selectedEngineer']) {
            throw new \RuntimeException('Malformed applied composition.');
        }
        if ((int) $row['installation_case_id'] !== (int) $card['caseId'] || (int) $row['object_id'] !== (int) $card['id']) {
            throw new \RuntimeException('Malformed applied object linkage.');
        }
        YiiObjectCardApplicationIntegrity::validateComposition($row, $selected);
        $installers = array_map(static fn (array $person): array => $person + ['status' => 'employed'], $selected['selectedInstallers']);
        $engineer = $selected['selectedEngineer'];
        $card['status'] = $card['opened'] ? 'Монтажные работы' : 'Готов к открытию';
        $card['applicationId'] = (int) $row['application_id'];
        $card['confirmedOriginal'] = ['orderId' => (int) $row['assignment_order_id'], 'revisionId' => (string) $row['original_revision_id'], 'sequence' => (int) $row['application_sequence']];
        $card['order'] = $this->order($card['id'], $row, $engineer, $installers, 'applied', $this->utc($row['applied_at_utc']));
        $card['controlEngineer'] = $engineer;
        return $card;
    }

    private function confirmed(array $card, array $row, int $sequence): array
    {
        $orderId = YiiObjectCardValues::positiveId($row['assignment_order_id']);
        $revision = trim((string) $row['current_revision_id']);
        $members = $this->db->createCommand("SELECT installer_tab_id,fio_snapshot,position_snapshot FROM `{$this->prefix}fm2_assignment_order_selection_members` WHERE assignment_order_id=:order ORDER BY installer_tab_id", [':order' => $orderId])->queryAll();
        if ($orderId === null || $revision === '' || $members === []) {
            throw new \RuntimeException('Malformed confirmed original.');
        }
        $installers = array_map(static fn (array $person): array => ['tabId' => (int) $person['installer_tab_id'], 'fullName' => (string) $person['fio_snapshot'], 'position' => (string) $person['position_snapshot'], 'status' => 'employed'], $members);
        $engineer = ['userId' => (int) $row['control_engineer_user_id'], 'fullName' => (string) $row['control_engineer_fio_snapshot'], 'position' => (string) $row['control_engineer_position_snapshot']];
        $card['status'] = 'Готов к открытию';
        $card['applicationId'] ??= null;
        $card['confirmedOriginal'] = ['orderId' => $orderId, 'revisionId' => $revision, 'sequence' => $sequence];
        $card['order'] = $this->order($card['id'], $row, $engineer, $installers, 'confirmed', (string) $row['selected_at_utc']);
        $card['controlEngineer'] = $engineer;
        array_unshift($card['events'], ['type' => 'assignment_order_signed_original_uploaded', 'occurredAt' => $this->utc($row['uploaded_at_utc']), 'actorId' => (int) $row['actor_user_id']]);
        return $card;
    }

    private function order(int $objectId, array $row, array $engineer, array $installers, string $status, string $prepared): array
    {
        $orderId = (int) $row['assignment_order_id'];
        $revision = (string) ($row['current_revision_id'] ?? $row['original_revision_id']);
        $meta = $this->one("SELECT v.byte_size,v.revision_number,v.document_date,v.uploaded_at_utc FROM `{$this->prefix}fm2_assignment_order_original_roots` r JOIN `{$this->prefix}fm2_assignment_order_original_revisions` v ON v.root_original_id=r.root_original_id AND v.revision_id=:revision WHERE r.installation_case_id=:case AND r.assignment_order_id=:order AND r.composition_identity=:identity AND r.composition_sha256=:hash LIMIT 2", [':revision' => $revision, ':case' => (int) $row['installation_case_id'], ':order' => $orderId, ':identity' => (string) $row['composition_identity'], ':hash' => (string) $row['composition_sha256']]);
        if ($meta === null) {
            throw new \RuntimeException('Original revision unavailable.');
        }
        if ($status === 'applied' && ((int) $meta['revision_number'] !== (int) $row['original_revision_number'] || (string) $meta['document_date'] !== (string) $row['document_date'])) {
            throw new \RuntimeException('Malformed applied original linkage.');
        }
        $artifact = ['type' => 'signed_original', 'filename' => 'Подписанный оригинал.pdf', 'mediaType' => 'application/pdf', 'size' => (int) $meta['byte_size'], 'revisionNumber' => (int) $meta['revision_number'], 'uploadedAt' => $this->utc($meta['uploaded_at_utc']), 'href' => "/pilot/objects/{$objectId}/assignment-orders/{$orderId}/originals/{$revision}/download"];
        return ['version' => (int) ($row['order_version'] ?? 0), 'status' => $status, 'orderDate' => (string) $row['document_date'], 'preparedAt' => $prepared, 'registrationNumber' => null, 'artifacts' => [$artifact], 'organizationType' => count($installers) === 1 ? 'individual' : 'brigade', 'engineer' => $engineer, 'installers' => $installers];
    }

    private function events(int $caseId,int$objectId,?string$cursor): array
    {
        $params=[':case'=>$caseId,':object'=>$objectId];$where='';if($cursor!==null){$decoded=base64_decode(strtr($cursor,'-_','+/'),true);$tuple=is_string($decoded)?json_decode($decoded,true):null;$time=$tuple[0]??null;if(is_int($time))$time=(string)$time;if(!is_array($tuple)||count($tuple)!==3||!is_string($time)||preg_match('/^\d+(?:\.\d{1,6})?$/D',$time)!==1||!is_int($tuple[1])||!is_int($tuple[2]))throw new \DomainException('Invalid history cursor.');[, $rank,$id]=$tuple;$where=' WHERE occurred_sort<:time OR (occurred_sort=:time2 AND (source_rank<:rank OR (source_rank=:rank2 AND source_id<:source)))';$params+=[':time'=>$time,':time2'=>$time,':rank'=>$rank,':rank2'=>$rank,':source'=>$id];}
        $p=$this->prefix;$sql="SELECT * FROM (SELECT id source_id,1 source_rank,UNIX_TIMESTAMP(REPLACE(SUBSTRING(occurred_at,1,19),'T',' ')) occurred_sort,event_type,occurred_at,actor_user_id,NULL actor_display_snapshot,NULL changes_json FROM `{$p}fm2_process_events` WHERE installation_case_id=:case UNION ALL SELECT event_id source_id,2 source_rank,UNIX_TIMESTAMP(occurred_at_utc) occurred_sort,event_type,DATE_FORMAT(occurred_at_utc,'%Y-%m-%dT%H:%i:%sZ') occurred_at,actor_user_id,actor_display_snapshot,changes_json FROM `{$p}fm2_object_detail_edit_events` WHERE installation_case_id=:case AND object_id=:object) chronology{$where} ORDER BY occurred_sort DESC,source_rank DESC,source_id DESC LIMIT 9";$rows=$this->db->createCommand($sql,$params)->queryAll();$more=count($rows)>8;if($more)array_pop($rows);$events=array_map(static fn(array$row):array=>['type'=>(string)$row['event_type'],'occurredAt'=>(string)$row['occurred_at'],'actorId'=>(int)$row['actor_user_id']]+($row['actor_display_snapshot']!==null?['actorName'=>(string)$row['actor_display_snapshot'],'changes'=>json_decode((string)$row['changes_json'],true,flags:JSON_THROW_ON_ERROR)]:[]),$rows);$last=end($rows);$next=$more&&is_array($last)?rtrim(strtr(base64_encode(json_encode([(string)$last['occurred_sort'],(int)$last['source_rank'],(int)$last['source_id']],JSON_THROW_ON_ERROR)),'+/','-_'),'='):null;return[$events,$next];
    }

    private function actorNames(array $card): array
    {
        $ids = array_values(array_unique(array_filter(array_map(static fn (array $event): int => (int) ($event['actorId'] ?? 0), $card['events']))));
        if (($card['openedByUserId'] ?? null) !== null && !in_array((int) $card['openedByUserId'], $ids, true)) {
            $ids[] = (int) $card['openedByUserId'];
        }
        $names = [];
        if ($ids !== []) {
            $params = []; $marks = [];
            foreach ($ids as $index => $id) { $key = ':actor' . $index; $marks[] = $key; $params[$key] = $id; }
            $rows = $this->db->createCommand("SELECT user_id,full_name,email FROM `{$this->prefix}fm2_pilot_users` WHERE user_id IN(" . implode(',', $marks) . ')', $params)->queryAll();
            foreach ($rows as $row) { $name = trim((string) $row['full_name']); $name = $name === '' ? trim((string) $row['email']) : $name; $names[(int) $row['user_id']] = $name === '' ? null : $name; }
        }
        foreach ($card['events'] as &$event) { if(!array_key_exists('actorName',$event))$event['actorName'] = $names[$event['actorId']] ?? null; }
        unset($event);
        if (($card['openedByUserId'] ?? null) !== null) {
            $id = (int) $card['openedByUserId'];
            $card['openedByName'] = $names[$id] ?? 'Пользователь недоступен · ID ' . $id;
        }
        return $card;
    }

    private function one(string $sql, array $params): ?array
    {
        $rows = $this->db->createCommand($sql, $params)->queryAll();
        if (count($rows) > 1) { throw new \RuntimeException('Ambiguous object card projection.'); }
        return $rows[0] ?? null;
    }

    private function equipmentFacts(int $objectId): array
    {
        $unavailable = ['readinessDate' => null, 'firstShipmentDate' => null, 'fullShipmentDate' => null, 'source' => '1c_erp', 'status' => 'unavailable', 'lastSuccessfulSyncAt' => null];
        try {
            $meta = $this->one("SELECT last_successful_observed_at,latest_failure_observed_at FROM `{$this->prefix}fm2_equipment_fact_sync_metadata` WHERE singleton_id=1", []);
            $row = $this->one("SELECT readiness_date,first_shipment_date,full_shipment_date,last_successful_observed_at FROM `{$this->prefix}fm2_equipment_fact_current` WHERE object_id=:object", [':object' => $objectId]);
            if ($meta === null) { throw new \RuntimeException('Equipment facts metadata unavailable.'); }
            $success = $meta['last_successful_observed_at'] ?? null;
            $failure = $meta['latest_failure_observed_at'] ?? null;
            $status = $row === null
                ? ($success === null && $failure !== null ? 'failed_before_success' : 'never_synced')
                : ($failure !== null && $failure > $success ? 'failed_after_success' : 'fresh');
            return [
                'readinessDate' => $row['readiness_date'] ?? null,
                'firstShipmentDate' => $row['first_shipment_date'] ?? null,
                'fullShipmentDate' => $row['full_shipment_date'] ?? null,
                'source' => '1c_erp',
                'status' => $status,
                'lastSuccessfulSyncAt' => $row === null ? null : $this->utc($row['last_successful_observed_at']),
            ];
        } catch (\Throwable) {
            return $unavailable;
        }
    }

    private function utc(mixed $value): string { return str_replace(' ', 'T', (string) $value) . 'Z'; }
}

<?php

declare(strict_types=1);
namespace FMonitor2\Otiz;

trait MariaDbNativePremiumInputsProgress
{
    private function progress(int $case, string $date, array &$issues): array
    {
        $p = $this->prefix;
        $q = $this->db->prepare(
            "SELECT t.* FROM `{$p}fm2_checklist_template_associations`a JOIN `{$p}fm2_checklist_template_snapshots`t ON t.id=a.template_snapshot_id WHERE a.subject_kind='operational_case' AND a.subject_id=? AND a.template_snapshot_version=t.snapshot_version AND a.template_content_sha256=t.content_sha256",
        );
        $q->execute([(string) $case]);
        $ts = $q->get_result()->fetch_all(MYSQLI_ASSOC);
        $t = count($ts) === 1 ? $ts[0] : null;
        $defs = [];
        $ok =
            $t &&
            preg_match('/^[a-f0-9]{64}$/D', $t["content_sha256"]) &&
            hash_equals($t["content_sha256"], hash("sha256", $t["payload_json"]));
        if ($ok) {
            try {
                $payload = json_decode($t["payload_json"], true, 512, JSON_THROW_ON_ERROR);
                if (isset($payload["definitions"])) {
                    foreach ($payload["definitions"] as $d) {
                        $defs[(int) $d["id"]] = (int) $d["share"];
                    }
                } else {
                    foreach ($payload["sections"] ?? [] as $s) {
                        foreach ($s["items"] ?? [] as $i) {
                            $defs[(int) $i["id"]] = (int) $i["weight"];
                        }
                    }
                }
                $ok = $defs !== [];
            } catch (\Throwable) {
                $ok = false;
            }
        }
        if (!$ok) {
            $this->issue($issues, "DEFINITION_VERSION_UNPROVEN");
        }
        $q = $this->db->prepare(
            "SELECT * FROM `{$p}fm2_checklist_operations` WHERE installation_case_id=? AND operation_type IN('item_completed','item_installers_changed','completion_retracted') AND LEFT(device_time,10)<=? AND LEFT(server_received_at,10)<=? ORDER BY accepted_revision,id",
        );
        $q->execute([$case, $date, $date]);
        $ops = $q->get_result()->fetch_all(MYSQLI_ASSOC);
        $ids = array_column($ops, "client_operation_id");
        $attrs = [];
        if ($ids) {
            $q = $this->db->prepare(
                "SELECT * FROM `{$p}fm2_checklist_operation_installers` WHERE client_operation_id IN(" .
                    implode(",", array_fill(0, count($ids), "?")) .
                    ") ORDER BY BINARY client_operation_id,installer_tab_id",
            );
            $q->execute($ids);
            $attrs = $q->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        $by = [];
        foreach ($attrs as $a) {
            $by[$a["client_operation_id"]][] = (string) $a["installer_tab_id"];
        }
        $active = [];
        $attrib = [];
        foreach ($ops as $o) {
            if (
                !$t ||
                (int) $o["template_snapshot_id"] !== (int) $t["id"] ||
                !hash_equals((string) $t["content_sha256"], (string) $o["template_content_sha256"])
            ) {
                $this->issue($issues, "DEFINITION_VERSION_UNPROVEN");
                continue;
            }
            $payload = json_decode($o["payload_json"], true) ?: [];
            $original = (string) ($payload["originalClientOperationId"] ?? "");
            if ($o["operation_type"] === "item_completed") {
                $item = (int) $o["item_id"];
                if (!isset($defs[$item])) {
                    $this->issue($issues, "CHECKLIST_ITEM_UNPROVEN");
                    continue;
                }
                $active[$item] = $o;
                $attrib[$o["client_operation_id"]] = $by[$o["client_operation_id"]] ?? [];
            } elseif ($o["operation_type"] === "item_installers_changed" && isset($active[(int) $o["item_id"]])) {
                $attrib[$active[(int) $o["item_id"]]["client_operation_id"]] = $by[$o["client_operation_id"]] ?? [];
            } elseif ($o["operation_type"] === "completion_retracted") {
                foreach ($active as $i => $event) {
                    if ($event["client_operation_id"] === $original) {
                        unset($active[$i]);
                    }
                }
            }
        }
        ksort($active, SORT_NUMERIC);
        $bp = 0;
        $con = [];
        foreach ($active as $event) {
            $tabs = $attrib[$event["client_operation_id"]] ?? [];
            usort($tabs, "strcmp");
            if (!$tabs) {
                $this->issue($issues, "CHECKLIST_ATTRIBUTION_ABSENT");
                continue;
            }
            $share = $defs[(int) $event["item_id"]] * 100;
            $bp += $share;
            $each = intdiv($share, count($tabs));
            $rem = $share % count($tabs);
            foreach ($tabs as $i => $tab) {
                $con[$tab] = ($con[$tab] ?? 0) + $each + ($i < $rem ? 1 : 0);
            }
        }
        uksort($con, "strcmp");
        [$facts, $cs] = $this->allCompletion($case);
        if ($this->documentary($facts, $cs, "pto_act", $date)) {
            $bp += 1000;
        }
        if ($bp >= 1000 && $this->documentary($facts, $cs, "declaration", $date)) {
            $bp += 500;
        }
        $canonical = [
            "reportDate" => $date,
            "template" => $t,
            "operations" => $ops,
            "attributions" => $attrs,
            "completionFacts" => $facts,
            "completionCorrections" => $cs,
        ];
        $proof = [
            "source" => $this->source(
                "Подтверждённый прогресс на отчётную дату",
                "fm2_checklist_operations/case/$case",
                $canonical,
            ),
            "templateId" => $t ? (int) $t["id"] : null,
            "templateHash" => $t["content_sha256"] ?? null,
            "clientOperationIds" => array_values(array_column($active, "client_operation_id")),
            "contributions" => $con,
        ];
        return [min(10000, $bp), $proof, $con];
    }
}

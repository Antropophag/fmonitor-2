<?php

declare(strict_types=1);
namespace FMonitor2\Otiz;

use FMonitor2\InstallationProcess\YiiObjectCardApplicationIntegrity;

trait MariaDbNativePremiumInputsEvidence
{
    private function composition(int $case, int $object, array &$issues): array
    {
        $p = $this->prefix;
        $q = $this->db->prepare(
            "SELECT * FROM `{$p}fm2_assignment_order_applications` WHERE installation_case_id=? ORDER BY application_sequence DESC LIMIT 1",
        );
        $q->execute([$case]);
        $r = $q->get_result()->fetch_assoc();
        if (!$r) {
            $this->issue($issues, "CREW_EVIDENCE_ABSENT");
            return [null, []];
        }
        try {
            $s = json_decode((string) $r["selected_snapshot_json"], true, 512, JSON_THROW_ON_ERROR);
            if (
                (int) $r["object_id"] !== $object ||
                !is_array($s) ||
                array_keys($s) !== ["selectedInstallers", "selectedEngineer"]
            ) {
                throw new \RuntimeException();
            }
            YiiObjectCardApplicationIntegrity::validateComposition($r, $s);
        } catch (\Throwable) {
            $this->issue($issues, "COMPOSITION_EVIDENCE_INVALID");
            return [null, []];
        }
        return [
            [
                "applicationId" => (int) $r["application_id"],
                "sequence" => (int) $r["application_sequence"],
                "source" => $this->source(
                    "Применённый состав распоряжения",
                    "fm2_assignment_order_applications/" . $r["application_id"],
                    $r,
                ),
            ],
            $s["selectedInstallers"],
        ];
    }
    private function completion(int $case, array &$issues): array
    {
        $p = $this->prefix;
        $q = $this->db->prepare(
            "SELECT * FROM `{$p}fm2_pilot_completion_facts` WHERE installation_case_id=? AND fact_type='pto_act' ORDER BY id",
        );
        $q->execute([$case]);
        $roots = array_map(fn($r) => $this->dbScalars($r), $q->get_result()->fetch_all(MYSQLI_ASSOC));
        $root = count($roots) === 1 ? $roots[0] : null;
        $cs = [];
        $ok = count($roots) <= 1;
        if ($root) {
            $q = $this->db->prepare(
                "SELECT * FROM `{$p}fm2_pilot_completion_fact_corrections` WHERE root_fact_id=? ORDER BY version_no,id",
            );
            $q->execute([$root["id"]]);
            $cs = array_map(fn($r) => $this->dbScalars($r), $q->get_result()->fetch_all(MYSQLI_ASSOC));
            $ok = $this->date($root["fact_date"]) && $this->instant($root["recorded_at"]);
            $prev = null;
            foreach ($cs as $i => $r) {
                $ok =
                    $ok &&
                    $this->date($r["fact_date"]) &&
                    $this->instant($r["recorded_at"]) &&
                    (int) $r["version_no"] === $i + 1 &&
                    ($r["previous_correction_id"] === null ? null : (int) $r["previous_correction_id"]) ===
                        ($prev === null ? null : (int) $prev["id"]) &&
                    ($r["previous_version_no"] === null ? null : (int) $r["previous_version_no"]) ===
                        ($prev === null ? null : (int) $prev["version_no"]);
                $prev = $r;
            }
        }
        if (!$ok) {
            $this->issue($issues, "COMPLETION_EVIDENCE_INVALID");
        }
        $leaf = $cs ? $cs[array_key_last($cs)] : $root;
        $e = [
            "root" => $root,
            "corrections" => $cs,
            "source" => $this->source("История акта ПТО", "fm2_pilot_completion_facts/case/$case/pto_act", [
                "root" => $root,
                "corrections" => $cs,
            ]),
        ];
        return [
            "evidence" => $e,
            "date" => $ok && $leaf ? $leaf["fact_date"] : null,
            "recorded" => $ok && $leaf ? $leaf["recorded_at"] : null,
        ];
    }
    private function certificate(int $case, array &$issues): ?array
    {
        try {
            return new \FMonitor2\DeadlineTransferCertificate\MariaDbDeadlineTransferCertificates(
                $this->db,
                $this->prefix,
                $this->clock,
            )->currentEvidence($case);
        } catch (\DomainException $e) {
            if ($e->getMessage() !== "CERTIFICATE_EVIDENCE_INVALID") {
                throw $e;
            }
            $this->issue($issues, "CERTIFICATE_EVIDENCE_INVALID");
            return null;
        }
    }
}

<?php

declare(strict_types=1);
namespace FMonitor2\Otiz;

trait MariaDbSnapshotBuilderPersistence
{
    private function previousProgress(int $objectId, string $date): int
    {
        $q = $this->db->prepare(
            "SELECT so.current_progress_bp FROM `{$this->prefix}fm2_pilot_otiz_snapshot_objects` so JOIN `{$this->prefix}fm2_pilot_otiz_snapshots` s ON s.id=so.snapshot_id AND s.status='accepted' WHERE so.object_id=? AND s.report_date<? ORDER BY s.report_date DESC,s.id DESC LIMIT 1",
        );
        $q->execute([$objectId, $date]);
        return (int) ($q->get_result()->fetch_column() ?: 0);
    }

    private function closureEvidence(int $objectId, string $date): array
    {
        $q = $this->db->prepare("SELECT * FROM `{$this->prefix}fm2_pilot_otiz_payment_closures` WHERE object_id=? AND closed_on<=? ORDER BY id");
        $q->execute([$objectId, $date]);
        $out = [];
        foreach ($q->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $row = array_map(static fn($value) => $value === null ? null : (string) $value, $row);
            $out[] = [
                "amountCents" => (int) $row["paid_cents"],
                "closedOn" => $row["closed_on"],
                "source" => [
                    "label" => "Принятое закрытие ОТиЗ",
                    "locator" => "fm2_pilot_otiz_payment_closures/" . $row["id"],
                    "contentSha256" => hash("sha256", json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
                ],
            ];
        }
        return $out;
    }

    private function disciplineAtCut(int $id, string $date): int
    {
        $q = $this->db->prepare("SELECT COALESCE(SUM(discipline_cents),0) FROM `{$this->prefix}fm2_pilot_otiz_payment_closures` WHERE object_id=? AND closed_on<=?");
        $q->execute([$id, $date]);
        return (int) $q->get_result()->fetch_column();
    }

    private function allocations(int $snapshot, array $object, int $distributed): array
    {
        $members = $object["team"];
        $participants = array_map(static fn($m) => ["tab" => (string) $m["tab"], "weight" => (int) $m["weight"]], $members);
        $amounts = $members === [] ? [] : PremiumCalculationV2::allocate($distributed, $participants);
        $by = [];
        foreach ($amounts as $a) {
            $by[$a["tab"]] = $a["amountCents"];
        }
        $weights = array_sum(array_column($members, "weight"));
        $stored = [];
        foreach ($members as $m) {
            $tab = (string) $m["tab"];
            $amount = (int) $by[$tab];
            $share = $weights ? intdiv(10000 * (int) $m["weight"], $weights) : 0;
            $q = $this->db->prepare(
                "INSERT INTO `{$this->prefix}fm2_pilot_otiz_snapshot_allocations`(snapshot_id,object_id,tab_id,full_name,position_name,contribution_bp,base_ktu_bp,adjustment_ktu_bp,effective_ktu_bp,share_bp,amount_cents,employment_status,participation_basis) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)",
            );
            $q->execute([
                $snapshot,
                (int) $object["id"],
                $tab,
                $m["name"],
                $m["position"],
                (int) ($m["contribution"] ?? $m["weight"]),
                10000,
                0,
                10000,
                $share,
                $amount,
                $m["employment"] ?? "employed",
                $m["basis"] ?? "Распоряжение № " . $object["id"] . "-Р",
            ]);
            $stored[] = ["tab" => $tab, "amountCents" => $amount];
        }
        return $stored;
    }

    private function insertObject(
        int $s,
        array $o,
        int $previous,
        string $date,
        int $kss,
        int $accrued,
        int $fund,
        int $closed,
        int $remaining,
        int $pool,
        int $distributed,
        int $undistributed,
        string $state,
        string $json,
    ): void {
        $q = $this->db->prepare(
            "INSERT INTO `{$this->prefix}fm2_pilot_otiz_snapshot_objects`(snapshot_id,object_id,regnumber,address,previous_progress_bp,current_progress_bp,progress_fact_date,premium_cents,shaft_bp,kss_bp,accrued_cents,fund_cents,closed_before_cents,remaining_cents,pool_cents,distributed_cents,undistributed_cents,calculation_state,inputs_json) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
        );
        $q->execute([
            $s,
            (int) $o["id"],
            $o["reg"],
            $o["address"],
            $previous,
            (int) $o["progress"],
            $date,
            (int) $o["premium"],
            (int) $o["shaft"],
            $kss,
            $accrued,
            $fund,
            $closed,
            $remaining,
            $pool,
            $distributed,
            $undistributed,
            $state,
            $json,
        ]);
    }
    private function issue(int $s, int $o, string $severity, string $code, string $message, string $owner): void
    {
        $q = $this->db->prepare(
            "INSERT INTO `{$this->prefix}fm2_pilot_otiz_snapshot_issues`(snapshot_id,object_id,severity,issue_code,message,owner_role,state) VALUES(?,?,?,?,?,?,'open')",
        );
        $q->execute([$s, $o, $severity, $code, $message, $owner]);
    }
}

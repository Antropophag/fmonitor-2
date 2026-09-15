<?php

declare(strict_types=1);
namespace FMonitor2\Otiz;

/** Persists an Excel-v2 calculation inside the publication owner's transaction. */
final class MariaDbSnapshotBuilder
{
    use MariaDbSnapshotBuilderPersistence;
    public function __construct(private \mysqli $db, private string $prefix, private \Closure $inputs) {}

    public function populate(int $snapshotId, string $date): void
    {
        $objects = ($this->inputs)($date);
        $totalPool = $totalPaid = $totalDistributed = 0;
        $identity = [];
        foreach ($objects as $object) {
            $objectId = (int) $object["id"];
            $progress = (int) $object["progress"];
            $previousProgress = $this->previousProgress($objectId, $date);
            $closures = $this->closureEvidence($objectId, $date);
            $blocked = $object["issues"] !== [];
            $calculation = $blocked
                ? null
                : PremiumCalculationV2::calculate($object["operands"], [
                    "closures" => $closures,
                    "actualPayouts" => [],
                ]);
            $paidBefore = array_sum(array_column($closures, "amountCents"));
            $amounts = $calculation["amounts"] ?? ["fundCents" => 0, "remainingFundCents" => 0, "poolCents" => 0];
            $daysLate = (int) ($calculation["daysLate"] ?? 0);
            $kss = (int) ($calculation["kssBp"] ?? 0);
            $fund = (int) $amounts["fundCents"];
            $pool = (int) $amounts["poolCents"];
            $remaining = (int) $amounts["remainingFundCents"];
            $discipline = $this->disciplineAtCut($objectId, $date);
            $distributed = max(0, $pool - $discipline);
            $undistributed = $pool - $distributed;
            $accrued = $paidBefore + $pool;
            $state = $blocked ? "blocked" : ($pool === 0 ? "no_new_amount" : ($object["pto"] !== null && $progress === 10000 && $remaining === 0 ? "completed" : "ready"));
            $inputs = [
                "address" => $object["address"],
                "deadline" => $object["deadline"],
                "pto" => $object["pto"],
                "daysLate" => $daysLate,
                "calculationOperandsSource" => "native_operational_facts",
                "calculationOperandsLabel" => "Подтверждённые native facts FMonitor 2",
                "sourceEvidence" => $object["sourceEvidence"],
                "premiumCalculation" => $calculation,
                "blockers" => $object["issues"],
            ];
            $json = json_encode($inputs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $this->insertObject($snapshotId, $object, $previousProgress, $date, $kss, $accrued, $fund, $paidBefore, $remaining, $pool, $distributed, $undistributed, $state, $json);
            foreach ($object["issues"] as $issue) {
                $this->issue($snapshotId, $objectId, "blocker", $issue["code"], $issue["message"], $issue["owner"]);
            }
            if ($daysLate > 0) {
                $this->issue(
                    $snapshotId,
                    $objectId,
                    "warning",
                    "DEADLINE_PENALTY",
                    "Просрочка {$daysLate} календ. дн.; Ксс уменьшен до " . number_format($kss / 10000, 2, ",", " "),
                    "ОТиЗ",
                );
            }
            $allocations = $this->allocations($snapshotId, $object, $distributed);
            $totalPool += $pool;
            $totalPaid += $paidBefore;
            $totalDistributed += $distributed;
            $identity[] = [
                "objectId" => $objectId,
                "inputs" => $inputs,
                "amounts" => [
                    "fund" => $fund,
                    "paidBefore" => $paidBefore,
                    "pool" => $pool,
                    "distributed" => $distributed,
                    "undistributed" => $undistributed,
                ],
                "allocations" => $allocations,
                "state" => $state,
            ];
        }
        $hash = hash(
            "sha256",
            json_encode(
                ["reportDate" => $date, "rulesVersion" => PremiumCalculationV2::VERSION, "objects" => $identity],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ),
        );
        $q = $this->db->prepare("UPDATE `{$this->prefix}fm2_pilot_otiz_snapshots` SET total_pool_cents=?,total_closed_cents=?,total_available_cents=?,content_hash=? WHERE id=?");
        $q->execute([$totalPool, $totalPaid, $totalDistributed, $hash, $snapshotId]);
    }

}

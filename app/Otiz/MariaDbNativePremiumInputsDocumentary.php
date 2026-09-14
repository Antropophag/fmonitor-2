<?php

declare(strict_types=1);
namespace FMonitor2\Otiz;

trait MariaDbNativePremiumInputsDocumentary
{
    private function allCompletion(int $case): array
    {
        $p = $this->prefix;
        $q = $this->db->prepare(
            "SELECT * FROM `{$p}fm2_pilot_completion_facts` WHERE installation_case_id=? ORDER BY id",
        );
        $q->execute([$case]);
        $f = $q->get_result()->fetch_all(MYSQLI_ASSOC);
        $q = $this->db->prepare(
            "SELECT c.* FROM `{$p}fm2_pilot_completion_fact_corrections`c JOIN `{$p}fm2_pilot_completion_facts`f ON f.id=c.root_fact_id WHERE f.installation_case_id=? ORDER BY c.root_fact_id,c.version_no,c.id",
        );
        $q->execute([$case]);
        return [$f, $q->get_result()->fetch_all(MYSQLI_ASSOC)];
    }
    private function documentary(array $f, array $cs, string $type, string $cut): bool
    {
        foreach ($f as $r) {
            if ($r["fact_type"] === $type) {
                if (
                    !$this->date($r["fact_date"]) ||
                    !$this->instant($r["recorded_at"]) ||
                    substr($r["recorded_at"], 0, 10) > $cut
                ) {
                    return false;
                }
                $leaf = $r;
                foreach ($cs as $c) {
                    if ($c["root_fact_id"] === $r["id"] && substr($c["recorded_at"], 0, 10) <= $cut) {
                        $leaf = $c;
                    }
                }
                return $this->date($leaf["fact_date"]) && $leaf["fact_date"] <= $cut;
            }
        }
        return false;
    }
}

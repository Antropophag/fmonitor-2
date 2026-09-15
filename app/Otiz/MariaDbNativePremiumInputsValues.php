<?php

declare(strict_types=1);
namespace FMonitor2\Otiz;

use DateTimeImmutable;

trait MariaDbNativePremiumInputsValues
{
    private function team(array $s, array $c, array &$issues): array
    {
        $out = [];
        foreach ($s as $i) {
            $tab = (string) ($i["tabId"] ?? "");
            $n = (int) ($c[$tab] ?? 0);
            if (!$n) {
                $this->issue($issues, "INSTALLER_ATTRIBUTION_ABSENT");
                continue;
            }
            $out[] = [
                "tab" => $tab,
                "name" => (string) $i["fullName"],
                "position" => (string) $i["position"],
                "contribution" => $n,
                "weight" => $n,
                "basis" => "Фактический вклад checklist × базовый управленческий коэффициент 1,00",
            ];
        }
        return $out;
    }
    private function finance(int $id, array &$issues): ?array
    {
        $p = $this->prefix;
        $q = $this->db->prepare("SELECT * FROM `{$p}fm2_pilot_object_details` WHERE object_id=? LIMIT 1");
        $q->execute([$id]);
        $r = $q->get_result()->fetch_assoc();
        if (!$r) {
            $this->issue($issues, "OBJECT_CARD_EVIDENCE_ABSENT");
            return null;
        }
        try {
            $x = json_decode($r["payload_json"], true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            $x = null;
        }
        if (!is_array($x) || !hash_equals((string) $r["content_sha256"], hash("sha256", $r["payload_json"]))) {
            $this->issue($issues, "OBJECT_CARD_PROVENANCE_INVALID");
            return null;
        }
        $f = $x["fields"] ?? [];
        $floor = filter_var($f["floors"]["raw"] ?? null, FILTER_VALIDATE_INT, [
            "options" => ["min_range" => 1],
        ]);
        $cap = filter_var($f["weight"]["raw"] ?? null, FILTER_VALIDATE_INT, [
            "options" => ["min_range" => 1],
        ]);
        $text = mb_strtolower((string) ($f["lift_type"]["display"] ?? ""), "UTF-8");
        $type = str_contains($text, "груз") ? "cargo" : (str_contains($text, "пассаж") ? "passenger" : null);
        $n = new NativePremiumNorms();
        $premium = $floor === false || $cap === false ? null : $n->premiumCents($type, (int) $floor, (int) $cap);
        $shaft = $n->shaftBasisPoints((string) ($f["pitmaterial"]["display"] ?? ""));
        if ($premium === null) {
            $this->issue($issues, "PREMIUM_NORM_UNRESOLVED");
        }
        if ($shaft === null) {
            $this->issue($issues, "SHAFT_COEFFICIENT_UNRESOLVED");
        }
        return [
            "premium" => $premium,
            "shaft" => $shaft,
            "date" => substr($r["captured_at"], 0, 10),
            "label" => "Характеристики карточки объекта + приложение 4 к приказу №178",
            "locator" => "fm2_pilot_object_details/" . $id,
            "sha" => $r["content_sha256"],
        ];
    }
    private function issue(array &$a, string $c): void
    {
        foreach ($a as $i) {
            if ($i["code"] === $c) {
                return;
            }
        }
        $a[] = ["code" => $c, "message" => $c, "owner" => "Администратор"];
    }
    private function source(string $l, string $loc, array $d): array
    {
        if (
            str_starts_with($loc, "fm2_assignment_order_applications/") ||
            str_starts_with($loc, "fm2_checklist_operations/") ||
            str_starts_with($loc, "fm2_pilot_completion_facts/")
        ) {
            $d = $this->dbScalars($d);
        }
        return [
            "label" => $l,
            "locator" => $loc,
            "contentSha256" => hash(
                "sha256",
                json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            ),
        ];
    }
    private function dbScalars(array $v): array
    {
        foreach ($v as $k => $x) {
            if (is_array($x)) {
                $v[$k] = $this->dbScalars($x);
            } elseif ($x !== null) {
                $v[$k] = (string) $x;
            }
        }
        return $v;
    }
    private function date(string $v): bool
    {
        $d = DateTimeImmutable::createFromFormat("!Y-m-d", $v);
        return $d !== false && $d->format("Y-m-d") === $v;
    }
    private function instant(string $v): bool
    {
        try {
            new DateTimeImmutable($v);
            return preg_match("/^\d{4}-\d{2}-\d{2}[T ]/D", $v) === 1;
        } catch (\Throwable) {
            return false;
        }
    }
}

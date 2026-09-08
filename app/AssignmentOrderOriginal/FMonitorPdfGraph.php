<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Latest object graph and page-tree validation, after history scanning. */
final class FMonitorPdfGraph
{
    public static function valid(array $objects, string $root): bool
    {
        $queue = [[$root, 0]]; $queued = [$root=>true]; $head = 0;
        while (isset($queue[$head])) {
            [$key, $depth] = $queue[$head++];
            if ($depth > 100 || !isset($objects[$key])) return false;
            if (preg_match_all('/(?<![^ ])([0-9]+) +([0-9]+) +R(?= |$)/', $objects[$key], $refs, PREG_SET_ORDER)) {
                foreach ($refs as $ref) {
                    $target = FMonitorPdfDictionary::reference($ref[1].' '.$ref[2].' R');
                    if ($target === null) return false;
                    if (!isset($queued[$target])) { $queued[$target] = true; $queue[] = [$target, $depth + 1]; }
                }
            }
        }
        $catalog = FMonitorPdfDictionary::entries($objects[$root]);
        $pages = FMonitorPdfDictionary::reference($catalog['Pages'] ?? null);
        if (FMonitorPdfDictionary::name($catalog['Type'] ?? null) !== 'Catalog' || $pages === null) return false;
        $seen = []; $count = self::pages($pages, null, $objects, $seen, 0);
        return $count !== null && $count > 0;
    }

    private static function pages(string $key, ?string $parent, array $objects, array &$seen, int $depth): ?int
    {
        if ($depth > 100 || isset($seen[$key]) || !isset($objects[$key])) return null;
        $seen[$key] = true; $dict = FMonitorPdfDictionary::entries($objects[$key]);
        $type = FMonitorPdfDictionary::name($dict['Type'] ?? null);
        if ($parent !== null && FMonitorPdfDictionary::reference($dict['Parent'] ?? null) !== $parent) return null;
        if ($type === 'Page') return $parent !== null ? 1 : null;
        if ($type !== 'Pages') return null;
        $count = FMonitorPdfDictionary::uint($dict, 'Count', 100000);
        $kids = isset($dict['Kids']) ? FMonitorPdfDictionary::arrayValues($dict['Kids']) : null;
        if ($count === null || $kids === null) return null;
        $total = 0;
        foreach ($kids as $kid) {
            $reference = FMonitorPdfDictionary::reference($kid);
            if ($reference === null) return null;
            $pages = self::pages($reference, $key, $objects, $seen, $depth + 1);
            if ($pages === null) return null;
            $total += $pages; if ($total > 100000) return null;
        }
        return $total === $count ? $total : null;
    }
}

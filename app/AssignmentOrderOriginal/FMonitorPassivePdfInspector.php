<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
require_once __DIR__.'/AssignmentOrderOriginalRuntime.php';
require_once __DIR__.'/FMonitorPdfLexical.php';
require_once __DIR__.'/FMonitorPdfDictionary.php';
require_once __DIR__.'/FMonitorPdfValue.php';
require_once __DIR__.'/FMonitorPdfPhysicalObjects.php';
require_once __DIR__.'/FMonitorPdfStreams.php';
require_once __DIR__.'/FMonitorPdfXref.php';
require_once __DIR__.'/FMonitorPdfGraph.php';

final class FMonitorPassivePdfInspector implements AssignmentOrderOriginalPdfInspector
{
    public const ALGORITHM_ID = 'fmonitor-passive-pdf-v1';

    public function __construct()
    { if ((int)ini_get('memory_limit') > 0 && (int)ini_get('memory_limit') < 256) ini_set('memory_limit', '256M'); }

    public function algorithmId(): string { return self::ALGORITHM_ID; }

    public function inspect(string $bytes): AssignmentOrderOriginalPdfInspection
    {
        try { return $this->parse($bytes); }
        catch (\Throwable) { return AssignmentOrderOriginalPdfInspection::invalid(); }
    }

    private function parse(string $bytes): AssignmentOrderOriginalPdfInspection
    {
        $length = strlen($bytes);
        if ($length < 20 || $length > 20971520 || !preg_match('/^%PDF-1\.[4-7](?:\r?\n|\r)/D', $bytes)
            || !preg_match('/startxref\s+([0-9]+)\s+%%EOF\s*$/D', $bytes, $last)) return AssignmentOrderOriginalPdfInspection::invalid();
        $offset = FMonitorPdfDictionary::number($last[1], $length - 1);
        if ($offset === null) return AssignmentOrderOriginalPdfInspection::invalid();
        $physical = FMonitorPdfPhysicalObjects::read($bytes);
        if (!$physical) return AssignmentOrderOriginalPdfInspection::invalid();
        $streams = new FMonitorPdfStreams($bytes, $physical); $xref = new FMonitorPdfXref($bytes, $physical, $streams);
        $history = []; $seen = []; $selected = []; $root = null; $unsafe = false;
        do {
            if (isset($seen[$offset]) || count($history) === 64) return AssignmentOrderOriginalPdfInspection::invalid();
            $seen[$offset] = true; $history[] = $offset; $section = $xref->section($offset);
            $root ??= $section['root']; $unsafe = $unsafe || $section['unsafe'];
            foreach ($section['entries'] as $number => [$type, $position, $generation]) {
                if ($type !== 1) continue;
                if (($physical[$position]['key'] ?? null) !== $number.' '.$generation) return AssignmentOrderOriginalPdfInspection::invalid();
                $selected[$position] = true;
            }
            $offset = $section['prev'];
        } while ($offset !== null);
        unset($section);
        if ($root === null || count($selected) !== count($physical)) return AssignmentOrderOriginalPdfInspection::invalid();
        foreach ($selected as $position => $_) $streams->validate($position);
        $entries = [];
        foreach (array_reverse($history) as $position) {
            $section = $xref->section($position);
            foreach ($section['entries'] as $number => $entry) $entries[$number] = $entry;
            foreach ($entries as $number => [$type, $container, $index]) {
                if ($type !== 2) continue;
                $source = $entries[$container] ?? null;
                if ($source === null || $source[0] !== 1) return AssignmentOrderOriginalPdfInspection::invalid();
                $members = $streams->members($source[1]);
                if (($members[$index]['number'] ?? null) !== $number) return AssignmentOrderOriginalPdfInspection::invalid();
            }
        }
        unset($section); $objects = [];
        foreach ($entries as $number => [$type, $position, $generation]) {
            if ($type === 1) $objects[$number.' '.$generation] = $physical[$position]['body'];
            elseif ($type === 2) $objects[$number.' 0'] = $streams->members($entries[$position][1])[$generation]['body'];
        }
        if (!FMonitorPdfGraph::valid($objects, $root)) return AssignmentOrderOriginalPdfInspection::invalid();
        return $unsafe || $streams->unsafe ? AssignmentOrderOriginalPdfInspection::unsafe() : AssignmentOrderOriginalPdfInspection::passive();
    }
}

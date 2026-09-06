<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Physical offsets remain the identity across incremental revisions. */
final class FMonitorPdfPhysicalObjects
{
    public static function read(string $bytes): array
    {
        $cursor = 0; $previous = []; $objects = [];
        while (($token = FMonitorPdfLexical::next($bytes, $cursor)) !== null) {
            if ($token['kind'] !== 'word' || $token['value'] !== 'obj') {
                $previous[] = $token; if (count($previous) > 2) array_shift($previous); continue;
            }
            if (count($previous) !== 2) throw new \UnexpectedValueException('Missing PDF object identity.');
            [$numberToken, $generationToken] = $previous;
            $number = FMonitorPdfDictionary::number($numberToken['value'], 100000);
            $generation = FMonitorPdfDictionary::number($generationToken['value'], 65535);
            if ($numberToken['kind'] !== 'word' || $generationToken['kind'] !== 'word' || $number === null || $number < 1 || $generation === null) throw new \UnexpectedValueException('Invalid PDF object identity.');
            $offset = $numberToken['start']; $view = FMonitorPdfLexical::view($bytes, $cursor, true);
            $cursor = $view['next']; $payload = null;
            if ($view['stream']) {
                $dict = FMonitorPdfDictionary::entries($view['body']);
                $length = FMonitorPdfDictionary::uint($dict, 'Length', strlen($bytes));
                if ($length === null || !preg_match('/\G(?:\r\n|\r|\n)/A', $bytes, $line, 0, $cursor)) throw new \UnexpectedValueException('Invalid PDF stream header.');
                $cursor += strlen($line[0]); $payload = [$cursor, $length]; $cursor += $length;
                $structural = in_array(FMonitorPdfDictionary::name($dict['Type'] ?? null), ['XRef','ObjStm'], true);
                if ($structural && !in_array($bytes[$cursor] ?? '', ["\r","\n"], true)) throw new \UnexpectedValueException('Missing structural stream separator.');
                if ($cursor > strlen($bytes) || !preg_match('/\G(?:\r\n|\n|\r)?endstream[\x00\x09\x0A\x0C\x0D\x20]*endobj(?=[\r\n]|$)/A', $bytes, $end, 0, $cursor)) throw new \UnexpectedValueException('Invalid PDF stream framing.');
                $cursor += strlen($end[0]);
            } else {
                $end = FMonitorPdfDictionary::valueEnd($view['body'], 0);
                if (FMonitorPdfLexical::next($view['body'], $end) !== null) throw new \UnexpectedValueException('Multiple PDF object values.');
            }
            $objects[$offset] = ['key'=>$number.' '.$generation, 'body'=>$view['body'], 'unsafe'=>$view['unsafe'], 'payload'=>$payload];
            if (count($objects) > 100000) throw new \UnexpectedValueException('Too many PDF objects.');
            $previous = [];
        }
        return $objects;
    }
}

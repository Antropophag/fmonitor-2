<?php

declare(strict_types=1);

namespace FMonitor2\Tests\Support;

final class AssignmentOrderOriginalPdfOracle
{
    public static function classic(string $catalogExtra = '', int $pageCount = 1, string $trailerExtra = ''): string
    {
        $kids = $pageCount === 1 ? '[3 0 R]' : '[]';
        $objects = [
            "<< /Type /Catalog /Pages 2 0 R $catalogExtra >>",
            "<< /Type /Pages /Kids $kids /Count $pageCount >>",
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 72 72] >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $number => $body) {
            $offsets[] = strlen($pdf);
            $pdf .= ($number + 1) . " 0 obj\n$body\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 4\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) $pdf .= sprintf("%010d 00000 n \n", $offset);
        return $pdf . "trailer\n<< /Size 4 /Root 1 0 R $trailerExtra >>\nstartxref\n$xref\n%%EOF\n";
    }

    public static function xrefStream(): string
    {
        $pdf = "%PDF-1.5\n";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 72 72] >>',
        ];
        $offsets = [0];
        foreach ($objects as $i => $body) { $offsets[] = strlen($pdf); $pdf .= ($i + 1) . " 0 obj\n$body\nendobj\n"; }
        $xrefOffset = strlen($pdf);
        $entries = pack('CNN', 0, 0, 65535);
        foreach (array_slice($offsets, 1) as $offset) $entries .= pack('CNN', 1, $offset, 0);
        $entries .= pack('CNN', 1, $xrefOffset, 0);
        $pdf .= "4 0 obj\n<< /Type /XRef /Size 5 /Root 1 0 R /W [1 4 4] /Length " . strlen($entries) . " >>\nstream\n" . $entries . "\nendstream\nendobj\n";
        return $pdf . "startxref\n$xrefOffset\n%%EOF\n";
    }

    public static function objectStream(): string
    {
        $pdf = "%PDF-1.5\n";
        $plain = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 72 72] >>',
        ];
        $offsets = [0];
        foreach ($plain as $n => $body) { $offsets[$n] = strlen($pdf); $pdf .= "$n 0 obj\n$body\nendobj\n"; }
        $payload = '5 0 << /Oracle /Harmless >>';
        $offsets[4] = strlen($pdf);
        $pdf .= "4 0 obj\n<< /Type /ObjStm /N 1 /First 4 /Length " . strlen($payload) . " >>\nstream\n$payload\nendstream\nendobj\n";
        $xrefOffset = strlen($pdf);
        $entries = pack('CNN', 0, 0, 65535);
        for ($n = 1; $n <= 4; $n++) $entries .= pack('CNN', 1, $offsets[$n], 0);
        $entries .= pack('CNN', 2, 4, 0);
        $entries .= pack('CNN', 1, $xrefOffset, 0);
        $pdf .= "6 0 obj\n<< /Type /XRef /Size 7 /Root 1 0 R /W [1 4 4] /Length " . strlen($entries) . " >>\nstream\n$entries\nendstream\nendobj\n";
        return $pdf . "startxref\n$xrefOffset\n%%EOF\n";
    }
}

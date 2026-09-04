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

    public static function flateObjectStream(string $hiddenDictionary): string
    {
        $base = "%PDF-1.5\n";
        $plain = [1 => '<< /Type /Catalog /Pages 2 0 R /Oracle 5 0 R >>', 2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>', 3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 72 72] >>'];
        $offsets = [0];
        foreach ($plain as $number => $body) { $offsets[$number] = strlen($base); $base .= "$number 0 obj\n$body\nendobj\n"; }
        $payload = '5 0 ' . $hiddenDictionary;
        $compressed = gzcompress($payload);
        if (!is_string($compressed)) throw new \RuntimeException('fixture');
        $offsets[4] = strlen($base);
        $base .= "4 0 obj\n<< /Type /ObjStm /N 1 /First 4 /Filter /FlateDecode /Length " . strlen($compressed) . " >>\nstream\n" . $compressed . "\nendstream\nendobj\n";
        $xref = strlen($base);
        $entries = pack('CNN', 0, 0, 65535);
        for ($number = 1; $number <= 4; $number++) $entries .= pack('CNN', 1, $offsets[$number], 0);
        $entries .= pack('CNN', 2, 4, 0);
        $entries .= pack('CNN', 1, $xref, 0);
        return $base . "6 0 obj\n<< /Type /XRef /Size 7 /Root 1 0 R /W [1 4 4] /Length " . strlen($entries) . " >>\nstream\n$entries\nendstream\nendobj\nstartxref\n$xref\n%%EOF\n";
    }

    public static function wrongObjectOffset(): string
    {
        $pdf = self::classic();
        return preg_replace('/0000000009 00000 n /', '0000000010 00000 n ', $pdf, 1) ?? throw new \RuntimeException('fixture');
    }

    public static function nonZeroCatalogGeneration(): string
    {
        $pdf = preg_replace('/1 0 obj/', '1 2 obj', self::classic(), 1) ?? throw new \RuntimeException('fixture');
        $pdf = preg_replace('/(0000000009) 00000 n /', '$1 00002 n ', $pdf, 1) ?? throw new \RuntimeException('fixture');
        return str_replace('/Root 1 0 R', '/Root 1 2 R', $pdf);
    }

    public static function catalogGenerationMismatch(): string
    {
        return str_replace('/Root 1 2 R', '/Root 1 1 R', self::nonZeroCatalogGeneration());
    }

    public static function malformedXrefStream(): string
    {
        return str_replace('/W [1 4 4]', '/W [1 4]', self::xrefStream());
    }

    public static function unsupportedStructuralFilter(): string
    {
        return str_replace('/Type /XRef ', '/Type /XRef /Filter /LZWDecode ', self::xrefStream());
    }

    public static function conflictingXrefIdentity(): string
    {
        return str_replace("trailer\n", "1 1\n0000000058 00000 n \ntrailer\n", self::classic());
    }

    public static function objectCountAboveLimit(): string
    {
        return str_replace('/Size 4 ', '/Size 100002 ', self::classic());
    }

    public static function aggregateStructuralInflationAboveLimit(): string
    {
        return self::flateObjectStream('<< /Padding (' . str_repeat('A', 67_108_865) . ') >>');
    }

    public static function cyclicPagesTree(): string
    {
        return str_replace('/Kids [3 0 R]', '/Kids [2 0 R]', self::classic());
    }

    public static function latestRootHasZeroPages(): string
    {
        $base = self::classic();
        $previous = (int) preg_replace('/.*startxref\n([0-9]+)\n%%EOF\n\z/s', '$1', $base);
        $catalogOffset = strlen($base);
        $base .= "4 0 obj\n<< /Type /Catalog /Pages 5 0 R >>\nendobj\n";
        $pagesOffset = strlen($base);
        $base .= "5 0 obj\n<< /Type /Pages /Kids [] /Count 0 >>\nendobj\n";
        $xref = strlen($base);
        return $base . "xref\n4 2\n" . sprintf('%010d 00000 n ', $catalogOffset) . "\n" . sprintf('%010d 00000 n ', $pagesOffset) . "\ntrailer\n<< /Size 6 /Root 4 0 R /Prev $previous >>\nstartxref\n$xref\n%%EOF\n";
    }

    public static function prevCycle(): string
    {
        $pdf = self::latestRootHasZeroPages();
        preg_match('/startxref\n([0-9]+)\n%%EOF\n\z/', $pdf, $match);
        return preg_replace('/\/Prev [0-9]+/', '/Prev ' . $match[1], $pdf, 1) ?? throw new \RuntimeException('fixture');
    }

    public static function overDepthGraph(): string
    {
        $extra = '/Oracle 4 0 R';
        $pdf = "%PDF-1.4\n";
        $bodies = [1 => "<< /Type /Catalog /Pages 2 0 R $extra >>", 2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>', 3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 72 72] >>'];
        for ($number = 4; $number <= 105; $number++) $bodies[$number] = $number === 105 ? '<< /End true >>' : '<< /Next ' . ($number + 1) . ' 0 R >>';
        $offsets = [0];
        foreach ($bodies as $number => $body) { $offsets[$number] = strlen($pdf); $pdf .= "$number 0 obj\n$body\nendobj\n"; }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 106\n0000000000 65535 f \n";
        for ($number = 1; $number <= 105; $number++) $pdf .= sprintf('%010d 00000 n ', $offsets[$number]) . "\n";
        return $pdf . "trailer\n<< /Size 106 /Root 1 0 R >>\nstartxref\n$xref\n%%EOF\n";
    }
}

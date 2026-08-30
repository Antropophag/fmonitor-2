<?php

declare(strict_types=1);

final class OtizPaidBeforeBalanceProfiler
{
    public const FORMAT_VERSION = 'otiz-paid-before-balance-v1';
    private const SHEET = 'Адресный перечень';
    private const HEADERS = [
        'B3' => 'Рег. Номер',
        'DO1' => 'отчетная дата',
        'EE3' => 'Премия за монтаж, выплаченная ранее на данный рег.номер',
    ];

    /** @param array<string,list<int>> $objectMapping */
    public function profile(string $path, array $objectMapping = [], ?string $previousPath = null): array
    {
        $current = $this->read($path);
        $previous = $previousPath === null ? null : $this->read($previousPath);
        $reasons = $current['artifactIssues'];
        $balances = [];
        $registrationCounts = [];
        foreach ($current['rows'] as $row) if ($row['registration'] !== null) $registrationCounts[$row['registration']] = ($registrationCounts[$row['registration']] ?? 0) + 1;

        foreach ($current['rows'] as $row) {
            $rowReasons = $row['issues'];
            $registration = $row['registration'];
            if ($registration !== null) {
                if ($registrationCounts[$registration] > 1) $rowReasons[] = 'DUPLICATE_REGNUMBER';
                $matches = $objectMapping[$registration] ?? [];
                if ($matches === []) $rowReasons[] = 'UNKNOWN_OBJECT';
                elseif (count(array_unique($matches)) !== 1) $rowReasons[] = 'AMBIGUOUS_OBJECT';
            }
            if ($previous !== null && $registration !== null && $row['amountCents'] !== null) {
                $old = $previous['byRegistration'][$registration] ?? null;
                if (is_int($old) && $row['amountCents'] < $old) $rowReasons[] = 'BALANCE_DECREASED';
            }
            foreach (array_unique($rowReasons) as $reason) $reasons[$reason] = ($reasons[$reason] ?? 0) + 1;
            if ($rowReasons === []) {
                $objectId = array_values(array_unique($objectMapping[$registration]))[0];
                $balances[] = [
                    'locator' => self::SHEET . '!' . $row['balanceCell'],
                    'locatorKey' => hash('sha256', $current['sha256'] . "\0" . self::SHEET . "\0" . $row['balanceCell']),
                    'registrationHash' => hash('sha256', $registration),
                    'objectId' => $objectId,
                    'asOf' => $current['asOf'],
                    'amountCents' => $row['amountCents'],
                ];
            }
        }
        usort($balances, static fn(array $a, array $b): int => $a['locator'] <=> $b['locator']);
        $canonical = json_encode($balances, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        ksort($reasons);
        return [
            'mode' => 'dry-run',
            'evidenceType' => 'paid_before_balance_assertion',
            'notPaymentFacts' => true,
            'formatVersion' => self::FORMAT_VERSION,
            'formatFingerprint' => self::formatFingerprint(),
            'artifactSha256' => $current['sha256'],
            'asOf' => $current['asOf'],
            'locatorScheme' => 'xlsx-sheet-cell-v1',
            'observedRows' => count($current['rows']),
            'acceptedAssertions' => count($balances),
            'acceptedTotalCents' => array_sum(array_column($balances, 'amountCents')),
            'evidenceDigest' => hash('sha256', $canonical),
            'quarantined' => array_sum($reasons),
            'quarantineReasons' => $reasons,
            'comparisonArtifactSha256' => $previous['sha256'] ?? null,
        ];
    }

    public static function formatFingerprint(): string
    {
        return hash('sha256', json_encode(['sheet' => self::SHEET, 'headers' => self::HEADERS, 'asOf' => 'DO2', 'registrationColumn' => 'B', 'balanceColumn' => 'EE', 'firstDataRow' => 4], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    private function read(string $path): array
    {
        if ($path === '' || !is_file($path) || is_link($path) || !is_readable($path)) throw new InvalidArgumentException('WORKBOOK_INVALID');
        $real = realpath($path);
        if ($real === false || str_starts_with($real, realpath(dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR)) throw new InvalidArgumentException('WORKBOOK_INVALID');
        $bytes = file_get_contents($real);
        if (!is_string($bytes) || $bytes === '') throw new InvalidArgumentException('WORKBOOK_INVALID');
        $zip = new ZipArchive();
        if ($zip->open($real, ZipArchive::RDONLY) !== true) throw new InvalidArgumentException('WORKBOOK_INVALID');
        try {
            $strings = $this->sharedStrings($zip);
            $worksheet = $this->worksheetXml($zip, self::SHEET);
            $cells = $this->relevantCells($worksheet);
            foreach (self::HEADERS as $cell => $expected) if ($this->cellText($cells, $strings, $cell) !== $expected) throw new InvalidArgumentException('WORKBOOK_FORMAT_UNSUPPORTED');
            $asOf = $this->excelDate($this->cellRaw($cells, 'DO2'));
            $artifactIssues = [];
            if ($asOf === null) $artifactIssues['AS_OF_INVALID'] = 1;
            if ($this->hasExternalLinks($zip)) $artifactIssues['EXTERNAL_LINK_PRESENT'] = 1;
            $rows = []; $byRegistration = [];
            $rowNumbers=[]; foreach(array_keys($cells) as$reference)if(preg_match('/^(?:B|EE)([0-9]+)$/D',$reference,$match)===1&&(int)$match[1]>=4)$rowNumbers[(int)$match[1]]=true; ksort($rowNumbers);
            foreach (array_keys($rowNumbers) as $number) {
                $registrationCell = $cells['B' . $number] ?? null;
                $balanceCell = $cells['EE' . $number] ?? null;
                if ($registrationCell === null && $balanceCell === null) continue;
                $registration = $this->registration($this->cellValue($registrationCell, $strings));
                $issues = [];
                if ($registration === null) $issues[] = 'REGNUMBER_MISSING_OR_INVALID';
                if ($asOf === null) $issues[] = 'AS_OF_MISSING_OR_INVALID';
                $formula = $balanceCell['formula'] ?? '';
                if ($formula !== '' && (str_contains($formula, '[') || str_contains($formula, 'http:') || str_contains($formula, 'https:'))) $issues[] = 'EXTERNAL_LINK_DEPENDENCY';
                $raw = $balanceCell['value'] ?? null;
                $amount = $this->moneyCents($raw);
                if ($raw === null || ($formula !== '' && $raw === '')) $issues[] = 'CACHED_NUMERIC_MISSING';
                elseif ($amount === null) $issues[] = 'BALANCE_NON_NUMERIC';
                elseif ($amount < 0) $issues[] = 'BALANCE_NEGATIVE';
                $rows[] = ['registration' => $registration, 'amountCents' => $amount, 'balanceCell' => 'EE' . $number, 'issues' => $issues];
                if ($registration !== null && is_int($amount)) $byRegistration[$registration] = $amount;
            }
            return ['sha256' => hash('sha256', $bytes), 'asOf' => $asOf, 'rows' => $rows, 'byRegistration' => $byRegistration, 'artifactIssues' => $artifactIssues];
        } finally { $zip->close(); }
    }

    /** @return list<string> */
    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml'); if (!is_string($xml)) return [];
        $root = simplexml_load_string($xml); if (!$root instanceof SimpleXMLElement) throw new InvalidArgumentException('WORKBOOK_INVALID');
        $root->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $values = [];
        foreach ($root->xpath('//m:si') ?: [] as $item) { $item->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'); $text = ''; foreach ($item->xpath('.//m:t') ?: [] as $part) $text .= (string) $part; $values[] = $text; }
        return $values;
    }

    private function worksheetXml(ZipArchive $zip, string $name): string
    {
        $workbook = simplexml_load_string((string) $zip->getFromName('xl/workbook.xml'));
        $rels = simplexml_load_string((string) $zip->getFromName('xl/_rels/workbook.xml.rels'));
        if (!$workbook instanceof SimpleXMLElement || !$rels instanceof SimpleXMLElement) throw new InvalidArgumentException('WORKBOOK_INVALID');
        $workbook->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'); $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $relationshipId = null;
        foreach ($workbook->xpath('//m:sheet') ?: [] as $sheet) if ((string) $sheet['name'] === $name) { $attributes = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships'); $relationshipId = (string) $attributes['id']; }
        $target = null; foreach ($rels->xpath('//*[local-name()="Relationship"]') ?: [] as $relationship) if ((string) $relationship['Id'] === $relationshipId) $target = (string) $relationship['Target'];
        if ($target === null || str_contains($target, '..')) throw new InvalidArgumentException('WORKBOOK_FORMAT_UNSUPPORTED');
        $xml = $zip->getFromName('xl/' . ltrim($target, '/'));
        if (!is_string($xml)) throw new InvalidArgumentException('WORKBOOK_INVALID'); return $xml;
    }

    /** @return array<string,array{type:string,value:?string,formula:string}> */
    private function relevantCells(string $xml): array
    {
        $reader=new XMLReader(); if(!$reader->XML($xml,null,LIBXML_NONET|LIBXML_COMPACT))throw new InvalidArgumentException('WORKBOOK_INVALID');$cells=[];
        while($reader->read())if($reader->nodeType===XMLReader::ELEMENT&&$reader->localName==='c'){$reference=$reader->getAttribute('r')??'';if(preg_match('/^(?:B[0-9]+|EE[0-9]+|DO[12])$/D',$reference)!==1)continue;$node=simplexml_load_string($reader->readOuterXml());if(!$node instanceof SimpleXMLElement)throw new InvalidArgumentException('WORKBOOK_INVALID');$node->registerXPathNamespace('m','http://schemas.openxmlformats.org/spreadsheetml/2006/main');$values=$node->xpath('./m:v')?:[];$formulas=$node->xpath('./m:f')?:[];$cells[$reference]=['type'=>(string)$node['t'],'value'=>$values===[]?null:trim((string)$values[0]),'formula'=>$formulas===[]?'':trim((string)$formulas[0])];}
        $reader->close();return$cells;
    }
    private function cellRaw(array $cells, string $reference): ?string { return $cells[$reference]['value'] ?? null; }
    private function cellValue(?array $cell, array $strings): ?string { if ($cell === null||$cell['value']===null) return null; return $cell['type'] === 's' ? ($strings[(int) $cell['value']] ?? null) : $cell['value']; }
    private function cellText(array $cells, array $strings, string $reference): ?string { $value = $this->cellValue($cells[$reference] ?? null, $strings); return $value === null ? null : preg_replace('/\s+/u', ' ', trim($value)); }
    private function registration(?string $value): ?string { $value = trim((string) $value); if (!preg_match('/^[0-9]{1,20}$/D', $value)) return null; return ltrim($value, '0') ?: '0'; }
    private function moneyCents(?string $value): ?int { if ($value === null || !preg_match('/^-?[0-9]{1,15}(?:\.[0-9]{1,8})?$/D', $value)) return null; $negative = str_starts_with($value, '-'); $value = ltrim($value, '-'); [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, ''); $fraction = substr(str_pad($fraction, 3, '0'), 0, 3); $cents = (int) $whole * 100 + (int) substr($fraction, 0, 2) + ((int) $fraction[2] >= 5 ? 1 : 0); return $negative ? -$cents : $cents; }
    private function excelDate(?string $serial): ?string { if ($serial === null || !preg_match('/^[0-9]{1,6}(?:\.0+)?$/D', $serial)) return null; $days = (int) $serial; if ($days < 1 || $days > 100000) return null; return (new DateTimeImmutable('1899-12-30', new DateTimeZone('UTC')))->modify('+' . $days . ' days')->format('Y-m-d'); }
    private function hasExternalLinks(ZipArchive $zip): bool { for ($i = 0; $i < $zip->numFiles; $i++) if (str_starts_with((string) $zip->getNameIndex($i), 'xl/externalLinks/externalLink')) return true; return false; }
}

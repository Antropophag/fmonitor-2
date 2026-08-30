<?php

declare(strict_types=1);

require_once __DIR__ . '/legacy-migration/OtizPaidBeforeBalanceProfiler.php';

function obpWorkbook(string $path, string $dateSerial, array $rows, bool $external = false): void
{
    $strings = ['Рег. Номер','отчетная дата','Премия за монтаж, выплаченная ранее на данный рег.номер'];
    foreach ($rows as $row) if (is_string($row[0]) && !preg_match('/^-?[0-9.]+$/D', $row[0])) $strings[] = $row[0];
    $index = array_flip($strings); $cells = '<row r="1"><c r="DO1" t="s"><v>1</v></c></row><row r="2"><c r="DO2"><v>'.$dateSerial.'</v></c></row><row r="3"><c r="B3" t="s"><v>0</v></c><c r="EE3" t="s"><v>2</v></c></row>';
    foreach ($rows as $offset => [$registration,$amount,$formula]) { $number=$offset+4; $regCell=is_string($registration)&&isset($index[$registration])?'<c r="B'.$number.'" t="s"><v>'.$index[$registration].'</v></c>':'<c r="B'.$number.'"><v>'.$registration.'</v></c>'; $formulaXml=$formula===''?'':'<f>'.htmlspecialchars($formula,ENT_XML1).'</f>'; $valueXml=$amount===null?'':'<v>'.$amount.'</v>'; $cells.='<row r="'.$number.'">'.$regCell.'<c r="EE'.$number.'">'.$formulaXml.$valueXml.'</c></row>'; }
    $zip=new ZipArchive(); if($zip->open($path,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true)throw new RuntimeException('fixture');
    $zip->addFromString('[Content_Types].xml','<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="xml" ContentType="application/xml"/></Types>');
    $zip->addFromString('xl/workbook.xml','<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Адресный перечень" sheetId="1" r:id="rId1"/></sheets></workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels','<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Target="worksheets/sheet1.xml"/></Relationships>');
    $zip->addFromString('xl/sharedStrings.xml','<?xml version="1.0"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'.implode('',array_map(static fn(string $s):string=>'<si><t>'.htmlspecialchars($s,ENT_XML1).'</t></si>',$strings)).'</sst>');
    $zip->addFromString('xl/worksheets/sheet1.xml','<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$cells.'</sheetData></worksheet>');
    if($external)$zip->addFromString('xl/externalLinks/externalLink1.xml','<?xml version="1.0"?><externalLink xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"/>');
    $zip->close();
}

$root=sys_get_temp_dir().'/fm2-otiz-balance-'.bin2hex(random_bytes(6));mkdir($root,0700);
try {
    $old=$root.'/old.xlsx';$current=$root.'/current.xlsx';
    obpWorkbook($old,'46250',[[1001,'12.34',''],[1002,'50','']],false);
    obpWorkbook($current,'46251',[[1001,'10',''],[1002,'50',''],[1002,'51',''],['bad','x',''],[1003,null,'[1]A1'],[1004,'-1',''],[1005,'20','']],true);
    $result=(new OtizPaidBeforeBalanceProfiler())->profile($current,['1001'=>[1],'1002'=>[2],'1003'=>[3],'1004'=>[4],'1005'=>[5,6]],$old);
    $expected=['AMBIGUOUS_OBJECT'=>1,'BALANCE_DECREASED'=>1,'BALANCE_NEGATIVE'=>1,'BALANCE_NON_NUMERIC'=>1,'CACHED_NUMERIC_MISSING'=>1,'DUPLICATE_REGNUMBER'=>2,'EXTERNAL_LINK_DEPENDENCY'=>1,'EXTERNAL_LINK_PRESENT'=>1,'REGNUMBER_MISSING_OR_INVALID'=>1];
    if($result['acceptedAssertions']!==0||$result['observedRows']!==7||$result['asOf']!=='2026-08-17'||$result['quarantineReasons']!==$expected||$result['notPaymentFacts']!==true)throw new RuntimeException('profile mismatch: '.json_encode($result));
    $good=$root.'/good.xlsx';obpWorkbook($good,'46251',[[1001,'12.345','']],false);$accepted=(new OtizPaidBeforeBalanceProfiler())->profile($good,['1001'=>[4512]]);
    if($accepted['acceptedAssertions']!==1||$accepted['acceptedTotalCents']!==1235||$accepted['quarantined']!==0||strlen($accepted['evidenceDigest'])!==64)throw new RuntimeException('accepted mismatch');
    echo "PASS OTIZ paid-before balance dry-run profiler\n";
} finally { foreach(glob($root.'/*')?:[] as$file)unlink($file);rmdir($root); }

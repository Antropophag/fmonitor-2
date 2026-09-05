<?php

declare(strict_types=1);

namespace FMonitor2\Tests\Support;

final class AssignmentOrderOriginalPdfCorpus
{
    /** @param list<string> $objects */
    public static function classic(array $objects,string $trailerExtra=''): string
    {
        $pdf="%PDF-1.7\n";$offsets=[0];
        foreach($objects as$index=>$body){$offsets[]=strlen($pdf);$number=$index+1;$pdf.="{$number} 0 obj\n{$body}\nendobj\n";}
        $xref=strlen($pdf);$pdf.="xref\n0 ".(count($objects)+1)."\n0000000000 65535 f \n";
        foreach(array_slice($offsets,1)as$offset)$pdf.=sprintf("%010d 00000 n \n",$offset);
        return$pdf."trailer\n<< /Size ".(count($objects)+1)." /Root 1 0 R {$trailerExtra}>>\nstartxref\n{$xref}\n%%EOF\n";
    }

    public static function passiveClassic(): string
    {
        return self::classic(['<< /Type /Catalog /Pages 2 0 R >>','<< /Type /Pages /Kids [3 0 R] /Count 1 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 72 72] >>']);
    }

    public static function zeroPage(): string
    {
        return self::classic(['<< /Type /Catalog /Pages 2 0 R >>','<< /Type /Pages /Kids [] /Count 0 >>']);
    }

    public static function encrypted(): string
    {
        return self::classic(['<< /Type /Catalog /Pages 2 0 R >>','<< /Type /Pages /Kids [3 0 R] /Count 1 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 72 72] >>','<< /Filter /Standard /V 1 /R 2 /O <00> /U <00> /P -4 >>'],'/Encrypt 4 0 R ');
    }

    public static function forbidden(string $name): string
    {
        return self::classic(["<< /Type /Catalog /Pages 2 0 R /{$name} 4 0 R >>",'<< /Type /Pages /Kids [3 0 R] /Count 1 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 72 72] >>','<< /Type /Action /S /Named >>']);
    }

    public static function xrefStream(): string
    {
        $pdf="%PDF-1.5\n";$offsets=[];foreach(['<< /Type /Catalog /Pages 2 0 R >>','<< /Type /Pages /Kids [3 0 R] /Count 1 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 72 72] >>']as$i=>$body){$number=$i+1;$offsets[$number]=strlen($pdf);$pdf.="{$number} 0 obj\n{$body}\nendobj\n";}$offsets[4]=strlen($pdf);$entries=pack('CNN',0,0,65535);for($i=1;$i<=4;$i++)$entries.=pack('CNN',1,$offsets[$i],0);$pdf.="4 0 obj\n<< /Type /XRef /Size 5 /Root 1 0 R /W [1 4 4] /Length ".strlen($entries)." >>\nstream\n{$entries}\nendstream\nendobj\nstartxref\n{$offsets[4]}\n%%EOF\n";return$pdf;
    }

    public static function xrefStreamWithIndex(bool $overlap): string
    {
        $pdf="%PDF-1.5\n";$offsets=[];foreach(['<< /Type /Catalog /Pages 2 0 R >>','<< /Type /Pages /Kids [3 0 R] /Count 1 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 72 72] >>']as$i=>$body){$number=$i+1;$offsets[$number]=strlen($pdf);$pdf.="{$number} 0 obj\n{$body}\nendobj\n";}$offsets[4]=strlen($pdf);$entries=pack('CNN',0,0,65535);for($i=1;$i<=4;$i++)$entries.=pack('CNN',1,$offsets[$i],0);$index=$overlap?'0 5 0 5':'0 3 3 2';if($overlap)$entries.=$entries;$pdf.="4 0 obj\n<< /Type /XRef /Size 5 /Root 1 0 R /W [1 4 4] /Index [{$index}] /Length ".strlen($entries)." >>\nstream\n{$entries}\nendstream\nendobj\nstartxref\n{$offsets[4]}\n%%EOF\n";return$pdf;
    }

    public static function objectStream(): string
    {
        $pdf="%PDF-1.5\n";$offsets=[];foreach(['<< /Type /Catalog /Pages 2 0 R >>','<< /Type /Pages /Kids [3 0 R] /Count 1 >>']as$i=>$body){$number=$i+1;$offsets[$number]=strlen($pdf);$pdf.="{$number} 0 obj\n{$body}\nendobj\n";}$payload='3 0 << /Type /Page /Parent 2 0 R /MediaBox [0 0 72 72] >>';$offsets[4]=strlen($pdf);$pdf.="4 0 obj\n<< /Type /ObjStm /N 1 /First 4 /Length ".strlen($payload)." >>\nstream\n{$payload}\nendstream\nendobj\n";$offsets[5]=strlen($pdf);$entries=pack('CNN',0,0,65535).pack('CNN',1,$offsets[1],0).pack('CNN',1,$offsets[2],0).pack('CNN',2,4,0).pack('CNN',1,$offsets[4],0).pack('CNN',1,$offsets[5],0);$pdf.="5 0 obj\n<< /Type /XRef /Size 6 /Root 1 0 R /W [1 4 4] /Length ".strlen($entries)." >>\nstream\n{$entries}\nendstream\nendobj\nstartxref\n{$offsets[5]}\n%%EOF\n";return$pdf;
    }

    /** @return array<string,string> */
    public static function unsafeCases(): array
    {
        $cases=[];foreach(['JavaScript','JS','OpenAction','AA','Launch','EmbeddedFiles','Filespec','FileAttachment','RichMedia','Movie','Sound','URI','GoToR','SubmitForm','ImportData']as$name)$cases[$name]=self::forbidden($name);return$cases;
    }

    public static function deepPageTree(int $depth=101): string
    {
        $objects=['<< /Type /Catalog /Pages 2 0 R >>'];
        for($i=0;$i<$depth;$i++){$number=$i+2;$child=$number+1;$objects[]="<< /Type /Pages /Parent ".($number===2?'1 0 R':($number-1).' 0 R')." /Kids [{$child} 0 R] /Count 1 >>";}
        $parent=$depth+1;$objects[]="<< /Type /Page /Parent {$parent} 0 R /MediaBox [0 0 72 72] >>";return self::classic($objects);
    }

    public static function duplicateIdentity(): string
    {
        $pdf=self::passiveClassic();$insert="1 0 obj\n<< /Type /Catalog /Pages 2 0 R /Duplicate true >>\nendobj\n";return str_replace("xref\n",$insert."xref\n",$pdf);
    }

    public static function prevCycle(): string
    {
        $pdf=self::passiveClassic();preg_match('/startxref\n([0-9]+)/',$pdf,$match);return str_replace('/Root 1 0 R >>','/Root 1 0 R /Prev '.$match[1].' >>',$pdf);
    }

    public static function validIncrementalPrev(): string
    {
        $base=self::passiveClassic();preg_match('/startxref\n([0-9]+)/',$base,$match);$previous=(int)$match[1];
        $offset=strlen($base);$base.="4 0 obj\n<< /Producer (FMonitor verifier) >>\nendobj\n";$xref=strlen($base);
        return $base."xref\n4 1\n".sprintf("%010d 00000 n \n",$offset)."trailer\n<< /Size 5 /Root 1 0 R /Prev {$previous} >>\nstartxref\n{$xref}\n%%EOF\n";
    }

    public static function escapedActiveName(): string
    {
        return self::classic(['<< /Type /Catalog /Pages 2 0 R /Java#53cript 4 0 R >>','<< /Type /Pages /Kids [3 0 R] /Count 1 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 72 72] >>','<< /Type /Action /S /Named >>']);
    }

    public static function indirectActiveAction(bool $reachable=true): string
    {
        $names=$reachable?' /Names 4 0 R':'';
        return self::classic(["<< /Type /Catalog /Pages 2 0 R{$names} >>",'<< /Type /Pages /Kids [3 0 R] /Count 1 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 72 72] >>','<< /JavaScript 5 0 R >>','<< /Names [(action) 6 0 R] >>','<< /Type /Action /S /Named >>']);
    }

    public static function decompressionBomb(): string
    {
        $inflated=str_repeat('A',67_108_865);$compressed=gzcompress($inflated,9);unset($inflated);return self::classic(['<< /Type /Catalog /Pages 2 0 R >>','<< /Type /Pages /Kids [3 0 R] /Count 1 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 72 72] >>','<< /Type /ObjStm /N 1 /First 4 /Filter /FlateDecode /Length '.strlen($compressed)." >>\nstream\n{$compressed}\nendstream"]);
    }

    public static function contentStreamLength(?int $declaredLength=null): string
    {
        $payload="q\nQ\n";$length=$declaredLength??strlen($payload);
        return self::classic(['<< /Type /Catalog /Pages 2 0 R >>','<< /Type /Pages /Kids [3 0 R] /Count 1 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 72 72] /Contents 4 0 R >>',"<< /Length {$length} >>\nstream\n{$payload}endstream"]);
    }

    public static function aggregateFlateContentStreams(int $decodedBytesPerStream): string
    {
        $streams=[];
        foreach(['A','B']as$byte){$inflated=str_repeat($byte,$decodedBytesPerStream);$compressed=gzcompress($inflated,9);unset($inflated);if(!is_string($compressed))throw new \RuntimeException('Fixture compression failed.');$streams[]='<< /Filter /FlateDecode /Length '.strlen($compressed).">>\nstream\n{$compressed}\nendstream";}
        return self::classic(['<< /Type /Catalog /Pages 2 0 R >>','<< /Type /Pages /Kids [3 0 R] /Count 1 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 72 72] /Contents [4 0 R 5 0 R] >>',$streams[0],$streams[1]]);
    }

    public static function repeatedClassicXrefIdentity(): string
    {
        $pdf=self::passiveClassic();$xref=strpos($pdf,"xref\n");if($xref===false)throw new \RuntimeException('Fixture xref missing.');$trailer=strpos($pdf,"trailer\n",$xref);if($trailer===false)throw new \RuntimeException('Fixture trailer missing.');$objectOneOffset=strpos($pdf,"1 0 obj\n");if($objectOneOffset===false)throw new \RuntimeException('Fixture object missing.');
        return substr($pdf,0,$trailer)."1 1\n".sprintf("%010d 00000 n \n",$objectOneOffset).substr($pdf,$trailer);
    }

    /** @return array<string,string> */
    public static function invalidPageTrees(): array
    {
        return[
            'root_not_catalog'=>self::classic(['<< /Type /CatXlog /Pages 2 0 R >>','<< /Type /Pages /Kids [3 0 R] /Count 1 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 72 72] >>']),
            'catalog_missing_pages'=>self::classic(['<< /Type /Catalog /Other 2 0 R >>','<< /Type /Pages /Kids [3 0 R] /Count 1 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 72 72] >>']),
            'catalog_pages_not_tree'=>self::classic(['<< /Type /Catalog /Pages 2 0 R >>','<< /Type /Panes /Kids [3 0 R] /Count 1 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 72 72] >>']),
            'pages_count_mismatch'=>self::classic(['<< /Type /Catalog /Pages 2 0 R >>','<< /Type /Pages /Kids [3 0 R] /Count 2 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 72 72] >>']),
            'page_only_via_other'=>self::classic(['<< /Type /Catalog /Pages 2 0 R >>','<< /Type /Pages /Kids [] /Count 0 /Other 3 0 R >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 72 72] >>']),
        ];
    }
}

<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;

final class AssignmentOrderOriginalPdfHistoryCorpus
{
    public const JPEG_BASE64='/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNzUK/9sAQwAIBgYHBgUIBwcHCQkICgwUDQwLCwwZEhMPFB0aHx4dGhwcICQuJyAiLCMcHCg3KSwwMTQ0NB8nOT04MjwuMzQy/9sAQwEJCQkMCwwYDQ0YMiEcITIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIy/8AAEQgAAQABAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A9/ooooA//9k=';

    public static function objects(string $catalogExtra=''):array
    {return ['<< /Type /Catalog /Pages 2 0 R '.$catalogExtra.' >>','<< /Type /Pages /Kids [3 0 R] /Count 1 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 72 72] >>'];}

    public static function direct(string $body,string $catalogExtra='',string $trailerExtra=''):string
    {return AssignmentOrderOriginalPdfCorpus::classic([...self::objects($catalogExtra),$body],$trailerExtra);}

    /** @param array<int,string> $objects Explicit rewritten object numbers. */
    public static function append(string $pdf,array $objects,int $size):string
    {
        preg_match_all('/startxref\n([0-9]+)\n%%EOF/',$pdf,$matches);$previous=(int)end($matches[1]);$offsets=[];
        foreach($objects as $number=>$body){$offsets[$number]=strlen($pdf);$pdf.="{$number} 0 obj\n{$body}\nendobj\n";}
        $xref=strlen($pdf);$pdf.="xref\n";foreach($offsets as $number=>$offset)$pdf.="{$number} 1\n".sprintf("%010d 00000 n \n",$offset);
        return $pdf."trailer\n<< /Size {$size} /Root 1 0 R /Prev {$previous} >>\nstartxref\n{$xref}\n%%EOF\n";
    }

    public static function overwritten(bool $active):string
    {
        $base=AssignmentOrderOriginalPdfCorpus::classic(self::objects($active?'/OpenAction << /S /JavaScript /JS (old-harmless-marker) >>':'/Producer (old-passive-marker)'));
        return self::append($base,[1=>self::objects()[0]],4);
    }

    public static function chain(int $sections):string
    {
        $pdf=AssignmentOrderOriginalPdfCorpus::passiveClassic();
        for($section=1;$section<$sections;++$section)$pdf=self::append($pdf,[1=>self::objects('/Generation ('.$section.')')[0]],4);
        return $pdf;
    }

    private static function stream(string $dictionary,string $payload):string
    {return '<< '.$dictionary.' /Length '.strlen($payload)." >>\nstream\n{$payload}\nendstream";}

    public static function image(string $filter,string $payload,?int $lengthDelta=null):string
    {
        $objects=self::objects();$objects[2]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 72 72] /Resources << /XObject << /Pixel 4 0 R >> >> /Contents 5 0 R >>';
        $image=self::stream('/Type /XObject /Subtype /Image /Width 1 /Height 1 /BitsPerComponent 8 /ColorSpace /DeviceRGB'.($filter===''?'':' /Filter '.$filter),$payload);
        if($lengthDelta!==null)$image=str_replace('/Length '.strlen($payload),'/Length '.(strlen($payload)+$lengthDelta),$image);
        return AssignmentOrderOriginalPdfCorpus::classic([...$objects,$image,self::stream('',"q 1 0 0 1 0 0 cm /Pixel Do Q")]);
    }

    public static function opaqueStream(string $dictionary,string $payload):string
    {return AssignmentOrderOriginalPdfCorpus::classic([...self::objects('/Extra 4 0 R'),self::stream($dictionary,$payload)]);}

    /** One compressed object 6, unreachable but explicitly listed in xref. */
    public static function objectStream(string $member,bool $flate=false,int $memberIndex=0,bool $escapedHeader=false):string
    {
        $payload='6 0 '.$member;$encoded=$flate?gzcompress($payload,9):$payload;if(!is_string($encoded))throw new \RuntimeException('fixture compression');
        $objects=self::objects();$objects[]=self::stream(($escapedHeader?'/Ty#70e /Obj#53tm':'/Type /ObjStm').' /N 1 /First 4'.($flate?' /Filter /FlateDecode':''),$encoded);
        return self::xrefDocument($objects,5,[6=>[4,$memberIndex]]);
    }

    public static function overwrittenObjectStream(bool $active,bool $badHistoricalIndex=false):string
    {
        $pdf=self::objectStream($active?'<< /JS (old-marker) >>':'<< /Producer (old-safe) >>',true,$badHistoricalIndex?1:0);
        // Current container contains object 8; old xref object 6 must resolve the
        // previous physical container, never this same-number replacement.
        preg_match('/startxref\n([0-9]+)/',$pdf,$previous);$offsets=[];
        for($number=1;$number<=5;++$number){$offsets[$number]=strpos($pdf,$number." 0 obj\n");if($offsets[$number]===false)throw new \RuntimeException('fixture base identity');}
        $payload='8 0 << /Producer (new-safe) >>';$encoded=gzcompress($payload,9);
        foreach([4=>self::stream('/Type /ObjStm /N 1 /First 4 /Filter /FlateDecode',$encoded),6=>'<< /Producer (current-direct-safe) >>'] as $number=>$body){$offsets[$number]=strlen($pdf);$pdf.="{$number} 0 obj\n{$body}\nendobj\n";}
        $offsets[7]=strlen($pdf);$entries=pack('CNN',0,0,65535);
        for($number=1;$number<=8;++$number)$entries.=$number===8?pack('CNN',2,4,0):pack('CNN',1,$offsets[$number],0);
        $pdf.="7 0 obj\n".self::stream('/Type /XRef /Size 9 /Root 1 0 R /Prev '.$previous[1].' /W [1 4 4]',$entries)."\nendobj\n";
        return $pdf."startxref\n{$offsets[7]}\n%%EOF\n";
    }

    /** @param list<string> $direct @param array<int,array{int,int}> $compressed */
    private static function xrefDocument(array $direct,int $xrefNumber,array $compressed):string
    {
        $pdf="%PDF-1.7\n";$offsets=[];foreach($direct as $index=>$body){$number=$index+1;$offsets[$number]=strlen($pdf);$pdf.="{$number} 0 obj\n{$body}\nendobj\n";}
        $offsets[$xrefNumber]=strlen($pdf);$size=max($xrefNumber,...array_keys($compressed))+1;$entries=pack('CNN',0,0,65535);
        for($number=1;$number<$size;++$number){if(isset($compressed[$number]))$entries.=pack('CNN',2,...$compressed[$number]);elseif(isset($offsets[$number]))$entries.=pack('CNN',1,$offsets[$number],0);else $entries.=pack('CNN',0,0,0);}
        $pdf.="{$xrefNumber} 0 obj\n".self::stream('/Type /XRef /Size '.$size.' /Root 1 0 R /W [1 4 4]',$entries)."\nendobj\n";
        return $pdf."startxref\n{$offsets[$xrefNumber]}\n%%EOF\n";
    }

    /** Exact aggregate structural decoded bytes; payloads contain valid objects. */
    public static function structuralAggregate(int $total,bool $referenceContainer=false):string
    {
        $objects=self::objects($referenceContainer?'/Extra 4 0 R':'');$first=intdiv($total,2);$sizes=[$first,$total-$first];
        foreach($sizes as $i=>$size){$number=7+$i;$head=$number.' 0 << /Padding (';$tail=') >>';$payload=$head.str_repeat($i===0?'A':'B',$size-strlen($head)-strlen($tail)).$tail;
            if(strlen($payload)!==$size)throw new \RuntimeException('fixture decoded bound');$encoded=gzcompress($payload,9);unset($payload);if(!is_string($encoded))throw new \RuntimeException('fixture compression');$objects[]=self::stream('/Type /ObjStm /N 1 /First 4 /Filter /FlateDecode',$encoded);}
        return self::xrefDocument($objects,6,[7=>[4,0],8=>[5,0]]);
    }
}

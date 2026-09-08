<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;

/** Composes public SHLZ Document Row and file-type assets. */
final class PilotDocumentView
{
    public static function row(string $filename,string $href,string $mediaType,int $bytes,string $version,string $modified):string
    {
        $e=PilotView::e(...);
        $extension=\strtolower(\pathinfo($filename,PATHINFO_EXTENSION));
        $kind=$mediaType==='application/pdf'?'file-pdf-default':(\in_array($extension,['doc','docx','xls','xlsx','ppt','pptx','csv','png','jpg','svg','zip','mp3','mp4','avi'],true)?$extension:'file-generic');
        $format=$mediaType==='application/pdf'?'PDF':\strtoupper($extension?:'Файл');
        $size=$bytes<=0?'':($bytes<1024?$bytes.' Б':($bytes<1048576?\number_format($bytes/1024,1,',','').' КБ':\number_format($bytes/1048576,1,',','').' МБ'));
        $metadata=\implode(' · ',\array_filter([$format,$size,$version],static fn(string $v):bool=>$v!==''));
        return '<div class="shlz-document-row" data-file-type="'.$e($kind==='file-pdf-default'?'pdf-default':$kind).'"><span class="shlz-document-row__visual" aria-hidden="true"><img src="/pilot/assets/file-types/'.$e($kind).'.svg" alt=""></span><span class="shlz-document-row__content"><a class="shlz-document-row__title" href="'.$e($href).'" title="'.$e($filename).'">'.$e($filename).'</a><span class="shlz-document-row__meta">'.$e($metadata).'</span><span class="shlz-document-row__modified">'.$e($modified).'</span></span><span class="shlz-document-row__actions"><a class="shlz-document-row__action" href="'.$e($href).'" download="'.$e($filename).'" aria-label="Скачать '.$e($filename).'"><img src="/pilot/assets/icons/download.svg" alt=""></a></span></div>';
    }
}

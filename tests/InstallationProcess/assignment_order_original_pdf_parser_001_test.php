<?php

declare(strict_types=1);

require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalPdfCorpus.php';

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalPdfStatus;
use FMonitor2\AssignmentOrderOriginal\FMonitorPassivePdfInspector;
use FMonitor2\Tests\Support\AssignmentOrderOriginalPdfCorpus as Corpus;

// Specification: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v14, owned algorithm fmonitor-passive-pdf-v1.
assertSameValue(1,preg_match('/^%PDF-/',Corpus::passiveClassic()),'Independent classic fixture has PDF magic.');
if(!class_exists(FMonitorPassivePdfInspector::class))throw new TestFailure('INTENDED_RED: approved FMonitorPassivePdfInspector production seam is absent.');
$inspector=new FMonitorPassivePdfInspector();assertSameValue('fmonitor-passive-pdf-v1',$inspector->algorithmId(),'Owned parser algorithm ID is exact.');
foreach(['classic'=>Corpus::passiveClassic(),'xref_stream'=>Corpus::xrefStream(),'object_stream'=>Corpus::objectStream()]as$name=>$bytes)assertSameValue(AssignmentOrderOriginalPdfStatus::PASSIVE_PDF,$inspector->inspect($bytes)->status,"{$name} passive PDF accepted.");
assertSameValue(AssignmentOrderOriginalPdfStatus::PASSIVE_PDF,$inspector->inspect(Corpus::validIncrementalPrev())->status,'A bounded valid incremental Prev chain is accepted after parsing every revision.');
foreach(['truncated'=>substr(Corpus::passiveClassic(),0,-12),'malformed'=>"%PDF-1.7\nnot objects\n%%EOF\n",'zero_page'=>Corpus::zeroPage()]as$name=>$bytes)assertSameValue(AssignmentOrderOriginalPdfStatus::INVALID_PDF,$inspector->inspect($bytes)->status,"{$name} fails closed invalid.");
$impossibleStartXref=preg_replace('/startxref\n[0-9]+\n%%EOF\n$/',"startxref\n99999999\n%%EOF\n",Corpus::passiveClassic());assertSameValue(AssignmentOrderOriginalPdfStatus::INVALID_PDF,$inspector->inspect((string)$impossibleStartXref)->status,'Out-of-file startxref offset fails closed.');
$outOfRangeXref=preg_replace('/0000000009 00000 n/', '9999999999 00000 n',Corpus::passiveClassic(),1);assertSameValue(AssignmentOrderOriginalPdfStatus::INVALID_PDF,$inspector->inspect((string)$outOfRangeXref)->status,'Out-of-file classic xref entry fails closed.');
$wrongClassicIdentity=preg_replace('/0000000009 00000 n/', '0000000058 00000 n',Corpus::passiveClassic(),1);
$mutateXrefStream=static function(string$pdf,int$object,string$entry):string{$stream=strpos($pdf,"stream\n",strpos($pdf,'/Type /XRef'));if($stream===false)throw new TestFailure('Independent xref-stream fixture has no stream.');$offset=$stream+7+($object*9);return substr_replace($pdf,$entry,$offset,9);};
$xrefStream=Corpus::xrefStream();assertSameValue(AssignmentOrderOriginalPdfStatus::PASSIVE_PDF,$inspector->inspect($xrefStream)->status,'Independent xref-stream positive control is accepted before entry mutation.');
$objectTwoOffset=strpos($xrefStream,"2 0 obj\n");if($objectTwoOffset===false)throw new TestFailure('Independent xref-stream fixture has object 2.');
$xrefFailures=['classic_identity'=>$inspector->inspect((string)$wrongClassicIdentity)->status,'stream_identity'=>$inspector->inspect($mutateXrefStream($xrefStream,1,pack('CNN',1,$objectTwoOffset,0)))->status,'stream_bounds'=>$inspector->inspect($mutateXrefStream($xrefStream,1,pack('CNN',1,strlen($xrefStream)+1,0)))->status,'stream_type'=>$inspector->inspect($mutateXrefStream($xrefStream,1,pack('CNN',3,0,0)))->status];assertSameValue(array_fill_keys(array_keys($xrefFailures),AssignmentOrderOriginalPdfStatus::INVALID_PDF),$xrefFailures,'Classic and stream xref entries are decoded, bounded and reconciled with exact object identities.');
assertSameValue(AssignmentOrderOriginalPdfStatus::UNSAFE_PDF,$inspector->inspect(Corpus::encrypted())->status,'Encrypted PDF is unsafe.');
foreach(Corpus::unsafeCases()as$name=>$bytes)assertSameValue(AssignmentOrderOriginalPdfStatus::UNSAFE_PDF,$inspector->inspect($bytes)->status,"Forbidden {$name} is unsafe.");
assertSameValue(AssignmentOrderOriginalPdfStatus::UNSAFE_PDF,$inspector->inspect(Corpus::escapedActiveName())->status,'Forbidden escaped name remains unsafe after lexical PDF-name decoding.');
$reachableIndirect=Corpus::indirectActiveAction(true);$unreachableIndirect=Corpus::indirectActiveAction(false);assertSameValue(false,str_contains(explode("endobj\n",$reachableIndirect,2)[0],'/JavaScript'),'Catalog has no direct forbidden action key.');assertSameValue(true,str_contains($reachableIndirect,'/Names 4 0 R'),'Allowed Catalog Names key is the only path to the referenced name-tree object.');assertSameValue(AssignmentOrderOriginalPdfStatus::UNSAFE_PDF,$inspector->inspect($reachableIndirect)->status,'Forbidden action reachable only through an indirect Names reference is unsafe.');assertSameValue(AssignmentOrderOriginalPdfStatus::PASSIVE_PDF,$inspector->inspect($unreachableIndirect)->status,'The identical forbidden dictionary is inert when unreachable, proving graph-traversal sensitivity.');
$unsupported=str_replace('/Length ', '/Filter /LZWDecode /Length ',Corpus::xrefStream());assertSameValue(AssignmentOrderOriginalPdfStatus::INVALID_PDF,$inspector->inspect($unsupported)->status,'Unsupported structural filter fails closed.');
$oversizeGraph=str_replace('/Size 5','/Size 100002',Corpus::xrefStream());assertSameValue(AssignmentOrderOriginalPdfStatus::INVALID_PDF,$inspector->inspect($oversizeGraph)->status,'Object bound fails closed.');
foreach(['prev_cycle'=>Corpus::prevCycle(),'duplicate_identity'=>Corpus::duplicateIdentity(),'reference_depth'=>Corpus::deepPageTree()]as$name=>$bytes)assertSameValue(AssignmentOrderOriginalPdfStatus::INVALID_PDF,$inspector->inspect($bytes)->status,"{$name} structural bound fails closed.");
assertSameValue(AssignmentOrderOriginalPdfStatus::INVALID_PDF,$inspector->inspect(Corpus::decompressionBomb())->status,'Aggregate structural decompression bound fails closed.');
fwrite(STDOUT,"ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_PDF_BOUNDARY_OK\n");

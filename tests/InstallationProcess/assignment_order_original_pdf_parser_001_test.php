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
assertSameValue(AssignmentOrderOriginalPdfStatus::UNSAFE_PDF,$inspector->inspect(Corpus::encrypted())->status,'Encrypted PDF is unsafe.');
foreach(Corpus::unsafeCases()as$name=>$bytes)assertSameValue(AssignmentOrderOriginalPdfStatus::UNSAFE_PDF,$inspector->inspect($bytes)->status,"Forbidden {$name} is unsafe.");
assertSameValue(AssignmentOrderOriginalPdfStatus::UNSAFE_PDF,$inspector->inspect(Corpus::escapedActiveName())->status,'Forbidden escaped name remains unsafe after lexical PDF-name decoding.');
$reachableIndirect=Corpus::indirectActiveAction(true);$unreachableIndirect=Corpus::indirectActiveAction(false);assertSameValue(false,str_contains(explode("endobj\n",$reachableIndirect,2)[0],'/JavaScript'),'Catalog has no direct forbidden action key.');assertSameValue(true,str_contains($reachableIndirect,'/Names 4 0 R'),'Allowed Catalog Names key is the only path to the referenced name-tree object.');assertSameValue(AssignmentOrderOriginalPdfStatus::UNSAFE_PDF,$inspector->inspect($reachableIndirect)->status,'Forbidden action reachable only through an indirect Names reference is unsafe.');assertSameValue(AssignmentOrderOriginalPdfStatus::PASSIVE_PDF,$inspector->inspect($unreachableIndirect)->status,'The identical forbidden dictionary is inert when unreachable, proving graph-traversal sensitivity.');
$unsupported=str_replace('/Length ', '/Filter /LZWDecode /Length ',Corpus::xrefStream());assertSameValue(AssignmentOrderOriginalPdfStatus::INVALID_PDF,$inspector->inspect($unsupported)->status,'Unsupported structural filter fails closed.');
$oversizeGraph=str_replace('/Size 5','/Size 100002',Corpus::xrefStream());assertSameValue(AssignmentOrderOriginalPdfStatus::INVALID_PDF,$inspector->inspect($oversizeGraph)->status,'Object bound fails closed.');
foreach(['prev_cycle'=>Corpus::prevCycle(),'duplicate_identity'=>Corpus::duplicateIdentity(),'reference_depth'=>Corpus::deepPageTree()]as$name=>$bytes)assertSameValue(AssignmentOrderOriginalPdfStatus::INVALID_PDF,$inspector->inspect($bytes)->status,"{$name} structural bound fails closed.");
assertSameValue(AssignmentOrderOriginalPdfStatus::INVALID_PDF,$inspector->inspect(Corpus::decompressionBomb())->status,'Aggregate structural decompression bound fails closed.');
fwrite(STDOUT,"ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_PDF_BOUNDARY_OK\n");

<?php

declare(strict_types=1);

require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalPdfCorpus.php';

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalPdfStatus;
use FMonitor2\AssignmentOrderOriginal\FMonitorPassivePdfInspector;
use FMonitor2\Tests\Support\AssignmentOrderOriginalPdfCorpus as Corpus;

$inspector=new FMonitorPassivePdfInspector();$cases=[];$expected=[];
foreach(['classic'=>Corpus::passiveClassic(),'incremental'=>Corpus::validIncrementalPrev(),'incremental_redefinition'=>Corpus::incrementalRedefinition(),'stream_object_tokens'=>Corpus::contentStreamObjectTokens()]as$name=>$bytes){$cases[$name]=$bytes;$expected[$name]=AssignmentOrderOriginalPdfStatus::PASSIVE_PDF;}
assertSameValue(2,preg_match_all('/(?:^|[\r\n])1 0 obj\s/',Corpus::incrementalRedefinition()),'Incremental control contains the same object identity in two revisions.');

foreach(['Root','Size']as$key)foreach(['duplicate'=>false,'conflicting'=>true]as$variant=>$conflict){$name=strtolower("classic_{$variant}_{$key}");$cases[$name]=Corpus::classicTrailerDictionaryVariant($key,$conflict);$expected[$name]=AssignmentOrderOriginalPdfStatus::INVALID_PDF;assertSameValue(2,preg_match_all('/\/'.preg_quote($key,'/').'\b/',$cases[$name]),"Classic {$variant} /{$key} fixture has exactly two selected keys.");}
$cases['incremental_duplicate_prev']=Corpus::incrementalPrevVariant();$cases['incremental_malformed_prev']=Corpus::incrementalPrevVariant(true);$expected['incremental_duplicate_prev']=$expected['incremental_malformed_prev']=AssignmentOrderOriginalPdfStatus::INVALID_PDF;assertSameValue(2,preg_match_all('/\/Prev\b/',$cases['incremental_duplicate_prev']),'Incremental duplicate Prev fixture changes only Prev multiplicity.');assertSameValue(1,preg_match_all('/\/Prev\b/',$cases['incremental_malformed_prev']),'Incremental malformed Prev fixture contains one nonconforming key.');

$cases['ordinary_duplicate_length']=Corpus::contentStreamDuplicateLength();$expected['ordinary_duplicate_length']=AssignmentOrderOriginalPdfStatus::INVALID_PDF;assertSameValue(2,preg_match_all('/\/Length\b/',$cases['ordinary_duplicate_length']),'Ordinary stream mutation duplicates only Length.');

foreach(['N','First','Length','Filter']as$key)foreach(['duplicate'=>false,'conflicting'=>true]as$variant=>$conflict){$name=strtolower("objstm_{$variant}_{$key}");$cases[$name]=Corpus::objectStreamDictionaryVariant($key,$conflict);$expected[$name]=AssignmentOrderOriginalPdfStatus::INVALID_PDF;$objectBody=explode("endobj\n",explode("4 0 obj\n",$cases[$name],2)[1],2)[0];assertSameValue(2,preg_match_all('/\/'.preg_quote($key,'/').'\b/',$objectBody),"ObjStm {$variant} /{$key} fixture has exactly two selected keys.");}

$actual=[];foreach($cases as$name=>$bytes){assertSameValue(true,strlen($bytes)<1024,"{$name} fixture remains compact and bounded.");$actual[$name]=$inspector->inspect($bytes)->status;}
assertSameValue($expected,$actual,'Newest-revision graph, stream framing and every structural dictionary key obey the approved fail-closed grammar.');
fwrite(STDOUT,"ASSIGNMENT_ORDER_ORIGINAL_PDF_INCREMENTAL_RED_OK\n");

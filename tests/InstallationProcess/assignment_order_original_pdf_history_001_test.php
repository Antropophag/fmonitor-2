<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalPdfCorpus.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalPdfHistoryCorpus.php';
use FMonitor2\AssignmentOrderOriginal\FMonitorPassivePdfInspector;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalPdfStatus as Status;
use FMonitor2\Tests\Support\AssignmentOrderOriginalPdfCorpus as Corpus;
use FMonitor2\Tests\Support\AssignmentOrderOriginalPdfHistoryCorpus as History;
// PDF-HISTORY-001 v0.1. Expected statuses are literal contract values.
$inspector=new FMonitorPassivePdfInspector();$failed=[];$passed=0;
function pdfHistoryCase(string $name,callable $case):void{global $failed,$passed;try{$case();++$passed;echo "PASS $name\n";}catch(Throwable $error){$failed[]=$name;echo "FAIL $name: ".$error->getMessage()."\n";}}
function pdfHistoryStatus(string $name,string $bytes,Status $expected):void{global $inspector;pdfHistoryCase($name,fn()=>assertSameValue($expected,$inspector->inspect($bytes)->status,'exact public inspection status'));}
pdfHistoryCase('public-algorithm-constant',function()use($inspector){assertSameValue(true,defined(FMonitorPassivePdfInspector::class.'::ALGORITHM_ID'),'declared public algorithm constant exists');$constant=(new ReflectionClass(FMonitorPassivePdfInspector::class))->getReflectionConstant('ALGORITHM_ID');assertSameValue(true,$constant->isPublic(),'algorithm constant public');assertSameValue(['fmonitor-passive-pdf-v1','fmonitor-passive-pdf-v1'],[$constant->getValue(),$inspector->algorithmId()],'same owned algorithm ID');});
foreach(['classic'=>Corpus::passiveClassic(),'xref'=>Corpus::xrefStream(),'objstm-raw'=>Corpus::objectStream(),'objstm-flate'=>Corpus::objectStreamVariant(0,'FlateDecode'),'incremental'=>Corpus::validIncrementalPrev()] as $name=>$bytes)pdfHistoryStatus('baseline-'.$name,$bytes,Status::PASSIVE_PDF);
$names=['JavaScript','JS','OpenAction','AA','Launch','EmbeddedFiles','Filespec','FileAttachment','RichMedia','Movie','Sound','URI','GoToR','SubmitForm','ImportData'];
foreach($names as $name)pdfHistoryStatus('unreachable-'.$name,History::direct('<< /'.$name.' (marker-only) >>'),Status::UNSAFE_PDF);
pdfHistoryStatus('unreachable-benign',History::direct('<< /Producer (passive-marker) >>'),Status::PASSIVE_PDF);
pdfHistoryStatus('old-active-overwritten',History::overwritten(true),Status::UNSAFE_PDF);
pdfHistoryStatus('old-benign-overwritten',History::overwritten(false),Status::PASSIVE_PDF);
pdfHistoryStatus('trailer-active',History::direct('<< /Producer (safe) >>',trailerExtra:'/JS (trailer-marker) '),Status::UNSAFE_PDF);
pdfHistoryStatus('trailer-benign-prefix',History::direct('<< /Producer (safe) >>',trailerExtra:'/JS-Notes (safe) '),Status::PASSIVE_PDF);
pdfHistoryStatus('indirect-name-value',History::direct('/JavaScript'),Status::UNSAFE_PDF);
foreach([false,true] as $flate){pdfHistoryStatus('unreachable-objstm-active-'.(int)$flate,History::objectStream('<< /JS (member-marker) >>',$flate),Status::UNSAFE_PDF);pdfHistoryStatus('unreachable-objstm-benign-'.(int)$flate,History::objectStream('<< /Producer (member-safe) >>',$flate),Status::PASSIVE_PDF);}
pdfHistoryStatus('escaped-structural-type-active',History::objectStream('<< /JS (marker) >>',true,escapedHeader:true),Status::UNSAFE_PDF);
pdfHistoryStatus('escaped-structural-type-benign',History::objectStream('<< /Producer (safe) >>',true,escapedHeader:true),Status::PASSIVE_PDF);
pdfHistoryStatus('old-objstm-active-overwritten',History::overwrittenObjectStream(true),Status::UNSAFE_PDF);
pdfHistoryStatus('old-objstm-benign-own-container',History::overwrittenObjectStream(false),Status::PASSIVE_PDF);
pdfHistoryStatus('bad-historical-type2-index',History::overwrittenObjectStream(false,true),Status::INVALID_PDF);
$lexical=[
 'literal'=>'/Note (harmless /JS text)',
 'nested-literal'=>'/Note (outer (inner /JS) tail)',
 'escaped-literal'=>'/Note (before \\(literal /JS\\) after)',
 'comment'=>"% harmless /JS\n /Note (safe)",
 'hex-string'=>'/Note <2F4A53>',
 'odd-hex-string'=>'/Note <2F4A5>',
 'hyphen-name'=>'/JS-Notes true',
 'dot-name'=>'/JS.Notes true',
 'underscore-name'=>'/JS_Notes true',
 'digit-name'=>'/JS0 true',
 'escaped-hyphen'=>'/JS#2DNotes true',
 'escaped-space'=>'/JS#20Notes true',
 'escaped-slash'=>'/JS#2FNotes true',
 'escaped-hash-not-recursive'=>'/J#2353 true',
 'lower-case'=>'/js true',
];
foreach($lexical as $name=>$entry){pdfHistoryStatus('lexical-current-'.$name,Corpus::classic(History::objects($entry)),Status::PASSIVE_PDF);pdfHistoryStatus('lexical-unreachable-'.$name,History::direct('<< '.$entry.' >>'),Status::PASSIVE_PDF);}
pdfHistoryStatus('escaped-active-name',History::direct('<< /J#53 (marker-only) >>'),Status::UNSAFE_PDF);
pdfHistoryStatus('escaped-active-object-stream',History::objectStream('<< /J#53 (marker-only) >>',true),Status::UNSAFE_PDF);
foreach(['bad-escape'=>'<< /Ab#XZ true >>','short-escape'=>'<< /Ab#1 true >>','nul-escape'=>'<< /Ab#00 true >>','unclosed-literal'=>'<< /Note (unfinished','dangling-string-escape'=>'<< /Note (unfinished\\','unclosed-hex'=>'<< /Note <0123','bad-hex'=>'<< /Note <01GG> >>'] as $name=>$body)pdfHistoryStatus('malformed-lexical-'.$name,History::direct($body),Status::INVALID_PDF);
$jpeg=base64_decode(History::JPEG_BASE64,true);
assertSameValue(692,strlen($jpeg),'pinned GD-generated 1x1 JPEG size');assertSameValue('47bdc9f10aaf2c373979c51d960ee3c4cef49e8b466e21a22558243ca6ceaa16',hash('sha256',$jpeg),'pinned JPEG literal hash');assertSameValue(true,str_contains($jpeg,"\xFF\xC0\x00\x11\x08\x00\x01\x00\x01\x03"),'JPEG SOF declares 1x1 RGB without a runtime codec dependency');
pdfHistoryStatus('quoted-structural-keys-not-entries',History::opaqueStream('/Note (/Length 999 /Type /ObjStm)','opaque'),Status::PASSIVE_PDF);
pdfHistoryStatus('comment-structural-keys-not-entries',History::opaqueStream("% /Length 999 /Type /ObjStm\n /Note (safe)",'opaque'),Status::PASSIVE_PDF);
pdfHistoryStatus('structural-type-prefix-is-opaque',History::opaqueStream('/Type /ObjStm-Notes','/JS'),Status::PASSIVE_PDF);
pdfHistoryStatus('opaque-asciihex-image',History::image('/ASCIIHexDecode','000000>'),Status::PASSIVE_PDF);
pdfHistoryStatus('opaque-real-jpeg-image',History::image('/DCTDecode',$jpeg),Status::PASSIVE_PDF);
pdfHistoryStatus('opaque-filter-array',History::image('[/ASCIIHexDecode]','000000>'),Status::PASSIVE_PDF);
pdfHistoryStatus('opaque-marker-pixel',History::image('','/JS'),Status::PASSIVE_PDF);
pdfHistoryStatus('opaque-flate-image',History::image('/FlateDecode',gzcompress("\x00\x00\x00",9)),Status::PASSIVE_PDF);
// Opaque name admission only; codec/pixel interpretation is explicitly outside this inspector.
foreach(['ASCII85Decode','LZWDecode','RunLengthDecode','CCITTFaxDecode','JBIG2Decode','JPXDecode'] as $filter)pdfHistoryStatus('opaque-filter-name-admission-'.$filter,History::image('/'.$filter,'opaque-data'),Status::PASSIVE_PDF);
pdfHistoryStatus('opaque-multiple-filter-names',History::image('[/ASCII85Decode /DCTDecode]','opaque-data'),Status::PASSIVE_PDF);
pdfHistoryStatus('opaque-escaped-filter-name',History::image('/ASCIIHex#44ecode','000000>'),Status::PASSIVE_PDF);
pdfHistoryStatus('opaque-escaped-duplicate-key',History::image('/DCTDecode /Fil#74er /DCTDecode',$jpeg),Status::INVALID_PDF);
foreach(['/MadeUpFilter','[/MadeUpFilter]','[/ASCIIHexDecode 1]','[]','4 0 R','/DCTDecode /Filter /DCTDecode'] as $index=>$filter)pdfHistoryStatus('bad-opaque-filter-'.$index,History::image($filter,'000000>'),Status::INVALID_PDF);
pdfHistoryStatus('crypt-filter-unsafe',History::image('/Crypt','000000>'),Status::UNSAFE_PDF);
pdfHistoryStatus('opaque-short-length',History::image('/ASCIIHexDecode','000000>',-1),Status::INVALID_PDF);
pdfHistoryStatus('opaque-long-length',History::image('/ASCIIHexDecode','000000>',2),Status::INVALID_PDF);
pdfHistoryStatus('structural-lzw-still-invalid',str_replace('/Length ','/Filter /LZWDecode /Length ',Corpus::xrefStream()),Status::INVALID_PDF);
pdfHistoryStatus('structural-filter-array-still-invalid',Corpus::xrefStreamGrammar('array'),Status::INVALID_PDF);
pdfHistoryStatus('structural-filter-indirect-still-invalid',Corpus::xrefStreamGrammar('indirect'),Status::INVALID_PDF);
pdfHistoryStatus('terminating-64-sections',History::chain(64),Status::PASSIVE_PDF);
pdfHistoryStatus('continued-65-sections',History::chain(65),Status::INVALID_PDF);
foreach([67_108_864=>Status::PASSIVE_PDF,67_108_865=>Status::INVALID_PDF] as $total=>$status){$bytes=History::structuralAggregate($total);assertSameValue(true,strlen($bytes)<200_000,'exact structural budget fixture stays compact');pdfHistoryStatus('structural-total-'.$total,$bytes,$status);unset($bytes);}
$cached=History::structuralAggregate(67_108_864,true);pdfHistoryStatus('structural-budget-cached-not-double-counted',$cached,Status::PASSIVE_PDF);unset($cached);
$opaque=Corpus::aggregateFlateContentStreams(33_554_433);assertSameValue(true,strlen($opaque)<200_000,'opaque expansion control is received-byte bounded');pdfHistoryStatus('opaque-flate-not-structural-budget',$opaque,Status::PASSIVE_PDF);unset($opaque);
if($failed!==[])throw new TestFailure('PDF history failed '.count($failed).' cases: '.implode(',',$failed));
echo "PASS PDF-HISTORY-001 ($passed cases)\n";

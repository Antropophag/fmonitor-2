<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use FMonitor2\YiiRuntime\Models\OriginalMetadata;
$fields=['csrfToken'=>'synthetic-csrf','requestId'=>'14141414-1414-4414-8414-000000000001','mode'=>'initial','documentDate'=>'2026-09-04','appendixStartDate'=>'2026-09-20','compositionConfirmed'=>true,'rootOriginalId'=>null,'targetRevisionId'=>null,'expectedCurrentRevisionId'=>null,'correctionReason'=>null,'originalFilename'=>'signed.pdf'];
$encode=static fn(array$f):string=>base64_encode(json_encode($f,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_LINE_TERMINATORS|JSON_THROW_ON_ERROR));
try{$actual=OriginalMetadata::fromHeader($encode($fields))->fields;}catch(InvalidArgumentException){throw new TestFailure('INTENDED_RED: original metadata has no independent appendixStartDate');}
assertSameValue($fields,$actual,'future appendix date is represented separately from documentDate');
foreach(['missing','type','extra']as$case){$bad=$fields;if($case==='missing')unset($bad['appendixStartDate']);elseif($case==='type')$bad['appendixStartDate']=42;else$bad['actualStartDate']='2026-09-21';$denied=false;try{OriginalMetadata::fromHeader($encode($bad));}catch(InvalidArgumentException){$denied=true;}assertSameValue(true,$denied,'strict metadata '.$case);}
echo "PASS ACTUAL-START-FROM-ORDER-001 HTTP metadata\n";

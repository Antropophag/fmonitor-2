<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\InstallationProcess\ProductionPdfAssignmentOrderRenderer;

function pdfInput(): array
{
    return [
        'assignmentOrderVersion'=>7,
        'assignmentOrderDate'=>'2026-08-27',
        'organizationType'=>'brigade',
        'installationObjectSnapshot'=>[
            'address'=>'Москва, ул. Проверочная, д. 10',
            'entrance'=>'2',
            'objectRegistrationNumber'=>'77-000123',
            'plannedStartDate'=>'2026-10-05',
            'plannedFinishDate'=>'2026-12-20',
        ],
        'installers'=>[
            ['tabId'=>1042,'fullName'=>'Иванов Иван Иванович','position'=>'Электромеханик по лифтам'],
            ['tabId'=>1043,'fullName'=>'Сидоров Сергей Сергеевич','position'=>'Помощник электромеханика'],
        ],
        'controlEngineer'=>['userId'=>73,'fullName'=>'Петров Пётр Петрович','position'=>'Инженер строительного контроля'],
    ];
}

/** @return list<string> */
function pdfDecodedStreams(string $pdf): array
{
    preg_match_all('/stream\R(.*?)\Rendstream/s',$pdf,$matches);
    $streams=[];
    foreach($matches[1] as $stream){$decoded=@gzuncompress($stream);$streams[]=is_string($decoded)?$decoded:$stream;}
    return $streams;
}

function assertPdfTextMarker(array $streams,string $marker): void
{
    $needle=strtolower(bin2hex(mb_convert_encoding($marker,'UTF-16BE','UTF-8')));
    foreach($streams as $stream){if(str_contains(strtolower(bin2hex($stream)),$needle))return;}
    throw new TestFailure("Combined PDF must contain semantic text marker: {$marker}");
}

function pdfMarkerPosition(array $streams,string $marker): int
{
    $needle=strtolower(bin2hex(mb_convert_encoding($marker,'UTF-16BE','UTF-8')));$haystack=strtolower(bin2hex(implode('', $streams)));$position=strpos($haystack,$needle);if($position===false)throw new TestFailure("PDF marker absent: {$marker}");return$position;
}

function assertPdfTextMarkerAbsent(array $streams,string $marker):void
{
    $needle=strtolower(bin2hex(mb_convert_encoding($marker,'UTF-16BE','UTF-8')));$haystack=strtolower(bin2hex(implode('', $streams)));assertSameValue(false,str_contains($haystack,$needle),"PDF must not contain marker: {$marker}");
}

$renderer=new ProductionPdfAssignmentOrderRenderer();
$artifacts=$renderer->renderAssignmentOrder(pdfInput());
assertSameValue(1,count($artifacts),'PDF renderer must return one combined artifact.');
$artifact=$artifacts[0];
assertSameValue('order',$artifact['type'],'Combined artifact must use the order type.');
assertSameValue('Распоряжение о закреплении монтажников.pdf',$artifact['filename'],'Combined artifact must expose the Unicode production filename.');
assertSameValue('application/pdf',$artifact['mediaType'],'Combined artifact must expose the PDF media type.');
assertSameValue('%PDF-',substr($artifact['bytes'],0,5),'Renderer must return PDF bytes.');
assertSameValue(3,preg_match_all('/\/Type\s*\/Page\b/',$artifact['bytes']),'Combined PDF must contain two order pages and one appendix page.');
$streams=pdfDecodedStreams($artifact['bytes']);
foreach(['РАСПОРЯЖЕНИЕ','Перечень монтажников','Москва, ул. Проверочная, д. 10','Иванов Иван Иванович','Петров Пётр Петрович'] as $marker)assertPdfTextMarker($streams,$marker);

$changeInput=pdfInput();
$changeInput['documentInstallers']=[
    $changeInput['installers'][1]+['workStatus'=>'Работа'],
    $changeInput['installers'][0]+['workStatus'=>'Перемещён'],
];
$changeArtifact=$renderer->renderAssignmentOrder($changeInput)[0];
$changeStreams=pdfDecodedStreams($changeArtifact['bytes']);
assertPdfTextMarker($changeStreams,'Перемещён');
assertPdfTextMarker($changeStreams,'Работа');
assertSameValue(true,pdfMarkerPosition($changeStreams,'Сидоров Сергей Сергеевич')<pdfMarkerPosition($changeStreams,'Работа')&&pdfMarkerPosition($changeStreams,'Работа')<pdfMarkerPosition($changeStreams,'Иванов Иван Иванович')&&pdfMarkerPosition($changeStreams,'Иванов Иван Иванович')<pdfMarkerPosition($changeStreams,'Перемещён'),'PDF rows must correlate Sidorov with Work and Ivanov with Moved status in row order.');
$noRemovalInput=pdfInput();$noRemovalInput['documentInstallers']=array_map(static fn(array$x):array=>$x+['workStatus'=>'Работа'],$noRemovalInput['installers']);$noRemovalStreams=pdfDecodedStreams($renderer->renderAssignmentOrder($noRemovalInput)[0]['bytes']);assertPdfTextMarker($noRemovalStreams,'Иванов Иван Иванович');assertPdfTextMarker($noRemovalStreams,'Сидоров Сергей Сергеевич');assertPdfTextMarker($noRemovalStreams,'Работа');assertPdfTextMarkerAbsent($noRemovalStreams,'Перемещён');

$invalidCases=[
    'zero version'=>static function(array &$input):void{$input['assignmentOrderVersion']=0;},
    'impossible order date'=>static function(array &$input):void{$input['assignmentOrderDate']='2026-02-30';},
    'unknown organization type'=>static function(array &$input):void{$input['organizationType']='crew';},
    'missing object field'=>static function(array &$input):void{unset($input['installationObjectSnapshot']['objectRegistrationNumber']);},
    'impossible planned date'=>static function(array &$input):void{$input['installationObjectSnapshot']['plannedFinishDate']='2026-13-01';},
    'empty installers'=>static function(array &$input):void{$input['installers']=[];},
    'invalid installer tab id'=>static function(array &$input):void{$input['installers'][0]['tabId']=0;},
    'missing engineer name'=>static function(array &$input):void{unset($input['controlEngineer']['fullName']);},
];
foreach($invalidCases as $case=>$mutate){$input=pdfInput();$mutate($input);$error=null;try{$renderer->renderAssignmentOrder($input);}catch(Throwable $caught){$error=$caught;}assertSameValue(InvalidArgumentException::class,$error===null?null:$error::class,"{$case} must fail with the stable input exception type.");assertSameValue('Invalid assignment order document input.',$error?->getMessage(),"{$case} must fail with the stable redacted message.");}

echo "PASS: ProductionPdfAssignmentOrderRenderer semantic contract\n";

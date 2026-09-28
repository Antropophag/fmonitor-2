<?php declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require dirname(__DIR__,2).'/app/autoload.php';
use FMonitor2\Otiz\OtizSettlementV2Workbook;
function wfiles(string$b):array{$p=tempnam(sys_get_temp_dir(),'xlsx');file_put_contents($p,$b);$z=new ZipArchive();assertSameValue(true,$z->open($p)===true,'independent XLSX reader');$o=[];for($i=0;$i<$z->numFiles;$i++)$o[$z->getNameIndex($i)]=$z->getFromIndex($i);$z->close();unlink($p);return$o;}
function wpath(array$f,string$n):string{$w=simplexml_load_string($f['xl/workbook.xml']);$w->registerXPathNamespace('m','http://schemas.openxmlformats.org/spreadsheetml/2006/main');$id='';foreach($w->xpath('//m:sheet')as$s)if((string)$s['name']===$n)$id=(string)$s->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];$r=simplexml_load_string($f['xl/_rels/workbook.xml.rels']);$r->registerXPathNamespace('p','http://schemas.openxmlformats.org/package/2006/relationships');foreach($r->xpath('//p:Relationship')as$x)if((string)$x['Id']===$id)return'xl/'.(string)$x['Target'];throw new TestFailure('sheet '.$n);}
function wrows(string$x):array{preg_match_all('/<row\b[^>]*>(.*?)<\/row>/s',$x,$m);return$m[1]??[];}
function wcells(string$r):array{preg_match_all('/<c\b([^>]*)>(.*?)<\/c>/s',$r,$m);$o=[];foreach($m[2]??[]as$i=>$b){preg_match('/<t[^>]*>(.*?)<\/t>|<v>(.*?)<\/v>/s',$b,$v);$raw=($v[1]??'')!==''?$v[1]:($v[2]??'');$o[]=['a'=>$m[1][$i]??'','v'=>html_entity_decode(strip_tags($raw),ENT_QUOTES|ENT_XML1,'UTF-8')];}return$o;}
function wfmt(array$f,string$a):string{assertSameValue(1,preg_match('/\bs="(\d+)"/',$a,$m),'style id');$s=simplexml_load_string($f['xl/styles.xml']);$s->registerXPathNamespace('m','http://schemas.openxmlformats.org/spreadsheetml/2006/main');$xf=$s->xpath('//m:cellXfs/m:xf');$id=(int)$xf[(int)$m[1]]['numFmtId'];foreach($s->xpath('//m:numFmts/m:numFmt')as$n)if((int)$n['numFmtId']===$id)return(string)$n['formatCode'];return[14=>'yyyy-mm-dd',2=>'0.00',10=>'0.00%'][$id]??'builtin-'.$id;}
$source=['calculation'=>['id'=>42,'status'=>'accepted','reportDate'=>'2026-09-26','revision'=>3,'rulesVersion'=>'otiz-v2','acceptedAt'=>'2026-09-26T10:00:00+03:00','acceptedBy'=>502,'contentHash'=>str_repeat('a',64),'totalCents'=>1498766],'objects'=>[
 ['objectId'=>1,'regnumber'=>'014903','address'=>'=HYPERLINK("bad")','fundCents'=>10000000,'plannedPremiumCents'=>1234500,'grossCents'=>900000,'deadlineCents'=>50000,'disciplineCents'=>10000,'payableCents'=>898766,'shaftBp'=>11000,'confirmedBp'=>6000,'paidBeforeCents'=>100000,'daysLate'=>5,'kssBp'=>9500,'responsibleName'=>'Петров Пётр','objectStatus'=>'working','change'=>'revision-1','changeDate'=>'2026-09-25'],
 ['objectId'=>2,'regnumber'=>'000007','address'=>'Невский, 1','fundCents'=>20000000,'plannedPremiumCents'=>2345600,'grossCents'=>600000,'deadlineCents'=>0,'disciplineCents'=>0,'payableCents'=>600000,'shaftBp'=>10000,'confirmedBp'=>4000,'paidBeforeCents'=>0,'daysLate'=>0,'kssBp'=>10000,'responsibleName'=>'Сидоров Сидор','objectStatus'=>'completed','change'=>'revision-2','changeDate'=>'2026-09-26']],
'recipients'=>[['employeeId'=>'A','tabNumber'=>'00103','name'=>'Иванов','objectId'=>1,'originalWeight'=>60,'finalShareBp'=>10000,'preDeductionCents'=>900000,'deductionCents'=>1234,'employment'=>'employed','amountCents'=>898766],['employeeId'=>'A','tabNumber'=>'00103','name'=>'Иванов','objectId'=>2,'originalWeight'=>40,'finalShareBp'=>10000,'preDeductionCents'=>600000,'deductionCents'=>0,'employment'=>'employed','amountCents'=>600000],['employeeId'=>'B','tabNumber'=>'00104','name'=>'Иванов','objectId'=>2,'originalWeight'=>10,'finalShareBp'=>0,'preDeductionCents'=>0,'deductionCents'=>0,'employment'=>'dismissed','excluded'=>true,'amountCents'=>0]],
'deductions'=>[['objectId'=>1,'employeeId'=>null,'amountCents'=>10000,'reason'=>'Общее удержание','document'=>'COMMON-1','createdAt'=>'2026-09-26T09:00:00+03:00'],['objectId'=>1,'employeeId'=>'A','amountCents'=>1234,'reason'=>'Личное удержание','document'=>'PERSONAL-1','createdAt'=>'2026-09-26T09:01:00+03:00']],
'decisions'=>[['employeeId'=>'B','decision'=>'do_not_pay','reason'=>'Приказ 7','actorId'=>501,'createdAt'=>'2026-09-26T09:02:00+03:00']],'issues'=>[['code'=>'EXCLUDED_ZERO','message'=>'Исключённый получатель']]];
$draft=OtizSettlementV2Workbook::build($source+['mode'=>'draft']);assertSameValue(true,str_contains($draft['filename'],'Черновик-Не-основание-выплаты'),'draft filename');
function wdateCell(array $cell):string { preg_match('/\bt="([^"]+)"/',$cell['a'],$type);assertSameValue(true,in_array($type[1]??'',['','n'],true),'date is a numeric OOXML cell rather than a styled string');assertSameValue(1,preg_match('/^\d+$/D',$cell['v']),'business date is an integral Excel serial');return(new DateTimeImmutable('1899-12-30'))->modify('+'.$cell['v'].' days')->format('Y-m-d'); }
$f=wfiles(OtizSettlementV2Workbook::build($source+['mode'=>'payment','exportedAt'=>'2026-09-26T12:00:00+03:00'])['bytes']);assertSameValue([false,false,true],[isset($f['xl/vbaProject.bin']),str_contains(implode("\n",array_keys($f)),'externalLink'),isset($f['xl/styles.xml'])],'safe styled XLSX');
$mp=wpath($f,'Расчёт ОТиЗ');$ap=wpath($f,'Приложение к приказу');$dp=wpath($f,'Удержания');$rp=wpath($f,'Решения по выплате');$meta=wpath($f,'Метаданные');$rows=wrows($f[$mp]);$hi=null;foreach($rows as$i=>$r)if(str_contains($r,'Расчетный период'))$hi=$i;assertSameValue(true,$hi!==null,'21-field header on main sheet');$c=wcells($rows[$hi+1]??'');assertSameValue(21,count($c),'21 fields');assertSameValue(['014903','1.1','12345','Иванов','00103','0.6','1000','5','0.95','500','9000','1','9000','12.34','8987.66','Петров Пётр','Трудоустроен','revision-1','2026-09-25','Монтажные работы'],array_map(fn($i)=>$i===19?wdateCell($c[$i]):$c[$i]['v'],range(1,20)),'exact saved field map and scales');assertSameValue('yyyy-mm-dd',wfmt($f,$c[0]['a']),'date style');foreach([6]as$i)assertSameValue('0.00%',wfmt($f,$c[$i]['a']),'percent style '.$i);foreach([2,3,7,9,10,11,13,14,15]as$i)assertSameValue('0.00',wfmt($f,$c[$i]['a']),'money style '.$i);
$ar=wrows($f[$ap]);assertSameValue(2,count($ar),'one positive recipient');assertSameValue(true,str_contains($ar[1],'00103')&&str_contains($ar[1],'14987.66'),'stable recipient aggregate');assertSameValue(false,str_contains($f[$ap],'00104'),'zero excluded');assertSameValue(true,str_contains($f[$dp],'COMMON-1')&&str_contains($f[$dp],'PERSONAL-1'),'distinct deductions');assertSameValue(true,str_contains($f[$rp],'Не платить')&&str_contains($f[$rp],'Приказ 7'),'decision explanation');foreach(['otiz-v2',str_repeat('a',64),'2026-09-26T10:00:00+03:00']as$v)assertSameValue(true,str_contains($f[$meta],$v),'metadata '.$v);foreach(['<cols>','<pane','autoFilter','pageMargins','pageSetup']as$v)assertSameValue(true,str_contains($f[$mp],$v),'layout '.$v);assertSameValue(true,str_contains($f['xl/workbook.xml'],'_xlnm.Print_Area')&&str_contains($f['xl/workbook.xml'],'_xlnm.Print_Titles'),'print definitions');$all=implode("\n",array_filter($f,fn($k)=>str_starts_with($k,'xl/worksheets/'),ARRAY_FILTER_USE_KEY));assertSameValue([false,true],[str_contains($all,'<f>'),str_contains($all,'=HYPERLINK(&quot;bad&quot;)')],'formula injection stored as text');
$h=implode("\n",wfiles(OtizSettlementV2Workbook::build($source+['mode'=>'history','exportedAt'=>'2026-10-01T12:00:00+03:00'])['bytes']));assertSameValue(true,str_contains($h,'Историческая выгрузка')&&str_contains($h,'на момент выгрузки'),'history labeling');assertSameValue(false,OtizSettlementV2Workbook::paymentExportAllowed($source,'unknown'),'unknown blocks');assertSameValue(true,OtizSettlementV2Workbook::historicalExportAllowed($source),'history allowed');
assertSameValue(true,str_contains($f[$ap],'Основание'),'payment appendix explains saved calculation basis');
assertSameValue(true,str_contains($f[$rp],'501')&&str_contains($f[$rp],'2026-09-26T09:02:00+03:00'),'decision workbook preserves saved actor/time');
$unknown=$source;$unknown['objects'][0]['daysLate']=null;
$unknownFiles=wfiles(OtizSettlementV2Workbook::build($unknown+['mode'=>'history'])['bytes']);
$unknownRows=wrows($unknownFiles[wpath($unknownFiles,'Расчёт ОТиЗ')]);
$unknownHeader=null;foreach($unknownRows as$i=>$row)if(str_contains($row,'Расчетный период'))$unknownHeader=$i;assertSameValue('',wcells($unknownRows[$unknownHeader+1])[8]['v'],'unknown deadline days do not become a false zero');
$paymentFilename=OtizSettlementV2Workbook::build($source+['mode'=>'payment'])['filename'];
$historyFilename=OtizSettlementV2Workbook::build($source+['mode'=>'history'])['filename'];
assertSameValue(false,$paymentFilename===$historyFilename,'payment and history files have distinguishable names');
assertSameValue('2026-09-26',wdateCell($c[0]),'numeric report date displays the exact saved day');
$mainXml=simplexml_load_string($f[$mp]);$mainXml->registerXPathNamespace('m','http://schemas.openxmlformats.org/spreadsheetml/2006/main');$filter=$mainXml->xpath('//m:autoFilter')[0];assertSameValue('A2:U'.count($rows),(string)$filter['ref'],'filter begins at real column headings below mode banner and includes every saved data row');
$styles=simplexml_load_string($f['xl/styles.xml']);$styles->registerXPathNamespace('m','http://schemas.openxmlformats.org/spreadsheetml/2006/main');assertSameValue(1,count($styles->xpath('//m:cellStyles/m:cellStyle[@builtinId="0"]')),'default workbook style exists for independent spreadsheet readers');
foreach(['draft'=>'Черновик. Не основание выплаты','history'=>'Историческая выгрузка','payment'=>'Реестр к выплате']as$mode=>$label){$book=wfiles(OtizSettlementV2Workbook::build($source+['mode'=>$mode])['bytes']);$main=$book[wpath($book,'Расчёт ОТиЗ')];$mainRows=wrows($main);assertSameValue(true,str_contains($mainRows[0],$label),'visible main first row identifies '.$mode.' mode');$xml=simplexml_load_string($main);$xml->registerXPathNamespace('m','http://schemas.openxmlformats.org/spreadsheetml/2006/main');assertSameValue('A1:U1',(string)$xml->xpath('//m:mergeCell')[0]['ref'],'mode banner spans printable report width');assertSameValue('2',(string)$xml->xpath('//m:pane')[0]['ySplit'],'banner and field headings both stay visible');assertSameValue(true,str_contains($book['xl/workbook.xml'],'$1:$2'),'printed header repeats mode banner and headings');}
$duplicate=$source;$issue=['code'=>'DUAL_ISSUE','message'=>'Одинаковая диагностика разных объектов','owner'=>'ФКР'];$duplicate['objects'][0]['issues']=[$issue];$duplicate['objects'][1]['issues']=[$issue];$duplicate['issues'][]=$issue+['objectId'=>1,'regnumber'=>'014903'];$book=wfiles(OtizSettlementV2Workbook::build($duplicate+['mode'=>'history'])['bytes']);$control=$book[wpath($book,'Контроль')];assertSameValue(2,count(array_filter(wrows($control),static fn($row)=>str_contains($row,'DUAL_ISSUE'))),'same object issue deduplicates across input paths without collapsing a different object');assertSameValue(true,str_contains($control,'014903')&&str_contains($control,'000007'),'distinct affected object identities retained');

// OTIZ-EXPORT-KTU-RU-001: original saved contribution is distinct from payment share.
function wmain(array $files): array {
    return array_map('wcells', array_slice(wrows($files[wpath($files, 'Расчёт ОТиЗ')]), 2));
}
$bounded = $source;
$bounded['recipients'] = [
    array_replace($source['recipients'][0], ['originalWeight'=>6000000, 'weight'=>7, 'amountCents'=>898766]),
    array_replace($source['recipients'][2], ['objectId'=>1, 'originalWeight'=>4000000, 'weight'=>99]),
    array_replace($source['recipients'][1], ['originalWeight'=>20000000]),
    array_replace($source['recipients'][2], ['employeeId'=>'C', 'originalWeight'=>0]),
];
$bounded['objects'][0]['change'] = 'object_details_revision_7';
$bounded['objects'][0]['entitlements'] = [
    ['kind'=>'progress','sourceId'=>'case-1-item-7','sourceRevision'=>'hash-1'],
    ['kind'=>'pto','sourceId'=>'completion-2','sourceRevision'=>'hash-2'],
    ['kind'=>'declaration','sourceId'=>'completion-3','sourceRevision'=>'hash-3'],
];
$bounded['decisions'][] = ['employeeId'=>'A','decision'=>'pay','reason'=>'Owner text remains literal','actorId'=>501];
foreach (['draft','history','payment'] as $mode) {
    $before = serialize($bounded);
    $book = wfiles(OtizSettlementV2Workbook::build($bounded + ['mode'=>$mode])['bytes']);
    $data = wmain($book);
    assertSameValue(['0.6','0.4','1','0'], array_map(static fn($row)=>$row[12]['v'],$data), 'original per-object slice KTU, including excluded and zero contributors: '.$mode);
    foreach ($data as $row) {
        assertSameValue('0.00', wfmt($book,$row[12]['a']), 'KTU coefficient format');
        assertSameValue(true, str_contains($row[12]['a'],'t="n"'), 'KTU is an Excel number');
    }
    assertSameValue(['8987.66','0','6000','0'], array_map(static fn($row)=>$row[15]['v'],$data), 'saved money unaffected by KTU normalization');
    assertSameValue(['Трудоустроен','Уволен','Трудоустроен','Уволен'], array_map(static fn($row)=>$row[17]['v'],$data), 'Russian saved workforce statuses');
    assertSameValue(['Монтажные работы','Монтажные работы','Работы завершены','Работы завершены'], array_map(static fn($row)=>$row[20]['v'],$data), 'Russian saved object statuses');
    assertSameValue('Изменение реквизитов объекта № 7',$data[0][18]['v'],'generated change label translated');
    $workers = array_map('wcells',array_slice(wrows($book[wpath($book,'Работники')]),1));
    assertSameValue(['6000000','4000000','20000000','0'],array_map(static fn($row)=>$row[3]['v'],$workers),'raw evidence weights retained separately');
    assertSameValue(['1','0','1','0'],array_map(static fn($row)=>$row[4]['v'],$workers),'payment shares do not replace contribution shares');
    $allSheets=implode("\n",array_filter($book,static fn($k)=>str_starts_with($k,'xl/worksheets/'),ARRAY_FILTER_USE_KEY));
    foreach (['>employed<','>dismissed<','>working<','>completed<','>unknown<','>pay<','>do_not_pay<','>Revision<','>Content hash<','>Actor<','>progress<','>pto<','>declaration<'] as $token) {
        assertSameValue(false,str_contains($allSheets,$token),'no English system label '.$token);
    }
    foreach (['Платить','Не платить','case-1-item-7','hash-1','otiz-v2','Owner text remains literal'] as $literal) assertSameValue(true,str_contains($allSheets,$literal),'localized presentation retains evidence '.$literal);
    assertSameValue($before,serialize($bounded),'export does not mutate saved input');
}
$fallback=$bounded;
foreach($fallback['recipients'] as &$recipient){$recipient['weight']=$recipient['originalWeight'];unset($recipient['originalWeight']);}unset($recipient);
assertSameValue(['0.6','0.4','1','0'],array_map(static fn($row)=>$row[12]['v'],wmain(wfiles(OtizSettlementV2Workbook::build($fallback)['bytes']))),'production weight field is normalized too');
foreach(['missing','zero','negative'] as $case){
    $unknownWeights=$fallback;
    if($case==='missing')unset($unknownWeights['recipients'][0]['weight']);
    elseif($case==='zero'){$unknownWeights['recipients'][0]['weight']=0;$unknownWeights['recipients'][1]['weight']=0;}
    else $unknownWeights['recipients'][0]['weight']=-1;
    $unknownWeights['recipients'][0]['employment']='new_unknown_status';
    $unknownWeights['objects'][0]['objectStatus']='new_unknown_status';
    $data=wmain(wfiles(OtizSettlementV2Workbook::build($unknownWeights)['bytes']));
    assertSameValue(['',''],[$data[0][12]['v'],$data[1][12]['v']],'unknown group contribution stays blank: '.$case);
    assertSameValue(['Нет данных','Нет данных'],[$data[0][17]['v'],$data[0][20]['v']],'unknown statuses remain explicit in Russian');
    assertSameValue('1',$data[2][12]['v'],'another object remains independent');
}
foreach(['needs_assignment_order'=>'Требуется распоряжение','assignment_order_prepared'=>'Требуется распоряжение','ready_to_open'=>'Готов к открытию','installation'=>'Монтажные работы','document_closeout'=>'Документарное закрытие','needs_assignment_change'=>'Требуется изменение','assignment_change_prepared'=>'Требуется изменение',''=> 'Нет данных'] as $code=>$label){
    $statuses=$source;$statuses['objects'][0]['objectStatus']=$code;
    assertSameValue($label,wmain(wfiles(OtizSettlementV2Workbook::build($statuses)['bytes']))[0][20]['v'],'saved status mapping: '.$code);
}
echo "settlement_v2_workbook_001_test: OK\n";

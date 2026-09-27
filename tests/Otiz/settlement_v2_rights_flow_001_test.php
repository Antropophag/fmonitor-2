<?php
declare(strict_types=1);

// OTIZ-SETTLEMENT-V2-001: real checklist HTTP -> canonical builder -> owner -> obligations.
// The normative fixture fund is 650,000 rubles; 10/10/20% scales the owner's
// 100,000-ruble example by 6.5 without inventing a production premium norm.
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__).'/Yii2/InspectionFixture.php';

use FMonitor2\Otiz\OtizSettlementV2;
use FMonitor2\Otiz\OtizSettlementV2DraftBuilder;
use FMonitor2\Otiz\OtizPortfolioEconomy;

function rightsAmounts(array $calculation): array
{
    $amounts = [];
    foreach ($calculation['recipients'] as $recipient) {
        if ((int)$recipient['amountCents'] !== 0) {
            $amounts[(string)$recipient['employeeId']] = (int)$recipient['amountCents'];
        }
    }
    ksort($amounts);
    return $amounts;
}

function rightsWorkbookBasis(string $bytes,string $name='Основания'): array
{
    $path=tempnam(sys_get_temp_dir(),'otiz-rights-xlsx');file_put_contents($path,$bytes);$zip=new ZipArchive();
    try{
        assertSameValue(true,$zip->open($path)===true,'real HTTP workbook opens independently');
        $book=simplexml_load_string($zip->getFromName('xl/workbook.xml'));$book->registerXPathNamespace('m','http://schemas.openxmlformats.org/spreadsheetml/2006/main');$rid=null;
        foreach($book->xpath('//m:sheet')as$sheet)if((string)$sheet['name']===$name)$rid=(string)$sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
        $rels=simplexml_load_string($zip->getFromName('xl/_rels/workbook.xml.rels'));$rels->registerXPathNamespace('p','http://schemas.openxmlformats.org/package/2006/relationships');$target=null;
        foreach($rels->xpath('//p:Relationship')as$rel)if((string)$rel['Id']===$rid)$target='xl/'.(string)$rel['Target'];
        assertSameValue(true,is_string($target),'selected rights have a workbook basis sheet');
        $sheet=simplexml_load_string($zip->getFromName($target));$sheet->registerXPathNamespace('m','http://schemas.openxmlformats.org/spreadsheetml/2006/main');$rows=[];
        foreach($sheet->xpath('//m:sheetData/m:row')as$row){$values=[];$types=[];foreach($row->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main')->c as$cell){$children=$cell->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main');$types[]=(string)$cell->attributes()['t'];$values[]=(string)$cell->attributes()['t']==='inlineStr'?(string)$children->is->t:(string)$children->v;}$values['_types']=$types;$rows[]=$values;}
        $rows=array_slice($rows,1);
        if($name==='Основания')foreach($rows as&$row){assertSameValue(true,in_array($row['_types'][4],['','n'],true),'recognition date is typed numeric');assertSameValue(1,preg_match('/^\d+$/D',$row[4]),'recognition date is an Excel day serial');$row[4]=(new DateTimeImmutable('1899-12-30'))->modify('+'.$row[4].' days')->format('Y-m-d');}unset($row);
        return $rows;
    }finally{$zip->close();unlink($path);}
}

$fixture = null;
$yii = null;
$failures = [];
$check = static function (string $label, callable $assertion) use (&$failures): void {
    try { $assertion(); echo "PASS $label\n"; }
    catch (TestFailure|DomainException $error) { $failures[] = $label.': '.$error->getMessage(); }
};
try {
    $fixture = new InspectionFixture(dirname(__DIR__, 2));
    $fixture->open();
    $http = $fixture->http;
    $db = $http->db;
    $p = $http->p;
    $db->query("INSERT INTO {$p}fm2_pilot_role_permissions(role_id,permission) VALUES(7,'otiz.manage')");
    $payload = json_decode($db->query("SELECT payload_json FROM {$p}fm2_pilot_object_details WHERE object_id=4512")->fetch_column(), true, flags: JSON_THROW_ON_ERROR);
    $payload['fields']['pitmaterial'] = ['raw'=>'41', 'display'=>'41'];
    $payload['fields']['lift_type'] = ['raw'=>'1', 'display'=>'пассажирский'];
    $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    $db->prepare("UPDATE {$p}fm2_pilot_object_details SET payload_json=?,content_sha256=? WHERE object_id=4512")->execute([$encoded, hash('sha256', $encoded)]);
    $db->query("UPDATE {$p}fm_maintable SET plan_finish_date='2026-10-01' WHERE id=4512");
    $csrf = InspectionFixture::csrf($fixture->page());
    $sequence = 1;
    $revision = 0;
    $send = static function (int $item, int $tab, string $type = 'item_completed') use ($fixture, $csrf, &$sequence, &$revision): void {
        $operation = InspectionFixture::operation($sequence++, $revision, $item);
        $operation['type'] = $type;
        $operation['deviceTime'] = '2026-09-03T10:00:00+03:00';
        $operation['installerTabIds'] = [$tab];
        $operation['sectionId'] = $item >= 37 ? 2 : ($item >= 28 ? 1 : ($item <= 6 ? 3 : 4));
        $result = InspectionFixture::result($fixture->send($operation, $csrf), 200, 'accepted');
        $revision = $result['revision'];
    };
    foreach ([28,29,30,31,32,33] as $item) $send($item, 7001); // 10% A
    foreach ([34,36,37,38] as $item) $send($item, 7002); // 10% B, latest event only B
    $environment = $http->environment();
    $yii = new yii\db\Connection([
        'dsn'=>'mysql:host='.$environment['FMONITOR_DB_HOST'].';port='.$environment['FMONITOR_DB_PORT'].';dbname='.$http->database,
        'username'=>$environment['FMONITOR_DB_USER'], 'password'=>$environment['FMONITOR_DB_PASSWORD'], 'charset'=>'utf8mb4',
    ]);
    $yii->open();
    $clock = static fn(): string => '2026-09-28T12:00:00+03:00';
    $builder = new OtizSettlementV2DraftBuilder($yii, $p, $p, $clock);
    $owner = new OtizSettlementV2($yii, $p, $clock, static fn(): string => 'allow');
    $serial = 1;
    $op = static function () use (&$serial): string { return sprintf('eeeeeeee-eeee-4eee-8eee-%012d', $serial++); };
    $initialInput = $builder->build(73, '2026-09-03');
    assertSameValue([65000000, 10000], [$initialInput['objects'][0]['fundCents'], $initialInput['objects'][0]['kssBp']], 'canonical normative fund/Kss setup');
    $initial = $owner->createDraft(73, $initialInput, $op());
    assertSameValue([7001=>6500000, 7002=>6500000], rightsAmounts($initial), 'initial A and B each own ten percent');
    $send(28,7002,'item_installers_changed');
    $check('accept detects changed attribution of its selected canonical work',static function()use($owner,$initial,$op,$db,$p):void{
        $before=(int)$db->query("SELECT COUNT(*) FROM {$p}fm2_otiz_entitlement_claims")->fetch_column();
        try{$owner->accept(73,$initial['calculationId'],$initial['revision'],$op());throw new TestFailure('stale saved attribution accepted');}
        catch(DomainException $e){assertSameValue('STALE_CALCULATION',$e->getMessage(),'relevant source correction requires explicit refresh');}
        assertSameValue($before,(int)$db->query("SELECT COUNT(*) FROM {$p}fm2_otiz_entitlement_claims")->fetch_column(),'stale source writes no claims');
        assertSameValue('draft',$owner->read($initial['calculationId'])['status'],'stale draft stays editable');
    });
    $send(28,7001,'item_installers_changed');
    if($owner->read($initial['calculationId'])['status']==='accepted'){
        // Continue collecting independent regressions after a reproduced unsafe acceptance.
        $bad=$owner->read($initial['calculationId']);$owner->cancel(73,$bad['calculationId'],$bad['revision'],'Изоляция ошибочного результата регрессии',$op());
        $initial=$owner->createDraft(73,$builder->build(73,'2026-09-03'),$op());
    }else{$initial=$owner->refreshDraft(73,$initial['calculationId'],$initial['revision'],$builder->build(73,'2026-09-03'),$op());}
    $accepted = $owner->accept(73, $initial['calculationId'], $initial['revision'], $op());
    $id = $accepted['calculationId'];
    $activeOriginalId=$id;
    $check('no new work is not accrued twice', static function () use ($builder): void {
        $again = $builder->build(73, '2026-09-04');
        assertSameValue(0, array_sum(array_map(static fn($o)=>array_sum(array_column($o['entitlements'], 'grossCents')), $again['objects'])), 'accepted-unpaid work has no new gross');
    });
    $check('T07 no new work has a usable HTTP return to unpaid obligations',static function()use($fixture,$http,$db,$p,$id,$op):void{
        $before=(int)$db->query("SELECT COUNT(*) FROM {$p}fm2_otiz_calculation_revisions")->fetch_column();
        $response=$http->form('/pilot/otiz/calculations',['_csrf'=>$http->token($fixture->cookies),'operationId'=>$op(),'reportDate'=>'2026-09-04'],$fixture->cookies);
        if($response['status']===303)$response=$http->request('GET',$response['headers']['location'][0],[],$fixture->cookies);
        assertSameValue(200,$response['status'],'expected no-work result is not a server error');
        assertSameValue(true,str_contains($response['body'],'Новых работ для расчёта нет'),'clear no-work explanation');
        assertSameValue(false,str_contains($response['body'],'EMPTY_CALCULATION'),'no internal exception code');
        assertSameValue(true,str_contains($response['body'],'/pilot/otiz/calculations/'.$id),'old unpaid obligation remains reachable');
        assertSameValue($before,(int)$db->query("SELECT COUNT(*) FROM {$p}fm2_otiz_calculation_revisions")->fetch_column(),'no empty financial document persisted');
    });
    foreach (['2026-09-03', '2026-09-04'] as $date) {
        $check('unchanged replacement '.$date, static function () use ($builder, $owner, $id, $accepted, $date, $op): void {
            $replacement = $owner->createReplacementDraft(73, $id, $accepted['revision'], $builder->buildReplacement(73, $date, $id), 'Проверка неизменных работ', $op());
            try {
                assertSameValue([7001=>6500000, 7002=>6500000], rightsAmounts($replacement), 'report date alone does not change recipients');
                assertSameValue($accepted['totalCents'], $owner->read($id)['remainingPayableCents'], 'preview preserves original payable');
            } finally { $owner->deleteDraft(73, $replacement['calculationId'], $replacement['revision'], 'Проверка закончена', $op()); }
        });
    }
    foreach ([39,40,41,1,2,3,4,5,6,7,9] as $item) $send($item, 7002); // precisely 20% new work
    $check('new unrelated work does not invalidate earlier payment basis',static function()use($owner,$id):void{$owner->assertPaymentExportAllowed(73,$id);});
    $newInput = $builder->build(73, '2026-09-04');
    $new = $owner->createDraft(73, $newInput, $op());
    $check('only B receives new twenty percent', static function () use ($new): void {
        assertSameValue([7002=>13000000], rightsAmounts($new), 'old work cannot receive the new pool');
        assertSameValue([2000,4000,2000],[$new['objects'][0]['acceptedBp']??null,$new['objects'][0]['confirmedBp']??null,$new['objects'][0]['newBp']??null],'saved result distinguishes prior, confirmed and selected work volume');
        assertSameValue(13000000,array_sum(array_column($new['objects'][0]['entitlements']??[],'grossCents')),'saved projection retains selected rights for UI and workbook basis');
    });
    $savedIncrementBasis=null;
    $check('real HTTP XLSX contains only saved selected rights',static function()use($new,$http,$fixture,$db,$p,&$savedIncrementBasis):void{
        $response=$http->request('GET','/pilot/otiz/calculations/'.$new['calculationId'].'/export.xlsx?mode=draft',[],$fixture->cookies);
        assertSameValue(200,$response['status'],'draft basis export available without mutating money');$rows=rightsWorkbookBasis($response['body']);
        $expectedIds=array_map(static fn($item)=>'case-6101-item-'.$item,[39,40,41,1,2,3,4,5,6,7,9]);$actualIds=array_column($rows,2);sort($expectedIds);sort($actualIds);assertSameValue($expectedIds,$actualIds,'workbook contains exact newly selected item identities, not all historical work or unknown fallback');
        $saved=json_decode($db->query("SELECT input_json FROM {$p}fm2_otiz_calculation_revisions WHERE id=".(int)$new['calculationId'])->fetch_column(),true,flags:JSON_THROW_ON_ERROR);$versions=array_column($saved['objects'][0]['entitlements'],'sourceRevision','sourceId');$total=0;
        foreach($rows as$row){assertSameValue(true,in_array($row['_types'][5],['','n'],true),'basis amount is a numeric XLSX cell, not a numeric-looking string');assertSameValue(['progress',$versions[$row[2]],'2026-09-03'],[$row[1],$row[3],$row[4]],'XLSX kind/version/date exactly match saved selected work');assertSameValue(1,preg_match('/^(\d+)(?:\.(\d{1,2}))?$/D',$row[5],$m),'basis money is a typed numeric exact decimal');$total+=(int)$m[1]*100+(int)str_pad($m[2]??'',2,'0');}
        assertSameValue(13000000,$total,'selected workbook rights sum to independently known B increment');$savedIncrementBasis=$rows;
    });
    $check('replacement with unclaimed new work preserves old attribution', static function () use ($builder, $owner, $id, $accepted, $op): void {
        $replacement = $owner->createReplacementDraft(73, $id, $accepted['revision'], $builder->buildReplacement(73, '2026-09-04', $id), 'Замена с новым объёмом', $op());
        try {
            $amounts = rightsAmounts($replacement);
            assertSameValue(6500000, $amounts[7001] ?? 0, 'A keeps exactly the original ten percent');
            assertSameValue(true, in_array($amounts[7002] ?? 0, [6500000,19500000], true), 'new B work is explicitly included or left for next calculation');
        } finally { $owner->deleteDraft(73, $replacement['calculationId'], $replacement['revision'], 'Проверка закончена', $op()); }
    });
    $neighbor = $owner->accept(73, $new['calculationId'], $new['revision'], $op());
    $check('replacement never borrows neighbor accepted work', static function () use ($builder, $owner, $id, $accepted, $neighbor, $op): void {
        $replacement = $owner->createReplacementDraft(73, $id, $accepted['revision'], $builder->buildReplacement(73, '2026-09-05', $id), 'Соседний расчёт сохранён', $op());
        try { assertSameValue([7001=>6500000,7002=>6500000], rightsAmounts($replacement), 'replacement owns only its original rights'); }
        finally { $owner->deleteDraft(73, $replacement['calculationId'], $replacement['revision'], 'Проверка закончена', $op()); }
        assertSameValue($neighbor, $owner->read($neighbor['calculationId']), 'neighbor snapshot and payable unchanged');
    });
    $send(28, 7002, 'item_installers_changed'); // only the 2% work changes A -> B
    $check('changed owned attribution stops unpaid payment basis',static function()use($owner,$id):void{
        try{$owner->assertPaymentExportAllowed(73,$id);throw new TestFailure('changed recipient source remained valid for payment');}
        catch(DomainException $e){assertSameValue('SNAPSHOT_REPLACEMENT_REQUIRED',$e->getMessage(),'accepted affected rights require replacement');}
    });
    $check('one corrected work changes only its recipients', static function () use ($builder, $owner, $id, $accepted, $neighbor, $op, &$activeOriginalId): void {
        $replacement = $owner->createReplacementDraft(73, $id, $accepted['revision'], $builder->buildReplacement(73, '2026-09-05', $id), 'Исправлены исполнители одной работы', $op());
        assertSameValue([7001=>5200000,7002=>7800000], rightsAmounts($replacement), '2% moves A to B; other 18% keeps its attribution');
        $replacement = $owner->accept(73, $replacement['calculationId'], $replacement['revision'], $op());
        $activeOriginalId=$replacement['calculationId'];
        assertSameValue('cancelled', $owner->read($id)['status'], 'successful complete replacement cancels original');
        assertSameValue($neighbor, $owner->read($neighbor['calculationId']), 'corrected replacement leaves accepted neighbor unchanged');
        assertSameValue([7001=>5200000,7002=>7800000], rightsAmounts($replacement), 'accepted obligations equal preview');
        $again = $builder->build(73, '2026-09-05');
        assertSameValue(0, array_sum(array_map(static fn($o)=>array_sum(array_column($o['entitlements'], 'grossCents')), $again['objects'])), 'correction and replacement mint no duplicate work');
    });
    $economy = new OtizPortfolioEconomy($yii, $p, $p);
    $check('unpaid obligations stay in payment queue', static function () use ($economy): void {
        assertSameValue(26000000, array_sum(array_column($economy->payableForObject(73, 4512), 'remainingCents')), 'original plus neighbor rights remain payable once');
    });
    $send(10,7002); // one percent performed while still employed
    $db->query("UPDATE {$p}fm2_workforce_catalog SET employment_status='dismissed',employed_to='2026-09-04',workforce_source_updated_at='2026-09-27T10:00:00+03:00' WHERE installer_tab_id=7002");
    $check('real workforce dismissal preserves earned rights and enables decision',static function()use($builder,$owner,$op):void{
        $input=$builder->build(73,'2026-09-05');
        $draft=$owner->createDraft(73,$input,$op());
        assertSameValue(['dismissed',650000],[$draft['recipients'][0]['employment'],$draft['recipients'][0]['amountCents']],'historically earned work defaults to payment with current dismissal warning');
    });
    $check('T03 replacement commits old plus unclaimed work without duplicate claim keys',static function()use($builder,$owner,$op,&$activeOriginalId,$neighbor):void{
        $old=$owner->read($activeOriginalId);
        $candidate=$owner->createReplacementDraft(73,$activeOriginalId,$old['revision'],$builder->buildReplacement(73,'2026-09-05',$activeOriginalId),'Добавлено отдельное новое выполнение',$op());
        $accepted=$owner->accept(73,$candidate['calculationId'],$candidate['revision'],$op());
        $activeOriginalId=$accepted['calculationId'];
        assertSameValue([7001=>5200000,7002=>8450000],rightsAmounts($accepted),'replacement adds only new one percent B to the correctly attributed original');
        assertSameValue($neighbor,$owner->read($neighbor['calculationId']),'committed replacement preserves neighboring accepted work');
        $next=$builder->build(73,'2026-09-05');
        assertSameValue(0,array_sum(array_map(static fn($o)=>array_sum(array_column($o['entitlements'],'grossCents')),$next['objects'])),'committed replacement leaves no duplicate new rights');
    });
    foreach([[15,84500000,[7001=>6760000,7002=>10985000]],[2,52000000,[7001=>4160000,7002=>6760000]]]as[$floors,$fund,$amounts]){
        $payload['fields']['floors']=['raw'=>(string)$floors,'display'=>(string)$floors];
        $encoded=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        $db->prepare("UPDATE {$p}fm2_pilot_object_details SET payload_json=?,content_sha256=? WHERE object_id=4512")->execute([$encoded,hash('sha256',$encoded)]);
        $check('T05 explicit normative correction '.$floors.' floors',static function()use($builder,$owner,$op,&$activeOriginalId,$neighbor,$fund,$amounts):void{
            $ordinary=$builder->build(73,'2026-09-05');
            assertSameValue($fund,$ordinary['objects'][0]['fundCents'],'real norm changes only through its proven technical source');
            assertSameValue(0,array_sum(array_map(static fn($o)=>array_sum(array_column($o['entitlements'],'grossCents')),$ordinary['objects'])),'norm change alone never creates new work in ordinary calculation');
            $old=$owner->read($activeOriginalId);$candidate=null;
            try{
                $candidate=$owner->createReplacementDraft(73,$activeOriginalId,$old['revision'],$builder->buildReplacement(73,'2026-09-05',$activeOriginalId),'Исправлена подтверждённая этажность',$op());
                assertSameValue($amounts,rightsAmounts($candidate),'replacement applies current norm to exactly its owned 21 percent');
                $candidate=$owner->accept(73,$candidate['calculationId'],$candidate['revision'],$op());$activeOriginalId=$candidate['calculationId'];
                assertSameValue($amounts,rightsAmounts($candidate),'full corrected basis can commit both higher and lower proven value');
                assertSameValue($neighbor,$owner->read($neighbor['calculationId']),'neighbor remains immutable under normative replacement');
            }finally{if($candidate!==null&&$candidate['status']==='draft')$owner->deleteDraft(73,$candidate['calculationId'],$candidate['revision'],'Окончание неуспешного опыта',$op());}
        });
    }
    $payload['fields']['floors']=['raw'=>'15','display'=>'15'];$payload['fields']['pitmaterial']=['raw'=>'86','display'=>'86'];
    $encoded=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    $db->prepare("UPDATE {$p}fm2_pilot_object_details SET payload_json=?,content_sha256=? WHERE object_id=4512")->execute([$encoded,hash('sha256',$encoded)]);
    $db->query("UPDATE {$p}fm_maintable SET plan_finish_date='2026-09-04' WHERE id=4512");
    $send(35,7001);
    $check('canonical half-cent deadline rounding',static function()use($builder,$owner,$op):void{
        $input=$builder->build(73,'2026-09-05');$draft=$owner->createDraft(73,$input,$op());$object=$draft['objects'][0];
        assertSameValue([97175000,9900,971750,9718,962032],[$object['fundCents'],$object['kssBp'],$object['grossCents'],$object['deadlineCents'],$draft['totalCents']],'canonical 845000 rubles x1.15 x1% with 1% penalty rounds 97.175 rubles to97.18');
    });
    $check('historical workbook basis is immutable after source and norm corrections',static function()use($http,$fixture,$neighbor,$savedIncrementBasis):void{
        assertSameValue(true,is_array($savedIncrementBasis),'earlier saved basis was independently captured');
        $response=$http->request('GET','/pilot/otiz/calculations/'.$neighbor['calculationId'].'/export.xlsx?mode=history',[],$fixture->cookies);
        assertSameValue(200,$response['status'],'historical basis survives new current norm');assertSameValue($savedIncrementBasis,rightsWorkbookBasis($response['body']),'historical export never rebuilds grounds from current facts');
    });
    $check('included native source issues survive real HTTP workbook export',static function()use($db,$p,$builder,$owner,$op,$http,$fixture):void{
        $db->query("UPDATE {$p}fm_maintable SET plan_finish_date=NULL WHERE id=4512");
        try{
            $input=$builder->build(73,'2026-09-05');$draft=$owner->createDraft(73,$input,$op());
            assertSameValue(true,in_array('DEADLINE_EVIDENCE_ABSENT',array_column($draft['objects'][0]['issues']??[],'code'),true),'saved included object retains real missing-date issue');
            $response=$http->request('GET','/pilot/otiz/calculations/'.$draft['calculationId'].'/export.xlsx?mode=draft',[],$fixture->cookies);
            assertSameValue(200,$response['status'],'blocked draft remains diagnostically exportable');$control=rightsWorkbookBasis($response['body'],'Контроль');$text=json_encode($control,JSON_UNESCAPED_UNICODE);
            assertSameValue(true,str_contains($text,'DEADLINE_EVIDENCE_ABSENT')&&str_contains($text,'TEST-4512'),'Control includes saved included-object issue and object identity');
            assertSameValue(1,count(array_filter($control,static fn($row)=>in_array('DEADLINE_EVIDENCE_ABSENT',$row,true))),'one saved object issue is exported exactly once');
        }finally{$db->query("UPDATE {$p}fm_maintable SET plan_finish_date='2026-09-04' WHERE id=4512");}
    });
    // Separate literal multi-object accounting oracle. The source chain above
    // covers builder attribution; here the exact 15,000/25,000 obligations matter.
    $admission = [4513=>'allow',4514=>'allow'];
    $multiOwner = new OtizSettlementV2($yii, $p, $clock, static function (int $object) use (&$admission): string { return $admission[$object] ?? 'allow'; });
    $multi = ['reportDate'=>'2026-09-04','objects'=>[]];
    foreach ([4513=>1500000,4514=>2500000] as $object=>$cents) {
        $db->query("INSERT INTO {$p}fm_maintable(id,ordadr_address,regnumber,plan_finish_date) VALUES($object,'Два объекта','RIGHTS-$object','2026-12-31')");
        $multi['objects'][] = ['objectId'=>$object,'fundCents'=>10000000,'kssBp'=>10000,
            'entitlements'=>[['sourceKind'=>'fixture','sourceId'=>'multi-'.$object,'sourceRevision'=>'1','kind'=>'progress','recognitionDate'=>'2026-09-03','grossCents'=>$cents]],
            'recipients'=>[['employeeId'=>'7001','tabNumber'=>'7001','name'=>'A','weight'=>1,'employment'=>'employed']]];
    }
    $md = $multiOwner->createDraft(73, $multi, $op());
    $ma = $multiOwner->accept(73, $md['calculationId'], $md['revision'], $op());
    $check('two-object economy separates object money from package', static function () use ($economy): void {
        $rows = $economy->read(73, ['year'=>'all','q'=>'RIGHTS-','pageSize'=>25])['rows'];
        $amounts = array_column($rows,'payableCents','objectId');
        assertSameValue([4513=>1500000,4514=>2500000], $amounts, 'each object shows only its own unpaid obligation');
        assertSameValue([1500000,4000000], array_values(array_intersect_key($economy->payableForObject(73,4513)[0],array_flip(['objectContributionCents','calculationRemainingCents']))), 'object contribution and calculation remaining are separately labeled');
    });
    $mr = $multiOwner->createReplacementDraft(73,$ma['calculationId'],$ma['revision'],$multi,'Полная замена',$op());
    $admission[4514] = 'blocked';
    $check('blocked replacement refresh cannot discard original object', static function () use ($multiOwner, $ma, $mr, $multi, $op): void {
        try {
            $refreshed = $multiOwner->refreshDraft(73,$mr['calculationId'],$mr['revision'],$multi,$op());
            $multiOwner->accept(73,$refreshed['calculationId'],$refreshed['revision'],$op());
            throw new TestFailure('partial replacement accepted and cancelled original');
        } catch (DomainException $error) {
            assertSameValue(true, in_array($error->getMessage(),['INCOMPLETE_REPLACEMENT','COMPOSITION_MISMATCH','ADMISSION_UNKNOWN'],true), 'specific complete-replacement refusal');
        }
        assertSameValue($ma,$multiOwner->read($ma['calculationId']),'blocked replacement leaves whole original accepted and payable');
    });
} finally {
    if ($yii instanceof yii\db\Connection) $yii->close();
    if ($fixture !== null) $fixture->close();
}
$check('three real recipients retain canonical contribution weights',static function():void{
    $f=new InspectionFixture(dirname(__DIR__,2));$connection=null;
    try{
        $h=$f->http;$db=$h->db;$p=$h->p;
        $third=$db->query("SELECT * FROM {$p}fm2_workforce_catalog WHERE installer_tab_id=7001")->fetch_assoc();$third['installer_tab_id']=800;$third['fio']='Третий монтажник';$h->insert($p.'fm2_workforce_catalog',$third);
        $payload=json_decode($db->query("SELECT payload_json FROM {$p}fm2_pilot_object_details WHERE object_id=4512")->fetch_column(),true,flags:JSON_THROW_ON_ERROR);$payload['fields']['pitmaterial']=['raw'=>'41','display'=>'41'];$payload['fields']['lift_type']=['raw'=>'1','display'=>'пассажирский'];$json=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$db->prepare("UPDATE {$p}fm2_pilot_object_details SET payload_json=?,content_sha256=? WHERE object_id=4512")->execute([$json,hash('sha256',$json)]);
        $f->open([7001,7002,800]);$operation=InspectionFixture::operation();$operation['deviceTime']='2026-09-03T10:00:00+03:00';$operation['installerTabIds']=[800,7002,7001];InspectionFixture::result($f->send($operation,InspectionFixture::csrf($f->page())),200,'accepted');
        $env=$h->environment();$connection=new yii\db\Connection(['dsn'=>'mysql:host='.$env['FMONITOR_DB_HOST'].';port='.$env['FMONITOR_DB_PORT'].';dbname='.$h->database,'username'=>$env['FMONITOR_DB_USER'],'password'=>$env['FMONITOR_DB_PASSWORD'],'charset'=>'utf8mb4']);$connection->open();$clock=static fn()=>'2026-09-28T12:00:00+03:00';$builder=new OtizSettlementV2DraftBuilder($connection,$p,$p,$clock);$owner=new OtizSettlementV2($connection,$p,$clock,static fn()=>'allow');$input=$builder->build(96,'2026-09-03');
        assertSameValue([7001=>67,7002=>67,800=>66],$input['objects'][0]['sourceEvidence']['progress']['contributions'],'canonical native per-item contribution split is independently observed');
        $draft=$owner->createDraft(96,$input,'cccccccc-aaaa-4ccc-8ccc-000000000001');
        assertSameValue([800=>429000,7001=>435500,7002=>435500],rightsAmounts($draft),'three-person KTU uses 67/67/66, never equal money split');
        $accepted=$owner->accept(96,$draft['calculationId'],$draft['revision'],'cccccccc-aaaa-4ccc-8ccc-000000000002');assertSameValue([800=>429000,7001=>435500,7002=>435500],rightsAmounts($accepted),'saved obligations preserve independently expected recipient cents');
        $stored=[];foreach($db->query("SELECT employee_id,amount_cents FROM {$p}fm2_otiz_recipient_obligations WHERE calculation_id=".(int)$accepted['calculationId'])->fetch_all(MYSQLI_ASSOC)as$row)$stored[(int)$row['employee_id']]=(int)$row['amount_cents'];ksort($stored);assertSameValue([800=>429000,7001=>435500,7002=>435500],$stored,'actual database obligations retain the canonical three-person cents');
        $cookies=[];$h->login($cookies,96);$response=$h->request('GET','/pilot/otiz/calculations/'.$accepted['calculationId'].'/export.xlsx?mode=history',[],$cookies);assertSameValue(200,$response['status'],'three-recipient saved export');$rows=rightsWorkbookBasis($response['body'],'Приложение к приказу');$amounts=[];foreach($rows as$row)$amounts[(int)$row[0]]=$row[2];ksort($amounts);assertSameValue([800=>'4290',7001=>'4355',7002=>'4355'],$amounts,'HTTP XLSX exact canonical three-person obligations');
    }finally{if($connection!==null)$connection->close();$f->close();}
});
$check('U02 documentary allocation basis participates in existing freshness',static function():void{
    $f=new InspectionFixture(dirname(__DIR__,2));$connection=null;
    try{
        $h=$f->http;$db=$h->db;$p=$h->p;
        foreach(['otiz.manage','installation.completion.pto.record']as$permission)$h->insert($p.'fm2_pilot_role_permissions',['role_id'=>7,'permission'=>$permission]);
        $payload=json_decode($db->query("SELECT payload_json FROM {$p}fm2_pilot_object_details WHERE object_id=4512")->fetch_column(),true,flags:JSON_THROW_ON_ERROR);$payload['fields']['pitmaterial']=['raw'=>'41','display'=>'41'];$payload['fields']['lift_type']=['raw'=>'1','display'=>'пассажирский'];$json=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$db->prepare("UPDATE {$p}fm2_pilot_object_details SET payload_json=?,content_sha256=? WHERE object_id=4512")->execute([$json,hash('sha256',$json)]);$db->query("UPDATE {$p}fm_maintable SET plan_finish_date='2026-10-01' WHERE id=4512");
        $foreign=$db->query("SELECT * FROM {$p}fm_maintable WHERE id=4512")->fetch_assoc();$foreign['id']=4513;$foreign['regnumber']='U02-FOREIGN';$h->insert($p.'fm_maintable',$foreign);
        $f->open();$csrf=InspectionFixture::csrf($f->page());$revision=0;$sequence=1;
        $sections=[[28,29,30,31,32,33,34,35,36],[37,38,39,40,41],[1,2,3,4,5,6],[7,8,9,10],[11,12,13,14,15],[16,17,18,19,20,21],[22,23,24,25,26,27]];$sectionByItem=[];foreach($sections as$s=>$items)foreach($items as$item)$sectionByItem[$item]=$s+1;
        $moved=[8,16,17,37,38,39,40,11,18,19]; // 34 of the canonical85 checklist percent
        $retained=[20,22,12,13,21,23,28,32]; //17; A51/B34 =60/40
        $send=static function(int$item,int$tab,string$type='item_completed',string$date='2026-09-03')use($f,$csrf,$sectionByItem,&$revision,&$sequence):void{$operation=InspectionFixture::operation($sequence++,$revision,$item);$operation['sectionId']=$sectionByItem[$item];$operation['installerTabIds']=[$tab];$operation['type']=$type;$operation['deviceTime']=$date.'T10:00:00+03:00';$result=InspectionFixture::result($f->send($operation,$csrf),200,'accepted');$revision=$result['revision'];};
        foreach($sectionByItem as$item=>$section)$send($item,in_array($item,[...$moved,...$retained],true)?7001:7002);
        $env=$h->environment();$connection=new yii\db\Connection(['dsn'=>'mysql:host='.$env['FMONITOR_DB_HOST'].';port='.$env['FMONITOR_DB_PORT'].';dbname='.$h->database,'username'=>$env['FMONITOR_DB_USER'],'password'=>$env['FMONITOR_DB_PASSWORD'],'charset'=>'utf8mb4']);$connection->open();$clock=static fn()=>'2026-09-28T12:00:00+03:00';$builder=new OtizSettlementV2DraftBuilder($connection,$p,$p,$clock);
        $db->query("INSERT INTO {$p}fm2_otiz_admission_events(object_id,source_revision,decision,reason_code,observed_at,incident_id) VALUES(4512,'u02-allow','allow',NULL,'2026-09-03T08:00:00+03:00',NULL)");$access=new FMonitor2\Otiz\MariaDbOtizSettlementV2Access($connection,$p);$owner=new OtizSettlementV2($connection,$p,$clock,static fn(int$o)=>$access->admission($o));$serial=1;$op=static function()use(&$serial):string{return sprintf('cccccccc-bbbb-4ccc-8ccc-%012d',$serial++);};
        $input=$builder->build(73,'2026-09-04');assertSameValue([7001=>5100,7002=>3400],$input['objects'][0]['sourceEvidence']['progress']['contributions'],'U02 real canonical work weights are60/40');$work=$owner->createDraft(73,$input,$op());$work=$owner->accept(73,$work['calculationId'],$work['revision'],$op());
        $pto=$h->form('/pilot/objects/4512/completion',['_csrf'=>$h->token($f->cookies),'action'=>'record_pto','ptoActDate'=>'2026-09-04'],$f->cookies);assertSameValue(303,$pto['status'],'U02 document recorded by real completion HTTP seam');$documentRows=$h->rows('fm2_pilot_completion_facts');
        $input=$builder->build(73,'2026-09-04');$document=$owner->createDraft(73,$input,$op());assertSameValue(['pto'],array_column($document['objects'][0]['entitlements'],'kind'),'U02 selected calculation is documentary-only, not checklist');assertSameValue([7001=>3900000,7002=>2600000],rightsAmounts($document),'U02 native65000-ruble documentary pool is60/40');
        $facts=static function()use($db,$p):string{$state=[];foreach(['calculation_revisions','entitlement_claims','recipient_obligations','payment_facts','payment_reversals','deductions','payment_decisions','v2_events','v2_operations']as$table)$state[$table]=$db->query("SELECT * FROM {$p}fm2_otiz_{$table}")->fetch_all(MYSQLI_ASSOC);return hash('sha256',json_encode($state,JSON_THROW_ON_ERROR));};
        foreach($moved as$item)$send($item,7002,'item_installers_changed');$fresh=$builder->build(73,'2026-09-04');$preview=$owner->createDraft(73,$fresh,$op());assertSameValue([7001=>1300000,7002=>5200000],rightsAmounts($preview),'U02 fresh builder observes20/80 after genuine attribution corrections');$owner->deleteDraft(73,$preview['calculationId'],$preview['revision'],'U02 независимый current oracle',$op());assertSameValue($documentRows,$h->rows('fm2_pilot_completion_facts'),'document itself unchanged');
        $before=$facts();$denial=null;try{$owner->accept(73,$document['calculationId'],$document['revision'],$op());}catch(DomainException$e){$denial=$e->getMessage();}assertSameValue('STALE_CALCULATION',$denial,'U02 changed used documentary contribution requires refresh despite unchanged document and allow');assertSameValue($before,$facts(),'U02 stale draft writes no financial facts');
        $document=$owner->refreshDraft(73,$document['calculationId'],$document['revision'],$fresh,$op());assertSameValue([7001=>1300000,7002=>5200000],rightsAmounts($document),'U02 refresh saves correct documentary recipients');$document=$owner->accept(73,$document['calculationId'],$document['revision'],$op());$url='/pilot/otiz/calculations/'.$document['calculationId'];$history=$h->request('GET',$url.'/export.xlsx?mode=history',[],$f->cookies);assertSameValue(200,$history['status'],'U02 saved history workbook');$savedRows=rightsWorkbookBasis($history['body'],'Приложение к приказу');$byPerson=[];foreach($savedRows as$row)$byPerson[(int)$row[0]]=$row[2];ksort($byPerson);assertSameValue([7001=>'13000',7002=>'52000'],$byPerson,'U02 saved documentary XLSX20/80 agrees with DB');
        $db->query("UPDATE {$p}fm_maintable SET ordadr_address='U02 чужое изменение',plan_finish_date='2030-01-01' WHERE id=4513");$send(20,7002,'item_installers_changed','2026-09-05');$before=$facts();$allowed=$h->request('GET',$url.'/export.xlsx?mode=payment',[],$f->cookies);assertSameValue(200,$allowed['status'],'U02 foreign object and attribution outside selected report cutoff do not invalidate');assertSameValue($before,$facts(),'U02 unrelated reads remain neutral');
        foreach($moved as$item)$send($item,7001,'item_installers_changed');$before=$facts();$denials=[];foreach(['export','payment']as$action){try{if($action==='export')$owner->assertPaymentExportAllowed(73,$document['calculationId']);else$owner->markPaid(73,$document['calculationId'],$document['revision'],'2026-09-26',$op());$denials[$action]=null;}catch(DomainException$e){$denials[$action]=$e->getMessage();}}assertSameValue(['export'=>'SNAPSHOT_REPLACEMENT_REQUIRED','payment'=>'SNAPSHOT_REPLACEMENT_REQUIRED'],$denials,'U02 accepted documentary basis uses the same freshness guard for export and payment');assertSameValue($before,$facts(),'U02 failed paid/export commands preserve accepted obligation and claims');
        $blocked=$h->request('GET',$url.'/export.xlsx?mode=payment',[],$f->cookies);assertSameValue(409,$blocked['status'],'U02 real HTTP payment export cannot bypass freshness');$blocked=$h->form($url.'/payments',['_csrf'=>$h->token($f->cookies),'expectedRevision'=>$document['revision'],'operationId'=>$op(),'paymentDate'=>'2026-09-26'],$f->cookies);assertSameValue(409,$blocked['status'],'U02 real HTTP mark-paid cannot bypass freshness');assertSameValue($before,$facts(),'U02 HTTP rejection preserves all financial facts');$historical=$h->request('GET',$url.'/export.xlsx?mode=history',[],$f->cookies);assertSameValue($savedRows,rightsWorkbookBasis($historical['body'],'Приложение к приказу'),'U02 historical export retains saved20/80 after sources return60/40');
        $replacement=$owner->createReplacementDraft(73,$document['calculationId'],$document['revision'],$builder->buildReplacement(73,'2026-09-04',$document['calculationId']),'U02 исправление доказанного вклада',$op());assertSameValue([7001=>3900000,7002=>2600000],rightsAmounts($replacement),'U02 proper replacement shows current60/40');$replacement=$owner->accept(73,$replacement['calculationId'],$replacement['revision'],$op());assertSameValue($work,$owner->read($work['calculationId']),'U02 neighboring approved checklist calculation never recalculated');$owner->assertPaymentExportAllowed(73,$replacement['calculationId']);
        $db->query("INSERT INTO {$p}fm2_otiz_admission_events(object_id,source_revision,decision,reason_code,observed_at,incident_id) VALUES(4512,'u02-blocked','blocked','COMPOSITION_MISMATCH','2026-09-28T13:00:00+03:00','u02-incident')");$before=$facts();try{$owner->assertPaymentExportAllowed(73,$replacement['calculationId']);throw new TestFailure('U02 fresh allocation bypassed257');}catch(DomainException$e){assertSameValue('COMPOSITION_MISMATCH',$e->getMessage(),'U02 freshness does not replace257 admission');}assertSameValue($before,$facts(),'U02 admission denial preserves obligations');
    }finally{if($connection!==null)$connection->close();$f->close();}
});
if ($failures !== []) { fwrite(STDERR, "REGRESSION_FAILURE\n".implode("\n", $failures)."\n"); exit(1); }
echo "settlement_v2_rights_flow_001_test: OK\n";

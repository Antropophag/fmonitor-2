<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/PreopeningFixture.php';

// YII2-ORIGINAL-OBJECT-IDENTITY-001: real HTTP/browser effective identity, access and immutable originals.
$f=null;$browser=null;
try {
    $f=new PreopeningFixture(dirname(__DIR__,2));
    assertSameValue('selected',$f->base->selection->app()->selectAssignmentOrderComposition(FMonitor2\Tests\Support\SelectionNativeFixture::command())->status()->value,'fixture owns selected composition');
    $p=$f->p;
    $f->db->query("UPDATE {$p}fm_maintable SET ordadr_address='Москва, Тестовая, 1',entrance='2',regnumber='TEST-4512',zavnumber='Z-77' WHERE id=4512");
    $f->start();$cookies=[];assertSameValue(303,$f->login($cookies)['status'],'authorized FKR login');
    $path='/pilot/objects/4512/assignment-orders/81/originals';

    $assertIdentity=static function(array$response,array$present,array$absent,string$label):void{
        assertSameValue(200,$response['status'],$label.' status');
        foreach($present as$value)assertSameValue(true,str_contains($response['body'],$value),$label.' contains '.$value);
        foreach($absent as$value)assertSameValue(false,str_contains($response['body'],$value),$label.' excludes '.$value);
    };
    $beforeInitial=$f->facts();$filesInitial=$f->base->privateFiles();
    $initial=$f->request('GET',$path.'/submit',[],$cookies);
    $assertIdentity($initial,['TEST-4512','Москва, Тестовая, 1','Подъезд 2','Заводской номер: Z-77','Распоряжение · версия 1'],['Объект № 4512','Объект монтажа № 4512'],'INTENDED_RED initial effective identity');
    assertSameValue([$beforeInitial,$filesInitial],[$f->facts(),$f->base->privateFiles()],'initial GET side-effect free');

    $runBrowser=static function(string$mode)use($f,&$browser):array{
        $config=$f->artifacts.'/original-identity-'.$mode.'.json';$result=$f->artifacts.'/original-identity-'.$mode.'-result.json';$log=$f->artifacts.'/original-identity-'.$mode.'.log';
        file_put_contents($config,json_encode(['mode'=>$mode,'origin'=>'http://127.0.0.1:'.$f->server['port'],'email'=>$f->emails[18],'password'=>$f->password,'artifacts'=>$f->artifacts,'result'=>$result,'playwright'=>getenv('FMONITOR_TEST_PLAYWRIGHT_MODULE')?:dirname($f->root).'/shlz-ui/node_modules/playwright'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));chmod($config,0600);
        $browser=proc_open([getenv('FMONITOR_TEST_NODE_BINARY')?:'node',__DIR__.'/original_object_identity_browser.mjs',$config],[0=>['file','/dev/null','r'],1=>['file',$log,'a'],2=>['file',$log,'a']],$pipes,$f->root);if(!is_resource($browser))throw new TestFailure('SETUP_FAILURE browser');
        $deadline=microtime(true)+60;do{$state=proc_get_status($browser);if(!$state['running'])break;usleep(20000);}while(microtime(true)<$deadline);if($state['running']){proc_terminate($browser,9);throw new TestFailure('browser timeout '.$mode.' '.$f->artifacts);}$exit=$state['exitcode'];proc_close($browser);$browser=null;
        assertSameValue(0,$exit,'INTENDED_RED browser '.$mode.' '.file_get_contents($log));return json_decode((string)file_get_contents($result),true,flags:JSON_THROW_ON_ERROR);
    };
    $initialBrowser=$runBrowser('initial');assertSameValue(['initial',true,true],[$initialBrowser['mode'],$initialBrowser['desktop'],$initialBrowser['narrow']],'initial browser viewports');

    $pdf=FMonitor2\Tests\Support\SelectedOriginalFixture::pdf();$meta=$f->metadata($cookies);$accepted=$f->upload($cookies,$meta,$pdf);assertSameValue(201,$accepted['status'],'initial upload preserved');$receipt=json_decode($accepted['body'],true,flags:JSON_THROW_ON_ERROR);
    $rootRows=$f->rows('fm2_assignment_order_original_revisions');$rootFiles=$f->base->privateFiles();assertSameValue([1,hash('sha256',$pdf)],[count($rootRows),$rootRows[0]['pdf_sha256']],'accepted root evidence');

    $manual=['regnumber'=>'РЕГ-&-НОВЫЙ','address'=>'Очень длинный <script>alert(1)</script> & адрес объекта монтажа для проверки безопасного переноса на очень узком экране','entrance'=>'12А','zavnumber'=>'ЗАВ-<42>'];
    $statement=$f->db->prepare("INSERT INTO {$p}fm2_object_detail_edits(object_id,revision,values_json,updated_at_utc,updated_by_user_id)VALUES(4512,1,?,'2026-09-24 02:00:00',18)");$statement->execute([json_encode($manual,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
    $correction=$f->request('GET',$path.'/submit',[],$cookies);$history=$f->request('GET',$path.'/history',[],$cookies);
    $expectedEscaped=['РЕГ-&amp;-НОВЫЙ','Очень длинный &lt;script&gt;alert(1)&lt;/script&gt; &amp; адрес','Подъезд 12А','Заводской номер: ЗАВ-&lt;42&gt;'];
    foreach(['correction'=>$correction,'history'=>$history]as$surface=>$response)$assertIdentity($response,$expectedEscaped,['TEST-4512','Москва, Тестовая, 1','Объект № 4512','Объект монтажа № 4512'],$surface.' manual effective identity');
    foreach([$correction,$history]as$response){assertSameValue(false,str_contains($response['body'],'<script>alert(1)</script>'),'hostile value never emitted as markup');}
    assertSameValue([$rootRows,$rootFiles],[$f->rows('fm2_assignment_order_original_revisions'),$f->base->privateFiles()],'manual edit and GETs preserve original rows/files');
    $manualBrowser=$runBrowser('manual');assertSameValue(['manual',true,true,false],[$manualBrowser['mode'],$manualBrowser['desktop'],$manualBrowser['narrow'],$manualBrowser['hostileElement']],'manual browser safe responsive text');

    $missing=['regnumber'=>null,'address'=>'Адрес без номера','entrance'=>'7','zavnumber'=>'   '];$statement=$f->db->prepare("UPDATE {$p}fm2_object_detail_edits SET revision=2,values_json=?,updated_at_utc='2026-09-24 02:01:00' WHERE object_id=4512");$statement->execute([json_encode($missing,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
    foreach(['form'=>$path.'/submit','history'=>$path.'/history']as$surface=>$url){$response=$f->request('GET',$url,[],$cookies);$assertIdentity($response,['Регистрационный номер не указан','Адрес без номера','Подъезд 7','Заводской номер не указан'],['Объект № 4512','Объект монтажа № 4512'],$surface.' missing identifiers');}
    $missingBrowser=$runBrowser('missing');assertSameValue(['missing',true,true],[$missingBrowser['mode'],$missingBrowser['desktop'],$missingBrowser['narrow']],'missing browser viewports');

    $correctionMeta=array_replace($f->metadata($cookies,'22222222-2222-4222-8222-000000000002'),['mode'=>'correction','documentDate'=>'2026-09-02','rootOriginalId'=>$receipt['rootOriginalId'],'targetRevisionId'=>$receipt['currentRevisionId'],'expectedCurrentRevisionId'=>$receipt['currentRevisionId'],'correctionReason'=>'Уточнены дата и файл']);
    $pdf2=FMonitor2\Tests\Support\AssignmentOrderOriginalPdfCorpus::classic(['<< /Type /Catalog /Pages 2 0 R >>','<< /Type /Pages /Kids [3 0 R] /Count 1 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 144 144] >>']);$corrected=$f->upload($cookies,$correctionMeta,$pdf2);assertSameValue(201,$corrected['status'],'correction remains available with missing display numbers');
    $rows=$f->rows('fm2_assignment_order_original_revisions');assertSameValue([2,$rootRows[0],['2026-09-01','2026-09-02'],[hash('sha256',$pdf),hash('sha256',$pdf2)]],[count($rows),$rows[0],array_column($rows,'document_date'),array_column($rows,'pdf_sha256')],'correction appends and preserves root/date/hash');
    $replay=$f->upload($cookies,$correctionMeta,$pdf2);assertSameValue([200,'replayed',2],[$replay['status'],json_decode($replay['body'],true,flags:JSON_THROW_ON_ERROR)['status'],count($f->rows('fm2_assignment_order_original_revisions'))],'safe replay adds no revision');

    $guest=[];$guestResponse=$f->request('GET',$path.'/submit',[],$guest);assertSameValue(303,$guestResponse['status'],'guest keeps login redirect');
    $denied=[];$f->login($denied,95);foreach([$path.'/submit',$path.'/history']as$url){$response=$f->request('GET',$url,[],$denied);assertSameValue(403,$response['status'],'no-read actor denied '.$url);foreach(['Адрес без номера','Регистрационный номер не указан','TEST-4512']as$secret)assertSameValue(false,str_contains($response['body'],$secret),'denied response hides '.$secret);}
    assertSameValue(404,$f->request('GET','/pilot/objects/9999/assignment-orders/81/originals/submit',[],$cookies)['status'],'unavailable object remains not found');
    foreach($rows as$row){$download=$f->request('GET',$path.'/'.$row['revision_id'].'/download',[],$cookies);assertSameValue([200,$row['pdf_sha256']],[ $download['status'],hash('sha256',$download['body'])],'exact historical download '.$row['revision_number']);}
    $f->noLegacy();echo 'PASS: YII2-ORIGINAL-OBJECT-IDENTITY-001 HTTP/browser; artifacts '.$f->artifacts."\n";
} finally {
    if(is_resource($browser)){proc_terminate($browser,9);proc_close($browser);}
    if($f instanceof PreopeningFixture)$f->close();
}

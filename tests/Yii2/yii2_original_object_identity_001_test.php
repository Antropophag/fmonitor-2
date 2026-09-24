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
    $effectiveProbe=$f->artifacts.'/effective-read-probe.jsonl';
    $router='<?php require '.var_export($f->root.'/vendor/autoload.php',true).';require '.var_export($f->root.'/vendor/yiisoft/yii2/Yii.php',true).';class OriginalIdentityCountingCommand extends yii\\db\\Command{public static int $effectiveReads=0;protected function queryInternal($method,$fetchMode=null){$sql=$this->getRawSql();if(str_contains($sql,"SELECT ordadr_address,entrance,regnumber,zavnumber FROM")||str_contains($sql,"SELECT revision,values_json FROM"))self::$effectiveReads++;return parent::queryInternal($method,$fetchMode);}} register_shutdown_function(static function(){file_put_contents('.var_export($effectiveProbe,true).',json_encode(["method"=>$_SERVER["REQUEST_METHOD"],"uri"=>$_SERVER["REQUEST_URI"],"status"=>http_response_code(),"effectiveReads"=>OriginalIdentityCountingCommand::$effectiveReads])."\\n",FILE_APPEND|LOCK_EX);});$config=require '.var_export($f->root.'/config/yii/web.php',true).';$factory=$config["components"]["db"];$config["components"]["db"]=static function()use($factory){$db=$factory();$db->commandClass=OriginalIdentityCountingCommand::class;return$db;};(new yii\\web\\Application($config))->run();';
    $f->start([],$router);$cookies=[];assertSameValue(303,$f->login($cookies)['status'],'authorized FKR login');
    $path='/pilot/objects/4512/assignment-orders/81/originals';

    $assertIdentity=static function(array$response,array$present,array$absent,string$label):void{
        assertSameValue(200,$response['status'],$label.' status');
        foreach($present as$value)assertSameValue(true,str_contains($response['body'],$value),$label.' contains '.$value);
        foreach($absent as$value)assertSameValue(false,str_contains($response['body'],$value),$label.' excludes '.$value);
    };
    $fragment=static function(string$html,string$pattern,string$label):string{assertSameValue(1,preg_match($pattern,$html,$match),$label.' fragment');return$match[0];};
    $beforeInitial=$f->facts();$filesInitial=$f->base->privateFiles();
    $initial=$f->request('GET',$path.'/submit',[],$cookies);
    $assertIdentity($initial,['TEST-4512','Москва, Тестовая, 1','Подъезд 2','Заводской номер: Z-77','Распоряжение · версия 1'],['Объект № 4512','Объект монтажа № 4512'],'INTENDED_RED initial effective identity');
    $initialBreadcrumb=$fragment($initial['body'],'#<nav class="fm2-breadcrumb".*?</nav>#s','initial breadcrumb');$initialPrimary=$fragment($initial['body'],'#<div class="fm2-order-object".*?</div>#s','initial primary identity');foreach(['TEST-4512','Москва, Тестовая, 1']as$value)assertSameValue(true,str_contains($initialBreadcrumb,$value),'initial breadcrumb scoped '.$value);foreach(['TEST-4512','Москва, Тестовая, 1','Подъезд 2','Заводской номер: Z-77']as$value)assertSameValue(true,str_contains($initialPrimary,$value),'initial primary scoped '.$value);
    assertSameValue([$beforeInitial,$filesInitial],[$f->facts(),$f->base->privateFiles()],'initial GET side-effect free');

    $runBrowser=static function(string$mode,string$surface)use($f,&$browser):array{
        $key=$mode.'-'.$surface;$config=$f->artifacts.'/original-identity-'.$key.'.json';$result=$f->artifacts.'/original-identity-'.$key.'-result.json';$log=$f->artifacts.'/original-identity-'.$key.'.log';
        file_put_contents($config,json_encode(['mode'=>$mode,'surface'=>$surface,'origin'=>'http://127.0.0.1:'.$f->server['port'],'email'=>$f->emails[18],'password'=>$f->password,'artifacts'=>$f->artifacts,'result'=>$result,'playwright'=>getenv('FMONITOR_TEST_PLAYWRIGHT_MODULE')?:dirname($f->root).'/shlz-ui/node_modules/playwright'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));chmod($config,0600);
        $browser=proc_open([getenv('FMONITOR_TEST_NODE_BINARY')?:'node',__DIR__.'/original_object_identity_browser.mjs',$config],[0=>['file','/dev/null','r'],1=>['file',$log,'a'],2=>['file',$log,'a']],$pipes,$f->root);if(!is_resource($browser))throw new TestFailure('SETUP_FAILURE browser');
        $deadline=microtime(true)+60;do{$state=proc_get_status($browser);if(!$state['running'])break;usleep(20000);}while(microtime(true)<$deadline);if($state['running']){proc_terminate($browser,9);throw new TestFailure('browser timeout '.$mode.' '.$f->artifacts);}$exit=$state['exitcode'];proc_close($browser);$browser=null;
        assertSameValue(0,$exit,'INTENDED_RED browser '.$key.' '.file_get_contents($log));return json_decode((string)file_get_contents($result),true,flags:JSON_THROW_ON_ERROR);
    };
    $initialBrowser=$runBrowser('initial','form');assertSameValue(['initial','form',true,true],[$initialBrowser['mode'],$initialBrowser['surface'],$initialBrowser['desktop'],$initialBrowser['narrow']],'initial browser viewports');

    $pdf=FMonitor2\Tests\Support\SelectedOriginalFixture::pdf();$meta=$f->metadata($cookies);$accepted=$f->upload($cookies,$meta,$pdf);assertSameValue(201,$accepted['status'],'initial upload preserved');$receipt=json_decode($accepted['body'],true,flags:JSON_THROW_ON_ERROR);
    $rootRows=$f->rows('fm2_assignment_order_original_revisions');$rootFiles=$f->base->privateFiles();assertSameValue([1,hash('sha256',$pdf)],[count($rootRows),$rootRows[0]['pdf_sha256']],'accepted root evidence');

    $manual=['regnumber'=>'РЕГ-&-НОВЫЙ','address'=>'Очень длинный <script>alert(1)</script> & адрес объекта монтажа для проверки безопасного переноса на очень узком экране','entrance'=>'12А','zavnumber'=>'ЗАВ-<42>'];
    $statement=$f->db->prepare("INSERT INTO {$p}fm2_object_detail_edits(object_id,revision,values_json,updated_at_utc,updated_by_user_id)VALUES(4512,1,?,'2026-09-24 02:00:00',18)");$statement->execute([json_encode($manual,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
    $beforeManualReads=$f->facts();$beforeManualRows=$f->rows('fm2_assignment_order_original_revisions');$beforeManualFiles=$f->base->privateFiles();$beforeManualDownload=$f->request('GET',$path.'/'.$receipt['currentRevisionId'].'/download',[],$cookies);assertSameValue([200,hash('sha256',$pdf)],[ $beforeManualDownload['status'],hash('sha256',$beforeManualDownload['body'])],'pre-GET exact historical bytes');$beforeManualReads=$f->facts();
    $correction=$f->request('GET',$path.'/submit',[],$cookies);$history=$f->request('GET',$path.'/history',[],$cookies);
    $expectedEscaped=['РЕГ-&amp;-НОВЫЙ','Очень длинный &lt;script&gt;alert(1)&lt;/script&gt; &amp; адрес','Подъезд 12А','Заводской номер: ЗАВ-&lt;42&gt;'];
    foreach(['correction'=>$correction,'history'=>$history]as$surface=>$response)$assertIdentity($response,$expectedEscaped,['TEST-4512','Москва, Тестовая, 1','Объект № 4512','Объект монтажа № 4512'],$surface.' manual effective identity');
    $correctionBreadcrumb=$fragment($correction['body'],'#<nav class="fm2-breadcrumb".*?</nav>#s','correction breadcrumb');$correctionPrimary=$fragment($correction['body'],'#<div class="fm2-order-object".*?</div>#s','correction primary identity');foreach(['РЕГ-&amp;-НОВЫЙ','Очень длинный &lt;script&gt;']as$value)assertSameValue(true,str_contains($correctionBreadcrumb,$value),'correction breadcrumb scoped '.$value);foreach($expectedEscaped as$value)assertSameValue(true,str_contains($correctionPrimary,$value),'correction primary scoped '.$value);
    foreach([$correction,$history]as$response){assertSameValue(false,str_contains($response['body'],'<script>alert(1)</script>'),'hostile value never emitted as markup');}
    $afterManualDownload=$f->request('GET',$path.'/'.$receipt['currentRevisionId'].'/download',[],$cookies);assertSameValue($beforeManualDownload['body'],$afterManualDownload['body'],'historical bytes identical after correction/history GET');assertSameValue([$beforeManualReads,$beforeManualRows,$beforeManualFiles],[$f->facts(),$f->rows('fm2_assignment_order_original_revisions'),$f->base->privateFiles()],'correction/history/download GETs preserve all facts, rows and files');
    foreach(['form','history']as$surface){$manualBrowser=$runBrowser('manual',$surface);assertSameValue(['manual',$surface,true,true,false],[$manualBrowser['mode'],$manualBrowser['surface'],$manualBrowser['desktop'],$manualBrowser['narrow'],$manualBrowser['hostileElement']],'manual browser safe responsive '.$surface);}

    $missing=['regnumber'=>null,'address'=>'Адрес без номера','entrance'=>'7','zavnumber'=>'   '];$statement=$f->db->prepare("UPDATE {$p}fm2_object_detail_edits SET revision=2,values_json=?,updated_at_utc='2026-09-24 02:01:00' WHERE object_id=4512");$statement->execute([json_encode($missing,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
    foreach(['form'=>$path.'/submit','history'=>$path.'/history']as$surface=>$url){$response=$f->request('GET',$url,[],$cookies);$assertIdentity($response,['Регистрационный номер не указан','Адрес без номера','Подъезд 7','Заводской номер не указан'],['Объект № 4512','Объект монтажа № 4512'],$surface.' missing identifiers');}
    foreach(['form','history']as$surface){$missingBrowser=$runBrowser('missing',$surface);assertSameValue(['missing',$surface,true,true],[$missingBrowser['mode'],$missingBrowser['surface'],$missingBrowser['desktop'],$missingBrowser['narrow']],'missing browser '.$surface.' viewports');}

    $correctionMeta=array_replace($f->metadata($cookies,'22222222-2222-4222-8222-000000000002'),['mode'=>'correction','documentDate'=>'2026-09-02','rootOriginalId'=>$receipt['rootOriginalId'],'targetRevisionId'=>$receipt['currentRevisionId'],'expectedCurrentRevisionId'=>$receipt['currentRevisionId'],'correctionReason'=>'Уточнены дата и файл']);
    $pdf2=FMonitor2\Tests\Support\AssignmentOrderOriginalPdfCorpus::classic(['<< /Type /Catalog /Pages 2 0 R >>','<< /Type /Pages /Kids [3 0 R] /Count 1 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 144 144] >>']);$corrected=$f->upload($cookies,$correctionMeta,$pdf2);assertSameValue(201,$corrected['status'],'correction remains available with missing display numbers');
    $rows=$f->rows('fm2_assignment_order_original_revisions');assertSameValue([2,$rootRows[0],['2026-09-01','2026-09-02'],[hash('sha256',$pdf),hash('sha256',$pdf2)]],[count($rows),$rows[0],array_column($rows,'document_date'),array_column($rows,'pdf_sha256')],'correction appends and preserves root/date/hash');
    $replay=$f->upload($cookies,$correctionMeta,$pdf2);assertSameValue([200,'replayed',2],[$replay['status'],json_decode($replay['body'],true,flags:JSON_THROW_ON_ERROR)['status'],count($f->rows('fm2_assignment_order_original_revisions'))],'safe replay adds no revision');

    $probeOffset=is_file($effectiveProbe)?count(file($effectiveProbe,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)):0;
    $guest=[];$guestResponse=$f->request('GET',$path.'/submit',[],$guest);assertSameValue(303,$guestResponse['status'],'guest keeps login redirect');
    $denied=[];$f->login($denied,95);foreach([$path.'/submit',$path.'/history']as$url){$response=$f->request('GET',$url,[],$denied);assertSameValue(403,$response['status'],'no-read actor denied '.$url);foreach(['Адрес без номера','Регистрационный номер не указан','TEST-4512']as$secret)assertSameValue(false,str_contains($response['body'],$secret),'denied response hides '.$secret);}
    foreach(['/pilot/objects/9999/assignment-orders/81/originals/submit','/pilot/objects/4512/assignment-orders/9999/originals/submit','/pilot/objects/9999/assignment-orders/81/originals/history','/pilot/objects/4512/assignment-orders/9999/originals/history']as$url){$response=$f->request('GET',$url,[],$cookies);assertSameValue(true,in_array($response['status'],[403,404],true),'unavailable object/order safe '.$url);}
    assertSameValue(200,$f->request('GET',$path.'/submit',[],$cookies)['status'],'authorized effective-read control');$probes=array_map(static fn(string$line):array=>json_decode($line,true,flags:JSON_THROW_ON_ERROR),array_slice(file($effectiveProbe,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES),$probeOffset));$securityUris=array_merge([$path.'/submit',$path.'/history'],['/pilot/objects/9999/assignment-orders/81/originals/submit','/pilot/objects/4512/assignment-orders/9999/originals/submit','/pilot/objects/9999/assignment-orders/81/originals/history','/pilot/objects/4512/assignment-orders/9999/originals/history']);foreach($probes as$probe)if(in_array($probe['uri'],$securityUris,true)&&in_array($probe['status'],[303,403,404],true))assertSameValue(0,$probe['effectiveReads'],'admission precedes effective read '.$probe['status'].' '.$probe['uri']);$authorizedProbes=array_values(array_filter($probes,static fn(array$probe):bool=>$probe['status']===200&&$probe['uri']===$path.'/submit'));assertSameValue(true,$authorizedProbes!==[]&&end($authorizedProbes)['effectiveReads']>0,'effective-read probe observes authorized control');
    foreach($rows as$row){$download=$f->request('GET',$path.'/'.$row['revision_id'].'/download',[],$cookies);assertSameValue([200,$row['pdf_sha256']],[ $download['status'],hash('sha256',$download['body'])],'exact historical download '.$row['revision_number']);}
    $f->noLegacy();echo 'PASS: YII2-ORIGINAL-OBJECT-IDENTITY-001 HTTP/browser; artifacts '.$f->artifacts."\n";
} finally {
    if(is_resource($browser)){proc_terminate($browser,9);proc_close($browser);}
    if($f instanceof PreopeningFixture)$f->close();
}

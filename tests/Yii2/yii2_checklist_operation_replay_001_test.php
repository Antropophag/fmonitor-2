<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/InspectionFixture.php';

// CHECKLIST-OPERATION-REPLAY-001 A-G. Commands cross the public Yii2 HTTP seam;
// SQL is limited to isolated fixture scheduling and independent persistence audit.
function corFiles(string $root):array
{
    $out=[];
    if(!is_dir($root))return$out;
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS))as$file)if($file->isFile())$out[substr($file->getPathname(),strlen($root))]=hash_file('sha256',$file->getPathname());
    ksort($out);return$out;
}

function corSnapshot(PreopeningFixture $h):array{return[$h->facts(),corFiles($h->base->privateRoot)];}

function corUnrelatedFacts(array $snapshot,string $prefix):array
{
    foreach(['fm2_checklist_operations','fm2_checklist_revisions','fm2_checklist_photos']as$table)unset($snapshot[0][$prefix.$table]);
    return$snapshot[0];
}

function corPost(InspectionFixture $f,int $object,array $operation,string $csrf,array &$cookies,?string $bytes=null):array
{
    $h=$f->http;$raw=$bytes??json_encode($operation,JSON_THROW_ON_ERROR);
    $headers=['X-FM2-CSRF: '.$csrf,'Origin: http://127.0.0.1:'.$h->server['port'],'Sec-Fetch-Site: same-origin'];
    if($bytes===null)$headers[]='Content-Type: application/json; charset=UTF-8';
    else{$headers[]='Content-Type: '.$operation['mime'];$headers[]='X-FM2-Operation: '.base64_encode(json_encode($operation,JSON_THROW_ON_ERROR));}
    return$h->request('POST',"/pilot/objects/$object/checklist/".($bytes===null?'operations':'photos'),[],$cookies,$headers,$raw);
}

function corAccepted(InspectionFixture $f,array $operation,string $csrf,int $revision,?string $bytes=null):array
{
    $result=InspectionFixture::result(corPost($f,4512,$operation,$csrf,$f->cookies,$bytes),200,'accepted');
    assertSameValue($revision,$result['revision'],'healthy prerequisite accepted revision');return$result;
}

function corOutcome(array &$failures,string $label,array $response,int $http,string $status,array $before,PreopeningFixture $h):void
{
    $body=json_decode($response['body'],true,flags:JSON_THROW_ON_ERROR);
    if($response['status']!==$http||($body['status']??null)!==$status)$failures[]="$label expected HTTP $http $status, got HTTP {$response['status']} ".($body['status']??'missing');
    if(corSnapshot($h)!==$before)$failures[]="$label changed persistence/private files";
}

function corReplay(InspectionFixture $f,array $operation,string $csrf,?string $bytes=null):void
{
    $before=corSnapshot($f->http);$response=corPost($f,4512,$operation,$csrf,$f->cookies,$bytes);
    $rows=array_values(array_filter($f->http->rows('fm2_checklist_operations'),static fn($row)=>$row['client_operation_id']===$operation['clientOperationId']));assertSameValue(1,count($rows),'accepted replay source exists once');
    $result=InspectionFixture::result($response,200,'duplicate');assertSameValue((int)$rows[0]['accepted_revision'],$result['revision'],'duplicate returns original accepted revision');assertSameValue($before,corSnapshot($f->http),'exact replay has zero writes');
}

function corRace(InspectionFixture $f,array $left,array $right,string $csrf,?string $leftBytes,?string $rightBytes):array
{
    $h=$f->http;$trigger=$h->p.'checklist_replay_race';
    $h->db->query("CREATE TRIGGER `$trigger` BEFORE INSERT ON `{$h->p}fm2_checklist_photos` FOR EACH ROW DO SLEEP(0.5)");
    $barrier=$h->artifacts.'/race-go-'.bin2hex(random_bytes(4));$results=[];$children=[];$env=$h->environment();
    $cookie=implode('; ',array_map(static fn($key,$value)=>$key.'='.$value,array_keys($f->cookies),$f->cookies));
    $config=$h->artifacts.'/race-config.json';file_put_contents($config,json_encode(['port'=>$h->server['port'],'cookie'=>$cookie,'csrf'=>$csrf],JSON_THROW_ON_ERROR));
    try{
        foreach([[$left,$leftBytes],[$right,$rightBytes]]as$index=>[$command,$bytes]){
            $path=$h->artifacts.'/race-result-'.$index.'-'.bin2hex(random_bytes(3)).'.json';$commandPath=$h->artifacts.'/race-command-'.$index.'-'.bin2hex(random_bytes(3)).'.json';file_put_contents($commandPath,json_encode($command,JSON_THROW_ON_ERROR));$bytesPath='';if($bytes!==null){$bytesPath=$h->artifacts.'/race-bytes-'.$index.'-'.bin2hex(random_bytes(3));file_put_contents($bytesPath,$bytes);}
            $pipes=[];$process=proc_open([PHP_BINARY,__DIR__.'/support/checklist_operation_replay_race_worker.php',$config,$path,$barrier,$commandPath,$bytesPath],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$h->root);if(!is_resource($process))throw new TestFailure('SETUP_FAILURE race worker');$children[]=[$process,$pipes,$path];
        }
        touch($barrier);
        foreach($children as[$process,$pipes,$path]){$stdout=stream_get_contents($pipes[1]);$stderr=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($process);$decoded=json_decode((string)file_get_contents($path),true,flags:JSON_THROW_ON_ERROR);if($exit!==0||!($decoded['ok']??false))throw new TestFailure('race worker: '.($decoded['error']??trim($stderr.$stdout)));$results[]=$decoded;}
    }finally{$h->db->query("DROP TRIGGER IF EXISTS `$trigger`");}
    return$results;
}

$f=null;$failures=[];
try{
    $f=new InspectionFixture(dirname(__DIR__,2));$f->open();$h=$f->http;$csrf=InspectionFixture::csrf($f->page());
    $item=InspectionFixture::operation(1,0,28);corAccepted($f,$item,$csrf,1);corReplay($f,$item,$csrf);
    $itemConflict=array_replace($item,['itemId'=>29]);$before=corSnapshot($h);corOutcome($failures,'item_completed existing conflict',$f->send($itemConflict,$csrf),409,'conflict',$before,$h);

    $changed=array_replace(InspectionFixture::operation(2,1,28),['type'=>'item_installers_changed','installerTabIds'=>[7002,7001]]);
    corAccepted($f,$changed,$csrf,2);$same=$changed;$same['installerTabIds']=[7001,7002];$same=array_reverse($same,true);corReplay($f,$same,$csrf);
    $before=corSnapshot($h);corOutcome($failures,'duplicate installer identities remain rejected',corPost($f,4512,array_replace($changed,['installerTabIds'=>[7001,7001,7002]]),$csrf,$f->cookies),422,'rejected',$before,$h);
    foreach([
        'different type'=>array_replace($changed,['type'=>'section_completed']),
        'different device'=>array_replace($changed,['deviceInstallationId'=>'cccccccc-cccc-4ccc-8ccc-cccccccccccc']),
        'different installers'=>array_replace($changed,['installerTabIds'=>[7001]]),
        'different section'=>array_replace($changed,['sectionId'=>2]),
        'different item'=>array_replace($changed,['itemId'=>29]),
    ]as$label=>$attempt){$before=corSnapshot($h);corOutcome($failures,$label,corPost($f,4512,$attempt,$csrf,$f->cookies),409,'conflict',$before,$h);}

    $retract=array_replace(InspectionFixture::operation(3,2,28),['type'=>'completion_retracted','originalClientOperationId'=>$item['clientOperationId'],'reason'=>'Ошибка отметки']);
    corAccepted($f,$retract,$csrf,3);$same=array_replace($retract,['reason'=>'  Ошибка отметки  ']);corReplay($f,$same,$csrf);
    foreach(['different original'=>array_replace($retract,['originalClientOperationId'=>InspectionFixture::operation(99)['clientOperationId']]),'different retract reason'=>array_replace($retract,['reason'=>'Другая причина'])]as$label=>$attempt){$before=corSnapshot($h);corOutcome($failures,$label,corPost($f,4512,$attempt,$csrf,$f->cookies),409,'conflict',$before,$h);}

    $revision=3;corAccepted($f,InspectionFixture::operation(4,$revision++,28),$csrf,$revision);
    foreach(range(29,36)as$itemId)corAccepted($f,InspectionFixture::operation(10+$itemId,$revision++,$itemId),$csrf,$revision);
    $png=InspectionFixture::png(10);$photo=array_replace(InspectionFixture::operation(60,$revision),['type'=>'photo_uploaded','sha256'=>hash('sha256',$png),'mime'=>'image/png','size'=>strlen($png),'originalName'=>'replay.png']);unset($photo['itemId'],$photo['installerTabIds']);corAccepted($f,$photo,$csrf,++$revision,$png);$same=array_reverse($photo,true);corReplay($f,$same,$csrf,$png);
    $before=corSnapshot($h);corOutcome($failures,'empty photo replay bytes',corPost($f,4512,$photo,$csrf,$f->cookies,''),413,'rejected',$before,$h);
    $before=corSnapshot($h);corOutcome($failures,'different photo replay bytes',corPost($f,4512,$photo,$csrf,$f->cookies,InspectionFixture::png(12)),422,'rejected',$before,$h);
    foreach([
        'different photo hash'=>array_replace($photo,['sha256'=>str_repeat('0',64)]),
        'different photo mime'=>array_replace($photo,['mime'=>'image/jpeg']),
        'different photo size'=>array_replace($photo,['size'=>strlen($png)+1]),
        'different photo name'=>array_replace($photo,['originalName'=>'other.png']),
    ]as$label=>$attempt){$before=corSnapshot($h);corOutcome($failures,$label,corPost($f,4512,$attempt,$csrf,$f->cookies,$png),409,'conflict',$before,$h);}

    $png2=InspectionFixture::png(11);$photo2=array_replace($photo,['clientOperationId'=>InspectionFixture::operation(61)['clientOperationId'],'baseRevision'=>$revision,'sha256'=>hash('sha256',$png2),'size'=>strlen($png2),'originalName'=>'second.png']);corAccepted($f,$photo2,$csrf,++$revision,$png2);
    $section=array_replace(InspectionFixture::operation(62,$revision),['type'=>'section_completed']);unset($section['itemId'],$section['installerTabIds']);corAccepted($f,$section,$csrf,++$revision);corReplay($f,array_reverse($section,true),$csrf);
    $before=corSnapshot($h);corOutcome($failures,'different completed section',corPost($f,4512,array_replace($section,['sectionId'=>2]),$csrf,$f->cookies),409,'conflict',$before,$h);

    $photoId=(int)$h->rows('fm2_checklist_photos')[0]['id'];$revoke=array_replace(InspectionFixture::operation(63,$revision),['type'=>'photo_revoked','photoId'=>$photoId,'reason'=>'Заменить снимок']);unset($revoke['itemId'],$revoke['installerTabIds']);corAccepted($f,$revoke,$csrf,++$revision);corReplay($f,array_replace($revoke,['reason'=>'  Заменить снимок ']),$csrf);
    foreach(['different revoked photo'=>array_replace($revoke,['photoId'=>$photoId+1]),'different revoke reason'=>array_replace($revoke,['reason'=>'Иная причина'])]as$label=>$attempt){$before=corSnapshot($h);corOutcome($failures,$label,corPost($f,4512,$attempt,$csrf,$f->cookies),409,'conflict',$before,$h);}

    $f->queueFixtures();$before=corSnapshot($h);corOutcome($failures,'different object',corPost($f,4513,$changed,$csrf,$f->cookies),409,'conflict',$before,$h);

    $manager=[];assertSameValue(303,$h->login($manager,97)['status'],'manager login');$managerCsrf=$h->token($manager);$before=corSnapshot($h);corOutcome($failures,'different actor',corPost($f,4512,$changed,$managerCsrf,$manager),409,'conflict',$before,$h);
    $none=[];assertSameValue(303,$h->login($none,96)['status'],'no-read login');$noneCsrf=$h->token($none);$before=corSnapshot($h);$denied=corPost($f,4512,$changed,$noneCsrf,$none);$body=json_decode($denied['body'],true,flags:JSON_THROW_ON_ERROR);if($denied['status']!==403||$body!==['status'=>'rejected'])$failures[]='no-read replay leaked a non-safe response';if(corSnapshot($h)!==$before)$failures[]='no-read replay wrote facts/files';

    $h->start(['PHP_CLI_SERVER_WORKERS'=>'3'],null,true);$csrf=InspectionFixture::csrf($f->page());
    $raceBase=(int)$h->rows('fm2_checklist_revisions')[0]['revision_no'];$racePng=InspectionFixture::png(20);$race=array_replace($photo,['clientOperationId'=>InspectionFixture::operation(80)['clientOperationId'],'baseRevision'=>$raceBase,'sha256'=>hash('sha256',$racePng),'size'=>strlen($racePng),'originalName'=>'race.png']);$before=corSnapshot($h);$results=corRace($f,$race,$race,$csrf,$racePng,$racePng);$statuses=array_column(array_column($results,'body'),'status');sort($statuses);$http=array_column($results,'status');sort($http);$revisions=array_column(array_column($results,'body'),'revision');sort($revisions);if($statuses!==['accepted','duplicate']||$http!==[200,200]||$revisions!==[$raceBase+1,$raceBase+1])$failures[]='equivalent HTTP race outcomes/revisions '.json_encode($results);$after=corSnapshot($h);if(count($h->rows('fm2_checklist_operations'))!==count($before[0][$h->p.'fm2_checklist_operations'][1])+1||count($h->rows('fm2_checklist_photos'))!==count($before[0][$h->p.'fm2_checklist_photos'][1])+1||(int)$h->rows('fm2_checklist_revisions')[0]['revision_no']!==$raceBase+1||count($after[1])!==count($before[1])+1||corUnrelatedFacts($after,$h->p)!==corUnrelatedFacts($before,$h->p))$failures[]='equivalent race did not persist exactly one operation/revision/photo/file and zero other facts';

    $raceBase=(int)$h->rows('fm2_checklist_revisions')[0]['revision_no'];$leftBytes=InspectionFixture::png(21);$rightBytes=InspectionFixture::png(22);$left=array_replace($photo,['clientOperationId'=>InspectionFixture::operation(81)['clientOperationId'],'baseRevision'=>$raceBase,'sha256'=>hash('sha256',$leftBytes),'size'=>strlen($leftBytes),'originalName'=>'race-left.png']);$right=array_replace($left,['sha256'=>hash('sha256',$rightBytes),'size'=>strlen($rightBytes),'originalName'=>'race-right.png']);$before=corSnapshot($h);$results=corRace($f,$left,$right,$csrf,$leftBytes,$rightBytes);$statuses=array_column(array_column($results,'body'),'status');sort($statuses);$http=array_column($results,'status');sort($http);if($statuses!==['accepted','conflict']||$http!==[200,409])$failures[]='conflicting HTTP race outcomes '.json_encode($results);$after=corSnapshot($h);if(count($h->rows('fm2_checklist_operations'))!==count($before[0][$h->p.'fm2_checklist_operations'][1])+1||count($h->rows('fm2_checklist_photos'))!==count($before[0][$h->p.'fm2_checklist_photos'][1])+1||(int)$h->rows('fm2_checklist_revisions')[0]['revision_no']!==$raceBase+1||count($after[1])!==count($before[1])+1||corUnrelatedFacts($after,$h->p)!==corUnrelatedFacts($before,$h->p))$failures[]='conflicting race left loser operation/revision/photo/file or other fact effects';

    if($failures!==[])throw new TestFailure("INTENDED_RED CHECKLIST-OPERATION-REPLAY-001:\n- ".implode("\n- ",$failures));
    echo "PASS: CHECKLIST-OPERATION-REPLAY-001 A-G typed replay equivalence\n";
}finally{if($f!==null)$f->close();}

<?php
declare(strict_types=1);

use FMonitor2\Otiz\{MariaDbObjectRegister,MariaDbObjectRegisterQuery,ObjectRegister};
use FMonitor2\Tests\Support\ObjectRegisterPagingFixture;

$root=dirname(__DIR__,2);
require $root.'/tests/bootstrap.php';
require $root.'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';

$size=isset($argv[1])?(int)$argv[1]:30000;
if($size<125||$size>100000)throw new InvalidArgumentException('Object count must be between 125 and 100000.');
$prefix='bench_otiz_page_'.bin2hex(random_bytes(4)).'_';
$fixture=null;

function sessionStatus(mysqli$db):array
{
    $result=$db->query("SHOW SESSION STATUS WHERE Variable_name IN ('Questions','Bytes_sent')");
    $out=[];foreach($result->fetch_all(MYSQLI_ASSOC)as$row)$out[$row['Variable_name']]=(int)$row['Value'];
    return$out;
}

function benchmarkRead(mysqli$db,ObjectRegister$register,array$query):array
{
    $before=sessionStatus($db);
    if(function_exists('memory_reset_peak_usage'))memory_reset_peak_usage();
    $memoryBefore=memory_get_usage(true);$start=hrtime(true);
    $result=$register->read(18,$query);
    $elapsed=(hrtime(true)-$start)/1_000_000_000;
    $peak=memory_get_peak_usage(true);$memoryAfter=memory_get_usage(true);
    $after=sessionStatus($db);
    return[
        'query'=>$result['query'],'elapsedSeconds'=>$elapsed,'total'=>$result['total'],
        'page'=>$result['page'],'pages'=>$result['pages'],'returnedRows'=>count($result['rows']),
        'questionsDeltaRaw'=>$after['Questions']-$before['Questions'],
        'bytesSentDelta'=>$after['Bytes_sent']-$before['Bytes_sent'],
        'memoryBeforeBytes'=>$memoryBefore,'memoryAfterBytes'=>$memoryAfter,'peakMemoryBytes'=>$peak,
        'resultDigest'=>hash('sha256',json_encode([$result['total'],$result['page'],$result['pages'],array_column($result['rows'],'object_id'),$result['summary']],JSON_THROW_ON_ERROR)),
    ];
}

function legacyObjectsSql(string$prefix):string
{
    return "SELECT l.id object_id,l.regnumber,l.ordadr_address address,d.payload_json,d.captured_at,
        so.snapshot_id,so.report_date,so.current_progress_bp,so.progress_fact_date,so.accrued_cents,so.pool_cents,so.kss_bp,so.calculation_state,so.inputs_json,
        COALESCE(c.paid_cents,0) paid_cents,COALESCE(c.discipline_cents,0) discipline_cents,
        COALESCE(c.deadline_cents,0) deadline_cents,COALESCE(sc.closed_cents,0) snapshot_closed_cents
        FROM `{$prefix}fm_maintable` l
        LEFT JOIN `{$prefix}fm2_pilot_object_details` d ON d.object_id=l.id
        LEFT JOIN (
            SELECT candidate.*,snapshot.report_date
            FROM `{$prefix}fm2_pilot_otiz_snapshot_objects` candidate
            JOIN `{$prefix}fm2_pilot_otiz_snapshots` snapshot ON snapshot.id=candidate.snapshot_id
            WHERE NOT EXISTS (
                SELECT 1 FROM `{$prefix}fm2_pilot_otiz_snapshot_objects` newer
                JOIN `{$prefix}fm2_pilot_otiz_snapshots` ns ON ns.id=newer.snapshot_id
                WHERE newer.object_id=candidate.object_id AND (ns.report_date>snapshot.report_date OR (ns.report_date=snapshot.report_date AND ns.id>snapshot.id))
            )
        ) so ON so.object_id=l.id
        LEFT JOIN (
            SELECT object_id,SUM(paid_cents) paid_cents,SUM(discipline_cents) discipline_cents,SUM(deadline_cents) deadline_cents
            FROM `{$prefix}fm2_pilot_otiz_payment_closures` GROUP BY object_id
        ) c ON c.object_id=l.id
        LEFT JOIN (
            SELECT snapshot_id,object_id,SUM(paid_cents+discipline_cents+deadline_cents) closed_cents
            FROM `{$prefix}fm2_pilot_otiz_payment_closures` GROUP BY snapshot_id,object_id
        ) sc ON sc.object_id=l.id AND sc.snapshot_id=so.snapshot_id
        ORDER BY so.snapshot_id IS NULL,FIELD(so.calculation_state,'blocked','ready','no_new_amount','completed'),l.regnumber,l.id";
}

function benchmarkLegacyObjects(mysqli$db,string$sql):array
{
    $planJson=(string)$db->query('EXPLAIN FORMAT=JSON '.$sql)->fetch_column();
    $before=sessionStatus($db);if(function_exists('memory_reset_peak_usage'))memory_reset_peak_usage();
    $memoryBefore=memory_get_usage(true);$start=hrtime(true);$rows=$db->query($sql)->fetch_all(MYSQLI_ASSOC);
    $elapsed=(hrtime(true)-$start)/1_000_000_000;$peak=memory_get_peak_usage(true);$count=count($rows);
    $digest=hash('sha256',json_encode(array_column($rows,'object_id'),JSON_THROW_ON_ERROR));unset($rows);
    $memoryAfter=memory_get_usage(true);$after=sessionStatus($db);
    return['source'=>'git show 41e39498:rapid-pilot/Otiz.php objects() SELECT lines 244-270','sqlSha256'=>hash('sha256',$sql),
        'elapsedSeconds'=>$elapsed,'returnedRows'=>$count,'questionsDeltaRaw'=>$after['Questions']-$before['Questions'],
        'bytesSentDelta'=>$after['Bytes_sent']-$before['Bytes_sent'],'memoryBeforeBytes'=>$memoryBefore,
        'memoryAfterBytes'=>$memoryAfter,'peakMemoryBytes'=>$peak,'rowIdentityDigest'=>$digest,
        'explain'=>json_decode($planJson,true,512,JSON_THROW_ON_ERROR),'planSha256'=>hash('sha256',$planJson)];
}

function explain(mysqli$db,string$prefix,array$input):array
{
    $query=ObjectRegister::query($input);
    $builder=new MariaDbObjectRegisterQuery($db,$prefix,$prefix);$base=$builder->base();$where=$builder->where($query);
    $order=match($query['sort']){
        'regnumber_asc'=>'regnumber ASC,object_id ASC',
        'regnumber_desc'=>'regnumber DESC,object_id ASC',
        default=>"snapshot_id IS NULL,FIELD(calculation_state,'blocked','ready','no_new_amount','completed'),regnumber,object_id",
    };
    $rawOrder=strtr($order,['snapshot_id'=>'so.snapshot_id','calculation_state'=>'so.calculation_state','regnumber'=>'l.regnumber','object_id'=>'l.id']);
    $offset=($query['page']-1)*$query['pageSize'];
    if($query['state']===''){
        $search=$builder->search($query,'l.regnumber','l.ordadr_address');
        $base=$builder->base($search." ORDER BY {$rawOrder} LIMIT {$query['pageSize']} OFFSET {$offset}");
        $where='';$offset=0;
    }
    $pageOrder=strtr($order,['snapshot_id'=>'page.snapshot_id','calculation_state'=>'page.calculation_state','regnumber'=>'page.regnumber','object_id'=>'page.object_id']);
    $countColumn=$query['state']===''?'':',COUNT(*) OVER() filtered_total';
    $sql=$base.' SELECT page.*,d.payload_json,so.inputs_json FROM (SELECT *'.$countColumn.' FROM economics'.$where.
        " ORDER BY {$order} LIMIT {$query['pageSize']} OFFSET {$offset}) page
        LEFT JOIN `{$prefix}fm2_pilot_object_details` d ON d.object_id=page.object_id
        LEFT JOIN `{$prefix}fm2_pilot_otiz_snapshot_objects` so ON so.object_id=page.object_id AND so.snapshot_id=page.snapshot_id
        ORDER BY {$pageOrder}";
    $json=(string)$db->query('EXPLAIN FORMAT=JSON '.$sql)->fetch_column();
    return['query'=>$query,'plan'=>json_decode($json,true,512,JSON_THROW_ON_ERROR),'planSha256'=>hash('sha256',$json)];
}

try{
    $sourceFiles=['app/Otiz/MariaDbObjectRegister.php','app/Otiz/MariaDbObjectRegisterQuery.php','app/Otiz/ObjectEconomy.php','app/Otiz/NativePremiumNorms.php'];
    $sourceHashes=static function()use($root,$sourceFiles):array{$out=[];foreach($sourceFiles as$file)$out[$file]=hash_file('sha256',$root.'/'.$file);return$out;};
    $sourceHashStart=$sourceHashes();
    $fixture=new ObjectRegisterPagingFixture($prefix);$db=$fixture->db;
    if($size>125){
        $values=[];
        for($id=126;$id<=$size;$id++){
            $values[]="({$id},'Synthetic scale address {$id}','SCALE-{$id}')";
            if(count($values)===500||$id===$size){$db->query("INSERT INTO `{$prefix}fm_maintable`(id,ordadr_address,regnumber) VALUES".implode(',',$values));$values=[];}
        }
        $db->query("INSERT INTO `{$prefix}fm2_pilot_object_details`(object_id,schema_version,content_sha256,payload_json,captured_at) SELECT l.id,d.schema_version,d.content_sha256,d.payload_json,d.captured_at FROM `{$prefix}fm_maintable` l CROSS JOIN `{$prefix}fm2_pilot_object_details` d WHERE d.object_id=1 AND l.id>125");
    }
    $register=new ObjectRegister($db,$prefix,$prefix);
    $pages=(int)ceil($size/50);
    $scenarios=[
        'first'=>['sort'=>'regnumber_asc','page'=>1,'pageSize'=>50],
        'last'=>['sort'=>'regnumber_asc','page'=>$pages,'pageSize'=>50],
        'search'=>['q'=>'Уникальная цель за третьей страницей','sort'=>'default','page'=>1,'pageSize'=>50],
        'state'=>['state'=>'missing_norm','sort'=>'default','page'=>1,'pageSize'=>50],
    ];
    $plans=[];foreach($scenarios as$name=>$query)$plans[$name]=explain($db,$prefix,$query);
    $measurements=[];foreach($scenarios as$name=>$query)$measurements[$name]=benchmarkRead($db,$register,$query);
    // Run the buffered legacy diagnostic last so its allocator high-water mark cannot
    // contaminate the bounded-memory candidate observations.
    $legacyBaseline=benchmarkLegacyObjects($db,legacyObjectsSql($prefix));
    $sourceHashEnd=$sourceHashes();
    echo json_encode([
        'schema'=>'otiz-object-register-benchmark-v1','sourceCommit'=>trim((string)shell_exec('git -C '.escapeshellarg($root).' rev-parse HEAD')),
        'sourceHashesStart'=>$sourceHashStart,'sourceHashesEnd'=>$sourceHashEnd,'sourceStable'=>$sourceHashStart===$sourceHashEnd,
        'objects'=>$size,'fixture'=>'ObjectRegisterPagingFixture 125 historical/snapshot/closure rows plus norm-valid synthetic remainder',
        'sessionCounterNote'=>'Each raw Questions delta includes the trailing SHOW SESSION STATUS observation; Bytes_sent is the same connection counter.',
        'legacyObjectsSqlBaseline'=>$legacyBaseline,'measurements'=>$measurements,'explains'=>$plans,
    ],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
}finally{if($fixture)$fixture->close();}

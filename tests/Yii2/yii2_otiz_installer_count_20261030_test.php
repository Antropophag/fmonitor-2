<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/ObjectQueueFixture.php';
// Owner feedback 2026-10-29: public queue, exact status filters and truthful progress.
$f=null;
try {
    $f=new ObjectQueueFixture(dirname(__DIR__,2));
    $f->object(451202,6102,'needs_assignment_order');
    $f->object(451208,6108,'needs_assignment_change');
    $f->order(6181,6108,1,7301);
    $f->checklist(6108,1,1); // Canonical item 1 contributes 2%.
    $f->checklist(6108,8,2); // Canonical item 8 contributes 5%.
    $f->insert($f->p.'fm2_order_installers',[
        'assignment_order_id'=>6181,'installer_tab_id'=>7002,'fio_snapshot'=>'Монтажник 7002',
        'position_snapshot'=>'Монтажник','employment_status_snapshot'=>'employed',
        'employed_from_snapshot'=>'2020-01-01','workforce_source_snapshot'=>'fixture',
        'workforce_source_updated_at_snapshot'=>'2026-09-20T09:00:00+03:00',
        'valid_from'=>'2020-01-01','valid_to'=>null,'change_action'=>'assign',
    ]);
    foreach ([[7003,'2020-12-31','assign'],[7004,null,'release']] as [$tab,$until,$action]) {
        $f->insert($f->p.'fm2_order_installers',[
            'assignment_order_id'=>6181,'installer_tab_id'=>$tab,'fio_snapshot'=>'Исторический участник',
            'position_snapshot'=>'Монтажник','employment_status_snapshot'=>'employed',
            'employed_from_snapshot'=>'2020-01-01','workforce_source_snapshot'=>'fixture',
            'workforce_source_updated_at_snapshot'=>'2026-09-20T09:00:00+03:00',
            'valid_from'=>'2020-01-01','valid_to'=>$until,'change_action'=>$action,
        ]);
    }
    $f->object(451209,6109,'working');$f->order(6191,6109,1,7301);
    $f->completion(6109,'pto_act');$f->completion(6109,'declaration');
    $f->db->query("INSERT IGNORE INTO {$f->p}fm2_pilot_role_permissions(role_id,permission)VALUES(9201,'otiz.manage')");
    $economy=new FMonitor2\Otiz\OtizPortfolioEconomy($f->connection(),$f->p,'');$portfolio=$economy->read(9101,['pageSize'=>100]);$byObject=array_column($portfolio['rows'],null,'objectId');assertSameValue(2,$byObject[451208]['installerCount']??null,'INTENDED_RED OTIZ exposes current team, excluding released/ended assignments');assertSameValue(1,$byObject[451201]['installerCount']??null,'latest order only in OTIZ');assertSameValue(0,$byObject[451202]['installerCount']??null,'no accepted crew gives zero');
    $q=$f->queue();$before=$f->facts();
    $working=$q->read(9101,'','installation',1);
    assertSameValue([451201],array_map('intval',array_column($working['objects'],'id')),'INTENDED_RED filter must match displayed installation status');
    assertSameValue(1,$working['filters']['total'],'same filtered count');
    $changed=$q->read(9101,'','needs_assignment_change',1)['objects'][0];
    assertSameValue('Требуется изменение',$changed['status'],'preserve change status');
    assertSameValue(7,$changed['completionProgress']??null,'INTENDED_RED preserve completed work during assignment change');
    assertSameValue(2,$changed['installerCount']??null,'INTENDED_RED current team count');
    assertSameValue(1,$working['objects'][0]['installerCount']??null,'only latest team, not sum of past orders');
    $pending=$q->read(9101,'451202','',1)['objects'][0];
    assertSameValue(0,$pending['installerCount']??null,'no accepted team before order');
    $done=$q->read(9101,'451209','completed',1)['objects'][0];
    assertSameValue(100,$done['completionProgress']??null,'PTO plus declaration gives 100 percent');
    assertSameValue($before,$f->facts(),'queue reads preserve all facts and schema');
    // OTIZ controller uses canonical non-empty legacy prefix; mirror this fixture object into that source.
    $columns=array_flip(array_column($f->db->query("SHOW COLUMNS FROM {$f->p}fm_maintable")->fetch_all(MYSQLI_ASSOC),'Field'));$source=$f->db->query("SELECT * FROM fm_maintable WHERE id=451208")->fetch_assoc();$f->insert($f->p.'fm_maintable',array_intersect_key($source,$columns));
    $f->http->start();$cookies=[];$f->http->login($cookies);
    $page=$f->http->request('GET','/pilot/objects?status=needs_assignment_change',[],$cookies);
    assertSameValue(200,$page['status'],'HTTP filtered list');
    $dom=new DOMDocument();@$dom->loadHTML('<?xml encoding="UTF-8">'.$page['body']);$xp=new DOMXPath($dom);
    $cell=$xp->query('//*[@data-completion-progress]')->item(0);
    assertSameValue('7%',trim($cell?->textContent??''),'HTTP shows known progress instead of dash');
    assertSameValue(false,str_contains($page['body'],'data-label="Монтажников"'),'count moved out of general queue');$otiz=$f->http->request('GET','/pilot/otiz/objects?year=all&q=451208',[],$cookies);assertSameValue(200,$otiz['status'],'OTIZ economy readable');assertSameValue(true,str_contains($otiz['body'],'Монтажников')&&str_contains($otiz['body'],'/pilot/objects/451208?tab=team#object-panel-team'),'OTIZ team column links to composition');
    echo "PASS: OTIZ feedback queue filters, progress, team and read-only behavior\n";
} finally {if($f instanceof ObjectQueueFixture)$f->close();}

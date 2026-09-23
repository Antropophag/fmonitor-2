<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\Tests\Support\SelectionHttpFixture;

$f=new SelectionHttpFixture(true,static fn()=>['FMONITOR_NOW'=>'2026-09-07T09:00:00+03:00']);
try{
    FMonitor2\InstallationProcess\BitrixWorkforceHistorySchemaMigration::apply($f->original->selection->db,'');
    $workforce=$f->original->selection->rows()['fm2_workforce_catalog'];$prototype=null;foreach($workforce as$row)if((int)$row['installer_tab_id']===7001)$prototype=$row;if($prototype===null)throw new TestFailure('SETUP_FAILURE: workforce prototype');for($i=1;$i<=23;$i++){$row=$prototype;$row['installer_tab_id']=7100+$i;$row['fio']='Search Person '.str_pad((string)$i,2,'0',STR_PAD_LEFT);$row['reconciliation_state']='delivered';$row['employed_from']='2020-01-01';$f->original->selection->schema->insert('fm2_workforce_catalog',$row);}
    $before=$f->original->selection->rows();
    $empty=$f->request('GET','/pilot/objects/4512/assignment-order/installers?q=&page=1','',18);assertSameValue(400,$empty['status'],'empty installer search is rejected');
    $short=$f->request('GET','/pilot/objects/4512/assignment-order/installers?q=7&page=1','',18);
    assertSameValue(400,$short['status'],'installer search requires at least two characters');
    $padded=$f->request('GET','/pilot/objects/4512/assignment-order/installers?q=0071&page=1','',18);$paddedJson=json_decode($padded['body'],true,512,JSON_THROW_ON_ERROR);assertSameValue([200,true],[$padded['status'],count($paddedJson['items']??[])>0],'leading-zero personnel number search remains familiar');
    $denied=$f->request('GET','/pilot/objects/4512/assignment-order/installers?q=Search&page=1','',99);assertSameValue(403,$denied['status'],'installer search requires exact selection capability');
    $found=$f->request('GET','/pilot/objects/4512/assignment-order/installers?q=Search&page=1','',18);
    assertSameValue(['application/json; charset=UTF-8',200],[$found['headers']['content-type']??null,$found['status']],'authorized installer search returns JSON');
    $json=json_decode($found['body'],true,512,JSON_THROW_ON_ERROR);
    assertSameValue([1,true,20],[$json['page']??null,$json['hasMore']??null,count($json['items']??[])],'bounded first page metadata');$second=$f->request('GET','/pilot/objects/4512/assignment-order/installers?q=Search&page=2','',18);$json2=json_decode($second['body'],true,512,JSON_THROW_ON_ERROR);assertSameValue([200,2,false,3],[$second['status'],$json2['page']??null,$json2['hasMore']??null,count($json2['items']??[])],'bounded last page metadata');assertSameValue([],array_values(array_intersect(array_column($json['items'],'tabId'),array_column($json2['items'],'tabId'))),'pages do not overlap');
    assertSameValue($before,$f->original->selection->rows(),'installer search is read only');
    echo "PASS installer search HTTP manual pilot\n";
}finally{$f->close();}

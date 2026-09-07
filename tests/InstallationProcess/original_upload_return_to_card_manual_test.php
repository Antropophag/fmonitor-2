<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use FMonitor2\PilotHttp\{HttpUser,OriginalUploadView};

// Owner feedback 2026-09-07: upload returns to the object; application/opening are separate roles/actions.
$user=new HttpUser(18,'Сотрудник загрузки','upload@example.invalid',['objects.read']);
$model=['objectId'=>4512,'orderId'=>81,'orderVersion'=>1,'current'=>null,'mode'=>'initial','suggestedDocumentDate'=>'2026-09-07','composition'=>['installers'=>[['fullName'=>'Монтажник']],'engineer'=>['fullName'=>'Инженер']]];
foreach([null,['revisionNumber'=>1,'documentDate'=>'2026-09-06','rootId'=>'root-81','revisionId'=>'revision-81']] as $current){
    $model['current']=$current;$model['mode']=$current===null?'initial':'correction';
    $html=OriginalUploadView::render($user,$model,'csrf-fixture');$dom=new DOMDocument();@$dom->loadHTML($html,LIBXML_NONET);$xpath=new DOMXPath($dom);
    $form=$xpath->query('//form[@data-original-upload-form]')->item(0);
    assertSameValue('/pilot/objects/4512',$form->getAttribute('data-return-url'),'accepted initial/correction returns to object card');
    assertSameValue('/pilot/objects/4512/assignment-orders/81/originals',$form->getAttribute('action'),'original command endpoint unchanged');
    assertSameValue(0,$xpath->query('//form[@data-original-upload-form]//a[contains(@href,"/execution")]')->length,'uploader is not prompted to apply composition/open work');
    assertSameValue(1,$xpath->query('//aside[contains(@class,"fm2-order-helper")]//a[@href="/pilot/objects/4512" and normalize-space(.)!=""]')->length,'helper offers one accessible return to object card');
    assertSameValue(1,$xpath->query('//form[@data-original-upload-form]//input[@name="csrfToken" and @value="csrf-fixture"]')->length,'upload keeps CSRF');
}
echo "PASS original upload and correction return to object card\n";

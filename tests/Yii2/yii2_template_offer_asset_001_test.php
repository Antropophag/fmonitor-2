<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require __DIR__.'/PreopeningFixture.php';
$f=null;
try{
 $f=new PreopeningFixture(dirname(__DIR__,2));$f->start();$cookies=[];assertSameValue(303,$f->login($cookies,18)['status'],'FKR login');
 $page=$f->request('GET','/pilot/objects/4512/assignment-order/selection',[],$cookies);assertSameValue(200,$page['status'],'selection page');
 assertSameValue(1,preg_match('#<script[^>]+src="/pilot/assets/template-offer\.js"#',$page['body']),'INTENDED_RED selection declares bounded template offer asset');
 $asset=$f->request('GET','/pilot/assets/template-offer.js',[],$cookies);assertSameValue([200,'text/javascript; charset=UTF-8','public, max-age=3600'],[$asset['status'],$asset['headers']['content-type'][0]??null,$asset['headers']['cache-control'][0]??null],'asset public transport');
 foreach(['data-template-offer','prefers-reduced-motion','transitioncancel']as$token)assertSameValue(true,str_contains($asset['body'],$token),'asset owns behavior '.$token);
 $preopening=$f->request('GET','/pilot/assets/preopening.js',[],$cookies);assertSameValue(200,$preopening['status'],'picker/upload asset retained');foreach(['data-template-offer','savedInstallerIds','prefers-reduced-motion','transitioncancel']as$token)assertSameValue(false,str_contains($preopening['body'],$token),'old asset releases template-offer ownership '.$token);
 $f->noLegacy();echo"PASS: TEMPLATE-OFFER-REVEAL-001 bounded Yii asset\n";
}finally{if($f instanceof PreopeningFixture)$f->close();}

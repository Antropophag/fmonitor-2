<?php
declare(strict_types=1);require dirname(__DIR__).'/bootstrap.php';require __DIR__.'/DocumentaryFixture.php';
$f=null;try{
 $f=new DocumentaryFixture(dirname(__DIR__,2));$f->open();$h=$f->http;
 $save=static function(string$text,int$revision,int$n)use($h,$f):void{$before=$h->facts();$r=$h->form('/pilot/objects/4512/details',['_csrf'=>$h->token($f->cookies),'requestId'=>sprintf('cccccccc-0000-4000-8000-%012d',$n),'expectedRevision'=>(string)$revision,'comment'=>$text],$f->cookies);assertSameValue(303,$r['status'],'comment accepted in lifecycle state');$f->unchangedExcept($before,['fm2_object_detail_edits','fm2_object_detail_edit_events','fm2_object_detail_edit_requests']);$page=$f->page();assertSameValue(200,$page['status'],'card readable');assertSameValue(true,str_contains($page['body'],$text),'comment readable in state');};
 $save('Комментарий во время монтажа',0,1);
 $f->progress();DocumentaryFixture::accepted($f->post('record_pto',['ptoActDate'=>'2026-09-05']));DocumentaryFixture::accepted($f->post('record_declaration',['declarationDate'=>'2026-09-06','declarationDetails'=>'Д-001']));
 assertSameValue(true,str_contains($f->page()['body'],'Работы завершены'),'completed lifecycle reached');$save('Пояснение после завершения работ',1,2);
 echo "PASS: object comments work on active and genuinely completed objects without changing process facts\n";
}finally{if($f instanceof DocumentaryFixture)$f->close();}

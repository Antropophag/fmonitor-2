<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/PreopeningFixture.php';

$f=null;
try{
 $f=new PreopeningFixture(dirname(__DIR__,2));$p=$f->p;
 $f->db->query("UPDATE {$p}fm_maintable SET zavnumber='0012.03',regnumber='77-000123' WHERE id=4512");
 $name=$f->db->real_escape_string('<Тех & 0012.03>');$f->db->query("INSERT INTO {$p}fm2_bitrix_order_document_links VALUES(501,'{$name}','0012.03','https://tenant.bitrix24.test/docs/501')");
 $f->db->query("INSERT INTO {$p}fm2_bitrix_order_document_links VALUES(502,'Монтажная схема','0012.03','https://tenant.bitrix24.test/docs/502')");
 $f->start();
 $guest=[];$denied=$f->request('GET','/pilot/objects/4512',[],$guest);assertSameValue(303,$denied['status'],'guest denied before documentation read');assertSameValue(false,str_contains($denied['body'],'docs/501'),'guest learns no link');
 $withoutRead=[];$f->login($withoutRead,96);$f->db->query("DELETE rp FROM {$p}fm2_pilot_role_permissions rp JOIN {$p}fm2_pilot_user_roles ur ON ur.role_id=rp.role_id WHERE ur.user_id=96 AND rp.permission='objects.read'");$denied=$f->request('GET','/pilot/objects/4512',[],$withoutRead);assertSameValue(403,$denied['status'],'active actor without objects.read denied');assertSameValue(false,str_contains($denied['body'],'docs/501'),'permission denial hides link');$factsBefore=$f->facts();
 $cookies=[];assertSameValue(303,$f->login($cookies,95)['status'],'objects.read actor login');$card=$f->request('GET','/pilot/objects/4512',[],$cookies);assertSameValue(200,$card['status'],'authorized card');foreach(['Техническая документация','2 папки в Битрикс24','Открывается в Битрикс24','https://tenant.bitrix24.test/docs/501','&lt;Тех &amp; 0012.03&gt;','fm2-technical-documents']as$text)assertSameValue(true,str_contains($card['body'],$text),'INTENDED_RED card content '.$text);assertSameValue(false,str_contains($card['body'],'<Тех & 0012.03>'),'source title escaped');assertSameValue(true,preg_match('#href="https://tenant\.bitrix24\.test/docs/501"[^>]*rel="noopener noreferrer"#',$card['body'])===1,'safe external relation');
 $head=$f->request('HEAD','/pilot/objects/4512',[],$cookies);assertSameValue([200,''],[$head['status'],$head['body']],'HEAD no body');assertSameValue($factsBefore,$f->facts(),'GET/HEAD never mutate any fact');
 $f->db->query("UPDATE {$p}fm_maintable SET zavnumber=NULL WHERE id=4512");$missing=$f->request('GET','/pilot/objects/4512',[],$cookies);assertSameValue([200,true],[$missing['status'],str_contains($missing['body'],'Номер заказа не указан')],'missing order degrades section only');
 $f->db->query("UPDATE {$p}fm_maintable SET zavnumber='NO-DOC' WHERE id=4512");$empty=$f->request('GET','/pilot/objects/4512',[],$cookies);assertSameValue([200,true,true],[$empty['status'],str_contains($empty['body'],'Техническая документация не найдена'),str_contains($empty['body'],'Проверьте номер заказа')],'empty section only');
 $f->db->query("DROP TABLE {$p}fm2_bitrix_order_document_links");$unavailable=$f->request('GET','/pilot/objects/4512',[],$cookies);assertSameValue([200,true],[$unavailable['status'],str_contains($unavailable['body'],'Техническая документация временно недоступна')],'corrupt projection leaves card usable');
 $unknown=$f->request('GET','/pilot/objects/999999',[],$cookies);assertSameValue(404,$unknown['status'],'unknown object unchanged');
 $f->db->query("UPDATE {$p}fm2_pilot_roles SET status=0 WHERE role_id=5");$revoked=$f->request('GET','/pilot/objects/4512',[],$cookies);assertSameValue(true,in_array($revoked['status'],[303,403],true),'fresh inactive role denied');assertSameValue(false,str_contains($revoked['body'],'docs/501'),'revoked actor learns no link');
 $f->noLegacy();
 echo "PASS: BITRIX-ORDER-DOCUMENT-LINKS-001 A7 object-card authorization/states/no mutation\n";
}finally{if($f instanceof PreopeningFixture)$f->close();}

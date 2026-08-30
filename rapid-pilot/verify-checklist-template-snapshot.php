<?php

declare(strict_types=1);

require_once __DIR__.'/legacy-migration/LegacyChecklistTemplateSnapshot.php';
function templateCheck(bool$c,string$m):void{if(!$c)throw new RuntimeException($m);}
$parts=[['id'=>2,'name'=>'B','rang'=>2],['id'=>1,'name'=>'A','rang'=>1]];$definitions=[['id'=>20,'part_id'=>2,'name'=>'Y','share'=>60,'rang'=>2,'needphoto'=>0],['id'=>10,'part_id'=>1,'name'=>'X','share'=>40,'rang'=>1,'needphoto'=>1]];
$a=LegacyChecklistTemplateSnapshot::build($parts,$definitions,'2026-08-30 12:00:00');$b=LegacyChecklistTemplateSnapshot::build(array_reverse($parts),array_reverse($definitions),'2026-08-30 12:00:00');
templateCheck($a===$b,'canonical snapshot is deterministic');templateCheck($a['counts']===['parts'=>2,'definitions'=>2,'totalShare'=>100],'snapshot counts');
templateCheck($a['payload']['validFrom']==='2026-08-30 12:00:00'&&str_contains($a['payload']['validity'],'future_native'),'validity begins at capture without backdating');
try{LegacyChecklistTemplateSnapshot::build($parts,[['id'=>10,'share'=>101]],'2026-08-30 12:00:00');throw new RuntimeException('invalid accepted');}catch(InvalidArgumentException){}
echo json_encode(['ok'=>true,'hash'=>$a['contentSha256'],'validFrom'=>$a['payload']['validFrom']],JSON_THROW_ON_ERROR),"\n";

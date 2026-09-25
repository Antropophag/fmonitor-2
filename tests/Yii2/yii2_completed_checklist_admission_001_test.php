<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require __DIR__.'/InspectionFixture.php';
$f=null;try{$f=new InspectionFixture(dirname(__DIR__,2));$f->open();$h=$f->http;$page=$f->page();$h->db->query("UPDATE {$h->p}fm2_installation_cases SET process_state='completed' WHERE id=6101");$before=$h->facts();$r=$f->send(InspectionFixture::operation(),InspectionFixture::csrf($page));assertSameValue(409,$r['status'],'INTENDED_RED completed item is domain rejection');assertSameValue($before,$h->facts(),'no facts');echo"PASS completed item admission\n";}finally{if($f instanceof InspectionFixture)$f->close();}

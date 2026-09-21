<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require dirname(__DIR__).'/Yii2/PreopeningFixture.php';

use FMonitor2\InstallationProcess\MariaDbEffectiveObjectDetails;

$f=null;try{$f=new PreopeningFixture(dirname(__DIR__,2));$p=$f->p;
 if(getenv('FMONITOR_FIXTURE_REACHABILITY')==='object-details-consumer-fixture'){assertSameValue(1,(int)$f->db->query("SELECT COUNT(*) FROM {$p}fm_maintable WHERE id=4512")->fetch_column(),'legacy source fixture');assertSameValue(1,(int)$f->db->query("SELECT COUNT(*) FROM {$p}fm2_pilot_object_details WHERE object_id=4512")->fetch_column(),'technical source fixture');echo"FIXTURE_REACHABLE: object-details-consumer-fixture\n";exit(0);}
 assertSameValue(true,class_exists(MariaDbEffectiveObjectDetails::class),'INTENDED_RED OBJECT-DETAILS-EDITING-001 effective reader exists');
 $reader=new MariaDbEffectiveObjectDetails($f->db,$p,$p);$before=$f->facts();$base=$reader->read(4512);
 assertSameValue(['TEST-4512','ZAV-4512'],[$base['regnumber']['value'],$base['zavnumber']['value']],'source fallback');
 $payload=json_encode(['zavnumber'=>'00123-А','pitmaterial'=>'86'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
 $f->db->query("INSERT INTO {$p}fm2_object_detail_edits(object_id,revision,values_json,updated_at_utc,updated_by_user_id)VALUES(4512,1,'".$f->db->real_escape_string($payload)."','2026-09-21 12:42:00',18)");
 $effective=$reader->read(4512);assertSameValue(['00123-А','manual','86','manual'],[$effective['zavnumber']['value'],$effective['zavnumber']['source'],$effective['pitmaterial']['raw'],$effective['pitmaterial']['source']],'A10 effective overlay provenance');
 assertSameValue($before['fm_maintable'],$f->facts()['fm_maintable'],'A9 legacy mirror bytes unchanged');
 assertSameValue($before['fm2_pilot_object_details'],$f->facts()['fm2_pilot_object_details'],'A9 technical snapshot/hash unchanged');
 echo "PASS: OBJECT-DETAILS-EDITING-001 effective consumer source preservation\n";
}finally{if($f instanceof PreopeningFixture)$f->close();}

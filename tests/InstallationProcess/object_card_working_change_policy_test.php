<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/PilotHttp/PilotHttp.php';
use FMonitor2\PilotHttp\ObjectCardProcessStatePolicy;
$registered=['id'=>11,'version_no'=>1,'kind'=>'initial','status'=>'registered','previous_assignment_order_id'=>null];
$change=['id'=>12,'version_no'=>2,'kind'=>'change','status'=>'prepared','previous_assignment_order_id'=>11];
assertSameValue(true,ObjectCardProcessStatePolicy::workingOrderValid($change,[$change,$registered],2),'Prepared change with its exact registered predecessor keeps the opened card readable');
assertSameValue(false,ObjectCardProcessStatePolicy::workingOrderValid(['id'=>11,'version_no'=>1,'kind'=>'initial','status'=>'prepared','previous_assignment_order_id'=>null],[['id'=>11,'version_no'=>1,'kind'=>'initial','status'=>'prepared','previous_assignment_order_id'=>null]],1),'Prepared initial order cannot masquerade as working');
assertSameValue(false,ObjectCardProcessStatePolicy::workingOrderValid($change,[$change,['id'=>10,'version_no'=>1,'kind'=>'initial','status'=>'registered','previous_assignment_order_id'=>null]],2),'An unrelated earlier registered order is not the predecessor');
echo "PASS object card working change policy\n";

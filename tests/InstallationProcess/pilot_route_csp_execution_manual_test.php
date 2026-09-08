<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/PilotHttp/PilotHttp.php';

use FMonitor2\PilotHttp\{ExecutionView,HttpUser,PilotRouteCsp};

$body=ExecutionView::render(new HttpUser(18,'ФКР','synthetic@example.invalid',['objects.read']),
    ['objectId'=>4512,'object'=>['address'=>'Тестовый адрес']],null,null,['actual_start_date'=>null],str_repeat('c',64));
assertSameValue(true,str_contains($body,'/pilot/assets/navigation.js'),'execution uses the shared interactive shell');
foreach(['GET','HEAD']as$method)assertSameValue(PilotRouteCsp::SCRIPT,
    PilotRouteCsp::forResponse($method,'/pilot/objects/4512/execution',200,'text/html; charset=UTF-8',$body),
    'execution permits its same-origin shell script');
assertSameValue(PilotRouteCsp::BASE,PilotRouteCsp::forResponse('POST','/pilot/objects/4512/execution',403,'text/html',$body),'denied commands retain base policy');
echo "PASS execution shell script CSP\n";

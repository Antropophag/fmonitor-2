<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/PilotHttp/PilotHttp.php';
require dirname(__DIR__,2).'/app/PilotHttp/FreshOrderSelectionView.php';

use FMonitor2\PilotHttp\{FreshOrderSelectionView,HttpUser,PilotRouteCsp};

// Owner feedback 2026-09-07: restore the existing modal and never embed the catalog.
$model=['objectId'=>4512,'latest'=>null,'selectionRevision'=>0,'lastTemplateDate'=>null,
    'object'=>['address'=>'Тестовый адрес','entrance'=>'1','objectRegistrationNumber'=>'TEST-4512','plannedStartDate'=>null,'plannedFinishDate'=>null],
    'installers'=>[['tabId'=>7001,'fullName'=>'Каталог Не Должен Встраиваться','position'=>'Монтажник','source'=>'synthetic','updatedAt'=>'2026-09-07']],
    'engineers'=>[['userId'=>73,'fullName'=>'Тестовый инженер','position'=>'Инженер']]];
$html=FreshOrderSelectionView::render(new HttpUser(18,'ФКР','fkr@example.invalid',['objects.read']),$model,str_repeat('c',64));
assertSameValue(true,str_contains($html,'<dialog ')&&str_contains($html,'Выбор монтажников'),'selection uses modal installer picker');
assertSameValue(false,str_contains($html,'Каталог Не Должен Встраиваться'),'catalog rows are not embedded in HTML');
assertSameValue(true,str_contains($html,'fm2-order-heading')&&str_contains($html,'fm2-order-helper'),'incumbent order layout and helper restored');
assertSameValue(true,str_contains($html,'/pilot/assets/selection-picker.js'),'server search controller included');
assertSameValue(PilotRouteCsp::ORIGINAL,PilotRouteCsp::forResponse('GET','/pilot/objects/4512/assignment-order/selection',200,'text/html',$html),'selection allows same-origin search requests');
echo "PASS modal selection surface excludes full catalog and permits bounded search\n";

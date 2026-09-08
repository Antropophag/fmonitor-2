<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require_once dirname(__DIR__,2).'/app/PilotHttp/PilotHttp.php';
require_once dirname(__DIR__,2).'/app/PilotHttp/PilotView.php';
require_once dirname(__DIR__,2).'/app/PilotHttp/ObjectListView.php';

use FMonitor2\PilotHttp\{HttpUser,ProductionObjectListRenderer,InstallationStatusLabels};
$row=['id'=>966,'registrationNumber'=>'TEST','address'=>'Тестовый адрес','entrance'=>'1','plannedStartDate'=>'2026-09-07','plannedFinishDate'=>'2026-10-01','status'=>'В работе'];
$html=(new ProductionObjectListRenderer())->render(new HttpUser(18,'ФКР','fixture@example.invalid',['objects.read']),[$row]);
assertSameValue(true,str_contains($html,'data-label="Статус"'),'table names the installation stage status');
assertSameValue(true,str_contains($html,'>Монтажные работы</span>'),'table uses the same status as the current status list');
assertSameValue(false,str_contains($html,'>В работе</span>'),'old status alias is absent');
assertSameValue(['Требуется распоряжение','Готов к открытию','Монтажные работы','Документарное закрытие','Работы завершены','Требуется изменение'],array_values(InstallationStatusLabels::OPTIONS),'owner-approved existing list is retained');
echo "PASS installation status vocabulary in table and filters\n";

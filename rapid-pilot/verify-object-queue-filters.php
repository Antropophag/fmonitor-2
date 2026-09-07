<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/app/PilotHttp/PilotHttp.php';
require_once dirname(__DIR__).'/app/PilotHttp/PilotView.php';
require_once dirname(__DIR__).'/app/PilotHttp/ObjectListView.php';
require_once dirname(__DIR__).'/app/PilotHttp/InstallationStatusLabels.php';
require_once __DIR__.'/ObjectQueue.php';

$source=file_get_contents(__DIR__.'/ObjectQueue.php');$reader=file_get_contents(dirname(__DIR__).'/app/PilotHttp/MariaDbObjectQueue.php');$labels=file_get_contents(dirname(__DIR__).'/app/PilotHttp/InstallationStatusLabels.php');$css=file_get_contents(__DIR__.'/pilot.css');$controller=file_get_contents(__DIR__.'/object-queue.js');$router=file_get_contents(__DIR__.'/router.php');
if(!is_string($source)||!is_string($reader)||!is_string($labels)||!is_string($css)||!is_string($controller)||!is_string($router))throw new RuntimeException('Object queue sources unavailable');
$check=static function(bool$value,string$message):void{if(!$value)throw new RuntimeException($message);};
$render=new ReflectionMethod(RapidPilotObjectQueue::class,'render');$html=$render->invoke(null,new \FMonitor2\PilotHttp\HttpUser(1,'Verifier','verifier@example.invalid',['objects.read']),[],['q'=>'','status'=>'','page'=>1,'pages'=>1,'total'=>0],false,'');
foreach(['name="q"','name="status"','type="search"','Рег. номер, адрес, подъезд или № объекта','Все статусы','>Статус</span>']as$needle)$check(str_contains($html,$needle),'missing rendered application filter: '.$needle);
foreach(['shlz-field--select shlz-select-root','data-shlz-select','role="combobox"','aria-controls="fm2-object-status-options"','aria-labelledby="fm2-object-status-label fm2-object-status-value"','role="listbox"','role="option"','type="hidden" name="status"','shlz-select__chevron']as$contract)$check(str_contains($html,$contract),'documented rendered SHLZ Select contract missing: '.$contract);
$check(str_contains($controller,"import { enhanceSelects } from '/pilot/assets/shlz-behaviors.js'"),'official SHLZ Select controller is not imported');
$check(str_contains($controller,'enhanceSelects(document)'),'SHLZ Select controller is not initialized');
$check(str_contains($router,"packages/behaviors/dist/browser.js"),'public built SHLZ behavior bundle is not routed');
foreach(['l.regnumber LIKE','l.ordadr_address LIKE','l.entrance LIKE','CAST(c.legacy_installation_object_id AS CHAR) LIKE']as$needle)$check(str_contains($reader,$needle),'search field missing from read owner: '.$needle);
foreach(['needs_assignment_order','ready_to_open','installation','document_closeout','completed','needs_assignment_change']as$status)$check(str_contains($reader,$status),'read owner status missing: '.$status);
$check(str_contains($source,'InstallationStatusLabels::OPTIONS'),'object filter does not render shared canonical status labels');
foreach(['needs_assignment_order'=>'Требуется распоряжение','ready_to_open'=>'Готов к открытию','installation'=>'Монтажные работы','document_closeout'=>'Документарное закрытие','completed'=>'Работы завершены','needs_assignment_change'=>'Требуется изменение']as$value=>$label){$check(str_contains($labels,"'$value' => '$label'")||str_contains($labels,"'$value'=>'$label'"),'shared status label missing: '.$value.'='.$label);$check(str_contains($html,'data-value="'.$value.'">'.$label.'</button>'),'exact shared status is absent from rendered filter: '.$value.'='.$label);}
$check(!str_contains($labels,"'assignment_order_prepared' => 'Распоряжение подготовлено'")&&!str_contains($labels,"'assignment_order_prepared'=>'Распоряжение подготовлено'"),'prepared order remains a user-visible filter status');
$check(!str_contains($labels,"'working' => 'В работе'")&&!str_contains($labels,"'working'=>'В работе'"),'obsolete combined working status remains in select');
$check(!str_contains($source,'fm2-next-document'),'next-step document labels remain');
$check(str_contains($source,"['q'=>\$filters['q'],'status'=>\$filters['status']]"),'pagination query does not preserve filters');
$check(str_contains($reader,"'page' => \$page, 'pages' => \$pages, 'total' => \$total, 'pageSize' => \$size"),'server-side pagination metadata missing from read owner');
$check(!str_contains($source,'fm2-segments'),'migration segment controls remain in rapid queue');
foreach(['Только миграция','Нативный импорт','Активны с cutover','Демо-данные']as$label)$check(!str_contains($source,$label),'migration filter label remains: '.$label);
$check(str_contains($css,'.fm2-object-filters'),'object filters have no responsive layout');
$check(str_contains($css,'.fm2-object-filters .shlz-field__control:focus-within'),'object filters have no scoped focus treatment');
echo "PASS object queue application filters\n";

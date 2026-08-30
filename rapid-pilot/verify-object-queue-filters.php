<?php

declare(strict_types=1);

$source=file_get_contents(__DIR__.'/ObjectQueue.php');$css=file_get_contents(__DIR__.'/pilot.css');$controller=file_get_contents(__DIR__.'/object-queue.js');$router=file_get_contents(__DIR__.'/router.php');
if(!is_string($source)||!is_string($css)||!is_string($controller)||!is_string($router))throw new RuntimeException('Object queue sources unavailable');
$check=static function(bool$value,string$message):void{if(!$value)throw new RuntimeException($message);};
foreach(['name="q"','name="status"','type="search"','Рег. номер, адрес, подъезд или № объекта','Все состояния']as$needle)$check(str_contains($source,$needle),'missing application filter: '.$needle);
foreach(['shlz-field--select shlz-select-root','data-shlz-select','role="combobox"','aria-controls="fm2-object-status-options"','aria-labelledby="fm2-object-status-label fm2-object-status-value"','role="listbox"','role="option"','type="hidden" name="status"','shlz-select__chevron']as$contract)$check(str_contains($source,$contract),'documented SHLZ Select contract missing: '.$contract);
$check(str_contains($controller,"import { enhanceSelects } from '/pilot/assets/shlz-behaviors.js'"),'official SHLZ Select controller is not imported');
$check(str_contains($controller,'enhanceSelects(document)'),'SHLZ Select controller is not initialized');
$check(str_contains($router,"packages/behaviors/dist/browser.js"),'public built SHLZ behavior bundle is not routed');
foreach(['l.regnumber LIKE','l.ordadr_address LIKE','l.entrance LIKE','CAST(c.legacy_installation_object_id AS CHAR) LIKE']as$needle)$check(str_contains($source,$needle),'search field missing: '.$needle);
foreach(['needs_assignment_order','assignment_order_prepared','ready_to_open','working','needs_assignment_change']as$status)$check(str_contains($source,$status),'status missing: '.$status);
$check(str_contains($source,"['q'=>\$filters['q'],'status'=>\$filters['status']]"),'pagination query does not preserve filters');
$check(str_contains($source,"'page'=>(int)\$page,'pages'=>\$pages,'total'=>\$total,'pageSize'=>50"),'server-side pagination metadata missing');
$check(!str_contains($source,'fm2-segments'),'migration segment controls remain in rapid queue');
foreach(['Только миграция','Нативный импорт','Активны с cutover','Демо-данные']as$label)$check(!str_contains($source,$label),'migration filter label remains: '.$label);
$check(str_contains($css,'.fm2-object-filters'),'object filters have no responsive layout');
$check(str_contains($css,'.fm2-object-filters .shlz-field__control:focus-within'),'object filters have no scoped focus treatment');
echo "PASS object queue application filters\n";

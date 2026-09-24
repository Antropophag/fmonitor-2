<?php declare(strict_types=1);use FMonitor2\YiiRuntime\ViewSupport;use yii\helpers\Html;$pagination=$objects[0]['_pagination']??['page'=>1,'pages'=>1,'total'=>0];$failedInspection=$failedInspection??[];$inspectionMessage=$inspectionMessage??'';$today=(new DateTimeImmutable('now',new DateTimeZone('Europe/Moscow')))->format('Y-m-d');$activity=static function(?string$value):?array{if($value===null||trim($value)==='')return null;try{$instant=(new DateTimeImmutable($value))->setTimezone(new DateTimeZone('Europe/Moscow'));return['source'=>$value,'date'=>$instant->format('d.m.Y'),'time'=>$instant->format('H:i')];}catch(Throwable){return null;}};ViewSupport::begin($this,'Стройконтроль',$identity,'construction-control');?>
<section class="fm2-control-queue" data-control-queue data-user-id="<?=(int)$identity->getId()?>">
<header class="fm2-control-header">
<div><h1>Стройконтроль</h1><p>Объекты, закреплённые за инженерами, и актуальное состояние чек-листов.</p></div>
<span data-result-count>
<?=$pagination['total']?> объектов</span>
</header>
<form class="fm2-control-tools" method="get" action="/pilot/construction-control" data-control-filter-form>
<label class="shlz-field fm2-control-search"><span class="shlz-field__label">Поиск объектов</span><span class="shlz-field__control"><img src="/pilot/assets/shlz-icons/search.svg" data-shlz-icon="search" alt=""><input class="shlz-input" type="search" name="query" value="<?=Html::encode($filters['query'])?>" data-control-search placeholder="Адрес или регистрационный номер"></span></label>
<div class="fm2-control-filters"><fieldset class="shlz-segment shlz-segment--sm" aria-label="Принадлежность объектов">
<label class="shlz-segment__option"><input class="shlz-segment__input" type="radio" name="ownership" value="mine" <?=$filters['ownership']==='mine'?'checked':''?>><span class="shlz-segment__label">Мои</span></label>
<label class="shlz-segment__option"><input class="shlz-segment__input" type="radio" name="ownership" value="all" <?=$filters['ownership']==='all'?'checked':''?>><span class="shlz-segment__label">Все</span></label>
</fieldset>
<label class="shlz-choice fm2-completed-switch"><input class="shlz-checkbox" type="checkbox" name="completed" value="1" data-show-completed <?=$filters['completed']==='1'?'checked':''?>><span>Показывать завершённые</span></label><button class="shlz-button shlz-button--secondary" type="button" data-clear-filters>Сбросить фильтры</button></div>
</form>
<div class="fm2-control-surface shlz-table-wrap" tabindex="0" aria-label="Объекты стройконтроля"><table class="shlz-table fm2-control-table"><thead class="shlz-table__head"><tr class="shlz-table__row"><th class="shlz-table__cell" scope="col">Объект</th><th class="shlz-table__cell" scope="col">Последняя активность</th><th class="shlz-table__cell" scope="col"><span class="fm2-visually-hidden">Отгрузка и техническая документация</span></th><th class="shlz-table__cell" scope="col">Инженер</th><th class="shlz-table__cell" scope="col"><span class="fm2-visually-hidden">Открыть</span></th></tr></thead>
<tbody>
<?php foreach($objects as$o):$engineer=$o['controlEngineer'];$shipmentDate=$o['fullShipmentDate']??$o['firstShipmentDate']??null;$shipmentState=($o['fullShipmentDate']??null)!==null?'full':(($o['firstShipmentDate']??null)!==null?'partial':'unknown');$shipmentTitle=$shipmentState==='full'?'Полностью отгружен':($shipmentState==='partial'?'Частично отгружен':'Не известно');$shipmentLabel=$shipmentDate===null?$shipmentTitle:$shipmentTitle.', '.(new DateTimeImmutable((string)$shipmentDate))->format('d.m.Y');?>
<tr class="fm2-control-row" data-control-row data-object-id="<?=$o['id']?>" data-engineer-id="<?=(int)($engineer['userId']??0)?>" data-completed="<?=$o['completed']?'true':'false'?>" data-document-status="<?=Html::encode($o['technicalDocumentStatus'])?>"<?php if($o['technicalDocumentStatus']==='available'):?> data-has-document="true"<?php endif?> data-search="<?=Html::encode(mb_strtolower($o['address'].' '.$o['registrationNumber']))?>">
<td class="fm2-object-cell">
<a class="fm2-control-link" href="/pilot/construction-control/objects/<?=$o['id']?>/checklist">
<span class="fm2-control-address"><?=Html::encode($o['address'])?></span><span class="fm2-control-reg">Рег. № <?=Html::encode($o['registrationNumber'])?><?php if(trim((string)$o['entrance'])!==''):?><span class="fm2-control-entrance">Подъезд <?=Html::encode((string)$o['entrance'])?></span><?php endif?></span>
</a>
<?php $plan=$o['inspectionPlan']??null;if(($o['inspectionToday']??false)===true):?><strong>Инспекция сегодня</strong><?php endif?>
<?php if(is_array($plan)):?><span>Инспекция запланирована на <?=Html::encode((new DateTimeImmutable($plan['inspectionDate']))->format('d.m.Y'))?></span><button class="shlz-button shlz-button--sm" type="button" data-inspection-action="reschedule" data-inspection-object-id="<?=$o['id']?>" data-plan-id="<?=$plan['scheduleId']?>" data-plan-version="<?=$plan['version']?>" data-inspection-date="<?=Html::encode($plan['inspectionDate'])?>">Перенести</button><button class="shlz-button shlz-button--sm" type="button" data-inspection-action="cancel" data-inspection-object-id="<?=$o['id']?>" data-plan-id="<?=$plan['scheduleId']?>" data-plan-version="<?=$plan['version']?>" data-inspection-date="<?=Html::encode($plan['inspectionDate'])?>">Отменить</button><?php else:?><button class="shlz-button shlz-button--sm" type="button" data-inspection-action="create" data-inspection-object-id="<?=$o['id']?>" data-plan-id="" data-plan-version="0" data-inspection-date="<?=$today?>">Запланировать инспекцию</button><?php endif?>
</td>
<?php $lastActivity=$activity($o['lastChecklistActivityAt']);$activityState=$lastActivity!==null?'recorded':(($o['ready']??false)?'ready':'empty');?><td data-activity-state="<?=$activityState?>">
<?php if($activityState==='ready'):?><span class="fm2-activity fm2-activity--ready">Готов к открытию</span><?php else:?><span class="fm2-activity-line"><span class="fm2-local-sync" data-local-sync>
<span class="fm2-visually-hidden">Синхронизировано</span>
</span><?php if($lastActivity!==null):?><time class="fm2-activity" datetime="<?=Html::encode($lastActivity['source'])?>"><span class="fm2-activity-date"><?=Html::encode($lastActivity['date'])?></span><small class="fm2-activity-time"><?=Html::encode($lastActivity['time'])?></small></time><?php else:?><span class="fm2-activity fm2-activity--never">Инспекций ещё не было</span><?php endif?></span><?php endif?>
</td>
<td class="fm2-shipment-cell" data-shipment-state="<?=$shipmentState?>"><span class="fm2-control-status-actions"><span class="fm2-shipment-status" aria-label="<?=Html::encode($shipmentLabel)?>" title="<?=Html::encode($shipmentLabel)?>">
<?php if($shipmentState!=='unknown'):?><span class="fm2-shipment-icon-stack" aria-hidden="true">
<img class="fm2-shipment-icon" src="/pilot/assets/shlz-icons/delivery-box.svg" alt="">
</span><?php endif?></span><?php if($o['technicalDocumentStatus']==='available'):?><span class="fm2-control-document-indicator" aria-label="Техническая документация доступна" title="Техническая документация доступна"><img src="/pilot/assets/shlz-icons/folder-file-open.svg" alt=""></span><?php endif?></span>
</td>
<td>
<?=Html::encode((string)($engineer['fullName']??'Инженер не назначен'))?>
</td>
<td class="fm2-row-action"><span aria-hidden="true"><img src="/pilot/assets/shlz-icons/chevron-right-duo.svg" data-shlz-icon="chevron-right-duo" alt=""></span></td>
</tr>
<?php endforeach?>
</tbody>
</table>
<div class="fm2-empty fm2-control-empty" data-control-empty <?=$pagination['total']===0?'':'hidden'?>>
<strong>Объекты не найдены</strong>
</div>
<footer class="fm2-control-footer"><span data-list-summary>Показано <?=count($objects)?> из <?=$pagination['total']?></span><?=ViewSupport::pagination('/pilot/construction-control',(int)$pagination['page'],(int)$pagination['pages'],(int)$pagination['total'],50,$filters,'Страницы стройконтроля')?></footer></div></section>
<script src="/pilot/assets/control-queue.js" defer>
</script>
<dialog class="shlz-modal fm2-inspection-dialog" data-inspection-dialog<?=($failedInspection!==[]?' data-inspection-failed':'')?> aria-labelledby="inspection-dialog-title"><form method="post" class="shlz-modal__surface fm2-inspection-dialog__surface" data-inspection-form><header class="shlz-modal__header"><div><h2 class="shlz-modal__title" id="inspection-dialog-title" data-inspection-title>Запланировать инспекцию</h2><p>Инспекция появится в календаре и списке стройконтроля.</p></div><button class="shlz-modal__close" type="button" aria-label="Закрыть" data-inspection-close>×</button></header><div class="shlz-modal__body fm2-inspection-dialog__body"><?php if($inspectionMessage!==''):?><p role="alert"><?=Html::encode($inspectionMessage)?></p><?php endif?><?=Html::hiddenInput(Yii::$app->request->csrfParam,Yii::$app->request->csrfToken)?><?=Html::hiddenInput('action',(string)($failedInspection['action']??'create'),['data-inspection-command'=>true])?><?=Html::hiddenInput('planId',(string)($failedInspection['planId']??''))?><?=Html::hiddenInput('expectedVersion',(string)($failedInspection['expectedVersion']??'0'))?><?=Html::hiddenInput('requestId',(string)($failedInspection['requestId']??ViewSupport::uuid()))?><?=Html::hiddenInput('ownership',(string)$filters['ownership'])?><?=Html::hiddenInput('query',(string)$filters['query'])?><?=Html::hiddenInput('completed',(string)$filters['completed'])?><label class="shlz-field" data-inspection-date-field data-native-date-fallback><span class="shlz-field__label">Дата инспекции</span><span class="shlz-field__control"><?=Html::input('date','inspectionDate',(string)($failedInspection['inspectionDate']??$today),['class'=>'shlz-input','required'=>true,'autofocus'=>true])?></span></label></div><footer class="shlz-modal__footer"><button class="shlz-button" type="button" data-inspection-close>Закрыть</button><button class="shlz-button shlz-button--primary" type="submit" data-inspection-submit>Запланировать</button></footer></form></dialog>
<script src="/pilot/assets/inspection-schedule.js" defer></script>
<?php ViewSupport::end($this);

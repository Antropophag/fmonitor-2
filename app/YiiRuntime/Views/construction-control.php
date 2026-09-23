<?php declare(strict_types=1);use FMonitor2\YiiRuntime\ViewSupport;use yii\helpers\Html;$pagination=$objects[0]['_pagination']??['page'=>1,'pages'=>1,'total'=>0];$activity=static function(?string$value):?array{if($value===null||trim($value)==='')return null;try{$instant=(new DateTimeImmutable($value))->setTimezone(new DateTimeZone('Europe/Moscow'));return['source'=>$value,'date'=>$instant->format('d.m.Y'),'time'=>$instant->format('H:i')];}catch(Throwable){return null;}};ViewSupport::begin($this,'Стройконтроль',$identity,'construction-control');?>
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
<div class="fm2-control-surface shlz-table-wrap" tabindex="0" aria-label="Объекты стройконтроля"><table class="shlz-table fm2-control-table"><thead class="shlz-table__head"><tr class="shlz-table__row"><th class="shlz-table__cell" scope="col">Объект</th><th class="shlz-table__cell" scope="col">Последняя активность</th><th class="shlz-table__cell" scope="col"><span class="fm2-visually-hidden">Отгрузка</span></th><th class="shlz-table__cell" scope="col">Инженер</th><th class="shlz-table__cell" scope="col"><span class="fm2-visually-hidden">Открыть</span></th></tr></thead>
<tbody>
<?php foreach($objects as$o):$engineer=$o['controlEngineer'];$shipmentDate=$o['fullShipmentDate']??$o['firstShipmentDate']??null;$shipmentState=($o['fullShipmentDate']??null)!==null?'full':(($o['firstShipmentDate']??null)!==null?'partial':'unknown');$shipmentTitle=$shipmentState==='full'?'Полностью отгружен':($shipmentState==='partial'?'Частично отгружен':'Не известно');$shipmentLabel=$shipmentDate===null?$shipmentTitle:$shipmentTitle.', '.(new DateTimeImmutable((string)$shipmentDate))->format('d.m.Y');?>
<tr class="fm2-control-row" data-control-row data-object-id="<?=$o['id']?>" data-engineer-id="<?=(int)($engineer['userId']??0)?>" data-completed="<?=$o['completed']?'true':'false'?>" data-document-status="<?=Html::encode($o['technicalDocumentStatus'])?>"<?php if($o['technicalDocumentCount']!==null):?> data-document-count="<?=(int)$o['technicalDocumentCount']?>"<?php endif?> data-search="<?=Html::encode(mb_strtolower($o['address'].' '.$o['registrationNumber']))?>">
<td class="fm2-object-cell">
<a class="fm2-control-link" href="/pilot/construction-control/objects/<?=$o['id']?>/checklist">
<span class="fm2-control-address"><?=Html::encode($o['address'])?></span><span class="fm2-control-reg">Рег. № <?=Html::encode($o['registrationNumber'])?><?php if(trim((string)$o['entrance'])!==''):?><span class="fm2-control-entrance">Подъезд <?=Html::encode((string)$o['entrance'])?></span><?php endif?></span>
<?php if($o['technicalDocumentStatus']==='available'):?><span class="fm2-control-document-marker">Документация · <?=(int)$o['technicalDocumentCount']?></span><?php elseif($o['technicalDocumentStatus']==='empty'):?><span class="fm2-control-document-marker fm2-control-document-marker--quiet">Документация · нет</span><?php elseif($o['technicalDocumentStatus']==='order_number_missing'):?><span class="fm2-control-document-marker fm2-control-document-marker--quiet">Документация · нет зав. №</span><?php else:?><span class="fm2-control-document-marker fm2-control-document-marker--quiet">Документация · недоступна</span><?php endif?>
</a>
</td>
<?php $lastActivity=$activity($o['lastChecklistActivityAt']);$activityState=$lastActivity!==null?'recorded':(($o['ready']??false)?'ready':'empty');?><td data-activity-state="<?=$activityState?>">
<?php if($activityState==='ready'):?><span class="fm2-activity fm2-activity--ready">Готов к открытию</span><?php else:?><span class="fm2-activity-line"><span class="fm2-local-sync" data-local-sync>
<span class="fm2-visually-hidden">Синхронизировано</span>
</span><?php if($lastActivity!==null):?><time class="fm2-activity" datetime="<?=Html::encode($lastActivity['source'])?>"><span class="fm2-activity-date"><?=Html::encode($lastActivity['date'])?></span><small class="fm2-activity-time"><?=Html::encode($lastActivity['time'])?></small></time><?php else:?><span class="fm2-activity fm2-activity--never">Инспекций ещё не было</span><?php endif?></span><?php endif?>
</td>
<td class="fm2-shipment-cell" data-shipment-state="<?=$shipmentState?>" aria-label="<?=Html::encode($shipmentLabel)?>" title="<?=Html::encode($shipmentLabel)?>"><span class="fm2-shipment-status">
<?php if($shipmentState!=='unknown'):?><span class="fm2-shipment-icon-stack" aria-hidden="true">
<img class="fm2-shipment-icon" src="/pilot/assets/shlz-icons/delivery-box.svg" alt="">
</span><?php endif?></span>
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
<?php ViewSupport::end($this);

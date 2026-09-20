<?php declare(strict_types=1);use FMonitor2\YiiRuntime\ViewSupport;use yii\helpers\Html;$pagination=$objects[0]['_pagination']??['page'=>1,'pages'=>1,'total'=>0];$activity=static function(?string$value):?array{if($value===null||trim($value)==='')return null;try{$instant=(new DateTimeImmutable($value))->setTimezone(new DateTimeZone('Europe/Moscow'));return['source'=>$value,'date'=>$instant->format('d.m.Y'),'time'=>$instant->format('H:i')];}catch(Throwable){return null;}};ViewSupport::begin($this,'Стройконтроль',$identity,'construction-control');?>
<section class="fm2-control-queue" data-control-queue data-user-id="<?=(int)$identity->getId()?>">
<header class="fm2-control-header">
<div><h1>Стройконтроль</h1><p>Объекты, закреплённые за инженерами, и актуальное состояние чек-листов.</p></div>
<span data-result-count>
<?=$pagination['total']?> объектов</span>
</header>
<div class="fm2-control-tools">
<label class="shlz-field fm2-control-search"><span class="shlz-field__label">Поиск объектов</span><span class="shlz-field__control"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg><input class="shlz-input" type="search" data-control-search placeholder="Адрес или регистрационный номер"></span></label>
<div class="fm2-control-filters"><fieldset class="shlz-segment shlz-segment--sm" aria-label="Принадлежность объектов">
<label class="shlz-segment__option"><input class="shlz-segment__input" type="radio" name="ownership" value="mine" checked><span class="shlz-segment__label">Мои</span></label>
<label class="shlz-segment__option"><input class="shlz-segment__input" type="radio" name="ownership" value="all"><span class="shlz-segment__label">Все</span></label>
</fieldset>
<label class="shlz-choice fm2-completed-switch"><input class="shlz-checkbox" type="checkbox" data-show-completed><span>Показывать завершённые</span></label></div>
</div>
<div class="fm2-control-surface"><table class="fm2-control-table"><thead><tr><th>Объект</th><th>Последняя активность</th><th>Инженер</th><th><span class="fm2-visually-hidden">Открыть</span></th></tr></thead>
<tbody>
<?php foreach($objects as$o):$engineer=$o['controlEngineer'];?>
<tr class="fm2-control-row" data-control-row data-object-id="<?=$o['id']?>" data-engineer-id="<?=(int)($engineer['userId']??0)?>" data-completed="<?=$o['completed']?'true':'false'?>" data-search="<?=Html::encode(mb_strtolower($o['address'].' '.$o['registrationNumber']))?>">
<td>
<a class="fm2-control-link" href="/pilot/construction-control/objects/<?=$o['id']?>/checklist">
<span class="fm2-control-address"><?=Html::encode($o['address'])?></span><span class="fm2-control-reg">Рег. № <?=Html::encode($o['registrationNumber'])?><?php if(trim((string)$o['entrance'])!==''):?><span class="fm2-control-entrance">Подъезд <?=Html::encode((string)$o['entrance'])?></span><?php endif?></span>
</a>
<?php if($o['ready']??false):?><strong>Готов к открытию</strong><?php endif?>
</td>
<td>
<?php $lastActivity=$activity($o['lastChecklistActivityAt']);?><span class="fm2-activity-line"><span class="fm2-local-sync" data-local-sync>
<span class="fm2-visually-hidden">Синхронизировано</span>
</span><?php if($lastActivity!==null):?><time class="fm2-activity" datetime="<?=Html::encode($lastActivity['source'])?>"><span class="fm2-activity-date"><?=Html::encode($lastActivity['date'])?></span><small class="fm2-activity-time"><?=Html::encode($lastActivity['time'])?></small></time><?php else:?><span class="fm2-activity fm2-activity--never">Инспекций ещё не было</span><?php endif?></span>
</td>
<td>
<?=Html::encode((string)($engineer['fullName']??'Инженер не назначен'))?>
</td>
<td class="fm2-row-action"><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m9 5 7 7-7 7"/></svg></span></td>
</tr>
<?php endforeach?>
</tbody>
</table>
<div class="fm2-empty fm2-control-empty" data-control-empty hidden>
<strong>Объекты не найдены</strong>
<button class="shlz-button shlz-button--secondary" type="button" data-clear-filters>Сбросить фильтры</button>
</div>
<footer class="fm2-control-footer"><span data-list-summary>Показано <?=count($objects)?> из <?=$pagination['total']?></span><?=ViewSupport::pagination('/pilot/construction-control',(int)$pagination['page'],(int)$pagination['pages'],(int)$pagination['total'],50,[],'Страницы стройконтроля')?></footer></div></section>
<script src="/pilot/assets/control-queue.js" defer>
</script>
<?php ViewSupport::end($this);

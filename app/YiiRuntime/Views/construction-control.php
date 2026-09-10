<?php declare(strict_types=1);use FMonitor2\YiiRuntime\ViewSupport;use yii\helpers\Html;$pagination=$objects[0]['_pagination']??['page'=>1,'pages'=>1,'total'=>0];ViewSupport::begin($this,'Стройконтроль',$identity);?>
<section class="fm2-control-queue" data-control-queue data-user-id="<?=(int)$identity->getId()?>">
<header class="fm2-control-header">
<h1>Стройконтроль</h1>
<span data-result-count>
<?=$pagination['total']?> объектов</span>
</header>
<div class="fm2-control-tools">
<label>
<input type="search" data-control-search placeholder="Адрес или регистрационный номер">
</label>
<fieldset>
<label>
<input type="radio" name="ownership" value="mine" checked>Мои</label>
<label>
<input type="radio" name="ownership" value="all">Все</label>
</fieldset>
<label>
<input type="checkbox" data-show-completed>Показывать завершённые</label>
</div>
<table>
<tbody>
<?php foreach($objects as$o):$engineer=$o['controlEngineer'];?>
<tr data-control-row data-object-id="<?=$o['id']?>" data-engineer-id="<?=(int)($engineer['userId']??0)?>" data-completed="<?=$o['completed']?'true':'false'?>" data-search="<?=Html::encode(mb_strtolower($o['address'].' '.$o['registrationNumber']))?>">
<td>
<a href="/pilot/construction-control/objects/<?=$o['id']?>/checklist">
<?=Html::encode($o['address'])?> · <?=Html::encode($o['registrationNumber'])?>
</a>
</td>
<td>
<span data-local-sync>
<span class="fm2-visually-hidden">Синхронизировано</span>
</span>
<?=Html::encode((string)($o['lastChecklistActivityAt']?:'Инспекций ещё не было'))?>
</td>
<td>
<?=Html::encode((string)($engineer['fullName']??'Инженер не назначен'))?>
</td>
</tr>
<?php endforeach?>
</tbody>
</table>
<div data-control-empty hidden>
<strong>Объекты не найдены</strong>
<button type="button" data-clear-filters>Сбросить фильтры</button>
</div>
<span data-list-summary>Показано <?=count($objects)?> из <?=$pagination['total']?>
</span>
<?php if($pagination["page"]>1):?>
<a href="/pilot/construction-control?page=<?=$pagination["page"]-1?>">Предыдущая</a>
<?php endif?>
<?php if($pagination["page"]<$pagination["pages"]):?>
<a href="/pilot/construction-control?page=<?=$pagination["page"]+1?>">Следующая</a>
<?php endif?>
</section>
<script src="/pilot/assets/control-queue.js" defer>
</script>
<?php ViewSupport::end($this);

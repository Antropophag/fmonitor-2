<?php
declare(strict_types=1);
use FMonitor2\YiiRuntime\ViewSupport;
use yii\helpers\Html;
$rub=static fn(int$c):string=>($c<0?'−':'').number_format(abs($c)/100,2,',',' ').' ₽';
$this->registerJsFile('/pilot/assets/otiz.js',['type'=>'module','position'=>\yii\web\View::POS_END]);
ViewSupport::begin($this,$title,$identity,'otiz');
?>
<div class="fm2-otiz fm2-otiz-page">
<?= $this->render('_otiz-nav',['current'=>$mode]) ?>
<?php if($mode==='payments'): ?>
<section class="fm2-otiz-workflow" data-otiz-workflow-header aria-labelledby="otiz-payments-title"><div><h1 id="otiz-payments-title">Подготовка выплат</h1><p>Выберите дату, чтобы собрать расчётный период и проверить готовность объектов.</p></div><form method="post" action="/pilot/otiz/calculate" class="fm2-otiz-period-form"><input type="hidden" name="_csrf" value="<?= Html::encode($csrf) ?>"><input type="hidden" name="operationId" value="<?= ViewSupport::uuid() ?>"><label class="shlz-field"><span class="shlz-field__label">Дата расчёта</span><span class="shlz-field__control"><input class="shlz-input" type="text" inputmode="numeric" name="reportDate" value="<?= Html::encode($date) ?>" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" aria-describedby="otiz-date-format" required></span><span class="shlz-field__secondary" id="otiz-date-format">Формат: ГГГГ-ММ-ДД</span></label><button class="shlz-button shlz-button--primary" type="submit">Подготовить расчёт</button></form></section>
<?= $this->render('_otiz-snapshot-list',['rows'=>$snapshots,'listTitle'=>'Расчётные периоды','context'=>'history']) ?>
<?php elseif($mode==='objects'): $q=$register['query']; ?>
<header class="fm2-page-header"><div><h1>Экономика объектов</h1><p>Фонд, выплаты и готовность монтажных объектов</p></div><span class="fm2-result-count"><?= (int)$register['total'] ?> объектов</span></header>
<?php if($hasError): ?><p class="fm2-otiz-feedback" role="alert">Действие не выполнено. Проверьте условия и повторите попытку.</p><?php endif ?>
<section class="fm2-otiz-register-summary" aria-label="Итоги реестра"><div><span>Фонд премии</span><strong><?= $rub((int)$register['summary']['fund']) ?></strong></div><div><span>Заработано</span><strong><?= $rub((int)$register['summary']['earned']) ?></strong></div><div><span>Выплачено</span><strong><?= $rub((int)$register['summary']['paid']) ?></strong></div><div><span>Удержано</span><strong><?= $rub((int)$register['summary']['penalties']) ?></strong></div></section>
<form method="get" action="/pilot/otiz" class="fm2-otiz-register-toolbar"><label class="shlz-field"><span class="shlz-field__label">Поиск</span><span class="shlz-field__control"><input class="shlz-input" type="search" name="q" data-otiz-search value="<?= Html::encode($q['q']) ?>" placeholder="Регномер или адрес"></span></label><label class="shlz-field"><span class="shlz-field__label">Состояние</span><span class="shlz-field__control">
<?= Html::dropDownList('state','',[''=>'Все состояния','blocked'=>'Требует данных','ready'=>'Готов к расчёту'],['class'=>'shlz-select']) ?>
</span></label><button class="shlz-button shlz-button--primary" type="submit">Показать</button></form>
<div class="fm2-otiz-register-wrap"><table class="shlz-table fm2-otiz-register-table" data-mobile-strategy="labelled-rows"><thead><tr><th>Объект</th><th>Фонд</th><th>Выплачено</th><th>Состояние</th></tr></thead><tbody>
<?php foreach($register['rows']as$row): ?><tr data-otiz-row><td data-label="Объект"><a href="/pilot/objects/<?= (int)$row['object_id'] ?>"><strong><?= Html::encode((string)$row['regnumber']) ?></strong><small><?= Html::encode((string)$row['address']) ?></small></a></td><td data-label="Фонд"><?= $rub((int)($row['fund_cents']??0)) ?></td><td data-label="Выплачено"><?= $rub((int)$row['paid_cents']) ?></td><td data-label="Состояние"><?= Html::encode((string)$row['state']) ?></td></tr><?php endforeach ?>
<?php if($register['rows']===[]): ?><tr class="fm2-otiz-register-empty-row"><td data-label="Объект">Нет объектов</td><td data-label="Фонд">—</td><td data-label="Выплачено">—</td><td data-label="Состояние">—</td></tr><?php endif ?>
</tbody></table></div>
<?php if($register['rows']===[]): ?><section class="fm2-otiz-empty" data-empty="objects"><h2>Объекты не найдены</h2><p>Измените фильтры или вернитесь к полному реестру.</p><a class="shlz-link" href="/pilot/otiz/objects">Сбросить фильтры</a></section><?php endif ?>
<?= $this->render('_otiz-snapshot-list',['rows'=>$snapshots,'listTitle'=>'Последние расчёты','context'=>'objects']) ?>
<?php else: ?>
<header class="fm2-page-header"><div><h1><?= Html::encode($title) ?></h1><p>Неизменяемая история расчётных периодов и выплат</p></div></header>
<?php if($hasError): ?><p class="fm2-otiz-feedback" role="alert">Действие не выполнено. Проверьте основание и повторите попытку.</p><?php endif ?>
<?= $this->render('_otiz-snapshot-list',['rows'=>$snapshots,'listTitle'=>'Расчётные периоды','context'=>'history']) ?>
<?php endif ?>
</div>
<?php ViewSupport::end($this); ?>

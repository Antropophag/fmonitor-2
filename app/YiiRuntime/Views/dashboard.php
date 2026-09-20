<?php
declare(strict_types=1);
use FMonitor2\YiiRuntime\Assets\ShellAssetBundle;
use FMonitor2\YiiRuntime\ViewSupport;
use yii\helpers\Html;
ShellAssetBundle::register($this);
ViewSupport::begin($this, 'Дашборд', $identity, 'dashboard');
?>
<main class="fm2-dashboard-page">
<?php if ($unavailable): ?>
    <section class="shlz-empty-state shlz-empty-state--basic fm2-dashboard-state" role="alert">
        <div class="shlz-empty-state__content"><h1>Данные временно недоступны</h1><p>Попробуйте обновить страницу позже.</p></div>
        <a class="shlz-button" href="/pilot/objects">Вернуться к объектам</a>
    </section>
<?php else: $date=(new DateTimeImmutable($cutoff))->format('d.m.Y'); ?>
    <header class="fm2-dashboard-header"><div><h1>Дашборд</h1><p>Операционная сводка по объектам монтажа</p></div><span class="shlz-status shlz-status--source-blue">Срез на <?=Html::encode($date)?></span></header>
    <section class="shlz-dashboard" aria-label="Показатели объектов">
        <div class="shlz-dashboard__grid fm2-dashboard-metrics">
<?php foreach ([
    ['total','Всего объектов',$total,'Все доступные объекты','/pilot/objects'],
    ['active','В активном монтаже',$active,'Монтажные работы и документарное закрытие','/pilot/objects?status=installation'],
    ['overdue','Просрочены',$overdueCount,'Не завершены, плановый срок раньше даты среза','#overdue'],
    ['upcoming','Начало в ближайшие 14 дней',$upcomingCount,'Плановый старт с даты среза по 13-й день включительно','#upcoming'],
] as [$key,$label,$value,$description,$href]): ?>
            <a class="shlz-chart-widget fm2-dashboard-metric" href="<?=Html::encode($href)?>" aria-label="<?=Html::encode($label)?>: <?=$value?>">
                <span class="shlz-chart-widget__header"><strong><?=Html::encode($label)?></strong></span>
                <span class="fm2-dashboard-value" data-dashboard-metric="<?=$key?>"><?=$value?></span>
                <span class="fm2-dashboard-basis"><?=Html::encode($description)?></span>
            </a>
<?php endforeach ?>
        </div>
    </section>
<?php if ($total === 0): ?>
    <section class="shlz-empty-state shlz-empty-state--basic fm2-dashboard-state"><div class="shlz-empty-state__content"><h2>Объектов пока нет</h2><p>Когда объекты станут доступны, здесь появится операционная сводка.</p></div><a class="shlz-link" href="/pilot/objects">Открыть реестр</a></section>
<?php else: ?>
    <div class="fm2-dashboard-lists">
<?php foreach ([['overdue','Просроченные объекты',$overdue,'Плановое окончание'],['upcoming','Ближайшие старты',$upcoming,'Плановое начало']] as [$key,$title,$rows,$dateLabel]): ?>
        <section class="fm2-dashboard-list" id="<?=$key?>" data-dashboard-list="<?=$key?>"><header><h2><?=$title?></h2><span><?=count($rows)?> из <?=$key==='overdue'?$overdueCount:$upcomingCount?></span></header>
<?php if ($rows === []): ?><div class="shlz-empty-state shlz-empty-state--simple"><div class="shlz-empty-state__content"><p>Объектов для внимания нет.</p></div></div>
<?php else: ?><ol><?php foreach ($rows as $row): ?><li data-dashboard-object data-object-id="<?=$row['id']?>"><a class="shlz-link" href="/pilot/objects/<?=$row['id']?>"><strong><?=Html::encode($row['registrationNumber'] !== '' ? $row['registrationNumber'] : 'Объект '.$row['id'])?></strong><span><?=$dateLabel?> · <?=Html::encode((new DateTimeImmutable($row['date']))->format('d.m.Y'))?></span></a></li><?php endforeach ?></ol><?php endif ?>
        </section>
<?php endforeach ?>
    </div>
    <a class="shlz-button fm2-dashboard-register" href="/pilot/objects">Перейти к реестру объектов</a>
<?php endif ?>
<?php endif ?>
</main>
<?php ViewSupport::end($this); ?>

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
<?php
$stageTitle='Объекты по этапам процесса';$weekTitle='Плановая нагрузка на 6 недель';$activityTitle='Давность последней подтверждённой активности';
$weekLabel=static fn(array $week):string=>(new DateTimeImmutable($week['start']))->format('d.m').'–'.(new DateTimeImmutable($week['end']))->format('d.m');
$stageMax=max(1,...array_column($charts['stages'],'value'));$weekMax=max(1,...array_merge(array_column($charts['weeks'],'starts'),array_column($charts['weeks'],'finishes')));$activityMax=max(1,...array_column($charts['activityAge'],'value'));
$bar=static function(string $href,string $name,string $label,int $value,int $maximum):void{$size=$value===0?0:(int)round($value/$maximum*112);?>
<a class="fm2-chart-bar" data-dashboard-bar href="<?=Html::encode($href)?>" aria-label="<?=Html::encode($name)?>"><span class="fm2-chart-bar__mark" style="--fm2-bar-size:<?=$size?>px"></span><span class="fm2-chart-bar__value" data-dashboard-value="<?=$value?>"><?=$value?></span> <span class="fm2-chart-bar__label"><?=Html::encode($label)?></span></a><?php };
?>
    <div class="fm2-dashboard-charts">
        <section class="shlz-chart-widget fm2-dashboard-chart fm2-dashboard-chart--stages" data-dashboard-chart="stages"><header class="shlz-chart-widget__header"><div><h2><?=$stageTitle?></h2><p>Текущий этап каждого объекта</p></div></header><div class="shlz-chart-widget__plot"><div class="fm2-chart-bars fm2-chart-bars--six">
<?php foreach($charts['stages'] as$item)$bar('/pilot/objects?chart=stage&bucket='.$item['key'],$stageTitle.' — '.$item['label'].': '.$item['value'],$item['label'],$item['value'],$stageMax); ?>
        </div></div></section>
        <section class="shlz-chart-widget fm2-dashboard-chart" data-dashboard-chart="weeks"><header class="shlz-chart-widget__header"><div><h2><?=$weekTitle?></h2><p><span class="fm2-chart-key fm2-chart-key--start"></span>Плановые начала · <span class="fm2-chart-key fm2-chart-key--finish"></span>Плановые окончания</p></div></header><div class="shlz-chart-widget__plot"><div class="fm2-chart-weeks">
<?php foreach($charts['weeks'] as$i=>$week):$label=$weekLabel($week);?><div class="fm2-chart-week"><span class="fm2-chart-week__label"><?=$label?></span><div class="fm2-chart-week__bars"><?php $bar('/pilot/objects?chart=planned-start&bucket='.$i,$weekTitle.' — Плановые начала — '.$label.': '.$week['starts'],'Начало',$week['starts'],$weekMax);$bar('/pilot/objects?chart=planned-finish&bucket='.$i,$weekTitle.' — Плановые окончания — '.$label.': '.$week['finishes'],'Окончание',$week['finishes'],$weekMax);?></div></div><?php endforeach ?>
        </div></div></section>
        <section class="shlz-chart-widget fm2-dashboard-chart" data-dashboard-chart="activity-age"><header class="shlz-chart-widget__header"><div><h2><?=$activityTitle?></h2><p>Только активные объекты, по серверному времени</p></div></header><div class="shlz-chart-widget__plot"><div class="fm2-chart-bars fm2-chart-bars--five">
<?php foreach($charts['activityAge'] as$item)$bar('/pilot/objects?chart=activity-age&bucket='.$item['key'],$activityTitle.' — '.$item['label'].': '.$item['value'],$item['label'],$item['value'],$activityMax); ?>
        </div></div></section>
    </div>
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

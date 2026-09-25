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
<?php $util=$installerUtilization;$uh=$installerUtilizationHistory;$uf=$uh[0]??null;$ul=$uh===[]?null:$uh[count($uh)-1];$umax=max(1,...array_merge([1],array_column($uh,'without_current'))); ?>
<p data-utilization-source>Полный кадровый срез · актуален на <?=Html::encode((new DateTimeImmutable($util['sourceUpdatedAt']))->setTimezone(new DateTimeZone('Europe/Moscow'))->format('d.m.Y H:i'))?></p>
<section class="shlz-chart-widget fm2-utilization-history" data-installer-utilization-history aria-labelledby="installer-utilization-title"><header class="shlz-chart-widget__header"><div><h2 id="installer-utilization-title">Загрузка монтажников и динамика</h2><p data-utilization-legend><span><i class="fm2-chart-key fm2-chart-key--start"></i>Без текущих работ</span> · <span><i class="fm2-chart-key fm2-chart-key--finish"></i>Из них без следующего назначения</span></p></div></header><div class="fm2-utilization-summary"><div><strong><?=$util['working']?></strong><span>В работе</span></div><div><strong><?=$util['awaiting_start']?></strong><span>Ожидают начала</span></div><div><strong><?=$util['unassigned']?></strong><span>Без текущих работ и следующих назначений</span></div></div>
<?php if($uh===[]):?><p>История начнёт накапливаться после первого ежедневного наблюдения.</p><?php else:?><div class="shlz-chart-widget__plot fm2-utilization-scroll" tabindex="0"><div class="fm2-utilization-bars"><?php foreach($uh as$point):$label=(new DateTimeImmutable($point['date']))->format('d.m');?><div class="fm2-utilization-date" data-observation-date="<?=$point['date']?>"><div><a data-series="without_current" data-count="<?=$point['without_current']?>" href="/pilot/dashboard/installers/observations/<?=$point['date']?>/without_current" aria-label="Без текущих работ, <?=$label?>: <?=$point['without_current']?>"><i style="height:<?=max(4,(int)round($point['without_current']/$umax*112))?>px"></i><b><?=$point['without_current']?></b></a><a data-series="without_next" data-count="<?=$point['without_next']?>" href="/pilot/dashboard/installers/observations/<?=$point['date']?>/without_next" aria-label="Из них без следующего назначения, <?=$label?>: <?=$point['without_next']?>"><i style="height:<?=max(4,(int)round($point['without_next']/$umax*112))?>px"></i><b><?=$point['without_next']?></b></a></div><time><?=$label?></time></div><?php endforeach?></div></div><?php if(count($uh)<2):?><p>Недостаточно наблюдений для динамики</p><?php else:$wcPp=round($ul['withoutCurrentShare']-$uf['withoutCurrentShare'],2);$wnPp=round($ul['withoutNextShare']-$uf['withoutNextShare'],2);?><div class="fm2-utilization-comparison" data-utilization-comparison data-from-date="<?=$uf['date']?>" data-to-date="<?=$ul['date']?>" data-from-denominator="<?=$uf['denominator']?>" data-to-denominator="<?=$ul['denominator']?>" data-without-current-from="<?=$uf['without_current']?>" data-without-current-to="<?=$ul['without_current']?>" data-without-current-from-share="<?=number_format($uf['withoutCurrentShare'],2,'.','')?>" data-without-current-to-share="<?=number_format($ul['withoutCurrentShare'],2,'.','')?>" data-without-current-pp="<?=number_format($wcPp,2,'.','')?>" data-without-next-from="<?=$uf['without_next']?>" data-without-next-to="<?=$ul['without_next']?>" data-without-next-from-share="<?=number_format($uf['withoutNextShare'],2,'.','')?>" data-without-next-to-share="<?=number_format($ul['withoutNextShare'],2,'.','')?>" data-without-next-pp="<?=number_format($wnPp,2,'.','')?>"><p><strong>Первое наблюдение</strong> <?=$uf['date']?> · Знаменатель <?=$uf['denominator']?> · <?=$uf['without_current']?> (<?=number_format($uf['withoutCurrentShare'],2,',',' ')?>%) / <?=$uf['without_next']?> (<?=number_format($uf['withoutNextShare'],2,',',' ')?>%)</p><p><strong>Последнее наблюдение</strong> <?=$ul['date']?> · Знаменатель <?=$ul['denominator']?> · <?=$ul['without_current']?> (<?=number_format($ul['withoutCurrentShare'],2,',',' ')?>%) / <?=$ul['without_next']?> (<?=number_format($ul['withoutNextShare'],2,',',' ')?>%)</p><p>Изменение: <?=number_format($wcPp,2,',',' ')?> п. п. / <?=number_format($wnPp,2,',',' ')?> п. п.</p></div><?php endif;endif?></section>
<?php $stageTitle='Объекты по этапам процесса';$weekTitle='Плановая нагрузка на 6 недель';$riskTitle='Риск срыва ближайших стартов';
$weekLabel=static fn(array $week):string=>(new DateTimeImmutable($week['start']))->format('d.m').'–'.(new DateTimeImmutable($week['end']))->format('d.m');
$stageMax=max(1,...array_column($charts['stages'],'value'));$weekMax=max(1,...array_merge(array_column($charts['weeks'],'starts'),array_column($charts['weeks'],'finishes')));$riskMax=max(1,...array_column($charts['startRisk'],'value'));
$bar=static function(string $href,string $name,string $label,int $value,int $maximum,string $tone='default',bool $showLabel=true):void{$height=4+($value===0?0:(int)round($value/$maximum*112));$y=116-$height;$mark=Html::tag('svg',Html::tag('rect','',['x'=>0,'y'=>$y,'width'=>56,'height'=>$height,'rx'=>4]),['class'=>'fm2-chart-bar__mark','viewBox'=>'0 0 56 116','preserveAspectRatio'=>'none','aria-hidden'=>'true']);?>
<a class="fm2-chart-bar fm2-chart-bar--<?=Html::encode($tone)?><?=$showLabel?'':' fm2-chart-bar--legend-only'?>" data-dashboard-bar href="<?=Html::encode($href)?>" aria-label="<?=Html::encode($name)?>"><span class="fm2-chart-bar__mark-frame"><?=$mark?></span><span class="fm2-chart-bar__value" data-dashboard-value="<?=$value?>"><?=$value?></span><?php if($showLabel):?> <span class="fm2-chart-bar__label"><?=Html::encode($label)?></span><?php endif?></a><?php };
?>
    <div class="fm2-dashboard-charts">
        <section class="shlz-chart-widget fm2-dashboard-chart fm2-dashboard-chart--stages" data-dashboard-chart="stages"><header class="shlz-chart-widget__header"><div><h2><?=$stageTitle?></h2><p>Текущий этап каждого объекта</p></div></header><div class="shlz-chart-widget__plot"><div class="fm2-chart-bars fm2-chart-bars--six">
<?php $stageTones=['needs_assignment_order'=>'order','ready_to_open'=>'ready','installation'=>'installation','document_closeout'=>'closeout','completed'=>'completed','needs_assignment_change'=>'change'];foreach($charts['stages'] as$item)$bar('/pilot/objects?chart=stage&bucket='.$item['key'],$stageTitle.' — '.$item['label'].': '.$item['value'],$item['label'],$item['value'],$stageMax,$stageTones[$item['key']]??'default'); ?>
        </div></div></section>
        <section class="shlz-chart-widget fm2-dashboard-chart" data-dashboard-chart="weeks"><header class="shlz-chart-widget__header"><div><h2><?=$weekTitle?></h2><p><span class="fm2-chart-key fm2-chart-key--start"></span>Плановые начала · <span class="fm2-chart-key fm2-chart-key--finish"></span>Плановые окончания</p></div></header><div class="shlz-chart-widget__plot"><div class="fm2-chart-weeks">
<?php foreach($charts['weeks'] as$i=>$week):$label=$weekLabel($week);?><div class="fm2-chart-week"><span class="fm2-chart-week__label" aria-label="<?=$label?>"><span><?=(new DateTimeImmutable($week['start']))->format('d.m')?></span><i aria-hidden="true"></i><span><?=(new DateTimeImmutable($week['end']))->format('d.m')?></span></span><div class="fm2-chart-week__bars"><?php $bar('/pilot/objects?chart=planned-start&bucket='.$i,$weekTitle.' — Плановые начала — '.$label.': '.$week['starts'],'Плановые начала',$week['starts'],$weekMax,'planned-start',false);$bar('/pilot/objects?chart=planned-finish&bucket='.$i,$weekTitle.' — Плановые окончания — '.$label.': '.$week['finishes'],'Плановые окончания',$week['finishes'],$weekMax,'planned-finish',false);?></div></div><?php endforeach ?>
        </div></div></section>
        <section class="shlz-chart-widget fm2-dashboard-chart" data-dashboard-chart="start-risk"><header class="shlz-chart-widget__header"><div><h2><?=$riskTitle?></h2><p>Плановый старт уже наступил или ожидается в течение 14 дней</p></div></header><div class="shlz-chart-widget__plot"><div class="fm2-chart-bars fm2-chart-bars--five fm2-chart-bars--risk">
<?php $riskTones=['overdue_start'=>'risk-overdue','order_0_7'=>'risk-high','order_8_14'=>'risk-medium','ready_0_14'=>'risk-ready','opened_0_14'=>'risk-opened'];foreach($charts['startRisk'] as$item)$bar('/pilot/objects?chart=start-risk&bucket='.$item['key'],$riskTitle.' — '.$item['label'].': '.$item['value'],$item['label'],$item['value'],$riskMax,$riskTones[$item['key']]); ?>
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

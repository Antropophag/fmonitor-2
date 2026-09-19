<?php declare(strict_types=1);use yii\helpers\Html;
$rub=static fn(int$c):string=>($c<0?'−':'').number_format(abs($c)/100,2,',',' ').' ₽';
$date=static fn(string$v):string=>preg_match('/^(\d{4})-(\d{2})-(\d{2})/D',$v,$m)?$m[3].'.'.$m[2].'.'.$m[1]:Html::encode($v);
if($rows===[]): ?>
<section class="fm2-otiz-empty" data-empty="<?= Html::encode($context) ?>"><h2><?= Html::encode($listTitle) ?></h2><p>Расчётных периодов пока нет. Подготовьте первый расчёт на вкладке выплат.</p></section>
<?php else: ?>
<section class="fm2-otiz-snapshots"><h2><?= Html::encode($listTitle) ?></h2><ul>
<?php foreach($rows as$row): ?><li><a href="/pilot/otiz/snapshots/<?= (int)$row['id'] ?>"><span>Расчёт на <?= $date((string)$row['report_date']) ?></span><strong><?= $rub((int)$row['total_pool_cents']) ?></strong></a></li><?php endforeach ?>
</ul></section>
<?php endif ?>

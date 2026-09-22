<?php
declare(strict_types=1);
use yii\helpers\Html;
$tabs=['objects'=>['/pilot/otiz/objects','Экономика объектов'],'payments'=>['/pilot/otiz/payments','Выполнение расчёта'],'history'=>['/pilot/otiz/history','Архив расчётов']];
$active=$current==='snapshot'?'payments':$current;
?>
<nav class="fm2-otiz-tabs" data-otiz-tabs aria-label="Разделы ОТиЗ">
<?php foreach($tabs as$key=>[$href,$label]): ?><a data-otiz-tab="<?= Html::encode($current==='snapshot'&&$key==='payments'?'snapshot':$key) ?>" href="<?= Html::encode($href) ?>"<?= $key===$active?' aria-current="page"':'' ?>><?= Html::encode($label) ?></a><?php endforeach ?>
</nav>

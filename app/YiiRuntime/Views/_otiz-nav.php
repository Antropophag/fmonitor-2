<?php
declare(strict_types=1);
use yii\helpers\Html;
$tabs=['objects'=>['/pilot/otiz/objects','Экономика объектов'],'payments'=>['/pilot/otiz/payments','Подготовка выплат'],'history'=>['/pilot/otiz/history','Архив расчётов']];
if($current==='snapshot')$tabs['snapshot']=[Yii::$app->request->url,'Текущий расчёт'];
?>
<nav class="fm2-otiz-tabs" data-otiz-tabs aria-label="Разделы ОТиЗ">
<?php foreach($tabs as$key=>[$href,$label]): ?><a data-otiz-tab="<?= Html::encode($key) ?>" href="<?= Html::encode($href) ?>"<?= $key===$current?' aria-current="page"':'' ?>><?= Html::encode($label) ?></a><?php endforeach ?>
</nav>

<?php declare(strict_types=1);
use FMonitor2\YiiRuntime\ViewSupport;
use yii\helpers\Html;
ViewSupport::begin($this, "Обращение сохранено", $identity);
?><div class="fm2-page-header"><div><h1>Обращение сохранено</h1><p>Номер обращения: <?= intval(
    $id,
) ?></p></div></div><a class="shlz-button shlz-button--primary" href="<?= Html::encode($pagePath) ?>">Вернуться</a><?php ViewSupport::end(
    $this,
);

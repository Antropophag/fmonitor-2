<?php

declare(strict_types=1);

use FMonitor2\YiiRuntime\ViewSupport;
use yii\helpers\Html;

ViewSupport::begin($this, $title, $identity);
?>
<nav class="fm2-breadcrumb" aria-label="Хлебные крошки"><a class="fm2-breadcrumb-link" href="/pilot/objects">Объекты монтажа</a><span aria-hidden="true">/</span><span aria-current="page"><?= Html::encode($title) ?></span></nav>
<div class="fm2-page-header fm2-order-heading"><div><h1><?= Html::encode($title) ?></h1><p role="alert"><?= Html::encode($message) ?></p></div></div>
<section class="fm2-order-surface"><div class="fm2-order-team"><h2><?= $retryable ? 'Данные временно недоступны' : 'Действие не выполнено' ?></h2><p><?= $retryable ? 'Подождите немного и повторите переход. Внесённые ранее факты не изменены.' : 'Вернитесь к предыдущему экрану, проверьте актуальные данные и повторите действие при необходимости.' ?></p></div><footer class="fm2-order-actions"><a class="shlz-link" href="<?= Html::encode($backUrl) ?>"><?= Html::encode($backLabel) ?></a><?php if ($retryable): ?><a class="shlz-button shlz-button--primary" href="<?= Html::encode($backUrl) ?>">Повторить</a><?php endif ?></footer></section>
<?php ViewSupport::end($this);

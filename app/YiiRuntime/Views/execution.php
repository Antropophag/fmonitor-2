<?php

declare(strict_types=1);

use FMonitor2\YiiRuntime\ViewSupport;
use yii\helpers\Html;

$latest = $model['latest'] ?? null;
ViewSupport::begin($this, 'Применение состава и открытие работ', $identity);
?>
<nav class="fm2-breadcrumb" aria-label="Хлебные крошки"><a class="fm2-breadcrumb-link" href="/pilot/objects">Объекты монтажа</a><span aria-hidden="true">/</span><a class="fm2-breadcrumb-link" href="/pilot/objects/<?= (int) $objectId ?>">Объект № <?= (int) $objectId ?></a><span aria-hidden="true">/</span><span aria-current="page">Открытие работ</span></nav>
<div class="fm2-page-header fm2-order-heading"><div><h1>Применение состава и открытие работ</h1><p>Текущий состав и принятый оригинал проверяются владельцем операции.</p></div></div>
<section class="fm2-order-surface">
    <header><div class="fm2-order-object"><strong>Объект монтажа № <?= (int) $objectId ?></strong><span><?= Html::encode($model['object']['address']) ?></span><small><?= Html::encode($model['object']['objectRegistrationNumber']) ?></small></div></header>
    <?php if ($latest): ?><div class="fm2-order-team"><div class="fm2-order-section-head"><div><h2>Выбранный состав · версия <?= (int) $latest['version'] ?></h2><p><?= $latest['hasAcceptedOriginal'] ? 'Подписанный оригинал принят' : 'Ожидается подписанный оригинал' ?></p></div></div><?php foreach ($latest['installers'] as $installer): ?><div class="fm2-object-installer"><strong><?= Html::encode($installer['fullName']) ?></strong><span><?= Html::encode($installer['position']) ?></span></div><?php endforeach ?><p class="fm2-order-engineer-line"><strong>Инженер строительного контроля</strong><span><?= Html::encode($latest['engineer']['fullName']) ?></span></p></div><?php else: ?><div class="fm2-order-team"><h2>Состав ещё не выбран</h2><p>Сначала выберите монтажников и инженера строительного контроля.</p></div><?php endif ?>
    <footer class="fm2-order-actions"><a class="shlz-link" href="/pilot/objects/<?= (int) $objectId ?>">К карточке объекта</a><a class="shlz-link" href="/pilot/objects/<?= (int) $objectId ?>/assignment-order/selection">Выбранный состав и оригинал</a><?php if ($latest && !$latest['hasAcceptedOriginal']): ?><a class="shlz-button shlz-button--primary" href="/pilot/objects/<?= (int) $objectId ?>/assignment-orders/<?= (int) $latest['orderId'] ?>/originals/submit">Загрузить оригинал</a><?php endif ?></footer>
</section>
<?php ViewSupport::end($this);

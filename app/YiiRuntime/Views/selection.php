<?php

declare(strict_types=1);

use FMonitor2\YiiRuntime\ViewSupport;
use yii\helpers\Html;

$id = (int) $objectId;
$latest = $latest ?? null;
$selected = $latest['installers'] ?? [];
$engineer = $latest['engineer']['userId'] ?? null;
$pending = $latest !== null && !$latest['hasAcceptedOriginal'];
$canUpload = $canUpload ?? false;
$canCorrect = $canCorrect ?? false;
$canReadOriginal = $canReadOriginal ?? false;
$path = '/pilot/objects/' . $id . '/assignment-order/selection';
ViewSupport::begin($this, $pending ? 'Изменить состав распоряжения' : 'Выбрать состав распоряжения', $identity);
?>
<nav class="fm2-breadcrumb" aria-label="Хлебные крошки"><a class="fm2-breadcrumb-link" href="/pilot/objects">Объекты монтажа</a><span aria-hidden="true">/</span><a class="fm2-breadcrumb-link" href="/pilot/objects/<?= $id ?>"><?= Html::encode($object['objectRegistrationNumber']) ?></a><span aria-hidden="true">/</span><span aria-current="page">Состав</span></nav>
<div class="fm2-page-header fm2-order-heading"><div><h1><?= $pending ? 'Изменить состав распоряжения' : 'Выбрать состав распоряжения' ?></h1><p><?= Html::encode($object['address']) ?> · <?= Html::encode($object['objectRegistrationNumber']) ?></p></div></div>
<?php if ($latest): ?>
    <form method="post" action="/pilot/objects/<?= $id ?>/assignment-orders/<?= (int) $latest['orderId'] ?>/template" target="_blank"><?= Html::hiddenInput('_csrf', $csrf) ?><button class="shlz-button" type="submit">Сформировать шаблон</button></form>
<?php endif ?>
<form class="fm2-order-form" method="post" action="<?= $path ?>" data-selection-picker data-search-url="/pilot/objects/<?= $id ?>/assignment-order/installers">
    <?= Html::hiddenInput('_csrf', $csrf) ?>
    <?= Html::hiddenInput('requestId', ViewSupport::uuid()) ?>
    <?= Html::hiddenInput('mode', $pending ? 'replace_pending' : 'new_order') ?>
    <?= Html::hiddenInput('expectedSelectionRevision', $selectionRevision) ?>
    <fieldset class="fm2-order-surface">
        <header><div class="fm2-order-object"><strong>Объект монтажа № <?= $id ?></strong><span><?= Html::encode($object['objectRegistrationNumber']) ?></span><small>Состав сохраняется отдельно от файла оригинала</small></div></header>
        <?php if ($latest): ?><div class="fm2-order-current"><div><strong>Текущий состав · версия <?= (int) $latest['version'] ?></strong><span><?= Html::encode(implode(', ', array_column($latest['installers'], 'fullName'))) ?></span><small><?= $pending ? 'Ожидается подписанный оригинал' : 'Подписанный оригинал принят' ?></small></div><div><?php if ($pending && $canUpload): ?><a class="shlz-link" href="/pilot/objects/<?= $id ?>/assignment-orders/<?= (int) $latest['orderId'] ?>/originals/submit">Загрузить оригинал</a><?php elseif (!$pending && $canCorrect): ?><a class="shlz-link" href="/pilot/objects/<?= $id ?>/assignment-orders/<?= (int) $latest['orderId'] ?>/originals/submit">Исправить оригинал</a><?php endif ?><?php if (!$pending && $canReadOriginal): ?><a class="shlz-link" href="/pilot/objects/<?= $id ?>/assignment-orders/<?= (int) $latest['orderId'] ?>/originals/history">История оригинала</a><?php endif ?></div></div><?php endif ?>
        <section class="fm2-order-team" aria-labelledby="installers-title"><div class="fm2-order-section-head"><div><h2 id="installers-title">Монтажники</h2><p>Выберите одного или нескольких сотрудников кадрового каталога.</p></div></div>
            <div class="fm2-order-selection"><div class="fm2-picker-selection" data-main-selection aria-live="polite"><?php if (!$selected): ?><span class="fm2-picker-selection-empty" data-selection-empty>Монтажники ещё не выбраны</span><?php endif ?><?php foreach ($selected as $installer): ?><span class="fm2-picker-chip" data-selected-installer data-tab-id="<?= (int) $installer['tabId'] ?>" data-full-name="<?= Html::encode($installer['fullName']) ?>" data-position="<?= Html::encode($installer['position']) ?>"><span><?= Html::encode($installer['fullName']) ?></span><small>№ <?= str_pad((string) $installer['tabId'], 6, '0', STR_PAD_LEFT) ?></small><button type="button" data-remove-installer aria-label="Убрать <?= Html::encode($installer['fullName']) ?>">×</button><?= Html::hiddenInput('installerTabIds[]', $installer['tabId']) ?></span><?php endforeach ?></div><button class="shlz-button shlz-button--primary" type="button" data-dialog-open>Выбрать монтажников</button></div>
        </section>
        <section class="fm2-order-engineer"><div><span>Инженер строительного контроля</span><small>Выберите одного ответственного и подтвердите выбор</small></div><div><fieldset><?php foreach ($engineers as $candidate): ?><label class="shlz-choice"><input class="shlz-radio" type="radio" name="controlEngineerUserId" value="<?= (int) $candidate['userId'] ?>"<?= (int) $candidate['userId'] === (int) $engineer ? ' checked' : '' ?> required><span><?= Html::encode($candidate['fullName']) ?> · <?= Html::encode($candidate['position']) ?></span></label><?php endforeach ?></fieldset><label class="shlz-choice"><input class="shlz-checkbox" type="checkbox" name="controlEngineerConfirmed" value="yes" required><span>Подтверждаю выбор инженера</span></label></div></section>
        <footer class="fm2-order-actions"><a class="shlz-link" href="/pilot/objects/<?= $id ?>">Отмена</a><button class="shlz-button shlz-button--primary" type="submit"><?= $pending ? 'Заменить ожидающий состав' : 'Сохранить состав' ?></button></footer>
    </fieldset>
    <aside class="fm2-order-helper"><div class="fm2-order-helper-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3v18M3 12h18"/></svg></div><div><h2>Следующий шаг</h2><p>После сохранения можно сформировать PDF-шаблон или сразу загрузить подписанный оригинал.</p></div><?php if ($latest && $pending && $canUpload): ?><a class="shlz-link" href="/pilot/objects/<?= $id ?>/assignment-orders/<?= (int) $latest['orderId'] ?>/originals/submit">Перейти к загрузке</a><?php endif ?></aside>
    <dialog class="shlz-modal fm2-selection-modal" data-installer-dialog aria-labelledby="installer-dialog-title"><div class="shlz-modal__surface"><header class="shlz-modal__header"><div><h2 class="shlz-modal__title" id="installer-dialog-title">Выбор монтажников</h2><p>Поиск по ФИО или табельному номеру</p></div><button class="shlz-modal__close" type="button" data-dialog-close aria-label="Закрыть">×</button></header><div class="shlz-modal__body"><div class="fm2-picker-modal-selection"><strong data-picker-count>Выбрано: <?= count($selected) ?></strong><div class="fm2-picker-selection" data-modal-selection></div></div><label class="fm2-search-field"><span>Поиск монтажника</span><input class="shlz-input" type="search" data-installer-search placeholder="Введите минимум 2 символа" autocomplete="off"></label><p class="fm2-picker-result-meta" data-installer-status aria-live="polite">Введите минимум 2 символа</p><div class="fm2-picker-results" data-installer-results></div><button class="shlz-button" type="button" data-installer-more hidden>Показать ещё</button></div><footer class="shlz-modal__footer"><button class="shlz-button shlz-button--primary" type="button" data-dialog-apply>Готово</button></footer></div></dialog>
</form>
<?php ViewSupport::end($this);

<?php

declare(strict_types=1);

use FMonitor2\YiiRuntime\ViewSupport;
use yii\helpers\Html;

$id = (int) $objectId;
$latest = $latest ?? null;
$selected = $latest['installers'] ?? [];
$assignment = $currentAssignment ?? ['status'=>'missing','revision'=>0,'engineer'=>null];
$engineer = $assignment['engineer']['userId'] ?? null;
$pending = $latest !== null && !$latest['hasAcceptedOriginal'];
$canUpload = $canUpload ?? false;
$canCorrect = $canCorrect ?? false;
$canReadOriginal = $canReadOriginal ?? false;
$path = '/pilot/objects/' . $id . '/assignment-order/selection';
$savedOrderId = $latest === null ? null : (int) $latest['orderId'];
$savedInstallerIds = array_values(array_unique(array_filter(
    array_map(static fn (array $installer): int => (int) ($installer['tabId'] ?? 0), $selected),
    static fn (int $installerId): bool => $installerId > 0,
)));
sort($savedInstallerIds, SORT_NUMERIC);
$savedEngineerId = (int) ($latest['engineer']['userId'] ?? 0);
$currentEngineerId = (int) ($engineer ?? 0);
ViewSupport::begin($this, $pending ? 'Изменить состав распоряжения' : 'Выбрать состав распоряжения', $identity);
?>
<nav class="fm2-breadcrumb" aria-label="Хлебные крошки"><a class="fm2-breadcrumb-link" href="/pilot/objects">Объекты монтажа</a><span aria-hidden="true">/</span><a class="fm2-breadcrumb-link" href="/pilot/objects/<?= $id ?>"><?= Html::encode($object['objectRegistrationNumber']) ?></a><span aria-hidden="true">/</span><span aria-current="page">Состав</span></nav>
<div class="fm2-page-header fm2-order-heading"><div><h1><?= $pending ? 'Изменить состав распоряжения' : 'Выбрать состав распоряжения' ?></h1><p><?= Html::encode($object['address']) ?> · <?= Html::encode($object['objectRegistrationNumber']) ?></p></div></div>
<div class="fm2-order-form" data-selection-picker data-search-url="/pilot/objects/<?= $id ?>/assignment-order/installers">
<form method="post" action="<?= $path ?>">
    <?= Html::hiddenInput('_csrf', $csrf) ?>
    <?= Html::hiddenInput('requestId', ViewSupport::uuid()) ?>
    <?= Html::hiddenInput('mode', $pending ? 'replace_pending' : 'new_order') ?>
    <?= Html::hiddenInput('expectedSelectionRevision', $selectionRevision) ?>
    <?php if ($assignment['status']==='found'): ?><?= Html::hiddenInput('expectedControlEngineerAssignmentRevision', $assignment['revision']) ?><?php endif ?>
    <fieldset class="fm2-order-surface">
        <header><div class="fm2-order-object"><strong>Объект монтажа № <?= $id ?></strong><span><?= Html::encode($object['objectRegistrationNumber']) ?></span><small>Состав сохраняется отдельно от файла оригинала</small></div></header>
        <?php if ($latest): ?><div class="fm2-order-current"><div><strong>Текущий состав · версия <?= (int) $latest['version'] ?></strong><span><?= Html::encode(implode(', ', array_column($latest['installers'], 'fullName'))) ?></span><small><?= $pending ? 'Ожидается подписанный оригинал' : 'Подписанный оригинал принят' ?></small></div><div><?php if ($pending && $canUpload): ?><a class="shlz-link" href="/pilot/objects/<?= $id ?>/assignment-orders/<?= (int) $latest['orderId'] ?>/originals/submit">Загрузить оригинал</a><?php elseif (!$pending && $canCorrect): ?><a class="shlz-link" href="/pilot/objects/<?= $id ?>/assignment-orders/<?= (int) $latest['orderId'] ?>/originals/submit">Исправить оригинал</a><?php endif ?><?php if (!$pending && $canReadOriginal): ?><a class="shlz-link" href="/pilot/objects/<?= $id ?>/assignment-orders/<?= (int) $latest['orderId'] ?>/originals/history">История оригинала</a><?php endif ?></div></div><?php endif ?>
        <section class="fm2-order-team" aria-labelledby="installers-title"><div class="fm2-order-section-head"><div><h2 id="installers-title">Монтажники</h2><p>Выберите одного или нескольких сотрудников кадрового каталога.</p></div></div>
            <div class="fm2-order-selection"><div class="fm2-picker-selection" data-main-selection aria-live="polite"><?php if (!$selected): ?><span class="fm2-picker-selection-empty" data-selection-empty>Монтажники ещё не выбраны</span><?php endif ?><?php foreach ($selected as $installer): ?><span class="fm2-picker-chip" data-selected-installer data-tab-id="<?= (int) $installer['tabId'] ?>" data-full-name="<?= Html::encode($installer['fullName']) ?>" data-position="<?= Html::encode($installer['position']) ?>"><span><?= Html::encode($installer['fullName']) ?></span><small>№ <?= str_pad((string) $installer['tabId'], 6, '0', STR_PAD_LEFT) ?></small><button type="button" data-remove-installer aria-label="Убрать <?= Html::encode($installer['fullName']) ?>">×</button><?= Html::hiddenInput('installerTabIds[]', $installer['tabId']) ?></span><?php endforeach ?></div><button class="shlz-button shlz-button--primary" type="button" data-dialog-open>Выбрать монтажников</button></div>
        </section>
        <section class="fm2-order-engineer"><div><span>Инженер строительного контроля</span><small>Закрепляется отдельно в карточке объекта</small></div><div><?php if($assignment['status']==='found'): ?><strong><?= Html::encode($assignment['engineer']['fullName']) ?></strong><small><?= Html::encode($assignment['engineer']['position']) ?> · <?= Html::encode($assignment['provenance']) ?></small><?php else: ?><p>Сначала закрепите инженера в карточке объекта</p><?php endif ?></div></section>
        <footer class="fm2-order-actions"><a class="shlz-link" href="/pilot/objects/<?= $id ?>">Отмена</a><button class="shlz-button shlz-button--primary" type="submit"<?= $assignment['status']==='found'?'':' disabled' ?>><?= $pending ? 'Заменить ожидающий состав' : 'Сохранить состав' ?></button></footer>
    </fieldset>
</form>
    <aside class="fm2-order-helper" data-template-offer data-saved-order-id="<?= $savedOrderId ?? '' ?>" data-saved-installer-ids="<?= Html::encode(implode(',', $savedInstallerIds)) ?>" data-saved-engineer-id="<?= $savedEngineerId > 0 ? $savedEngineerId : '' ?>" data-current-engineer-id="<?= $currentEngineerId > 0 ? $currentEngineerId : '' ?>" hidden inert><div class="fm2-order-helper-icon" aria-hidden="true"><img src="/pilot/assets/shlz-icons/plus-alt-2.svg" data-shlz-icon="plus-alt-2" alt=""></div><div><h2>Следующий шаг</h2><p data-template-offer-exact>Состав сохранён. PDF-шаблон можно сформировать или сразу перейти к загрузке подписанного оригинала.</p><p data-template-offer-save-first>Сначала сохраните состав. После сохранения можно сформировать PDF-шаблон или сразу загрузить оригинал.</p></div><?php if ($latest): ?><form method="post" action="/pilot/objects/<?= $id ?>/assignment-orders/<?= (int) $latest['orderId'] ?>/template" target="_blank"><?= Html::hiddenInput('_csrf', $csrf) ?><button class="shlz-button" type="submit" disabled>Сформировать шаблон</button></form><?php endif ?><?php if ($latest && $pending && $canUpload): ?><a class="shlz-link" href="/pilot/objects/<?= $id ?>/assignment-orders/<?= (int) $latest['orderId'] ?>/originals/submit">Перейти к загрузке</a><?php endif ?></aside>
</div>
<dialog class="shlz-modal fm2-selection-modal" data-installer-dialog aria-labelledby="installer-dialog-title"><div class="shlz-modal__surface"><header class="shlz-modal__header"><div><h2 class="shlz-modal__title" id="installer-dialog-title">Выбор монтажников</h2><p>Поиск по ФИО или табельному номеру</p></div><button class="shlz-modal__close" type="button" data-dialog-close aria-label="Закрыть">×</button></header><div class="shlz-modal__body"><div class="fm2-picker-modal-selection"><strong data-picker-count>Выбрано: <?= count($selected) ?></strong><div class="fm2-picker-selection" data-modal-selection></div></div><label class="fm2-search-field"><span>Поиск монтажника</span><input class="shlz-input" type="search" data-installer-search placeholder="Введите минимум 2 символа" autocomplete="off"></label><p class="fm2-picker-result-meta" data-installer-status aria-live="polite">Введите минимум 2 символа</p><div class="fm2-picker-results" data-installer-results></div><button class="shlz-button" type="button" data-installer-more hidden>Показать ещё</button></div><footer class="shlz-modal__footer"><button class="shlz-button shlz-button--primary" type="button" data-dialog-apply>Готово</button></footer></div></dialog>
<?php ViewSupport::end($this);

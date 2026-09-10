<?php

declare(strict_types=1);

use FMonitor2\YiiRuntime\ViewSupport;
use yii\helpers\Html;

$title = $current ? 'Исправить оригинал' : 'Загрузить оригинал';
$path = '/pilot/objects/' . (int) $objectId . '/assignment-orders/' . (int) $orderId . '/originals';
ViewSupport::begin($this, $title, $identity);
?>
<nav class="fm2-breadcrumb" aria-label="Хлебные крошки"><a class="fm2-breadcrumb-link" href="/pilot/objects">Объекты монтажа</a><span aria-hidden="true">/</span><a class="fm2-breadcrumb-link" href="/pilot/objects/<?= (int) $objectId ?>">Объект № <?= (int) $objectId ?></a><span aria-hidden="true">/</span><span aria-current="page"><?= $title ?></span></nav>
<div class="fm2-page-header fm2-order-heading"><div><h1><?= $title ?></h1><p>Проверьте выбранный состав и приложите оформленный документ.</p></div></div>
<form class="fm2-order-form" action="<?= $path ?>" data-original-upload-form data-return-url="/pilot/objects/<?= (int) $objectId ?>">
    <?= Html::hiddenInput('csrfToken', $csrf) ?>
    <?= Html::hiddenInput('requestId', ViewSupport::uuid()) ?>
    <?= Html::hiddenInput('mode', $mode) ?>
    <?= Html::hiddenInput('rootOriginalId', $current['rootId'] ?? '') ?>
    <?= Html::hiddenInput('targetRevisionId', $current['revisionId'] ?? '') ?>
    <?= Html::hiddenInput('expectedCurrentRevisionId', $current['revisionId'] ?? '') ?>
    <?php if (!$current): ?><?= Html::hiddenInput('correctionReason', '') ?><?php endif ?>
    <fieldset class="fm2-order-surface" data-original-fields disabled>
        <header><div class="fm2-order-object"><strong>Объект монтажа № <?= (int) $objectId ?></strong><span>Распоряжение · версия <?= (int) ($orderVersion ?? 0) ?></span><small>Выбранный состав сохранён отдельно от файла оригинала</small></div></header>
        <?php if ($current): ?><div class="fm2-order-current"><div><strong>Принятый оригинал · редакция <?= (int) $current['revisionNumber'] ?></strong><span>Дата документа: <?= Html::encode($current['documentDate']) ?></span><small>Предыдущий файл и дата сохранятся в истории.</small></div><a class="shlz-link" href="<?= $path ?>/history">История и PDF</a></div><?php endif ?>
        <section class="fm2-order-upload" aria-labelledby="signed-original"><div class="fm2-order-section-head"><div><h2 id="signed-original">Подписанный оригинал</h2><p>Загрузите один PDF-файл и укажите дату, напечатанную в документе.</p></div><span class="shlz-status shlz-status--blue">До 20 МиБ</span></div><label class="fm2-file-drop" data-file-drop><input type="file" name="original" accept="application/pdf,.pdf" required><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5M5 14v5h14v-5"/></svg><span><strong><u>Выберите</u> или перетащите сюда файл</strong><small data-file-name>PDF, не более 20 МиБ</small></span></label></section>
        <section class="fm2-order-team"><div class="fm2-order-section-head"><div><h2>Выбранный состав</h2><p><?= Html::encode(implode(', ', array_column($composition['installers'], 'fullName'))) ?></p></div></div><?php foreach ($composition['installers'] as $installer): ?><div class="fm2-object-installer"><strong><?= Html::encode($installer['fullName']) ?></strong><span><?= Html::encode($installer['position']) ?></span></div><?php endforeach ?><p class="fm2-order-engineer-line"><strong>Инженер строительного контроля</strong><span><?= Html::encode($composition['engineer']['fullName']) ?></span></p></section>
        <section class="fm2-order-team fm2-original-details"><label class="shlz-field"><span class="shlz-field__label">Дата распоряжения</span><span class="shlz-field__control"><input class="shlz-input" type="date" name="documentDate" value="<?= Html::encode($suggestedDocumentDate) ?>" required></span><span class="shlz-field__secondary">Проверьте дату, напечатанную в оригинале.</span></label><?php if ($current): ?><label class="shlz-field"><span class="shlz-field__label">Причина исправления</span><span class="shlz-field__control"><input class="shlz-input" name="correctionReason" maxlength="500" required></span><span class="shlz-field__secondary">Кратко укажите, почему выпускается новая редакция.</span></label><?php endif ?><label class="shlz-choice"><input class="shlz-checkbox" type="checkbox" name="compositionConfirmed" required><span>Подтверждаю соответствие оригинала выбранному составу</span></label><p>Загрузка сохраняет оригинал. Открытие работ выполняется отдельно.</p></section>
        <footer class="fm2-order-actions"><a class="shlz-link" href="/pilot/objects/<?= (int) $objectId ?>/assignment-order/selection">К составу</a><button class="shlz-button shlz-button--primary" type="submit" data-original-submit><?= $title ?></button></footer>
    </fieldset>
    <aside class="fm2-order-helper"><div class="fm2-order-helper-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3v18M3 12h18"/></svg></div><div><h2>После загрузки</h2><p>После успешного ответа откроется карточка объекта. При обрыве связи файл и данные останутся в форме для безопасного повтора.</p></div><a class="shlz-link" href="/pilot/objects/<?= (int) $objectId ?>">К карточке объекта</a></aside>
    <noscript><p role="alert">Для загрузки оригинала включите JavaScript и обновите страницу.</p></noscript><p class="fm2-original-status" role="status" aria-live="polite" data-original-status></p>
</form>
<?php ViewSupport::end($this);

<?php

declare(strict_types=1);

use yii\helpers\Html;

$pto = $completion['pto_act'];
$declaration = $completion['declaration'];
$at85 = in_array($status, ['Документарное закрытие', 'Работы завершены'], true);
$progress = (int) ($completionProgress ?? ($at85 ? 85 : 0));
$today = Html::encode($today);
$renderHistory = static function (string $type, array $fact): void {
    $title = $type === 'pto_act' ? 'Акт ПТО' : 'Декларация';
    ?><details class="fm2-detail-group"><summary><?= Html::encode($title) ?> · история</summary><ol><?php
    foreach ($fact['history'] as $row): ?>
        <li class="fm2-event" data-completion-history="<?= Html::encode($type) ?>" data-completion-version="<?= (int) $row['version'] ?>">
            <div><strong><?= $row['version'] === 0 ? 'Исходная запись' : 'Исправление ' . (int) $row['version'] ?></strong>
                <span>Дата: <?= Html::encode($row['date']) ?><?php if ($type === 'declaration'): ?> · Реквизиты: <?= Html::encode($row['details']) ?><?php endif ?></span>
                <?php if ($row['reason'] !== null): ?><span>Причина: <?= Html::encode($row['reason']) ?></span><?php endif ?>
                <small><?= Html::encode($row['actorName']) ?> · ID <?= (int) $row['actorId'] ?> · <time datetime="<?= Html::encode($row['recordedAt']) ?>"><?= Html::encode($row['recordedAt']) ?></time></small>
            </div>
        </li>
    <?php endforeach ?></ol></details><?php
};
?>
<section id="completion" class="fm2-panel fm2-completion">
    <header><div><h2>Документарное закрытие</h2><p>Последние 15% закрываются актом ПТО и декларацией.</p></div><strong><?= $progress ?>%</strong></header>
    <div class="fm2-completion-track" aria-hidden="true"><progress class="fm2-completion-track__segment" max="85" value="<?= min(85, $progress) ?>"></progress><progress class="fm2-completion-track__segment fm2-completion-track__segment--documents" max="15" value="<?= max(0, $progress - 85) ?>"></progress></div>
    <?php if (!$at85): ?>
        <p>Сначала завершите монтажные работы до 85% в чек-листе.</p><a class="shlz-link" href="/pilot/objects/<?= (int) $id ?>/checklist">Перейти к чек-листу</a>
    <?php elseif ($pto === null && $canRecordPto): ?>
        <form class="fm2-completion-form" method="post" action="/pilot/objects/<?= (int) $id ?>/completion"><?= Html::hiddenInput('_csrf', $csrf) ?><?= Html::hiddenInput('action', 'record_pto') ?><label class="shlz-field"><span class="shlz-field__label">Дата акта ПТО</span><input class="shlz-input" type="date" name="ptoActDate" required max="<?= $today ?>"></label><button class="shlz-button shlz-button--primary" type="submit">Зафиксировать акт ПТО</button></form>
    <?php elseif ($pto !== null && $declaration === null && $canRecordDeclaration): ?>
        <form class="fm2-completion-form" method="post" action="/pilot/objects/<?= (int) $id ?>/completion"><?= Html::hiddenInput('_csrf', $csrf) ?><?= Html::hiddenInput('action', 'record_declaration') ?><label class="shlz-field"><span class="shlz-field__label">Дата декларации</span><input class="shlz-input" type="date" name="declarationDate" required max="<?= $today ?>"></label><label class="shlz-field fm2-completion-details"><span class="shlz-field__label">Реквизиты декларации</span><input class="shlz-input" name="declarationDetails" required maxlength="500"></label><button class="shlz-button shlz-button--primary" type="submit">Добавить декларацию</button></form>
    <?php elseif ($pto !== null && $declaration !== null): ?><p>Документы зафиксированы. Монтажные работы завершены.</p><?php endif ?>
    <?php if ($pto !== null): ?><p><strong>Акт ПТО:</strong> <?= Html::encode($pto['date']) ?></p><?php $renderHistory('pto_act', $pto); ?>
        <?php if ($canCorrectPto): ?><details><summary>Исправить акт ПТО</summary><form class="fm2-completion-form" method="post" action="/pilot/objects/<?= (int) $id ?>/completion"><?= Html::hiddenInput('_csrf', $csrf) ?><?= Html::hiddenInput('action', 'correct_pto') ?><?= Html::hiddenInput('factId', $pto['id']) ?><label class="shlz-field"><span class="shlz-field__label">Исправленная дата акта ПТО</span><input class="shlz-input" type="date" name="ptoActDate" value="<?= Html::encode($pto['date']) ?>" required max="<?= $today ?>"></label><label class="shlz-field"><span class="shlz-field__label">Причина исправления</span><textarea class="shlz-input" name="reason" required maxlength="1000"></textarea></label><button class="shlz-button shlz-button--primary" type="submit">Сохранить исправление</button></form></details><?php endif ?>
    <?php endif ?>
    <?php if ($declaration !== null): ?><p><strong>Декларация:</strong> <?= Html::encode($declaration['date']) ?> · <?= Html::encode($declaration['details']) ?></p><?php $renderHistory('declaration', $declaration); ?>
        <?php if ($canCorrectDeclaration): ?><details><summary>Исправить декларацию</summary><form class="fm2-completion-form" method="post" action="/pilot/objects/<?= (int) $id ?>/completion"><?= Html::hiddenInput('_csrf', $csrf) ?><?= Html::hiddenInput('action', 'correct_declaration') ?><?= Html::hiddenInput('factId', $declaration['id']) ?><label class="shlz-field"><span class="shlz-field__label">Исправленная дата декларации</span><input class="shlz-input" type="date" name="declarationDate" value="<?= Html::encode($declaration['date']) ?>" required max="<?= $today ?>"></label><label class="shlz-field fm2-completion-details"><span class="shlz-field__label">Исправленные реквизиты</span><input class="shlz-input" name="declarationDetails" value="<?= Html::encode($declaration['details']) ?>" maxlength="500"></label><label class="shlz-field"><span class="shlz-field__label">Причина исправления</span><textarea class="shlz-input" name="reason" required maxlength="1000"></textarea></label><button class="shlz-button shlz-button--primary" type="submit">Сохранить исправление</button></form></details><?php endif ?>
    <?php endif ?>
</section>

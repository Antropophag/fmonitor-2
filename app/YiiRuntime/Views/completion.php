<?php

declare(strict_types=1);

use yii\helpers\Html;

$pto = $completion['pto_act'];
$declaration = $completion['declaration'];
$at85 = in_array($status, ['Документарное закрытие', 'Работы завершены'], true);
$progress = (int) ($completionProgress ?? ($at85 ? 85 : 0));
$today = Html::encode($today);
$state = is_array($completionFormState ?? null) ? $completionFormState : null;
$submittedAction = (string) ($state['action'] ?? '');
$submittedValues = is_array($state['values'] ?? null) ? $state['values'] : [];
$errorField = is_string($state['field'] ?? null) ? $state['field'] : null;
$errorMessage = is_string($state['message'] ?? null) ? $state['message'] : '';
$value = static fn (string $action, string $name, string $default): string => $submittedAction === $action && array_key_exists($name, $submittedValues)
    ? (string) $submittedValues[$name] : $default;
$errorId = static fn (string $action, string $name): string => 'completion-' . str_replace('_', '-', $action) . '-' . $name . '-error';
$fieldAttributes = static function (string $action, string $name) use ($submittedAction, $errorField, $errorId): string {
    if ($submittedAction !== $action || $errorField !== $name) return '';
    return ' aria-invalid="true" aria-describedby="' . $errorId($action, $name) . '" data-completion-focus="true"';
};
$fieldError = static function (string $action, string $name) use ($submittedAction, $errorField, $errorMessage, $errorId): string {
    if ($submittedAction !== $action || $errorField !== $name) return '';
    return '<span class="fm2-completion-field-error" id="' . $errorId($action, $name) . '" role="alert">' . Html::encode($errorMessage) . '</span>';
};
$generalError = static function (string $action) use ($submittedAction, $errorField, $errorMessage): string {
    if ($submittedAction !== $action || $errorField !== null) return '';
    return '<p class="fm2-completion-error" role="alert" tabindex="-1" data-completion-focus="true">' . Html::encode($errorMessage) . '</p>';
};
$submittedFormVisible = match ($submittedAction) {
    'record_pto' => $at85 && $pto === null && $canRecordPto,
    'record_declaration' => $at85 && $pto !== null && $declaration === null && $canRecordDeclaration,
    'correct_pto' => $pto !== null && $canCorrectPto,
    'correct_declaration' => $declaration !== null && $canCorrectDeclaration,
    default => false,
};
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
    <?php if ($errorMessage !== '' && !$submittedFormVisible): ?><p class="fm2-completion-error" role="alert" tabindex="-1" data-completion-focus="true"><?= Html::encode($errorMessage) ?></p><?php endif ?>
    <div class="fm2-completion-track" role="progressbar" aria-label="Готовность работ" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $progress ?>"><progress class="fm2-completion-track__segment" max="85" value="<?= min(85, $progress) ?>" aria-hidden="true"></progress><progress class="fm2-completion-track__segment fm2-completion-track__segment--documents" max="15" value="<?= max(0, $progress - 85) ?>" aria-hidden="true"></progress></div>
    <?php if (!$at85): ?>
        <p>Сначала завершите монтажные работы до 85% в чек-листе.</p><?php if ($canReadChecklist): ?><a class="shlz-link" href="/pilot/objects/<?= (int) $id ?>/checklist">Перейти к чек-листу</a><?php endif ?>
    <?php elseif ($pto === null && $canRecordPto): ?>
        <form class="fm2-completion-form" method="post" action="/pilot/objects/<?= (int) $id ?>/completion" data-completion-form="record_pto"><?= Html::hiddenInput('_csrf', $csrf) ?><?= Html::hiddenInput('action', 'record_pto') ?><?= $generalError('record_pto') ?><label class="shlz-field<?= $submittedAction === 'record_pto' && $errorField === 'ptoActDate' ? ' shlz-field--error' : '' ?>"><span class="shlz-field__label">Дата акта ПТО</span><input class="shlz-input" type="date" name="ptoActDate" value="<?= Html::encode($value('record_pto', 'ptoActDate', $today)) ?>" required max="<?= $today ?>"<?= $fieldAttributes('record_pto', 'ptoActDate') ?>><?= $fieldError('record_pto', 'ptoActDate') ?></label><button class="shlz-button shlz-button--primary" type="submit">Зафиксировать акт ПТО</button></form>
    <?php elseif ($pto !== null && $declaration === null && $canRecordDeclaration): ?>
        <form class="fm2-completion-form" method="post" action="/pilot/objects/<?= (int) $id ?>/completion" data-completion-form="record_declaration"><?= Html::hiddenInput('_csrf', $csrf) ?><?= Html::hiddenInput('action', 'record_declaration') ?><?= $generalError('record_declaration') ?><label class="shlz-field<?= $submittedAction === 'record_declaration' && $errorField === 'declarationDate' ? ' shlz-field--error' : '' ?>"><span class="shlz-field__label">Дата декларации</span><input class="shlz-input" type="date" name="declarationDate" value="<?= Html::encode($value('record_declaration', 'declarationDate', $today)) ?>" required max="<?= $today ?>"<?= $fieldAttributes('record_declaration', 'declarationDate') ?>><?= $fieldError('record_declaration', 'declarationDate') ?></label><label class="shlz-field fm2-completion-details<?= $submittedAction === 'record_declaration' && $errorField === 'declarationDetails' ? ' shlz-field--error' : '' ?>"><span class="shlz-field__label">Реквизиты декларации</span><input class="shlz-input" name="declarationDetails" value="<?= Html::encode($value('record_declaration', 'declarationDetails', '')) ?>" required maxlength="500"<?= $fieldAttributes('record_declaration', 'declarationDetails') ?>><?= $fieldError('record_declaration', 'declarationDetails') ?></label><button class="shlz-button shlz-button--primary" type="submit">Завершить работы</button></form>
    <?php elseif ($pto !== null && $declaration !== null): ?><p>Документы зафиксированы. Монтажные работы завершены.</p><?php endif ?>
    <?php if ($pto !== null): ?><p><strong>Акт ПТО:</strong> <?= Html::encode($pto['date']) ?></p><?php $renderHistory('pto_act', $pto); ?>
        <?php if ($canCorrectPto): ?><details<?= $submittedAction === 'correct_pto' ? ' open' : '' ?>><summary>Исправить акт ПТО</summary><form class="fm2-completion-form" method="post" action="/pilot/objects/<?= (int) $id ?>/completion" data-completion-form="correct_pto"><?= Html::hiddenInput('_csrf', $csrf) ?><?= Html::hiddenInput('action', 'correct_pto') ?><?= Html::hiddenInput('factId', $pto['id']) ?><?= $generalError('correct_pto') ?><label class="shlz-field<?= $submittedAction === 'correct_pto' && $errorField === 'ptoActDate' ? ' shlz-field--error' : '' ?>"><span class="shlz-field__label">Исправленная дата акта ПТО</span><input class="shlz-input" type="date" name="ptoActDate" value="<?= Html::encode($value('correct_pto', 'ptoActDate', (string) $pto['date'])) ?>" required max="<?= $today ?>"<?= $fieldAttributes('correct_pto', 'ptoActDate') ?>><?= $fieldError('correct_pto', 'ptoActDate') ?></label><label class="shlz-field<?= $submittedAction === 'correct_pto' && $errorField === 'reason' ? ' shlz-field--error' : '' ?>"><span class="shlz-field__label">Причина исправления</span><textarea class="shlz-input" name="reason" required maxlength="1000"<?= $fieldAttributes('correct_pto', 'reason') ?>><?= Html::encode($value('correct_pto', 'reason', '')) ?></textarea><?= $fieldError('correct_pto', 'reason') ?></label><button class="shlz-button shlz-button--primary" type="submit">Сохранить исправление</button></form></details><?php endif ?>
    <?php endif ?>
    <?php if ($declaration !== null): ?><p><strong>Декларация:</strong> <?= Html::encode($declaration['date']) ?> · <?= Html::encode($declaration['details']) ?></p><?php $renderHistory('declaration', $declaration); ?>
        <?php if ($canCorrectDeclaration): ?><details<?= $submittedAction === 'correct_declaration' ? ' open' : '' ?>><summary>Исправить декларацию</summary><form class="fm2-completion-form" method="post" action="/pilot/objects/<?= (int) $id ?>/completion" data-completion-form="correct_declaration"><?= Html::hiddenInput('_csrf', $csrf) ?><?= Html::hiddenInput('action', 'correct_declaration') ?><?= Html::hiddenInput('factId', $declaration['id']) ?><?= $generalError('correct_declaration') ?><label class="shlz-field<?= $submittedAction === 'correct_declaration' && $errorField === 'declarationDate' ? ' shlz-field--error' : '' ?>"><span class="shlz-field__label">Исправленная дата декларации</span><input class="shlz-input" type="date" name="declarationDate" value="<?= Html::encode($value('correct_declaration', 'declarationDate', (string) $declaration['date'])) ?>" required max="<?= $today ?>"<?= $fieldAttributes('correct_declaration', 'declarationDate') ?>><?= $fieldError('correct_declaration', 'declarationDate') ?></label><label class="shlz-field fm2-completion-details<?= $submittedAction === 'correct_declaration' && $errorField === 'declarationDetails' ? ' shlz-field--error' : '' ?>"><span class="shlz-field__label">Исправленные реквизиты</span><input class="shlz-input" name="declarationDetails" value="<?= Html::encode($value('correct_declaration', 'declarationDetails', (string) $declaration['details'])) ?>" maxlength="500"<?= $fieldAttributes('correct_declaration', 'declarationDetails') ?>><?= $fieldError('correct_declaration', 'declarationDetails') ?></label><label class="shlz-field<?= $submittedAction === 'correct_declaration' && $errorField === 'reason' ? ' shlz-field--error' : '' ?>"><span class="shlz-field__label">Причина исправления</span><textarea class="shlz-input" name="reason" required maxlength="1000"<?= $fieldAttributes('correct_declaration', 'reason') ?>><?= Html::encode($value('correct_declaration', 'reason', '')) ?></textarea><?= $fieldError('correct_declaration', 'reason') ?></label><button class="shlz-button shlz-button--primary" type="submit">Сохранить исправление</button></form></details><?php endif ?>
    <?php endif ?>
</section>

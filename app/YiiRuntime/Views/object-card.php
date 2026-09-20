<?php

declare(strict_types=1);

use FMonitor2\YiiRuntime\ViewSupport;
use yii\helpers\Html;

$date = static function (?string $value, bool $withTime = false): string {
    if ($value === null || $value === '') return 'Неизвестно';
    try {
        $parsed = new DateTimeImmutable($value);
        if ($withTime) $parsed = $parsed->setTimezone(new DateTimeZone('Europe/Moscow'));
        return $parsed->format($withTime ? 'd.m.Y, H:i' : 'd.m.Y');
    } catch (Throwable) {
        return 'Неизвестно';
    }
};
$statusClasses = [
    'Требуется распоряжение' => 'shlz-status--orange',
    'Готов к открытию' => 'shlz-status--source-blue',
    'Монтажные работы' => 'shlz-status--cyan',
    'В работе' => 'shlz-status--cyan',
    'Документарное закрытие' => 'shlz-status--purple',
    'Работы завершены' => 'shlz-status--bright-green',
    'Требуется изменение' => 'shlz-status--pink',
];
$detailLabels = ['floors' => 'Этажность', 'weight' => 'Грузоподъёмность, кг', 'speed' => 'Скорость, м/с', 'pittype' => 'Тип шахты', 'pitmaterial' => 'Материал шахты', 'paired' => 'Очередность', 'lift_type' => 'Тип лифта'];
$eventLabels = [
    'assignment_order_composition_selected' => 'Состав распоряжения выбран',
    'assignment_order_composition_applied' => 'Состав распоряжения применён',
    'assignment_order_selection_replaced' => 'Ожидающий состав заменён',
    'assignment_order_template_generated' => 'Шаблон распоряжения сформирован',
    'assignment_order_signed_original_uploaded' => 'Подписанный оригинал принят',
    'assignment_order_original_accepted' => 'Подписанный оригинал принят',
    'assignment_order_signed_original_corrected' => 'Подписанный оригинал исправлен',
    'assignment_order_original_corrected' => 'Подписанный оригинал исправлен',
    'installation_opened' => 'Монтажные работы открыты',
    'installation_opened_from_original' => 'Монтажные работы открыты',
    'inspection_scheduled' => 'Инспекция запланирована',
    'control_engineer_changed' => 'Инженер строительного контроля изменён',
];
$canCorrect = $canCorrect ?? false;
$canReadOriginal = $canReadOriginal ?? false;
$engineerOptions = [];
foreach ($eligibleEngineers as $engineer) $engineerOptions[(int) $engineer['user_id']] = (string) $engineer['full_name'];
$orderId = $confirmedOriginal['orderId'] ?? null;
$size = static function (int $bytes): string {
    if ($bytes < 1024) return $bytes . ' Б';
    return number_format($bytes / 1024, $bytes < 10240 ? 1 : 0, ',', ' ') . ' КиБ';
};
$registrationIdentity=trim((string)$registrationNumber)!==''?'Регистрационный номер '.$registrationNumber:'Регистрационный номер не указан';
$factoryIdentity=trim((string)($factoryNumber??''))!==''?'Заводской номер лифта '.$factoryNumber:'Заводской номер лифта не указан';
ViewSupport::begin($this, $registrationIdentity, $identity);
?>
<nav class="fm2-breadcrumb" aria-label="Хлебные крошки"><a class="fm2-breadcrumb-link" href="/pilot/objects">Объекты монтажа</a><span aria-hidden="true">/</span><span aria-current="page"><?= Html::encode($registrationIdentity) ?></span></nav>
<article class="fm2-object-data" data-work-surface>
    <header class="fm2-card-header">
        <div class="fm2-object-title">
            <div class="fm2-object-title__line"><h1><?= Html::encode($address) ?> · подъезд <?= Html::encode($entrance) ?></h1><span class="shlz-status <?= $statusClasses[$status] ?? 'shlz-status--neutral' ?>"><?= Html::encode($status) ?></span></div>
            <p><?= Html::encode($factoryIdentity) ?></p>
        </div>
        <div class="fm2-registration"><span>Регистрационный номер</span><strong><?= trim((string) $registrationNumber) !== '' ? Html::encode($registrationNumber) : 'Не указан' ?></strong></div>
    </header>

    <?php if ($order === null): ?>
        <section class="fm2-next-action" aria-labelledby="next-action-heading"><div><h2 id="next-action-heading">Требуется распоряжение</h2><p>Выберите монтажников. Текущее закрепление инженера будет добавлено в распоряжение автоматически.</p></div><?php if ($canSelect): ?><a class="shlz-button shlz-button--primary" href="/pilot/objects/<?= (int) $id ?>/assignment-order/prepare">Выбрать состав</a><?php endif ?></section>
    <?php elseif (!$opened && $canOpen && !empty($confirmedOriginal)): ?>
        <section class="fm2-next-action" aria-labelledby="next-action-heading"><div><h2 id="next-action-heading">Открыть монтажные работы</h2><p>Укажите фактическую дату начала. Состав и принятый оригинал будут проверены ещё раз.</p></div><form class="fm2-inline-form" method="post" action="/pilot/objects/<?= (int) $id ?>/execution"><?= Html::hiddenInput('_csrf', $csrf) ?><?= Html::hiddenInput('action', 'open_confirmed') ?><?= Html::hiddenInput('requestId', ViewSupport::uuid()) ?><?= Html::hiddenInput('orderId', $confirmedOriginal['orderId']) ?><?= Html::hiddenInput('revisionId', $confirmedOriginal['revisionId']) ?><?= Html::hiddenInput('sequence', $confirmedOriginal['sequence']) ?><label class="fm2-open-date" for="actualStartDate"><span>Фактическая дата начала</span><input class="shlz-input" id="actualStartDate" type="date" name="actualStartDate" required></label><button class="shlz-button shlz-button--primary" type="submit">Открыть работы</button></form></section>
    <?php elseif ($opened && $canReadChecklist): ?>
        <section class="fm2-next-action" aria-labelledby="next-action-heading"><div><h2 id="next-action-heading">Монтажные работы</h2><p>Фиксируйте выполненные работы, исполнителей и фотографии в чек-листе.</p></div><a class="shlz-button shlz-button--primary" href="/pilot/objects/<?= (int) $id ?>/checklist">Перейти к чек-листу</a></section>
    <?php endif ?>

    <div class="fm2-object-layout">
        <main class="fm2-object-workspace" aria-label="Монтажное дело">
            <section class="fm2-object-tabs" aria-labelledby="order-region-heading">
                <div class="fm2-object-tab-panel">
                    <div class="fm2-detail-group">
                        <h2 id="order-region-heading">Распоряжение и состав</h2>
                        <?php if ($order === null): ?><div class="fm2-quiet-empty"><strong>Распоряжение ещё не подготовлено</strong><span>Состав появится здесь после выбора монтажников.</span></div>
                        <?php else: ?>
                            <h3>Состав распоряжения · версия <?= (int) $order['version'] ?></h3><div class="fm2-installer-roster"><?php foreach ($order['installers'] as $installer): ?><div class="fm2-object-installer"><strong><?= Html::encode($installer['fullName']) ?></strong><span><?= Html::encode($installer['position']) ?></span><span class="shlz-status shlz-status--bright-green">Работает</span></div><?php endforeach ?></div>
                            <div class="fm2-team-engineer"><div><span>Инженер строительного контроля</span><strong><?= Html::encode($order['engineer']['fullName']) ?></strong></div><div><span>Форма организации труда</span><strong><?= $order['organizationType'] === 'brigade' ? 'Бригадная' : 'Индивидуальная' ?></strong></div></div>
                            <?php if ($canSelect): ?><p><a class="shlz-link" href="/pilot/objects/<?= (int) $id ?>/assignment-order/selection">Изменить состав распоряжения</a></p><?php endif ?>
                        <?php endif ?>
                    </div>
                    <div class="fm2-detail-group" aria-labelledby="documents-region-heading"><h2 id="documents-region-heading">Документы</h2>
                        <?php if ($order === null): ?><div class="fm2-quiet-empty"><strong>Документов пока нет</strong><span>Подписанный оригинал можно загрузить после выбора состава.</span></div>
                        <?php else: ?><dl><div class="fm2-fact"><dt>Дата распоряжения</dt><dd><time datetime="<?= Html::encode($order['orderDate']) ?>"><?= $date($order['orderDate']) ?></time></dd></div></dl><?php if ($canReadOriginal): ?><?php foreach ($order['artifacts'] as $artifact): ?><p><a class="shlz-link" href="<?= Html::encode($artifact['href']) ?>"><?= Html::encode($artifact['filename']) ?></a> · редакция <?= (int) $artifact['revisionNumber'] ?> · <?= Html::encode($size((int) $artifact['size'])) ?></p><?php endforeach ?><?php if ($orderId): ?><p><a class="shlz-link" href="/pilot/objects/<?= (int) $id ?>/assignment-orders/<?= (int) $orderId ?>/originals/history">История оригинала</a></p><?php endif ?><?php endif ?><?php if ($canCorrect && $orderId): ?><p><a class="shlz-link" href="/pilot/objects/<?= (int) $id ?>/assignment-orders/<?= (int) $orderId ?>/originals/submit">Исправить оригинал</a></p><?php endif ?><?php endif ?>
                    </div>
                </div>
            </section>

            <?php if ($opened) require __DIR__ . '/completion.php'; ?>

            <section class="fm2-integrated-panel" aria-labelledby="technical-region-heading"><div class="fm2-detail-group"><h2 id="technical-region-heading">Технические данные</h2><?php if ($objectDetailsStatus === 'available'): ?><dl><?php foreach ($objectDetails['fields'] as $name => $field): $value = $field['display'] ?? $field['raw'] ?? 'Не указано'; ?><div class="fm2-fact"><dt><?= Html::encode($detailLabels[$name] ?? $name) ?></dt><dd><?= Html::encode((string) $value) ?></dd></div><?php endforeach ?></dl><?php if ($objectDetails['fields'] === []): ?><div class="fm2-quiet-empty"><strong>Характеристики не указаны</strong><span>В снимке нет дополнительных технических данных.</span></div><?php endif ?><?php else: ?><div class="fm2-problem" role="status"><strong>Карточка технических данных <?= $objectDetailsStatus === 'corrupt' ? 'повреждена' : 'недоступна' ?></strong><span>Основные сведения и действия по объекту остаются доступны.</span></div><?php endif ?></div></section>
            <section class="fm2-integrated-panel" aria-labelledby="technical-documents-heading"><div class="fm2-detail-group"><h2 id="technical-documents-heading">Техническая документация</h2><?php if ($technicalDocuments['status'] === 'available'): ?><?php foreach ($technicalDocuments['links'] as $link): ?><p><a class="shlz-link" href="<?= Html::encode($link['url']) ?>" rel="noopener noreferrer"><?= Html::encode($link['name']) ?></a></p><?php endforeach ?><?php elseif ($technicalDocuments['status'] === 'order_number_missing'): ?><p role="status">Номер заказа не указан</p><?php elseif ($technicalDocuments['status'] === 'empty'): ?><p role="status">Техническая документация не найдена</p><?php else: ?><p role="status">Техническая документация временно недоступна</p><?php endif ?></div></section>
            <section class="fm2-integrated-panel" aria-labelledby="history-region-heading"><div class="fm2-detail-group"><h2 id="history-region-heading">История</h2><?php if ($events): ?><ol class="fm2-event-list"><?php foreach ($events as $event): ?><li class="fm2-event"><div><strong><?= Html::encode($eventLabels[$event['type']] ?? ($event['type'] === 'Состав применён' ? 'Состав распоряжения применён' : 'Событие монтажного дела')) ?></strong><small><time datetime="<?= Html::encode($event['occurredAt']) ?>"><?= $date($event['occurredAt'], true) ?></time> МСК · <?= Html::encode($event['actorName'] ?? ('Пользователь недоступен · ID ' . $event['actorId'])) ?></small></div></li><?php endforeach ?></ol><?php else: ?><div class="fm2-quiet-empty"><strong>История пока пуста</strong><span>События монтажного дела появятся после первого действия.</span></div><?php endif ?></div></section>
        </main>
        <aside class="fm2-static-passport" aria-labelledby="object-context-heading">
            <div class="fm2-passport-heading"><h2 id="object-context-heading">Контекст объекта</h2><span>Ответственные и контрольные даты</span></div>
            <div class="fm2-compact-list">
                <section class="fm2-compact-row" aria-labelledby="engineer-heading">
                    <h3 id="engineer-heading">Инженер строительного контроля</h3>
                    <div class="fm2-team-content">
                        <?php if (($currentEngineerAssignment['status'] ?? 'missing') === 'found'): ?>
                            <div class="fm2-team-engineer"><div><span>Текущее закрепление</span><strong><?= Html::encode($currentEngineerAssignment['engineer']['fullName']) ?></strong></div><div><span>Источник</span><strong><?= Html::encode($currentEngineerAssignment['provenance']) ?></strong></div></div>
                        <?php else: ?><div class="fm2-quiet-empty"><strong>Инженер не назначен</strong><span>Закрепление требуется до подготовки распоряжения.</span></div><?php endif ?>
                        <?php if ($canAssignEngineer): ?><form class="fm2-engineer-form" method="post" action="/pilot/objects/<?= (int) $id ?>/control-engineer-assignment"><?= Html::hiddenInput('_csrf', $csrf) ?><?= Html::hiddenInput('requestId', ViewSupport::uuid()) ?><?= Html::hiddenInput('expectedRevision', (int) ($currentEngineerAssignment['revision'] ?? 0)) ?><?= ViewSupport::choice('engineerUserId', '', ['' => 'Выберите инженера'] + $engineerOptions, 'Выберите инженера', ['required' => true]) ?><button class="shlz-button shlz-button--secondary" type="submit">Закрепить</button></form><?php endif ?>
                    </div>
                </section>
                <section class="fm2-compact-row" aria-labelledby="deadlines-heading"><h3 id="deadlines-heading">Сроки монтажа</h3><div class="fm2-compact-values"><div class="fm2-compact-value<?= $plannedStartDate ? '' : ' fm2-compact-value--empty' ?>"><span>Плановое начало</span><strong><?= $plannedStartDate ? '<time datetime="' . Html::encode($plannedStartDate) . '">' . $date($plannedStartDate) . '</time>' : 'Неизвестно' ?></strong></div><div class="fm2-compact-value<?= $plannedFinishDate ? '' : ' fm2-compact-value--empty' ?>"><span>Плановое завершение</span><strong><?= $plannedFinishDate ? '<time datetime="' . Html::encode($plannedFinishDate) . '">' . $date($plannedFinishDate) . '</time>' : 'Неизвестно' ?></strong></div><?php if ($opened): ?><div class="fm2-compact-value"><span>Фактическое начало</span><strong><time datetime="<?= Html::encode($actualStartDate) ?>"><?= $date($actualStartDate) ?></time></strong></div><div class="fm2-compact-value"><span>Работы открыл</span><strong data-opening-actor><?= Html::encode($openedByName ?? ('Пользователь недоступен · ID ' . $openedByUserId)) ?></strong></div><div class="fm2-compact-value"><span>Время открытия</span><strong><time data-opening-time datetime="<?= Html::encode($openedAt) ?>"><?= $date($openedAt, true) ?> МСК</time></strong></div><?php endif ?></div></section>
                <?php if (Yii::$app->canonicalAccess->checkAccess((int) $identity->id, 'deadline_certificate.read')): ?><a class="shlz-link fm2-context-link" href="/pilot/objects/<?= (int) $id ?>/deadline-certificates">Справки о переносе срока</a><?php endif ?>
            </div>
        </aside>
    </div>
</article>
<?php ViewSupport::end($this);

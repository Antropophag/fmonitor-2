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
$orderId = $confirmedOriginal['orderId'] ?? null;
$size = static function (int $bytes): string {
    if ($bytes < 1024) return $bytes . ' Б';
    return number_format($bytes / 1024, $bytes < 10240 ? 1 : 0, ',', ' ') . ' КиБ';
};
ViewSupport::begin($this, 'Объект ' . $registrationNumber, $identity);
?>
<nav class="fm2-breadcrumb" aria-label="Хлебные крошки"><a class="fm2-breadcrumb-link" href="/pilot/objects">Объекты монтажа</a><span aria-hidden="true">/</span><span aria-current="page"><?= Html::encode($registrationNumber) ?></span></nav>
<header class="fm2-object-identity">
    <div><h1><?= Html::encode($address) ?></h1><p>Подъезд <?= Html::encode($entrance) ?></p><span><?= Html::encode($registrationNumber) ?> · ID <?= (int) $id ?></span></div>
    <span class="shlz-status <?= $statusClasses[$status] ?? 'shlz-status--neutral' ?>"><?= Html::encode($status) ?></span>
</header>
<div class="fm2-object-dashboard">
    <main>
        <section class="fm2-panel fm2-object-data">
            <header><div><h2>Распоряжение и состав</h2><p>Актуальное документное основание монтажного дела</p></div></header>
            <div class="fm2-object-tab-panel">
                <?php if ($order === null): ?>
                    <div class="fm2-next-action"><h2>Требуется распоряжение</h2><p>Выберите монтажников и инженера строительного контроля, затем загрузите подписанный оригинал.</p><?php if ($canSelect): ?><a class="shlz-button shlz-button--primary" href="/pilot/objects/<?= (int) $id ?>/assignment-order/prepare">Выбрать состав</a><?php endif ?></div>
                <?php else: ?>
                    <div class="fm2-detail-group"><h3>Состав распоряжения · версия <?= (int) $order['version'] ?></h3>
                        <?php foreach ($order['installers'] as $installer): ?><div class="fm2-object-installer"><strong><?= Html::encode($installer['fullName']) ?></strong><span><?= Html::encode($installer['position']) ?></span><span class="shlz-status shlz-status--bright-green">Работает</span></div><?php endforeach ?>
                        <p class="fm2-order-engineer-line"><strong>Инженер строительного контроля</strong><span><?= Html::encode($order['engineer']['fullName']) ?></span></p>
                    </div>
                    <div class="fm2-detail-group"><h3>Документы</h3><dl class="fm2-details"><dt>Дата распоряжения</dt><dd><time datetime="<?= Html::encode($order['orderDate']) ?>"><?= $date($order['orderDate']) ?></time></dd><dt>Форма организации труда</dt><dd><?= $order['organizationType'] === 'brigade' ? 'Бригадная' : 'Индивидуальная' ?></dd></dl>
                        <?php if ($canReadOriginal): ?><?php foreach ($order['artifacts'] as $artifact): ?><p><a class="shlz-link" href="<?= Html::encode($artifact['href']) ?>"><?= Html::encode($artifact['filename']) ?></a> · редакция <?= (int) $artifact['revisionNumber'] ?> · <?= Html::encode($size((int) $artifact['size'])) ?></p><?php endforeach ?><?php if ($orderId): ?><p><a class="shlz-link" href="/pilot/objects/<?= (int) $id ?>/assignment-orders/<?= (int) $orderId ?>/originals/history">История оригинала</a></p><?php endif ?><?php endif ?>
                    </div>
                    <?php if ($canSelect): ?><p><a class="shlz-link" href="/pilot/objects/<?= (int) $id ?>/assignment-order/selection">Изменить состав распоряжения</a></p><?php endif ?>
                    <?php if ($canCorrect && $orderId): ?><p><a class="shlz-link" href="/pilot/objects/<?= (int) $id ?>/assignment-orders/<?= (int) $orderId ?>/originals/submit">Исправить оригинал</a></p><?php endif ?>
                <?php endif ?>
            </div>
        </section>
        <?php if (!$opened && $canOpen && !empty($confirmedOriginal)): ?>
            <section class="fm2-panel fm2-next-action"><h2>Открыть монтажные работы</h2><p>Укажите фактическую дату начала. Состав и принятый оригинал будут проверены ещё раз.</p>
                <form class="fm2-inline-form" method="post" action="/pilot/objects/<?= (int) $id ?>/execution"><?= Html::hiddenInput('_csrf', $csrf) ?><?= Html::hiddenInput('action', 'open_confirmed') ?><?= Html::hiddenInput('requestId', ViewSupport::uuid()) ?><?= Html::hiddenInput('orderId', $confirmedOriginal['orderId']) ?><?= Html::hiddenInput('revisionId', $confirmedOriginal['revisionId']) ?><?= Html::hiddenInput('sequence', $confirmedOriginal['sequence']) ?><label class="fm2-open-date" for="actualStartDate"><span>Фактическая дата начала</span><input class="shlz-input" id="actualStartDate" type="date" name="actualStartDate" required></label><button class="shlz-button shlz-button--primary" type="submit">Открыть работы</button></form>
            </section>
        <?php endif ?>
        <?php if ($opened): ?><section class="fm2-panel fm2-next-action"><h2>Монтажные работы</h2><p>Фиксируйте выполненные работы, исполнителей и фотографии в чек-листе.</p><a class="shlz-button shlz-button--primary" href="/pilot/objects/<?= (int)$id ?>/checklist">Перейти к чек-листу</a></section><?php require __DIR__ . '/completion.php'; endif ?>
        <section class="fm2-panel fm2-object-data"><header><div><h2>Технические данные</h2><p>Зафиксированный снимок характеристик объекта</p></div></header><div class="fm2-object-tab-panel">
            <?php if ($objectDetailsStatus === 'available'): ?><div class="fm2-detail-group"><dl class="fm2-details"><?php foreach ($objectDetails['fields'] as $name => $field): $value = $field['display'] ?? $field['raw'] ?? 'Не указано'; ?><dt><?= Html::encode($detailLabels[$name] ?? $name) ?></dt><dd><?= Html::encode((string) $value) ?></dd><?php endforeach ?></dl><?php if ($objectDetails['fields'] === []): ?><p>В снимке нет дополнительных технических характеристик.</p><?php endif ?></div><?php else: ?><p role="status">Карточка технических данных <?= $objectDetailsStatus === 'corrupt' ? 'повреждена' : 'недоступна' ?>. Основные сведения и действия по объекту остаются доступны.</p><?php endif ?>
        </div></section>
    </main>
    <aside>
        <section class="fm2-panel fm2-detail-group"><h2>Сроки монтажа</h2><dl class="fm2-details"><dt>Плановое начало</dt><dd><?= $plannedStartDate ? '<time datetime="' . Html::encode($plannedStartDate) . '">' . $date($plannedStartDate) . '</time>' : 'Неизвестно' ?></dd><dt>Плановое завершение</dt><dd><?= $plannedFinishDate ? '<time datetime="' . Html::encode($plannedFinishDate) . '">' . $date($plannedFinishDate) . '</time>' : 'Неизвестно' ?></dd><?php if ($opened): ?><dt>Фактическое начало</dt><dd><time datetime="<?= Html::encode($actualStartDate) ?>"><?= $date($actualStartDate) ?></time></dd><dt>Работы открыл</dt><dd><span data-opening-actor><?= Html::encode($openedByName ?? ('Пользователь недоступен · ID ' . $openedByUserId)) ?></span></dd><dt>Время открытия</dt><dd><time data-opening-time datetime="<?= Html::encode($openedAt) ?>"><?= $date($openedAt, true) ?> МСК</time></dd><?php endif ?></dl></section>
        <?php if ($events): ?><section class="fm2-panel fm2-detail-group"><h2>История</h2><ol><?php foreach ($events as $event): ?><li class="fm2-event"><div><strong><?= Html::encode($eventLabels[$event['type']] ?? ($event['type'] === 'Состав применён' ? 'Состав распоряжения применён' : 'Событие монтажного дела')) ?></strong><small><time datetime="<?= Html::encode($event['occurredAt']) ?>"><?= $date($event['occurredAt'], true) ?></time> МСК · <?= Html::encode($event['actorName'] ?? ('Пользователь недоступен · ID ' . $event['actorId'])) ?></small></div></li><?php endforeach ?></ol></section><?php endif ?>
    </aside>
</div>
<?php ViewSupport::end($this);

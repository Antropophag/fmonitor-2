<?php

declare(strict_types=1);

use FMonitor2\YiiRuntime\ViewSupport;
use FMonitor2\InstallationProcess\ObjectDetailsReferenceCatalogue;
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
$detailLabels = [
    'floors' => 'Этажность', 'weight' => 'Грузоподъёмность, кг', 'speed' => 'Скорость, м/с',
    'pittype' => 'Тип шахты', 'pitmaterial' => 'Материал шахты', 'lift_type' => 'Тип лифта',
];
$technicalDisplay = static function (string $name, array $field): string {
    $codeLabels = [
        'pittype' => ['40' => 'Глухая'],
        'pitmaterial' => ['41' => 'Железобетон'],
    ];
    $value = trim((string) ($field['display'] ?? $field['raw'] ?? ''));
    if ($value === '') return 'Не указано';
    if ($name === 'speed' && $value === '44') return '1,0';
    if (in_array($name, ['floors', 'weight', 'speed'], true)) return $value;
    if (preg_match('/^-?\d+(?:[.,]\d+)?$/D', $value) !== 1) return $value;
    return $codeLabels[$name][$value] ?? 'Не указано';
};
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
    'object_details_changed' => 'Данные объекта изменены',
];
$canCorrect = $canCorrect ?? false;
$canReadOriginal = $canReadOriginal ?? false;
$orderId = $confirmedOriginal['orderId'] ?? null;
$engineerOptions = [];
foreach ($eligibleEngineers as $engineer) {
    $engineerOptions[(int) $engineer['user_id']] = (string) $engineer['full_name'];
}
$size = static fn (int $bytes): string => $bytes < 1024
    ? $bytes . ' Б'
    : number_format($bytes / 1024, $bytes < 10240 ? 1 : 0, ',', ' ') . ' КиБ';
$icon = static function (string $name): string {
    $extension = strtolower((string) pathinfo((string) parse_url($name, PHP_URL_PATH), PATHINFO_EXTENSION));
    return match ($extension) {
        'pdf' => 'file-pdf-default',
        'doc' => 'file-doc',
        'docx' => 'file-docx',
        'xls' => 'file-xls',
        'xlsx' => 'file-xlsx',
        'jpg', 'jpeg', 'png', 'gif', 'webp' => 'file-img',
        default => 'file-generic',
    };
};
$employment = static function (array $installer): array {
    return match ($installer['employmentStatus'] ?? null) {
        'employed' => ['Работает', 'shlz-status--bright-green'],
        'dismissed' => ['Уволен', 'shlz-status--neutral'],
    };
};
$documentRow = static function (array $document) use ($icon): string {
    $name = (string) $document['name'];
    $href = (string) $document['href'];
    $external = (bool) ($document['external'] ?? false);
    ob_start();
    ?>
    <article class="shlz-document-row">
        <div class="shlz-document-row__visual"><img src="/pilot/assets/shlz-file-types/<?= $icon($name ?: $href) ?>.svg" alt=""></div>
        <div class="shlz-document-row__content">
            <a class="shlz-document-row__title" href="<?= Html::encode($href) ?>"<?= $external ? ' rel="noopener noreferrer"' : '' ?>><?= Html::encode($name) ?></a>
            <span class="shlz-document-row__meta"><?= Html::encode((string) $document['meta']) ?></span>
        </div>
        <div class="shlz-document-row__actions">
            <a class="shlz-document-row__action" href="<?= Html::encode($href) ?>"<?= $external ? ' rel="noopener noreferrer"' : '' ?> aria-label="Скачать <?= Html::encode($name) ?>"><img src="/pilot/assets/shlz-icons/download.svg" alt=""></a>
        </div>
    </article>
    <?php
    return (string) ob_get_clean();
};
$registrationIdentity = trim((string) $registrationNumber) !== ''
    ? 'Регистрационный номер ' . $registrationNumber
    : 'Регистрационный номер не указан';
$liftTypeDisplay = $objectDetailsStatus === 'available' && isset($objectDetails['fields']['lift_type'])
    ? $technicalDisplay('lift_type', $objectDetails['fields']['lift_type'])
    : 'Не указано';
ViewSupport::begin($this, $registrationIdentity, $identity);
?>
<nav class="fm2-breadcrumb" aria-label="Хлебные крошки">
    <a class="fm2-breadcrumb-link" href="/pilot/objects">Объекты монтажа</a>
    <span aria-hidden="true">/</span>
    <span aria-current="page"><?= Html::encode($registrationIdentity) ?></span>
</nav>
<article class="fm2-object-data" data-work-surface>
    <header class="fm2-card-header">
        <div class="fm2-object-title">
            <div class="fm2-object-title__line">
                <h1><?= Html::encode($address) ?> · подъезд <?= Html::encode($entrance) ?></h1>
                <span class="shlz-status <?= $statusClasses[$status] ?? 'shlz-status--neutral' ?>"><?= Html::encode($status) ?></span>
            </div>
            <p><?= trim((string) ($factoryNumber ?? '')) !== '' ? 'Заводской номер лифта ' . Html::encode($factoryNumber) : 'Заводской номер лифта не указан' ?></p>
        </div>
        <div class="fm2-registration">
            <span>Регистрационный номер</span>
            <strong><?= trim((string) $registrationNumber) !== '' ? Html::encode($registrationNumber) : 'Не указан' ?></strong>
        </div>
        <?php if (($detailEditor['allowed'] ?? false) === true): ?><button class="shlz-button shlz-button--icon fm2-object-edit" type="button" data-object-details-edit aria-label="Редактировать данные объекта" title="Редактировать данные объекта"><svg data-shlz-icon="plus-alt-2" aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></button><?php endif ?>
    </header>
    <?php if (($detailEditor['allowed'] ?? false) === true): $persistedEditorValues=[
        'address'=>$address,'entrance'=>$entrance,'regnumber'=>$registrationNumber,'zavnumber'=>$factoryNumber,
        'floors'=>$objectDetails['fields']['floors']['raw']??'','weight'=>$objectDetails['fields']['weight']['raw']??'','speed'=>$objectDetails['fields']['speed']['raw']??'',
        'pittype'=>$objectDetails['fields']['pittype']['raw']??'','pitmaterial'=>$objectDetails['fields']['pitmaterial']['raw']??'','lift_type'=>$objectDetails['fields']['lift_type']['raw']??'','paired'=>$objectDetails['fields']['paired']['raw']??'',
    ];$persistedWireValues=array_map(static fn($value):string=>(string)($value??''),$persistedEditorValues);$editorValues=array_replace($persistedEditorValues,$detailEditor['values']??[]);$editorErrors=$detailEditor['errors']??[]; ?>
    <dialog class="shlz-modal fm2-object-details-modal" data-object-details-dialog <?= ($detailEditor['open']??false)?'data-object-details-invalid':'' ?> aria-labelledby="object-details-title"><form class="shlz-modal__surface" method="post" action="/pilot/objects/<?= (int)$id ?>/details" data-object-details-baseline="<?= Html::encode(json_encode($persistedWireValues,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)) ?>"><header class="shlz-modal__header"><div><h2 class="shlz-modal__title" id="object-details-title">Редактировать данные объекта</h2></div><button class="shlz-modal__close" type="button" data-object-details-close aria-label="Закрыть"><span aria-hidden="true">×</span></button></header><div class="shlz-modal__body">
        <?= Html::hiddenInput('_csrf',$csrf) ?><?= Html::hiddenInput('requestId',ViewSupport::uuid()) ?><?= Html::hiddenInput('expectedRevision',(int)$detailEditor['revision']) ?>
        <?php if(isset($editorErrors['_form'])): ?><p class="fm2-problem" role="alert"><?= Html::encode($editorErrors['_form']) ?></p><?php endif ?>
        <fieldset><legend>Идентификация и размещение</legend><?php foreach(['address'=>'Адрес','entrance'=>'Подъезд','regnumber'=>'Регистрационный номер','zavnumber'=>'Заводской номер']as$name=>$label): $error=$editorErrors[$name]??null; ?><label class="fm2-details-field"><span><?= $label ?></span><input class="shlz-input" name="<?= $name ?>" value="<?= Html::encode((string)($editorValues[$name]??'')) ?>" <?= in_array($name,['address','entrance'],true)?'required':'' ?> <?= $name==='zavnumber'?'maxlength="120"':'' ?> <?= $error!==null?'aria-invalid="true" aria-describedby="details-error-'.$name.'"':'' ?>><?php if($error!==null): ?><span id="details-error-<?= $name ?>" role="alert"><?= Html::encode($error) ?></span><?php endif ?></label><?php endforeach ?></fieldset>
        <fieldset><legend>Классификация оборудования</legend><?php foreach(['floors'=>'Этажность','weight'=>'Грузоподъёмность','speed'=>'Скорость']as$name=>$label): $error=$editorErrors[$name]??null; ?><label class="fm2-details-field"><span><?= $label ?></span><input class="shlz-input" inputmode="decimal" name="<?= $name ?>" value="<?= Html::encode((string)($editorValues[$name]??'')) ?>" <?= $error!==null?'aria-invalid="true" aria-describedby="details-error-'.$name.'"':'' ?>><?php if($error!==null): ?><span id="details-error-<?= $name ?>" role="alert"><?= Html::encode($error) ?></span><?php endif ?></label><?php endforeach ?><?php foreach(['pittype'=>'Тип шахты','pitmaterial'=>'Материал шахты','lift_type'=>'Тип лифта']as$name=>$label): $error=$editorErrors[$name]??null;$selected=(string)($editorValues[$name]??'');$persistedSelected=(string)($persistedEditorValues[$name]??'');$options=[''=>'Не изменять']+ObjectDetailsReferenceCatalogue::options($name);foreach([$selected,$persistedSelected]as$available)if($available!==''&&!array_key_exists($available,$options))$options=[$available=>'Недопустимое значение: '.$available]+$options;$attributes=[];if($error!==null){$attributes['aria-invalid']='true';$attributes['aria-describedby']='details-error-'.$name;} ?><div class="fm2-details-field"><?= ViewSupport::choice($name,$selected,$options,$label,$attributes) ?><?php if($error!==null): ?><span id="details-error-<?= $name ?>" role="alert"><?= Html::encode($error) ?></span><?php endif ?></div><?php endforeach ?><div class="fm2-details-field"><?= ViewSupport::choice('paired',(string)($editorValues['paired']??''),[''=>'Не изменять','1'=>'Да','0'=>'Нет'],'Спаренность лифта') ?></div></fieldset>
    </div><footer class="shlz-modal__footer"><button class="shlz-button" type="button" data-object-details-cancel>Отмена</button><button class="shlz-button shlz-button--primary" type="submit">Сохранить</button></footer></form></dialog>
    <?php endif ?>
    <div class="fm2-object-layout">
    <aside class="fm2-static-passport" aria-labelledby="object-context-heading">
        <div class="fm2-passport-heading"><h2 id="object-context-heading">Паспорт объекта</h2><span>Технические характеристики</span></div>
        <section class="fm2-passport-technical" aria-labelledby="object-technical-heading">
            <h3 class="fm2-visually-hidden" id="object-technical-heading">Технические характеристики</h3>
            <dl>
                <?php if ($objectDetailsStatus === 'available'): ?><?php foreach ($objectDetails['fields'] as $name => $field): if (in_array($name, ['paired', 'lift_type'], true)) continue; $value = $technicalDisplay((string) $name, $field); ?><div class="fm2-fact"><dt><?= Html::encode($detailLabels[$name] ?? $name) ?></dt><dd><?= Html::encode($value) ?></dd></div><?php endforeach ?><?php endif ?>
                <div class="fm2-fact"><dt>Тип лифта</dt><dd><?= Html::encode($liftTypeDisplay) ?></dd></div>
            </dl>
            <?php if ($objectDetailsStatus === 'available'): ?>
                <?php if ($objectDetails['fields'] === []): ?><div class="fm2-quiet-empty"><strong>Характеристики не указаны</strong><span>Дополнительные сведения об оборудовании отсутствуют.</span></div><?php endif ?>
            <?php else: ?>
                <div class="fm2-problem" role="status"><strong>Карточка технических данных <?= $objectDetailsStatus === 'corrupt' ? 'повреждена' : 'недоступна' ?></strong><span>Работа с объектом остаётся доступна.</span></div>
            <?php endif ?>
        </section>
    </aside>
    <main class="fm2-object-workspace" aria-label="Монтажное дело">
        <?php if ($order === null): ?>
            <section class="fm2-next-action" aria-labelledby="next-action-heading">
                <div><h2 id="next-action-heading">Требуется распоряжение</h2><p>Выберите монтажников. Текущее закрепление инженера будет добавлено в распоряжение автоматически.</p></div>
                <?php if ($canSelect): ?><a class="shlz-button shlz-button--primary" href="/pilot/objects/<?= (int) $id ?>/assignment-order/prepare">Выбрать состав</a><?php endif ?>
            </section>
        <?php elseif (!$opened && $canOpen && !empty($confirmedOriginal)): ?>
            <section class="fm2-next-action" aria-labelledby="next-action-heading">
                <div><h2 id="next-action-heading">Открыть монтажные работы</h2><p>Укажите фактическую дату начала работ.</p></div>
                <form class="fm2-inline-form" method="post" action="/pilot/objects/<?= (int) $id ?>/execution">
                    <?= Html::hiddenInput('_csrf', $csrf) ?>
                    <?= Html::hiddenInput('action', 'open_confirmed') ?>
                    <?= Html::hiddenInput('requestId', ViewSupport::uuid()) ?>
                    <?= Html::hiddenInput('orderId', $confirmedOriginal['orderId']) ?>
                    <?= Html::hiddenInput('revisionId', $confirmedOriginal['revisionId']) ?>
                    <?= Html::hiddenInput('sequence', $confirmedOriginal['sequence']) ?>
                    <label class="fm2-open-date" for="actualStartDate"><input class="shlz-input" id="actualStartDate" type="date" name="actualStartDate" aria-label="Фактическая дата начала работ" required></label>
                    <button class="shlz-button shlz-button--primary" type="submit">Открыть работы</button>
                </form>
            </section>
        <?php elseif ($opened && $canReadChecklist): ?>
            <section class="fm2-next-action" aria-labelledby="next-action-heading">
                <div><h2 id="next-action-heading">Монтажные работы</h2><p>Фиксируйте выполненные работы, исполнителей и фотографии в чек-листе.</p></div>
                <a class="shlz-button shlz-button--primary" href="/pilot/objects/<?= (int) $id ?>/checklist">Перейти к чек-листу</a>
            </section>
        <?php endif ?>
        <div class="shlz-tabs fm2-object-tabs" data-shlz-tabs>
            <div class="shlz-tabs__list" role="tablist" aria-label="Разделы карточки объекта">
                <button class="shlz-tabs__tab" id="object-tab-readiness" type="button" role="tab" aria-selected="true" aria-controls="object-panel-readiness">Сроки и готовность</button>
                <button class="shlz-tabs__tab" id="object-tab-team" type="button" role="tab" aria-selected="false" aria-controls="object-panel-team" tabindex="-1">Команда</button>
                <button class="shlz-tabs__tab" id="object-tab-documents" type="button" role="tab" aria-selected="false" aria-controls="object-panel-documents" tabindex="-1">Документы</button>
                <button class="shlz-tabs__tab" id="object-tab-history" type="button" role="tab" aria-selected="false" aria-controls="object-panel-history" tabindex="-1">История</button>
            </div>
            <section class="shlz-tabs__panel fm2-object-tab-panel" id="object-panel-readiness" role="tabpanel" aria-labelledby="object-tab-readiness">
                <div class="fm2-tab-section">
                    <h2>Сроки монтажа</h2>
                    <dl class="fm2-fact-grid">
                        <div class="fm2-fact"><dt>Плановое начало</dt><dd><?= $plannedStartDate ? '<time datetime="' . Html::encode($plannedStartDate) . '">' . $date($plannedStartDate) . '</time>' : 'Неизвестно' ?></dd></div>
                        <div class="fm2-fact"><dt>Плановое завершение</dt><dd><?= $plannedFinishDate ? '<time datetime="' . Html::encode($plannedFinishDate) . '">' . $date($plannedFinishDate) . '</time>' : 'Неизвестно' ?></dd></div>
                        <?php if ($opened): ?><div class="fm2-fact"><dt>Фактическое начало</dt><dd><time datetime="<?= Html::encode($actualStartDate) ?>"><?= $date($actualStartDate) ?></time></dd></div><?php endif ?>
                    </dl>
                    <?php if (Yii::$app->canonicalAccess->checkAccess((int) $identity->id, 'deadline_certificate.read')): ?><div class="fm2-workspace-actions shlz-cluster"><a class="shlz-button shlz-button--secondary" href="/pilot/objects/<?= (int) $id ?>/deadline-certificates">Справки о переносе срока</a></div><?php endif ?>
                </div>
                <div class="fm2-tab-section">
                    <h2>Готовность</h2>
                    <dl class="fm2-fact-grid">
                        <div class="fm2-fact"><dt>Монтажное дело</dt><dd><?= Html::encode($status) ?></dd></div>
                        <div class="fm2-fact"><dt>Оригинал распоряжения</dt><dd><?= !empty($confirmedOriginal) ? 'Принят' : 'Не принят' ?></dd></div>
                        <div class="fm2-fact"><dt>Готовность оборудования</dt><dd><?= $equipmentFacts['readinessDate'] ? $date($equipmentFacts['readinessDate']) : 'неизвестно' ?></dd></div>
                        <div class="fm2-fact"><dt>Первое грузоместо</dt><dd><?= $equipmentFacts['firstShipmentDate'] ? $date($equipmentFacts['firstShipmentDate']) : 'неизвестно' ?></dd></div>
                        <div class="fm2-fact"><dt>Полная отгрузка</dt><dd><?= $equipmentFacts['fullShipmentDate'] ? $date($equipmentFacts['fullShipmentDate']) : 'неизвестно' ?></dd></div>
                        <div class="fm2-fact"><dt>Источник</dt><dd>1С ERP</dd></div>
                        <div class="fm2-fact"><dt>Последнее успешное обновление</dt><dd><?= $equipmentFacts['lastSuccessfulSyncAt'] ? $date($equipmentFacts['lastSuccessfulSyncAt'], true) : 'неизвестно' ?></dd></div>
                        <?php if ($opened): ?>
                            <div class="fm2-fact"><dt>Работы открыл</dt><dd data-opening-actor><?= Html::encode($openedByName ?? ('Пользователь недоступен · ID ' . $openedByUserId)) ?></dd></div>
                            <div class="fm2-fact"><dt>Время открытия</dt><dd><time data-opening-time datetime="<?= Html::encode($openedAt) ?>"><?= $date($openedAt, true) ?> МСК</time></dd></div>
                        <?php endif ?>
                    </dl>
                    <?php if ($equipmentFacts['status'] === 'never_synced'): ?><p role="status">Синхронизация ещё не выполнялась</p>
                    <?php elseif ($equipmentFacts['status'] === 'failed_before_success'): ?><p role="status">Синхронизация завершилась ошибкой до первого успешного обновления</p>
                    <?php elseif ($equipmentFacts['status'] === 'failed_after_success'): ?><p role="status">Последняя попытка завершилась ошибкой</p>
                    <?php elseif ($equipmentFacts['status'] === 'unavailable'): ?><p role="status">Данные оборудования временно недоступны</p><?php endif ?>
                </div>
                <?php if ($opened) require __DIR__ . '/completion.php'; ?>
            </section>
            <section class="shlz-tabs__panel fm2-object-tab-panel" id="object-panel-team" role="tabpanel" aria-labelledby="object-tab-team" hidden>
                <div class="fm2-tab-section">
                    <h2>Монтажники</h2>
                    <?php if ($order === null): ?><div class="fm2-quiet-empty"><strong>Состав ещё не выбран</strong><span>Монтажники появятся здесь после подготовки распоряжения.</span></div>
                    <?php else: ?><div class="fm2-installer-roster"><?php foreach ($order['installers'] as $installer): [$employmentLabel, $employmentClass] = $employment($installer); ?><div class="fm2-object-installer"><div><span class="fm2-team-role">Монтажник</span><strong><?= Html::encode($installer['fullName']) ?></strong></div><span>Табельный № <?= Html::encode((string) ($installer['tabId'] ?? 'Не указан')) ?></span><span class="shlz-status <?= $employmentClass ?>"><?= $employmentLabel ?></span></div><?php endforeach ?></div>
                        <?php if ($canSelect): ?><div class="fm2-workspace-actions shlz-cluster"><a class="shlz-button shlz-button--secondary" href="/pilot/objects/<?= (int) $id ?>/assignment-order/selection">Изменить состав бригады</a></div><?php endif ?>
                    <?php endif ?>
                </div>
                <div class="fm2-tab-section">
                    <h2>Ответственные</h2>
                    <?php if (($currentEngineerAssignment['status'] ?? 'missing') === 'found'): ?><div class="fm2-person-row"><span class="fm2-team-role">Инженер строительного контроля</span><strong><?= Html::encode($currentEngineerAssignment['engineer']['fullName']) ?></strong><span><?= Html::encode((string) ($currentEngineerAssignment['engineer']['phone'] ?? 'Телефон не указан')) ?></span></div>
                    <?php else: ?><div class="fm2-quiet-empty"><strong>Инженер не назначен</strong><span>Закрепление требуется до подготовки распоряжения.</span></div><?php endif ?>
                    <?php if ($canAssignEngineer): ?>
                        <form class="fm2-engineer-form" method="post" action="/pilot/objects/<?= (int) $id ?>/control-engineer-assignment"><?= Html::hiddenInput('_csrf', $csrf) ?><?= Html::hiddenInput('requestId', ViewSupport::uuid()) ?><?= Html::hiddenInput('expectedRevision', (int) ($currentEngineerAssignment['revision'] ?? 0)) ?><?= ViewSupport::choice('engineerUserId', '', ['' => 'Выберите инженера'] + $engineerOptions, 'Выберите инженера', ['required' => true]) ?><button class="shlz-button shlz-button--secondary" type="submit">Закрепить</button></form>
                    <?php endif ?>
                </div>
            </section>
            <section class="shlz-tabs__panel fm2-object-tab-panel" id="object-panel-documents" role="tabpanel" aria-labelledby="object-tab-documents" hidden>
                <div class="fm2-tab-section">
                    <h2>Распоряжения</h2>
                    <?php if ($order === null): ?><div class="fm2-quiet-empty"><strong>Документов пока нет</strong><span>Подписанный оригинал можно загрузить после выбора состава.</span></div>
                    <?php else: ?><dl class="fm2-fact-grid"><div class="fm2-fact"><dt>Дата распоряжения</dt><dd><time datetime="<?= Html::encode($order['orderDate']) ?>"><?= $date($order['orderDate']) ?></time></dd></div></dl>
                        <?php if ($canReadOriginal): ?><div class="shlz-document-list"><?php foreach ($order['artifacts'] as $artifact): ?><?= $documentRow(['name' => $artifact['filename'], 'href' => $artifact['href'], 'meta' => 'Редакция ' . (int) $artifact['revisionNumber'] . ' · ' . $size((int) $artifact['size'])]) ?><?php endforeach ?></div><?php endif ?>
                        <?php if ($orderId && ($canReadOriginal || $canCorrect)): ?>
                            <div class="fm2-document-actions shlz-cluster">
                                <?php if ($canReadOriginal): ?><a class="shlz-button shlz-button--secondary" href="/pilot/objects/<?= (int) $id ?>/assignment-orders/<?= (int) $orderId ?>/originals/history">История оригинала</a><?php endif ?>
                                <?php if ($canCorrect): ?><a class="shlz-button shlz-button--secondary" href="/pilot/objects/<?= (int) $id ?>/assignment-orders/<?= (int) $orderId ?>/originals/submit">Исправить оригинал</a><?php endif ?>
                            </div>
                        <?php endif ?>
                    <?php endif ?>
                </div>
                <div class="fm2-tab-section">
                    <h2>Техническая документация</h2>
                    <?php if ($technicalDocuments['status'] === 'available'): ?><div class="shlz-document-list"><?php foreach ($technicalDocuments['links'] as $link): ?><?= $documentRow(['name' => $link['name'], 'href' => $link['url'], 'meta' => 'Технический документ', 'external' => true]) ?><?php endforeach ?></div>
                    <?php elseif ($technicalDocuments['status'] === 'order_number_missing'): ?><p role="status">Номер заказа не указан</p>
                    <?php elseif ($technicalDocuments['status'] === 'empty'): ?><p role="status">Техническая документация не найдена</p>
                    <?php else: ?><p role="status">Техническая документация временно недоступна</p><?php endif ?>
                </div>
            </section>
            <section class="shlz-tabs__panel fm2-object-tab-panel" id="object-panel-history" role="tabpanel" aria-labelledby="object-tab-history" hidden>
                <div class="fm2-tab-section">
                    <h2>История монтажного дела</h2>
                    <?php if ($events): ?><ol class="fm2-event-list"><?php foreach ($events as $event): ?><li class="fm2-event"><div><strong><?= Html::encode($eventLabels[$event['type']] ?? ($event['type'] === 'Состав применён' ? 'Состав распоряжения применён' : 'Событие монтажного дела')) ?></strong><small><time datetime="<?= Html::encode($event['occurredAt']) ?>"><?= $date($event['occurredAt'], true) ?></time> МСК · <?= Html::encode($event['actorName'] ?? ('Пользователь недоступен · ID ' . $event['actorId'])) ?></small><?php foreach($event['changes']??[]as$change): ?><span class="fm2-event-change"><?= Html::encode((string)$change['label']) ?>: <?= Html::encode((string)($change['old']['display']??'Не указано')) ?> → <?= Html::encode((string)($change['new']['display']??'Не указано')) ?></span><?php endforeach ?></div></li><?php endforeach ?></ol>
                    <?php else: ?><div class="fm2-quiet-empty"><strong>История пока пуста</strong><span>События монтажного дела появятся после первого действия.</span></div><?php endif ?>
                    <?php if(($historyNext??null)!==null): ?><a class="shlz-button shlz-button--secondary" href="/pilot/objects/<?= (int)$id ?>?history=<?= Html::encode($historyNext) ?>#history">Показать ещё</a><?php endif ?>
                </div>
            </section>
        </div>
    </main>
    </div>
</article>
<?php ViewSupport::end($this);

<?php

declare(strict_types=1);

use FMonitor2\YiiRuntime\Assets\InstallerDirectoryAssetBundle;
use FMonitor2\YiiRuntime\ViewSupport;
use yii\helpers\Html;

InstallerDirectoryAssetBundle::register($this);
$dateTime = static function (?string $value): string {
    if ($value === null || $value === '') return 'Неизвестно';
    return (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('Europe/Moscow'))->format('d.m.Y H:i');
};
$date = static function (?string $value): string {
    if ($value === null || $value === '') return 'Дата увольнения неизвестна';
    return (new DateTimeImmutable($value))->format('d.m.Y');
};
$statusControl = ViewSupport::choice('status', (string) $filters['status'], ['' => 'Все', 'employed' => 'Работает', 'dismissed' => 'Уволен'], 'Кадровый статус');
$availabilityControl = ViewSupport::choice('availability', (string) $filters['availability'], ['' => 'Все', 'assigned' => 'Закреплён', 'free' => 'Свободен'], 'Закрепление');
$query = static function (int $page) use ($filters): string {
    $values = array_filter(['q' => $filters['q'], 'status' => $filters['status'], 'availability' => $filters['availability'], 'page' => $page], static fn($value, $key): bool => $value !== '' && !($key === 'page' && $value === 1), ARRAY_FILTER_USE_BOTH);
    return '/pilot/installers' . ($values === [] ? '' : '?' . http_build_query($values, '', '&', PHP_QUERY_RFC3986));
};
ViewSupport::begin($this, 'Монтажники', $identity, 'installers');
?><div class="fm2-page-header"><div><h1>Монтажники</h1><p>Кадровый статус и действующие закрепления</p></div><span class="fm2-result-count"><?= (int) $filters['total'] ?> сотрудников</span></div>
<section class="fm2-directory-summary"><div><strong data-directory-summary="total"><?= (int) $summary['total'] ?></strong><span>Всего</span></div><div><strong data-directory-summary="working"><?= (int) $summary['working'] ?></strong><span>Работают</span></div><div><strong data-directory-summary="dismissed"><?= (int) $summary['dismissed'] ?></strong><span>Уволены</span></div><div><strong data-directory-summary="assigned"><?= (int) $summary['assigned'] ?></strong><span>Закреплены</span></div></section>
<section class="fm2-list-surface" data-filter-query="<?= Html::encode(http_build_query(array_filter(['q' => $filters['q'], 'status' => $filters['status'], 'availability' => $filters['availability']], static fn($value): bool => $value !== ''), '', '&', PHP_QUERY_RFC3986)) ?>"><form method="get" action="/pilot/installers" class="fm2-user-toolbar"><label class="shlz-field"><span class="shlz-field__label">Поиск</span><span class="shlz-field__control"><input class="shlz-input" type="search" name="q" maxlength="120" value="<?= Html::encode($filters['q']) ?>" placeholder="ФИО или табельный номер"></span></label><?= $statusControl ?><?= $availabilityControl ?><button class="shlz-button shlz-button--primary" type="submit">Показать</button><?php if ($filters['q'] !== '' || $filters['status'] !== '' || $filters['availability'] !== ''): ?><a class="shlz-button shlz-button--secondary" href="/pilot/installers">Сбросить</a><?php endif ?></form>
<?php if ((int) $summary['total'] === 0): ?><p class="shlz-table__empty">Каталог монтажников пока не загружен</p><?php elseif ($rows === []): ?><p class="shlz-table__empty">Ничего не найдено</p><?php else: ?><div class="shlz-table-wrap fm2-installer-table-scroll"><table class="shlz-table fm2-installer-table"><thead class="shlz-table__head"><tr class="shlz-table__row"><th class="shlz-table__cell">Монтажник</th><th class="shlz-table__cell">Должность</th><th class="shlz-table__cell">Кадровый статус</th><th class="shlz-table__cell">Действующие закрепления</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr class="shlz-table__row"><td class="shlz-table__cell" data-label="Монтажник"><strong><?= Html::encode(trim($row['fio'])) ?></strong><small>таб. <?= str_pad((string) $row['installer_tab_id'], 6, '0', STR_PAD_LEFT) ?></small></td><td class="shlz-table__cell" data-label="Должность"><?= Html::encode(trim($row['position'])) ?></td><td class="shlz-table__cell" data-label="Кадровый статус"><span class="fm2-employment fm2-employment--<?= $row['employment_status'] === 'employed' ? 'active' : 'dismissed' ?>"><?= $row['employment_status'] === 'employed' ? 'Работает' : 'Уволен' ?></span><?php if ($row['employment_status'] === 'dismissed'): ?><small><?= Html::encode($date($row['dismissal_effective_at'])) ?></small><?php endif ?></td><td class="shlz-table__cell" data-label="Действующие закрепления"><?php if ($row['assignments'] === []): ?><span class="shlz-table__empty">Нет действующих закреплений</span><?php else: ?><?php foreach ($row['assignments'] as $assignment): ?><a class="shlz-link fm2-assignment-link" href="/pilot/objects/<?= (int) $assignment['object_id'] ?>"><strong><?= Html::encode($assignment['registration_number']) ?></strong><span><?= Html::encode($assignment['address']) ?></span></a><?php endforeach ?><?php endif ?></td></tr><?php endforeach ?></tbody></table></div><?php endif ?>
<?= ViewSupport::pagination('/pilot/installers',(int)$filters['page'],(int)$filters['pages'],(int)$filters['total'],50,['q'=>$filters['q'],'status'=>$filters['status'],'availability'=>$filters['availability']],'Страницы монтажников') ?></section></main></div></div>
<?php ViewSupport::end($this);

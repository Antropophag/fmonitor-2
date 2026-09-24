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
$currentControl = ViewSupport::choice('current', (string) $filters['current'], ['' => 'Любая', 'present' => 'Есть', 'absent' => 'Нет'], 'Текущая работа');
$upcomingControl = ViewSupport::choice('upcoming', (string) $filters['upcoming'], ['' => 'Любое', 'present' => 'Есть', 'absent' => 'Нет'], 'Предстоящее назначение');
$query = static function (int $page) use ($filters): string {
    $values = array_filter(['q' => $filters['q'], 'status' => $filters['status'], 'availability' => $filters['availability'], 'current'=>$filters['current'], 'upcoming'=>$filters['upcoming'], 'page' => $page], static fn($value, $key): bool => $value !== '' && !($key === 'page' && $value === 1), ARRAY_FILTER_USE_BOTH);
    return '/pilot/installers' . ($values === [] ? '' : '?' . http_build_query($values, '', '&', PHP_QUERY_RFC3986));
};
ViewSupport::begin($this, 'Монтажники', $identity, 'installers');
?><div class="fm2-page-header"><div><h1>Монтажники</h1><p>Кадровый статус и действующие закрепления</p></div><span class="fm2-result-count"><?= (int) $filters['total'] ?> сотрудников</span></div>
<section class="fm2-directory-summary"><div><strong data-directory-summary="total"><?= (int) $summary['total'] ?></strong><span>Всего</span></div><div><strong data-directory-summary="working"><?= (int) $summary['working'] ?></strong><span>Работают</span></div><div><strong data-directory-summary="dismissed"><?= (int) $summary['dismissed'] ?></strong><span>Уволены</span></div><div><strong data-directory-summary="assigned"><?= (int) $summary['assigned'] ?></strong><span>Закреплены</span></div></section>
<section class="fm2-list-surface" data-filtered-count="<?= (int)$filters['total'] ?>" data-filter-query="<?= Html::encode(http_build_query(array_filter(['q'=>$filters['q'],'status'=>$filters['status'],'availability'=>$filters['availability'],'current'=>$filters['current'],'upcoming'=>$filters['upcoming']],static fn($value):bool=>$value!==''),'','&',PHP_QUERY_RFC3986)) ?>"><form method="get" action="/pilot/installers" class="fm2-user-toolbar"><label class="shlz-field"><span class="shlz-field__label">Поиск</span><span class="shlz-field__control"><input class="shlz-input" type="search" name="q" maxlength="120" value="<?= Html::encode($filters['q']) ?>" placeholder="ФИО или табельный номер"></span></label><?= $statusControl ?><?= $availabilityControl ?><?= $currentControl ?><?= $upcomingControl ?><button class="shlz-button shlz-button--primary" type="submit">Показать</button><?php if ($filters['q']!==''||$filters['status']!==''||$filters['availability']!==''||$filters['current']!==''||$filters['upcoming']!==''): ?><a class="shlz-button shlz-button--secondary" href="/pilot/installers">Сбросить</a><?php endif ?></form>
<?php if ((int)$summary['total']===0):?><p class="shlz-table__empty">Каталог монтажников пока не загружен</p><?php elseif($rows===[]):?><p class="shlz-table__empty">Ничего не найдено</p><?php else:?><div class="shlz-table-wrap fm2-installer-table-scroll"><table class="shlz-table fm2-installer-table"><thead><tr><th>Монтажник</th><th>Кадровый статус</th><th>Загрузка</th><th>Объекты</th></tr></thead><tbody><?php foreach($rows as$row):$count=(int)$row['currentWorkCount'];?><tr data-current-work-count="<?=$count?>"><td><a class="shlz-link" href="/pilot/installers/<?=(int)$row['installer_tab_id']?>?return=<?=rawurlencode(http_build_query(['q'=>$filters['q'],'current'=>$filters['current'],'upcoming'=>$filters['upcoming'],'page'=>$filters['page']]))?>"><strong><?=Html::encode(trim($row['fio']))?></strong></a><small>таб. <?=str_pad((string)$row['installer_tab_id'],6,'0',STR_PAD_LEFT)?> · <?=Html::encode($row['position'])?> · <?=Html::encode($row['workforce_source'])?></small></td><td><?=$row['employment_status']==='employed'?'Работает':'Уволен'?></td><td><?=$count===0?'Нет текущих работ · Нет действующих закреплений':$count.' '.($count===1?'текущая работа':'текущие работы')?><?php if(($row['upcomingAssignments']??[])!==[]):?><small>Предстоящее назначение: <?=Html::encode($row['upcomingAssignments'][0]['registration_number'])?></small><?php endif?></td><td><?php $shown=[];foreach($row['assignments']as$a):$shown[(int)$a['object_id']]=true;?><a class="shlz-link fm2-assignment-link" href="/pilot/objects/<?=(int)$a['object_id']?>"><strong><?=Html::encode($a['registration_number'])?></strong><span><?=Html::encode($a['address'])?></span></a><?php endforeach?><?php foreach($row['upcomingAssignments']as$a):if(isset($shown[(int)$a['object_id']]))continue;?><a class="shlz-link fm2-assignment-link" href="/pilot/objects/<?=(int)$a['object_id']?>"><strong><?=Html::encode($a['registration_number'])?></strong><span><?=Html::encode($a['address'])?></span></a><?php endforeach?></td></tr><?php endforeach?></tbody></table></div><?php endif?>
<?= ViewSupport::pagination('/pilot/installers',(int)$filters['page'],(int)$filters['pages'],(int)$filters['total'],50,['q'=>$filters['q'],'status'=>$filters['status'],'availability'=>$filters['availability']],'Страницы монтажников') ?></section></main></div></div>
<?php ViewSupport::end($this);

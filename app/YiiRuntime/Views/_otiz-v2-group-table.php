<?php declare(strict_types=1);

use FMonitor2\YiiRuntime\ViewSupport;
use yii\helpers\Html;

$objects = [];
foreach ($calculation['objects'] as $object) {
    $objects[(string) $object['objectId']] = $object;
}
$percent = static fn (int $part, int $whole): string => $whole > 0
    ? number_format($part * 100 / $whole, 2, ',', ' ') . ' %'
    : '—';
$bp = static fn (array $row, string $key): string => array_key_exists($key, $row)
    ? number_format((int) $row[$key] / 100, 2, ',', ' ') . ' %'
    : '—';
$employment = static fn (string $value): string => match ($value) {
    'employed' => 'Трудоустроен',
    'dismissed' => 'Уволен',
    default => 'Нет данных',
};
$decision = static fn (string $value): string => $value === 'do_not_pay' ? 'Не платить' : 'Платить';
?>
<?php foreach (['objects' => 'object', 'employees' => 'employee'] as $axis => $singular): ?>
<section class="fm2-otiz-surface fm2-otiz-v2-axis" data-grouping="<?=$axis?>"<?=$q['group'] === $axis ? '' : ' hidden'?>>
  <div class="fm2-otiz-v2-table-tools">
    <span><?=$axis === 'objects' ? 'Распределение по объектам' : 'Получатели расчёта'?></span>
    <span><button class="fm2-otiz-v2-text-action" type="button" data-otiz-expand-all>Раскрыть все</button><button class="fm2-otiz-v2-text-action" type="button" data-otiz-collapse-all>Свернуть все</button></span>
  </div>
  <div class="shlz-table-wrap" tabindex="0">
    <table class="shlz-table fm2-otiz-v2-group-table fm2-otiz-v2-group-table--<?=$axis?>">
      <?php if ($axis === 'objects'): ?><colgroup><col><col><col><col><col><col></colgroup>
      <?php else: ?><colgroup><col><col><col><col><col></colgroup><?php endif ?>
      <thead class="shlz-table__head"><tr class="shlz-table__row">
        <?php foreach ($axis === 'objects' ? ['Объект / получатель', 'Объём / доля', 'До уменьшений', 'Уменьшение', 'К выплате', 'Действия'] : ['Монтажник / объект', 'Объектов', 'Доля по объекту', 'К выплате', 'Действия'] as $index => $header): ?>
          <th class="shlz-table__cell" scope="col"><?php if ($index === ($axis === 'objects' ? 5 : 4)): ?><span class="shlz-visually-hidden"><?=$header?></span><?php else: ?><?=$header?><?php endif ?></th>
        <?php endforeach ?>
      </tr></thead>
      <?php foreach ($grouping[$axis]['rows'] as $entry): $parent = $entry['parent']; $key = (string) $parent[$axis === 'objects' ? 'objectId' : 'employeeId']; $target = $singular . '-' . $key; $control = $target . '-details'; ?>
        <?php if ($axis === 'objects'): $object = $objects[$key] ?? $parent; $objectReduction = (int)($object['deadlineCents'] ?? 0) + (int)($object['disciplineCents'] ?? 0); foreach($calculation['deductions']??[]as$deduction)if((int)$deduction['objectId']===(int)$object['objectId']&&($deduction['employeeId']??null)!==null)$objectReduction+=(int)$deduction['amountCents']; $hasDismissed = (bool) array_filter($entry['children'], static fn (array $row): bool => ($row['employment'] ?? '') === 'dismissed'); ?>
        <tbody class="fm2-otiz-v2-parent-body"><tr class="shlz-table__row fm2-otiz-v2-parent-row" id="<?=Html::encode($target)?>" data-otiz-parent-row>
          <th class="shlz-table__cell" scope="row"><div class="fm2-otiz-v2-identity"><button class="fm2-otiz-v2-caret" type="button" data-otiz-group-toggle aria-expanded="false" aria-controls="<?=Html::encode($control)?>"><span class="shlz-visually-hidden">Раскрыть получателей объекта <?=Html::encode((string) $object['regnumber'])?></span></button><span><strong><?=Html::encode((string) $object['regnumber'])?></strong><a href="/pilot/objects/<?=(int) $object['objectId']?>"><?=Html::encode((string) ($object['address'] ?: 'Карточка объекта'))?></a><?php if ($hasDismissed): ?><small class="shlz-status shlz-status--red" data-object-dismissed-recipient>Есть уволенный участник</small><?php endif ?></span></div></th>
          <td class="shlz-table__cell"><strong><?=$bp($object, 'acceptedBp')?> → <?=$bp($object, 'confirmedBp')?></strong><small>+<?=number_format((int) ($object['newBp'] ?? 0) / 100, 2, ',', ' ')?> п.п. нового объёма</small></td>
          <td class="shlz-table__cell shlz-table__cell--numeric"><strong><?=$rub((int) ($object['grossCents'] ?? 0))?></strong></td>
          <td class="shlz-table__cell shlz-table__cell--numeric"><strong><?=$objectReduction > 0 ? '−' . $rub($objectReduction) : '—'?></strong><small>За сроки <?=$rub((int) ($object['deadlineCents'] ?? 0))?> · общие <?=$rub((int) ($object['disciplineCents'] ?? 0))?></small><?php if ($draft): ?><button class="fm2-otiz-v2-inline-action" type="button" data-v2-deduction-open data-object-id="<?=(int) $object['objectId']?>" data-employee-id="">Удержание</button><?php endif ?></td>
          <td class="shlz-table__cell shlz-table__cell--numeric"><strong><?=$rub((int) ($object['payableCents'] ?? $entry['subtotalCents']))?></strong><small><?=count(array_filter($entry['children'], static fn (array $row): bool => (int) ($row['amountCents'] ?? 0) > 0))?> получателей</small><?php if (array_filter($entry['children'], static fn (array $row): bool => (int) ($row['preDeductionCents'] ?? 0) > (int) ($row['originalContributionCents'] ?? 0))): ?><small>Есть перераспределение</small><?php endif ?></td>
          <td class="shlz-table__cell"><button class="fm2-otiz-v2-proof-action" type="button" data-otiz-object-proof="<?=Html::encode((string) $object['regnumber'])?>" aria-label="Как рассчитана сумма по объекту <?=Html::encode((string) $object['regnumber'])?>"></button></td>
        </tr></tbody>
        <?php $preTotal = array_sum(array_map(static fn (array $row): int => (int) ($row['preDeductionCents'] ?? 0), $entry['children'])); $weightTotal = array_sum(array_map(static fn (array $row): int => (int) ($row['weight'] ?? 0), $entry['children'])); ?>
        <tbody id="<?=Html::encode($control)?>" class="fm2-otiz-v2-child-body" data-otiz-detail-row hidden>
          <?php foreach ($entry['children'] as $row): $employeeId = (string) $row['employeeId']; ?>
          <tr class="shlz-table__row fm2-otiz-v2-child-row<?=($row['employment'] ?? '') === 'dismissed' ? ' is-dismissed' : ''?>" data-otiz-allocation-row data-object-id="<?=(int) $row['objectId']?>" data-employee-id="<?=Html::encode($employeeId)?>">
            <th class="shlz-table__cell" scope="row"><a href="#employee-<?=Html::encode($employeeId)?>" data-otiz-crosslink data-target-group="employees" data-target-id="employee-<?=Html::encode($employeeId)?>"><?=Html::encode((string) $row['name'])?></a><small>Таб. № <?=Html::encode((string) $row['tabNumber'])?> · <?=$employment((string) ($row['employment'] ?? 'unknown'))?><?php if (!empty($row['excluded'])): ?> · исключён<?php endif ?></small><?php if ($draft): ?><button class="fm2-otiz-v2-inline-action" type="button" data-v2-decision-open data-employee-id="<?=Html::encode($employeeId)?>"><?=((int)($row['reservedCents']??0)>0?'В резерве · Решение':$decision((string)($row['decision']??'pay')))?></button><?php else: ?><small><?=((int)($row['reservedCents']??0)>0?'В резерве':$decision((string)($row['decision']??'pay')))?></small><?php endif ?></th>
            <td class="shlz-table__cell"><strong><?=$percent((int) ($row['preDeductionCents'] ?? 0), $preTotal)?> к распределению</strong><small>Исходный вклад <?=$percent((int) ($row['weight'] ?? 0), $weightTotal)?></small></td>
            <td class="shlz-table__cell shlz-table__cell--numeric"><?=$rub((int) ($row['preDeductionCents'] ?? 0))?></td>
            <td class="shlz-table__cell shlz-table__cell--numeric"><strong><?=((int) ($row['deductionCents'] ?? 0)) > 0 ? '−' . $rub((int) $row['deductionCents']) : '—'?></strong><?php if ($draft && empty($row['excluded'])): ?><button class="fm2-otiz-v2-inline-action" type="button" data-v2-deduction-open data-object-id="<?=(int) $row['objectId']?>" data-employee-id="<?=Html::encode($employeeId)?>">Личное удержание</button><?php endif ?></td>
            <td class="shlz-table__cell shlz-table__cell--numeric"><strong data-allocation-amount data-allocation-amount-cents="<?=(int) $row['amountCents']?>"><?=$rub((int) $row['amountCents'])?></strong><?php $redistributed = (int) ($row['preDeductionCents'] ?? 0) - (int) ($row['originalContributionCents'] ?? 0); ?><?php if ((int) ($row['reservedCents'] ?? 0) > 0): ?><small>Сохранено <?=$rub((int) $row['reservedCents'])?> · <?=Html::encode((string) ($row['reserveReason'] ?? ''))?></small><?php elseif ($redistributed > 0): ?><small>+<?=$rub($redistributed)?> от перераспределения</small><?php endif ?></td>
            <td class="shlz-table__cell"><a class="fm2-otiz-v2-row-link" href="#employee-<?=Html::encode($employeeId)?>" data-otiz-crosslink data-target-group="employees" data-target-id="employee-<?=Html::encode($employeeId)?>" aria-label="Все объекты получателя <?=Html::encode((string) $row['name'])?>"></a></td>
          </tr><?php endforeach ?>
        </tbody>
        <?php else: $employeeId = (string) $parent['employeeId']; $dismissedEmployee = ($parent['employment'] ?? '') === 'dismissed'; ?>
        <tbody class="fm2-otiz-v2-parent-body"><tr class="shlz-table__row fm2-otiz-v2-parent-row<?=$dismissedEmployee ? ' is-dismissed' : ''?>" id="<?=Html::encode($target)?>" data-otiz-parent-row data-otiz-employee-group>
          <th class="shlz-table__cell" scope="row"><div class="fm2-otiz-v2-identity"><button class="fm2-otiz-v2-caret" type="button" data-otiz-employee-toggle aria-expanded="false" aria-controls="<?=Html::encode($control)?>"><span class="shlz-visually-hidden">Раскрыть объекты монтажника <?=Html::encode((string) $parent['name'])?></span></button><span><strong><?=Html::encode((string) $parent['name'])?></strong><small>Таб. № <?=Html::encode((string) $parent['tabNumber'])?> · <?=$employment((string) ($parent['employment'] ?? 'unknown'))?></small><?php if ($draft): ?><button class="fm2-otiz-v2-inline-action" type="button" data-v2-decision-open data-employee-id="<?=Html::encode($employeeId)?>"><?=((int)($parent['reservedCents']??0)>0?'В резерве · Решение':$decision((string)($parent['decision']??'pay')))?></button><?php else: ?><small><?=((int)($parent['reservedCents']??0)>0?'В резерве':$decision((string)($parent['decision']??'pay')))?></small><?php endif ?></span></div></th>
          <td class="shlz-table__cell"><?=count($entry['children'])?></td><td class="shlz-table__cell">—</td>
          <td class="shlz-table__cell shlz-table__cell--numeric"><strong><?=$rub((int) $entry['subtotalCents'])?></strong><small>Всего по расчёту</small></td><td class="shlz-table__cell"></td>
        </tr></tbody>
        <tbody id="<?=Html::encode($control)?>" class="fm2-otiz-v2-child-body" data-otiz-detail-row hidden>
          <?php foreach ($entry['children'] as $row): $object = $objects[(string) $row['objectId']] ?? []; $siblings = array_values(array_filter($calculation['recipients'], static fn (array $candidate): bool => (string) $candidate['objectId'] === (string) $row['objectId'])); $preTotal = array_sum(array_map(static fn (array $candidate): int => (int) ($candidate['preDeductionCents'] ?? 0), $siblings)); $weightTotal = array_sum(array_map(static fn (array $candidate): int => (int) ($candidate['weight'] ?? 0), $siblings)); ?>
          <tr class="shlz-table__row fm2-otiz-v2-child-row" data-object-contribution data-otiz-allocation-row data-object-id="<?=(int) $row['objectId']?>" data-employee-id="<?=Html::encode($employeeId)?>">
            <th class="shlz-table__cell" scope="row"><a href="#object-<?=(int) $row['objectId']?>" data-otiz-crosslink data-target-group="objects" data-target-id="object-<?=(int) $row['objectId']?>"><?=Html::encode((string) ($object['regnumber'] ?? ('Объект ' . $row['objectId'])))?></a><small><?=Html::encode((string) ($object['address'] ?? ''))?></small></th>
            <td class="shlz-table__cell">—</td><td class="shlz-table__cell"><strong><?=$percent((int) ($row['preDeductionCents'] ?? 0), $preTotal)?></strong><small>Исходный вклад <?=$percent((int) ($row['weight'] ?? 0), $weightTotal)?></small></td>
            <td class="shlz-table__cell shlz-table__cell--numeric"><strong data-allocation-amount data-allocation-amount-cents="<?=(int) $row['amountCents']?>"><?=$rub((int) $row['amountCents'])?></strong><small><?php if ((int) ($row['reservedCents'] ?? 0) > 0): ?>Сохранено <?=$rub((int) $row['reservedCents'])?> · <?=Html::encode((string) ($row['reserveReason'] ?? ''))?><?php else: ?>До личного удержания <?=$rub((int) ($row['preDeductionCents'] ?? 0))?><?php if ((int) ($row['deductionCents'] ?? 0) > 0): ?> · удержано <?=$rub((int) $row['deductionCents'])?><?php endif ?><?php endif ?></small><?php if ($draft && empty($row['excluded'])): ?><button class="fm2-otiz-v2-inline-action" type="button" data-v2-deduction-open data-object-id="<?=(int) $row['objectId']?>" data-employee-id="<?=Html::encode($employeeId)?>">Удержание</button><?php endif ?></td>
            <td class="shlz-table__cell"><a class="fm2-otiz-v2-row-link" href="#object-<?=(int) $row['objectId']?>" data-otiz-crosslink data-target-group="objects" data-target-id="object-<?=(int) $row['objectId']?>" aria-label="Распределение объекта <?=Html::encode((string) ($object['regnumber'] ?? $row['objectId']))?>"></a></td>
          </tr><?php endforeach ?>
        </tbody>
        <?php endif ?>
      <?php endforeach ?>
      <tfoot><tr class="shlz-table__row"><th class="shlz-table__cell" colspan="<?=$axis === 'objects' ? 4 : 3?>">Общий итог</th><td class="shlz-table__cell shlz-table__cell--numeric"><strong><?=$rub((int) $calculation['totalCents'])?></strong></td><td class="shlz-table__cell"></td></tr></tfoot>
    </table>
  </div>
  <?php $pageQuery=['group'=>$axis,'q'=>$q['q'],'pageSize'=>$q['pageSize']];foreach(['excludeFrame','excludeCompleted','excludeDismissed','onlyReserved']as$filter)if(!empty($q[$filter]))$pageQuery[$filter]='1';$pageTotal=(int)$grouping[$axis]['total'];$pageFrom=$pageTotal===0?0:((int)$grouping[$axis]['page']-1)*(int)$q['pageSize']+1;$pageTo=min($pageTotal,(int)$grouping[$axis]['page']*(int)$q['pageSize']);?>
  <div class="fm2-otiz-v2-pagination-row">
    <form class="fm2-otiz-v2-page-size" method="get"><input type="hidden" name="group" value="<?=Html::encode($axis)?>"><input type="hidden" name="q" value="<?=Html::encode((string)$q['q'])?>"><?php foreach(['excludeFrame','excludeCompleted','excludeDismissed','onlyReserved']as$filter)if(!empty($q[$filter])):?><input type="hidden" name="<?=$filter?>" value="1"><?php endif?><label for="otiz-page-size-<?=$axis?>">Строк на странице</label><select class="shlz-input" id="otiz-page-size-<?=$axis?>" name="pageSize" data-otiz-page-size><?php foreach([10,25,50,100]as$size):?><option value="<?=$size?>"<?=$size===(int)$q['pageSize']?' selected':''?>><?=$size?></option><?php endforeach?></select><noscript><button class="shlz-button shlz-button--secondary">Показать</button></noscript></form>
    <div class="fm2-otiz-v2-page-navigation"><span class="fm2-otiz-v2-page-count">Страница <?=(int)$grouping[$axis]['page']?> из <?=(int)$grouping[$axis]['pages']?></span><?=ViewSupport::pagination('/pilot/otiz/calculations/'.$id,(int)$grouping[$axis]['page'],(int)$grouping[$axis]['pages'],$pageTotal,(int)$q['pageSize'],$pageQuery,'Страницы '.($axis==='objects'?'объектов':'монтажников'))?><?php if((int)$grouping[$axis]['pages']<=1):?><span class="shlz-pagination__summary"><?=$pageFrom?>–<?=$pageTo?> из <?=$pageTotal?></span><?php endif?></div>
  </div>
</section>
<?php endforeach ?>

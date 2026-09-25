<?php declare(strict_types=1);

use yii\helpers\Html;

$money = static fn(int $cents): string => ($cents < 0 ? '−' : '').number_format(abs($cents) / 100, 2, ',', ' ').' ₽';
$percent = static fn(int $basisPoints): string => number_format($basisPoints / 100, 2, ',', ' ').' %';
$coefficient = static fn(int $basisPoints): string => number_format($basisPoints / 10000, 2, ',', ' ');
$snapshotDate = static function (?string $value): string {
    if ($value !== null && preg_match('/^(\d{4})-(\d{2})-(\d{2})/D', $value, $matches)) {
        return $matches[3].'.'.$matches[2].'.'.$matches[1];
    }
    return 'Не сохранено в этом расчёте';
};
$basis = trim((string)($a['participation_basis'] ?? ''));
?>
<details data-installer-allocation-details data-installer-tab="<?=Html::encode((string)$a['tab_id'])?>">
<summary><?=Html::encode((string)$a['full_name'])?> · <?=$money((int)$a['amount_cents'])?></summary>
<dl>
<div><dt>Табельный номер</dt><dd><?=Html::encode((string)$a['tab_id'])?></dd></div>
<div><dt>Расчётная дата</dt><dd><?=$snapshotDate(isset($s['report_date']) ? (string)$s['report_date'] : null)?></dd></div>
<div><dt>Сумма к распределению</dt><dd><?=$money((int)$o['distributed_cents'])?></dd></div>
<div><dt>Вклад</dt><dd><?=$percent((int)$a['contribution_bp'])?></dd></div>
<div><dt>КТУ</dt><dd><?=$coefficient((int)$a['effective_ktu_bp'])?></dd></div>
<div><dt>Доля</dt><dd><?=$percent((int)$a['share_bp'])?></dd></div>
<div><dt>Основание участия</dt><dd><?=Html::encode($basis !== '' ? $basis : 'Не сохранено в этом расчёте')?></dd></div>
<div><dt>Итог работника в расчёте</dt><dd><?=$money((int)$a['amount_cents'])?></dd></div>
</dl>
</details>

<?php declare(strict_types=1);
use FMonitor2\YiiRuntime\ViewSupport;
use yii\helpers\Html;
ViewSupport::begin($this, "Справки о переносе срока", $identity);
$current = $history["current"];
?>
<nav class="fm2-breadcrumb"><a href="/pilot/objects/<?= (int) $objectId ?>">Объект монтажа № <?= (int) $objectId ?></a></nav><header class="fm2-page-header"><div><h1>Справки о переносе срока монтажа</h1><p>Текущий срок: <?= Html::encode(
    $currentDeadline,
) ?></p></div></header>
<?php if (
    $canWrite
): ?><section class="fm2-panel"><form method="post" enctype="multipart/form-data"><input type="hidden" name="_csrf" value="<?= Html::encode(
    $csrf,
) ?>"><input type="hidden" name="requestId" value="<?= Html::encode(
    ViewSupport::uuid(),
) ?>"><input type="hidden" name="expectedVersion" value="<?= count(
    $history["history"],
) ?>"><label>Дата справки<input type="date" name="certificateDate" required></label><label>Новый срок<input type="date" name="newDeadline" required></label><label>Причина исправления<input name="correctionReason"></label><label>PDF<input type="file" name="pdf" accept="application/pdf" required></label><button class="shlz-button shlz-button--primary" type="submit">Сохранить справку</button></form></section><?php endif; ?>
<section class="fm2-panel"><h2>История</h2><table><thead><tr><th>Редакция</th><th>Дата справки</th><th>Новый срок</th><th>Автор</th><th>Записано</th><th>Причина</th><th>PDF</th></tr></thead><tbody><?php foreach (
    $history["history"]
    as $r
): ?><tr><td><?= $r["revisionNumber"] ?></td><td><?= Html::encode($r["certificateDate"]) ?></td><td><?= Html::encode(
    $r["newDeadline"],
) ?></td><td><?= Html::encode(
    ($actorNames[$r["actorId"]] ?? "") . " · " . $r["actorId"] . " ",
) ?></td><td><?= Html::encode($r["recordedAt"]) ?></td><td><?= Html::encode(
    $r["correctionReason"] ?? "—",
) ?></td><td><a href="/pilot/objects/<?= (int) $objectId ?>/deadline-certificates/<?= (int) $r[
    "id"
] ?>/pdf">Скачать</a></td></tr><?php endforeach; ?></tbody></table></section>
<?php ViewSupport::end($this);

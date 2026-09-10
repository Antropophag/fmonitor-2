<?php

declare(strict_types=1);

use FMonitor2\YiiRuntime\ViewSupport;
use yii\helpers\Html;

$objectId = (int) $history['objectId'];
$orderId = (int) $history['orderId'];
$base = '/pilot/objects/' . $objectId . '/assignment-orders/' . $orderId . '/originals';
$instant = static function (string $value): string {
    try { return (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('Europe/Moscow'))->format('d.m.Y, H:i'); } catch (Throwable) { return 'Время недоступно'; }
};
$size = static fn(int $bytes): string => $bytes < 1024 ? $bytes . ' Б' : number_format($bytes / 1024, $bytes < 10240 ? 1 : 0, ',', ' ') . ' КиБ';
ViewSupport::begin($this, 'История оригинала', $identity);
?>
<nav class="fm2-breadcrumb" aria-label="Хлебные крошки"><a class="fm2-breadcrumb-link" href="/pilot/objects">Объекты монтажа</a><span aria-hidden="true">/</span><a class="fm2-breadcrumb-link" href="/pilot/objects/<?= $objectId ?>">Объект № <?= $objectId ?></a><span aria-hidden="true">/</span><span aria-current="page">История оригинала</span></nav>
<div class="fm2-page-header fm2-order-heading"><div><h1>История оригинала</h1><p>Распоряжение · версия <?= (int) $history['orderVersion'] ?>. Все принятые редакции сохранены.</p></div><span class="fm2-result-count"><?= (int) $history['totalRevisions'] ?> редакций</span></div>
<section class="fm2-order-surface"><header><div class="fm2-order-object"><strong>Объект монтажа № <?= $objectId ?></strong><span>Распоряжение № <?= $orderId ?></span><small>Текущая редакция отмечена в списке</small></div></header><div class="shlz-document-list fm2-order-documents">
    <?php foreach ($history['revisions'] as $revision): $current = $revision['revisionId'] === $history['currentRevisionId']; ?>
        <article class="shlz-document-row"><div><a class="shlz-link" href="<?= $base ?>/<?= Html::encode($revision['revisionId']) ?>/download">Подписанный оригинал.pdf</a><strong>Редакция <?= (int) $revision['revisionNumber'] ?><?= $current ? ' · текущая' : '' ?></strong><p>Дата распоряжения: <time datetime="<?= Html::encode($revision['documentDate']) ?>"><?= Html::encode((new DateTimeImmutable($revision['documentDate']))->format('d.m.Y')) ?></time></p><?php if ($revision['correctionReason']): ?><p>Причина исправления: <?= Html::encode($revision['correctionReason']) ?></p><?php endif ?></div><small>Принял: <?= Html::encode($revision['actorName'] ?? ('пользователь ID ' . $revision['actorUserId'])) ?><br><time datetime="<?= Html::encode($revision['uploadedAt']) ?>"><?= $instant($revision['uploadedAt']) ?></time> МСК<br><?= Html::encode($size((int) $revision['byteSize'])) ?></small></article>
    <?php endforeach ?>
</div><footer class="fm2-order-actions"><a class="shlz-link" href="/pilot/objects/<?= $objectId ?>">К карточке объекта</a><a class="shlz-link" href="<?= $base ?>/submit">К форме оригинала</a></footer></section>
<?php ViewSupport::end($this);

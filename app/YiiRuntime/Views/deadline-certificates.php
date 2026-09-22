<?php
declare(strict_types=1);
use FMonitor2\YiiRuntime\ViewSupport;
use yii\helpers\Html;
ViewSupport::begin($this,'Справки о переносе срока',$identity);
?>
<nav class="fm2-breadcrumb"><a href="/pilot/objects/<?=(int)$objectId?>">Объект монтажа № <?=(int)$objectId?></a></nav>
<header class="fm2-page-header fm2-certificate-heading"><div><h1>Справки о переносе срока монтажа</h1><p>Текущий срок: <?=Html::encode($currentDeadline)?></p></div></header>
<?php if($canWrite):?><section class="fm2-panel"><form method="post" enctype="multipart/form-data"><input type="hidden" name="_csrf" value="<?=Html::encode($csrf)?>"><input type="hidden" name="requestId" value="<?=Html::encode(ViewSupport::uuid())?>"><input type="hidden" name="expectedVersion" value="<?=count($history['history'])?>">
<label class="shlz-field"><span class="shlz-field__label">Дата справки</span><span class="shlz-field__control"><input class="shlz-input" type="date" name="certificateDate" required></span></label>
<label class="shlz-field"><span class="shlz-field__label">Новый срок</span><span class="shlz-field__control"><input class="shlz-input" type="date" name="newDeadline" required></span></label>
<label class="shlz-field"><span class="shlz-field__label">Причина исправления</span><span class="shlz-field__control"><input class="shlz-input" name="correctionReason"></span></label>
<label class="shlz-field"><span class="shlz-field__label">PDF</span><span class="shlz-field__control"><input class="shlz-input" type="file" name="pdf" accept="application/pdf" required></span><span class="shlz-field__secondary">Один PDF-файл со справкой.</span></label>
<button class="shlz-button shlz-button--primary" type="submit">Сохранить справку</button></form></section><?php endif?>
<section class="fm2-panel"><h2>История</h2><div class="shlz-table-wrap" tabindex="0" aria-label="История справок о переносе срока"><table class="shlz-table"><thead class="shlz-table__head"><tr class="shlz-table__row"><?php foreach(['Редакция','Дата справки','Новый срок','Автор','Записано','Причина','PDF'] as $heading):?><th class="shlz-table__cell" scope="col"><?=Html::encode($heading)?></th><?php endforeach?></tr></thead><tbody>
<?php foreach($history['history'] as $revision):?><tr class="shlz-table__row"><td class="shlz-table__cell"><?=(int)$revision['revisionNumber']?></td><td class="shlz-table__cell"><?=Html::encode($revision['certificateDate'])?></td><td class="shlz-table__cell"><?=Html::encode($revision['newDeadline'])?></td><td class="shlz-table__cell"><?=Html::encode(($actorNames[$revision['actorId']]??'').' · '.$revision['actorId'])?></td><td class="shlz-table__cell"><?=Html::encode($revision['recordedAt'])?></td><td class="shlz-table__cell"><?=Html::encode($revision['correctionReason']??'—')?></td><td class="shlz-table__cell"><a class="shlz-link" href="/pilot/objects/<?=(int)$objectId?>/deadline-certificates/<?=(int)$revision['id']?>/pdf">Скачать</a></td></tr><?php endforeach?>
</tbody></table></div></section>
<?php ViewSupport::end($this);

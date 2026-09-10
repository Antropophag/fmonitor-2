<?php declare(strict_types=1);
use FMonitor2\YiiRuntime\ViewSupport;use yii\helpers\Html;
$sections=[1=>['Демонтаж старого оборудования',[[28,'Зачистка шахты, переделка кабины',2],[29,'Демонтаж направляющих',2],[30,'Демонтаж кронштейнов',2],[31,'Демонтаж ДШ',2],[32,'Демонтаж МП (СУ; СПЧ; ВУ)',1],[33,'Демонтаж каркаса кабины',1],[34,'Демонтаж противовеса',2],[35,'Демонтаж приямка, НУ',1],[36,'Демонтаж лебёдки, ОС',2]]],2=>['Монтаж направляющих',[[37,'Монтаж основных кронштейнов',3],[38,'Монтаж кронштейнов противовеса',3],[39,'Монтаж основных направляющих',3],[40,'Монтаж направляющих противовеса',3],[41,'Выверка направляющих, зачистка стыков',2]]],3=>['Монтаж кабины и противовеса',[[1,'Монтаж каркаса кабины',2],[2,'Монтаж противовеса',2],[3,'Загрузка противовеса',1],[4,'Сборка купе кабины',2],[5,'Установка привода и ДК',1],[6,'Монтаж буферов',1]]],4=>['Монтаж ДШ',[[7,'Сборка порталов',2],[8,'Монтаж ДШ',5],[9,'Установка фартуков',1],[10,'Монтаж вызывных постов и этажных указателей',1]]],5=>['Монтаж в МП',[[11,'Монтаж лебёдки',3],[12,'Запасовка канатов',2],[13,'Монтаж ОС; НУ (запасовка)',2],[14,'Монтаж СУЛ с ПЧ; ВУ',1],[15,'Разварка контура МП; шахты; приямка',2]]],6=>['ПНР',[[16,'Электромонтаж по шахте',4],[17,'Электроразводка по МП',4],[18,'Наладка ДШ',3],[19,'Установка шунтов и датчиков',3],[20,'Пуск лифта в «нормальную» работу',3],[21,'Диспетчеризация',2]]],7=>['Стройотделка',[[22,'Установка ОДШЛ; порогов (покраска)',3],[23,'СОР МП; шахты; приямка',2],[24,'Примыкания ДШ (в шахте)',1],[25,'Покраска сварных соединений, контура, шахты',1],[26,'Освещение МП; шахты',1],[27,'Установка противопожарных дверей; люков',1]]]];
$enabled=(bool)$access['opened']&&((bool)$access['roleAccess']||(bool)$access['itemComplete']);$legacy=(bool)$access['opened']&&(bool)$access['roleAccess'];$assigned=(bool)$access['opened']&&(bool)$access['assigned'];
ViewSupport::begin($this,'Чек-лист объекта № '.$id,$identity);?>
<div class="fm2-check-page" data-checklist data-user-id="<?= (int)$identity->getId()?>" data-object-id="<?= $id?>" data-csrf="<?=Html::encode($csrf)?>" data-projection="<?=Html::encode(base64_encode(json_encode($projection,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)))?>" data-item-completion-enabled="<?=$enabled?'true':'false'?>" data-legacy-operations-enabled="<?=$legacy?'true':'false'?>" data-assigned-correction-enabled="<?=$assigned?'true':'false'?>" data-photo-revoke-enabled="<?=$assigned&&$access['photoRevoke']?'true':'false'?>" data-enabled="<?=$enabled?'true':'false'?>">
<header class="fm2-check-hero">
<div class="fm2-check-topline">
<a class="fm2-back-link" href="<?=$fromControl?'/pilot/construction-control':'/pilot/objects/'.$id?>">
<?=$fromControl?'Стройконтроль':'Карточка объекта'?>
</a>
<div class="fm2-sync-banner" data-sync-banner role="status">
<span data-sync-copy>Синхронизировано</span>
<button type="button" data-sync-now hidden>Повторить отправку</button>
</div>
</div>
<div class="fm2-check-object">
<h1>
<?=Html::encode($access['address'])?>, подъезд <?=Html::encode($access['entrance'])?>
</h1>
<p>Рег. № <?=Html::encode($access['registrationNumber'])?>
</p>
</div>
<div class="fm2-progress-summary">
<strong>
<span data-total-progress data-progress-cap="<?=$progressCap?>">0</span>%</strong>
<span>
<span data-total-items>0</span> из 41 монтажной работы</span>
</div>
</header>
<?php if(!$enabled):?>
<div class="fm2-check-gate" role="status">
<strong>Чек-лист недоступен</strong>
<span>Для действий требуются открытые работы и полномочия.</span>
</div>
<?php endif?>
<main class="fm2-check-layout">
<div class="fm2-check-content">
<?php foreach($sections as$sectionId=>[$title,$items]):$weight=array_sum(array_column($items,2));?>
<section class="fm2-check-section<?=$sectionId===1?' is-open':''?>" data-check-section="<?=$sectionId?>" data-section-weight="<?=$weight?>"<?=$enabled?'':' inert'?>>
<button class="fm2-check-section-head fm2-section-toggle" type="button" aria-expanded="false">
<span class="fm2-section-title">
<strong>
<?=Html::encode($title)?>
</strong>
<small>
<span data-section-count>0 из <?=count($items)?>
</span> · <span data-section-progress>0 из <?=$weight?>%</span>
<span data-section-state>Не начат</span>
</small>
</span>
</button>
<div class="fm2-check-section-body">
<div class="fm2-check-items">
<button class="fm2-check-all" type="button" data-check-all<?=$legacy?'':' disabled'?>>
<span class="fm2-check-box">
</span>
<span>Отметить все работы раздела</span>
</button>
<?php foreach($items as[$item,$name,$share]):?>
<div class="fm2-check-item" data-check-item="<?=$item?>" data-weight="<?=$share?>">
<button class="fm2-check-toggle" type="button" role="checkbox" aria-checked="false"<?=$enabled?'':' disabled'?>>
<span class="fm2-check-box">
</span>
<span class="fm2-check-label">
<?=Html::encode($name)?>
<small data-item-sync>Не отмечено</small>
</span>
<span class="fm2-check-weight">+<?=$share?>%</span>
</button>
<button class="fm2-item-installers" type="button" data-installer-edit<?=$legacy?'':' disabled'?>>
<span data-installer-summary>Все монтажники объекта</span>
<small>Изменить исполнителей</small>
</button>
</div>
<?php endforeach?>
</div>
<div class="fm2-photo-zone">
<div class="fm2-photo-copy">
<strong>Фото раздела</strong>
<span>Минимум одно фото для завершения</span>
</div>
<div class="fm2-photo-actions">
<label class="shlz-button fm2-camera-action">Снять фото<input type="file" accept="image/*" capture="environment" data-photo-input<?=$legacy?'':' disabled'?>>
</label>
<label class="fm2-file-action">Выбрать с устройства<input type="file" accept="image/jpeg,image/png,image/webp" multiple data-photo-input<?=$legacy?'':' disabled'?>>
</label>
</div>
<div class="fm2-photo-strip" data-photo-strip>
<span class="fm2-photo-empty">Фотографий пока нет</span>
</div>
</div>
</div>
</section>
<?php endforeach?>
<section class="fm2-check-section fm2-check-section--completion" data-check-section="8">
<div class="fm2-check-closeout">
<div>
<strong>Документарное закрытие</strong>
<p>Последние 15% закрываются актом ПТО и декларацией.</p>
</div>
<a class="shlz-button shlz-button--primary" href="/pilot/objects/<?=$id?>#completion">К документарному закрытию</a>
</div>
</section>
</div>
</main>
<button class="fm2-all-photos" type="button" data-open-gallery>Все фото <span data-photo-total>0</span>
</button>
<section class="fm2-gallery-page" data-gallery hidden>
<button type="button" data-close-gallery>К чек-листу</button>
<div data-gallery-content>
</div>
</section>
<dialog class="fm2-confirm-dialog" data-bulk-dialog>
<div>
<h2>Отметить весь раздел?</h2>
<p>Все работы в разделе <strong data-bulk-section-name>
</strong> будут отмечены.</p>
<button type="button" data-bulk-cancel>Отмена</button>
<button type="button" data-bulk-confirm>Отметить все</button>
</div>
</dialog>
<dialog class="fm2-installer-dialog" data-installer-dialog>
<div>
<header>
<h2>Исполнители работы</h2>
<p data-installer-item-name>
</p>
<button type="button" data-installer-cancel>×</button>
</header>
<div data-installer-options>
</div>
<p data-installer-error hidden>Выберите хотя бы одного монтажника.</p>
<button type="button" data-installer-cancel>Отмена</button>
<button type="button" data-installer-save>Сохранить состав</button>
</div>
</dialog>
<div class="fm2-toast" data-toast hidden>
</div>
</div>
<script src="/pilot/assets/checklist.js?v=20260910-1" defer>
</script>
<?php ViewSupport::end($this);

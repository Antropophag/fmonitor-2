<?php declare(strict_types=1);
namespace FMonitor2\PilotHttp;
final class ProductionPilotShellRenderer implements PilotShellRenderer
{
    public function render(HttpUser $user):string{return PilotView::document($user,'Моя работа','Моя работа','<div class="fm2-eyebrow">Моя работа</div>','<div class="fm2-page-header"><h1>Моя работа</h1><p><span class="shlz-tag">Пилот подключён</span></p><p>Объекты монтажа появятся после подключения карточки.</p></div><section class="fm2-next"><h2>Очередь объектов</h2><p>Откройте очередь, чтобы выбрать объект и подготовить распоряжение.</p><a class="shlz-button" href="/pilot/objects">Перейти к объектам монтажа</a></section>');}
}

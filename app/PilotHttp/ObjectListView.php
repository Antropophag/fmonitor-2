<?php declare(strict_types=1);
namespace FMonitor2\PilotHttp;
// Owns the queue <main composition supplied to the shared document shell.
final class ProductionObjectListRenderer implements ObjectListRenderer
{
    public function render(HttpUser $user,array $objects):string
    {
        $body='<div class="fm2-eyebrow">Моя работа</div><div class="fm2-page-header"><h1>Объекты монтажа</h1><p>Выберите объект монтажа, чтобы продолжить подготовку работ.</p></div>';
        if($objects===[])$body.='<section class="shlz-empty-state fm2-empty"><strong>Сейчас нет объектов монтажа</strong><p>Импортированные объекты монтажа пока отсутствуют.</p><a class="shlz-link" href="/pilot/objects">Обновить страницу</a></section>';
        else{$body.='<ul class="fm2-queue-list">';foreach($objects as$o){$e=[PilotView::class,'e'];$id=$e($o['id']);$body.='<li class="fm2-queue-item"><div class="fm2-queue-identity"><a class="shlz-link" href="/pilot/objects/'.$id.'">'.$id.'</a><strong class="fm2-db-text">'.$e($o['registrationNumber']).'</strong></div><div class="fm2-db-text">'.$e($o['address']).' · Подъезд '.$e($o['entrance']).'</div><div>'.$e($o['plannedStartDate']).' — '.$e($o['plannedFinishDate']).'</div><span class="fm2-queue-hint">Открыть карточку объекта монтажа</span></li>';}$body.='</ul>';}
        return PilotView::document($user,'Объекты монтажа','Объекты монтажа','',$body);
    }
}

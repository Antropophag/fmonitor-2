<?php declare(strict_types=1);
namespace FMonitor2\PilotHttp;
// Owns the queue <main composition supplied to the shared document shell.
final class ProductionObjectListRenderer implements ObjectListRenderer,CompatibilityObjectListRenderer
{
    public function render(HttpUser $user,array $objects):string
    {
        $body='<div class="fm2-eyebrow">Моя работа</div><div class="fm2-page-header"><h1>Объекты монтажа</h1><p>Выберите объект монтажа, чтобы продолжить подготовку работ.</p></div>';
        if($objects===[])$body.='<section class="shlz-empty-state fm2-empty"><strong>Сейчас нет объектов монтажа</strong><p>Импортированные объекты монтажа пока отсутствуют.</p><a class="shlz-link" href="/pilot/objects">Обновить страницу</a></section>';
        else{$body.='<ul class="fm2-queue-list">';foreach($objects as$o){$e=[PilotView::class,'e'];$id=$e($o['id']);$body.='<li class="fm2-queue-item"><div class="fm2-queue-identity"><a class="shlz-link" href="/pilot/objects/'.$id.'">'.$id.'</a><strong class="fm2-db-text">'.$e($o['registrationNumber']).'</strong></div><div class="fm2-db-text">'.$e($o['address']).' · Подъезд '.$e($o['entrance']).'</div><div>'.$e($o['plannedStartDate']).' — '.$e($o['plannedFinishDate']).'</div><span class="fm2-queue-hint">Открыть карточку объекта монтажа</span></li>';}$body.='</ul>';}
        return PilotView::document($user,'Объекты монтажа','Объекты монтажа','',$body);
    }
    public function renderCompatibility(HttpUser $user,array $objects):string
    {
        $body='<h1>Объекты монтажа</h1>';$body.=$objects===[]?'<p>Импортированные объекты монтажа пока отсутствуют.</p>':'<ul>';foreach($objects as$o)$body.='<li><a class="shlz-link" href="/pilot/objects/'.PilotView::e($o['id']).'">'.PilotView::e($o['id']).'</a><span>'.PilotView::e($o['registrationNumber']).' · '.PilotView::e($o['address']).' · '.PilotView::e($o['entrance']).' · '.PilotView::e($o['plannedStartDate']).' · '.PilotView::e($o['plannedFinishDate']).'</span></li>';if($objects!==[])$body.='</ul>';
        return '<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Объекты монтажа — FMonitor 2.0</title><link rel="stylesheet" href="/pilot/assets/shlz.css"></head><body class="shlz-scope"><a class="shlz-link" href="#main-content">Перейти к содержанию</a><header><strong>FMonitor 2.0</strong><span>'.PilotView::e($user->displayName).'</span></header><nav aria-label="Основная навигация"><a class="shlz-link" href="/pilot/">Моя работа</a><a class="shlz-link" href="/pilot/objects" aria-current="page">Объекты монтажа</a></nav><main id="main-content" tabindex="-1">'.$body.'</main><script src="/pilot/assets/navigation.js"></script></body></html>';
    }
}

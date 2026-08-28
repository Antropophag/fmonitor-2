<?php declare(strict_types=1);
namespace FMonitor2\PilotHttp;
final class ProductionObjectListRenderer implements ObjectListRenderer
{
    public function render(HttpUser $user,array $objects):string
    {
        $body='<div class="fm2-eyebrow">Моя работа</div><div class="fm2-page-header"><h1>Объекты монтажа</h1><p>Выберите объект монтажа, чтобы продолжить подготовку работ.</p></div>';
        if($objects===[])$body.='<section class="shlz-empty-state fm2-empty"><strong>Сейчас нет объектов монтажа</strong><p>Импортированные объекты монтажа пока отсутствуют.</p><a class="shlz-link" href="/pilot/objects">Обновить страницу</a></section>';
        else{$body.='<p class="fm2-summary">В очереди: '.\count($objects).'</p><table class="shlz-table fm2-queue-table"><thead><tr><th>Объект монтажа</th><th>План</th><th>Состояние</th><th>Следующий шаг</th></tr></thead><tbody>';foreach($objects as$o){$e=[PilotView::class,'e'];$status=$o['status']??'Требуется распоряжение';$next=match($status){'Распоряжение подготовлено'=>'Внести номер 1С ДО в карточке объекта','Готов к открытию'=>'Открыть работы в карточке объекта','В работе'=>'Перейти в карточку объекта',default=>'Выбрать состав'};$href=$status==='Требуется распоряжение'?'/pilot/objects/'.$e($o['id']).'/assignment-order/prepare':'/pilot/objects/'.$e($o['id']);$identity='<strong>'.$e($o['registrationNumber']).'</strong><span> № '.$e($o['id']).'</span>';$action=$status==='Требуется распоряжение'?'<a class="shlz-link" href="'.$href.'">Выбрать состав</a>':'<span>'.$e($next).'</span>';$identity=$status==='Требуется распоряжение'?'<span class="fm2-db-text">'.$identity.'</span>':'<a class="shlz-link fm2-db-text" href="'.$href.'">'.$identity.'</a>';$body.='<tr><td data-label="Объект монтажа">'.$identity.'<span class="fm2-db-text">'.$e($o['address']).', подъезд '.$e($o['entrance']).'</span></td><td data-label="План"><span>'.$e($o['plannedStartDate']).'</span><span> — '.$e($o['plannedFinishDate']).'</span></td><td data-label="Состояние"><span class="shlz-status">'.$e($status).'</span></td><td data-label="Следующий шаг">'.$action.'</td></tr>';}$body.='</tbody></table>';}
        return PilotView::document($user,'Объекты монтажа','Объекты монтажа',PilotView::breadcrumb([['Моя работа','/pilot/']],'Объекты монтажа'),$body);
    }
}

<?php declare(strict_types=1);
namespace FMonitor2\PilotHttp;

final class ProductionObjectListRenderer implements ObjectListRenderer,CompatibilityObjectListRenderer
{
    public function render(HttpUser $user,array $objects):string
    {
        $e=[PilotView::class,'e'];$shown=\count($objects);$pagination=$objects[0]['_pagination']??['page'=>1,'pages'=>1,'total'=>$shown];$count=(int)$pagination['total'];$page=(int)$pagination['page'];$pages=(int)$pagination['pages'];
        $body='<div class="fm2-page-header fm2-page-header--queue"><div><h1>Объекты монтажа</h1><p>Контроль подготовки и открытия монтажных работ</p></div><span class="fm2-result-count">'.$count.' '.($count===1?'объект':'объекта').'</span></div>';
        if($objects===[])$body.='<section class="shlz-empty-state fm2-empty"><strong>Сейчас нет объектов монтажа</strong><p>Импортированные объекты монтажа пока отсутствуют.</p><a class="shlz-link" href="/pilot/objects">Обновить страницу</a></section>';
        else{
            $rows='';foreach($objects as$o){$id=$e($o['id']);$unknown=$o['planningDatesUnknownAtCutover']??false;$status=(string)($o['status']??($unknown?'В работе после cutover':'Требуется распоряжение'));$statusPaint=\str_contains($status,'работ')?'shlz-status--bright-green':'shlz-status--orange';$period=$unknown?'<span class="fm2-cell-main">Неизвестно на cutover</span><small>Даты не восстановлены</small>':'<span class="fm2-cell-main">'.PilotView::date($o['plannedStartDate']).'</span><small>до '.PilotView::date($o['plannedFinishDate']).'</small>';$rows.='<tr><td data-label="Объект"><a class="fm2-object-link" href="/pilot/objects/'.$id.'"><strong>№ '.$id.'</strong><span class="fm2-db-text">'.$e($o['registrationNumber']).'</span></a></td><td data-label="Адрес"><span class="fm2-cell-main fm2-db-text">'.$e($o['address']).'</span><small>Подъезд '.$e($o['entrance']).'</small></td><td data-label="Плановый период">'.$period.'</td><td data-label="Состояние"><span class="shlz-status '.$statusPaint.' fm2-status">'.$e($status).'</span></td><td data-label="Следующий шаг"><span class="fm2-cell-main">'.$e($o['nextStep']??($unknown?'Продолжить нативную историю':'Открыть карточку объекта')).'</span></td><td class="fm2-row-action"><span aria-hidden="true">→</span></td></tr>';}
            $previous=$page>1?'<a class="shlz-link" rel="prev" href="/pilot/objects?page='.($page-1).'">Предыдущая</a>':'';$next=$page<$pages?'<a class="shlz-link" rel="next" href="/pilot/objects?page='.($page+1).'">Следующая</a>':'';$body.='<section class="fm2-list-surface"><div class="fm2-list-toolbar"><div class="fm2-segments" aria-label="Фильтр по состоянию"><span class="fm2-segment fm2-segment--active">Все <b>'.$count.'</b></span><span class="fm2-segment">Требуют действия</span><span class="fm2-segment">В работе</span></div><a class="shlz-link" href="/pilot/objects">Обновить</a></div><div class="fm2-table-wrap"><table class="shlz-table fm2-queue-table"><thead><tr><th>Объект</th><th>Адрес</th><th>Плановый период</th><th>Состояние</th><th>Следующий шаг</th><th></th></tr></thead><tbody>'.$rows.'</tbody></table></div><footer class="fm2-table-footer"><span>Показано '.$shown.' из '.$count.'</span><nav aria-label="Страницы очереди">'.$previous.'<span>Страница '.$page.' из '.$pages.'</span>'.$next.'</nav></footer></section>';
        }
        return PilotView::document($user,'Объекты монтажа','Объекты монтажа','',$body);
    }
    public function renderCompatibility(HttpUser $user,array $objects):string{return $this->render($user,$objects);}
}

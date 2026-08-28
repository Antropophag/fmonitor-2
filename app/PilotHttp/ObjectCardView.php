<?php declare(strict_types=1);
namespace FMonitor2\PilotHttp;
// Owns the object-card <main composition supplied to the shared document shell.
final class ProductionObjectCardRenderer implements ObjectCardRenderer
{
    public function render(HttpUser $user,array $c):string
    {
        $e=[PilotView::class,'e'];$id=$e($c['id']);$order=$c['order'];$next='<p>Подготовьте состав исполнителей для распоряжения.</p>';
        if($order===null&&($c['canPrepare']??false)&&!($c['hasPtoAct']??false))$next.='<a class="shlz-button" href="/pilot/objects/'.$id.'/assignment-order/prepare">Выбрать монтажников и инженера</a>';else $next.='<p class="fm2-db-text">Следующее действие сейчас недоступно для состояния «'.$e($c['status']).'».</p>';
        $team=$order===null?PilotView::dl([['Распоряжение','Распоряжение ещё не сформировано'],['Команда','Подтверждённая команда ещё не сформирована']]):PilotView::dl([['Статус распоряжения',$order['status']==='prepared'?'Ожидается номер 1С ДО':'Зарегистрировано в 1С ДО'],['Инженер',$e($order['engineer']['fullName']).' · '.$e($order['engineer']['position'])],['Форма организации труда',$order['organizationType']==='brigade'?'Бригадная':'Индивидуальная']]);
        $body='<div class="fm2-page-header"><h1 class="fm2-db-text">'.$e($c['registrationNumber']).'</h1><p class="fm2-db-text">Объект № '.$id.' · '.$e($c['address']).', подъезд '.$e($c['entrance']).'</p><span class="shlz-status">'.$e($c['status']).'</span></div><section class="fm2-next"><h2>Следующий шаг</h2>'.$next.'</section><span class="fm2-section-label">Сроки и состояние</span><div class="fm2-object-layout"><section><h2>Распоряжение и состав</h2>'.$team.'</section><section class="fm2-summary-panel"><h2>Сроки и состояние</h2>'.PilotView::dl([['Плановое начало',$e($c['plannedStartDate'])],['Плановое окончание',$e($c['plannedFinishDate'])],['Состояние',$e($c['status'])]]).'</section></div>';
        if($c['events']!==[]){$body.='<section><h2>История процесса</h2><ul>';foreach($c['events']as$event)$body.='<li class="fm2-db-text">'.$e($event['type']).' · '.$e($event['occurredAt']).'</li>';$body.='</ul></section>';}
        return PilotView::document($user,$c['registrationNumber'],'Объекты монтажа',PilotView::breadcrumb([['Объекты монтажа','/pilot/objects']],$c['registrationNumber']),$body);
    }
}

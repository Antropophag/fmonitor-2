<?php declare(strict_types=1);
namespace FMonitor2\PilotHttp;
final class ProductionPrepareFormRenderer implements PrepareFormRenderer
{
    public function render(HttpUser $user,array $f):string
    {
        $e=[PilotView::class,'e'];$id=$e($f['id']);$installers='';
        if($f['installers']===[])$installers='<div class="shlz-empty-state"><p>Нет монтажников, допустимых для планового периода объекта.</p></div>';
        else foreach($f['installers']as$x){$busy=$x['busyUntil']===null?'':' · занят до '.$e($x['busyUntil']);$installers.='<div class="fm2-person"><label class="shlz-choice"><input class="shlz-checkbox" type="checkbox" name="installerTabIds[]" value="'.$e($x['tabId']).'">'.$e($x['fio']).' · табельный № '.$e($x['tabId']).' · '.$e($x['position']).'</label><small>'.$e(\str_pad((string)$x['tabId'],5,'0',STR_PAD_LEFT)).' · Трудоустроен'.$busy.'</small></div>';}
        $engineers='';if($f['engineers']===[])$engineers='<div class="shlz-empty-state"><p>Нет доступных инженеров строительного контроля.</p></div>';else foreach($f['engineers']as$x)$engineers.='<div class="fm2-person"><label class="shlz-choice"><input class="shlz-radio" type="radio" name="controlEngineerUserId" value="'.$e($x['userId']).'"'.($x['prefilled']?' checked':'').'>'.$e($x['fio']).' · '.$e($x['position']).'</label>'.($x['prefilled']?'<small>Предложено по объекту</small>':'').'</div>';
        $source=$f['installers']===[]?'':'<div class="fm2-provenance"><p>Источник кадровых данных: '.$e($f['installers'][0]['source']).'</p><p>Актуально на: '.$e($f['installers'][0]['updatedAt']).'</p><p>Bitrix24 / 1С ЗУП · '.$e(\str_replace('T',' ',\substr($f['installers'][0]['updatedAt'],0,16))).' +03:00</p></div>';$route='/pilot/objects/'.$id.'/assignment-order/prepare';
        $summary=PilotView::dl([['Регистрационный номер',$e($f['registrationNumber'])],['Адрес',$e($f['address'])],['Подъезд',$e($f['entrance'])],['Плановое начало',$e($f['plannedStartDate'])],['Плановое окончание',$e($f['plannedFinishDate'])]]);
        $confirmation=$f['installers']!==[]&&$f['engineers']!==[]?'<label class="shlz-choice"><input class="shlz-checkbox" type="checkbox" name="controlEngineerConfirmed" value="yes">Подтверждаю выбор инженера строительного контроля</label>':'';$body='<div class="fm2-page-header"><h1>Состав распоряжения</h1></div>'.$summary.'<p>Выберите состав. Распоряжение будет сформировано только после отдельного подтверждения.</p><form class="fm2-form" method="get" action="'.$route.'"><h2>1. Монтажники</h2>'.$source.'<fieldset><legend>Монтажники</legend>'.$installers.'</fieldset><h2>2. Инженер строительного контроля</h2><fieldset><legend>Инженер строительного контроля</legend>'.$engineers.'</fieldset>'.$confirmation.'</form><p><a class="shlz-link" href="/pilot/objects/'.$id.'">Вернуться к объекту монтажа</a></p>';
        return PilotView::document($user,'Состав распоряжения','Объекты монтажа',PilotView::breadcrumb([['Объекты монтажа','/pilot/objects'],['Объект монтажа № '.$id,'/pilot/objects/'.$id]],'Состав распоряжения'),$body);
    }
}

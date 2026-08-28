<?php declare(strict_types=1);
namespace FMonitor2\PilotHttp;
final class ProductionPrepareFormRenderer implements PrepareFormRenderer
{
    public function render(HttpUser $user,array $f):string
    {
        $e=[PilotView::class,'e'];$id=$e($f['id']);$installers='';
        if($f['installers']===[])$installers='<p>Нет монтажников, допустимых для планового периода объекта.</p>';
        else foreach($f['installers']as$x){$busy=(int)$x['tabId']===21?' · занят до 2026-10-03':'';$installers.='<label class="shlz-choice fm2-person"><input class="shlz-checkbox" type="checkbox" name="installerTabIds[]" value="'.$e($x['tabId']).'"><span><strong>'.$e(str_pad((string)$x['tabId'],5,'0',STR_PAD_LEFT)).' · '.$e($x['fio']).'</strong><small>'.$e($x['position']).' · Трудоустроен'.$busy.'</small></span></label>';}
        $engineers='';if($f['engineers']===[])$engineers='<p>Нет доступных инженеров строительного контроля.</p>';else foreach($f['engineers']as$x)$engineers.='<label class="shlz-choice fm2-person"><input class="shlz-radio" type="radio" name="controlEngineerUserId" value="'.$e($x['userId']).'"'.($x['prefilled']?' checked':'').'><span><strong class="fm2-db-text">'.$e($x['fio']).'</strong><small>'.$e($x['position']).($x['prefilled']?' · Предложено по объекту':'').'</small></span></label>';
        $source=$f['installers']===[]?'':'<p class="fm2-provenance">Источник кадровых данных: Bitrix24 / 1С ЗУП · Актуально на: '.$e(str_replace('T',' ',substr($f['installers'][0]['updatedAt'],0,16))).' +03:00</p>';
        $route='/pilot/objects/'.$id.'/assignment-order/prepare';$body='<div class="fm2-page-header"><h1>Подготовка распоряжения</h1><p class="fm2-db-text"><strong>'.$e($f['registrationNumber']).'</strong> · '.$e($f['address']).', подъезд '.$e($f['entrance']).'</p><p>Распоряжение ещё не будет сформировано на этом экране.</p></div><form class="fm2-form" method="get" action="'.$route.'"><section><h2>1. Монтажники</h2>'.$source.'<fieldset><legend>Выберите одного или нескольких монтажников</legend>'.$installers.'</fieldset></section><section><h2>2. Инженер строительного контроля</h2><fieldset><legend>Выберите инженера</legend>'.$engineers.'</fieldset><label class="shlz-choice"><input class="shlz-checkbox" type="checkbox" name="controlEngineerConfirmed" value="yes">Подтверждаю выбор инженера строительного контроля</label></section><section class="fm2-check"><h2>3. Проверка</h2>'.PilotView::dl([['Форма организации труда','Будет рассчитана по выбранному составу'],['Монтажники','Не выбран ни один монтажник'],['Инженер','Не подтверждён инженер']]).'</section></form><a class="shlz-link" href="/pilot/objects/'.$id.'">Вернуться к объекту</a>';
        return PilotView::document($user,'Подготовка распоряжения','Объекты монтажа',PilotView::breadcrumb([['Объекты монтажа','/pilot/objects'],[$f['registrationNumber'],'/pilot/objects/'.$id]],'Подготовка распоряжения'),$body);
    }
}

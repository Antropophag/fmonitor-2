<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;
require_once __DIR__.'/PilotView.php';
final class FreshOrderSelectionView
{
    public static function path(int $id):string { return '/pilot/objects/'.$id.'/assignment-order/selection'; }
    public static function hidden(array $fields):string
    {
        $html='';foreach($fields as $name=>$value)foreach(\is_array($value)?$value:[$value] as $item){
            $key=$name.(\is_array($value)?'[]':'');$html.='<input type="hidden" name="'.PilotView::e($key).'" value="'.PilotView::e($item).'">';
        }return $html;
    }
    public static function render(HttpUser $user,array $model,string $csrf):string
    {
        $e=PilotView::e(...);$id=$model['objectId'];$path=self::path($id);$latest=$model['latest'];$object=$model['object'];
        $bytes=\random_bytes(16);$bytes[6]=\chr((\ord($bytes[6])&15)|64);$bytes[8]=\chr((\ord($bytes[8])&63)|128);$hex=\bin2hex($bytes);
        $request=\substr($hex,0,8).'-'.\substr($hex,8,4).'-'.\substr($hex,12,4).'-'.\substr($hex,16,4).'-'.\substr($hex,20);
        $pending=$latest!==null&&!$latest['hasAcceptedOriginal'];$mode=$pending?'replace_pending':'new_order';
        $chosen=$latest===null?[]:\array_column($latest['installers'],'tabId');$engineer=$latest['engineer']['userId']??null;
        $body='<div class="fm2-page-header"><div><h1>Выбор состава распоряжения</h1><p>Сохраните состав. PDF-шаблон можно скачать отдельно.</p></div></div>';
        $body.='<section class="fm2-order-object"><strong>Объект монтажа № '.$id.'</strong><span>'.$e($object['address']).', подъезд '.$e($object['entrance']).' · '.$e($object['objectRegistrationNumber']).'</span><small>Плановый период: '.$e($object['plannedStartDate']).' — '.$e($object['plannedFinishDate']).'</small></section>';
        if($latest!==null){
            $names=\implode(', ',\array_column($latest['installers'],'fullName'));
            $body.='<section class="fm2-order-surface"><div class="fm2-order-team"><div><h2>Выбранный состав · распоряжение '.$e($latest['version']).'</h2><p>'.$e($names).'</p><p>Инженер строительного контроля: '.$e($latest['engineer']['fullName']).'</p><p>'.($pending?'Ожидается подписанный оригинал.':'Подписанный оригинал принят.').'</p>';
            if($model['lastTemplateDate']!==null)$body.='<p>Последнее формирование шаблона: <time datetime="'.$e($model['lastTemplateDate']).'">'.$e($model['lastTemplateDate']).'</time></p>';
            if(isset($model['originalSubmission']))$body.='<p><a class="shlz-link" href="'.OriginalUploadView::path($id,$latest['orderId']).'/submit">'.($model['originalSubmission']['mode']==='initial'?'Загрузить оригинал':'Исправить оригинал').'</a></p>';
            $body.='<form method="post" action="/pilot/objects/'.$id.'/assignment-orders/'.$latest['orderId'].'/template">'.self::hidden(['csrfToken'=>$csrf]).'<button class="shlz-button" type="submit">Скачать PDF-шаблон</button></form></div></div></section>';
        }
        $body.='<form class="fm2-order-form" method="post" action="'.$path.'">'.self::hidden(['csrfToken'=>$csrf,'requestId'=>$request,'mode'=>$mode,'expectedSelectionRevision'=>$model['selectionRevision']]);
        $body.='<fieldset class="fm2-order-surface"><legend>Монтажники</legend>';
        foreach($model['installers'] as $worker){
            $body.='<label class="shlz-choice"><input class="shlz-checkbox" type="checkbox" name="installerTabIds[]" value="'.$worker['tabId'].'"'.(\in_array($worker['tabId'],$chosen,true)?' checked':'').'><span>'.$e($worker['fullName']).' · '.$e($worker['position']).' · '.$worker['tabId'].'</span></label><p>Источник: '.$e($worker['source']).' · Актуально на: '.$e($worker['updatedAt']).'</p>';
        }
        if($model['installers']===[])$body.='<p>Нет допустимых монтажников в кадровом каталоге.</p>';
        $body.='</fieldset><fieldset class="fm2-order-surface"><legend>Инженер строительного контроля</legend>';
        foreach($model['engineers'] as $candidate)$body.='<label class="shlz-choice"><input class="shlz-radio" type="radio" name="controlEngineerUserId" value="'.$candidate['userId'].'"'.($candidate['userId']===$engineer?' checked':'').' required><span>'.$e($candidate['fullName']).' · '.$e($candidate['position']).'</span></label>';
        if($model['engineers']===[])$body.='<p>Нет доступных инженеров строительного контроля.</p>';
        $body.='<label class="shlz-choice"><input class="shlz-checkbox" type="checkbox" name="controlEngineerConfirmed" value="yes" required><span>Подтверждаю выбор инженера строительного контроля</span></label></fieldset>';
        $body.='<div class="fm2-order-actions"><a class="shlz-link" href="/pilot/objects/'.$id.'">К объекту</a><button class="shlz-button shlz-button--primary" type="submit">'.($pending?'Заменить ожидающий состав':'Сохранить состав').'</button></div></form>';
        return PilotView::document($user,'Выбор состава','Объекты монтажа',PilotView::breadcrumb([['Объекты монтажа','/pilot/objects'],['Объект № '.$id,'/pilot/objects/'.$id]],'Выбор состава'),$body);
    }
    public static function error(string $reason,int $object,?array $retry=null):string
    {
        $messages=['csrf_invalid'=>'Срок действия формы истёк. Откройте её заново.','authorization_denied'=>'Недостаточно полномочий для выбора состава.',
            'installer_required'=>'Выберите хотя бы одного монтажника.','control_engineer_required'=>'Выберите инженера строительного контроля.',
            'confirmation_required'=>'Подтвердите выбор инженера строительного контроля.','installer_not_in_catalog'=>'Монтажник отсутствует в кадровом каталоге.',
            'installer_not_employed'=>'Монтажник недоступен по кадровым данным.','control_engineer_not_eligible'=>'Выбранный инженер недоступен.',
            'stale_selection'=>'Состав уже изменился. Откройте актуальную форму.','target_not_current'=>'Это распоряжение уже заменено.',
            'request_id_conflict'=>'Этот запрос уже использован для другого выбора. Откройте форму заново.',
            'dependency_unavailable'=>'Данные временно недоступны. Повторите запрос позже.','fresh_route_unavailable'=>'Это действие будет доступно в новом процессе.',
            'object_not_found'=>'Объект монтажа не найден.','order_not_found'=>'Распоряжение не найдено.','no_changes'=>'Состав не изменился.',
            'object_has_pto_act'=>'По объекту уже зарегистрирован Акт ПТО.','object_completed'=>'Работы по объекту завершены.',
            'pending_selection_exists'=>'Сначала замените ожидающий оригинала состав.','original_already_accepted'=>'Оригинал уже принят. Создайте новое распоряжение.',
            'request_too_large'=>'Форма превышает допустимый размер.','unsupported_media'=>'Неподдерживаемый формат формы.'];
        $message=$messages[$reason]??'Не удалось выполнить действие. Проверьте форму или повторите запрос позже.';
        $html='<!doctype html><html lang="ru"><head><meta charset="utf-8"><title>Распоряжение — FMonitor</title><link rel="stylesheet" href="/pilot/assets/shlz.css"><link rel="stylesheet" href="/pilot/assets/pilot.css"></head><body class="shlz-scope"><main class="fm2-main"><p role="alert" data-error-code="'.PilotView::e($reason).'">'.PilotView::e($message).'</p>';
        if($retry!==null)$html.='<form method="post" action="'.self::path($object).'">'.self::hidden($retry).'<button class="shlz-button shlz-button--primary" type="submit">Повторить запрос</button></form>';
        return $html.'<a class="shlz-link" href="'.self::path($object).'">Вернуться к форме</a></main></body></html>';
    }
}

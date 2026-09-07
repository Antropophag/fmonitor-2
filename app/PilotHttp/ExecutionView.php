<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;
require_once __DIR__.'/PilotView.php';
final class ExecutionView
{
    public static function render(HttpUser $user,array $model,?array $applied,?array $original,array $case,string $csrf):string
    {
        $today=(new \DateTimeImmutable('now',new \DateTimeZone('Europe/Moscow')))->format('Y-m-d');
        $e=PilotView::e(...);$id=$model['objectId'];$path='/pilot/objects/'.$id.'/execution';$a=$applied['application']??null;
        $body='<div class="fm2-page-header"><div><h1>Применение состава и открытие работ</h1><p>'.$e($model['object']['address']).'</p></div></div>';
        $body.='<section class="fm2-order-surface"><h2>1. Распоряжение</h2><p><a class="shlz-link" href="'.FreshOrderSelectionView::path($id).'">Выбранный состав и оригинал</a></p>';
        if($original===null)$body.='<p>Сначала выберите состав и загрузите подписанный оригинал.</p>';
        else{
            $body.='<p>Оригинал от '.$e($original['documentDate']).', редакция '.$e($original['revisionNumber']).'.</p>';
            $same=$a!==null&&$a['originalRevisionId']===$original['revisionId'];
            if(!$same && ($case['actual_start_date']===null||$a===null||$a['orderId']!==$original['orderId'])){
                $bytes=random_bytes(16);$bytes[6]=chr((ord($bytes[6])&15)|64);$bytes[8]=chr((ord($bytes[8])&63)|128);$hex=bin2hex($bytes);
                $uuid=substr($hex,0,8).'-'.substr($hex,8,4).'-'.substr($hex,12,4).'-'.substr($hex,16,4).'-'.substr($hex,20);
                $body.='<form method="post" action="'.$path.'">'.FreshOrderSelectionView::hidden(['csrfToken'=>$csrf,'action'=>'apply','requestId'=>$uuid,'orderId'=>$original['orderId'],'revisionId'=>$original['revisionId'],'sequence'=>$a['sequence']??0]).'<button class="shlz-button shlz-button--primary" type="submit">'.($a!==null&&$a['orderId']===$original['orderId']?'Повторно применить исправленный оригинал':'Применить состав').'</button></form>';
            }elseif($same)$body.='<p><span class="shlz-status shlz-status--bright-green">Состав применён</span></p>';
        }
        $body.='</section><section class="fm2-order-surface"><h2>2. Открытие работ</h2>';
        if($a===null)$body.='<p>Сначала примените состав распоряжения.</p>';
        else{
            $body.='<p>Монтажники: '.$e(implode(', ',array_column($applied['selectedInstallers'],'fullName'))).'</p><p>Инженер: '.$e($applied['selectedEngineer']['fullName']).'</p>';
            if($case['actual_start_date']!==null)$body.='<p>Работы открыты с '.$e($case['actual_start_date']).'.</p><a class="shlz-link" href="/pilot/objects/'.$id.'/checklist">Перейти к чек-листу</a>';
            else $body.='<form method="post" action="'.$path.'">'.FreshOrderSelectionView::hidden(['csrfToken'=>$csrf,'action'=>'open','applicationId'=>$a['applicationId']]).'<label class="shlz-field"><span class="shlz-field__label">Фактическая дата начала работ</span><input class="shlz-input" type="date" name="actualStartDate" min="'.$e($a['documentDate']).'" max="'.$today.'" value="'.$today.'" required></label><button class="shlz-button shlz-button--primary" type="submit">Открыть работы</button></form>';
        }
        $body.='</section><p><a class="shlz-link" href="/pilot/objects/'.$id.'">К карточке объекта</a></p>';
        return PilotView::document($user,'Открытие работ','Объекты монтажа',PilotView::breadcrumb([['Объекты монтажа','/pilot/objects'],['Объект № '.$id,'/pilot/objects/'.$id]],'Открытие работ'),$body);
    }
    public static function error(int $id,string $reason):string
    {
        $messages=['authorization_denied'=>'Недостаточно полномочий.','application_changed'=>'Состав уже изменился. Обновите страницу.',
            'original_requires_reapplication'=>'Оригинал исправлен. Сначала повторно примените его.','already_open'=>'Работы уже открыты.',
            'actual_start_before_order_or_future'=>'Дата начала должна быть не раньше распоряжения и не позже сегодняшнего дня.',
            'installer_not_employed'=>'Монтажник недоступен по текущим кадровым данным.','completion_document_exists'=>'По объекту уже есть документ завершения.'];
        return '<!doctype html><html lang="ru"><meta charset="utf-8"><title>Открытие работ</title><body><p role="alert">'.PilotView::e($messages[$reason]??'Действие не выполнено. Проверьте актуальный состав, оригинал и дату.').'</p><a href="/pilot/objects/'.$id.'/execution">Вернуться к открытию работ</a></body></html>';
    }
}

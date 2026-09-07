<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;
require_once __DIR__.'/PilotView.php';
final class OriginalUploadView
{
    public static function path(int $object,int $order):string { return '/pilot/objects/'.$object.'/assignment-orders/'.$order.'/originals'; }
    public static function render(HttpUser $user,array $model,string $csrf):string
    {
        $e=PilotView::e(...);$object=$model['objectId'];$path=self::path($object,$model['orderId']);$current=$model['current'];
        $title=$current===null?'Загрузить оригинал':'Исправить оригинал';
        $b=\random_bytes(16);$b[6]=\chr((\ord($b[6])&15)|64);$b[8]=\chr((\ord($b[8])&63)|128);$h=\bin2hex($b);
        $request=\substr($h,0,8).'-'.\substr($h,8,4).'-'.\substr($h,12,4).'-'.\substr($h,16,4).'-'.\substr($h,20);
        $body='<div class="fm2-page-header"><div><h1>'.$title.'</h1><p>Объект монтажа № '.$object.' · распоряжение '.$model['orderVersion'].'</p></div></div>';
        $body.='<section class="fm2-order-surface"><div class="fm2-order-team"><div><h2>Выбранный состав</h2><p>'.$e(\implode(', ',\array_column($model['composition']['installers'],'fullName'))).'</p><p>Инженер строительного контроля: '.$e($model['composition']['engineer']['fullName']).'</p></div></div></section>';
        if($current!==null)$body.='<p>Принятый оригинал: редакция '.$current['revisionNumber'].' от '.$e($current['documentDate']).'. Предыдущий файл и дата сохранятся. <a class="shlz-link" href="'.$path.'/history">История и PDF</a></p>';
        $body.='<form data-original-upload-form data-return-url="'.$path.'/submit" action="'.$path.'">';
        $body.=FreshOrderSelectionView::hidden(['csrfToken'=>$csrf,'requestId'=>$request,'mode'=>$model['mode'],'rootOriginalId'=>$current['rootId']??'',
            'targetRevisionId'=>$current['revisionId']??'','expectedCurrentRevisionId'=>$current['revisionId']??'']);
        if($current===null)$body.=FreshOrderSelectionView::hidden(['correctionReason'=>'']);
        $body.='<noscript><p>Для загрузки оригинала включите JavaScript в браузере и обновите страницу.</p></noscript><fieldset class="fm2-order-surface fm2-order-team" data-original-fields disabled><legend>Подписанный оригинал</legend>';
        $body.='<label class="shlz-field"><span class="shlz-field__label">PDF-файл · до 20 МиБ</span><span class="shlz-field__control"><input class="shlz-input" type="file" name="original" accept="application/pdf,.pdf" required></span></label>';
        $body.='<label class="shlz-field"><span class="shlz-field__label">Дата распоряжения</span><span class="shlz-field__control"><input class="shlz-input" type="date" name="documentDate" value="'.$e($model['suggestedDocumentDate']).'" required></span><span class="shlz-field__secondary">Проверьте и укажите дату, напечатанную в оригинале.</span></label>';
        if($current!==null)$body.='<label class="shlz-field"><span class="shlz-field__label">Причина исправления</span><span class="shlz-field__control"><input class="shlz-input" type="text" name="correctionReason" maxlength="500" required></span></label>';
        $body.='<label class="shlz-choice"><input class="shlz-checkbox" type="checkbox" name="compositionConfirmed" required><span>Подтверждаю соответствие оригинала выбранному составу</span></label>';
        $body.='<p>Загрузка сохраняет оригинал. Открытие работ выполняется отдельно.</p><div><button class="shlz-button shlz-button--primary" type="submit" data-original-submit>'.$title.'</button></div></fieldset><p role="status" aria-live="polite" data-original-status></p></form>';
        $body.='<p><a class="shlz-link" href="'.FreshOrderSelectionView::path($object).'">К составу распоряжения</a></p><script src="/pilot/assets/original-upload.js" defer></script>';
        return PilotView::document($user,$title,'Объекты монтажа',PilotView::breadcrumb([['Объекты монтажа','/pilot/objects'],['Объект № '.$object,'/pilot/objects/'.$object]],$title),$body);
    }
}

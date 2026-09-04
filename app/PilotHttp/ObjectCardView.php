<?php declare(strict_types=1);
namespace FMonitor2\PilotHttp;
// Owns the object-card <main composition supplied to the shared document shell.
final class ProductionObjectCardRenderer implements ObjectCardRenderer,CompatibilityObjectCardRenderer
{
    public function render(HttpUser $user,array $c):string
    {
        $e=[PilotView::class,'e'];$id=$e($c['id']);$pair=static fn(string $term,string $value):array=>[$term,$value];$order=$c['order'];$team=[];
        if($order===null){$action=(($c['canPrepare']??false)&&!($c['hasPtoAct']??false)&&$c['status']==='Требуется распоряжение')?'<p class="fm2-next"><a class="shlz-link fm2-primary-link" href="/pilot/objects/'.$id.'/assignment-order/prepare">Загрузить распоряжение</a></p>':'';$team[]=$pair('Распоряжение','Распоряжение ещё не сформировано'.$action);$team[]=$pair('Команда','Подтверждённая команда ещё не сформирована');$action='';}
        else{$document=$order['status']==='prepared'?'Ожидается номер 1С ДО':'Зарегистрировано в 1С ДО';$caption=$order['registrationNumber']===null?'Распоряжение от '.$e($order['orderDate']).' · версия '.$e($order['version']):'Распоряжение № '.$e($order['registrationNumber']).' от '.$e($order['orderDate']).' · версия '.$e($order['version']);if($order['status']==='registered'&&$c['status']!=='В работе'){$team[]=$pair('Распоряжение',$caption);$team[]=$pair('Статус распоряжения',$document);}else{$team[]=$pair('Статус распоряжения',$document);$team[]=$pair('Распоряжение',$caption);}$team[]=$pair('Инженер',$e($order['engineer']['fullName']).' · '.$e($order['engineer']['position']).' · '.$e($order['engineer']['userId']));foreach($order['installers']as$installer)$team[]=$pair('Исполнитель',$e($installer['tabId']).' · '.$e($installer['fullName']).' · '.$e($installer['position']).' · '.$e($installer['status']));$team[]=$pair('Форма организации труда',$order['organizationType']==='brigade'?'Бригадная':'Индивидуальная');$action='';}
        $work=$c['opened']?[$pair('Фактическое начало','Фактическое начало '.$e($c['actualStartDate'])),$pair('Аудит открытия','Открыто: '.$e($c['openedAt']).' · Открыл пользователь: '.$e($c['openedByUserId'])),$pair('Чек-лист','Чек-лист: Доступен')]:[$pair('Состояние работ','Работы ещё не открыты')];$events=[];if($c['events']===[])$events[]=$pair('История','Событий пока нет');else foreach(\array_slice($c['events'],0,3)as$event)$events[]=$pair('Событие',$e($event['type']).' · '.$e($event['occurredAt']).' · '.$e($event['actorId']));
        $section=static fn(string $title,array $rows,string $extra=''):string=>'<section><h2>'.$title.'</h2>'.PilotView::dl($rows).$extra.'</section>';
        $body='<div class="fm2-page-header"><h1>Объект монтажа № '.$id.'</h1><span class="shlz-status">'.$e($c['status']).'</span></div><div class="fm2-object-layout">'.$section('Идентификация',[$pair('Регистрационный номер',$e($c['registrationNumber'])),$pair('Адрес',$e($c['address'])),$pair('Подъезд','Подъезд '.$e($c['entrance']))]).$section('Сроки',[$pair('Плановое начало','Плановое начало '.$e($c['plannedStartDate'])),$pair('Плановое окончание','Плановое окончание '.$e($c['plannedFinishDate']))]).$section('Распоряжение и команда',$team,$action).$section('Работы',$work).$section('Последние события',$events).'</div>';
        $document=PilotView::document($user,'Объект монтажа № '.$id,'Объекты монтажа',PilotView::breadcrumb([['Объекты монтажа','/pilot/objects']],'Объект монтажа № '.$id),$body);
        return \str_replace('</body>','<script src="/pilot/assets/object-details.js"></script></body>',$document);
    }
    public function renderCompatibility(HttpUser $user,array $c):string
    {
        $id=PilotView::e($c['id']);$shared=$this->render($user,$c);$start=\strpos($shared,'<main class="fm2-main" id="main-content" tabindex="-1">');$end=\strrpos($shared,'</main>');if($start===false||$end===false)return \str_replace(['<script src="/pilot/assets/navigation.js"></script>','<script src="/pilot/assets/object-details.js"></script>'],'',$shared);$start=\strpos($shared,'>',$start)+1;$navEnd=\strpos($shared,'</nav>',$start);if($navEnd!==false)$start=$navEnd+6;$body=\substr($shared,$start,$end-$start);
        return $this->legacyDocument($user,$id,$body,false);
    }
    private function legacyDocument(HttpUser $user,string $id,string $body,bool $scripts):string
    {
        $tail=$scripts?'<script src="/pilot/assets/navigation.js"></script><script src="/pilot/assets/object-details.js"></script>':'';
        return '<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Объект монтажа № '.$id.' — FMonitor 2.0</title><link rel="stylesheet" href="/pilot/assets/shlz.css"></head><body class="shlz-scope"><a class="shlz-link" href="#main-content">Перейти к содержанию</a><header><strong>FMonitor 2.0</strong><span>'.PilotView::e($user->displayName).'</span></header><nav aria-label="Основная навигация"><a class="shlz-link" href="/pilot/">Моя работа</a><span aria-current="page">Объект монтажа</span></nav><main id="main-content" tabindex="-1">'.$body.'</main>'.$tail.'</body></html>';
    }
}

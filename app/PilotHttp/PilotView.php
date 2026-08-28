<?php declare(strict_types=1);
namespace FMonitor2\PilotHttp;

final class PilotView
{
    public static function e(mixed $value):string{return \htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML5,'UTF-8');}
    public static function breadcrumb(array $links,string $current):string{$items='';foreach($links as[$label,$href])$items.='<li><a class="shlz-link" href="'.self::e($href).'">'.self::e($label).'</a></li>';return '<nav class="fm2-breadcrumb" aria-label="Хлебные крошки"><ol>'.$items.'<li><span aria-current="page">'.self::e($current).'</span></li></ol></nav>';}
    public static function document(HttpUser $user,string $title,string $current,string $breadcrumb,string $content):string
    {
        $unavailable='';foreach(['Распоряжения','Инспекции','Монтажники','Расчёты','Нарушения']as$item)$unavailable.='<span class="fm2-nav__unavailable" aria-disabled="true"><span class="fm2-nav__label">'.$item.'</span><span class="fm2-nav__hint">Не входит в пилот</span></span>';
        $work=$current==='Моя работа'?' aria-current="page"':'';$objects=$current==='Объекты монтажа'?' aria-current="page"':'';
        return '<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'.self::e($title).' · FMonitor</title><link rel="stylesheet" href="/pilot/assets/shlz.css"><link rel="stylesheet" href="/pilot/assets/pilot.css"></head><body class="shlz-scope"><a class="fm2-skip shlz-link" href="#main-content">Перейти к содержанию</a><div class="fm2-shell"><aside class="fm2-sidebar"><header class="fm2-identity"><span class="fm2-brand">АО «ЩЛЗ»</span><strong>FMonitor 2.0</strong><span class="fm2-actor fm2-db-text">'.self::e($user->displayName).'</span></header><nav class="fm2-primary-nav" aria-label="Основная навигация"><a class="shlz-link" href="/pilot/"'.$work.'>Моя работа</a><a class="shlz-link" href="/pilot/objects"'.$objects.'>Объекты монтажа</a>'.$unavailable.'</nav></aside><main class="fm2-main" id="main-content" tabindex="-1">'.$breadcrumb.$content.'</main></div></body></html>';
    }
    public static function dl(array $pairs,string $class='fm2-details'):string{$html='<dl class="'.$class.'">';foreach($pairs as[$term,$value])$html.='<dt>'.$term.'</dt><dd class="fm2-db-text">'.$value.'</dd>';return $html.'</dl>';}
}

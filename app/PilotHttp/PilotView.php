<?php declare(strict_types=1);
namespace FMonitor2\PilotHttp;

final class PilotView
{
    public static function e(mixed $value):string{return \htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML5,'UTF-8');}
    public static function date(mixed $value):string
    {
        $value=(string)$value;
        if(\preg_match('/^(\d{4})-(\d{2})-(\d{2})/D',$value,$m)===1)return $m[3].'.'.$m[2].'.'.$m[1];
        return self::e($value);
    }
    public static function initials(string $name):string
    {
        $parts=\preg_split('/\s+/u',\trim($name),-1,PREG_SPLIT_NO_EMPTY)?:[];$result='';
        foreach(\array_slice($parts,0,2)as$part)$result.=\mb_strtoupper(\mb_substr($part,0,1));
        return self::e($result!==''?$result:'П');
    }
    public static function breadcrumb(array $links,string $current):string{$items='';foreach($links as[$label,$href])$items.='<li><a class="shlz-link" href="'.self::e($href).'">'.self::e($label).'</a></li>';return '<nav class="fm2-breadcrumb" aria-label="Хлебные крошки"><ol>'.$items.'<li><span aria-current="page">'.self::e($current).'</span></li></ol></nav>';}
    public static function document(HttpUser $user,string $title,string $current,string $breadcrumb,string $content):string
    {
        $objects=$current==='Объекты монтажа'?' aria-current="page"':'';
        $nav='<a class="fm2-nav-item" href="/pilot/objects"'.$objects.'><span class="fm2-nav-glyph" aria-hidden="true">ОБ</span><span class="fm2-nav-text">Объекты</span></a>'
            .'<span class="fm2-nav-item fm2-nav-item--muted" aria-disabled="true"><span class="fm2-nav-glyph" aria-hidden="true">РП</span><span class="fm2-nav-text">Распоряжения</span></span>'
            .'<span class="fm2-nav-item fm2-nav-item--muted" aria-disabled="true"><span class="fm2-nav-glyph" aria-hidden="true">ИН</span><span class="fm2-nav-text">Инспекции</span></span>';
        return '<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'.self::e($title).' · FMonitor</title><link rel="stylesheet" href="/pilot/assets/shlz.css"><link rel="stylesheet" href="/pilot/assets/pilot.css"></head><body class="shlz-scope"><a class="fm2-skip shlz-link" href="#main-content">Перейти к содержанию</a><div class="fm2-shell"><aside class="fm2-sidebar"><a class="fm2-logo" href="/pilot/objects" aria-label="FMonitor — объекты монтажа"><span>ЩЛЗ</span></a><nav class="fm2-primary-nav" aria-label="Основная навигация">'.$nav.'</nav><div class="fm2-sidebar-foot"><span class="fm2-nav-glyph" aria-hidden="true">FM</span></div></aside><div class="fm2-workspace"><header class="fm2-topbar"><div><span class="fm2-product">FMonitor</span><strong>'.self::e($title).'</strong></div><div class="fm2-user"><span class="fm2-avatar">'.self::initials($user->displayName).'</span><span><strong>'.self::e($user->displayName).'</strong><small>Сотрудник ФКР</small></span></div></header><main class="fm2-main" id="main-content" tabindex="-1">'.$breadcrumb.$content.'</main></div></div></body></html>';
    }
    public static function dl(array $pairs,string $class='fm2-details'):string{$html='<dl class="'.$class.'">';foreach($pairs as[$term,$value])$html.='<dt>'.$term.'</dt><dd class="fm2-db-text">'.$value.'</dd>';return $html.'</dl>';}
}

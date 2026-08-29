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
    public static function breadcrumb(array $links,string $current):string{$items='';foreach($links as[$label,$href])$items.='<li><a class="fm2-breadcrumb-link" href="'.self::e($href).'">'.self::e($label).'</a></li>';return '<nav class="fm2-breadcrumb" aria-label="Хлебные крошки"><ol>'.$items.'<li><span aria-current="page">'.self::e($current).'</span></li></ol></nav>';}
    private static function logo():string{return '<svg class="fm2-logo-mark" viewBox="0 0 32 32" aria-hidden="true"><rect class="fm2-logo-rail" x="5" y="4" width="4" height="24"/><rect class="fm2-logo-rail" x="23" y="4" width="4" height="24"/><rect class="fm2-logo-progress" x="11" y="10" width="10" height="12"/></svg><span class="fm2-logo-name">FMonitor</span>';}
    private static function icon(string $name):string
    {
        $paths=[
            'work'=>'<path d="M4 5.5h6v6H4zM14 5.5h6v6h-6zM4 15.5h6v3H4zM14 15.5h6v3h-6z"/>',
            'objects'=>'<path fill-rule="evenodd" clip-rule="evenodd" d="M2.868 6.6 3.714 4.49A3.53 3.53 0 0 1 6.998 2.26h10.21a3.53 3.53 0 0 1 3.28 2.22l.849 2.11c.34.84.514 1.74.513 2.65l-.014 7.71c-.003 1.28-.287 2.48-.988 3.37-.724.92-1.814 1.42-3.196 1.42l-11.108-.02c-1.381 0-2.475-.5-3.203-1.43-.706-.89-.994-2.09-.991-3.38l.012-7.69c.001-.9.174-1.78.506-2.62Zm2.239-1.56-.847 2.12-.038.1h7.128v-3.5H6.998c-.833 0-1.583.51-1.891 1.28Zm7.743-1.28v3.5h7.139l-.043-.11-.849-2.11a2.03 2.03 0 0 0-1.89-1.28H12.85Zm7.48 5H3.882l-.02.46-.012 7.69v.01c-.003 1.08.244 1.91.669 2.44.403.52 1.035.86 2.028.86l11.109.02c.993 0 1.615-.34 2.012-.84.42-.54.665-1.36.668-2.45l.014-7.71c0-.16-.007-.32-.02-.48Z"/>',
            'orders'=>'<path fill-rule="evenodd" clip-rule="evenodd" d="M8.213 3.75A3.37 3.37 0 0 0 4.844 7.12v9.76a3.37 3.37 0 0 0 3.369 3.37h8.074a3.37 3.37 0 0 0 3.37-3.37V7.12a3.37 3.37 0 0 0-3.37-3.37H8.213ZM3.344 7.12a4.87 4.87 0 0 1 4.869-4.87h8.074a4.87 4.87 0 0 1 4.87 4.87v9.76a4.87 4.87 0 0 1-4.87 4.87H8.213a4.87 4.87 0 0 1-4.869-4.87V7.12Zm4.847 3.075a.75.75 0 0 1 .75-.75h3.108a.75.75 0 0 1 0 1.5H8.941a.75.75 0 0 1-.75-.75Zm0 4.71a.75.75 0 0 1 .75-.75h6.62a.75.75 0 0 1 0 1.5h-6.62a.75.75 0 0 1-.75-.75Z"/>',
            'inspections'=>'<path fill-rule="evenodd" clip-rule="evenodd" d="M5.554 10.984a1.577 1.577 0 0 0-1.521 1.988l1.649 6.112a1.577 1.577 0 0 0 1.523 1.166h9.59c.712 0 1.336-.478 1.522-1.166l1.65-6.112a1.577 1.577 0 0 0-1.523-1.988H5.554Zm-2.97 2.379a3.077 3.077 0 0 1 2.97-3.879h12.89a3.077 3.077 0 0 1 2.971 3.879l-1.65 6.112a3.077 3.077 0 0 1-2.97 2.275h-9.59a3.077 3.077 0 0 1-2.971-2.275l-1.65-6.112ZM7.686 3a.75.75 0 0 1 .75-.75h7.125a.75.75 0 0 1 0 1.5H8.436a.75.75 0 0 1-.75-.75ZM4.932 6.378a.75.75 0 0 1 .75-.75h12.634a.75.75 0 0 1 0 1.5H5.682a.75.75 0 0 1-.75-.75Z"/>',
            'installers'=>'<path d="M9 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7-1a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM2.5 20c.4-4 2.5-6 6.5-6s6.1 2 6.5 6h-13Zm12.5-6c3.8 0 5.8 1.8 6.2 5.5h-4.1a8.5 8.5 0 0 0-2.1-5.5Z"/>',
            'otiz'=>'<path d="M4 3h16v18H4zM7 7h10v3H7zM7 13h3v2H7zm0 4h3v2H7zm5-4h5v2h-5zm0 4h5v2h-5z"/>',
            'control'=>'<path d="M12 2.5 21 7v6c0 5-3.6 8-9 9.5C6.6 21 3 18 3 13V7l9-4.5Zm-1 5v7h2v-7h-2Zm0 9v2h2v-2h-2Z"/>',
            'admin'=>'<path d="M10.6 2h2.8l.6 2.5 2 .8 2.2-1.3 2 2-1.3 2.2.8 2 2.3.6v2.8l-2.3.6-.8 2 1.3 2.2-2 2-2.2-1.3-2 .8-.6 2.5h-2.8l-.6-2.5-2-.8-2.2 1.3-2-2 1.3-2.2-.8-2-2.3-.6v-2.8l2.3-.6.8-2L3.8 6l2-2L8 5.3l2-.8.6-2.5ZM12 8.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z"/>',
            'roles'=>'<path d="M6 3.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Zm12 0a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7ZM2 19.5c.3-4.2 1.7-6.5 4-6.5s3.7 2.3 4 6.5H2Zm12 0c.3-4.2 1.7-6.5 4-6.5s3.7 2.3 4 6.5h-8Z"/>',
        ];
        return '<svg class="fm2-nav-icon" viewBox="0 0 24 24" aria-hidden="true">'.($paths[$name]??'').'</svg>';
    }
    public static function document(HttpUser $user,string $title,string $current,string $breadcrumb,string $content):string
    {
        $objects=$current==='Объекты монтажа'?' aria-current="page"':'';$constructionControl=$current==='Стройконтроль'?' aria-current="page"':'';$installers=$current==='Монтажники'?' aria-current="page"':'';$users=$current==='Пользователи'?' aria-current="page"':'';$roles=$current==='Роли'?' aria-current="page"':'';
        $item=static fn(string $icon,string $label,bool $active=false):string=>'<span class="fm2-nav-item'.($active?' fm2-nav-item--active':' fm2-nav-item--muted').'"'.($active?'':' aria-disabled="true"').'>'.self::icon($icon).'<span class="fm2-nav-text">'.$label.'</span></span>';
        $nav='<span class="fm2-nav-group">Работа</span>'.$item('work','Моя работа').'<a class="fm2-nav-item" href="/pilot/construction-control"'.$constructionControl.'>'.self::icon('inspections').'<span class="fm2-nav-text">Стройконтроль</span></a><a class="fm2-nav-item" href="/pilot/objects"'.$objects.'>'.self::icon('objects').'<span class="fm2-nav-text">Объекты монтажа</span></a>'.$item('orders','Распоряжения')
            .'<span class="fm2-nav-group">Справочники</span><a class="fm2-nav-item" href="/pilot/installers"'.$installers.'>'.self::icon('installers').'<span class="fm2-nav-text">Монтажники</span></a>'
            .'<span class="fm2-nav-group">Управление</span>'.$item('otiz','Расчёты ОТиЗ').$item('control','Контроль')
            .'<span class="fm2-nav-group">Администрирование</span><a class="fm2-nav-item" href="/pilot/admin/users"'.$users.'>'.self::icon('admin').'<span class="fm2-nav-text">Пользователи</span></a><a class="fm2-nav-item" href="/pilot/admin/roles"'.$roles.'>'.self::icon('roles').'<span class="fm2-nav-text">Роли</span></a>';
        $trigger='<summary class="fm2-nav-trigger"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg><span class="fm2-nav-trigger-text">Развернуть меню</span></summary>';
        $pilotCssHref=$current==='Стройконтроль'?'/pilot/assets/pilot.css?v=20260829-20':'/pilot/assets/pilot.css';
        return '<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'.self::e($title).' · FMonitor</title><link rel="stylesheet" href="/pilot/assets/shlz.css"><link rel="stylesheet" href="'.$pilotCssHref.'"></head><body class="shlz-scope"><a class="fm2-skip shlz-link" href="#main-content">Перейти к содержанию</a><div class="fm2-shell"><aside class="fm2-sidebar"><div class="fm2-nav-body"><a class="fm2-logo" href="/pilot/objects" aria-label="FMonitor — объекты монтажа">'.self::logo().'</a><nav class="fm2-primary-nav" aria-label="Основная навигация">'.$nav.'</nav></div><details class="fm2-nav-state">'.$trigger.'</details></aside><div class="fm2-workspace"><main class="fm2-main" id="main-content" tabindex="-1">'.$breadcrumb.$content.'</main></div></div></body></html>';
    }
    public static function dl(array $pairs,string $class='fm2-details'):string{$html='<dl class="'.$class.'">';foreach($pairs as[$term,$value])$html.='<dt>'.$term.'</dt><dd class="fm2-db-text">'.$value.'</dd>';return $html.'</dl>';}
}

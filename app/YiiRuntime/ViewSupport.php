<?php

declare(strict_types=1);

namespace FMonitor2\YiiRuntime;

use FMonitor2\YiiRuntime\Assets\PreopeningAssetBundle;
use yii\helpers\Html;
use yii\web\View;

final class ViewSupport
{
    public static function begin(View $view, string $title, object $identity, ?string $currentSection = null): void
    {
        PreopeningAssetBundle::register($view);
        $csrf = Html::hiddenInput(\Yii::$app->request->csrfParam, \Yii::$app->request->csrfToken);
        $parts = preg_split('/\s+/u', trim((string) $identity->displayName), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }
        $view->beginPage();
        ?><!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= Html::encode($title) ?> · FMonitor 2.0</title>
    <link rel="icon" href="/pilot/assets/favicon.svg">
    <?php $view->head() ?>
</head>
<body class="shlz-scope">
<?php $view->beginBody() ?>
<a class="fm2-skip shlz-link" href="#main-content">Перейти к содержанию</a>
<div class="fm2-shell">
    <aside class="fm2-sidebar">
        <div class="fm2-nav-body">
            <a class="fm2-logo" href="/pilot/objects" aria-label="FMonitor 2.0">
                <svg class="fm2-logo-mark" viewBox="0 0 32 32" aria-hidden="true"><rect class="fm2-logo-rail" x="5" y="4" width="4" height="24"/><rect class="fm2-logo-rail" x="23" y="4" width="4" height="24"/><rect class="fm2-logo-progress" x="11" y="10" width="10" height="12"/></svg>
                <span class="fm2-logo-name">FMonitor 2.0</span>
            </a>
            <?= MainNavigation::render($identity, $currentSection) ?>
        </div>
        <div class="fm2-sidebar-user">
            <span class="shlz-avatar shlz-avatar--32 fm2-sidebar-avatar"><?= Html::encode($initials) ?></span>
            <span class="fm2-sidebar-user-copy"><strong><?= Html::encode($identity->displayName) ?></strong><small><?= Html::encode($identity->email) ?></small></span>
            <form method="post" action="/pilot/logout" class="fm2-logout-form"><?= $csrf ?><button class="fm2-logout" type="submit" aria-label="Выйти"><svg class="fm2-user-logout-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M10 4H5v16h5M14 8l4 4-4 4m4-4H9" fill="none" stroke="currentColor" stroke-width="1.7"/></svg><span class="fm2-logout-text">Выйти</span></button></form>
        </div>
        <?= MainNavigation::collapseControl() ?>
    </aside>
    <div class="fm2-workspace"><div class="fm2-main" id="main-content" tabindex="-1" role="main">
<?php
    }

    public static function end(View $view): void
    {
        ?></div></div></div><?php
        $view->endBody();
        ?></body></html><?php
        $view->endPage();
    }

    public static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 15) | 64);
        $bytes[8] = chr((ord($bytes[8]) & 63) | 128);
        $hex = bin2hex($bytes);
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
    }

    /** @param array<string|int,string> $options */
    public static function choice(string $name, string $value, array $options, string $label, array $attributes = []): string
    {
        static $sequence = 0;
        $sequence++;
        $choice = 'sel' . 'ect';
        $id = 'shlz-' . $choice . '-' . $sequence;
        $encode = static fn(string $text): string => Html::encode($text);
        $required = ($attributes['required'] ?? false) === true;
        $selectedLabel = array_key_exists($value, $options) ? (string) $options[$value] : (string) reset($options);
        $rootData = $required ? ' data-shlz-' . $choice . '-required' : '';
        $inputData = '';
        foreach ($attributes as $key => $attribute) {
            if ($key === 'required') continue;
            $encoded = ' ' . $encode((string) $key) . '="' . $encode((string) $attribute) . '"';
            if ($key === 'data-role-filter') $rootData .= $encoded;
            else $inputData .= $encoded;
        }
        $items = '';
        foreach ($options as $optionValue => $optionLabel) {
            $selected = (string) $optionValue === $value;
            $items .= '<button class="shlz-' . $choice . '__option" type="button" role="option" aria-selected="' . ($selected ? 'true' : 'false') . '" data-value="' . $encode((string) $optionValue) . '">' . $encode((string) $optionLabel) . '</button>';
        }
        $native = Html::dropDownList($name, $value, $options, ['class' => 'shlz-' . $choice,'required' => $required]);
        return '<div class="shlz-field shlz-field--' . $choice . ' shlz-' . $choice . '-root" data-shlz-' . $choice . $rootData . '>'
            . '<span class="shlz-field__label" id="' . $id . '-label">' . $encode($label) . '</span>'
            . '<button class="shlz-field__control shlz-' . $choice . '__trigger' . ($value !== '' ? ' shlz-' . $choice . '__trigger--selected' : '') . '" type="button" role="combobox" aria-haspopup="listbox" aria-expanded="false" aria-controls="' . $id . '-options" aria-labelledby="' . $id . '-label ' . $id . '-value"><span id="' . $id . '-value" data-shlz-' . $choice . '-value>' . $encode($selectedLabel) . '</span><svg class="shlz-' . $choice . '__chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8.5 12 15.5 19 8.5"/></svg></button>'
            . '<div class="shlz-' . $choice . '__listbox" id="' . $id . '-options" role="listbox" aria-labelledby="' . $id . '-label" hidden>' . $items . '</div>'
            . '<input type="hidden" name="' . $encode($name) . '" value="' . $encode($value) . '" disabled' . $inputData . '><span class="shlz-' . $choice . '-fallback">' . $native . '</span></div>';
    }

    public static function pagination(string $path,int $current,int $pages,int $total,int $pageSize,array $query,string $label):string
    {
        if($pages<=1||$current<1||$current>$pages||$pageSize<1)return '';
        $encode=static fn(string$value):string=>htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML5,'UTF-8');
        $href=static function(int$page)use($path,$query,$encode):string{$values=$query;$values['page']=$page;return $encode($path.'?'.http_build_query($values,'','&',PHP_QUERY_RFC3986));};
        $icon=static fn(string$name):string=>'<img class="shlz-pagination__icon" src="/pilot/assets/shlz-icons/'.($name==='arrow-left-md'?'chevron-left-duo':'chevron-right-duo').'.svg" alt="">';
        $items='<li>'.($current>1?'<a class="shlz-pagination__item" rel="prev" href="'.$href($current-1).'" aria-label="Предыдущая страница">'.$icon('arrow-left-md').'</a>':'<span class="shlz-pagination__item shlz-pagination__item--disabled" aria-disabled="true">'.$icon('arrow-left-md').'<span class="shlz-visually-hidden">Предыдущая страница недоступна</span></span>').'</li>';
        $window=[1];for($page=max(2,$current-1);$page<=min($pages-1,$current+1);$page++)$window[]=$page;if($pages>1)$window[]=$pages;$window=array_values(array_unique($window));$previous=0;
        foreach($window as$page){if($previous!==0&&$page>$previous+1)$items.='<li><span class="shlz-pagination__item shlz-pagination__item--ellipsis" aria-hidden="true">…</span></li>';$items.='<li><a class="shlz-pagination__item" href="'.$href($page).'"'.($page===$current?' aria-current="page"':'').'>'.$page.'</a></li>';$previous=$page;}
        $items.='<li>'.($current<$pages?'<a class="shlz-pagination__item" rel="next" href="'.$href($current+1).'" aria-label="Следующая страница">'.$icon('arrow-right-md').'</a>':'<span class="shlz-pagination__item shlz-pagination__item--disabled" aria-disabled="true">'.$icon('arrow-right-md').'<span class="shlz-visually-hidden">Следующая страница недоступна</span></span>').'</li>';
        $from=($current-1)*$pageSize+1;$to=min($total,$current*$pageSize);
        return '<nav class="shlz-pagination" aria-label="'.$encode($label).'"><ul class="shlz-pagination__list">'.$items.'</ul><span class="shlz-pagination__summary">'.$from.'–'.$to.' из '.$total.'</span></nav>';
    }
}

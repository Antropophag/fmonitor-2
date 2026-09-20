<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/autoload.php';

use FMonitor2\YiiRuntime\ViewSupport;

// OTIZ-SHLZ-UI-001 A12: one public shlz pagination composition for Yii lists.
assertSameValue(true,method_exists(ViewSupport::class,'pagination'),'INTENDED_RED: shared Yii pagination renderer exists');
$html=ViewSupport::pagination('/pilot/otiz',3,8,125,25,['q'=>'Лифт 1','state'=>'ready','sort'=>'regnumber_asc'],'Страницы объектов ОТиЗ');
$dom=new DOMDocument();libxml_use_internal_errors(true);$dom->loadHTML('<?xml encoding="utf-8"?>'.$html);libxml_clear_errors();$xp=new DOMXPath($dom);
assertSameValue(1,$xp->query('//nav[contains(concat(" ",normalize-space(@class)," ")," shlz-pagination ") and @aria-label="Страницы объектов ОТиЗ"]')->length,'public pagination landmark');
assertSameValue(1,$xp->query('//nav/ul[contains(concat(" ",normalize-space(@class)," ")," shlz-pagination__list ")]')->length,'public list composition');
assertSameValue(1,$xp->query('//a[@aria-current="page" and normalize-space(.)="3"]')->length,'current page remains a link');
assertSameValue(0,$xp->query('//button')->length,'pagination uses destinations, not buttons');
assertSameValue($xp->query('//nav/ul/li')->length,$xp->query('//nav/ul/li/*')->length,'one public item per list entry');assertSameValue(0,$xp->query('//nav//a[not(contains(concat(" ",normalize-space(@class)," ")," shlz-pagination__item "))]')->length,'every destination uses public item class');assertSameValue(true,$xp->query('//span[contains(concat(" ",normalize-space(@class)," ")," shlz-pagination__item--ellipsis ") and @aria-hidden="true"]')->length>=1,'compact range uses non-link ellipsis');assertSameValue(true,$xp->query('//img[contains(concat(" ",normalize-space(@class)," ")," shlz-pagination__icon ") and @alt=""]')->length>=2,'directions use public icons');assertSameValue(1,$xp->query('//*[contains(concat(" ",normalize-space(@class)," ")," shlz-pagination__summary ") and contains(normalize-space(.),"51–75 из 125")]')->length,'range summary is visible');
foreach($xp->query('//nav//a[@href]')as$link){parse_str((string)parse_url(html_entity_decode($link->getAttribute('href')),PHP_URL_QUERY),$query);assertSameValue(['Лифт 1','ready','regnumber_asc'],[$query['q']??null,$query['state']??null,$query['sort']??null],'query context preserved');}
$first=ViewSupport::pagination('/pilot/objects',1,3,51,25,[],'Страницы объектов');$last=ViewSupport::pagination('/pilot/objects',3,3,51,25,[],'Страницы объектов');assertSameValue(true,str_contains($first,'shlz-pagination__item--disabled')&&str_contains($first,'Предыдущая страница недоступна'),'first page disables previous direction');assertSameValue(true,str_contains($last,'shlz-pagination__item--disabled')&&str_contains($last,'Следующая страница недоступна'),'last page disables next direction');
foreach(['app/YiiRuntime/Views/objects.php','app/YiiRuntime/Views/installers.php','app/YiiRuntime/Views/construction-control.php','app/YiiRuntime/Views/otiz.php']as$file){$source=(string)file_get_contents(dirname(__DIR__,2).'/'.$file);assertSameValue(true,str_contains($source,'ViewSupport::pagination'),'INTENDED_RED: '.$file.' consumes shared pagination');assertSameValue(false,str_contains($source,'fm2-pagination'),'local pagination substitute removed from '.$file);}
echo "PASS: OTIZ-SHLZ-UI-001 shared Yii pagination composition\n";

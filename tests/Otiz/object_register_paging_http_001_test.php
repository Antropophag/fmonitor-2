<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require_once dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';

use FMonitor2\Tests\Support\{ObjectRegisterPagingFixture,SelectionHttpFixture,SelectedOriginalFixture};

// Real router/session seam. The fixture is disposable and carries no production data.
$p='orh_'.bin2hex(random_bytes(5)).'_';
$f=new SelectionHttpFixture(true,static function(SelectedOriginalFixture$original)use($p):array{
    ObjectRegisterPagingFixture::seed($original,$p,3);
    return['FMONITOR_NOW'=>'2026-09-09T12:00:00+03:00'];
},$p);
$rows=static function(string$html):array{$dom=new DOMDocument();@$dom->loadHTML($html,LIBXML_NONET);$xpath=new DOMXPath($dom);$ids=[];foreach($xpath->query('//*[@data-otiz-row]//a[starts-with(@href,"/pilot/objects/")]')as$link)$ids[]=(int)basename((string)$link->getAttribute('href'));return$ids;};
$control=static function(string$html,string$name):?string{$dom=new DOMDocument();@$dom->loadHTML($html,LIBXML_NONET);$xpath=new DOMXPath($dom);$nodes=$xpath->query('//*[@name="'.$name.'"]');return$nodes->length?(string)$nodes->item(0)->getAttribute('value'):null;};
try{
    $first=$f->request('GET','/pilot/otiz?sort=regnumber_asc&pageSize=50','',31);
    assertSameValue(200,$first['status'],'authorized real OTIZ register; server log='.(string)@file_get_contents($f->original->control.'/http.log'));
    assertSameValue(range(1,50),$rows($first['body']),'HTTP returns only the first stable server page');
    assertSameValue(false,str_contains($first['body'],'/pilot/objects/51"'),'page one DOM excludes page two object');
    assertSameValue(true,str_contains($first['body'],'aria-label="Страница 1" aria-current="page"')||str_contains($first['body'],'aria-current="page" aria-label="Страница 1"'),'current page has accessible marker');
    assertSameValue(true,str_contains(html_entity_decode($first['body'],ENT_QUOTES),'sort=regnumber_asc&pageSize=50&page=2')||str_contains(html_entity_decode($first['body'],ENT_QUOTES),'sort=regnumber_asc&page=2&pageSize=50'),'pager preserves sort and page size');

    $third=$f->request('GET','/pilot/otiz?sort=regnumber_asc&pageSize=50&page=3','',31);
    assertSameValue(200,$third['status'],'third HTTP page exists');
    assertSameValue(range(101,125),$rows($third['body']),'third HTTP page has only its remainder');
    preg_match('/<section class="fm2-otiz-register-summary".*?<\/section>/s',$first['body'],$summaryOne);
    preg_match('/<section class="fm2-otiz-register-summary".*?<\/section>/s',$third['body'],$summaryThree);
    assertSameValue($summaryOne[0]??null,$summaryThree[0]??null,'global financial summary is page-independent');

    $query=rawurlencode('УНИКАЛЬНАЯ цель');
    $found=$f->request('GET','/pilot/otiz?q='.$query.'&state=planned&sort=regnumber_desc&pageSize=25','',31);
    assertSameValue(200,$found['status'],'server-side filtered request succeeds');
    assertSameValue([125],$rows($found['body']),'HTTP search reaches former third-page object');
    assertSameValue('УНИКАЛЬНАЯ цель',$control($found['body'],'q'),'query survives in GET control');
    assertSameValue('planned',$control($found['body'],'state'),'state survives in GET control');
    assertSameValue('regnumber_desc',$control($found['body'],'sort'),'sort survives in GET control');
    assertSameValue('25',$control($found['body'],'pageSize'),'page size survives in GET control');
    assertSameValue(false,str_contains($found['body'],'name="page" value="3"'),'filter form does not preserve an old page');

    $empty=$f->request('GET','/pilot/otiz?q='.rawurlencode('нет совпадений'),'',31);
    assertSameValue(200,$empty['status'],'empty filtered page one succeeds');
    assertSameValue([],$rows($empty['body']),'empty result renders no object rows');
    assertSameValue(true,str_contains($empty['body'],'Объекты не найдены')||str_contains($empty['body'],'ничего не найден'),'empty result explains the state');

    foreach(['/pilot/otiz?page=0','/pilot/otiz?page=01','/pilot/otiz?pageSize=101','/pilot/otiz?state=unknown','/pilot/otiz?sort=unknown','/pilot/otiz?q%5B%5D=x']as$target)
        assertSameValue(400,$f->request('GET',$target,'',31)['status'],'invalid known query parameter maps to HTTP 400: '.$target);
    assertSameValue(404,$f->request('GET','/pilot/otiz?page=4','',31)['status'],'page beyond result maps to HTTP 404');
    assertSameValue(403,$f->request('GET','/pilot/otiz?page=0','',18)['status'],'authorization precedes invalid query handling');
    echo "PASS OTIZ real HTTP bounded paging, preserved GET context, empty and refusal states\n";
}finally{$f->close();}

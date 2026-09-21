<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/ObjectQueueFixture.php';

$root=dirname(__DIR__,2);foreach(glob($root.'/app/YiiRuntime/Views/*.php')as$view)assertSameValue(false,str_contains((string)file_get_contents($view),'fm2-primary-nav'),'shared MainNavigation is sole primary-nav owner '.basename($view));$navigationJs=(string)file_get_contents($root.'/app/YiiRuntime/Assets/navigation.js');foreach(['insertAdjacentHTML','appendChild','before(','after(','replaceWith']as$mutation)assertSameValue(false,str_contains($navigationJs,$mutation),'navigation client does not insert/reorder links '.$mutation);

// YII2-MAIN-NAVIGATION-001: real Yii HTTP and semantic navigation DOM.
$fixture = null;
try {
    $fixture = new ObjectQueueFixture(dirname(__DIR__, 2));
    $http = $fixture->http;
    $db = $fixture->db;
    $prefix = $fixture->p;
    $db->query("ALTER TABLE fm_maintable ADD responsstroicontrol VARCHAR(80) NULL");
    foreach (['construction_control.read', 'otiz.manage', 'installers.read'] as $permission) {
        $fixture->insert($prefix . 'fm2_pilot_role_permissions', ['role_id' => 9201, 'permission' => $permission]);
    }
    $http->start();
    $cookies = [];
    assertSameValue(303, $http->login($cookies)['status'], 'SETUP_OK authenticated full-permission actor');

    $routes = [
        '/pilot/dashboard' => '/pilot/dashboard',
        '/pilot/objects' => '/pilot/objects',
        '/pilot/calendar' => '/pilot/calendar',
        '/pilot/installers' => '/pilot/installers',
        '/pilot/construction-control' => '/pilot/construction-control',
        '/pilot/otiz' => '/pilot/otiz',
        '/pilot/admin/users' => '/pilot/admin/users',
        '/pilot/admin/roles' => '/pilot/admin/roles',
        '/pilot/feedback' => null,
    ];
    $labels = [
        '/pilot/dashboard' => 'Дашборд',
        '/pilot/objects' => 'Объекты монтажа',
        '/pilot/calendar' => 'Календарь',
        '/pilot/installers' => 'Монтажники',
        '/pilot/construction-control' => 'Стройконтроль',
        '/pilot/otiz' => 'ОТиЗ',
        '/pilot/feedback' => 'Обратная связь',
        '/pilot/admin/users' => 'Пользователи',
        '/pilot/admin/roles' => 'Роли',
    ];
    $navigation = static function (string $html): array {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $loaded = $document->loadHTML('<?xml encoding="UTF-8">' . $html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        assertSameValue(true, $loaded, 'response is parseable HTML');
        $xpath = new DOMXPath($document);
        $nodes = $xpath->query('//nav[@aria-label="Основная навигация"]');
        assertSameValue(1, $nodes->length, 'INTENDED_RED exactly one shared MAIN navigation');
        $links = [];
        foreach ($xpath->query('.//a[@href]', $nodes->item(0)) as $link) {
            $href = html_entity_decode($link->getAttribute('href'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $links[] = [
                'href' => $href,
                'label' => trim((string) preg_replace('/\s+/u', ' ', $link->textContent)),
                'current' => $link->getAttribute('aria-current'),
            ];
        }
        return [$links, $document, $xpath, $nodes->item(0)];
    };
    $svgSignature = static function (DOMElement $svg): array {
        $signature=['root'=>[
            'width'=>$svg->getAttribute('width'),
            'height'=>$svg->getAttribute('height'),
            'viewBox'=>$svg->hasAttribute('viewBox')?$svg->getAttribute('viewBox'):$svg->getAttribute('viewbox'),
        ],'paths'=>[]];
        foreach ($svg->getElementsByTagName('path') as $path) {
            $attributes=[];
            foreach ($path->attributes as $attribute) if ($attribute->name !== 'id') $attributes[$attribute->name]=$attribute->value;
            ksort($attributes);$signature['paths'][]=$attributes;
        }
        return $signature;
    };
    $sourceSignature = static function (string $name) use ($svgSignature): array {
        $digests=['book'=>'02474fdd62404567cbe9b93cbb5f90e46157b9e47d3058e3c2abb8f14ed2c224','chat'=>'e9f9b5909e20b379d3fb3bd394a23bd227cc549c8991b83b6b7b6072b1959441','chevron-left-duo'=>'c07cf377ff53529a6e1dd12deb7142cc3e0df15c10ec9d0cd4ba9eb5020716aa','chevron-right-duo'=>'95af60d983a376e4b61ccf073ff449881d0e8c3ccc38ec78fa4a3d904e85e496','circle-grid-interface-sidebar'=>'3af6f30c34dc5401cb2a2e875d91fd83fd0bcb64157f3c14ee4b6b243a21daff','pie-chart'=>'3bd0df2b308d820e1fda0bec8caaefe47f13bc540d89158f930975ee36969186','setting-tool-circle'=>'68850778eaf784874ca689e1787a4ab01a49676dea1ff79cc1b73c560197d08c','user-1'=>'9ed6d6338c6b5a965d9f94c3a6aa9a8b20221af384e1960f5dc528b8c5d11b39','user-sidebar'=>'da954ca48eba978e5c2c0004b9dbd542e391daabe5293f0d3fc2fa98ff3ce260','calendar-sidebar'=>'e48dfea86a132c25b44344ee52b4244853280ddbbf7794d1552ca26e0194f54c'];
        $path=dirname(__DIR__,2).'/app/YiiRuntime/Assets/shlz-icons/'.$name.'.svg';$bytes=is_file($path)?file_get_contents($path):false;
        assertSameValue(true,is_string($bytes),'pinned shlz icon exists '.$name);assertSameValue($digests[$name]??null,hash('sha256',$bytes),'immutable pinned shlz digest '.$name);
        $document=new DOMDocument();assertSameValue(true,$document->loadXML($bytes),'pinned shlz icon parses '.$name);
        return $svgSignature($document->documentElement);
    };
    $assertMatrix = static function (array $expectedSections, array $availableRoutes) use ($http, &$cookies, $navigation, $labels, $svgSignature, $sourceSignature): void {
        $observedOrder = null;
        foreach ($availableRoutes as $route => $current) {
            $response = $http->request('GET', $route, [], $cookies);
            assertSameValue(200, $response['status'], 'available route ' . $route);
            [$links, , $xpath, $main] = $navigation($response['body']);
            $feedback = '/pilot/feedback?from=' . rawurlencode($route);
            $expected = [];
            foreach ($expectedSections as $href) $expected[] = ['href' => $href, 'label' => $labels[$href]];
            $actual = array_map(static fn(array $link): array => array_intersect_key($link, ['href' => true, 'label' => true]), $links);
            usort($expected, static fn(array $a, array $b): int => strcmp($a['href'], $b['href']));
            $membership = $actual;
            usort($membership, static fn(array $a, array $b): int => strcmp($a['href'], $b['href']));
            assertSameValue($expected, $membership, 'INTENDED_RED exact permitted MAIN membership and labels on ' . $route);
            $order = array_map(static fn(array $link): string => str_starts_with($link['href'], '/pilot/feedback?') ? '/pilot/feedback' : $link['href'], $links);
            $expectedOrder = array_values(array_filter(['/pilot/dashboard','/pilot/objects','/pilot/construction-control','/pilot/calendar','/pilot/otiz','/pilot/installers','/pilot/admin/users','/pilot/admin/roles'], static fn(string $href): bool => in_array($href, $expectedSections, true)));
            assertSameValue($expectedOrder, $order, 'INTENDED_RED exact MAIN order on ' . $route);
            assertSameValue(0, $xpath->query('.//a[starts-with(@href,"/pilot/feedback")]', $main)->length, 'INTENDED_RED feedback is not a MAIN navigation item on ' . $route);
            $floatingFeedback = $xpath->query('//a[contains(concat(" ",normalize-space(@class)," ")," fm2-feedback-fab ") and starts-with(@href,"/pilot/feedback")]');
            $feedbackExpected=$route==='/pilot/feedback'?0:1;
            assertSameValue($feedbackExpected, $floatingFeedback->length, 'INTENDED_RED floating feedback presence on ' . $route);
            if($feedbackExpected===1){
                assertSameValue($feedback, html_entity_decode($floatingFeedback->item(0)->getAttribute('href'), ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'floating feedback preserves source route');
                assertSameValue('Обратная связь', $floatingFeedback->item(0)->getAttribute('aria-label'), 'floating feedback has accessible name');
                $fabSvg=$xpath->query('.//*[name()="svg" and @data-shlz-icon="chat"]',$floatingFeedback->item(0))->item(0);
                assertSameValue(true,$fabSvg instanceof DOMElement,'feedback shlz chat icon');
                assertSameValue($sourceSignature('chat'),$svgSignature($fabSvg),'feedback geometry equals pinned shlz chat');
            }
            $groups = [];
            foreach ($xpath->query('./span[contains(concat(" ",normalize-space(@class)," ")," fm2-nav-group ")]', $main) as $group) {
                $groups[] = trim($group->textContent);
            }
            $expectedGroups = ['Монтаж'];
            if (in_array('/pilot/admin/users', $expectedSections, true)) $expectedGroups[] = 'Администрирование';
            assertSameValue($expectedGroups, $groups, 'preserved sidebar groups on ' . $route);
            $tokens=[];
            foreach ($main->childNodes as $child) {
                if (!$child instanceof DOMElement) continue;
                $classes=' '.$child->getAttribute('class').' ';
                if (str_contains($classes,' fm2-nav-group ')) $tokens[]=['group',trim($child->textContent)];
                elseif ($child->tagName==='a') $tokens[]=['link',html_entity_decode($child->getAttribute('href'),ENT_QUOTES|ENT_HTML5,'UTF-8')];
            }
            $expectedTokens=[];
            foreach ([
                'Монтаж'=>['/pilot/dashboard','/pilot/objects','/pilot/construction-control','/pilot/calendar','/pilot/otiz','/pilot/installers'],
                'Администрирование'=>['/pilot/admin/users','/pilot/admin/roles'],
            ] as $group=>$children) {
                $present=array_values(array_filter($children,static fn(string $href):bool=>in_array($href,$expectedSections,true)));
                if ($present===[]) continue;
                $expectedTokens[]=['group',$group];foreach($present as $href)$expectedTokens[]=['link',$href];
            }
            assertSameValue($expectedTokens,$tokens,'INTENDED_RED exact group-to-child hierarchy on '.$route);
            assertSameValue(count($links), $xpath->query('./a/*[name()="svg" and contains(concat(" ",normalize-space(@class)," ")," fm2-nav-icon ") and contains(concat(" ",normalize-space(@class)," ")," fm2-nav-icon--shlz ") and @aria-hidden="true"]', $main)->length, 'INTENDED_RED every MAIN link uses one shlz icon on ' . $route);
            $iconByHref=['/pilot/dashboard'=>'pie-chart','/pilot/objects'=>'circle-grid-interface-sidebar','/pilot/calendar'=>'calendar-sidebar','/pilot/construction-control'=>'setting-tool-circle','/pilot/installers'=>'user-sidebar','/pilot/otiz'=>'pie-chart','/pilot/admin/users'=>'user-1','/pilot/admin/roles'=>'book'];
            foreach($links as$link){$name=$iconByHref[$link['href']]??null;assertSameValue(true,is_string($name),'known nav icon '.$link['href']);$svg=$xpath->query('./*[name()="svg" and @data-shlz-icon="'.$name.'"]',$main->getElementsByTagName('a')->item(array_search($link,$links,true)))->item(0);assertSameValue(true,$svg instanceof DOMElement,'exact nav icon '.$name);assertSameValue($sourceSignature($name),$svgSignature($svg),'nav geometry equals pinned shlz '.$name);}
            foreach(['chevron-left-duo','chevron-right-duo']as$name){$svg=$xpath->query('//summary[contains(concat(" ",normalize-space(@class)," ")," fm2-nav-trigger ")]/*[name()="svg" and @data-shlz-icon="'.$name.'"]')->item(0);assertSameValue(true,$svg instanceof DOMElement,'collapse contains '.$name);assertSameValue($sourceSignature($name),$svgSignature($svg),'collapse geometry equals pinned shlz '.$name);}
            $active = array_values(array_column(array_filter($links, static fn(array $link): bool => $link['current'] === 'page'), 'href'));
            assertSameValue($current === null ? [] : [$current], $active, 'INTENDED_RED only the canonical current section is marked on ' . $route);
        }
    };

    $canonical = ['/pilot/dashboard', '/pilot/objects', '/pilot/calendar', '/pilot/construction-control', '/pilot/installers', '/pilot/otiz', '/pilot/admin/users', '/pilot/admin/roles'];
    $before = $fixture->facts();
    $assertMatrix($canonical, $routes);
    $assertMatrix($canonical, $routes); // repeated reads independently render and remain read-only
    assertSameValue($before, $fixture->facts(), 'root and nested repeated reads create no database facts');

    $db->query("DELETE FROM {$prefix}fm2_pilot_role_permissions WHERE role_id=9201 AND permission='installers.read'");
    $phaseBefore = $fixture->facts();
    $withoutInstallers = array_values(array_diff($canonical, ['/pilot/installers']));
    $assertMatrix($withoutInstallers, array_diff_key($routes, ['/pilot/installers' => true]));
    assertSameValue(403, $http->request('GET', '/pilot/installers', [], $cookies)['status'], 'direct installer directory authorization unchanged');
    assertSameValue($phaseBefore, $fixture->facts(), 'no-installers reads and denial create no facts');
    $db->query("INSERT INTO {$prefix}fm2_pilot_role_permissions(role_id,permission) VALUES(9201,'installers.read')");

    $otiz = $http->request('GET', '/pilot/otiz', [], $cookies);
    [, $otizDocument, $otizXpath, $mainNode] = $navigation($otiz['body']);
    $internal = [];
    foreach ($otizXpath->query('//nav[not(@aria-label="Основная навигация")]') as $nav) {
        $candidate = [];
        foreach ($otizXpath->query('.//a[@href]', $nav) as $link) $candidate[] = [
            'href' => $link->getAttribute('href'),
            'label' => trim((string) preg_replace('/\s+/u', ' ', $link->textContent)),
        ];
        if ($candidate !== []) $internal[] = $candidate;
    }
    assertSameValue(true, $mainNode->ownerDocument === $otizDocument, 'MAIN navigation belongs to parsed OTIZ document');
    assertSameValue([[
        ['href' => '/pilot/otiz/objects', 'label' => 'Экономика объектов'],
        ['href' => '/pilot/otiz/payments', 'label' => 'Подготовка выплат'],
        ['href' => '/pilot/otiz/history', 'label' => 'Архив расчётов'],
    ]], $internal, 'OTIZ internal navigation remains one distinct semantic nav');
    foreach (['/pilot/otiz/objects', '/pilot/otiz/payments', '/pilot/otiz/history'] as $internalRoute) {
        $internalPage = $http->request('GET', $internalRoute, [], $cookies);
        assertSameValue(200, $internalPage['status'], 'existing OTIZ internal route remains available ' . $internalRoute);
        [$internalLinks] = $navigation($internalPage['body']);
        $active = array_values(array_column(array_filter($internalLinks, static fn(array $link): bool => $link['current'] === 'page'), 'href'));
        assertSameValue(['/pilot/otiz'], $active, 'shared MAIN keeps OTIZ current on internal route ' . $internalRoute);
    }

    $db->query("DELETE FROM {$prefix}fm2_pilot_role_permissions WHERE role_id=9201 AND permission='otiz.manage'");
    $phaseBefore = $fixture->facts();
    $withoutOtiz = array_diff($canonical, ['/pilot/otiz']);
    $assertMatrix(array_values($withoutOtiz), array_diff_key($routes, ['/pilot/otiz' => true]));
    assertSameValue(403, $http->request('GET', '/pilot/otiz', [], $cookies)['status'], 'direct OTIZ authorization unchanged');
    assertSameValue($phaseBefore, $fixture->facts(), 'no-OTIZ reads and denial create no facts');

    $db->query("INSERT INTO {$prefix}fm2_pilot_role_permissions(role_id,permission) VALUES(9201,'otiz.manage')");
    $db->query("DELETE FROM {$prefix}fm2_pilot_role_permissions WHERE role_id=9201 AND permission='construction_control.read'");
    $phaseBefore = $fixture->facts();
    $withoutControl = array_diff($canonical, ['/pilot/construction-control']);
    $assertMatrix(array_values($withoutControl), array_diff_key($routes, ['/pilot/construction-control' => true]));
    assertSameValue(403, $http->request('GET', '/pilot/construction-control', [], $cookies)['status'], 'direct construction-control authorization unchanged');
    assertSameValue($phaseBefore, $fixture->facts(), 'no-control reads and denial create no facts');

    $db->query("INSERT INTO {$prefix}fm2_pilot_role_permissions(role_id,permission) VALUES(9201,'construction_control.read')");
    $db->query("DELETE FROM {$prefix}fm2_pilot_role_permissions WHERE role_id=9201 AND permission='objects.read'");
    $phaseBefore = $fixture->facts();
    $withoutObjects = ['/pilot/installers', '/pilot/construction-control', '/pilot/otiz', '/pilot/admin/users', '/pilot/admin/roles'];
    $assertMatrix($withoutObjects, array_intersect_key($routes, array_fill_keys([...$withoutObjects, '/pilot/feedback'], true)));
    assertSameValue(403, $http->request('GET', '/pilot/objects', [], $cookies)['status'], 'direct objects authorization unchanged');
    assertSameValue($phaseBefore, $fixture->facts(), 'no-objects reads and denial create no facts');

    $db->query("INSERT INTO {$prefix}fm2_pilot_role_permissions(role_id,permission) VALUES(9201,'objects.read')");
    $db->query("DELETE FROM {$prefix}fm2_pilot_role_permissions WHERE role_id=9201 AND permission IN ('access.administer','inspection.schedule')");
    $phaseBefore = $fixture->facts();
    $withoutAdmin = ['/pilot/dashboard', '/pilot/objects', '/pilot/calendar', '/pilot/construction-control', '/pilot/installers', '/pilot/otiz'];
    $assertMatrix($withoutAdmin, array_intersect_key($routes, array_fill_keys([...$withoutAdmin, '/pilot/feedback'], true)));
    foreach (['/pilot/admin/users', '/pilot/admin/roles'] as $adminRoute) {
        assertSameValue(403, $http->request('GET', $adminRoute, [], $cookies)['status'], 'both direct admin routes retain access.administer guard ' . $adminRoute);
    }
    assertSameValue($phaseBefore, $fixture->facts(), 'no-admin reads and denials create no facts');

    $db->query("INSERT INTO {$prefix}fm2_pilot_role_permissions(role_id,permission) VALUES(9201,'access.administer')");
    $db->query("DELETE FROM {$prefix}fm2_pilot_role_permissions WHERE role_id=9201 AND permission IN ('otiz.manage','construction_control.read')");
    $phaseBefore = $fixture->facts();
    $restricted = ['/pilot/dashboard', '/pilot/objects', '/pilot/calendar', '/pilot/installers', '/pilot/admin/users', '/pilot/admin/roles'];
    $assertMatrix($restricted, array_intersect_key($routes, array_fill_keys([...$restricted, '/pilot/feedback'], true)));
    assertSameValue($phaseBefore, $fixture->facts(), 'restricted admin combination reads create no facts');

    $guest = [];
    $guestResponse = $http->request('GET', '/pilot/admin/users', [], $guest);
    assertSameValue([303, '/pilot/login'], [$guestResponse['status'], $guestResponse['headers']['location'][0] ?? null], 'guest direct-route authorization unchanged');

    echo "PASS: YII2-MAIN-NAVIGATION-001 shared permission-aware Yii navigation\n";
} finally {
    if ($fixture instanceof ObjectQueueFixture) $fixture->close();
}

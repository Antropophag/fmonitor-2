<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/PreopeningFixture.php';

$fixture = null;
$browserProcess = null;
$browserConfig = null;
$failures = [];
$check = static function (bool $value, string $message) use (&$failures): void {
    if (!$value) $failures[] = $message;
};

try {
    $fixture = new PreopeningFixture(dirname(__DIR__, 2));
    $fixture->start();
    $cookies = [];
    $fixture->login($cookies);
    $fixture->base->selection->app()->selectAssignmentOrderComposition(
        FMonitor2\Tests\Support\SelectionNativeFixture::command(),
    );
    $fixture->nativeOriginal('2026-09-01');
    $fixture->db->query("UPDATE {$fixture->p}fm2_workforce_catalog SET employment_status='dismissed',dismissal_effective_at='2026-09-10' WHERE installer_tab_id=7001");
    $details = json_encode([
        'schemaVersion' => 'technical-object-detail-v1',
        'objectId' => 4512,
        'fields' => [
            'floors' => ['raw' => '9', 'display' => '9'],
            'weight' => ['raw' => '630', 'display' => '630'],
            'speed' => ['raw' => '44', 'display' => '44'],
            'lift_type' => ['raw' => '1', 'display' => 'Пассажирский'],
            'pittype' => ['raw' => '40', 'display' => '40'],
            'pitmaterial' => ['raw' => '41', 'display' => '41'],
            'paired' => ['raw' => '39', 'display' => '39'],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $statement = $fixture->db->prepare("UPDATE {$fixture->p}fm2_pilot_object_details SET payload_json=?,content_sha256=? WHERE object_id=4512");
    $statement->execute([$details, hash('sha256', $details)]);

    $before = $fixture->facts();
    $card = $fixture->request('GET', '/pilot/objects/4512', [], $cookies);
    $check($card['status'] === 200, 'card GET');
    $body = $card['body'];
    foreach (['Сроки и готовность','Команда','Документы','История','role="tablist"','role="tab"','role="tabpanel"','data-shlz-tabs','shlz-document-row','shlz-document-row__visual','shlz-document-row__title','shlz-document-row__meta','shlz-document-row__actions','/pilot/assets/shlz-file-types/file-pdf-default.svg','/pilot/assets/shlz-icons/download.svg','Регистрационный номер','Табельный','Уволен','Сведения о поставке не переданы'] as $text) {
        $check(str_contains($body, $text), 'rendered ' . $text);
    }
    foreach (['Источник','legacy_fmonitor','position_snapshot','workforce_source','Статус не указан'] as $text) {
        $check(!str_contains($body, $text), 'card hides ' . $text);
    }
    $assetCookies = [];
    foreach ([
        '/pilot/assets/shlz-file-types/file-pdf-default.svg' => dirname($fixture->root) . '/shlz-ui/packages/icons/dist/file-types/file-pdf-default.svg',
        '/pilot/assets/shlz-icons/download.svg' => dirname($fixture->root) . '/shlz-ui/packages/icons/dist/icons/download.svg',
    ] as $assetPath => $canonicalPath) {
        $asset = $fixture->request('GET', $assetPath, [], $assetCookies);
        $assetHead = $fixture->request('HEAD', $assetPath, [], $assetCookies);
        $canonicalBytes = (string) file_get_contents($canonicalPath);
        $check($asset['status'] === 200 && $asset['body'] === $canonicalBytes, 'canonical asset bytes ' . $assetPath);
        $check(($asset['headers']['content-type'][0] ?? null) === 'image/svg+xml; charset=UTF-8', 'asset MIME ' . $assetPath);
        $check(($asset['headers']['cache-control'][0] ?? null) === 'public, max-age=3600', 'asset revalidated cache ' . $assetPath);
        $check(($asset['headers']['x-content-type-options'][0] ?? null) === 'nosniff', 'asset nosniff ' . $assetPath);
        $check(($asset['headers']['cross-origin-resource-policy'][0] ?? null) === 'same-origin', 'asset CORP ' . $assetPath);
        $check($assetHead['status'] === 200 && $assetHead['body'] === '', 'asset HEAD ' . $assetPath);
        $check(($assetHead['headers']['content-length'][0] ?? null) === (string) strlen($canonicalBytes), 'asset HEAD length ' . $assetPath);
    }
    foreach (['/pilot/assets/shlz-file-types/not-a-format.svg','/pilot/assets/shlz-file-types/%2e%2e%2fdownload.svg'] as $rejectedAsset) {
        $rejected = $fixture->request('GET', $rejectedAsset, [], $assetCookies);
        $check($rejected['status'] === 404 && !str_contains($rejected['body'], '<svg'), 'asset rejection ' . $rejectedAsset);
    }
    $check(substr_count($body, 'role="tab"') === 4, 'exact four tabs');
    $check(substr_count($body, 'role="tabpanel"') === 4, 'exact four panels');
    $check(!str_contains($body, 'fm2-object-summary'), 'no duplicate summary');

    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="utf-8"?>' . $body);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $xpath = new DOMXPath($document);
    $panel = $xpath->query('//*[@id="object-panel-readiness"]')->item(0);
    $check($panel !== null, 'readiness panel exists');
    $fact = static function (string $label, string $value) use ($xpath, $panel): bool {
        if ($panel === null) return false;
        foreach ($xpath->query('.//dt[normalize-space()="' . $label . '"]', $panel) ?: [] as $term) {
            $next = $term->nextSibling;
            while ($next && $next->nodeType !== XML_ELEMENT_NODE) $next = $next->nextSibling;
            if ($next && trim($next->textContent) === $value) return true;
        }
        return false;
    };
    foreach ([['Плановое начало','01.10.2026'],['Плановое завершение','01.12.2026'],['Монтажное дело','Готов к открытию'],['Оригинал распоряжения','Принят']] as [$label, $value]) {
        $check($fact($label, $value), 'readiness pair ' . $label . '=' . $value);
    }
    $panelText = $panel ? trim($panel->textContent) : '';
    $check(str_contains($panelText, 'Сведения о поставке не переданы'), 'readiness missing delivery');
    $check(!str_contains($panelText, 'Фактическое начало'), 'absent factual start is not invented');
    foreach (['Этажность','Грузоподъёмность, кг','Скорость, м/с'] as $label) {
        $check(!str_contains($panelText, $label), 'readiness excludes passport fact ' . $label);
    }
    $passport = $xpath->query('//*[contains(concat(" ",normalize-space(@class)," ")," fm2-static-passport ")]')->item(0);
    $check($passport !== null, 'passport exists');
    $passportText = $passport ? trim($passport->textContent) : '';
    foreach (['Адрес','Регистрационный номер','Текущий статус'] as $duplicateLabel) {
        $check(!str_contains($passportText, $duplicateLabel), 'passport excludes duplicate ' . $duplicateLabel);
    }
    $passportFact = static function (string $label, string $value) use ($xpath, $passport): bool {
        if ($passport === null) return false;
        foreach ($xpath->query('.//dt[normalize-space()="' . $label . '"]', $passport) ?: [] as $term) {
            $next = $term->nextSibling;
            while ($next && $next->nodeType !== XML_ELEMENT_NODE) $next = $next->nextSibling;
            if ($next && trim($next->textContent) === $value) return true;
        }
        return false;
    };
    foreach ([['Этажность','9'],['Грузоподъёмность, кг','630'],['Скорость, м/с','1,0'],['Тип лифта','Пассажирский'],['Тип шахты','Глухая'],['Материал шахты','Железобетон']] as [$label, $value]) {
        $check($passportFact($label, $value), 'passport pair ' . $label . '=' . $value);
    }
    $check($passport !== null && $xpath->query('.//dl', $passport)->length === 1, 'passport characteristics share one definition list');
    $liftTypeRow = $passport === null ? null : $xpath->query('.//div[contains(concat(" ",normalize-space(@class)," ")," fm2-fact ")][dt[normalize-space()="Тип лифта"] and preceding-sibling::div]', $passport);
    $check($liftTypeRow !== null && $liftTypeRow->length === 1, 'lift type uses ordinary divided fact row');
    $check(!str_contains($passportText, 'Очередность'), 'passport excludes work sequence');
    foreach (['>40<','>41<','>44<','>39<'] as $rawCode) $check(!str_contains($body, $rawCode), 'technical raw code hidden ' . $rawCode);
    $workspaceNode = $xpath->query('//*[contains(concat(" ",normalize-space(@class)," ")," fm2-object-workspace ")]')->item(0);
    $check($workspaceNode !== null, 'workspace exists');
    $workspaceAction = $workspaceNode === null ? null : $xpath->query('.//*[contains(concat(" ",normalize-space(@class)," ")," fm2-next-action ")]', $workspaceNode);
    $check($workspaceAction !== null && $workspaceAction->length === 1, 'primary action belongs to workspace');
    $bareWorkspaceActions = $workspaceNode === null ? null : $xpath->query('.//a[contains(concat(" ",normalize-space(@class)," ")," shlz-link ")]', $workspaceNode);
    $check($bareWorkspaceActions !== null && $bareWorkspaceActions->length === 0, 'workspace has no bare action links');
    $crewAction = $workspaceNode === null ? null : $xpath->query('.//a[contains(concat(" ",normalize-space(@class)," ")," shlz-button ") and normalize-space()="Изменить состав бригады" and contains(@href,"/assignment-order/selection")]', $workspaceNode);
    $check($crewAction !== null && $crewAction->length === 1, 'crew change is a button with user-facing copy');
    $check(preg_match('~pilot\.css\?v=[a-f0-9]{12}~', $body) === 1, 'pilot CSS cache bust');
    $check(preg_match('~navigation\.js\?v=[a-f0-9]{12}~', $body) === 1, 'navigation JS cache bust');
    $check(preg_match('~fm2-object-installer.*?Монтажник 7001.*?Табельный.*?7001.*?Уволен~s', $body) === 1, 'installer binds fio tab current status');
    $check(preg_match('~shlz-document-row.*?file-pdf-default\.svg.*?Подписанный оригинал\.pdf.*?shlz-document-row__meta.*?href="/pilot/objects/4512/~s', $body) === 1, 'document icon available filename metadata href');
    $documentsPanel = $xpath->query('//*[@id="object-panel-documents"]')->item(0);
    $check($documentsPanel !== null, 'documents panel exists');
    foreach (['История оригинала' => '/originals/history', 'Исправить оригинал' => '/originals/submit'] as $actionLabel => $hrefSuffix) {
        $actions = $documentsPanel === null ? null : $xpath->query(
            './/a[contains(concat(" ",normalize-space(@class)," ")," shlz-button ") and normalize-space()="' . $actionLabel . '" and contains(@href,"' . $hrefSuffix . '")]',
            $documentsPanel,
        );
        $check($actions !== null && $actions->length === 1, 'document secondary action ' . $actionLabel);
    }

    $repeat = $fixture->request('GET', '/pilot/objects/4512', [], $cookies);
    $head = $fixture->request('HEAD', '/pilot/objects/4512', [], $cookies);
    $check($repeat['status'] === 200 && $head['status'] === 200 && $head['body'] === '', 'repeat GET HEAD');
    $check($before === $fixture->facts(), 'HTTP no writes');

    $readerCookies = [];
    $fixture->login($readerCookies, 95);
    $restricted = $fixture->request('GET', '/pilot/objects/4512', [], $readerCookies);
    foreach (['open_confirmed','Изменить состав','Исправить оригинал','Закрепить'] as $control) {
        $check(!str_contains($restricted['body'], $control), 'read-only hides ' . $control);
    }
    $directory = (string) file_get_contents($fixture->root . '/app/YiiRuntime/Views/installers.php');
    $check(str_contains($directory, '>Должность<'), 'directory position');
    foreach (['>Источник<','workforce_source','1С ЗУП'] as $text) $check(!str_contains($directory, $text), 'directory hides ' . $text);
    foreach (['object-card.php','selection.php','original.php','execution.php'] as $file) {
        $source = (string) file_get_contents($fixture->root . '/app/YiiRuntime/Views/' . $file);
        foreach (['position','department','provenance'] as $field) $check(!str_contains($source, "['{$field}']"), $file . ' no ' . $field);
    }

    $browserConfig = $fixture->artifacts . '/object-card-browser.json';
    file_put_contents($browserConfig, json_encode([
        'origin' => 'http://127.0.0.1:' . $fixture->server['port'],
        'email' => $fixture->emails[18],
        'password' => $fixture->password,
        'playwright' => getenv('FMONITOR_TEST_PLAYWRIGHT_MODULE') ?: dirname($fixture->root) . '/shlz-ui/node_modules/playwright',
        'result' => $fixture->artifacts . '/object-card-browser-result.json',
    ], JSON_THROW_ON_ERROR));
    $browserProcess = proc_open(
        [getenv('FMONITOR_TEST_NODE_BINARY') ?: 'node', __DIR__ . '/object_card_presentation_browser.mjs', $browserConfig],
        [0 => ['file','/dev/null','r'], 1 => ['file',$fixture->artifacts . '/browser.log','a'], 2 => ['file',$fixture->artifacts . '/browser.log','a']],
        $pipes,
        $fixture->root,
    );
    if (!is_resource($browserProcess)) throw new TestFailure('SETUP_FAILURE browser');
    $deadline = microtime(true) + 90;
    do { $state = proc_get_status($browserProcess); if (!$state['running']) break; usleep(20000); } while (microtime(true) < $deadline);
    if ($state['running']) throw new TestFailure('SETUP_FAILURE browser timeout');
    proc_close($browserProcess); $browserProcess = null;
    $browser = json_decode((string) file_get_contents($fixture->artifacts . '/object-card-browser-result.json'), true, flags: JSON_THROW_ON_ERROR);
    foreach ($browser['failures'] as $item) $failures[] = 'browser ' . $item;
    $check($before === $fixture->facts(), 'browser no writes');
    if ($failures) throw new TestFailure('INTENDED_RED object card presentation: ' . implode(' | ', $failures));
    echo "PASS: YII2-OBJECT-CARD-PRESENTATION-001 HTTP/browser/no-write\n";
} finally {
    if (is_resource($browserProcess)) { proc_terminate($browserProcess, 9); proc_close($browserProcess); }
    if ($browserConfig && is_file($browserConfig)) unlink($browserConfig);
    if ($fixture instanceof PreopeningFixture) $fixture->close();
}

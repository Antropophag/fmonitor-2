<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/PilotHttp/AccessPolicy.php';
require_once dirname(__DIR__, 2) . '/app/PilotHttp/HttpUser.php';
require_once dirname(__DIR__, 2) . '/app/PilotHttp/PilotView.php';

// Specification: PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001 v1.
// Approved public representation seam: configured shared pilot navigation DOM.

use FMonitor2\PilotHttp\AccessPolicy;
use FMonitor2\PilotHttp\HttpUser;
use FMonitor2\PilotHttp\PilotView;

function pwnDom(string $html, string $why): DOMXPath
{
    $document = new DOMDocument();
    $old = libxml_use_internal_errors(true);
    $ok = $document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    libxml_use_internal_errors($old);
    assertSameValue(true, $ok, $why . ' parses');
    return new DOMXPath($document);
}

function pwnNavigation(DOMXPath $xpath, string $why): void
{
    $nav = "//nav[@aria-label='Основная навигация']";
    assertSameValue(1, (int) $xpath->evaluate("count($nav)"), $why . ' one primary navigation');
    assertSameValue(0, (int) $xpath->evaluate("count($nav//*[normalize-space(.)='Моя работа'])"), $why . ' no visible or hidden work label');
    assertSameValue(0, (int) $xpath->evaluate("count($nav//*[@aria-label='Моя работа' or @title='Моя работа'] | $nav//img[@alt='Моя работа'])"), $why . ' no direct accessible work label');
    foreach ($xpath->query($nav . '//*[@aria-labelledby]') as $node) {
        $label = '';
        foreach (preg_split('/\s+/', trim($node->getAttribute('aria-labelledby'))) ?: [] as $id) {
            if ($id === '') {
                continue;
            }
            $matches = $xpath->query('//*[@id=' . pwnXpathLiteral($id) . ']');
            if ($matches->length === 1) {
                $label .= ' ' . $matches->item(0)->textContent;
            }
        }
        assertSameValue(false, trim((string) preg_replace('/\s+/u', ' ', $label)) === 'Моя работа', $why . ' no referenced accessible work label');
    }
    assertSameValue(0, (int) $xpath->evaluate("count($nav//a[@href='/pilot/' or @href='/pilot'])"), $why . ' no root navigation destination');
    assertSameValue(0, (int) $xpath->evaluate("count($nav//*[@aria-current and (@href='/pilot/' or @href='/pilot' or normalize-space(.)='Моя работа')] | $nav//*[@aria-disabled='true' and (@data-route='/pilot/' or @data-href='/pilot/' or @data-destination='/pilot/')])"), $why . ' no root current or disabled substitute');
}

function pwnXpathLiteral(string $value): string
{
    if (!str_contains($value, "'")) {
        return "'" . $value . "'";
    }
    throw new TestFailure('fixture aria-labelledby id contains apostrophe');
}

function pwnItems(DOMXPath $xpath): array
{
    $nodes = $xpath->query("//nav[@aria-label='Основная навигация']/*");
    $items = [];
    foreach ($nodes as $node) {
        $items[] = [
            trim((string) preg_replace('/\s+/u', ' ', $node->textContent)),
            $node instanceof DOMElement ? $node->getAttribute('href') : '',
            $node instanceof DOMElement ? $node->getAttribute('aria-current') : '',
            $node instanceof DOMElement ? $node->getAttribute('aria-disabled') : '',
        ];
    }
    return $items;
}

$minimal = new HttpUser(18, 'Сотрудник ФКР (тест)', 'fkr@example.invalid', [AccessPolicy::OBJECTS_READ]);
$broad = new HttpUser(19, 'Широкий тестовый доступ', 'broad@example.invalid', [
    AccessPolicy::OBJECTS_READ,
    AccessPolicy::CONSTRUCTION_CONTROL_READ,
    AccessPolicy::INSTALLERS_READ,
    AccessPolicy::ADMINISTER_ACCESS,
]);

$representations = [
    ['/pilot/', $minimal, 'Моя работа'],
    ['/pilot/objects', $minimal, 'Объекты монтажа'],
    ['/pilot/objects/4512', $minimal, 'Объекты монтажа'],
    ['/pilot/objects/4512/assignment-order/prepare', $minimal, 'Объекты монтажа'],
    ['/pilot/objects/4512/checklist', $minimal, 'Объекты монтажа'],
    ['/pilot/construction-control', $broad, 'Стройконтроль'],
    ['/pilot/construction-control/objects/4512/checklist', $broad, 'Стройконтроль'],
    ['/pilot/installers', $broad, 'Монтажники'],
    ['/pilot/admin/users', $broad, 'Пользователи'],
    ['/pilot/admin/roles', $broad, 'Роли'],
];

foreach ($representations as [$route, $actor, $current]) {
    $content = '<h1>sentinel ' . htmlspecialchars($route, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h1>';
    $first = PilotView::document($actor, 'Sentinel', $current, '', $content);
    $repeat = PilotView::document($actor, 'Sentinel', $current, '', $content);
    assertSameValue($first, $repeat, $route . ' repeat representation');
    assertSameValue(true, str_contains($first, $content), $route . ' content bytes preserved');
    $xpath = pwnDom($first, $route);
    pwnNavigation($xpath, $route);
}

$minimalObjects = pwnDom(PilotView::document($minimal, 'Sentinel', 'Объекты монтажа', '', '<h1>sentinel</h1>'), 'minimal siblings');
assertSameValue([
    ['Работа', '', '', ''],
    ['Объекты монтажа', '/pilot/objects', 'page', ''],
    ['Распоряжения', '', '', 'true'],
    ['Управление', '', '', ''],
    ['Расчёты ОТиЗ', '', '', 'true'],
    ['Контроль', '', '', 'true'],
], pwnItems($minimalObjects), 'minimal actor exact remaining sibling sequence and states');

$broadUsers = pwnDom(PilotView::document($broad, 'Sentinel', 'Пользователи', '', '<h1>sentinel</h1>'), 'broad siblings');
assertSameValue([
    ['Работа', '', '', ''],
    ['Стройконтроль', '/pilot/construction-control', '', ''],
    ['Объекты монтажа', '/pilot/objects', '', ''],
    ['Распоряжения', '', '', 'true'],
    ['Справочники', '', '', ''],
    ['Монтажники', '/pilot/installers', '', ''],
    ['Управление', '', '', ''],
    ['Расчёты ОТиЗ', '', '', 'true'],
    ['Контроль', '', '', 'true'],
    ['Администрирование', '', '', ''],
    ['Пользователи', '/pilot/admin/users', 'page', ''],
    ['Роли', '/pilot/admin/roles', '', ''],
], pwnItems($broadUsers), 'broad actor exact remaining sibling sequence and states');

echo "PASS: PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001 configured shared navigation\n";

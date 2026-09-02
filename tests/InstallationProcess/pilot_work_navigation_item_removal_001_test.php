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
            hash('sha256', $node->ownerDocument->saveHTML($node)),
        ];
    }
    return $items;
}

function pwnHashes(DOMXPath $xpath): array
{
    $hashes = [];
    foreach ($xpath->query("//nav[@aria-label='Основная навигация']/*") as $node) {
        if (trim((string) preg_replace('/\s+/u', ' ', $node->textContent)) === 'Моя работа') continue;
        $hashes[] = hash('sha256', $node->ownerDocument->saveHTML($node));
    }
    return $hashes;
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

$minimalRootHashes=['e8f55f37cc37cc7faac43c7b19ca30b6e2270301dd0a06ea9ac809e730439787','4f2fcf9f64280c9bb5d76d594acb6130c54d50505fefb73c122ac32e5caec962','f144121bf33ec44811826a24f960d1ba1a3e24b14d824a0b7d3f5b21432ca9dd','c5ee6105bcc0b88abfafe671770436335d6b504774cfc5cc417ffaa56186c9a8','629720a8c164bb29a1869821e51d7236a8479e8981713cc1b22389b2582db9de','5126c31fba27062f6a75cd6785133de83df6c02d1d72cd0df78df20641bd179d'];
$minimalObjectsHashes=['e8f55f37cc37cc7faac43c7b19ca30b6e2270301dd0a06ea9ac809e730439787','89e38a8db8c6747a57e4021b24e78c41b60fcb0fa8db0eb78931ef8d78076208','f144121bf33ec44811826a24f960d1ba1a3e24b14d824a0b7d3f5b21432ca9dd','c5ee6105bcc0b88abfafe671770436335d6b504774cfc5cc417ffaa56186c9a8','629720a8c164bb29a1869821e51d7236a8479e8981713cc1b22389b2582db9de','5126c31fba27062f6a75cd6785133de83df6c02d1d72cd0df78df20641bd179d'];
$broadBase=['e8f55f37cc37cc7faac43c7b19ca30b6e2270301dd0a06ea9ac809e730439787','eb79d631ff128ff7b1a17f49fc160ecc99b4fb524e9b1961ad938fc1e768ec9a','4f2fcf9f64280c9bb5d76d594acb6130c54d50505fefb73c122ac32e5caec962','f144121bf33ec44811826a24f960d1ba1a3e24b14d824a0b7d3f5b21432ca9dd','4ce8192b5d0184a93152489995824a9a46e0b116183f57e8328fff61d0996bfa','9a162177348ef532945d88a7a1af1237a47978f6bfd32d0b15b1af50183e9ca4','c5ee6105bcc0b88abfafe671770436335d6b504774cfc5cc417ffaa56186c9a8','629720a8c164bb29a1869821e51d7236a8479e8981713cc1b22389b2582db9de','5126c31fba27062f6a75cd6785133de83df6c02d1d72cd0df78df20641bd179d','89d36a6a96926e8c9b080f737a5e4b54b5ea598670f46bb1ea4b53c74685258d','d8fab7842bc224746144ef2e02c510210c15c6be8127c48e7b7c617f1a434ea9','59413584d40f53f78f27c7f813bd7532df457cab4755a061d04632117e3356ce'];
$broadCurrent=['/pilot/construction-control'=>[1,'314607c3cfab965097d2a2deda1285183b34be662c85ef9014bc0dc3ed9e03d8'],'/pilot/construction-control/objects/4512/checklist'=>[1,'314607c3cfab965097d2a2deda1285183b34be662c85ef9014bc0dc3ed9e03d8'],'/pilot/installers'=>[5,'3cc4f756aaa38a0f73a8fd725ee562d527b3228697f737fdc5c1608a45104977'],'/pilot/admin/users'=>[10,'fdd196bbaf8c1745f9111beeb4eceb36204b31a3e2b3a5f0a3915fbfb64142c5'],'/pilot/admin/roles'=>[11,'f49a024ea2648138c2f7d8545e0143916fcaf00c5e6579232806a715a4961316']];
$expectedHashes=['/pilot/'=>$minimalRootHashes,'/pilot/objects'=>$minimalObjectsHashes,'/pilot/objects/4512'=>$minimalObjectsHashes,'/pilot/objects/4512/assignment-order/prepare'=>$minimalObjectsHashes,'/pilot/objects/4512/checklist'=>$minimalObjectsHashes];
foreach($broadCurrent as$route=>[$index,$hash]){$values=$broadBase;$values[$index]=$hash;$expectedHashes[$route]=$values;}

foreach ($representations as [$route, $actor, $current]) {
    $content = '<h1>sentinel ' . htmlspecialchars($route, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h1>';
    $first = PilotView::document($actor, 'Sentinel', $current, '', $content);
    $repeat = PilotView::document($actor, 'Sentinel', $current, '', $content);
    assertSameValue($first, $repeat, $route . ' repeat representation');
    assertSameValue(true, str_contains($first, $content), $route . ' content bytes preserved');
    $xpath = pwnDom($first, $route);
    pwnNavigation($xpath, $route);
    assertSameValue($expectedHashes[$route], pwnHashes($xpath), $route . ' exact remaining child/icon/accessibility bytes');
}

$minimalObjects = pwnDom(PilotView::document($minimal, 'Sentinel', 'Объекты монтажа', '', '<h1>sentinel</h1>'), 'minimal siblings');
assertSameValue([
    ['Работа', '', '', '', 'e8f55f37cc37cc7faac43c7b19ca30b6e2270301dd0a06ea9ac809e730439787'],
    ['Объекты монтажа', '/pilot/objects', 'page', '', '89e38a8db8c6747a57e4021b24e78c41b60fcb0fa8db0eb78931ef8d78076208'],
    ['Распоряжения', '', '', 'true', 'f144121bf33ec44811826a24f960d1ba1a3e24b14d824a0b7d3f5b21432ca9dd'],
    ['Управление', '', '', '', 'c5ee6105bcc0b88abfafe671770436335d6b504774cfc5cc417ffaa56186c9a8'],
    ['Расчёты ОТиЗ', '', '', 'true', '629720a8c164bb29a1869821e51d7236a8479e8981713cc1b22389b2582db9de'],
    ['Контроль', '', '', 'true', '5126c31fba27062f6a75cd6785133de83df6c02d1d72cd0df78df20641bd179d'],
], pwnItems($minimalObjects), 'minimal actor exact remaining sibling sequence and states');

$broadUsers = pwnDom(PilotView::document($broad, 'Sentinel', 'Пользователи', '', '<h1>sentinel</h1>'), 'broad siblings');
assertSameValue([
    ['Работа', '', '', '', 'e8f55f37cc37cc7faac43c7b19ca30b6e2270301dd0a06ea9ac809e730439787'],
    ['Стройконтроль', '/pilot/construction-control', '', '', 'eb79d631ff128ff7b1a17f49fc160ecc99b4fb524e9b1961ad938fc1e768ec9a'],
    ['Объекты монтажа', '/pilot/objects', '', '', '4f2fcf9f64280c9bb5d76d594acb6130c54d50505fefb73c122ac32e5caec962'],
    ['Распоряжения', '', '', 'true', 'f144121bf33ec44811826a24f960d1ba1a3e24b14d824a0b7d3f5b21432ca9dd'],
    ['Справочники', '', '', '', '4ce8192b5d0184a93152489995824a9a46e0b116183f57e8328fff61d0996bfa'],
    ['Монтажники', '/pilot/installers', '', '', '9a162177348ef532945d88a7a1af1237a47978f6bfd32d0b15b1af50183e9ca4'],
    ['Управление', '', '', '', 'c5ee6105bcc0b88abfafe671770436335d6b504774cfc5cc417ffaa56186c9a8'],
    ['Расчёты ОТиЗ', '', '', 'true', '629720a8c164bb29a1869821e51d7236a8479e8981713cc1b22389b2582db9de'],
    ['Контроль', '', '', 'true', '5126c31fba27062f6a75cd6785133de83df6c02d1d72cd0df78df20641bd179d'],
    ['Администрирование', '', '', '', '89d36a6a96926e8c9b080f737a5e4b54b5ea598670f46bb1ea4b53c74685258d'],
    ['Пользователи', '/pilot/admin/users', 'page', '', 'fdd196bbaf8c1745f9111beeb4eceb36204b31a3e2b3a5f0a3915fbfb64142c5'],
    ['Роли', '/pilot/admin/roles', '', '', '59413584d40f53f78f27c7f813bd7532df457cab4755a061d04632117e3356ce'],
], pwnItems($broadUsers), 'broad actor exact remaining sibling sequence and states');

echo "PASS: PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001 configured shared navigation\n";

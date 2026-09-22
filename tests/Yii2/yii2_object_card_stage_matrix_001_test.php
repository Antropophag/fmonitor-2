<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/DocumentaryFixture.php';

$f = null;
$failures = [];
$check = static function (mixed $expected, mixed $actual, string $message) use (&$failures): void {
    if ($expected !== $actual) $failures[] = $message . ' expected=' . var_export($expected, true) . ' actual=' . var_export($actual, true);
};
try {
    $f = new DocumentaryFixture(dirname(__DIR__, 2));
    $f->open(); $h = $f->http;
    $assertAction = static function (array $page, string $heading, ?string $command, array $forbidden = []) use ($check): void {
        $check(200, $page['status'], 'stage card status');
        $check(1, substr_count($page['body'], 'class="fm2-next-action"'), 'one primary action ' . $heading);
        preg_match('~<section class="fm2-next-action".*?</section>~s', $page['body'], $match);
        $action = $match[0] ?? '';
        $check(true, str_contains($action, $heading), 'stage heading ' . $heading);
        if ($command !== null) $check(true, str_contains($action, $command), 'stage command ' . $command);
        foreach ($forbidden as $text) $check(false, str_contains($action, $text), 'forbidden competing command ' . $text);
    };
    $before = $h->facts();
    $assertAction($f->page(), 'Монтажные работы', 'Перейти к чек-листу', ['Открыть работы', 'Работы завершены']);
    $check($before, $h->facts(), 'working read-only');
    $workingReader = []; $h->login($workingReader, 95);

    $h->db->query("UPDATE {$h->p}fm2_installation_cases SET process_state='needs_assignment_change' WHERE id=6101");
    $assertAction($f->page(), 'Требуется изменение', null, ['Перейти к чек-листу', 'Продолжить монтажные работы']);
    $h->db->query("UPDATE {$h->p}fm2_installation_cases SET process_state='working' WHERE id=6101");

    $f->progress();
    $assertAction($f->page(), 'Требуется акт ПТО', 'Перейти к акту ПТО', ['Перейти к чек-листу', 'Перейти к декларации']);
    $reader = $workingReader;
    $readerPto = $f->page($reader);
    $assertAction($readerPto, 'Требуется акт ПТО', null, ['Перейти к акту ПТО']);

    DocumentaryFixture::accepted($f->post('record_pto', ['ptoActDate' => '2026-09-05']));
    $assertAction($f->page(), 'Требуется декларация', 'Перейти к декларации', ['Перейти к акту ПТО', 'Перейти к чек-листу']);
    $readerDeclaration = $f->page($reader);
    $assertAction($readerDeclaration, 'Требуется декларация', null, ['Перейти к декларации']);

    DocumentaryFixture::accepted($f->post('record_declaration', ['declarationDate' => '2026-09-06', 'declarationDetails' => 'Д-001']));
    $assertAction($f->page(), 'Работы завершены', null, ['Перейти к чек-листу', 'Перейти к акту ПТО', 'Перейти к декларации', 'Открыть работы']);
    $head = $h->request('HEAD', '/pilot/objects/4512', [], $f->cookies);
    $check([200, ''], [$head['status'], $head['body']], 'completed HEAD');
    $h->noLegacy();
    if ($failures) throw new TestFailure('INTENDED_RED stage matrix: ' . implode(' | ', $failures));
    echo "PASS: YII2-OBJECT-CARD-STAGE-ACTIONS-001 executable stage matrix\n";
} finally { if ($f instanceof DocumentaryFixture) $f->close(); }

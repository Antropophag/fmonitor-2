<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/PreopeningFixture.php';

// YII2-OBJECT-CARD-STAGE-ACTIONS-001: public card facts and next action.
$fixture = null;
$failures = [];
$check = static function (mixed $expected, mixed $actual, string $message) use (&$failures): void {
    if ($expected !== $actual) $failures[] = $message . ' expected=' . var_export($expected, true) . ' actual=' . var_export($actual, true);
};
try {
    $fixture = new PreopeningFixture(dirname(__DIR__, 2));
    $fixture->start();
    $manager = [];
    $check(303, $fixture->login($manager, 97)['status'], 'manager login');
    $empty = $fixture->request('GET', '/pilot/objects/4512', [], $manager);
    foreach (['Требуется распоряжение', 'Выбрать состав'] as $text) $check(true, str_contains($empty['body'], $text), 'no-selection action ' . $text);
    $check(1, substr_count($empty['body'], 'class="fm2-next-action"'), 'no-selection one action');
    $emptyReaderCookies = [];
    $check(303, $fixture->login($emptyReaderCookies, 95)['status'], 'empty reader login');
    $emptyReader = $fixture->request('GET', '/pilot/objects/4512', [], $emptyReaderCookies);
    $check(true, str_contains($emptyReader['body'], 'Требуется распоряжение'), 'restricted no-selection state');
    $check(false, str_contains($emptyReader['body'], 'Выбрать состав') || str_contains($emptyReader['body'], '/assignment-order/prepare'), 'restricted no-selection command absent');
    $check(1, substr_count($emptyReader['body'], 'class="fm2-next-action"'), 'restricted no-selection one block');
    $check(303, $fixture->selection($manager)['status'], 'persist pending composition');
    $check([], $fixture->rows('fm2_assignment_order_original_revisions'), 'no original before card read');
    $check([], $fixture->rows('fm2_assignment_order_applications'), 'no application before card read');

    $before = $fixture->facts();
    foreach (['GET', 'HEAD', 'GET'] as $method) {
        $card = $fixture->request($method, '/pilot/objects/4512', [], $manager);
        $check(200, $card['status'], 'pending card ' . $method);
        if ($method === 'HEAD') {
            $check('', $card['body'], 'HEAD body');
            continue;
        }
        $body = $card['body'];
        foreach (['Состав сохранён. Ожидается подписанный оригинал', 'Монтажник 7001', 'Загрузить подписанный оригинал', 'Загрузить оригинал', 'Подписанный оригинал ожидается'] as $text) {
            $check(true, str_contains($body, $text), 'pending copy ' . $text);
        }
        $check(1, substr_count($body, 'class="fm2-next-action"'), 'one primary action');
        $check(true, str_contains($body, '/pilot/objects/4512/assignment-orders/81/originals/submit'), 'exact pending upload href');
        foreach (['Состав ещё не выбран', 'Подписанный оригинал.pdf', 'Открыть работы', 'name="action" value="open_confirmed"'] as $forbidden) {
            $check(false, str_contains($body, $forbidden), 'pending card excludes ' . $forbidden);
        }
    }
    $check($before, $fixture->facts(), 'repeated card reads create no facts');

    $reader = [];
    $check(303, $fixture->login($reader, 95)['status'], 'reader login');
    $restricted = $fixture->request('GET', '/pilot/objects/4512', [], $reader);
    $check(200, $restricted['status'], 'reader sees pending state');
    foreach (['Состав сохранён. Ожидается подписанный оригинал', 'Монтажник 7001', 'Подписанный оригинал ожидается'] as $text) {
        $check(true, str_contains($restricted['body'], $text), 'reader state ' . $text);
    }
    foreach (['Загрузить оригинал', '/assignment-orders/81/originals/submit', 'Открыть работы'] as $forbidden) {
        $check(false, str_contains($restricted['body'], $forbidden), 'reader has no command ' . $forbidden);
    }
    $check(1, substr_count($restricted['body'], 'class="fm2-next-action"'), 'reader one informative primary block');

    $fixture->nativeOriginal('2026-09-01');
    $ready = $fixture->request('GET', '/pilot/objects/4512', [], $manager);
    $check(200, $ready['status'], 'accepted original card');
    foreach (['Готов к открытию', 'Открыть монтажные работы', 'name="action" value="open_confirmed"', 'Подписанный оригинал.pdf'] as $text) {
        $check(true, str_contains($ready['body'], $text), 'ready behavior preserved ' . $text);
    }
    foreach (['Состав сохранён. Ожидается подписанный оригинал', 'Подписанный оригинал ожидается'] as $text) {
        $check(false, str_contains($ready['body'], $text), 'ready excludes pending copy ' . $text);
    }
    $readyReader = $fixture->request('GET', '/pilot/objects/4512', [], $reader);
    $check(true, str_contains($readyReader['body'], 'Открыть монтажные работы'), 'restricted opening state visible');
    $check(false, str_contains($readyReader['body'], 'name="action" value="open_confirmed"') || str_contains($readyReader['body'], 'Открыть работы</button>'), 'restricted opening command absent');
    $check(1, substr_count($readyReader['body'], 'class="fm2-next-action"'), 'restricted opening one block');

    $revision = $fixture->rows('fm2_assignment_order_original_revisions')[0]['revision_id'];
    $open = [
        '_csrf' => $fixture->csrf($ready['body']), 'action' => 'open_confirmed',
        'requestId' => '33333333-3333-4333-8333-000000000001', 'orderId' => '81',
        'revisionId' => $revision, 'sequence' => '0', 'actualStartDate' => '2026-09-02',
    ];
    $check(303, $fixture->form('/pilot/objects/4512/execution', $open, $manager)['status'], 'open existing confirmed original');
    $check(303, $fixture->selection($manager, '44444444-4444-4444-8444-000000000001', [7002], 'new_order', 1)['status'], 'prepare later order while work is open');
    $duringBefore = $fixture->facts();
    $duringWork = $fixture->request('GET', '/pilot/objects/4512', [], $manager);
    $check(200, $duringWork['status'], 'opened card with pending replacement');
    foreach (['Действующая бригада', 'Монтажник 7001', 'Ожидающий состав', 'Монтажник 7002'] as $text) {
        $check(true, str_contains($duringWork['body'], $text), 'applied and pending are separate ' . $text);
    }
    $check(true, str_contains($duringWork['body'], '/assignment-orders/82/originals/submit'), 'later pending order upload href');
    $check(false, str_contains($duringWork['body'], '/assignment-orders/81/originals/submit">Загрузить оригинал'), 'applied order is not pending upload target');
    $check(true, str_contains($duringWork['body'], 'Монтажные работы'), 'opened pending keeps working stage');
    $check(true, str_contains($duringWork['body'], 'Перейти к чек-листу'), 'opened pending keeps checklist primary action');
    $check(1, substr_count($duringWork['body'], 'class="fm2-next-action"'), 'opened pending one primary block');
    $check(true, str_contains($duringWork['body'], 'Подписанный оригинал.pdf'), 'applied original document remains visible');
    $check(false, str_contains($duringWork['body'], 'Открыть работы'), 'opened pending never reopens work');
    $check($duringBefore, $fixture->facts(), 'opened pending read creates no facts');

    $fixture->noLegacy();
    if ($failures) throw new TestFailure('INTENDED_RED stage actions: ' . implode(' | ', $failures));
    echo "PASS: YII2-OBJECT-CARD-STAGE-ACTIONS-001 pending/rights/ready/stage matrix\n";
} finally {
    if ($fixture instanceof PreopeningFixture) $fixture->close();
}

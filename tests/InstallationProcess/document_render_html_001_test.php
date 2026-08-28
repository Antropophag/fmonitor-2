<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/InMemoryInstallationProcessEnvironment.php';

use FMonitor2\InstallationProcess\InstallationProcess;
use FMonitor2\InstallationProcess\ProductionHtmlAssignmentOrderRenderer;
use FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment;

// Specification: DOCUMENT-RENDER-HTML-001 v0.2.

$expectedOrderBytes = <<<'HTML'
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title>Проект распоряжения</title>
<style>@page{size:A4;margin:18mm}body{font:14px/1.4 Arial,sans-serif;color:#111}h1{font-size:20px;margin:0 0 18px}dl{display:grid;grid-template-columns:190px 1fr;gap:8px 16px}dt{font-weight:700}dd{margin:0}@media print{body{print-color-adjust:exact}}</style>
</head>
<body>
<main>
<h1>Проект распоряжения</h1>
<dl>
<dt>Статус документа</dt><dd>Проект</dd>
<dt>Дата распоряжения</dt><dd>27.08.2026</dd>
<dt>Адрес объекта</dt><dd>Москва, ул. Примерная, д. 10</dd>
<dt>Подъезд/секция</dt><dd>2</dd>
<dt>Регистрационный номер объекта</dt><dd>77-000123</dd>
<dt>Форма организации труда</dt><dd>Индивидуальная</dd>
<dt>Инженер строительного контроля</dt><dd>Петров Пётр Петрович — Инженер строительного контроля</dd>
</dl>
</main>
</body>
</html>
HTML;
$expectedOrderBytes .= "\n";

$expectedAppendixBytes = <<<'HTML'
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title>Приложение к проекту распоряжения</title>
<style>@page{size:A4 landscape;margin:14mm}body{font:12px/1.35 Arial,sans-serif;color:#111}h1{font-size:18px;margin:0 0 14px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #333;padding:6px;vertical-align:top;text-align:left}th{font-weight:700;background:#eee}@media print{body{print-color-adjust:exact}}</style>
</head>
<body>
<main>
<h1>Приложение к проекту распоряжения</h1>
<table>
<thead><tr><th>Объект</th><th>Плановые даты</th><th>Монтажник</th><th>Кадровый факт</th><th>Инженер строительного контроля</th></tr></thead>
<tbody><tr><td>Москва, ул. Примерная, д. 10; подъезд/секция 2; рег. номер 77-000123</td><td>05.10.2026–20.12.2026</td><td>1042 — Иванов Иван Иванович — Электромеханик по лифтам</td><td>employed; источник one_c_zup_via_bitrix</td><td>Петров Пётр Петрович — Инженер строительного контроля</td></tr></tbody>
</table>
</main>
</body>
</html>
HTML;
$expectedAppendixBytes .= "\n";

$base = new InMemoryInstallationProcessEnvironment();
$base->allowPreparationBy(18);
$base->setNow('2026-08-26T21:30:00+00:00');
$base->seedInstallationObjectProcess(4512, [
    'installationObjectId' => 4512,
    'processState' => 'needs_assignment_order',
    'assignmentOrders' => [],
    'assignments' => [],
    'openTasks' => [['type' => 'prepare_assignment_order', 'assigneeRole' => 'fkr']],
    'installationOpened' => false,
    'checklistAvailable' => false,
    'events' => [],
]);
$base->seedInstallationObjectSnapshot(4512, ['address' => 'Москва, ул. Примерная, д. 10', 'entrance' => '2', 'objectRegistrationNumber' => '77-000123', 'plannedStartDate' => '2026-10-05', 'plannedFinishDate' => '2026-12-20', 'ptoActDate' => null]);
$base->seedInstallerSnapshot(1042, ['tabId' => 1042, 'fullName' => 'Иванов Иван Иванович', 'position' => 'Электромеханик по лифтам', 'status' => 'employed', 'employedFrom' => '2024-02-01', 'employedTo' => null, 'source' => 'one_c_zup_via_bitrix', 'sourceUpdatedAt' => '2026-08-26T18:00:00+03:00']);
$base->seedEngineerSnapshot(73, ['userId' => 73, 'fullName' => 'Петров Пётр Петрович', 'position' => 'Инженер строительного контроля', 'active' => true, 'role' => 'construction_control_engineer']);

$renderer = new ProductionHtmlAssignmentOrderRenderer();
$environment = new class($base, $renderer) {
    /** @var list<array<string, mixed>> */
    public array $returnedArtifacts = [];
    public function __construct(private readonly InMemoryInstallationProcessEnvironment $base, private readonly ProductionHtmlAssignmentOrderRenderer $renderer) {}
    public function renderAssignmentOrder(array $input): array
    {
        return $this->returnedArtifacts = $this->renderer->renderAssignmentOrder($input);
    }
    public function __call(string $name, array $arguments): mixed { return $this->base->{$name}(...$arguments); }
};

$process = new InstallationProcess($environment);
assertSameValue([
    'accepted' => true,
    'assignmentOrderVersion' => 1,
    'status' => 'prepared',
    'assignmentOrderDate' => '2026-08-27',
    'organizationType' => 'individual',
], $process->prepareAssignmentOrder(4512, [1042], 73, 18), 'Production HTML renderer must support public preparation.');

assertSameValue([
    ['type' => 'order', 'filename' => 'assignment-order-v1.html', 'mediaType' => 'text/html', 'bytes' => $expectedOrderBytes],
    ['type' => 'appendix', 'filename' => 'assignment-order-v1-appendix.html', 'mediaType' => 'text/html', 'bytes' => $expectedAppendixBytes],
], $environment->returnedArtifacts, 'Renderer boundary must return the two exact self-contained escaped UTF-8 HTML byte strings.');

foreach ($environment->returnedArtifacts as $artifact) {
    assertSameValue(false, preg_match('/<script\b|https?:\/\/|<link\b|<iframe\b|<form\b|\son[a-z]+\s*=/i', $artifact['bytes']) === 1, 'Rendered HTML must contain no script, remote resource, form, iframe or executable event attribute.');
}

$projection = $process->getInstallationObjectProcess(4512);
assertSameValue([
    'installationObjectId' => 4512,
    'processState' => 'assignment_order_prepared',
    'assignmentOrders' => [[
        'version' => 1,
        'status' => 'prepared',
        'registrationNumber' => null,
        'assignmentOrderDate' => '2026-08-27',
        'organizationType' => 'individual',
        'installationObjectSnapshot' => [
            'address' => 'Москва, ул. Примерная, д. 10',
            'entrance' => '2',
            'objectRegistrationNumber' => '77-000123',
            'plannedStartDate' => '2026-10-05',
            'plannedFinishDate' => '2026-12-20',
            'ptoActDate' => null,
        ],
        'installers' => [[
            'tabId' => 1042,
            'fullName' => 'Иванов Иван Иванович',
            'position' => 'Электромеханик по лифтам',
            'status' => 'employed',
            'employedFrom' => '2024-02-01',
            'employedTo' => null,
            'source' => 'one_c_zup_via_bitrix',
            'sourceUpdatedAt' => '2026-08-26T18:00:00+03:00',
        ]],
        'controlEngineer' => [
            'userId' => 73,
            'fullName' => 'Петров Пётр Петрович',
            'position' => 'Инженер строительного контроля',
            'active' => true,
            'role' => 'construction_control_engineer',
        ],
        'artifacts' => [
            ['type' => 'order', 'filename' => 'assignment-order-v1.html', 'mediaType' => 'text/html', 'size' => 1093, 'sha256' => '682749a063958eb102f5b184c4dfe6c21a009f77932b3b68b3b92e340adf4928'],
            ['type' => 'appendix', 'filename' => 'assignment-order-v1-appendix.html', 'mediaType' => 'text/html', 'size' => 1262, 'sha256' => 'da33d58efd35c6211d850446ee9f159526c9ba779fbdd9355b68ac35806ee3ac'],
        ],
    ]],
    'assignments' => [
        ['role' => 'installer', 'tabId' => 1042, 'assignmentOrderVersion' => 1, 'status' => 'preliminary'],
        ['role' => 'control_engineer', 'userId' => 73, 'assignmentOrderVersion' => 1, 'status' => 'preliminary'],
    ],
    'openTasks' => [],
    'installationOpened' => false,
    'checklistAvailable' => false,
    'events' => [[
        'type' => 'assignment_order_prepared',
        'occurredAt' => '2026-08-26T21:30:00+00:00',
        'actorId' => 18,
        'payload' => [
            'assignmentOrderVersion' => 1,
            'assignmentOrderDate' => '2026-08-27',
            'installerTabIds' => [1042],
            'controlEngineerUserId' => 73,
            'organizationType' => 'individual',
            'artifactSha256' => [
                'order' => '682749a063958eb102f5b184c4dfe6c21a009f77932b3b68b3b92e340adf4928',
                'appendix' => 'da33d58efd35c6211d850446ee9f159526c9ba779fbdd9355b68ac35806ee3ac',
            ],
        ],
    ]],
], $projection, 'Public projection must preserve every inherited process fact and expose exactly one approved preparation event.');

$validDocumentInput = [
    'assignmentOrderVersion' => 1,
    'assignmentOrderDate' => '2026-08-27',
    'organizationType' => 'individual',
    'installationObjectSnapshot' => [
        'address' => 'Москва, ул. Примерная, д. 10',
        'entrance' => '2',
        'objectRegistrationNumber' => '77-000123',
        'plannedStartDate' => '2026-10-05',
        'plannedFinishDate' => '2026-12-20',
    ],
    'installers' => [[
        'tabId' => 1042,
        'fullName' => 'Иванов Иван Иванович',
        'position' => 'Электромеханик по лифтам',
        'status' => 'employed',
        'source' => 'one_c_zup_via_bitrix',
    ]],
    'controlEngineer' => [
        'userId' => 73,
        'fullName' => 'Петров Пётр Петрович',
        'position' => 'Инженер строительного контроля',
    ],
];

$invalidDocuments = [
    'zero version' => static function (array &$input): void { $input['assignmentOrderVersion'] = 0; },
    'path-like string version' => static function (array &$input): void { $input['assignmentOrderVersion'] = '../1'; },
    'missing version' => static function (array &$input): void { unset($input['assignmentOrderVersion']); },
    'impossible order date' => static function (array &$input): void { $input['assignmentOrderDate'] = '2026-02-31'; },
    'non-string order date' => static function (array &$input): void { $input['assignmentOrderDate'] = 20260827; },
    'unsupported brigade' => static function (array &$input): void { $input['organizationType'] = 'brigade'; },
    'missing object snapshot' => static function (array &$input): void { unset($input['installationObjectSnapshot']); },
    'non-array object snapshot' => static function (array &$input): void { $input['installationObjectSnapshot'] = 'object'; },
    'missing object address' => static function (array &$input): void { unset($input['installationObjectSnapshot']['address']); },
    'blank object entrance' => static function (array &$input): void { $input['installationObjectSnapshot']['entrance'] = " \t "; },
    'non-string object registration number' => static function (array &$input): void { $input['installationObjectSnapshot']['objectRegistrationNumber'] = 77000123; },
    'impossible planned start date' => static function (array &$input): void { $input['installationObjectSnapshot']['plannedStartDate'] = '2026-02-31'; },
    'malformed planned finish date' => static function (array &$input): void { $input['installationObjectSnapshot']['plannedFinishDate'] = '20.12.2026'; },
    'missing installers list' => static function (array &$input): void { unset($input['installers']); },
    'non-list installers' => static function (array &$input): void { $input['installers'] = [1 => $input['installers'][0]]; },
    'empty installers list' => static function (array &$input): void { $input['installers'] = []; },
    'brigade-sized installers list' => static function (array &$input): void { $input['installers'][] = $input['installers'][0]; },
    'non-array installer' => static function (array &$input): void { $input['installers'][0] = 'installer'; },
    'non-positive installer tab id' => static function (array &$input): void { $input['installers'][0]['tabId'] = 0; },
    'string installer tab id' => static function (array &$input): void { $input['installers'][0]['tabId'] = '1042'; },
    'blank installer full name' => static function (array &$input): void { $input['installers'][0]['fullName'] = ' '; },
    'missing installer position' => static function (array &$input): void { unset($input['installers'][0]['position']); },
    'non-string installer status' => static function (array &$input): void { $input['installers'][0]['status'] = true; },
    'blank installer source' => static function (array &$input): void { $input['installers'][0]['source'] = "\n"; },
    'missing engineer' => static function (array &$input): void { unset($input['controlEngineer']); },
    'non-array engineer' => static function (array &$input): void { $input['controlEngineer'] = 'engineer'; },
    'non-positive engineer user id' => static function (array &$input): void { $input['controlEngineer']['userId'] = 0; },
    'string engineer user id' => static function (array &$input): void { $input['controlEngineer']['userId'] = '73'; },
    'missing engineer full name' => static function (array &$input): void { unset($input['controlEngineer']['fullName']); },
    'blank engineer position' => static function (array &$input): void { $input['controlEngineer']['position'] = "\t"; },
];

foreach ($invalidDocuments as $case => $corrupt) {
    $documentInput = $validDocumentInput;
    $corrupt($documentInput);
    $returnedArtifacts = null;
    $caught = null;
    try {
        $returnedArtifacts = $renderer->renderAssignmentOrder($documentInput);
    } catch (Throwable $error) {
        $caught = $error;
    }
    assertSameValue(InvalidArgumentException::class, $caught === null ? null : $caught::class, "{$case} must fail with the approved exception type.");
    assertSameValue('Invalid assignment order document input.', $caught?->getMessage(), "{$case} must not disclose the invalid value or renderer details.");
    assertSameValue(null, $returnedArtifacts, "{$case} must expose no empty or partial artifact result.");
}

echo "PASS: DOCUMENT-RENDER-HTML-001 production HTML artifacts\n";

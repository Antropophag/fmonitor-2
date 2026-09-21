<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/EquipmentFactsFixture.php';

use FMonitor2\InstallationProcess\{EquipmentFactsApplication, ErpEquipmentFactsDelivery, ErpEquipmentFactsDeliveryConfig, NativeErpEquipmentFactsDelivery};

$config = new ErpEquipmentFactsDeliveryConfig('erp.invalid', 'not-the-default', 'reader', 'synthetic', 500, 7, 2);
$calls = [];
$transport = static function (string $sql, array $parameters, array $options) use (&$calls): array {
    $calls[] = [$sql, $parameters, $options];
    $normalized = mb_strtolower((string) preg_replace('/\s+/u', ' ', $sql));
    if (str_contains($normalized, 'max(')) {
        return array_map(static fn(string $number): array => [
            'sourceOrderNumber' => $number,
            'readinessDate' => $number === 'A-100' ? '2026-09-01' : null,
            'fullShipmentDate' => null,
        ], $parameters);
    }
    return $parameters === ['A-100', 'B-200']
        ? [['sourceOrderNumber' => 'A-100', 'shipmentDate' => '2026-09-03']]
        : [];
};

$result = (new NativeErpEquipmentFactsDelivery($config, $transport))->fetch([
    ' A-100 ', '0', '', 'B-200', 'A-100', 'C-300', '   ',
]);
assertSameValue('complete', $result['status'] ?? null,
    'INTENDED_RED: source accepts bounded local candidate set instead of global snapshot');
assertSameValue(['A-100', 'B-200', 'C-300'], array_column($result['records'] ?? [], 'sourceOrderNumber'),
    'unique nonzero exact local orders only');
assertSameValue(4, count($calls), 'chunk size two issues two queries per local chunk');

foreach ($calls as [$sql, $parameters, $options]) {
    preg_match_all('/(?:from|join)\s+([^\s]+)/iu', $sql, $tables);
    assertSameValue(true, $tables[1] !== [], 'query references ERP tables');
    foreach ($tables[1] as $table) assertSameValue(true, str_starts_with($table, '[1c-erp].['),
        'every referenced ERP table is fully qualified: ' . $table);
    assertSameValue(substr_count($sql, '?'), count($parameters), 'every local number is a bound parameter');
    assertSameValue(true, count($parameters) >= 1 && count($parameters) <= 2, 'parameter chunk is bounded');
    assertSameValue(['maxRows' => 500, 'timeoutSeconds' => 7, 'readOnly' => true], $options,
        'bounded read-only transport options');
    foreach (['0', ''] as $excluded) assertSameValue(false, in_array($excluded, $parameters, true), 'zero/empty never queried');
}

$sql = mb_strtolower(implode("\n", array_column($calls, 0)));
foreach ([
    'sale.Номер as sourceOrderNumber',
    "nullif(convert(varchar(10), max(sroki.ДатаКомплектности), 23), '0001-01-01') as readinessDate",
    "nullif(convert(varchar(10), max(sroki.ДатаПолнойОтгрузки), 23), '0001-01-01') as fullShipmentDate",
    'prod.Номер = sale.Номер',
    'sroki.ЗаказКлиента = sale.Ссылка',
    'prod.Ссылка = etap.Распоряжение',
    'sale.Ссылка = prod.ДокументОснование',
    'type.Ссылка = sale.shlz_ТипЗаказа',
    'etap.ПометкаУдаления = 0',
    "type.Наименование = n'ЛифтовоеОборудование'",
] as $contract) assertSameValue(true, str_contains($sql, mb_strtolower($contract)), 'real legacy join/filter: ' . $contract);
assertSameValue(true, str_contains($sql, mb_strtolower('group by sale.Номер')), 'orders group by exact sale number');
assertSameValue(true, str_contains($sql, mb_strtolower('convert(varchar(10), min(etap.ДатаОтгрузки), 23) as shipmentDate')),
    'shipment result is exact MIN after filters');
assertSameValue(true,
    str_contains($sql, 'датаотгрузки is not null') && str_contains($sql, "датаотгрузки <> '0001-01-01'"),
    'NULL and sentinel are excluded before MIN shipment aggregation');

$serialized = json_encode([$calls, $result], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
assertSameValue(false, str_contains($serialized, 'synthetic'), 'credentials never enter query result evidence');

$fixture = new EquipmentFactsFixture('chunk_atomic');
try {
    $fixture->migrate();
    $fixture->object(91, 'A-100');
    $owner = new EquipmentFactsApplication($fixture->db, $fixture->p, EquipmentFactsFixture::HMAC_KEY);
    $owner->execute(equipmentCommand('51515151-5151-4151-8151-515151515151', '2026-09-21T08:00:00.000000Z', [
        equipmentRecord('A-100', '2026-09-01', null, null),
    ]));
    $before = [
        'current' => $fixture->rows('fm2_equipment_fact_current'),
        'history' => $fixture->rows('fm2_equipment_fact_history'),
        'diagnostics' => $fixture->rows('fm2_equipment_fact_diagnostics'),
        'lastSuccess' => $fixture->db->query("SELECT last_successful_run_id,last_successful_observed_at FROM {$fixture->p}fm2_equipment_fact_sync_metadata")->fetch_assoc(),
    ];
    $chunkCalls = 0;
    $partial = new NativeErpEquipmentFactsDelivery($config,
        static function (string $sql, array $parameters, array $options) use (&$chunkCalls): array {
            $chunkCalls++;
            if ($chunkCalls === 3) throw new RuntimeException('private later chunk failure SELECT credential');
            if (str_contains(mb_strtolower($sql), 'max(')) return [[
                'sourceOrderNumber' => $parameters[0], 'readinessDate' => '2026-09-09', 'fullShipmentDate' => null,
            ]];
            return [];
        });
    $receipt = ErpEquipmentFactsDelivery::run(
        static fn(): array => $partial->fetch(['A-100', 'B-200', 'C-300']),
        $owner->execute(...),
        '52525252-5252-4252-8252-525252525252',
        '2026-09-21T09:00:00.000000Z',
    );
    assertSameValue(['status' => 'failed', 'runId' => '52525252-5252-4252-8252-525252525252', 'reason' => 'SOURCE_UNAVAILABLE'],
        $receipt, 'later chunk failure records one safe failed run');
    assertSameValue($before, [
        'current' => $fixture->rows('fm2_equipment_fact_current'),
        'history' => $fixture->rows('fm2_equipment_fact_history'),
        'diagnostics' => $fixture->rows('fm2_equipment_fact_diagnostics'),
        'lastSuccess' => $fixture->db->query("SELECT last_successful_run_id,last_successful_observed_at FROM {$fixture->p}fm2_equipment_fact_sync_metadata")->fetch_assoc(),
    ], 'later chunk failure changes no projection/history/diagnostics/last-success');
} finally { $fixture->close(); }

echo "PASS: ERP-EQUIPMENT-FACTS-001 bounded real legacy source scope\n";

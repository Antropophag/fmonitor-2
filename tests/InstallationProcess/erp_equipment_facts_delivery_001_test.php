<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\InstallationProcess\{ErpEquipmentFactsDeliveryConfig, MariaDbErpEquipmentFactsSource, NativeErpEquipmentFactsDelivery};

$stageSql = mb_strtolower(MariaDbErpEquipmentFactsSource::shipmentsQuery(1));
assertSameValue(true, str_contains($stageSql, 'датаотгрузки is not null')
    && str_contains($stageSql, "датаотгрузки <> '0001-01-01'"),
    'production aggregate excludes null/sentinel before MIN');
$production = file_get_contents(dirname(__DIR__, 2) . '/app/YiiRuntime/Commands/ErpEquipmentFactsSyncController.php');
$transportSource = file_get_contents(dirname(__DIR__, 2) . '/app/InstallationProcess/MariaDbSqlServerEquipmentFactsTransport.php');
assertSameValue(true, str_contains($transportSource, 'Encrypt=yes')
    && str_contains($transportSource, 'TrustServerCertificate=yes'),
    'owner-approved pilot SQL Server transport remains encrypted with compatibility trust exception');
assertSameValue(true, str_contains($transportSource, '\';Database=\'.$config->database'),
    'transport opens the validated real legacy database from configuration');
assertSameValue(false, str_contains($transportSource, 'Encrypt=no')
    || str_contains($transportSource, 'Database=1c-erp'),
    'transport never disables encryption or hard-codes 1c-erp as default database');
foreach (['PDO::SQLSRV_ATTR_QUERY_TIMEOUT', 'TOP (', 'fetch(PDO::FETCH_ASSOC)'] as $boundary) {
    assertSameValue(true, str_contains($production, $boundary), 'production transport enforces ' . $boundary);
}
assertSameValue(false, str_contains($production, 'fetchAll('), 'production transport never materializes an unbounded result');

$config = new ErpEquipmentFactsDeliveryConfig('sqlserver.test', 'SHLZ-STAGE', 'reader', 'secret', 10000, 30, 100);
$calls = [];
$transport = static function (string $sql, array $params, array $options) use (&$calls): array {
    $calls[] = [$sql, $params, $options];
    if (str_contains($sql, 'BI_Sпроф_СрокиХраненияГотовойПродукцииID')) return [
        ['sourceOrderNumber' => ' ORD-1 ', 'readinessDate' => '2026-09-01', 'fullShipmentDate' => '2026-09-05'],
        ['sourceOrderNumber' => 'ORD-2', 'readinessDate' => null, 'fullShipmentDate' => '2026-09-06'],
    ];
    return [
        ['sourceOrderNumber' => 'ORD-1', 'shipmentDate' => '0001-01-01'],
        ['sourceOrderNumber' => 'ORD-1', 'shipmentDate' => '2026-09-03'],
        ['sourceOrderNumber' => 'ORD-1', 'shipmentDate' => '2026-09-02'],
    ];
};
$result = (new NativeErpEquipmentFactsDelivery($config, $transport))->fetch(['ORD-1', 'ORD-2']);
assertSameValue(['status' => 'complete', 'records' => [
    ['sourceOrderNumber' => 'ORD-1', 'readinessDate' => '2026-09-01', 'firstShipmentDate' => '2026-09-02', 'fullShipmentDate' => '2026-09-05'],
    ['sourceOrderNumber' => 'ORD-2', 'readinessDate' => null, 'firstShipmentDate' => null, 'fullShipmentDate' => '2026-09-06'],
]], $result, 'exact independent normalization and sentinel');
assertSameValue(2, count($calls), 'exactly two bounded queries');
$normalized = array_map(static fn($sql): string => mb_strtolower((string) preg_replace('/\s+/u', ' ', trim((string) preg_replace('#--[^\r\n]*|/\*.*?\*/#su', '', $sql)))), array_column($calls, 0));
[$orders, $stages] = $normalized;
foreach (['select', 'max(', 'датакомплектности', 'датаполнойотгрузки', 'from', 'bi_dзаказклиентаid', 'join', 'bi_sпроф_срокихраненияготовойпродукцииid', ' on ', 'номер', 'group by'] as $part) {
    assertSameValue(true, str_contains($orders, $part), 'executed aggregate query structure ' . $part);
}
foreach (['select', 'датаотгрузки', 'from', 'bi_dзаказклиентаid', 'join', 'bi_dэтаппроизводства2_2id', ' on ', 'пометкаудаления', '=0', 'наименование', "=n'лифтовоеоборудование'", 'group by'] as $part) {
    assertSameValue(true, str_contains(str_replace(' ', '', $stages), str_replace(' ', '', $part)), 'executed stage query structure ' . $part);
}
assertSameValue(2, substr_count($orders, 'max('), 'both order facts independently aggregated');
assertSameValue(true, str_contains($orders, 'convert(varchar(10), max(')
    && str_contains($stages, 'convert(varchar(10), min('), 'SQL Server projects aggregates as canonical date-only strings');
assertSameValue(2, substr_count($orders, "nullif(convert(varchar(10), max("),
    'both order aggregates normalize legacy sentinel to authoritative NULL');
foreach ($calls as $call) {
    assertSameValue(['ORD-1', 'ORD-2'], $call[1], 'exact candidate parameters');
    assertSameValue(['maxRows' => 10000, 'timeoutSeconds' => 30, 'readOnly' => true], $call[2], 'bounded read-only options');
}
assertSameValue(false, str_contains(json_encode([$calls, $result], JSON_THROW_ON_ERROR), 'secret'), 'credentials hidden');

$failed = (new NativeErpEquipmentFactsDelivery($config, static fn() => throw new RuntimeException('private transport')))->fetch(['ORD']);
assertSameValue(['status' => 'failed', 'reason' => 'SOURCE_UNAVAILABLE'], $failed, 'technical failure typed');
$orphanStage = static fn(string $sql, array $params): array => str_contains($sql, 'BI_Sпроф_СрокиХраненияГотовойПродукцииID')
    ? [['sourceOrderNumber' => 'ORD', 'readinessDate' => null, 'fullShipmentDate' => null]]
    : [['sourceOrderNumber' => 'MISSING', 'shipmentDate' => '2026-09-01']];
assertSameValue(['status' => 'complete', 'records' => [[
    'sourceOrderNumber' => 'ORD', 'readinessDate' => null, 'firstShipmentDate' => null, 'fullShipmentDate' => null,
]]], (new NativeErpEquipmentFactsDelivery($config, $orphanStage))->fetch(['ORD', 'MISSING']),
    'stage outside authoritative order aggregate is ignored without failure or synthetic record');
$cases = [
    static fn(string $sql, array $params): array => str_contains($sql, 'BI_Sпроф_СрокиХраненияГотовойПродукцииID')
        ? [['sourceOrderNumber' => 'ORD', 'readinessDate' => 'bad', 'fullShipmentDate' => null]] : [],
    static fn(string $sql, array $params): array => str_contains($sql, 'BI_Sпроф_СрокиХраненияГотовойПродукцииID')
        ? [['sourceOrderNumber' => 'ORD', 'readinessDate' => null, 'fullShipmentDate' => null], ['sourceOrderNumber' => 'ORD', 'readinessDate' => null, 'fullShipmentDate' => null]] : [],
];
foreach ($cases as $case) assertSameValue(['status' => 'failed', 'reason' => 'SOURCE_INVALID'],
    (new NativeErpEquipmentFactsDelivery($config, $case))->fetch(['ORD']), 'invalid/orphan/duplicate whole batch');

echo "PASS: ERP-EQUIPMENT-FACTS-001 G adapter\n";

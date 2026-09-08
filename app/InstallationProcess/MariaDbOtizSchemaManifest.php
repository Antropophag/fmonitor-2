<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Exact structural readiness matcher shared by OTIZ canonical migrations and runtime checks. */
final class MariaDbOtizSchemaManifest
{
    /** @param list<array{0:string,1:string,2:string,3:string}> $columns @param array<string,array{0:int,1:string}> $indexes */
    public static function matches(\mysqli $db,string $table,array $columns,array $indexes):bool
    {
        $properties=MariaDbSchemaInspector::tableProperties($db,$table);if(!is_array($properties)||strtoupper((string)$properties['ENGINE'])!=='INNODB')return false;
        $actual=MariaDbSchemaInspector::columns($db,$table);if(count($actual)!==count($columns))return false;
        foreach($columns as$i=>$expected)if([(string)$actual[$i]['COLUMN_NAME'],strtolower((string)$actual[$i]['COLUMN_TYPE']),(string)$actual[$i]['IS_NULLABLE'],(string)$actual[$i]['EXTRA']]!==$expected)return false;
        $actualIndexes=[];foreach(MariaDbSchemaInspector::indexes($db,$table)as$index)$actualIndexes[(string)$index['INDEX_NAME']]=[(int)$index['NON_UNIQUE'],(string)$index['COLUMNS']];ksort($actualIndexes);ksort($indexes);return$actualIndexes===$indexes;
    }
}

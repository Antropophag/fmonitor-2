<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

/** One structured source for planning-v9 DDL and expected metadata. */
final class InspectionPlanningDefinitionSchemaMigration
{
    public const SCHEDULES = 'fm2_pilot_inspection_schedules';
    public const EVENTS = 'fm2_pilot_inspection_schedule_events';

    public static function definitions(string $prefix, string $collation): array
    {
        $column = static fn(string $name,string $type,string $extra='',?string $columnCollation=null):array =>
            compact('name','type','extra','columnCollation');
        $index = static fn(string $name,bool $unique,array $columns):array => compact('name','unique','columns');
        $schemas = [
            self::SCHEDULES => [
                'columns'=>[$column('id','BIGINT UNSIGNED','auto_increment'),$column('installation_case_id','BIGINT UNSIGNED'),
                    $column('legacy_object_id','BIGINT UNSIGNED'),$column('control_engineer_user_id','BIGINT UNSIGNED'),
                    $column('inspection_date','DATE'),$column('scheduled_by_user_id','BIGINT UNSIGNED'),
                    $column('scheduled_at','VARCHAR(40)')],
                'indexes'=>[
                    $index('PRIMARY',true,['id']),
                    $index('unique_planned_inspection',true,[
                        'installation_case_id','control_engineer_user_id','inspection_date',
                    ]),
                    $index('calendar_date',false,['inspection_date','id']),
                    $index('engineer_day',false,[
                        'control_engineer_user_id','inspection_date','id',
                    ]),
                ],
                'checks'=>[],
            ],
            self::EVENTS => [
                'columns'=>[$column('id','BIGINT UNSIGNED','auto_increment'),$column('schedule_id','BIGINT UNSIGNED'),
                    $column('installation_case_id','BIGINT UNSIGNED'),$column('event_type','VARCHAR(80)'),
                    $column('payload_json','LONGTEXT','', 'utf8mb4_bin'),$column('actor_user_id','BIGINT UNSIGNED'),
                    $column('occurred_at','VARCHAR(40)')],
                'indexes'=>[$index('PRIMARY',true,['id']),$index('schedule_id',false,['schedule_id','id']),
                    $index('installation_case_id',false,['installation_case_id','id'])],
                'checks'=>['json_valid(payload_json)'],
            ],
        ];
        $result = [];
        foreach ($schemas as $name => $schema) {
            $result[$name] = [
                'ddl'=>self::render($prefix . $name, $schema, $collation),
                'manifest'=>MariaDbInspectionPlanningSchemaFingerprint::manifest($schema, $collation),
            ];
        }
        return $result;
    }

    private static function render(string $table, array $schema, string $collation): string
    {
        $parts = [];
        foreach ($schema['columns'] as $column) {
            $sql = $column['name'] . ' ' . $column['type'];
            if ($column['columnCollation'] !== null) {
                $sql .= ' CHARACTER SET utf8mb4 COLLATE ' . $column['columnCollation'];
            }
            $sql .= ' NOT NULL';
            if ($column['extra'] === 'auto_increment') $sql .= ' AUTO_INCREMENT';
            $parts[] = $sql;
        }
        foreach ($schema['indexes'] as $index) {
            $head = $index['name'] === 'PRIMARY' ? 'PRIMARY KEY'
                : ($index['unique'] ? 'UNIQUE KEY ' : 'KEY ') . $index['name'];
            $parts[] = $head . '(' . implode(',', $index['columns']) . ')';
        }
        foreach ($schema['checks'] as $check) {
            $name = 'json_valid_' . substr(hash('sha256', $table), 0, 16);
            $parts[] = "CONSTRAINT `{$name}` CHECK({$check})";
        }
        return 'CREATE TABLE `' . $table . '`(' . implode(',', $parts)
            . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE `{$collation}`";
    }
}

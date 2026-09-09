<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Literal deployment definition reviewed by DURABLE-JOBS-SCHEMA-001. */
final class JobsDefinitionSchemaMigration
{
    public static function tables(): array
    {
        return array_merge(JobsQueueDefinitionSchemaMigration::tables(),JobsDeliveryDefinitionSchemaMigration::tables());
    }
    public static function ddl(string $prefix, string $name, array $definition): string
    {
        $parts=[];
        foreach($definition['columns'] as $column){
            $sql='`'.$column['name'].'` '.$column['type'];
            if($column['charset']!==null)$sql.=' CHARACTER SET '.$column['charset'].' COLLATE '.$column['collation'];
            $sql.=$column['nullable']?' NULL':' NOT NULL';
            if($column['default']!==null)$sql.=' DEFAULT '.$column['default'];
            if($column['extra']!=='')$sql.=' '.$column['extra'];
            $parts[]=$sql;
        }
        $columns=static fn(array $names): string=>'(`'.implode('`,`',$names).'`)';
        foreach($definition['indexes'] as $index){
            $kind=$index['name']==='PRIMARY'?'PRIMARY KEY':($index['unique']?'UNIQUE KEY ':'KEY ').'`'.str_replace('@prefix',$prefix,$index['name']).'`';
            $parts[]=$kind.$columns($index['columns']);
        }
        foreach($definition['foreignKeys'] as $fk)$parts[]='CONSTRAINT `'.str_replace('@prefix',$prefix,$fk['name']).'` FOREIGN KEY'.$columns($fk['columns']).' REFERENCES `'.$prefix.$fk['target'].'`'.$columns($fk['referenced']).' ON UPDATE '.$fk['update'].' ON DELETE '.$fk['delete'];
        foreach($definition['checks'] as $check)$parts[]='CHECK('.$check.')';
        return 'CREATE TABLE `'.$prefix.$name.'`('.implode(',',$parts).') ENGINE='.$definition['engine'].' DEFAULT CHARSET=utf8mb4 COLLATE '.$definition['collation'];
    }
}

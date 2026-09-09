<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

/** Explicit deployment seam for the OTIZ settlement ledger. Never called by runtime. */
final class OtizSettlementSchemaMigration
{
    public static function apply(\mysqli $db, string $prefix): array
    {
        if (preg_match('/^[A-Za-z0-9_]{0,25}$/D', $prefix) !== 1) throw new \InvalidArgumentException('INVALID_TABLE_PREFIX');
        $tables=self::definitions();$missing=[];
        foreach($tables as$name=>$definition){$table=$prefix.$name;$escaped=$db->real_escape_string($table);$exists=(int)$db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}'")->fetch_column()>0;if(!$exists)$missing[]=$name;elseif(!MariaDbExactSchemaFingerprint::matches($db,$table,$definition['fingerprint'],'utf8mb4_unicode_ci'))return['applied'=>false,'schemaVersion'=>24,'reason'=>'SCHEMA_MIGRATION_CONFLICT'];}
        foreach($missing as$name)$db->query(str_replace('@table','`'.$prefix.$name.'`',$tables[$name]['ddl']));
        return['applied'=>$missing!==[],'schemaVersion'=>24,'tablesCreated'=>array_map(static fn(string$n):string=>$prefix.$n,$missing)];
    }
    private static function definitions():array
    {
        $column=static fn(string$n,string$t,?string$charset=null,?string$collation=null):array=>['name'=>$n,'type'=>$t,'nullable'=>'NO','default'=>null,'extra'=>'','generated'=>'NEVER','generationExpression'=>null,'charset'=>$charset,'collation'=>$collation];
        $primary=static fn(string$c,int$s):array=>['name'=>'PRIMARY','nonUnique'=>0,'sequence'=>$s,'column'=>$c,'subPart'=>null,'collation'=>'A','type'=>'BTREE','ignored'=>'NO'];
        return['fm2_otiz_settlement_locks'=>['ddl'=>'CREATE TABLE @table(object_id BIGINT UNSIGNED NOT NULL,PRIMARY KEY(object_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci','fingerprint'=>['columns'=>[$column('object_id','bigint(20) unsigned')],'indexes'=>[$primary('object_id',1)]]],'fm2_otiz_settlement_operations'=>['ddl'=>'CREATE TABLE @table(actor_user_id BIGINT UNSIGNED NOT NULL,operation_id CHAR(36) NOT NULL,request_sha256 CHAR(64) NOT NULL,status VARCHAR(40) NOT NULL,result_json LONGTEXT NOT NULL,created_at VARCHAR(40) NOT NULL,PRIMARY KEY(actor_user_id,operation_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci','fingerprint'=>['columns'=>[$column('actor_user_id','bigint(20) unsigned'),$column('operation_id','char(36)','utf8mb4','utf8mb4_unicode_ci'),$column('request_sha256','char(64)','utf8mb4','utf8mb4_unicode_ci'),$column('status','varchar(40)','utf8mb4','utf8mb4_unicode_ci'),$column('result_json','longtext','utf8mb4','utf8mb4_unicode_ci'),$column('created_at','varchar(40)','utf8mb4','utf8mb4_unicode_ci')],'indexes'=>[$primary('actor_user_id',1),$primary('operation_id',2)]]]];
    }
}

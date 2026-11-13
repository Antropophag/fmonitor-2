<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Additive v39 immutable signed-appendix submission intents. */
final class AssignmentOrderOriginalStartSchemaMigration
{
    private const TABLE='fm2_assignment_order_original_starts';

    public static function apply(\mysqli $db,string $prefix=''):array
    {
        if(PHP_INT_SIZE!==8||preg_match('/^[A-Za-z0-9_]{0,25}$/D',$prefix)!==1)throw new \InvalidArgumentException('INVALID_TABLE_PREFIX');
        $table=$prefix.self::TABLE;$state=self::state($db,$table);
        if($state==='conflict')throw new \RuntimeException('SCHEMA_MIGRATION_CONFLICT');
        if($state==='absent'){
            $db->query("CREATE TABLE `{$table}` (request_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,installation_case_id BIGINT UNSIGNED NOT NULL,object_id BIGINT UNSIGNED NOT NULL,assignment_order_id BIGINT UNSIGNED NOT NULL,actor_user_id BIGINT UNSIGNED NOT NULL,mode VARCHAR(20) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,document_date DATE NOT NULL,appendix_start_date DATE NOT NULL,pdf_sha256 CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,byte_size INT UNSIGNED NOT NULL,semantic_fingerprint CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,created_at_utc DATETIME(6) NOT NULL,PRIMARY KEY(request_id),KEY ix_original_start_order(assignment_order_id,created_at_utc),KEY ix_original_start_fingerprint(semantic_fingerprint),CHECK(mode IN('initial','correction')),CHECK(pdf_sha256 REGEXP '^[0-9a-f]{64}$'),CHECK(semantic_fingerprint REGEXP '^[0-9a-f]{64}$'),CHECK(byte_size BETWEEN 5 AND 20971520)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            if(self::state($db,$table)!=='ready')throw new \RuntimeException('SCHEMA_MIGRATION_CONFLICT');
            return['applied'=>true,'schemaVersion'=>39,'tablesCreated'=>[$table]];
        }
        return['applied'=>false,'schemaVersion'=>39,'tablesCreated'=>[]];
    }

    public static function isCompleteCompatible(\mysqli $db,string $prefix=''):bool
    {
        try{return preg_match('/^[A-Za-z0-9_]{0,25}$/D',$prefix)===1&&self::state($db,$prefix.self::TABLE)==='ready';}catch(\Throwable){return false;}
    }

    private static function state(\mysqli $db,string $table):string
    {
        $s=$db->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');$s->execute([$table]);$row=$s->get_result()->fetch_assoc();$s->close();
        if($row===null)return'absent';if(($row['ENGINE']??null)!=='InnoDB')return'conflict';
        $s=$db->prepare('SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION');$s->execute([$table]);$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
        $expected=[
            ['request_id','char(36)','NO','ascii_bin'],['installation_case_id','bigint(20) unsigned','NO',null],['object_id','bigint(20) unsigned','NO',null],['assignment_order_id','bigint(20) unsigned','NO',null],['actor_user_id','bigint(20) unsigned','NO',null],['mode','varchar(20)','NO','ascii_bin'],['document_date','date','NO',null],['appendix_start_date','date','NO',null],['pdf_sha256','char(64)','NO','ascii_bin'],['byte_size','int(10) unsigned','NO',null],['semantic_fingerprint','char(64)','NO','ascii_bin'],['created_at_utc','datetime(6)','NO',null],
        ];
        $actual=array_map(static fn(array$r):array=>[$r['COLUMN_NAME'],strtolower($r['COLUMN_TYPE']),$r['IS_NULLABLE'],$r['COLLATION_NAME']],$rows);
        if($actual!==$expected)return'conflict';
        $s=$db->prepare('SELECT INDEX_NAME,NON_UNIQUE,GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) cols FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? GROUP BY INDEX_NAME,NON_UNIQUE ORDER BY INDEX_NAME');$s->execute([$table]);$indexes=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
        $map=[];foreach($indexes as$r)$map[$r['INDEX_NAME']]=[(int)$r['NON_UNIQUE'],$r['cols']];
        if(($map['PRIMARY']??null)!==[0,'request_id']||($map['ix_original_start_order']??null)!==[1,'assignment_order_id,created_at_utc']||($map['ix_original_start_fingerprint']??null)!==[1,'semantic_fingerprint'])return'conflict';
        $s=$db->prepare('SELECT CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME=?');$s->execute([$table]);$checks=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
        $clauses=array_map(static fn(array$r):string=>strtolower(str_replace(['`',' ','(',')'],['','','',''],(string)$r['CHECK_CLAUSE'])),$checks);
        foreach(["modein'initial','correction'","pdf_sha256regexp'^[0-9a-f]{64}$'","semantic_fingerprintregexp'^[0-9a-f]{64}$'",'byte_sizebetween5and20971520']as$required){$found=false;foreach($clauses as$clause)if(str_contains($clause,$required)){$found=true;break;}if(!$found)return'conflict';}
        return'ready';
    }
}

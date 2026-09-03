<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

/** Canonical additive v12 owner for assignment-order original evidence. */
final class AssignmentOrderOriginalSchemaMigration
{
    public static function apply(\mysqli $db, string $prefix = ''): array
    {
        IdentityAccessDefinitionSchemaMigration::assertPrefix($prefix);
        $collation = IdentityAccessDefinitionSchemaMigration::databaseCollation($db);
        $capabilityTable = $prefix . 'fm2_process_user_capabilities';
        $capability = ProcessCapabilityChecksClassifier::inspect($db, $capabilityTable);
        $schemas = self::schemas($prefix);
        $states = [];
        $conflicts = [];
        if (!in_array($capability['state'] ?? null, ['v4', 'v12'], true)) {
            $conflicts[] = $capabilityTable;
        }
        foreach ($schemas as $name => $schema) {
            $table = $prefix . $name;
            if (!MariaDbSchemaInspector::tableExists($db, $table)) {
                $states[$name] = 'absent';
            } elseif (self::matches($db, $table, $schema, $prefix, $collation)) {
                $states[$name] = 'exact';
            } else {
                $states[$name] = 'conflict';
                $conflicts[] = $table;
            }
        }
        if ($conflicts !== []) {
            sort($conflicts, SORT_STRING);
            return ['applied'=>false,'schemaVersion'=>12,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>$conflicts];
        }

        $changed = false;
        if (($capability['state'] ?? null) === 'v4') {
            $db->query("ALTER TABLE `{$capabilityTable}` DROP CONSTRAINT `ck_fm2_process_user_capability`, ADD CONSTRAINT `ck_fm2_process_user_capability` CHECK(capability IN ('assignment_order.prepare','assignment_order.confirm_registration','installation.open','construction_control_engineer','assignment_order.original.upload','assignment_order.original.correct','assignment_order.original.storage.reconcile'))");
            $changed = true;
        }
        $created = [];
        foreach ($schemas as $name => $schema) {
            if ($states[$name] !== 'absent') continue;
            $table = $prefix . $name;
            $db->query(self::ddl($table, $schema, $collation));
            if ($name === 'fm2_assignment_order_original_revisions') {
                self::dropRedundantPreviousIndex($db, $table);
            }
            $created[] = $table;
            $changed = true;
        }
        sort($created, SORT_STRING);
        return ['applied'=>$changed,'schemaVersion'=>12,'tablesCreated'=>$created];
    }

    private static function dropRedundantPreviousIndex(\mysqli $db, string $table): void
    {
        $enabled = (int)$db->query('SELECT @@SESSION.FOREIGN_KEY_CHECKS value')->fetch_assoc()['value'];
        $db->query('SET FOREIGN_KEY_CHECKS=0');
        try { $db->query("ALTER TABLE `{$table}` DROP INDEX `fk_aoo_revision_previous`"); }
        finally { $db->query('SET FOREIGN_KEY_CHECKS='.$enabled); }
    }

    private static function schemas(string $p): array
    {
        $c=static fn(string$n,string$t,bool$nullable=false,string$extra=''):array=>[$n,$t,$nullable,$extra];
        $i=static fn(string$kind,string$name,array$columns):array=>[$kind,$name,$columns];
        $f=static fn(string$name,array$columns,string$target,array$targets):array=>[$name,$columns,$p.$target,$targets];
        return [
            'fm2_assignment_order_original_roots'=>[
                'columns'=>[$c('root_original_id','VARCHAR(160)'),$c('installation_case_id','BIGINT UNSIGNED'),$c('assignment_order_id','BIGINT UNSIGNED'),$c('composition_identity','VARCHAR(255)'),$c('composition_sha256','CHAR(64)'),$c('created_at','VARCHAR(40)')],
                'indexes'=>[$i('PRIMARY','PRIMARY',['root_original_id']),$i('UNIQUE','uq_aoo_root_order',['assignment_order_id']),$i('INDEX','ix_aoo_root_case',['installation_case_id'])],
                'fks'=>[$f('fk_aoo_root_case',['installation_case_id'],'fm2_installation_cases',['id']),$f('fk_aoo_root_order',['assignment_order_id'],'fm2_assignment_orders',['id'])],
                'checks'=>['char_length(composition_sha256)=64']],
            'fm2_assignment_order_original_revisions'=>[
                'columns'=>[$c('revision_id','VARCHAR(160)'),$c('root_original_id','VARCHAR(160)'),$c('revision_number','INT UNSIGNED'),$c('previous_revision_id','VARCHAR(160)',true),$c('expected_current_revision_id','VARCHAR(160)',true),$c('current_marker','TINYINT UNSIGNED',true),$c('document_date','DATE'),$c('uploaded_at','VARCHAR(40)'),$c('actor_user_id','BIGINT UNSIGNED'),$c('pdf_sha256','CHAR(64)'),$c('byte_size','BIGINT UNSIGNED'),$c('private_content_identity','VARCHAR(255)'),$c('correction_reason','VARCHAR(500)',true)],
                'indexes'=>[$i('PRIMARY','PRIMARY',['revision_id']),$i('UNIQUE','uq_aoo_revision_number',['root_original_id','revision_number']),$i('UNIQUE','uq_aoo_revision_identity',['root_original_id','revision_id']),$i('UNIQUE','uq_aoo_current',['root_original_id','current_marker'])],
                'fks'=>[$f('fk_aoo_revision_root',['root_original_id'],'fm2_assignment_order_original_roots',['root_original_id']),$f('fk_aoo_revision_previous',['root_original_id','previous_revision_id'],'fm2_assignment_order_original_revisions',['root_original_id','revision_id'])],
                'checks'=>['byte_size>=1 AND byte_size<=20971520','char_length(pdf_sha256)=64','current_marker IS NULL OR current_marker=1','revision_number=1 AND previous_revision_id IS NULL AND expected_current_revision_id IS NULL AND correction_reason IS NULL OR revision_number>1 AND previous_revision_id IS NOT NULL AND expected_current_revision_id=previous_revision_id AND char_length(trim(correction_reason)) BETWEEN 1 AND 500']],
            'fm2_assignment_order_original_requests'=>[
                'columns'=>[$c('request_id','CHAR(36)'),$c('actor_user_id','BIGINT UNSIGNED'),$c('mode','VARCHAR(20)'),$c('installation_case_id','BIGINT UNSIGNED'),$c('assignment_order_id','BIGINT UNSIGNED'),$c('status','VARCHAR(20)'),$c('reason_code','VARCHAR(80)',true),$c('retryable','TINYINT'),$c('root_original_id','VARCHAR(160)',true),$c('current_revision_id','VARCHAR(160)',true),$c('revision_number','INT UNSIGNED',true),$c('document_date','DATE',true),$c('sha256','CHAR(64)',true),$c('byte_size','BIGINT UNSIGNED',true),$c('uploaded_at','VARCHAR(40)',true),$c('attempted_at','VARCHAR(40)')],
                'indexes'=>[$i('PRIMARY','PRIMARY',['request_id']),$i('INDEX','ix_aoo_request_order',['assignment_order_id','request_id']),$i('INDEX','ix_aoo_request_revision',['current_revision_id']),$i('INDEX','ix_aoo_request_case',['installation_case_id']),$i('INDEX','ix_aoo_request_root',['root_original_id'])],
                'fks'=>[$f('fk_aoo_request_order',['assignment_order_id'],'fm2_assignment_orders',['id']),$f('fk_aoo_request_case',['installation_case_id'],'fm2_installation_cases',['id']),$f('fk_aoo_request_root',['root_original_id'],'fm2_assignment_order_original_roots',['root_original_id']),$f('fk_aoo_request_revision',['current_revision_id'],'fm2_assignment_order_original_revisions',['revision_id'])],
                'checks'=>["mode IN ('initial','correction')","status IN ('accepted','rejected','conflict')",'retryable=0',"status='accepted' AND reason_code IS NULL AND root_original_id IS NOT NULL AND current_revision_id IS NOT NULL AND revision_number IS NOT NULL AND document_date IS NOT NULL AND sha256 IS NOT NULL AND byte_size IS NOT NULL AND uploaded_at IS NOT NULL OR status IN ('rejected','conflict') AND reason_code IS NOT NULL AND root_original_id IS NULL AND current_revision_id IS NULL AND revision_number IS NULL AND document_date IS NULL AND sha256 IS NULL AND byte_size IS NULL AND uploaded_at IS NULL"]],
            'fm2_assignment_order_original_fingerprints'=>[
                'columns'=>[$c('fingerprint','CHAR(64)'),$c('request_id','CHAR(36)'),$c('root_original_id','VARCHAR(160)'),$c('revision_id','VARCHAR(160)')],
                'indexes'=>[$i('PRIMARY','PRIMARY',['fingerprint']),$i('UNIQUE','uq_aoo_fingerprint_request',['request_id']),$i('INDEX','ix_aoo_fingerprint_revision',['revision_id']),$i('INDEX','ix_aoo_fingerprint_root_revision',['root_original_id','revision_id'])],
                'fks'=>[$f('fk_aoo_fingerprint_request',['request_id'],'fm2_assignment_order_original_requests',['request_id']),$f('fk_aoo_fingerprint_root',['root_original_id'],'fm2_assignment_order_original_roots',['root_original_id']),$f('fk_aoo_fingerprint_revision',['revision_id'],'fm2_assignment_order_original_revisions',['revision_id'])],
                'checks'=>['char_length(fingerprint)=64']],
            'fm2_assignment_order_original_events'=>[
                'columns'=>[$c('id','BIGINT UNSIGNED',false,'auto_increment'),$c('event_type','VARCHAR(80)'),$c('installation_case_id','BIGINT UNSIGNED'),$c('assignment_order_id','BIGINT UNSIGNED'),$c('root_original_id','VARCHAR(160)'),$c('revision_id','VARCHAR(160)'),$c('occurred_at','VARCHAR(40)'),$c('actor_user_id','BIGINT UNSIGNED')],
                'indexes'=>[$i('PRIMARY','PRIMARY',['id']),$i('UNIQUE','uq_aoo_event_revision',['revision_id']),$i('INDEX','ix_aoo_event_order',['assignment_order_id']),$i('INDEX','ix_aoo_event_case',['installation_case_id','assignment_order_id','id']),$i('INDEX','ix_aoo_event_root',['root_original_id'])],
                'fks'=>[$f('fk_aoo_event_case',['installation_case_id'],'fm2_installation_cases',['id']),$f('fk_aoo_event_order',['assignment_order_id'],'fm2_assignment_orders',['id']),$f('fk_aoo_event_root',['root_original_id'],'fm2_assignment_order_original_roots',['root_original_id']),$f('fk_aoo_event_revision',['revision_id'],'fm2_assignment_order_original_revisions',['revision_id'])],
                'checks'=>["event_type IN ('assignment_order_original_accepted','assignment_order_original_corrected')"]],
            'fm2_assignment_order_original_attempt_audits'=>[
                'columns'=>[$c('id','BIGINT UNSIGNED',false,'auto_increment'),$c('request_id','CHAR(36)'),$c('actor_identity','VARCHAR(120)'),$c('mode','VARCHAR(20)'),$c('installation_case_id','BIGINT UNSIGNED'),$c('assignment_order_id','BIGINT UNSIGNED'),$c('status','VARCHAR(20)'),$c('reason_code','VARCHAR(80)'),$c('attempted_at','VARCHAR(40)')],
                'indexes'=>[$i('PRIMARY','PRIMARY',['id']),$i('UNIQUE','uq_aoo_audit_request',['request_id']),$i('INDEX','ix_aoo_audit_actor',['actor_identity','attempted_at'])],
                'fks'=>[$f('fk_aoo_audit_request',['request_id'],'fm2_assignment_order_original_requests',['request_id'])],
                'checks'=>["mode IN ('initial','correction')","status IN ('rejected','conflict')"]],
            'fm2_assignment_order_original_maintenance_results'=>[
                'columns'=>[$c('request_id','CHAR(36)'),$c('system_principal_id','VARCHAR(160)'),$c('status','VARCHAR(20)'),$c('reason_code','VARCHAR(80)',true),$c('retryable','TINYINT'),$c('scanned','INT UNSIGNED'),$c('deleted','INT UNSIGNED'),$c('retained','INT UNSIGNED'),$c('failed','INT UNSIGNED'),$c('next_cursor','VARCHAR(500)',true),$c('attempted_at','VARCHAR(40)')],
                'indexes'=>[$i('PRIMARY','PRIMARY',['request_id']),$i('INDEX','ix_aoo_maintenance_principal',['system_principal_id','attempted_at'])],
                'fks'=>[],
                'checks'=>['scanned=deleted+retained+failed',"status IN ('completed','partial','rejected')","status='completed' AND reason_code IS NULL AND retryable=0 OR status='partial' AND reason_code IN ('locked','storage_failure') AND retryable=1 OR status='rejected' AND reason_code IN ('invalid_command','authorization_denied') AND retryable=0"]],
        ];
    }

    private static function ddl(string $table, array $schema, string $collation): string
    {
        $parts=[];
        foreach($schema['columns'] as[$name,$type,$nullable,$extra])$parts[]=$name.' '.$type.($nullable?' NULL':' NOT NULL').($extra==='auto_increment'?' AUTO_INCREMENT':'');
        foreach($schema['indexes'] as[$kind,$name,$columns])$parts[]=($kind==='PRIMARY'?'PRIMARY KEY':($kind==='UNIQUE'?'UNIQUE KEY ':'KEY ').$name).'('.implode(',',$columns).')';
        foreach($schema['fks'] as[$name,$columns,$target,$targets])$parts[]='CONSTRAINT '.$name.' FOREIGN KEY('.implode(',',$columns).') REFERENCES `'.$target.'`('.implode(',',$targets).')';
        foreach($schema['checks'] as$check)$parts[]='CHECK('.$check.')';
        return 'CREATE TABLE `'.$table.'`('.implode(',',$parts).") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE `{$collation}`";
    }

    private static function matches(\mysqli $db,string $table,array $schema,string $prefix,string $collation):bool
    {
        if(MariaDbSchemaInspector::tableProperties($db,$table)!==['ENGINE'=>'InnoDB','TABLE_COLLATION'=>$collation])return false;
        $e=$db->real_escape_string($table);
        $actual=array_map(static fn(array$r):string=>$r['COLUMN_NAME'].':'.preg_replace('/^(bigint|int|tinyint)\(\d+\)/','$1',strtolower($r['COLUMN_TYPE'])).':'.$r['IS_NULLABLE'].($r['EXTRA']===''?'':':'.$r['EXTRA']),$db->query("SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$e}' ORDER BY ORDINAL_POSITION")->fetch_all(MYSQLI_ASSOC));
        $expected=array_map(static fn(array$c):string=>$c[0].':'.strtolower($c[1]).':'.($c[2]?'YES':'NO').($c[3]===''?'':':'.$c[3]),$schema['columns']);
        if($actual!==$expected)return false;
        $rows=$db->query("SELECT INDEX_NAME,NON_UNIQUE,SEQ_IN_INDEX,COLUMN_NAME,SUB_PART,COLLATION,INDEX_TYPE,IGNORED FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$e}' ORDER BY BINARY INDEX_NAME,SEQ_IN_INDEX")->fetch_all(MYSQLI_ASSOC);$groups=[];
        foreach($rows as$r){if($r['SUB_PART']!==null||$r['COLLATION']!=='A'||$r['INDEX_TYPE']!=='BTREE'||$r['IGNORED']!=='NO')return false;$n=$r['INDEX_NAME'];$groups[$n]??=[($n==='PRIMARY'?'PRIMARY':((int)$r['NON_UNIQUE']===0?'UNIQUE':'INDEX')),[]];$groups[$n][1][]=$r['COLUMN_NAME'];}
        $actual=array_map(static fn(array$x):string=>$x[0].'|'.implode(',',$x[1]),array_values($groups));$expected=array_map(static fn(array$x):string=>$x[0].'|'.implode(',',$x[2]),$schema['indexes']);sort($actual);sort($expected);if($actual!==$expected)return false;
        $rows=$db->query("SELECT k.CONSTRAINT_NAME,k.COLUMN_NAME,k.ORDINAL_POSITION,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,r.DELETE_RULE FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.TABLE_NAME=k.TABLE_NAME AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME WHERE k.CONSTRAINT_SCHEMA=DATABASE() AND k.TABLE_NAME='{$e}' AND k.REFERENCED_TABLE_NAME IS NOT NULL ORDER BY BINARY k.CONSTRAINT_NAME,k.ORDINAL_POSITION")->fetch_all(MYSQLI_ASSOC);$groups=[];
        foreach($rows as$r){$n=$r['CONSTRAINT_NAME'];$groups[$n]??=[[],substr($r['REFERENCED_TABLE_NAME'],strlen($prefix)),[],$r['DELETE_RULE']];$groups[$n][0][]=$r['COLUMN_NAME'];$groups[$n][2][]=$r['REFERENCED_COLUMN_NAME'];}$actual=array_map(static fn(array$x):string=>implode(',',$x[0]).'>'.$x[1].'.'.implode(',',$x[2]).':'.$x[3],array_values($groups));$expected=array_map(static fn(array$x):string=>implode(',',$x[1]).'>'.substr($x[2],strlen($prefix)).'.'.implode(',',$x[3]).':RESTRICT',$schema['fks']);sort($actual);sort($expected);if($actual!==$expected)return false;
        $actual=array_map(static fn(array$r):string=>self::normalize($r['CHECK_CLAUSE']),$db->query("SELECT cc.CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS cc JOIN information_schema.TABLE_CONSTRAINTS tc ON tc.CONSTRAINT_SCHEMA=cc.CONSTRAINT_SCHEMA AND tc.TABLE_NAME=cc.TABLE_NAME AND tc.CONSTRAINT_NAME=cc.CONSTRAINT_NAME WHERE tc.CONSTRAINT_SCHEMA=DATABASE() AND tc.TABLE_NAME='{$e}'")->fetch_all(MYSQLI_ASSOC));$expected=array_map([self::class,'normalize'],$schema['checks']);sort($actual);sort($expected);return$actual===$expected;
    }

    private static function normalize(string$value):string
    {$out='';$quoted=false;for($i=0,$n=strlen($value);$i<$n;$i++){$ch=$value[$i];if($ch==="'"){$out.=$ch;if($quoted&&$i+1<$n&&$value[$i+1]==="'"){$out.="'";$i++;}else$quoted=!$quoted;}elseif($quoted)$out.=$ch;elseif($ch!=='`'&&!ctype_space($ch))$out.=strtolower($ch);}return$out;}
}

<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

// INSPECTION-EVIDENCE-SCHEMA-001 sections 3-6 and Gate-2 cases
// G2-01, G2-02, G2-04, G2-10 and G2-16.
// Approved public seams: bin/fmonitor2-migrate.php and
// InspectionEvidenceSchemaMigration::apply(mysqli,string).

function iesQuote(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

/** @return array{exitCode:int,stdout:string,stderr:string} */
function iesRunRunner(string $database, string $prefix = '', bool $unreachable = false): array
{
    $environment = [
        'FMONITOR_DB_HOST' => $unreachable ? '127.0.0.1' : (getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1'),
        'FMONITOR_DB_PORT' => $unreachable ? '1' : (getenv('FMONITOR_TEST_DB_PORT') ?: '23306'),
        'FMONITOR_DB_NAME' => $unreachable ? 'must_not_be_accessed' : $database,
        'FMONITOR_DB_USER' => $unreachable ? 'must_not_connect' : (getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root'),
        'FMONITOR_DB_PASSWORD' => $unreachable ? 'invalid' : (getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local'),
        'FMONITOR_PROCESS_TABLE_PREFIX' => $prefix,
    ];
    $command = array_merge(['env'], array_map(
        static fn (string $name, string $value): string => $name . '=' . $value,
        array_keys($environment),
        array_values($environment),
    ), ['php', dirname(__DIR__, 2) . '/bin/fmonitor2-migrate.php']);
    $pipes = [];
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__, 2));
    if (!is_resource($process)) throw new TestFailure('Canonical migration runner must start.');
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    return ['exitCode' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
}

/** @return array<string,mixed> */
function iesApply(mysqli $connection, string $prefix): array
{
    $class = 'FMonitor2\\InstallationProcess\\InspectionEvidenceSchemaMigration';
    if (!class_exists($class)) throw new TestFailure('Approved public InspectionEvidenceSchemaMigration seam is missing.');
    return $class::apply($connection, $prefix);
}

function iesCreateRevisions(mysqli $db, string $prefix): void
{
    $db->query('CREATE TABLE ' . iesQuote($prefix . 'fm2_checklist_revisions') . "(
      installation_case_id BIGINT UNSIGNED NOT NULL, revision_no BIGINT UNSIGNED NOT NULL DEFAULT 0,
      updated_at VARCHAR(40) NOT NULL, PRIMARY KEY(installation_case_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function iesCreateOperations(mysqli $db, string $prefix, bool $predecessor): void
{
    $tail = $predecessor ? '' : ', template_snapshot_id BIGINT UNSIGNED NULL, template_snapshot_version VARCHAR(80) NULL, template_content_sha256 CHAR(64) NULL';
    $db->query('CREATE TABLE ' . iesQuote($prefix . 'fm2_checklist_operations') . "(
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, installation_case_id BIGINT UNSIGNED NOT NULL,
      client_operation_id CHAR(36) NOT NULL, device_installation_id CHAR(36) NOT NULL,
      operation_type VARCHAR(40) NOT NULL, section_id TINYINT UNSIGNED NOT NULL, item_id SMALLINT UNSIGNED NULL,
      actor_user_id BIGINT UNSIGNED NOT NULL, device_time VARCHAR(40) NOT NULL, server_received_at VARCHAR(40) NOT NULL,
      base_revision BIGINT UNSIGNED NOT NULL, accepted_revision BIGINT UNSIGNED NOT NULL, payload_json TEXT NOT NULL{$tail},
      PRIMARY KEY(id), UNIQUE KEY client_operation_id(client_operation_id), KEY installation_case_id(installation_case_id,id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function iesCreateInstallers(mysqli $db, string $prefix, bool $predecessor): void
{
    $tail = $predecessor ? '' : ', assignment_source VARCHAR(40) NOT NULL';
    $db->query('CREATE TABLE ' . iesQuote($prefix . 'fm2_checklist_operation_installers') . "(
      client_operation_id CHAR(36) NOT NULL, installer_tab_id BIGINT UNSIGNED NOT NULL,
      fio_snapshot VARCHAR(300) NOT NULL, position_snapshot VARCHAR(300) NOT NULL,
      employment_status_snapshot VARCHAR(40) NOT NULL, dismissal_effective_at_snapshot VARCHAR(40) NULL,
      workforce_source_updated_at_snapshot VARCHAR(40) NOT NULL{$tail},
      PRIMARY KEY(client_operation_id,installer_tab_id), KEY installer_tab_id(installer_tab_id,client_operation_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function iesCreatePhotos(mysqli $db, string $prefix): void
{
    $db->query('CREATE TABLE ' . iesQuote($prefix . 'fm2_checklist_photos') . "(
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, installation_case_id BIGINT UNSIGNED NOT NULL,
      section_id TINYINT UNSIGNED NOT NULL, upload_operation_id CHAR(36) NOT NULL, sha256 CHAR(64) NOT NULL,
      mime_type VARCHAR(40) NOT NULL, byte_size INT UNSIGNED NOT NULL, original_name VARCHAR(255) NOT NULL,
      storage_name VARCHAR(255) NOT NULL, actor_user_id BIGINT UNSIGNED NOT NULL, device_time VARCHAR(40) NOT NULL,
      server_received_at VARCHAR(40) NOT NULL, revoked_at VARCHAR(40) NULL,
      PRIMARY KEY(id), UNIQUE KEY upload_operation_id(upload_operation_id),
      UNIQUE KEY installation_case_id(installation_case_id,section_id,sha256), KEY installation_case_id_2(installation_case_id,section_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/** @return array<string,mixed> */
function iesState(mysqli $db, string $prefix): array
{
    $state = [];
    foreach (['fm2_checklist_revisions','fm2_checklist_operations','fm2_checklist_operation_installers','fm2_checklist_photos'] as $base) {
        $name = $prefix . $base; $escaped = $db->real_escape_string($name);
        $exists = $db->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}'")->num_rows === 1;
        if (!$exists) { $state[$name] = null; continue; }
        $quoted = iesQuote($name);
        $columns = $db->query("SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA,IS_GENERATED,GENERATION_EXPRESSION,CHARACTER_SET_NAME,COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}' ORDER BY ORDINAL_POSITION")->fetch_all(MYSQLI_ASSOC);
        $indexes = $db->query("SELECT INDEX_NAME,NON_UNIQUE,SEQ_IN_INDEX,COLUMN_NAME,SUB_PART,COLLATION,INDEX_TYPE,IGNORED FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}' ORDER BY BINARY INDEX_NAME,SEQ_IN_INDEX")->fetch_all(MYSQLI_ASSOC);
        $rows = $db->query("SELECT * FROM {$quoted} ORDER BY 1,2")->fetch_all(MYSQLI_ASSOC);
        $table = $db->query("SELECT ENGINE,TABLE_COLLATION,AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}'")->fetch_assoc();
        $constraints = $db->query("SELECT CONSTRAINT_NAME,CONSTRAINT_TYPE FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}' AND CONSTRAINT_TYPE IN ('FOREIGN KEY','CHECK') ORDER BY BINARY CONSTRAINT_NAME")->fetch_all(MYSQLI_ASSOC);
        $state[$name] = compact('columns','indexes','rows','table','constraints');
    }
    return $state;
}

function iesAssertFinal(mysqli $db, string $prefix): void
{
    $expected = [
        'fm2_checklist_revisions' => [
            'columns'=>['installation_case_id|bigint(20) unsigned|NO|NULL||NEVER|NULL','revision_no|bigint(20) unsigned|NO|0||NEVER|NULL','updated_at|varchar(40)|NO|NULL||NEVER|NULL'],
            'indexes'=>['PRIMARY|0|1|installation_case_id|NULL|A|BTREE|NO'],
        ],
        'fm2_checklist_operations' => [
            'columns'=>['id|bigint(20) unsigned|NO|NULL|auto_increment|NEVER|NULL','installation_case_id|bigint(20) unsigned|NO|NULL||NEVER|NULL','client_operation_id|char(36)|NO|NULL||NEVER|NULL','device_installation_id|char(36)|NO|NULL||NEVER|NULL','operation_type|varchar(40)|NO|NULL||NEVER|NULL','section_id|tinyint(3) unsigned|NO|NULL||NEVER|NULL','item_id|smallint(5) unsigned|YES|NULL||NEVER|NULL','actor_user_id|bigint(20) unsigned|NO|NULL||NEVER|NULL','device_time|varchar(40)|NO|NULL||NEVER|NULL','server_received_at|varchar(40)|NO|NULL||NEVER|NULL','base_revision|bigint(20) unsigned|NO|NULL||NEVER|NULL','accepted_revision|bigint(20) unsigned|NO|NULL||NEVER|NULL','payload_json|text|NO|NULL||NEVER|NULL','template_snapshot_id|bigint(20) unsigned|YES|NULL||NEVER|NULL','template_snapshot_version|varchar(80)|YES|NULL||NEVER|NULL','template_content_sha256|char(64)|YES|NULL||NEVER|NULL'],
            'indexes'=>['PRIMARY|0|1|id|NULL|A|BTREE|NO','client_operation_id|0|1|client_operation_id|NULL|A|BTREE|NO','installation_case_id|1|1|installation_case_id|NULL|A|BTREE|NO','installation_case_id|1|2|id|NULL|A|BTREE|NO'],
        ],
        'fm2_checklist_operation_installers' => [
            'columns'=>['client_operation_id|char(36)|NO|NULL||NEVER|NULL','installer_tab_id|bigint(20) unsigned|NO|NULL||NEVER|NULL','fio_snapshot|varchar(300)|NO|NULL||NEVER|NULL','position_snapshot|varchar(300)|NO|NULL||NEVER|NULL','employment_status_snapshot|varchar(40)|NO|NULL||NEVER|NULL','dismissal_effective_at_snapshot|varchar(40)|YES|NULL||NEVER|NULL','workforce_source_updated_at_snapshot|varchar(40)|NO|NULL||NEVER|NULL','assignment_source|varchar(40)|NO|NULL||NEVER|NULL'],
            'indexes'=>['PRIMARY|0|1|client_operation_id|NULL|A|BTREE|NO','PRIMARY|0|2|installer_tab_id|NULL|A|BTREE|NO','installer_tab_id|1|1|installer_tab_id|NULL|A|BTREE|NO','installer_tab_id|1|2|client_operation_id|NULL|A|BTREE|NO'],
        ],
        'fm2_checklist_photos' => [
            'columns'=>['id|bigint(20) unsigned|NO|NULL|auto_increment|NEVER|NULL','installation_case_id|bigint(20) unsigned|NO|NULL||NEVER|NULL','section_id|tinyint(3) unsigned|NO|NULL||NEVER|NULL','upload_operation_id|char(36)|NO|NULL||NEVER|NULL','sha256|char(64)|NO|NULL||NEVER|NULL','mime_type|varchar(40)|NO|NULL||NEVER|NULL','byte_size|int(10) unsigned|NO|NULL||NEVER|NULL','original_name|varchar(255)|NO|NULL||NEVER|NULL','storage_name|varchar(255)|NO|NULL||NEVER|NULL','actor_user_id|bigint(20) unsigned|NO|NULL||NEVER|NULL','device_time|varchar(40)|NO|NULL||NEVER|NULL','server_received_at|varchar(40)|NO|NULL||NEVER|NULL','revoked_at|varchar(40)|YES|NULL||NEVER|NULL'],
            'indexes'=>['PRIMARY|0|1|id|NULL|A|BTREE|NO','installation_case_id|0|1|installation_case_id|NULL|A|BTREE|NO','installation_case_id|0|2|section_id|NULL|A|BTREE|NO','installation_case_id|0|3|sha256|NULL|A|BTREE|NO','installation_case_id_2|1|1|installation_case_id|NULL|A|BTREE|NO','installation_case_id_2|1|2|section_id|NULL|A|BTREE|NO','upload_operation_id|0|1|upload_operation_id|NULL|A|BTREE|NO'],
        ],
    ];
    $state = iesState($db, $prefix);
    foreach ($expected as $base => $manifest) {
        $actual = $state[$prefix . $base];
        $columns = array_map(static fn(array $c): string => implode('|', [$c['COLUMN_NAME'],$c['COLUMN_TYPE'],$c['IS_NULLABLE'],$c['COLUMN_DEFAULT'] === null ? 'NULL' : (string)$c['COLUMN_DEFAULT'],$c['EXTRA'],$c['IS_GENERATED'],$c['GENERATION_EXPRESSION'] === null ? 'NULL' : (string)$c['GENERATION_EXPRESSION']]), $actual['columns']);
        assertSameValue($manifest['columns'], $columns, "{$base} exact ordered column fingerprint.");
        $indexes = array_map(static fn(array $i): string => implode('|', [$i['INDEX_NAME'],$i['NON_UNIQUE'],$i['SEQ_IN_INDEX'],$i['COLUMN_NAME'],$i['SUB_PART'] === null ? 'NULL' : (string)$i['SUB_PART'],$i['COLLATION'],$i['INDEX_TYPE'],$i['IGNORED']]), $actual['indexes']);
        assertSameValue($manifest['indexes'], $indexes, "{$base} exact ordered index fingerprint.");
        assertSameValue('InnoDB', $actual['table']['ENGINE'], "{$base} exact engine.");
        assertSameValue('utf8mb4_unicode_ci', $actual['table']['TABLE_COLLATION'], "{$base} database-default collation.");
        assertSameValue([], $actual['constraints'], "{$base} has no FK/CHECK.");
        foreach ($actual['columns'] as $column) if ($column['CHARACTER_SET_NAME'] !== null) {
            assertSameValue('utf8mb4', $column['CHARACTER_SET_NAME'], "{$base}.{$column['COLUMN_NAME']} charset.");
            assertSameValue('utf8mb4_unicode_ci', $column['COLLATION_NAME'], "{$base}.{$column['COLUMN_NAME']} collation.");
        }
    }
}

function iesAssertRuntimeDoesNotOwnDdl(): void
{
    $root = dirname(__DIR__, 2); $violations = [];
    foreach (['app/PilotHttp', 'rapid-pilot', 'public'] as $area) {
        $path = $root . '/' . $area;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') continue;
            $source = (string) file_get_contents($file->getPathname());
            if (preg_match('/\\b(?:CREATE|ALTER|DROP|RENAME|TRUNCATE)\\s+TABLE\\b[^;]*(?:fm2_checklist_revisions|fm2_checklist_operations|fm2_checklist_operation_installers|fm2_checklist_photos)/is', $source) === 1) {
                $violations[] = substr($file->getPathname(), strlen($root) + 1);
            }
        }
    }
    sort($violations, SORT_STRING);
    assertSameValue([], $violations, 'Runtime and rapid-pilot must not own inspection-evidence DDL.');
}

/** @return list<string> */
function iesExpectedAffected(string $prefix, array $forms, string $wanted): array
{
    $bases=['revisions'=>'fm2_checklist_revisions','operations'=>'fm2_checklist_operations','installers'=>'fm2_checklist_operation_installers','photos'=>'fm2_checklist_photos'];$out=[];
    foreach($forms as $kind=>$form) if(($wanted==='created'&&$form==='absent')||($wanted==='upgraded'&&$form==='predecessor'))$out[]=$prefix.$bases[$kind];
    sort($out,SORT_STRING);return$out;
}

function iesCreateForm(mysqli $db,string $prefix,string $kind,string $form):void
{
    if($form==='absent')return;
    match($kind){
        'revisions'=>iesCreateRevisions($db,$prefix),
        'operations'=>iesCreateOperations($db,$prefix,$form==='predecessor'),
        'installers'=>iesCreateInstallers($db,$prefix,$form==='predecessor'),
        'photos'=>iesCreatePhotos($db,$prefix),
    };
}

function iesAssertCompatibleProduct(mysqli $db):void
{
    $n=0;
    foreach(['absent','final'] as $revisions)foreach(['absent','final'] as $photos)foreach(['absent','predecessor','final'] as $operations)foreach(['absent','predecessor','final'] as $installers){
        $prefix='p'.str_pad((string)$n++,2,'0',STR_PAD_LEFT).'_';$forms=compact('revisions','operations','installers','photos');
        foreach($forms as$kind=>$form)iesCreateForm($db,$prefix,$kind,$form);
        $id=sprintf('55555555-5555-4555-8555-%012d',$n);if($revisions!=='absent')$db->query('INSERT INTO '.iesQuote($prefix.'fm2_checklist_revisions')." VALUES(7,3,'rev-{$n}')");if($operations!=='absent'){$tail=$operations==='predecessor'?'':',NULL,NULL,NULL';$db->query('INSERT INTO '.iesQuote($prefix.'fm2_checklist_operations')." VALUES(11,7,'{$id}','66666666-6666-4666-8666-666666666666','item_completed',1,1,2,'device','server',2,3,'{\"case\":{$n}}'{$tail})");$db->query('ALTER TABLE '.iesQuote($prefix.'fm2_checklist_operations').' AUTO_INCREMENT=80');}if($installers!=='absent'){$tail=$installers==='predecessor'?'':",'completion'";$db->query('INSERT INTO '.iesQuote($prefix.'fm2_checklist_operation_installers')." VALUES('{$id}',42,'ФИО-{$n}','Должность-{$n}','employed',NULL,'updated-{$n}'{$tail})");}if($photos!=='absent'){$db->query('INSERT INTO '.iesQuote($prefix.'fm2_checklist_photos')." VALUES(21,7,1,'77777777-7777-4777-8777-".str_pad((string)$n,12,'0',STR_PAD_LEFT)."','".str_repeat('c',64)."','image/png',4,'x.png','x.bin',2,'device','server',NULL)");$db->query('ALTER TABLE '.iesQuote($prefix.'fm2_checklist_photos').' AUTO_INCREMENT=90');}$before=iesState($db,$prefix);
        $result=iesApply($db,$prefix);$created=iesExpectedAffected($prefix,$forms,'created');$upgraded=iesExpectedAffected($prefix,$forms,'upgraded');
        assertSameValue(['applied'=>$created!==[]||$upgraded!==[],'schemaVersion'=>8,'tablesCreated'=>$created,'tablesUpgraded'=>$upgraded],$result,"G2-03 compatible product case {$prefix} exact result.");
        iesAssertFinal($db,$prefix);
        $after=iesState($db,$prefix);foreach($forms as$kind=>$form)if($form!=='absent'){$base=['revisions'=>'fm2_checklist_revisions','operations'=>'fm2_checklist_operations','installers'=>'fm2_checklist_operation_installers','photos'=>'fm2_checklist_photos'][$kind];$old=$before[$prefix.$base]['rows'][0];$new=$after[$prefix.$base]['rows'][0];foreach($old as$key=>$value)assertSameValue($value,$new[$key],"G2-03 {$prefix}{$base}.{$key} sentinel byte preserved.");if(in_array($kind,['operations','photos'],true))assertSameValue((string)$before[$prefix.$base]['table']['AUTO_INCREMENT'],(string)$after[$prefix.$base]['table']['AUTO_INCREMENT'],"G2-03 {$prefix}{$base} allocator preserved.");}
    }
    assertSameValue(36,$n,'G2-03 exhausts all 36 compatible states.');
}

function iesAssertMalformedAndMetadataConflicts(mysqli $db):void
{
    $columns=['template_snapshot_id BIGINT UNSIGNED NULL','template_snapshot_version VARCHAR(80) NULL','template_content_sha256 CHAR(64) NULL'];
    for($mask=1;$mask<7;$mask++){
        $prefix='sub'.$mask.'_';iesCreateOperations($db,$prefix,true);
        for($i=0;$i<3;$i++)if(($mask&(1<<$i))!==0)$db->query('ALTER TABLE '.iesQuote($prefix.'fm2_checklist_operations').' ADD COLUMN '.$columns[$i]);
        $before=iesState($db,$prefix);
        assertSameValue(['applied'=>false,'schemaVersion'=>8,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$prefix.'fm2_checklist_operations']],iesApply($db,$prefix),"G2-05 partial subset {$mask} conflicts.");
        assertSameValue($before,iesState($db,$prefix),"G2-05 partial subset {$mask} is not repaired.");
    }
    foreach(['insttype_'=>'ALTER TABLE %s MODIFY assignment_source VARCHAR(39) NOT NULL','instdef_'=>"ALTER TABLE %s MODIFY assignment_source VARCHAR(40) NOT NULL DEFAULT 'pilot_backfill_current_order'",'instpos_'=>'ALTER TABLE %s MODIFY assignment_source VARCHAR(40) NOT NULL FIRST']as$prefix=>$sql){iesCreateInstallers($db,$prefix,false);$target=$prefix.'fm2_checklist_operation_installers';$db->query(sprintf($sql,iesQuote($target)));$before=iesState($db,$prefix);assertSameValue(['applied'=>false,'schemaVersion'=>8,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$target]],iesApply($db,$prefix),"G2-05 malformed installer {$prefix}.");assertSameValue($before,iesState($db,$prefix),"G2-05 {$prefix} zero repair.");}
    $mutations=[
      'ctype_'=>'ALTER TABLE %s MODIFY updated_at VARCHAR(39) NOT NULL',
      'null_'=>'ALTER TABLE %s MODIFY updated_at VARCHAR(40) NULL',
      'def_'=>"ALTER TABLE %s MODIFY updated_at VARCHAR(40) NOT NULL DEFAULT 'NULL'",
      'extra_'=>'ALTER TABLE %s ADD extra_column INT NULL',
      'reorder_'=>'ALTER TABLE %s MODIFY updated_at VARCHAR(40) NOT NULL AFTER installation_case_id',
      'gen_'=>'ALTER TABLE %s DROP updated_at, ADD updated_at VARCHAR(40) AS (CAST(revision_no AS CHAR)) VIRTUAL',
      'genexpr_'=>'ALTER TABLE %s DROP updated_at, ADD updated_at VARCHAR(40) AS (CONCAT(revision_no,\'-x\')) VIRTUAL',
      'engine_'=>'ALTER TABLE %s ENGINE=MyISAM',
      'tcol_'=>'ALTER TABLE %s DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci',
      'ccol_'=>'ALTER TABLE %s MODIFY updated_at VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL',
      'iname_'=>'ALTER TABLE %s DROP PRIMARY KEY, ADD UNIQUE KEY wrong_name(installation_case_id)',
      'iadd_'=>'ALTER TABLE %s ADD INDEX extra_index(revision_no)',
      'isub_'=>'ALTER TABLE %s ADD INDEX prefix_index(updated_at(4))',
      'check_'=>'ALTER TABLE %s ADD CONSTRAINT extra_check CHECK(revision_no>=0)',
    ];
    foreach($mutations as$prefix=>$sql){iesCreateRevisions($db,$prefix);$target=$prefix.'fm2_checklist_revisions';$db->query(sprintf($sql,iesQuote($target)));$before=iesState($db,$prefix);assertSameValue(['applied'=>false,'schemaVersion'=>8,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$target]],iesApply($db,$prefix),"G2-06..09 {$prefix} conflict.");assertSameValue($before,iesState($db,$prefix),"{$prefix} zero mutation.");}
    foreach(['extraonly_'=>'ALTER TABLE %s MODIFY id BIGINT UNSIGNED NOT NULL','iorder_'=>'ALTER TABLE %s DROP INDEX installation_case_id, ADD INDEX installation_case_id(id,installation_case_id)','iunique_'=>'ALTER TABLE %s DROP INDEX installation_case_id, ADD UNIQUE INDEX installation_case_id(installation_case_id,id)','idir_'=>'ALTER TABLE %s DROP INDEX installation_case_id, ADD INDEX installation_case_id(installation_case_id DESC,id)','ivis_'=>'ALTER TABLE %s ALTER INDEX installation_case_id IGNORED','itype_'=>'ALTER TABLE %s DROP INDEX client_operation_id, ADD FULLTEXT INDEX client_operation_id(client_operation_id)','iremove_'=>'ALTER TABLE %s DROP INDEX installation_case_id']as$prefix=>$sql){iesCreateOperations($db,$prefix,false);$target=$prefix.'fm2_checklist_operations';$db->query(sprintf($sql,iesQuote($target)));$before=iesState($db,$prefix);assertSameValue(['applied'=>false,'schemaVersion'=>8,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$target]],iesApply($db,$prefix),"G2-08 {$prefix} conflict.");assertSameValue($before,iesState($db,$prefix),"G2-08 {$prefix} zero mutation.");}
    iesCreateRevisions($db,'fk_');$db->query('CREATE TABLE fk_parent(id BIGINT UNSIGNED PRIMARY KEY) ENGINE=InnoDB');$db->query('ALTER TABLE fk_fm2_checklist_revisions ADD CONSTRAINT extra_fk FOREIGN KEY(installation_case_id) REFERENCES fk_parent(id)');$before=iesState($db,'fk_');assertSameValue(['applied'=>false,'schemaVersion'=>8,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>['fk_fm2_checklist_revisions']],iesApply($db,'fk_'),'G2-09 FK conflicts.');assertSameValue($before,iesState($db,'fk_'),'G2-09 FK zero mutation.');
}

function iesAssertPredecessorVariants(mysqli $db):void
{
    foreach([['op_empty_',true,false,false],['op_rows_',true,false,true],['in_empty_',false,true,false],['in_rows_',false,true,true],['both_empty_',true,true,false],['both_rows_',true,true,true]] as[$prefix,$op,$installer,$populated]){
        iesCreateRevisions($db,$prefix);iesCreatePhotos($db,$prefix);iesCreateOperations($db,$prefix,$op);iesCreateInstallers($db,$prefix,$installer);
        if($populated){$id='33333333-3333-4333-8333-'.str_pad((string)crc32($prefix),12,'0',STR_PAD_LEFT);$id=substr($id,0,36);$db->query("INSERT INTO ".iesQuote($prefix.'fm2_checklist_operations')."(id,installation_case_id,client_operation_id,device_installation_id,operation_type,section_id,item_id,actor_user_id,device_time,server_received_at,base_revision,accepted_revision,payload_json".($op?'':',template_snapshot_id,template_snapshot_version,template_content_sha256').") VALUES(7,1,'{$id}','44444444-4444-4444-8444-444444444444','item_completed',1,1,2,'d','s',0,1,'{}'".($op?'':",NULL,NULL,NULL").")");$db->query('ALTER TABLE '.iesQuote($prefix.'fm2_checklist_operations').' AUTO_INCREMENT=70');if($installer)$db->query("INSERT INTO ".iesQuote($prefix.'fm2_checklist_operation_installers')." VALUES('{$id}',42,'ФИО','Должность','employed',NULL,'updated')");}
        $before=iesState($db,$prefix);$result=iesApply($db,$prefix);$expected=[];if($installer)$expected[]=$prefix.'fm2_checklist_operation_installers';if($op)$expected[]=$prefix.'fm2_checklist_operations';sort($expected,SORT_STRING);assertSameValue(['applied'=>$expected!==[],'schemaVersion'=>8,'tablesCreated'=>[],'tablesUpgraded'=>$expected],$result,"G2-04 {$prefix} exact upgrade list.");iesAssertFinal($db,$prefix);
        if($populated){$after=iesState($db,$prefix);assertSameValue('70',(string)$after[$prefix.'fm2_checklist_operations']['table']['AUTO_INCREMENT'],"{$prefix} allocator preserved.");assertSameValue(array_slice($before[$prefix.'fm2_checklist_operations']['rows'][0],0,13,true),array_slice($after[$prefix.'fm2_checklist_operations']['rows'][0],0,13,true),"{$prefix} legacy operation bytes preserved.");if($installer){$old=$before[$prefix.'fm2_checklist_operation_installers']['rows'][0];$new=$after[$prefix.'fm2_checklist_operation_installers']['rows'][0];foreach($old as$key=>$value)assertSameValue($value,$new[$key],"G2-04 {$prefix} installer {$key} byte preserved.");assertSameValue('pilot_backfill_current_order',$new['assignment_source'],"G2-04 {$prefix} installer exact backfill.");}}
    }
}

function iesAssertOrderingSeam(mysqli $db):void
{
    $application='FMonitor2\\InstallationProcess\\CanonicalMigrationApplication';$noop=static fn(mysqli $_,string $__):array=>['applied'=>false];$v8Touched=false;$evidence=static function(mysqli $_,string $__)use(&$v8Touched):array{$v8Touched=true;return['applied'=>true];};
    foreach([[1=>$noop,2=>$noop,3=>$noop,4=>$noop,5=>$noop,6=>$noop,8=>$evidence],[1=>$noop,2=>$noop,4=>$noop,3=>$noop,5=>$noop,6=>$noop,7=>$noop,8=>$evidence],[1=>$noop,2=>$noop,3=>$noop,4=>$noop,5=>$noop,6=>$noop,7=>$noop,9=>$evidence]]as$i=>$catalogue){$v8Touched=false;$out=$application::run($db,'',$catalogue);assertSameValue(['exitCode'=>70,'result'=>['ok'=>false,'reason'=>'MIGRATION_FAILED']],$out,"G2-13 invalid catalogue {$i} rejected.");assertSameValue(false,$v8Touched,"G2-13 invalid catalogue {$i} stops before evidence.");}
}

function iesAssertRuntimePreconditions(mysqli $admin,string $host,int $port,string $database):void
{
    $root=dirname(__DIR__,2).'/.test-artifacts/ies-runtime-'.bin2hex(random_bytes(5));if(!mkdir($root,0700,true))throw new TestFailure('Runtime storage fixture must be created.');
    $runtimeUser='ies_runtime_'.bin2hex(random_bytes(4));$admin->query("CREATE USER '{$runtimeUser}'@'%' IDENTIFIED BY 'runtime-pass'");$admin->query("GRANT SELECT,INSERT,UPDATE,DELETE ON ".iesQuote($database).".* TO '{$runtimeUser}'@'%'");
    try{
        iesCreateRuntimeFixture($admin,'gold_');$runtime=new mysqli($host,$runtimeUser,'runtime-pass',$database,$port);$runtime->set_charset('utf8mb4');$sync=new FMonitor2\PilotHttp\ChecklistSync($runtime,'gold_',$root,'2026-09-01T12:00:01+03:00');$sync->ensureSchema();$actor=new FMonitor2\PilotHttp\HttpUser(901,'Verifier','verify@example.invalid');$item=['clientOperationId'=>'11111111-1111-4111-8111-111111111111','deviceInstallationId'=>'22222222-2222-4222-8222-222222222222','type'=>'item_completed','deviceTime'=>'2026-09-01T12:00:00+03:00','baseRevision'=>0,'sectionId'=>3,'itemId'=>1,'installerTabIds'=>['42']];assertSameValue(['status'=>'accepted','revision'=>1],$sync->accept(1701,$actor,$item),'G2-14 item succeeds under DML-only principal.');$bytes=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',true);$hash=hash('sha256',$bytes);$photo=['clientOperationId'=>'33333333-3333-4333-8333-333333333333','deviceInstallationId'=>'22222222-2222-4222-8222-222222222222','type'=>'photo_uploaded','deviceTime'=>'2026-09-01T12:00:00+03:00','baseRevision'=>1,'sectionId'=>3,'sha256'=>$hash,'mime'=>'image/png','size'=>strlen($bytes),'originalName'=>'evidence.png'];assertSameValue(['status'=>'accepted','revision'=>2],$sync->accept(1701,$actor,$photo,$bytes),'G2-14 photo succeeds under DML-only principal.');$projection=$sync->projection(1701);assertSameValue(2,$projection['revision'],'G2-14 successful sync projection observes both facts.');assertSameValue(true,is_file($root.'/checklist/'.$hash.'.bin'),'G2-14 accepted photo blob exists.');$runtime->close();
        foreach(['fm2_checklist_revisions','fm2_checklist_operations','fm2_checklist_operation_installers','fm2_checklist_photos']as$i=>$base)foreach(['absent','incompatible']as$mode){$prefix='rt'.$i.($mode==='absent'?'a_':'i_');iesCreateRuntimeFixture($admin,$prefix);if($mode==='absent')$admin->query('DROP TABLE '.iesQuote($prefix.$base));else$admin->query('ALTER TABLE '.iesQuote($prefix.$base).' ADD schema_drift INT NULL');$before=iesState($admin,$prefix);$files=iesFiles($root);$candidate=new FMonitor2\PilotHttp\ChecklistSync($admin,$prefix,$root,'2026-09-01T12:01:00+03:00');$attempt=$photo;$attempt['clientOperationId']=sprintf('44444444-4444-4444-8444-%012d',$i*2+($mode==='absent'?1:2));try{$candidate->ensureSchema();$candidate->accept(1701,$actor,$attempt,$bytes);throw new TestFailure("G2-15 {$base} {$mode} must fail closed.");}catch(FMonitor2\PilotHttp\PilotHttpInfrastructureUnavailable){}assertSameValue($before,iesState($admin,$prefix),"G2-15 {$base} {$mode} zero DML/repair.");assertSameValue($files,iesFiles($root),"G2-15 {$base} {$mode} attempted photo has zero filesystem persistence.");}
    }finally{$admin->query("DROP USER IF EXISTS '{$runtimeUser}'@'%'");iesRemoveTree($root);}
}

/** @return list<string> */
function iesFiles(string $root):array{$out=[];$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));foreach($it as$f)if($f->isFile())$out[]=substr($f->getPathname(),strlen($root)+1).'|'.hash_file('sha256',$f->getPathname());sort($out,SORT_STRING);return$out;}

function iesRemoveTree(string $root):void{if(!is_dir($root))return;$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);foreach($it as$f)$f->isDir()?@rmdir($f->getPathname()):@unlink($f->getPathname());@rmdir($root);}

function iesCreateRuntimeFixture(mysqli $db,string $prefix):void
{
    $ddl=['fm2_installation_cases'=>'id BIGINT PRIMARY KEY,legacy_installation_object_id BIGINT NOT NULL,process_state VARCHAR(80) NOT NULL','fm2_assignment_orders'=>'id BIGINT PRIMARY KEY,installation_case_id BIGINT NOT NULL,version_no INT NOT NULL,status VARCHAR(40) NOT NULL,control_engineer_user_id BIGINT NULL','fm2_order_installers'=>'assignment_order_id BIGINT NOT NULL,installer_tab_id BIGINT NOT NULL,fio_snapshot VARCHAR(300) NOT NULL,position_snapshot VARCHAR(300) NOT NULL,employment_status_snapshot VARCHAR(40) NOT NULL,employed_to_snapshot VARCHAR(40) NULL,workforce_source_updated_at_snapshot VARCHAR(40) NOT NULL,valid_to VARCHAR(40) NULL','fm2_workforce_catalog'=>'installer_tab_id BIGINT PRIMARY KEY,fio VARCHAR(300) NOT NULL,position VARCHAR(300) NOT NULL,employment_status VARCHAR(40) NOT NULL,dismissal_effective_at VARCHAR(40) NULL,workforce_source_updated_at VARCHAR(40) NOT NULL','fm2_checklist_template_snapshots'=>'id BIGINT PRIMARY KEY,snapshot_version VARCHAR(80) NOT NULL,valid_from DATETIME NOT NULL,content_sha256 CHAR(64) NOT NULL,payload_json TEXT NOT NULL','fm2_checklist_template_associations'=>'subject_kind VARCHAR(40) NOT NULL,subject_id VARCHAR(160) NOT NULL,effective_at DATETIME NOT NULL,template_snapshot_id BIGINT NOT NULL,template_snapshot_version VARCHAR(80) NOT NULL,template_content_sha256 CHAR(64) NOT NULL'];foreach($ddl as$name=>$definition)$db->query('CREATE TABLE '.iesQuote($prefix.$name)."({$definition}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $identity=FMonitor2\InstallationProcess\IdentityAccessDefinitionSchemaMigration::definitions($prefix,'utf8mb4_unicode_ci');foreach($identity as$definition)$db->query($definition['ddl']);
    $db->query("INSERT INTO ".iesQuote($prefix.'fm2_pilot_users')."(user_id,full_name,email,phone,status,activation_state,session_version,source_updated_at) VALUES(901,'Verifier','verify@example.invalid','',1,'active',1,'2026-09-01T12:00:00+03:00')");
    $db->query("INSERT INTO ".iesQuote($prefix.'fm2_pilot_roles')."(role_id,code,name,description,status,source_updated_at) VALUES(902,'inspection_fixture','Inspection fixture','Test fixture only',1,'2026-09-01T12:00:00+03:00')");
    $db->query("INSERT INTO ".iesQuote($prefix.'fm2_pilot_role_permissions')."(role_id,permission) VALUES(902,'inspection.item.complete')");
    $db->query("INSERT INTO ".iesQuote($prefix.'fm2_pilot_user_roles')."(user_id,role_id,origin,assigned_at,assigned_by_user_id) VALUES(901,902,'fixture','2026-09-01T12:00:00+03:00',NULL)");
    iesApply($db,$prefix);
    $db->query("INSERT INTO ".iesQuote($prefix.'fm2_checklist_revisions')." VALUES(71,0,'2026-09-01T12:00:00+03:00')");
    $hash=str_repeat('a',64);$payload='{"sections":[{"id":3,"items":[{"id":1}]}]}';$db->query("INSERT INTO ".iesQuote($prefix.'fm2_installation_cases')." VALUES(71,1701,'working')");$db->query("INSERT INTO ".iesQuote($prefix.'fm2_assignment_orders')." VALUES(81,71,1,'registered',NULL)");$db->query("INSERT INTO ".iesQuote($prefix.'fm2_order_installers')." VALUES(81,42,'Иванов','Электромеханик','employed',NULL,'2026-09-01',NULL)");$db->query("INSERT INTO ".iesQuote($prefix.'fm2_workforce_catalog')." VALUES(42,'Иванов','Электромеханик','employed',NULL,'2026-09-01')");$db->query("INSERT INTO ".iesQuote($prefix.'fm2_checklist_template_snapshots')." VALUES(91,'fixture-v1','2026-08-01 00:00:00','{$hash}','{$payload}')");$db->query("INSERT INTO ".iesQuote($prefix.'fm2_checklist_template_associations')." VALUES('operational_case','71','2026-08-02 00:00:00',91,'fixture-v1','{$hash}')");
}

function iesAssertDatabaseDefaults(mysqli $admin,string $host,int $port,string $user,string $password):void
{
    $uca='ies_uca_'.bin2hex(random_bytes(4));$latin='ies_latin_'.bin2hex(random_bytes(4));
    $admin->query('CREATE DATABASE '.iesQuote($uca).' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci');$admin->query('CREATE DATABASE '.iesQuote($latin).' DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci');
    try{$connection=new mysqli($host,$user,$password,$uca,$port);$connection->set_charset('utf8mb4');assertSameValue(true,iesApply($connection,'uca_')['applied'],'G2-12 documented UCA alias is applicable.');$state=iesState($connection,'uca_');foreach($state as$table)assertSameValue('utf8mb4_uca1400_ai_ci',$table['table']['TABLE_COLLATION'],'G2-12 exact UCA database default emitted.');$connection->close();$connection=new mysqli($host,$user,$password,$latin,$port);$connection->set_charset('utf8mb4');$before=iesState($connection,'');try{iesApply($connection,'');throw new TestFailure('G2-12 non-utf8mb4 database must fail preflight.');}catch(FMonitor2\InstallationProcess\DatabaseUnavailable){}assertSameValue($before,iesState($connection,''),'G2-12 invalid database charset has zero mutation.');$connection->close();}finally{$admin->query('DROP DATABASE IF EXISTS '.iesQuote($uca));$admin->query('DROP DATABASE IF EXISTS '.iesQuote($latin));}
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$host = getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('FMONITOR_TEST_DB_PORT') ?: '23306');
$user = getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root';
$password = getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local';
$database = 't_ies_001_' . bin2hex(random_bytes(6));
$admin = new mysqli($host, $user, $password, '', $port);
$admin->set_charset('utf8mb4');
$admin->query("CREATE DATABASE " . iesQuote($database) . " DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

try {
    $runner = iesRunRunner($database);
    assertSameValue(0, $runner['exitCode'], 'Canonical runner setup must succeed.');
    assertSameValue('', $runner['stderr'], 'Canonical runner setup keeps stderr empty.');
    $runnerResult = json_decode($runner['stdout'], true, flags: JSON_THROW_ON_ERROR);
    assertSameValue([1,2,3,4,5,6,7], array_slice($runnerResult['appliedVersions'], 0, 7), 'Landed prerequisites v1-v7 must apply before inspection evidence.');
    assertSameValue(11, $runnerResult['schemaVersion'], 'G2-01 canonical runner must own literal terminal v11 after proven v1-v7.');
    assertSameValue([1,2,3,4,5,6,7,8,9,10,11], $runnerResult['appliedVersions'], 'G2-01 runner ordering is exact.');
    iesAssertRuntimeDoesNotOwnDdl();

    $db = new mysqli($host, $user, $password, $database, $port); $db->set_charset('utf8mb4');
    iesAssertFinal($db, '');
    $db->query("INSERT INTO fm2_checklist_revisions VALUES(1,9,'sentinel')");
    $db->query("INSERT INTO fm2_checklist_operations VALUES(41,1,'55555555-5555-4555-8555-555555555555','66666666-6666-4666-8666-666666666666','item_completed',1,1,2,'d','s',8,9,'{}',NULL,NULL,NULL)");
    $db->query("INSERT INTO fm2_checklist_photos VALUES(51,1,1,'77777777-7777-4777-8777-777777777777','".str_repeat('a',64)."','image/png',4,'a.png','a.bin',2,'d','s',NULL)");
    $db->query('ALTER TABLE fm2_checklist_operations AUTO_INCREMENT=90');$db->query('ALTER TABLE fm2_checklist_photos AUTO_INCREMENT=100');
    $before = iesState($db, '');
    $repeat=iesRunRunner($database);assertSameValue(0,$repeat['exitCode'],'G2-02 repeat runner exits zero.');assertSameValue(['ok'=>true,'schemaVersion'=>11,'appliedVersions'=>[]],json_decode($repeat['stdout'],true,flags:JSON_THROW_ON_ERROR),'G2-02 repeat runner omits terminal migrations.');
    assertSameValue(['applied'=>false,'schemaVersion'=>8,'tablesCreated'=>[],'tablesUpgraded'=>[]], iesApply($db, ''), 'G2-02 direct seam exact repeat is a no-op.');
    assertSameValue($before, iesState($db, ''), 'G2-02 repeat preserves metadata, rows and allocators.');
    $db->query('DROP TABLE fm2_pilot_completion_fact_corrections');$db->query('DROP TABLE fm2_pilot_completion_facts');

    iesAssertCompatibleProduct($db);iesAssertPredecessorVariants($db);iesAssertMalformedAndMetadataConflicts($db);iesAssertOrderingSeam($db);iesAssertDatabaseDefaults($db,$host,$port,$user,$password);

    iesCreateOperations($db, 'up_', true); iesCreateInstallers($db, 'up_', true);
    $opId = '11111111-1111-4111-8111-111111111111';
    $db->query("INSERT INTO up_fm2_checklist_operations VALUES(41,7,'{$opId}','22222222-2222-4222-8222-222222222222','item_complete',2,3,9,'device','server',4,5,'{\"kept\":true}')");
    $db->query("INSERT INTO up_fm2_checklist_operation_installers VALUES('{$opId}',1042,'Иванов','Электромеханик','employed',NULL,'source-time')");
    $db->query('ALTER TABLE up_fm2_checklist_operations AUTO_INCREMENT=90');
    assertSameValue(true, iesApply($db, 'up_')['applied'], 'G2-04 both exact predecessors upgrade together.');
    iesAssertFinal($db, 'up_');
    $upgraded = iesState($db, 'up_');
    assertSameValue([null,null,null], array_values(array_intersect_key($upgraded['up_fm2_checklist_operations']['rows'][0], array_flip(['template_snapshot_id','template_snapshot_version','template_content_sha256']))), 'Operations backfill is exact SQL NULL.');
    assertSameValue('pilot_backfill_current_order', $upgraded['up_fm2_checklist_operation_installers']['rows'][0]['assignment_source'], 'Installer backfill literal is exact.');
    assertSameValue('90', (string) $upgraded['up_fm2_checklist_operations']['table']['AUTO_INCREMENT'], 'Operations allocator is preserved.');

    iesCreateOperations($db, 'bad_', true); iesCreateInstallers($db, 'bad_', false); iesCreatePhotos($db, 'bad_');
    $db->query("INSERT INTO bad_fm2_checklist_operations VALUES(61,5,'88888888-8888-4888-8888-888888888888','99999999-9999-4999-8999-999999999999','item_completed',1,1,2,'device','server',0,1,'{\"atomic\":true}')");
    $db->query("INSERT INTO bad_fm2_checklist_photos VALUES(71,5,1,'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa','".str_repeat('b',64)."','image/png',4,'b.png','b.bin',2,'device','server',NULL)");
    $db->query('ALTER TABLE bad_fm2_checklist_operations AUTO_INCREMENT=160');$db->query('ALTER TABLE bad_fm2_checklist_photos AUTO_INCREMENT=170');
    $db->query('ALTER TABLE bad_fm2_checklist_operation_installers ADD INDEX extra_snapshot(fio_snapshot)');
    $db->query('ALTER TABLE bad_fm2_checklist_photos DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
    $before = iesState($db, 'bad_');
    assertSameValue(['applied'=>false,'schemaVersion'=>8,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>['bad_fm2_checklist_operation_installers','bad_fm2_checklist_photos']], iesApply($db, 'bad_'), 'G2-10 all conflicts are binary ordered.');
    assertSameValue($before, iesState($db, 'bad_'), 'G2-10 conflict prevents missing create and predecessor upgrade.');

    iesCreateRevisions($db, 'decoy_'); $db->query('ALTER TABLE decoy_fm2_checklist_revisions ADD arbitrary INT NULL');
    $decoy = iesState($db, 'decoy_');
    assertSameValue(true, iesApply($db, 'target_')['applied'], 'G2-16 exact target prefix applies independently.');
    assertSameValue($decoy, iesState($db, 'decoy_'), 'G2-16 decoy family is byte-identical.');
    iesAssertFinal($db, 'target_');
    $prefix25=str_repeat('a',25);$prefixRun=iesRunRunner($database,$prefix25);assertSameValue(0,$prefixRun['exitCode'],'G2-11 25-byte prefix accepted.');iesAssertFinal($db,$prefix25);
    $prefix26=iesRunRunner($database,str_repeat('b',26),true);assertSameValue(['exitCode'=>64,'stdout'=>"{\"ok\":false,\"reason\":\"CONFIGURATION_INVALID\"}\n",'stderr'=>''],$prefix26,'G2-11 26-byte prefix rejected before deliberately unreachable DB access.');

    iesAssertRuntimePreconditions($db,$host,$port,$database);
    $db->query('ALTER TABLE fm2_checklist_revisions ADD unprefixed_drift INT NULL');iesCreateRevisions($db,'decoy2_');$db->query('ALTER TABLE decoy2_fm2_checklist_revisions ADD decoy_drift INT NULL');$unprefixed=iesState($db,'');$decoy2=iesState($db,'decoy2_');assertSameValue(true,iesApply($db,'isolated_')['applied'],'G2-16 compatible exact prefix applies.');assertSameValue($unprefixed,iesState($db,''),'G2-16 incompatible unprefixed family untouched.');assertSameValue($decoy2,iesState($db,'decoy2_'),'G2-16 incompatible decoy family untouched.');
    $db->close();
} finally {
    $admin->query('DROP DATABASE IF EXISTS ' . iesQuote($database));
    $admin->close();
}

echo "INSPECTION-EVIDENCE-SCHEMA-001 tests passed.\n";

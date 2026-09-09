<?php
declare(strict_types=1);

/** Test-only literal metadata oracle, independent of production definitions. */
function jobsAssertExtensionsSchema(mysqli $db, string $prefix): void
{
    $manifest = json_decode(file_get_contents(__DIR__.'/jobs_extensions_schema_manifest.json'), true, 512, JSON_THROW_ON_ERROR);
    foreach ($manifest['tables'] as $suffix => $expected) {
        $table = $prefix.$suffix;
        $info = $db->query("SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}'")->fetch_assoc();
        assertSameValue([$expected['engine'], $expected['collation']], [$info['ENGINE'] ?? null, $info['TABLE_COLLATION'] ?? null], $table.' exact storage');
        $columns = $db->query("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}' ORDER BY ORDINAL_POSITION")->fetch_all(MYSQLI_ASSOC);
        $actual = array_map(static fn(array $r): array => [
            'name'=>$r['COLUMN_NAME'],
            'type'=>preg_replace('/^(tinyint|smallint|int|bigint)\([0-9]+\)/', '$1', $r['COLUMN_TYPE']),
            'nullable'=>$r['IS_NULLABLE']==='YES',
            'default'=>$r['COLUMN_DEFAULT']==='NULL' ? null : $r['COLUMN_DEFAULT'],
            'extra'=>$r['EXTRA'], 'charset'=>$r['CHARACTER_SET_NAME'], 'collation'=>$r['COLLATION_NAME'],
        ], $columns);
        assertSameValue($expected['columns'], $actual, $table.' exact ordered columns');
        $indexes = [];
        foreach ($db->query("SELECT * FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}' ORDER BY INDEX_NAME,SEQ_IN_INDEX")->fetch_all(MYSQLI_ASSOC) as $r) {
            $name = $r['INDEX_NAME'];
            $indexes[$name] ??= ['name'=>$name, 'columns'=>[], 'unique'=>(int)$r['NON_UNIQUE']===0, 'type'=>$r['INDEX_TYPE']];
            $indexes[$name]['columns'][] = $r['COLUMN_NAME'];
            assertSameValue(null, $r['SUB_PART'], $table.' index has no column prefix truncation');
        }
        $wanted = [];
        foreach ($expected['indexes'] as $index) { $index['name']=str_replace('@prefix', $prefix, $index['name']); $wanted[$index['name']]=$index; }
        ksort($indexes); ksort($wanted);
        assertSameValue($wanted, $indexes, $table.' exact indexes');
        $foreignKeys = [];
        $sql = "SELECT k.CONSTRAINT_NAME,k.COLUMN_NAME,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,r.UPDATE_RULE,r.DELETE_RULE FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME WHERE k.TABLE_SCHEMA=DATABASE() AND k.TABLE_NAME='{$table}' AND k.REFERENCED_TABLE_SCHEMA=DATABASE() ORDER BY k.CONSTRAINT_NAME,k.ORDINAL_POSITION";
        foreach ($db->query($sql)->fetch_all(MYSQLI_ASSOC) as $r) {
            $name=$r['CONSTRAINT_NAME'];
            $foreignKeys[$name] ??= ['name'=>$name,'columns'=>[], 'target'=>$r['REFERENCED_TABLE_NAME'],'referenced'=>[], 'update'=>$r['UPDATE_RULE'],'delete'=>$r['DELETE_RULE']];
            $foreignKeys[$name]['columns'][]=$r['COLUMN_NAME']; $foreignKeys[$name]['referenced'][]=$r['REFERENCED_COLUMN_NAME'];
        }
        $wanted=[];
        foreach ($expected['foreignKeys'] as $fk) { $fk['name']=str_replace('@prefix',$prefix,$fk['name']); $fk['target']=$prefix.$fk['target']; $wanted[$fk['name']]=$fk; }
        ksort($foreignKeys); ksort($wanted);
        assertSameValue($wanted,$foreignKeys,$table.' exact non-cascading foreign keys');
        $referenceCount=(int)$db->query("SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}' AND REFERENCED_TABLE_NAME IS NOT NULL")->fetch_column();
        assertSameValue(array_sum(array_map(static fn(array $fk): int=>count($fk['columns']),$expected['foreignKeys'])), $referenceCount, $table.' has no additional cross-database foreign keys');
        $normalize=static function(string $value): string {
        $parts=preg_split("/('(?:''|\\\\.|[^'\\\\])*')/s",$value,-1,PREG_SPLIT_DELIM_CAPTURE);
        $normalized='';
        foreach($parts as $index=>$part)$normalized.=$index%2===1?$part:strtolower(preg_replace('/[\\s`]+/','',$part));
        while(str_starts_with($normalized,'(')&&str_ends_with($normalized,')')){
            $syntax=preg_replace("/'(?:''|\\\\.|[^'\\\\])*'/s",'?', $normalized);
            $depth=0;$outer=true;
            for($index=0,$last=strlen($syntax)-1;$index<=$last;$index++){
                if($syntax[$index]==='(')$depth++;
                elseif($syntax[$index]===')'&&--$depth===0&&$index!==$last){$outer=false;break;}
            }
            if(!$outer)break;
            $normalized=substr($normalized,1,-1);
        }
        return $normalized;
        };
        $actual=array_map($normalize,array_column($db->query("SELECT CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='{$table}'")->fetch_all(MYSQLI_ASSOC),'CHECK_CLAUSE'));
        $wanted=array_map($normalize,$expected['checks']); sort($actual); sort($wanted);
        assertSameValue($wanted,$actual,$table.' exact check expressions');
    }
}


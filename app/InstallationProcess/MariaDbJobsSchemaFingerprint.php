<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Read-only physical compatibility for the deployment-owned Jobs family. */
final class MariaDbJobsSchemaFingerprint
{
    public static function exists(\mysqli $db, string $table): bool
    {
        return self::rows($db,'TABLES','TABLE_NAME',$table)!==[];
    }
    public static function compatible(\mysqli $db, string $prefix, string $name, array $expected): bool
    {
        $table=$prefix.$name;
        $info=self::rows($db,'TABLES','TABLE_NAME',$table);
        if(count($info)!==1||$info[0]['ENGINE']!==$expected['engine']||$info[0]['TABLE_COLLATION']!==$expected['collation'])return false;
        $rows=self::rows($db,'COLUMNS','TABLE_NAME',$table,'ORDINAL_POSITION');
        $columns=[];
        foreach($rows as $r)$columns[]=[
            'name'=>$r['COLUMN_NAME'],'type'=>preg_replace('/^(tinyint|smallint|int|bigint)\([0-9]+\)/','$1',$r['COLUMN_TYPE']),
            'nullable'=>$r['IS_NULLABLE']==='YES','default'=>$r['COLUMN_DEFAULT']==='NULL'?null:$r['COLUMN_DEFAULT'],
            'extra'=>$r['EXTRA'],'charset'=>$r['CHARACTER_SET_NAME'],'collation'=>$r['COLLATION_NAME'],
        ];
        if($columns!==$expected['columns'])return false;
        $indexes=[];
        foreach(self::rows($db,'STATISTICS','TABLE_NAME',$table,'INDEX_NAME,SEQ_IN_INDEX') as $r){
            if($r['SUB_PART']!==null)return false;
            $key=$r['INDEX_NAME'];
            $indexes[$key]??=['name'=>$key,'columns'=>[],'unique'=>(int)$r['NON_UNIQUE']===0,'type'=>$r['INDEX_TYPE']];
            $indexes[$key]['columns'][]=$r['COLUMN_NAME'];
        }
        $wanted=[];
        foreach($expected['indexes'] as $index){$index['name']=str_replace('@prefix',$prefix,$index['name']);$wanted[$index['name']]=$index;}
        ksort($wanted);ksort($indexes);if($wanted!==$indexes)return false;
        $database=$db->query('SELECT DATABASE()')->fetch_column();$foreign=[];
        $rules=[];
        foreach(self::rows($db,'REFERENTIAL_CONSTRAINTS','TABLE_NAME',$table) as $r)$rules[$r['CONSTRAINT_NAME']]=$r;
        foreach(self::rows($db,'KEY_COLUMN_USAGE','TABLE_NAME',$table,'CONSTRAINT_NAME,ORDINAL_POSITION') as $r){
            if($r['REFERENCED_TABLE_NAME']===null)continue;
            if($r['REFERENCED_TABLE_SCHEMA']!==$database)return false;
            $key=$r['CONSTRAINT_NAME'];$rule=$rules[$key]??null;if($rule===null)return false;
            $foreign[$key]??=['name'=>$key,'columns'=>[],'target'=>$r['REFERENCED_TABLE_NAME'],'referenced'=>[],'update'=>$rule['UPDATE_RULE'],'delete'=>$rule['DELETE_RULE']];
            $foreign[$key]['columns'][]=$r['COLUMN_NAME'];$foreign[$key]['referenced'][]=$r['REFERENCED_COLUMN_NAME'];
        }
        $wanted=[];
        foreach($expected['foreignKeys'] as $fk){$fk['name']=str_replace('@prefix',$prefix,$fk['name']);$fk['target']=$prefix.$fk['target'];$wanted[$fk['name']]=$fk;}
        ksort($wanted);ksort($foreign);if($wanted!==$foreign)return false;
        $normalize=self::normalizeCheck(...);
        $actual=array_map($normalize,array_column(self::rows($db,'CHECK_CONSTRAINTS','TABLE_NAME',$table),'CHECK_CLAUSE'));
        $wanted=array_map($normalize,$expected['checks']);sort($actual);sort($wanted);
        return $actual===$wanted;
    }
    private static function normalizeCheck(string $value): string
    {
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
    }
    private static function rows(\mysqli $db,string $family,string $column,string $table,string $order=''): array
    {
        $schema=in_array($family,['REFERENTIAL_CONSTRAINTS','CHECK_CONSTRAINTS'],true)?'CONSTRAINT_SCHEMA':'TABLE_SCHEMA';
        $sql="SELECT * FROM information_schema.{$family} WHERE {$schema}=DATABASE() AND {$column}=?";
        if($order!=='')$sql.=' ORDER BY '.$order;
        $statement=$db->prepare($sql);$statement->bind_param('s',$table);$statement->execute();
        return $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

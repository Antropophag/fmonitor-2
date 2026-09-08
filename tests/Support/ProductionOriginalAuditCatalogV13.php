<?php

declare(strict_types=1);
use FMonitor2\Tests\Support\AssignmentOrderOriginalDatabaseSetupV1 as OriginalV2Contract;
require_once __DIR__.'/AssignmentOrderOriginalDatabaseSetupV1.php';

/** TEST-only extension from immutable v2 literals plus ATTEMPT-AUDIT-001 v0.4. */
final class ProductionOriginalAuditCatalogV13
{
    public static function columns():array
    {
        $result=ProductionMigrationRunnerCatalogContract::columnsV12();
        foreach(OriginalV2Contract::columnManifest() as $table=>$columns)foreach($columns as [$name,$type,$nullable,$charset,$collation,$default,$extra])
            $result[$table][]=[$name,preg_replace('/^(bigint|int|smallint|tinyint)\(\d+\)/','$1',$type),$nullable,$extra];
        return $result;
    }
    public static function charset(string $table,string $column,bool $textual):?string
    {
        foreach(OriginalV2Contract::columnManifest()[$table]??[] as $row)if($row[0]===$column)return $row[3];
        return $textual?'utf8mb4':null;
    }
    public static function indexes():array
    {
        $result=ProductionMigrationRunnerCatalogContract::indexesV12();
        foreach(OriginalV2Contract::keys() as $table=>$keys)foreach($keys as [$kind,$columns]){
            if($table==='fm2_assignment_order_original_audits'&&$kind==='UNIQUE')$kind='INDEX';
            $result[]=$table.'|'.$kind.'|'.$columns;
        }
        return $result;
    }
    public static function foreignKeys():array
    {
        $result=ProductionMigrationRunnerCatalogContract::foreignKeys();
        foreach(OriginalV2Contract::foreignKeys() as $table=>$keys)if($table!=='fm2_assignment_order_original_audits')foreach($keys as [$column,$ref,$refColumn,$rules])
            $result[]=$table.'|'.$column.'|'.$ref.'|'.$refColumn.'|'.explode('/',$rules)[1];
        sort($result,SORT_STRING);return $result;
    }
    public static function checks():array
    {
        $result=ProductionMigrationRunnerCatalogContract::checks();
        foreach($result as &$row)if($row['constraint']==='ck_fm2_process_user_capability'){
            $row['constraint']='ck_fm2_process_user_capability_v5';
            $row['clause']="capabilityin('assignment_order.prepare','assignment_order.confirm_registration','installation.open','construction_control_engineer','assignment_order.original.upload','assignment_order.original.correct')";
        }unset($row);
        foreach(OriginalV2Contract::checks() as $table=>$checks)foreach($checks as $check){
            if($table==='fm2_assignment_order_original_audits'){
                if($check==="statusin('accepted','rejected','conflict')")$check="statusin('accepted','rejected','conflict','failed')";
                elseif(str_contains($check,"status='accepted'"))$check=substr($check,0,-1)."or(status='failed'andreason_codein('stream_failure','storage_failure')))";
            }
            $result[]=['table'=>$table,'constraint'=>null,'clause'=>$check];
        }
        return self::sortChecks($result);
    }
    public static function sortChecks(array $checks):array
    {usort($checks,static fn($a,$b)=>[$a['table'],$a['clause'],$a['constraint']]<=>[$b['table'],$b['clause'],$b['constraint']]);return $checks;}
    public static function assertNormalizerSensitivity():void
    {
        foreach(['author ization_denied','author`ization_denied'] as $literal)
            assertSameValue("reason_code='".$literal."'",self::normalizeOriginal("`reason_code` = '".$literal."'"),'test-side CHECK normalizer preserves quoted bytes');
    }
    public static function normalizeOriginal(string $value):string
    {
$validateBooleanLexical=static function(string$value):void{$depth=0;$quoted=false;for($i=0,$length=strlen($value);$i<$length;$i++){$char=$value[$i];if($quoted){if($char==="\\"){if($i+1>=$length)throw new TestFailure('CHECK_NORMALIZATION_FAILURE: invalid lexical structure.');$i++;continue;}if($char==="'"){if($i+1<$length&&$value[$i+1]==="'"){$i++;continue;}$quoted=false;}continue;}if($char==="'"){$quoted=true;continue;}if($char==='('){$depth++;continue;}if($char===')'){if($depth===0)throw new TestFailure('CHECK_NORMALIZATION_FAILURE: invalid lexical structure.');$depth--;continue;}if($char===';')throw new TestFailure('CHECK_NORMALIZATION_FAILURE: invalid lexical structure.');}if($quoted||$depth!==0)throw new TestFailure('CHECK_NORMALIZATION_FAILURE: invalid lexical structure.');};
$foldUnquotedAscii=static function(string$value,bool$compact=false):string{$folded='';$quoted=false;for($i=0,$length=strlen($value);$i<$length;$i++){$char=$value[$i];if($quoted){$folded.=$char;if($char==="\\"&&$i+1<$length){$folded.=$value[++$i];continue;}if($char==="'"&&$i+1<$length&&$value[$i+1]==="'"){$folded.=$value[++$i];continue;}if($char==="'")$quoted=false;continue;}if($char==="'"){$quoted=true;$folded.=$char;continue;}if($char==='`'||($compact&&in_array($char,[' ',"\n","\r","\t"],true)))continue;$ord=ord($char);$folded.=$ord>=65&&$ord<=90?chr($ord+32):$char;}return$folded;};
$stripBooleanOuter=static function(string$value):string{while(str_starts_with($value,'(')&&str_ends_with($value,')')){$depth=0;$quote=false;$whole=true;for($i=0,$n=strlen($value);$i<$n;$i++){$char=$value[$i];if($quote){if($char==="\\"){$i++;continue;}if($char==="'"){if($i+1<$n&&$value[$i+1]==="'"){$i++;continue;}$quote=false;}continue;}if($char==="'"){$quote=true;continue;}if($char==='(')$depth++;elseif($char===')')$depth--;if($depth===0&&$i<$n-1){$whole=false;break;}if($depth<0)throw new TestFailure('CHECK_NORMALIZATION_FAILURE: unbalanced expression.');}if($quote||$depth!==0)throw new TestFailure('CHECK_NORMALIZATION_FAILURE: malformed expression.');if(!$whole)break;$value=trim(substr($value,1,-1));}return$value;};
$splitBoolean=static function(string$value,string$operator):array{$parts=[];$start=0;$depth=0;$quote=false;$between=false;for($i=0,$n=strlen($value);$i<$n;){$char=$value[$i];if($quote){if($char==="\\"){$i+=2;continue;}if($char==="'"){if($i+1<$n&&$value[$i+1]==="'"){$i+=2;continue;}$quote=false;}$i++;continue;}if($char==="'"){$quote=true;$i++;continue;}if($char==='('){$depth++;$i++;continue;}if($char===')'){$depth--;$i++;continue;}if($depth===0&&preg_match('/\G([a-z_][a-z0-9_]*)/A',$value,$match,0,$i)===1){$word=$match[1];$length=strlen($word);if($word==='between')$between=true;if($word===$operator){if($operator==='and'&&$between){$between=false;}else{$parts[]=trim(substr($value,$start,$i-$start));$start=$i+$length;}}$i+=$length;continue;}$i++;}if($parts===[])return[$value];$parts[]=trim(substr($value,$start));return$parts;};
$parseBoolean=null;
$parseBoolean=static function(string$value)use(&$parseBoolean,$stripBooleanOuter,$splitBoolean,$foldUnquotedAscii):array{$value=$stripBooleanOuter(trim($value));$or=$splitBoolean($value,'or');if(count($or)>1)return['or',array_map($parseBoolean,$or)];$and=$splitBoolean($value,'and');if(count($and)>1)return['and',array_map($parseBoolean,$and)];$atom=$foldUnquotedAscii($value,true);$atom=str_replace("'[[:cntrl:]/\\\\\\\\]'","'[[:cntrl:]/\\\\]'",$atom);$atom=(string)preg_replace("/!\\(([^()]+)regexp('[^']*')\\)/","$1notregexp$2",$atom);if($atom===''||str_contains($atom,';')||str_contains($atom,'xor'))throw new TestFailure('CHECK_NORMALIZATION_FAILURE: unsupported atom.');return['atom',$atom];};
$serializeBoolean=null;
$serializeBoolean=static function(array$node,bool$insideOr=false)use(&$serializeBoolean):string{if($node[0]==='atom')return$node[1];if(!in_array($node[0],['and','or'],true)||!isset($node[1])||count($node[1])<2)throw new TestFailure('CHECK_NORMALIZATION_FAILURE: unsupported AST.');if($node[0]==='and'){$value=implode('and',array_map(static fn(array$child):string=>$serializeBoolean($child,false),$node[1]));return$insideOr?'('.$value.')':$value;}return'('.implode('or',array_map(static fn(array$child):string=>$serializeBoolean($child,true),$node[1])).')';};
$normalizeCheck=static function(string$value)use($validateBooleanLexical,$foldUnquotedAscii,$serializeBoolean,$parseBoolean):string{$validateBooleanLexical($value);return$serializeBoolean($parseBoolean($foldUnquotedAscii($value)));};
        return $normalizeCheck($value);
    }
}

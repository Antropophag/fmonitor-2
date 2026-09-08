<?php

declare(strict_types=1);
namespace FMonitor2\InstallationProcess;
use FMonitor2\AssignmentOrderOriginal as O;

/** @internal Read-only recognition of the exact two audit schema generations. */
final class MariaDbOriginalAttemptAuditSchemaFingerprint
{
    public const AUDIT='fm2_assignment_order_original_audits';
    public const INDEX='idx_aoou_attempt_request';
    public static function statusCheck(): string
    { return "status IN ('accepted','rejected','conflict','failed')"; }
    public static function pairCheck(): string
    {
        $rejected="'authorization_denied','invalid_command','order_not_found','composition_not_confirmed','invalid_composition','file_too_large','not_pdf','invalid_pdf','unsafe_pdf','future_document_date','no_changes'";
        $conflict="'semantic_collision','stale_revision','target_not_found','target_not_current','initial_already_exists'";
        return "(status='accepted' AND reason_code IS NULL) OR (status='rejected' AND reason_code IN ({$rejected}))"
            ." OR (status='conflict' AND reason_code IN ({$conflict})) OR (status='failed' AND reason_code IN ('stream_failure','storage_failure'))";
    }
    public static function state(\mysqli $db,string $prefix): string
    {
        if(!O\AssignmentOrderOriginalSchemaMigration::exists($db,$prefix.self::AUDIT))return 'ABSENT';
        $actual=O\AssignmentOrderOriginalSchemaMigration::snap($db,$prefix.self::AUDIT,$prefix);
        if(($actual[0][1]??null)==='utf8mb4_uca1400_ai_ci')$actual[0][1]='utf8mb4_unicode_ci';
        $v2=O\AssignmentOrderOriginalSchemaMigration::expected(self::AUDIT);
        if($actual===$v2)return 'V2';
        $v3=$v2;
        $v3[2]=array_values(array_filter($v3[2],static fn($key)=>$key!==['UNIQUE','request_id,status,reason_code']));
        $v3[2][]=['INDEX','request_id,status,reason_code'];sort($v3[2]);$v3[3]=[];
        $v3[4]=array_values(array_filter($v3[4],static fn($check)=>!str_contains($check,'status')));
        $v3[4][]=O\MariaDbCheckCanonicalizer::normalize(self::statusCheck());
        $v3[4][]=O\MariaDbCheckCanonicalizer::normalize(self::pairCheck());sort($v3[4]);
        if($actual!==$v3)return 'CONFLICT';
        $indexes=self::rows($db,$prefix,"SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=? GROUP BY INDEX_NAME",[self::INDEX]);
        return count($indexes)===1?'V3':'CONFLICT';
    }
    public static function fullV3(\mysqli $db,string $prefix): bool
    {
        if(self::state($db,$prefix)!=='V3')return false;
        foreach(O\AssignmentOrderOriginalPhysicalNames::ALIASES as $logical=>$alias)
            if(strlen($prefix.$logical)<=64&&O\AssignmentOrderOriginalSchemaMigration::exists($db,$prefix.$alias))return false;
        foreach(O\AssignmentOrderOriginalDatabaseSetupV1::TABLES as $table) {
            if($table===self::AUDIT)continue;
            if(!O\AssignmentOrderOriginalSchemaMigration::exists($db,$prefix.$table)
                ||!O\AssignmentOrderOriginalSchemaMigration::same($db,$prefix,$table))return false;
        }
        return (ProcessCapabilityChecksClassifier::inspect($db,$prefix.'fm2_process_user_capabilities')['state']??null)==='v5';
    }
    /** @return array{index:string,foreignKey:string,checks:list<string>} */
    public static function alterationNames(\mysqli $db,string $prefix): array
    {
        $indexes=self::rows($db,$prefix,"SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND NON_UNIQUE=0 AND INDEX_NAME<>'PRIMARY' GROUP BY INDEX_NAME");
        $fks=self::rows($db,$prefix,'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND REFERENCED_TABLE_NAME IS NOT NULL');
        $checks=self::rows($db,$prefix,'SELECT CONSTRAINT_NAME,CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME=?');
        $names=[];foreach($checks as $check)if(str_contains(O\MariaDbCheckCanonicalizer::normalize($check['CHECK_CLAUSE']),'status'))$names[]=self::name($check['CONSTRAINT_NAME']);
        if(count($indexes)!==1||count($fks)!==1||count($names)!==2)throw new \RuntimeException('Audit schema metadata unavailable');
        return ['index'=>self::name($indexes[0]['INDEX_NAME']),'foreignKey'=>self::name($fks[0]['CONSTRAINT_NAME']),'checks'=>$names];
    }
    private static function name(string $name): string
    { if(preg_match('/^[A-Za-z0-9_$]{1,64}$/D',$name)!==1)throw new \RuntimeException('Audit identifier unavailable');return $name; }
    private static function rows(\mysqli $db,string $prefix,string $sql,array $extra=[]): array
    {
        $stmt=$db->prepare($sql);if(!$stmt)throw new \RuntimeException('Audit schema read unavailable');
        try{$values=[$prefix.self::AUDIT,...$extra];$stmt->bind_param(str_repeat('s',count($values)),...$values);if(!$stmt->execute())throw new \RuntimeException('Audit schema read unavailable');$result=$stmt->get_result();if(!$result)throw new \RuntimeException('Audit schema read unavailable');return $result->fetch_all(MYSQLI_ASSOC);}
        finally{$stmt->close();}
    }
}

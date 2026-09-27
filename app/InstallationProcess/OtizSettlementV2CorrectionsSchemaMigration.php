<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Additive acceptance frontier for stable, cumulative economic rights. */
final class OtizSettlementV2CorrectionsSchemaMigration
{
    public static function apply(\mysqli $db, string $prefix): array
    {
        if (preg_match('/^[A-Za-z0-9_]{0,25}$/D', $prefix) !== 1) throw new \InvalidArgumentException('INVALID_TABLE_PREFIX');
        $table = $prefix.'fm2_otiz_entitlement_frontiers';
        $exists = (int) $db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='".$db->real_escape_string($table)."'")->fetch_column();
        if ($exists === 0) {
            $db->query("CREATE TABLE `{$table}`(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,right_key VARCHAR(255) NOT NULL,claimed_cents BIGINT NOT NULL,updated_at VARCHAR(40) NOT NULL,PRIMARY KEY(id),UNIQUE KEY right_key(right_key),CONSTRAINT `".substr(hash('sha256', $prefix.'frontier_amount'), 0, 24)."` CHECK(claimed_cents>=0)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $claimsTable=$prefix.'fm2_otiz_entitlement_claims';
            $db->query("INSERT INTO `{$table}`(right_key,claimed_cents,updated_at) SELECT CONCAT_WS('|',object_id,source_kind,source_id,entitlement_kind),SUM(gross_cents),'migration-v37' FROM `{$claimsTable}` WHERE active=1 GROUP BY object_id,source_kind,source_id,entitlement_kind");
        }
        $events = $prefix.'fm2_otiz_admission_events';
        $eventsExist = (int) $db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='".$db->real_escape_string($events)."'")->fetch_column();
        if ($eventsExist === 0) {
            $db->query("CREATE TABLE `{$events}`(generation BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,object_id BIGINT UNSIGNED NOT NULL,source_revision VARCHAR(120) NOT NULL,decision VARCHAR(24) NOT NULL,reason_code VARCHAR(80) NULL,observed_at VARCHAR(40) NOT NULL,incident_id VARCHAR(120) NULL,operation_id CHAR(36) NULL,producer_user_id BIGINT UNSIGNED NULL,evidence_json LONGTEXT NULL,PRIMARY KEY(generation),UNIQUE KEY operation_id(operation_id),KEY object_generation(object_id,generation),KEY object_observed(object_id,observed_at,generation),CONSTRAINT `".substr(hash('sha256', $prefix.'admission_event_decision'), 0, 24)."` CHECK(decision IN('allow','blocked','unknown')),CONSTRAINT `".substr(hash('sha256', $prefix.'admission_event_reason'), 0, 24)."` CHECK(decision<>'blocked' OR (reason_code IS NOT NULL AND CHAR_LENGTH(TRIM(reason_code))>0))) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $inputs = $prefix.'fm2_otiz_admission_inputs';
            $db->query("INSERT INTO `{$events}`(object_id,source_revision,decision,reason_code,observed_at,incident_id,operation_id,producer_user_id,evidence_json) SELECT object_id,source_revision,decision,reason_code,observed_at,incident_id,operation_id,producer_user_id,evidence_json FROM `{$inputs}` ORDER BY observed_at,object_id,source_revision");
        }
        $claimsTable=$prefix.'fm2_otiz_entitlement_claims';
        $db->query("INSERT INTO `{$table}`(right_key,claimed_cents,updated_at) SELECT CONCAT_WS('|',object_id,source_kind,source_id,entitlement_kind),SUM(gross_cents),'migration-v37' FROM `{$claimsTable}` WHERE active=1 GROUP BY object_id,source_kind,source_id,entitlement_kind ON DUPLICATE KEY UPDATE claimed_cents=VALUES(claimed_cents),updated_at=VALUES(updated_at)");
        $frontierReconciled=$db->affected_rows;
        $inputs=$prefix.'fm2_otiz_admission_inputs';
        $db->query("INSERT INTO `{$events}`(object_id,source_revision,decision,reason_code,observed_at,incident_id,operation_id,producer_user_id,evidence_json) SELECT i.object_id,i.source_revision,i.decision,i.reason_code,i.observed_at,i.incident_id,i.operation_id,i.producer_user_id,i.evidence_json FROM `{$inputs}` i WHERE NOT EXISTS(SELECT 1 FROM `{$events}` e WHERE (i.operation_id IS NOT NULL AND BINARY e.operation_id=BINARY i.operation_id) OR (i.operation_id IS NULL AND e.object_id=i.object_id AND BINARY e.source_revision=BINARY i.source_revision AND BINARY e.observed_at=BINARY i.observed_at)) ORDER BY i.observed_at,i.object_id,i.source_revision");
        $eventsReconciled=$db->affected_rows;
        $reasonConstraint=substr(hash('sha256',$prefix.'admission_event_reason'),0,24);
        $reasonClause=$db->query("SELECT cc.CHECK_CLAUSE FROM information_schema.TABLE_CONSTRAINTS tc JOIN information_schema.CHECK_CONSTRAINTS cc ON cc.CONSTRAINT_SCHEMA=tc.CONSTRAINT_SCHEMA AND cc.CONSTRAINT_NAME=tc.CONSTRAINT_NAME WHERE tc.CONSTRAINT_SCHEMA=DATABASE() AND tc.TABLE_NAME='".$db->real_escape_string($events)."' AND tc.CONSTRAINT_NAME='".$db->real_escape_string($reasonConstraint)."' AND tc.CONSTRAINT_TYPE='CHECK'")->fetch_column();
        $reasonStrong=is_string($reasonClause)&&stripos(str_replace('`','',$reasonClause),'reason_code is not null')!==false;
        if(!$reasonStrong){if($reasonClause!==false)$db->query("ALTER TABLE `{$events}` DROP CONSTRAINT `{$reasonConstraint}`");$db->query("ALTER TABLE `{$events}` ADD CONSTRAINT `{$reasonConstraint}` CHECK(decision<>'blocked' OR (reason_code IS NOT NULL AND CHAR_LENGTH(TRIM(reason_code))>0))");}
        $frontierMismatch=(int)$db->query("SELECT COUNT(*) FROM (SELECT CONCAT_WS('|',object_id,source_kind,source_id,entitlement_kind) right_key,SUM(gross_cents) claimed_cents FROM `{$claimsTable}` WHERE active=1 GROUP BY object_id,source_kind,source_id,entitlement_kind) c LEFT JOIN `{$table}` f ON BINARY f.right_key=BINARY c.right_key WHERE f.id IS NULL OR f.claimed_cents<>c.claimed_cents")->fetch_column();
        $missingEvents=(int)$db->query("SELECT COUNT(*) FROM `{$inputs}` i WHERE NOT EXISTS(SELECT 1 FROM `{$events}` e WHERE (i.operation_id IS NOT NULL AND BINARY e.operation_id=BINARY i.operation_id) OR (i.operation_id IS NULL AND e.object_id=i.object_id AND BINARY e.source_revision=BINARY i.source_revision AND BINARY e.observed_at=BINARY i.observed_at))")->fetch_column();
        if($frontierMismatch!==0||$missingEvents!==0)throw new \RuntimeException('OTIZ_V37_RECONCILIATION_FAILED');
        $claims = $prefix.'fm2_otiz_entitlement_claims';
        $expression = (string) $db->query("SELECT GENERATION_EXPRESSION FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='".$db->real_escape_string($claims)."' AND COLUMN_NAME='active_claim_key'")->fetch_column();
        $upgraded = !str_contains(strtolower($expression), 'calculation_id');
        if ($upgraded) $db->query("ALTER TABLE `{$claims}` MODIFY active_claim_key CHAR(64) GENERATED ALWAYS AS (IF(active=1,SHA2(CONCAT_WS('|',calculation_id,object_id,source_kind,source_id,entitlement_kind),256),NULL)) STORED");
        $created=[];if($exists===0)$created[]=$table;if($eventsExist===0)$created[]=$events;
        return ['applied'=>$created!==[] || $upgraded || $frontierReconciled>0 || $eventsReconciled>0 || !$reasonStrong,'schemaVersion'=>37,'tablesCreated'=>$created,'columnsChanged'=>$upgraded ? [$claims.'.active_claim_key'] : []];
    }
}

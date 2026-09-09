<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

use yii\db\Connection;

/** Explicit deployment seam for the OTIZ settlement ledger. Never called by runtime. */
final class OtizSettlementSchemaMigration
{
    public static function apply(Connection $db, string $prefix): void
    {
        if (preg_match('/^[A-Za-z0-9_]{0,25}$/D', $prefix) !== 1) throw new \InvalidArgumentException('INVALID_TABLE_PREFIX');
        $db->createCommand("CREATE TABLE IF NOT EXISTS `{$prefix}fm2_otiz_settlement_locks`(object_id BIGINT UNSIGNED NOT NULL,PRIMARY KEY(object_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")->execute();
        $db->createCommand("CREATE TABLE IF NOT EXISTS `{$prefix}fm2_otiz_settlement_operations`(actor_user_id BIGINT UNSIGNED NOT NULL,operation_id CHAR(36) NOT NULL,request_sha256 CHAR(64) NOT NULL,status VARCHAR(40) NOT NULL,result_json LONGTEXT NOT NULL,created_at VARCHAR(40) NOT NULL,PRIMARY KEY(actor_user_id,operation_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")->execute();
    }
}

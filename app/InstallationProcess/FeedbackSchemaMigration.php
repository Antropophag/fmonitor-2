<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class FeedbackSchemaMigration
{
    public static function apply(\mysqli $db, string $prefix = ""): array
    {
        if (preg_match('/^[A-Za-z0-9_]{0,25}$/D', $prefix) !== 1) {
            throw new \InvalidArgumentException("Invalid prefix.");
        }
        $names = ["fm2_feedback", "fm2_feedback_results"];
        $missing = [];
        $conflicts = [];
        foreach ($names as $name) {
            $table = $prefix . $name;
            $exists =
                (int) $db
                    ->query(
                        "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='" .
                            $db->real_escape_string($table) .
                            "'",
                    )
                    ->fetch_column() > 0;
            if (!$exists) {
                $missing[] = $name;
            } elseif (!self::compatible($db, $prefix, $name)) {
                $conflicts[] = $table;
            }
        }
        if ($conflicts !== []) {
            return [
                "applied" => false,
                "schemaVersion" => 25,
                "reason" => "SCHEMA_MIGRATION_CONFLICT",
                "conflictingTables" => $conflicts,
            ];
        }
        $collation = IdentityAccessDefinitionSchemaMigration::databaseCollation($db);
        $created = [];
        foreach ($missing as $name) {
            $table = $prefix . $name;
            $db->query(self::ddl($table, $prefix, $name, $collation));
            $created[] = $table;
        }
        return [
            "applied" => $created !== [],
            "schemaVersion" => 25,
            "tablesCreated" => $created,
        ];
    }
    private static function ddl(string $table, string $prefix, string $name, string $collation): string
    {
        if ($name === "fm2_feedback") {
            return "CREATE TABLE `$table` (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,request_id CHAR(36) NOT NULL,request_fingerprint CHAR(64) NOT NULL,actor_user_id BIGINT UNSIGNED NOT NULL,description TEXT NOT NULL,page_path VARCHAR(255) NOT NULL,object_id BIGINT UNSIGNED NULL,app_version VARCHAR(80) NOT NULL,created_at DATETIME(6) NOT NULL,PRIMARY KEY(id),UNIQUE KEY `{$prefix}feedback_uq_request`(actor_user_id,request_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=$collation";
        }
        return "CREATE TABLE `$table` (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,feedback_id BIGINT UNSIGNED NOT NULL,request_id CHAR(36) NOT NULL,request_fingerprint CHAR(64) NOT NULL,actor_user_id BIGINT UNSIGNED NOT NULL,result TEXT NOT NULL,created_at DATETIME(6) NOT NULL,PRIMARY KEY(id),UNIQUE KEY `{$prefix}feedback_results_uq_request`(actor_user_id,request_id),KEY `{$prefix}feedback_results_ix_feedback`(feedback_id,id),CONSTRAINT `{$prefix}feedback_results_fk_feedback` FOREIGN KEY(feedback_id) REFERENCES `{$prefix}fm2_feedback`(id) ON UPDATE RESTRICT ON DELETE RESTRICT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=$collation";
    }
    private static function compatible(\mysqli $db, string $prefix, string $name): bool
    {
        $table = $db->real_escape_string($prefix . $name);
        $properties = $db
            ->query("SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$table'")
            ->fetch_row();
        $collation = IdentityAccessDefinitionSchemaMigration::databaseCollation($db);
        if ($properties !== ["InnoDB", $collation]) {
            return false;
        }
        $columns = $db
            ->query(
                "SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$table' ORDER BY ORDINAL_POSITION",
            )
            ->fetch_all(MYSQLI_NUM);
        $root = [
            ["id", "bigint(20) unsigned", "NO", "auto_increment"],
            ["request_id", "char(36)", "NO", ""],
            ["request_fingerprint", "char(64)", "NO", ""],
            ["actor_user_id", "bigint(20) unsigned", "NO", ""],
            ["description", "text", "NO", ""],
            ["page_path", "varchar(255)", "NO", ""],
            ["object_id", "bigint(20) unsigned", "YES", ""],
            ["app_version", "varchar(80)", "NO", ""],
            ["created_at", "datetime(6)", "NO", ""],
        ];
        $result = [
            ["id", "bigint(20) unsigned", "NO", "auto_increment"],
            ["feedback_id", "bigint(20) unsigned", "NO", ""],
            ["request_id", "char(36)", "NO", ""],
            ["request_fingerprint", "char(64)", "NO", ""],
            ["actor_user_id", "bigint(20) unsigned", "NO", ""],
            ["result", "text", "NO", ""],
            ["created_at", "datetime(6)", "NO", ""],
        ];
        if ($columns !== ($name === "fm2_feedback" ? $root : $result)) {
            return false;
        }
        $textColumns =
            $name === "fm2_feedback"
                ? ["request_id", "request_fingerprint", "description", "page_path", "app_version"]
                : ["request_id", "request_fingerprint", "result"];
        $metadata = $db
            ->query(
                "SELECT COLUMN_NAME,COLUMN_DEFAULT,CHARACTER_SET_NAME,COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$table' ORDER BY ORDINAL_POSITION",
            )
            ->fetch_all(MYSQLI_ASSOC);
        foreach ($metadata as $column) {
            if (!in_array($column["COLUMN_DEFAULT"], [null, "NULL"], true)) {
                return false;
            }
            $text = in_array($column["COLUMN_NAME"], $textColumns, true);
            if (
                ($text && [$column["CHARACTER_SET_NAME"], $column["COLLATION_NAME"]] !== ["utf8mb4", $collation]) ||
                (!$text && ($column["CHARACTER_SET_NAME"] !== null || $column["COLLATION_NAME"] !== null))
            ) {
                return false;
            }
        }
        $indexes = $db
            ->query(
                "SELECT NON_UNIQUE,GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) c FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$table' GROUP BY INDEX_NAME,NON_UNIQUE ORDER BY BINARY INDEX_NAME",
            )
            ->fetch_all(MYSQLI_NUM);
        $expected =
            $name === "fm2_feedback"
                ? [["0", "id"], ["0", "actor_user_id,request_id"]]
                : [["0", "id"], ["1", "feedback_id,id"], ["0", "actor_user_id,request_id"]];
        if ($indexes !== $expected) {
            return false;
        }
        $fks = $db
            ->query(
                "SELECT k.COLUMN_NAME,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,r.UPDATE_RULE,r.DELETE_RULE FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME WHERE k.TABLE_SCHEMA=DATABASE() AND k.TABLE_NAME='$table' AND k.REFERENCED_TABLE_NAME IS NOT NULL",
            )
            ->fetch_all(MYSQLI_NUM);
        return $fks === ($name === "fm2_feedback" ? [] : [["feedback_id", $prefix . "fm2_feedback", "id", "RESTRICT", "RESTRICT"]]);
    }
}

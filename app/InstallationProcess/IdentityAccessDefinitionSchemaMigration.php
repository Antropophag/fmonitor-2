<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

/** Literal v6 catalogue and compatibility fingerprint implementation. */
final class IdentityAccessDefinitionSchemaMigration
{
    private const TABLES = [
        'fm2_pilot_users',
        'fm2_pilot_roles',
        'fm2_pilot_role_permissions',
        'fm2_pilot_user_roles',
        'fm2_pilot_auth_credentials',
        'fm2_pilot_invitations',
        'fm2_pilot_user_role_events',
        'fm2_pilot_auth_attempts',
        'fm2_pilot_user_status_events',
    ];

    public static function tables(): array { return self::TABLES; }

    public static function assertPrefix(string $prefix): void
    {
        if (strlen($prefix) > 25 || preg_match('/^[A-Za-z0-9_]*$/D', $prefix) !== 1) {
            throw new \InvalidArgumentException('Invalid table prefix.');
        }
    }

    public static function databaseCollation(\mysqli $connection): string
    {
        $defaults = $connection->query('SELECT DEFAULT_CHARACTER_SET_NAME,DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=DATABASE()')->fetch_assoc();
        $charset = (string) ($defaults['DEFAULT_CHARACTER_SET_NAME'] ?? '');
        $collation = (string) ($defaults['DEFAULT_COLLATION_NAME'] ?? '');
        if ($charset !== 'utf8mb4' || preg_match('/^[A-Za-z0-9_]+$/D', $collation) !== 1) {
            throw new DatabaseUnavailable('Database default is not a safe utf8mb4 collation.');
        }

        $escapedCollation = $connection->real_escape_string($collation);
        $exact = $connection->query("SELECT COUNT(*) AS n FROM information_schema.COLLATIONS WHERE COLLATION_NAME='{$escapedCollation}' AND CHARACTER_SET_NAME='utf8mb4'")->fetch_assoc();
        $member = (int) ($exact['n'] ?? 0) === 1;
        if (!$member && str_starts_with($collation, 'utf8mb4_')) {
            $alias = substr($collation, strlen('utf8mb4_'));
            $escapedAlias = $connection->real_escape_string($alias);
            $aliasRow = $connection->query("SELECT COUNT(*) AS n FROM information_schema.COLLATIONS WHERE COLLATION_NAME='{$escapedAlias}' AND CHARACTER_SET_NAME IS NULL")->fetch_assoc();
            $member = (int) ($aliasRow['n'] ?? 0) === 1;
        }
        if (!$member) {
            throw new DatabaseUnavailable('Database default collation is not registered for utf8mb4.');
        }

        try {
            $connection->query("SELECT _utf8mb4'identity-access-collation-trial' COLLATE `{$collation}`")->fetch_assoc();
        } catch (\Throwable $error) {
            throw new DatabaseUnavailable('Database default collation cannot be applied to utf8mb4.', 0, $error);
        }
        return $collation;
    }

    public static function matches(\mysqli $connection, string $table, array $expected, string $collation): bool
    {
        $escaped = $connection->real_escape_string($table);
        $properties = MariaDbSchemaInspector::tableProperties($connection, $table);
        if ($properties === null || $properties['ENGINE'] !== 'InnoDB' || $properties['TABLE_COLLATION'] !== $collation) {
            return false;
        }
        $columns = $connection->query("SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA,CHARACTER_SET_NAME,COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}' ORDER BY ORDINAL_POSITION")->fetch_all(MYSQLI_ASSOC);
        $columnSignatures = array_map(static fn (array $row): string => implode('|', [
            $row['COLUMN_NAME'], $row['COLUMN_TYPE'], $row['IS_NULLABLE'],
            $row['COLUMN_DEFAULT'] === null ? 'NULL' : (string) $row['COLUMN_DEFAULT'],
            $row['EXTRA'], $row['CHARACTER_SET_NAME'] ?? 'NULL', $row['COLLATION_NAME'] ?? 'NULL',
        ]), $columns);
        if ($columnSignatures !== $expected['columns']) {
            return false;
        }
        $indexes = $connection->query("SELECT NON_UNIQUE,INDEX_NAME,SEQ_IN_INDEX,COLUMN_NAME,INDEX_TYPE FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}' ORDER BY INDEX_NAME,SEQ_IN_INDEX")->fetch_all(MYSQLI_ASSOC);
        if (IdentityAccessSemanticFingerprintSchemaMigration::indexes($indexes) !== IdentityAccessSemanticFingerprintSchemaMigration::indexSignatures($expected['indexes'])) {
            return false;
        }
        $foreignKeys = $connection->query("SELECT k.CONSTRAINT_NAME,k.COLUMN_NAME,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,r.DELETE_RULE,r.UPDATE_RULE FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME WHERE k.TABLE_SCHEMA=DATABASE() AND k.TABLE_NAME='{$escaped}' AND k.REFERENCED_TABLE_NAME IS NOT NULL ORDER BY k.CONSTRAINT_NAME,k.ORDINAL_POSITION")->fetch_all(MYSQLI_ASSOC);
        return IdentityAccessSemanticFingerprintSchemaMigration::foreignKeys($foreignKeys) === IdentityAccessSemanticFingerprintSchemaMigration::foreignKeySignatures($expected['foreignKeys']);
    }

    public static function definitions(string $prefix, string $collation): array
    {
        $fk = static fn (string $empty, string $short): string => $prefix === '' ? $empty : 'fk_' . $prefix . $short;
        $tail = " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE `{$collation}`";
        $ddls = [
            'fm2_pilot_users' => "CREATE TABLE `{$prefix}fm2_pilot_users`(user_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,full_name VARCHAR(300) NOT NULL,email VARCHAR(254) NOT NULL,phone VARCHAR(100) NOT NULL DEFAULT '',status TINYINT(1) NOT NULL DEFAULT 1,activation_state ENUM('invited','active','blocked') NOT NULL,session_version INT UNSIGNED NOT NULL DEFAULT 1,source_updated_at VARCHAR(40) NOT NULL,PRIMARY KEY(user_id),UNIQUE KEY uq_ia_users_email(email),KEY ix_ia_users_status_name(status,full_name)){$tail}",
            'fm2_pilot_roles' => "CREATE TABLE `{$prefix}fm2_pilot_roles`(role_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,code VARCHAR(64) NOT NULL,name VARCHAR(300) NOT NULL,description VARCHAR(500) NOT NULL,status TINYINT(1) NOT NULL,source_updated_at VARCHAR(40) NOT NULL,PRIMARY KEY(role_id),UNIQUE KEY uq_ia_roles_code(code)){$tail}",
            'fm2_pilot_role_permissions' => "CREATE TABLE `{$prefix}fm2_pilot_role_permissions`(role_id BIGINT UNSIGNED NOT NULL,permission VARCHAR(100) NOT NULL,PRIMARY KEY(role_id,permission),CONSTRAINT `{$fk('fk_ia_role_permissions_role','ia_rp_role')}` FOREIGN KEY(role_id) REFERENCES `{$prefix}fm2_pilot_roles`(role_id) ON DELETE CASCADE ON UPDATE RESTRICT){$tail}",
            'fm2_pilot_user_roles' => "CREATE TABLE `{$prefix}fm2_pilot_user_roles`(user_id BIGINT UNSIGNED NOT NULL,role_id BIGINT UNSIGNED NOT NULL,origin VARCHAR(40) NOT NULL,assigned_at VARCHAR(40) NOT NULL,assigned_by_user_id BIGINT UNSIGNED NULL DEFAULT NULL,PRIMARY KEY(user_id,role_id),KEY ix_ia_user_roles_role(role_id),CONSTRAINT `{$fk('fk_ia_user_roles_user','ia_ur_user')}` FOREIGN KEY(user_id) REFERENCES `{$prefix}fm2_pilot_users`(user_id) ON DELETE CASCADE ON UPDATE RESTRICT,CONSTRAINT `{$fk('fk_ia_user_roles_role','ia_ur_role')}` FOREIGN KEY(role_id) REFERENCES `{$prefix}fm2_pilot_roles`(role_id) ON DELETE RESTRICT ON UPDATE RESTRICT){$tail}",
            'fm2_pilot_auth_credentials' => "CREATE TABLE `{$prefix}fm2_pilot_auth_credentials`(user_id BIGINT UNSIGNED NOT NULL,email_normalized VARCHAR(254) NOT NULL,password_hash VARCHAR(255) NULL DEFAULT NULL,password_set_at VARCHAR(40) NULL DEFAULT NULL,updated_at VARCHAR(40) NOT NULL,PRIMARY KEY(user_id),UNIQUE KEY uq_ia_auth_credentials_email(email_normalized),CONSTRAINT `{$fk('fk_ia_auth_credentials_user','ia_ac_user')}` FOREIGN KEY(user_id) REFERENCES `{$prefix}fm2_pilot_users`(user_id) ON DELETE CASCADE ON UPDATE RESTRICT){$tail}",
            'fm2_pilot_invitations' => "CREATE TABLE `{$prefix}fm2_pilot_invitations`(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,user_id BIGINT UNSIGNED NOT NULL,token_hash BINARY(32) NOT NULL,expires_at DATETIME(6) NOT NULL,used_at DATETIME(6) NULL DEFAULT NULL,revoked_at DATETIME(6) NULL DEFAULT NULL,created_by_user_id BIGINT UNSIGNED NULL DEFAULT NULL,created_at DATETIME(6) NOT NULL,PRIMARY KEY(id),UNIQUE KEY uq_ia_invitations_token(token_hash),KEY ix_ia_invitations_user_expiry(user_id,expires_at),CONSTRAINT `{$fk('fk_ia_invitations_user','ia_inv_user')}` FOREIGN KEY(user_id) REFERENCES `{$prefix}fm2_pilot_users`(user_id) ON DELETE CASCADE ON UPDATE RESTRICT){$tail}",
            'fm2_pilot_user_role_events' => "CREATE TABLE `{$prefix}fm2_pilot_user_role_events`(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,user_id BIGINT UNSIGNED NOT NULL,role_id BIGINT UNSIGNED NOT NULL,action VARCHAR(40) NOT NULL,occurred_at VARCHAR(40) NOT NULL,actor_user_id BIGINT UNSIGNED NULL DEFAULT NULL,PRIMARY KEY(id),KEY ix_ia_user_role_events_user(user_id,id)){$tail}",
            'fm2_pilot_auth_attempts' => "CREATE TABLE `{$prefix}fm2_pilot_auth_attempts`(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,email_normalized VARCHAR(254) NOT NULL,succeeded TINYINT(1) NOT NULL,attempted_at DATETIME(6) NOT NULL,PRIMARY KEY(id),KEY ix_ia_auth_attempts_email_time(email_normalized,attempted_at)){$tail}",
            'fm2_pilot_user_status_events' => "CREATE TABLE `{$prefix}fm2_pilot_user_status_events`(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,user_id BIGINT UNSIGNED NOT NULL,action VARCHAR(40) NOT NULL,occurred_at VARCHAR(40) NOT NULL,actor_user_id BIGINT UNSIGNED NOT NULL,PRIMARY KEY(id),KEY ix_ia_user_status_events_user(user_id,id)){$tail}",
        ];

        $manifests = self::literalManifests($prefix, $collation, $fk);
        $result = [];
        foreach ($ddls as $name => $ddl) {
            $result[$name] = ['ddl' => $ddl, 'manifest' => $manifests[$name]];
        }
        return $result;
    }

    private static function literalManifests(string $prefix, string $collation, callable $fk): array
    {
        $c = static fn (string $name, string $type, string $null, ?string $default = null, string $extra = '', bool $character = false): string => implode('|', [$name,$type,$null,$default ?? 'NULL',$extra,$character?'utf8mb4':'NULL',$character?$collation:'NULL']);
        $i = static fn (int $nonUnique, string $name, int $sequence, string $column): string => "{$nonUnique}|{$name}|{$sequence}|{$column}|BTREE";
        $f = static fn (string $name, string $column, string $target, string $targetColumn, string $delete): string => "{$name}|{$column}|{$target}|{$targetColumn}|{$delete}|RESTRICT";
        $m = static fn (array $columns, array $indexes, array $foreignKeys = []): array => ['columns'=>$columns,'indexes'=>$indexes,'foreignKeys'=>$foreignKeys];
        return [
            'fm2_pilot_users'=>$m([$c('user_id','bigint(20) unsigned','NO',null,'auto_increment'),$c('full_name','varchar(300)','NO',null,'',true),$c('email','varchar(254)','NO',null,'',true),$c('phone','varchar(100)','NO',"''",'',true),$c('status','tinyint(1)','NO','1'),$c('activation_state',"enum('invited','active','blocked')",'NO',null,'',true),$c('session_version','int(10) unsigned','NO','1'),$c('source_updated_at','varchar(40)','NO',null,'',true)],[$i(1,'ix_ia_users_status_name',1,'status'),$i(1,'ix_ia_users_status_name',2,'full_name'),$i(0,'PRIMARY',1,'user_id'),$i(0,'uq_ia_users_email',1,'email')]),
            'fm2_pilot_roles'=>$m([$c('role_id','bigint(20) unsigned','NO',null,'auto_increment'),$c('code','varchar(64)','NO',null,'',true),$c('name','varchar(300)','NO',null,'',true),$c('description','varchar(500)','NO',null,'',true),$c('status','tinyint(1)','NO'),$c('source_updated_at','varchar(40)','NO',null,'',true)],[$i(0,'PRIMARY',1,'role_id'),$i(0,'uq_ia_roles_code',1,'code')]),
            'fm2_pilot_role_permissions'=>$m([$c('role_id','bigint(20) unsigned','NO'),$c('permission','varchar(100)','NO',null,'',true)],[$i(0,'PRIMARY',1,'role_id'),$i(0,'PRIMARY',2,'permission')],[$f($fk('fk_ia_role_permissions_role','ia_rp_role'),'role_id',$prefix.'fm2_pilot_roles','role_id','CASCADE')]),
            'fm2_pilot_user_roles'=>$m([$c('user_id','bigint(20) unsigned','NO'),$c('role_id','bigint(20) unsigned','NO'),$c('origin','varchar(40)','NO',null,'',true),$c('assigned_at','varchar(40)','NO',null,'',true),$c('assigned_by_user_id','bigint(20) unsigned','YES')],[$i(1,'ix_ia_user_roles_role',1,'role_id'),$i(0,'PRIMARY',1,'user_id'),$i(0,'PRIMARY',2,'role_id')],[$f($fk('fk_ia_user_roles_role','ia_ur_role'),'role_id',$prefix.'fm2_pilot_roles','role_id','RESTRICT'),$f($fk('fk_ia_user_roles_user','ia_ur_user'),'user_id',$prefix.'fm2_pilot_users','user_id','CASCADE')]),
            'fm2_pilot_auth_credentials'=>$m([$c('user_id','bigint(20) unsigned','NO'),$c('email_normalized','varchar(254)','NO',null,'',true),$c('password_hash','varchar(255)','YES',null,'',true),$c('password_set_at','varchar(40)','YES',null,'',true),$c('updated_at','varchar(40)','NO',null,'',true)],[$i(0,'PRIMARY',1,'user_id'),$i(0,'uq_ia_auth_credentials_email',1,'email_normalized')],[$f($fk('fk_ia_auth_credentials_user','ia_ac_user'),'user_id',$prefix.'fm2_pilot_users','user_id','CASCADE')]),
            'fm2_pilot_invitations'=>$m([$c('id','bigint(20) unsigned','NO',null,'auto_increment'),$c('user_id','bigint(20) unsigned','NO'),$c('token_hash','binary(32)','NO'),$c('expires_at','datetime(6)','NO'),$c('used_at','datetime(6)','YES'),$c('revoked_at','datetime(6)','YES'),$c('created_by_user_id','bigint(20) unsigned','YES'),$c('created_at','datetime(6)','NO')],[$i(1,'ix_ia_invitations_user_expiry',1,'user_id'),$i(1,'ix_ia_invitations_user_expiry',2,'expires_at'),$i(0,'PRIMARY',1,'id'),$i(0,'uq_ia_invitations_token',1,'token_hash')],[$f($fk('fk_ia_invitations_user','ia_inv_user'),'user_id',$prefix.'fm2_pilot_users','user_id','CASCADE')]),
            'fm2_pilot_user_role_events'=>$m([$c('id','bigint(20) unsigned','NO',null,'auto_increment'),$c('user_id','bigint(20) unsigned','NO'),$c('role_id','bigint(20) unsigned','NO'),$c('action','varchar(40)','NO',null,'',true),$c('occurred_at','varchar(40)','NO',null,'',true),$c('actor_user_id','bigint(20) unsigned','YES')],[$i(1,'ix_ia_user_role_events_user',1,'user_id'),$i(1,'ix_ia_user_role_events_user',2,'id'),$i(0,'PRIMARY',1,'id')]),
            'fm2_pilot_auth_attempts'=>$m([$c('id','bigint(20) unsigned','NO',null,'auto_increment'),$c('email_normalized','varchar(254)','NO',null,'',true),$c('succeeded','tinyint(1)','NO'),$c('attempted_at','datetime(6)','NO')],[$i(1,'ix_ia_auth_attempts_email_time',1,'email_normalized'),$i(1,'ix_ia_auth_attempts_email_time',2,'attempted_at'),$i(0,'PRIMARY',1,'id')]),
            'fm2_pilot_user_status_events'=>$m([$c('id','bigint(20) unsigned','NO',null,'auto_increment'),$c('user_id','bigint(20) unsigned','NO'),$c('action','varchar(40)','NO',null,'',true),$c('occurred_at','varchar(40)','NO',null,'',true),$c('actor_user_id','bigint(20) unsigned','NO')],[$i(1,'ix_ia_user_status_events_user',1,'user_id'),$i(1,'ix_ia_user_status_events_user',2,'id'),$i(0,'PRIMARY',1,'id')]),
        ];
    }
}

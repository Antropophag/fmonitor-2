<?php
declare(strict_types=1);
namespace FMonitor2\demo;

use FMonitor2\InstallationProcess\CanonicalMigrationApplication;
use FMonitor2\InstallationProcess\ProductionPilotMigrationCatalogue;
use FMonitor2\InstallationProcess\PilotCaseImporter;
use mysqli;
use RuntimeException;

/** Owns fictional, disposable demo generations only; never used by runtime HTTP or imports. */
final class PilotDemoDatabase
{
    /** Canonical v24 table identities; status checks only read, never run migrations. */
    public const TABLES = [
        'fm2_pilot_otiz_snapshots', 'fm2_pilot_otiz_snapshot_objects', 'fm2_pilot_otiz_snapshot_allocations',
        'fm2_pilot_otiz_snapshot_issues', 'fm2_pilot_otiz_snapshot_evidence', 'fm2_pilot_otiz_payment_closures',
        'fm2_pilot_otiz_events', 'fm2_otiz_publications', 'fm2_otiz_settlement_locks',
        'fm2_otiz_settlement_operations', 'fm2_migrated_evidence_decisions',
        'fm2_migrated_evidence_projection', 'fm2_migrated_evidence_conflicts',
        'fm2_migrated_evidence_decision_state', 'fm2_migration_quarantine_decisions',
        'fm2_assignment_application_attempts', 'fm2_assignment_orders', 'fm2_assignment_order_applications',
        'fm2_assignment_order_identities', 'fm2_assignment_order_id_receipts', 'fm2_assignment_order_original_audits',
        'fm2_assignment_order_original_events', 'fm2_assignment_order_original_requests', 'fm2_assignment_order_original_revisions',
        'fm2_assignment_order_original_roots', 'fm2_assignment_order_selections', 'fm2_assignment_order_selection_audits',
        'fm2_assignment_order_selection_events', 'fm2_assignment_order_selection_members', 'fm2_assignment_order_selection_requests',
        'fm2_checklist_operations', 'fm2_checklist_operation_installers', 'fm2_checklist_photos', 'fm2_checklist_revisions',
        'fm2_checklist_template_associations', 'fm2_checklist_template_snapshots', 'fm2_installation_cases',
        'fm2_migration_classification_provenance', 'fm2_order_artifacts', 'fm2_order_installers',
        'fm2_original_maintenance_audits', 'fm2_original_maintenance_requests', 'fm2_pilot_auth_attempts',
        'fm2_pilot_auth_credentials', 'fm2_pilot_completion_facts', 'fm2_pilot_completion_fact_corrections',
        'fm2_pilot_inspection_schedules', 'fm2_pilot_inspection_schedule_events', 'fm2_pilot_invitations',
        'fm2_pilot_object_details', 'fm2_pilot_object_detail_quarantine', 'fm2_pilot_roles', 'fm2_pilot_role_permissions',
        'fm2_pilot_users', 'fm2_pilot_user_roles', 'fm2_pilot_user_role_events', 'fm2_pilot_user_status_events',
        'fm2_process_events', 'fm2_process_tasks', 'fm2_process_user_capabilities', 'fm2_workforce_catalog',
        'fm2_workforce_observations', 'fm2_workforce_sync_metadata', 'fm2_workforce_sync_runs',
        'fm2_jobs', 'fm2_job_events', 'fm2_outbox_intents', 'fm2_outbox_attempt_events',
        'fm2_scheduler_slots', 'fm2_worker_heartbeats', 'fm_maintable',
    ];

    public static function provision(mysqli $db, string $process, string $legacy, string $fingerprint, int $generation, string $nonce, string $now): void
    {
        if (preg_match('/^[0-9a-f]{8}$/D', $fingerprint) !== 1 || $generation < 1
            || $process !== "fm2d_{$fingerprint}_g{$generation}_" || $legacy !== $process
            || preg_match('/^[0-9a-f]{32}$/D', $nonce) !== 1) throw new RuntimeException('Invalid demo generation');
        foreach (array_unique([$process, $legacy]) as $prefix) {
            $query = $db->prepare('SELECT COUNT(*) n FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND LEFT(TABLE_NAME,CHAR_LENGTH(?))=?');
            $query->bind_param('ss', $prefix, $prefix); $query->execute();
            if ((int)$query->get_result()->fetch_assoc()['n'] !== 0) throw new RuntimeException('Demo generation is not empty');
        }
        $db->query("CREATE TABLE `{$legacy}fm_maintable` (id BIGINT UNSIGNED NOT NULL PRIMARY KEY,ordadr_address VARCHAR(500),entrance VARCHAR(80),regnumber VARCHAR(120),workdatestart VARCHAR(40),workdateendadjusted VARCHAR(40),plan_finish_date VARCHAR(40),workdatefinish VARCHAR(40),ptoactdate VARCHAR(40),responsstroicontrol VARCHAR(80)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->query("CREATE TABLE `{$legacy}users_roles` (id BIGINT UNSIGNED NOT NULL PRIMARY KEY,name VARCHAR(300) NOT NULL,status INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->query("CREATE TABLE `{$legacy}users` (id BIGINT UNSIGNED NOT NULL PRIMARY KEY,name VARCHAR(300) NOT NULL,email VARCHAR(300) NOT NULL,role_id BIGINT UNSIGNED NOT NULL,status INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->query("INSERT INTO `{$legacy}users_roles` VALUES(5,'ФКР',1),(8,'Строительный контроль',1)");
        $db->query("INSERT INTO `{$legacy}users` VALUES(18,'Сидоров Сергей Сергеевич','sidorov@shlz.ru',5,1),(73,'Анна Волкова','volkova@shlz.ru',8,1)");
        $db->query("INSERT INTO `{$legacy}fm_maintable` VALUES(4512,'Москва, ул. Примерная, д. 10','2','77-000123','2026-10-05','2026-12-20',NULL,NULL,NULL,'73'),(4999,'Москва, ул. Непилотная, д. 1','1','77-000999','2026-09-30','2026-12-01',NULL,NULL,NULL,'73')");
        $migration=CanonicalMigrationApplication::run($db,$process,ProductionPilotMigrationCatalogue::migrations());
        if($migration!==['exitCode'=>0,'result'=>['ok'=>true,'schemaVersion'=>24,'appliedVersions'=>range(1,24)]])throw new RuntimeException();
        $marker=$db->real_escape_string("fmonitor2-demo:{$fingerprint}:{$generation}:{$nonce}");
        $db->query("ALTER TABLE `{$process}fm2_installation_cases` COMMENT='{$marker}'");
        $db->query("ALTER TABLE `{$legacy}fm_maintable` COMMENT='{$marker}'");
        $db->query("INSERT INTO `{$process}fm2_process_user_capabilities` VALUES(18,'assignment_order.prepare',NULL),(18,'assignment_order.confirm_registration',NULL),(18,'installation.open',NULL),(73,'construction_control_engineer','Инженер строительного контроля')");
        $db->query("INSERT INTO `{$process}fm2_workforce_catalog` (installer_tab_id,fio,position,employment_status,employed_from,employed_to,workforce_source,workforce_source_updated_at,reconciliation_state,authority_system,delivery_system,delivery_person_id,last_successful_sync_at) VALUES(1042,'Иванов Иван Иванович','Электромеханик по лифтам','employed','2024-02-01',NULL,'one_c_zup_via_bitrix','2026-08-27T18:15:00+03:00','delivered','1c_zup','bitrix24',1042,'2026-08-27T18:15:00+03:00'),(2088,'Петров Пётр Петрович','Электромеханик по лифтам','employed','2025-01-10',NULL,'one_c_zup_via_bitrix','2026-08-27T18:15:00+03:00','delivered','1c_zup','bitrix24',2088,'2026-08-27T18:15:00+03:00')");
        $db->query("INSERT INTO `{$process}fm2_pilot_users`(user_id,full_name,email,status,activation_state,source_updated_at) VALUES(18,'Сидоров Сергей Сергеевич','sidorov@shlz.ru',1,'active','2026-08-29T12:00:00+03:00'),(73,'Анна Волкова','volkova@shlz.ru',1,'active','2026-08-29T12:00:00+03:00')");
        $db->query("INSERT INTO `{$process}fm2_pilot_roles`(role_id,code,name,description,status,source_updated_at) VALUES(1,'fkr_operator','ФКР','Synthetic demo role',1,'2026-08-29T12:00:00+03:00'),(2,'construction_control_engineer','Строительный контроль','Synthetic demo role',1,'2026-08-29T12:00:00+03:00')");
        $db->query("INSERT INTO `{$process}fm2_pilot_user_roles`(user_id,role_id,origin,assigned_at) VALUES(18,1,'bootstrap','2026-08-29T12:00:00+03:00'),(73,2,'bootstrap','2026-08-29T12:00:00+03:00')");
        foreach(['objects.read','assignment_order.prepare','assignment_order.composition.select','assignment_order.original.read','assignment_order.original.upload','assignment_order.original.correct','installation.open']as$permission)$db->query("INSERT INTO `{$process}fm2_pilot_role_permissions`(role_id,permission) VALUES(1,'".$db->real_escape_string($permission)."')");
        foreach(['objects.read','construction_control.read','checklist.read','checklist.edit','inspection.item.complete','inspection.photo.revoke']as$permission)$db->query("INSERT INTO `{$process}fm2_pilot_role_permissions`(role_id,permission) VALUES(2,'".$db->real_escape_string($permission)."')");
        $detail=json_encode(['schemaVersion'=>'technical-object-detail-v1','objectId'=>4512,'fields'=>[]],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);$detailSql=$db->real_escape_string($detail);$detailHash=hash('sha256',$detail);$db->query("INSERT INTO `{$process}fm2_pilot_object_details`(object_id,schema_version,content_sha256,payload_json,captured_at) VALUES(4512,'technical-object-detail-v1','$detailHash','$detailSql','2026-08-29T12:00:00+03:00')");
        $import = (new PilotCaseImporter($db, $process, $legacy))->import([4512], $now);
        if (($import['imported'] ?? null) !== [4512]) throw new RuntimeException();
    }

    /** Drop only a disposable namespace authenticated by both independent anchor comments. */
    public static function removeGeneration(mysqli $db, string $prefix, string $marker): bool
    {
        if (preg_match('/^fm2d_([0-9a-f]{8})_g([1-9][0-9]*)_$/D', $prefix, $parts) !== 1
            || preg_match('/^fmonitor2-demo:'. $parts[1] .':'. $parts[2] .':[0-9a-f]{32}$/D', $marker) !== 1) return false;
        foreach (['fm2_installation_cases', 'fm_maintable'] as $anchor) {
            $table = $prefix . $anchor;
            $query = $db->prepare('SELECT TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
            $query->bind_param('s', $table); $query->execute();
            if (($query->get_result()->fetch_assoc()['TABLE_COMMENT'] ?? null) !== $marker) return false;
        }
        $query = $db->prepare('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND LEFT(TABLE_NAME,CHAR_LENGTH(?))=?');
        $query->bind_param('ss', $prefix, $prefix); $query->execute();
        $tables = array_column($query->get_result()->fetch_all(MYSQLI_ASSOC), 'TABLE_NAME');
        $db->query('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach ($tables as $table) $db->query('DROP TABLE `'.str_replace('`', '``', $table).'`');
        } finally { $db->query('SET FOREIGN_KEY_CHECKS=1'); }
        return true;
    }

    public static function configuredActor(mysqli $db, string $prefix, string $email): int
    {
        if (preg_match('/^fm2d_[0-9a-f]{8}_g[1-9][0-9]*_$/D', $prefix) !== 1) throw new RuntimeException();
        $query = $db->prepare("SELECT user_id FROM `{$prefix}fm2_pilot_users` WHERE BINARY email=BINARY ? AND status=1 AND BINARY activation_state=BINARY 'active' LIMIT 2");
        $query->bind_param('s', $email); $query->execute(); $rows=$query->get_result()->fetch_all(MYSQLI_ASSOC);
        if (count($rows)!==1) throw new RuntimeException();
        return (int)$rows[0]['user_id'];
    }
}

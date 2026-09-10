<?php
declare(strict_types=1);

/** Structural controls retain names/counts where the defective reader was shallow. */
final class QueueReadinessFaults
{
    public static function cases(ObjectQueueFixture $f): array
    {
        $p=$f->p;$facts=$p.'fm2_pilot_completion_facts';$corrections=$p.'fm2_pilot_completion_fact_corrections';
        $ops=$p.'fm2_checklist_operations';$revisions=$p.'fm2_checklist_revisions';$events=$p.'fm2_pilot_inspection_schedule_events';
        $collation=(string)$f->db->query('SELECT @@collation_database')->fetch_column();
        $planCheck=$f->db->query("SELECT CONSTRAINT_NAME,CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='$events'")->fetch_assoc();
        $completionCheck=$f->db->query("SELECT CONSTRAINT_NAME,CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='$corrections' ORDER BY CONSTRAINT_NAME LIMIT 1")->fetch_assoc();
        $rootFk=(string)$f->db->query("SELECT CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='$corrections' AND REFERENCED_TABLE_NAME='$facts'")->fetch_column();
        assertSameValue(true,is_array($planCheck)&&is_array($completionCheck)&&$rootFk!=='','canonical constraints present');
        $replaceCheck=static fn(string $table,array $check,string $expression):string=>"ALTER TABLE $table DROP CONSTRAINT `{$check['CONSTRAINT_NAME']}`, ADD CONSTRAINT `{$check['CONSTRAINT_NAME']}` CHECK($expression)";
        $replaceFk=static fn(string $target,string $delete='RESTRICT'):array=>["ALTER TABLE $corrections DROP FOREIGN KEY `$rootFk`","ALTER TABLE $corrections ADD CONSTRAINT `$rootFk` FOREIGN KEY(root_fact_id) REFERENCES $target(id) ON UPDATE RESTRICT ON DELETE $delete"];
        $f->db->query("CREATE TABLE {$p}readiness_fk_decoy(id BIGINT UNSIGNED PRIMARY KEY) ENGINE=InnoDB");
        return [
            ['planning','same-count wrong CHECK',$replaceCheck($events,$planCheck,'CHAR_LENGTH(event_type)>0'),$replaceCheck($events,$planCheck,$planCheck['CHECK_CLAUSE'])],
            ['completion','same-name column type',"ALTER TABLE $facts MODIFY details VARCHAR(499) NOT NULL DEFAULT ''","ALTER TABLE $facts MODIFY details VARCHAR(500) NOT NULL DEFAULT ''"],
            ['completion','column nullable',"ALTER TABLE $facts MODIFY recorded_at VARCHAR(40) NULL","ALTER TABLE $facts MODIFY recorded_at VARCHAR(40) NOT NULL"],
            ['completion','column default',"ALTER TABLE $facts ALTER details SET DEFAULT 'changed'","ALTER TABLE $facts ALTER details SET DEFAULT ''"],
            ['completion','column collation',"ALTER TABLE $facts MODIFY details VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT ''","ALTER TABLE $facts MODIFY details VARCHAR(500) CHARACTER SET utf8mb4 COLLATE $collation NOT NULL DEFAULT ''"],
            ['completion','table collation',"ALTER TABLE $facts DEFAULT COLLATE utf8mb4_bin","ALTER TABLE $facts DEFAULT COLLATE $collation"],
            ['completion','index uniqueness',"ALTER TABLE $facts DROP INDEX uq_case_fact, ADD KEY uq_case_fact(installation_case_id,fact_type)","ALTER TABLE $facts DROP INDEX uq_case_fact, ADD UNIQUE KEY uq_case_fact(installation_case_id,fact_type)"],
            ['completion','same-count wrong CHECK',$replaceCheck($corrections,$completionCheck,'version_no>=0'),$replaceCheck($corrections,$completionCheck,$completionCheck['CHECK_CLAUSE'])],
            ['completion','same-count wrong FK target',$replaceFk($p.'readiness_fk_decoy'),$replaceFk($facts)],
            ['completion','same-count wrong FK rule',$replaceFk($facts,'CASCADE'),$replaceFk($facts)],
            ['evidence','same-name column type',"ALTER TABLE $ops MODIFY section_id SMALLINT UNSIGNED NOT NULL","ALTER TABLE $ops MODIFY section_id TINYINT UNSIGNED NOT NULL"],
            ['evidence','column extra auto_increment',"ALTER TABLE $ops MODIFY id BIGINT UNSIGNED NOT NULL","ALTER TABLE $ops MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT"],
            ['evidence','column nullable',"ALTER TABLE $ops MODIFY actor_user_id BIGINT UNSIGNED NULL","ALTER TABLE $ops MODIFY actor_user_id BIGINT UNSIGNED NOT NULL"],
            ['evidence','column default',"ALTER TABLE $ops ALTER base_revision SET DEFAULT 77","ALTER TABLE $ops ALTER base_revision DROP DEFAULT"],
            ['evidence','column charset',"ALTER TABLE $ops MODIFY device_time VARCHAR(40) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL","ALTER TABLE $ops MODIFY device_time VARCHAR(40) CHARACTER SET utf8mb4 COLLATE $collation NOT NULL"],
            ['evidence','column collation',"ALTER TABLE $ops MODIFY device_time VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL","ALTER TABLE $ops MODIFY device_time VARCHAR(40) CHARACTER SET utf8mb4 COLLATE $collation NOT NULL"],
            ['evidence','table engine',"ALTER TABLE $revisions ENGINE=MyISAM","ALTER TABLE $revisions ENGINE=InnoDB"],
            ['evidence','same-count wrong index columns',"ALTER TABLE $ops DROP INDEX installation_case_id, ADD KEY installation_case_id(item_id,id)","ALTER TABLE $ops DROP INDEX installation_case_id, ADD KEY installation_case_id(installation_case_id,id)"],
            ['evidence','ignored index',"ALTER TABLE $ops ALTER INDEX installation_case_id IGNORED","ALTER TABLE $ops ALTER INDEX installation_case_id NOT IGNORED"],
            ['evidence','extra CHECK',"ALTER TABLE $ops ADD CONSTRAINT {$p}readiness_extra_check CHECK(section_id>=0)","ALTER TABLE $ops DROP CONSTRAINT {$p}readiness_extra_check"],
            ['evidence','generated column',"ALTER TABLE $revisions DROP COLUMN revision_no, ADD COLUMN revision_no BIGINT UNSIGNED GENERATED ALWAYS AS(0) VIRTUAL AFTER installation_case_id","ALTER TABLE $revisions DROP COLUMN revision_no, ADD COLUMN revision_no BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER installation_case_id"],
        ];
    }

    public static function ddl(ObjectQueueFixture $f,string|array $sql): void
    {
        $table=$f->p.'fm2_pilot_completion_fact_corrections';
        $statements=is_array($sql)?$sql:[$sql];
        $correction=str_starts_with($statements[0],'ALTER TABLE '.$table.' ');
        $checks=(int)$f->db->query('SELECT @@SESSION.FOREIGN_KEY_CHECKS')->fetch_column();
        // Canonical migration deliberately removes a redundant composite FK index.
        // MariaDB COPY ALTER needs it temporarily; requests never see this setup index.
        if($correction)$f->db->query("ALTER TABLE $table ADD KEY readiness_fixture_support(previous_correction_id,root_fact_id,previous_version_no)");
        try {foreach($statements as$statement)$f->db->query($statement);}
        finally {
            if($correction){
                $f->db->query('SET FOREIGN_KEY_CHECKS=0');
                try{$f->db->query("ALTER TABLE $table DROP INDEX readiness_fixture_support");}
                finally{$f->db->query('SET FOREIGN_KEY_CHECKS='.$checks);}
            }
        }
        assertSameValue($checks,(int)$f->db->query('SELECT @@SESSION.FOREIGN_KEY_CHECKS')->fetch_column(),'fixture restores FK checking');
    }

    public static function ready(ObjectQueueFixture $f,string $family): bool
    {
        return match($family) {
            'planning'=>FMonitor2\InstallationProcess\InspectionPlanningSchemaMigration::isCompleteCompatible($f->db,$f->p),
            'completion'=>FMonitor2\InstallationProcess\InstallationCompletionDetailsSchemaMigration::isCompleteCompatible($f->db,$f->p),
            'evidence'=>FMonitor2\InstallationProcess\InspectionPhotoContentIndexSchemaMigration::isCompleteCompatible($f->db,$f->p),
        };
    }

    public static function verify(ObjectQueueFixture $f,array &$cookies): void
    {
        $failures=[];
        foreach(self::cases($f) as [$family,$label,$break,$restore]) {
            assertSameValue(true,self::ready($f,$family),'fixture ready before '.$family.' '.$label);
            try{self::ddl($f,$break);}catch(Throwable $error){throw new TestFailure('SETUP_FAILURE '.$family.' '.$label.': '.$error->getMessage());}
            try {
                assertSameValue(false,self::ready($f,$family),'old public predicate detects '.$family.' '.$label);
                $before=$f->facts();$unavailable=false;
                try{$f->queue()->read(9101,'','',1);}catch(Throwable){$unavailable=true;}
                $response=$f->http->request('GET','/pilot/objects',[],$cookies);
                assertSameValue($before,$f->facts(),'query and HTTP never repair '.$family.' '.$label);
                if(!$unavailable||$response['status']!==503) {
                    $failures[]=$family.' '.$label.': ownerUnavailable='.($unavailable?'true':'false').', HTTP='.$response['status'];
                } elseif($family==='planning') {
                    $failed=false;try{$f->planning()->scheduleInspection(9101,451201,'2026-09-12');}catch(Throwable){$failed=true;}
                    assertSameValue(true,$failed,'planning command must reject same-count wrong constraint');
                    assertSameValue($before,$f->facts(),'planning wrong constraint no facts/repair');
                }
                fwrite(STDERR, 'READINESS_PROBE '.$family.' '.$label.': HTTP='.$response['status']."\n");
            } finally {self::ddl($f,$restore);}
            assertSameValue(true,self::ready($f,$family),'exact restored family '.$family.' '.$label);
        }
        assertSameValue([], $failures, 'INTENDED_RED exact manifest parity must reject every structural drift');
    }
}

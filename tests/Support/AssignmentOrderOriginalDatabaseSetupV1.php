<?php

declare(strict_types=1);

namespace FMonitor2\Tests\Support;

final class AssignmentOrderOriginalDatabaseSetupV1
{
    public const VERSION = 1;
    public const TABLES = [
        'fm2_assignment_order_original_roots',
        'fm2_assignment_order_original_revisions',
        'fm2_assignment_order_original_requests',
        'fm2_assignment_order_original_events',
        'fm2_assignment_order_original_audits',
        'fm2_assignment_order_original_maintenance_requests',
        'fm2_assignment_order_original_maintenance_audits',
    ];

    public const PROJECTIONS = [
        'orderCompositionSha256' => ['388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5', '{"caseId":4512,"compositionIdentity":"composition-81-v1","engineerUserId":31,"installers":[7001,7002],"orderId":81}'],
        'caseSha256' => ['b28f40fe02e9b4ca3981a5edaf5e165f9f95531e38c87bc70a8458444b2ace84', '{"actualStartDate":null,"caseId":4512,"processState":"prepared"}'],
        'openingSha256' => ['89c2844c0f723aacd7b7982b36d6297df53a3d2f2b44a268212ab43149687f42', '{"actualStartDate":null,"openedAt":null,"openedByUserId":null}'],
        'tasksSha256' => ['272d922aa2cdcad49bd98141062fc752eb4f31690a720b46c2fa7a0e1b0fe799', '{"items":[{"assigneeRole":"fkr_operator","status":"open","taskId":9001,"taskType":"assignment_order_original_upload"}]}'],
        'checklistSha256' => ['ccb8260ee585db0d0cec53f376d71a66869cf1eca3f287e5a5d7a4e91a04d546', '{"items":[{"availability":"blocked_until_opening","checklistIdentity":"installation-case-4512"}]}'],
        'decoySha256' => ['963ca80eddc50543eb940cf813923bd451d0974585a529e7880107df6982e2ca', '{"items":[{"caseId":9999,"marker":"fixture-decoy-v1"}]}'],
    ];

    /** @return array<string,list<array{string,string,string}>> */
    public static function columns(): array
    {
        return [
            self::TABLES[0] => [['root_original_id','varchar(80)','NO'],['installation_case_id','bigint(20) unsigned','NO'],['assignment_order_id','bigint(20) unsigned','NO'],['current_revision_id','varchar(80)','NO'],['composition_identity','varchar(160)','NO'],['composition_sha256','char(64)','NO'],['created_at_utc','datetime(6)','NO']],
            self::TABLES[1] => [['revision_id','varchar(80)','NO'],['root_original_id','varchar(80)','NO'],['revision_number','int(10) unsigned','NO'],['previous_revision_id','varchar(80)','YES'],['document_date','date','NO'],['uploaded_at_utc','datetime(6)','NO'],['actor_user_id','bigint(20) unsigned','NO'],['pdf_sha256','char(64)','NO'],['byte_size','int(10) unsigned','NO'],['private_content_identity','varchar(160)','NO'],['correction_reason','varchar(500)','YES'],['request_id','char(36)','NO'],['operation_fingerprint','char(64)','NO'],['event_type','varchar(80)','NO']],
            self::TABLES[2] => [['request_id','char(36)','NO'],['mode','varchar(20)','NO'],['installation_case_id','bigint(20) unsigned','NO'],['assignment_order_id','bigint(20) unsigned','NO'],['actor_identity','varchar(160)','NO'],['status','varchar(20)','NO'],['reason_code','varchar(80)','YES'],['retryable','tinyint(3) unsigned','NO'],['root_original_id','varchar(80)','YES'],['current_revision_id','varchar(80)','YES'],['revision_number','int(10) unsigned','YES'],['document_date','date','YES'],['sha256','char(64)','YES'],['byte_size','int(10) unsigned','YES'],['uploaded_at_utc','datetime(6)','YES'],['attempted_at_utc','datetime(6)','NO']],
            self::TABLES[3] => [['event_id','bigint(20) unsigned','NO'],['event_type','varchar(80)','NO'],['installation_case_id','bigint(20) unsigned','NO'],['assignment_order_id','bigint(20) unsigned','NO'],['root_original_id','varchar(80)','NO'],['revision_id','varchar(80)','NO'],['occurred_at_utc','datetime(6)','NO'],['actor_user_id','bigint(20) unsigned','NO']],
            self::TABLES[4] => [['audit_id','bigint(20) unsigned','NO'],['request_id','char(36)','NO'],['actor_identity','varchar(160)','NO'],['mode','varchar(20)','NO'],['installation_case_id','bigint(20) unsigned','NO'],['assignment_order_id','bigint(20) unsigned','NO'],['status','varchar(20)','NO'],['reason_code','varchar(80)','YES'],['attempted_at_utc','datetime(6)','NO']],
            self::TABLES[5] => [['request_id','char(36)','NO'],['system_principal_id','varchar(160)','NO'],['status','varchar(20)','NO'],['reason_code','varchar(80)','YES'],['retryable','tinyint(3) unsigned','NO'],['scanned','int(10) unsigned','NO'],['deleted','int(10) unsigned','NO'],['retained','int(10) unsigned','NO'],['failed','int(10) unsigned','NO'],['next_cursor','varchar(500)','YES'],['attempted_at_utc','datetime(6)','NO']],
            self::TABLES[6] => [['audit_id','bigint(20) unsigned','NO'],['request_id','char(36)','NO'],['system_principal_id','varchar(160)','NO'],['status','varchar(20)','NO'],['reason_code','varchar(80)','YES'],['retryable','tinyint(3) unsigned','NO'],['scanned','int(10) unsigned','NO'],['deleted','int(10) unsigned','NO'],['retained','int(10) unsigned','NO'],['failed','int(10) unsigned','NO'],['attempted_at_utc','datetime(6)','NO']],
        ];
    }

    /** @return array<string,list<array{string,string,string,?string,?string,?string,string}>> */
    public static function columnManifest(): array
    {
        $ascii = [
            self::TABLES[0] => ['root_original_id','current_revision_id','composition_identity','composition_sha256'],
            self::TABLES[1] => ['revision_id','root_original_id','previous_revision_id','pdf_sha256','private_content_identity','request_id','operation_fingerprint'],
            self::TABLES[2] => ['request_id','root_original_id','current_revision_id','sha256'],
            self::TABLES[3] => ['root_original_id','revision_id'], self::TABLES[4] => ['request_id'],
            self::TABLES[5] => ['request_id'], self::TABLES[6] => ['request_id'],
        ];
        $auto = [self::TABLES[3]=>['event_id'],self::TABLES[4]=>['audit_id'],self::TABLES[6]=>['audit_id']];
        $out=[];
        foreach(self::columns() as$table=>$columns)foreach($columns as[$name,$type,$nullable]){
            $character=str_starts_with($type,'varchar')||str_starts_with($type,'char');
            $binary=in_array($name,$ascii[$table],true);
            $out[$table][]=[$name,$type,$nullable,$character?($binary?'ascii':'utf8mb4'):null,$character?($binary?'ascii_bin':'utf8mb4_unicode_ci'):null,null,in_array($name,$auto[$table]??[],true)?'auto_increment':''];
        }
        return$out;
    }

    /** @return array<string,list<string>> */
    public static function checks(): array
    {
        $id=static fn(string$v,int$n=80):string=>"char_length({$v})between1and{$n}and{$v}notregexp'[[:cntrl:]/\\\\]'";
        $uuid=static fn(string$v):string=>"{$v}regexp'^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$'";
        $evidence='root_original_idisnotnullandcurrent_revision_idisnotnullandrevision_numberisnotnullanddocument_dateisnotnullandsha256isnotnullandbyte_sizeisnotnullanduploaded_at_utcisnotnull';
        $empty='root_original_idisnullandcurrent_revision_idisnullandrevision_numberisnullanddocument_dateisnullandsha256isnullandbyte_sizeisnullanduploaded_at_utcisnull';
        $rejected="reason_codein('authorization_denied','invalid_command','order_not_found','composition_not_confirmed','invalid_composition','file_too_large','not_pdf','invalid_pdf','unsafe_pdf','future_document_date','no_changes')";
        $conflict="reason_codein('semantic_collision','stale_revision','target_not_found','target_not_current','initial_already_exists')";
        return[
            self::TABLES[0]=>["composition_sha256regexp'^[0-9a-f]{64}$'",$id('root_original_id'),$id('current_revision_id')],
            self::TABLES[1]=>["revision_number>=1","pdf_sha256regexp'^[0-9a-f]{64}$'","operation_fingerprintregexp'^[0-9a-f]{64}$'","byte_sizebetween1and20971520","event_typein('assignment_order_original_accepted','assignment_order_original_corrected')","((revision_number=1andprevious_revision_idisnullandcorrection_reasonisnullandevent_type='assignment_order_original_accepted')or(revision_number>1andprevious_revision_idisnotnullandchar_length(trim(correction_reason))between1and500andevent_type='assignment_order_original_corrected'))",$id('revision_id'),$id('root_original_id'),$id('previous_revision_id'),$id('private_content_identity',160)],
            self::TABLES[2]=>[$uuid('request_id'),"modein('initial','correction')","statusin('accepted','replayed','rejected','conflict')","retryable=0","(statusnotin('accepted','replayed')or(reason_codeisnulland{$evidence}))","(status<>'rejected'or({$rejected}and{$empty}))","(status<>'conflict'or({$conflict}and{$empty}))",$id('root_original_id'),$id('current_revision_id')],
            self::TABLES[3]=>["event_typein('assignment_order_original_accepted','assignment_order_original_corrected')",$id('root_original_id'),$id('revision_id')],
            self::TABLES[4]=>[$uuid('request_id'),"modein('initial','correction')","statusin('accepted','rejected','conflict')","((status='accepted'andreason_codeisnull)or(status='rejected'and{$rejected})or(status='conflict'and{$conflict}))"],
            self::TABLES[5]=>[$uuid('request_id'),"statusin('completed','replayed','rejected','partial')","((statusin('completed','replayed')andreason_codeisnullandretryable=0)or(status='rejected'andreason_codein('invalid_command','authorization_denied')andretryable=0)or(status='partial'andreason_codein('locked','storage_failure')andretryable=1))","scanned=deleted+retained+failed"],
            self::TABLES[6]=>[$uuid('request_id'),"statusin('completed','rejected','partial')","((status='completed'andreason_codeisnullandretryable=0)or(status='rejected'andreason_codein('invalid_command','authorization_denied')andretryable=0)or(status='partial'andreason_codein('locked','storage_failure')andretryable=1))","scanned=deleted+retained+failed"],
        ];
    }

    /** @return array<string,list<array{string,string}>> */
    public static function keys(): array
    {
        return [
            self::TABLES[0] => [['INDEX','installation_case_id,assignment_order_id'],['PRIMARY','root_original_id'],['UNIQUE','assignment_order_id'],['UNIQUE','current_revision_id']],
            self::TABLES[1] => [['INDEX','root_original_id,revision_number'],['PRIMARY','revision_id'],['UNIQUE','operation_fingerprint'],['UNIQUE','previous_revision_id'],['UNIQUE','private_content_identity'],['UNIQUE','request_id'],['UNIQUE','root_original_id,revision_number']],
            self::TABLES[2] => [['INDEX','installation_case_id,assignment_order_id,attempted_at_utc'],['PRIMARY','request_id']],
            self::TABLES[3] => [['INDEX','installation_case_id,assignment_order_id,event_id'],['PRIMARY','event_id'],['UNIQUE','root_original_id,revision_id,event_type']],
            self::TABLES[4] => [['INDEX','installation_case_id,assignment_order_id,audit_id'],['PRIMARY','audit_id'],['UNIQUE','request_id,status,reason_code']],
            self::TABLES[5] => [['PRIMARY','request_id']],
            self::TABLES[6] => [['PRIMARY','audit_id'],['UNIQUE','request_id']],
        ];
    }

    /** @return array<string,list<array{string,string,string,string}>> */
    public static function foreignKeys(): array
    {
        return [
            self::TABLES[0] => [],
            self::TABLES[1] => [['previous_revision_id',self::TABLES[1],'revision_id','RESTRICT/RESTRICT'],['root_original_id',self::TABLES[0],'root_original_id','RESTRICT/RESTRICT']],
            self::TABLES[2] => [['current_revision_id',self::TABLES[1],'revision_id','RESTRICT/RESTRICT'],['root_original_id',self::TABLES[0],'root_original_id','RESTRICT/RESTRICT']],
            self::TABLES[3] => [['revision_id',self::TABLES[1],'revision_id','RESTRICT/RESTRICT'],['root_original_id',self::TABLES[0],'root_original_id','RESTRICT/RESTRICT']],
            self::TABLES[4] => [['request_id',self::TABLES[2],'request_id','RESTRICT/RESTRICT']],
            self::TABLES[5] => [],
            self::TABLES[6] => [['request_id',self::TABLES[5],'request_id','RESTRICT/RESTRICT']],
        ];
    }

    /** @return array<string,int> */
    public static function checkCounts(): array
    {
        return [
            self::TABLES[0] => 3,
            self::TABLES[1] => 10,
            self::TABLES[2] => 9,
            self::TABLES[3] => 3,
            self::TABLES[4] => 4,
            self::TABLES[5] => 4,
            self::TABLES[6] => 4,
        ];
    }

    public static function rootsDdl(string $prefix): string
    {
        return "CREATE TABLE `{$prefix}" . self::TABLES[0] . "` (root_original_id VARCHAR(80) CHARACTER SET ascii COLLATE ascii_bin NOT NULL, installation_case_id BIGINT UNSIGNED NOT NULL, assignment_order_id BIGINT UNSIGNED NOT NULL, current_revision_id VARCHAR(80) CHARACTER SET ascii COLLATE ascii_bin NOT NULL, composition_identity VARCHAR(160) CHARACTER SET ascii COLLATE ascii_bin NOT NULL, composition_sha256 CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL, created_at_utc DATETIME(6) NOT NULL, PRIMARY KEY(root_original_id), UNIQUE KEY(assignment_order_id), UNIQUE KEY(current_revision_id), KEY(installation_case_id,assignment_order_id), CHECK(composition_sha256 REGEXP '^[0-9a-f]{64}$'), CHECK(CHAR_LENGTH(root_original_id) BETWEEN 1 AND 80 AND root_original_id NOT REGEXP '[[:cntrl:]/\\\\\\\\]'), CHECK(CHAR_LENGTH(current_revision_id) BETWEEN 1 AND 80 AND current_revision_id NOT REGEXP '[[:cntrl:]/\\\\\\\\]')) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    }
}

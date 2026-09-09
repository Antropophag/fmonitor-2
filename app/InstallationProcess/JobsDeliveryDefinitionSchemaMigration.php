<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** @internal Literal reviewed Jobs schema family; no runtime mutation. */
final class JobsDeliveryDefinitionSchemaMigration
{
    public static function tables(): array
    {
        return [
            'fm2_outbox_intents'=>[
                'columns'=>[
                    ['name'=>'intent_id','type'=>'bigint unsigned','nullable'=>false,'default'=>null,'extra'=>'auto_increment','charset'=>null,'collation'=>null],
                    ['name'=>'domain_event_id','type'=>'varchar(120)','nullable'=>false,'default'=>null,'extra'=>'','charset'=>'ascii','collation'=>'ascii_bin'],
                    ['name'=>'channel','type'=>'varchar(40)','nullable'=>false,'default'=>null,'extra'=>'','charset'=>'ascii','collation'=>'ascii_bin'],
                    ['name'=>'template_reference','type'=>'varchar(120)','nullable'=>false,'default'=>null,'extra'=>'','charset'=>'ascii','collation'=>'ascii_bin'],
                    ['name'=>'payload_version','type'=>'smallint unsigned','nullable'=>false,'default'=>null,'extra'=>'','charset'=>null,'collation'=>null],
                    ['name'=>'data_json','type'=>'longtext','nullable'=>false,'default'=>null,'extra'=>'','charset'=>'utf8mb4','collation'=>'utf8mb4_bin'],
                    ['name'=>'fingerprint','type'=>'char(64)','nullable'=>false,'default'=>null,'extra'=>'','charset'=>'ascii','collation'=>'ascii_bin'],
                    ['name'=>'status','type'=>'varchar(16)','nullable'=>false,'default'=>'\'pending\'','extra'=>'','charset'=>'ascii','collation'=>'ascii_bin'],
                    ['name'=>'provider_reference','type'=>'varchar(200)','nullable'=>true,'default'=>null,'extra'=>'','charset'=>'utf8mb4','collation'=>'utf8mb4_bin'],
                    ['name'=>'created_at_utc','type'=>'char(27)','nullable'=>false,'default'=>null,'extra'=>'','charset'=>'ascii','collation'=>'ascii_bin'],
                    ['name'=>'delivered_at_utc','type'=>'char(27)','nullable'=>true,'default'=>null,'extra'=>'','charset'=>'ascii','collation'=>'ascii_bin'],
                ],
                'indexes'=>[
                    ['name'=>'PRIMARY','columns'=>['intent_id'],'unique'=>true,'type'=>'BTREE'],
                    ['name'=>'@prefixoutbox_uq_event_channel','columns'=>['domain_event_id','channel'],'unique'=>true,'type'=>'BTREE'],
                    ['name'=>'@prefixoutbox_ix_pending','columns'=>['status','intent_id'],'unique'=>false,'type'=>'BTREE'],
                ],
                'foreignKeys'=>[
                ],
                'checks'=>[
                    'payload_version BETWEEN 1 AND 65535',
                    'JSON_VALID(data_json)',
                    'status IN (\'pending\',\'delivered\',\'dead\')',
                ],
                'engine'=>'InnoDB',
                'collation'=>'utf8mb4_unicode_ci',
            ],
            'fm2_outbox_attempt_events'=>[
                'columns'=>[
                    ['name'=>'attempt_event_id','type'=>'bigint unsigned','nullable'=>false,'default'=>null,'extra'=>'auto_increment','charset'=>null,'collation'=>null],
                    ['name'=>'intent_id','type'=>'bigint unsigned','nullable'=>false,'default'=>null,'extra'=>'','charset'=>null,'collation'=>null],
                    ['name'=>'job_id','type'=>'bigint unsigned','nullable'=>false,'default'=>null,'extra'=>'','charset'=>null,'collation'=>null],
                    ['name'=>'attempt','type'=>'tinyint unsigned','nullable'=>false,'default'=>null,'extra'=>'','charset'=>null,'collation'=>null],
                    ['name'=>'outcome','type'=>'varchar(40)','nullable'=>false,'default'=>null,'extra'=>'','charset'=>'ascii','collation'=>'ascii_bin'],
                    ['name'=>'failure_code','type'=>'varchar(80)','nullable'=>true,'default'=>null,'extra'=>'','charset'=>'ascii','collation'=>'ascii_bin'],
                    ['name'=>'safe_detail','type'=>'varchar(500)','nullable'=>true,'default'=>null,'extra'=>'','charset'=>'utf8mb4','collation'=>'utf8mb4_bin'],
                    ['name'=>'provider_reference','type'=>'varchar(200)','nullable'=>true,'default'=>null,'extra'=>'','charset'=>'utf8mb4','collation'=>'utf8mb4_bin'],
                    ['name'=>'idempotency_reference','type'=>'char(64)','nullable'=>false,'default'=>null,'extra'=>'','charset'=>'ascii','collation'=>'ascii_bin'],
                    ['name'=>'occurred_at_utc','type'=>'char(27)','nullable'=>false,'default'=>null,'extra'=>'','charset'=>'ascii','collation'=>'ascii_bin'],
                ],
                'indexes'=>[
                    ['name'=>'PRIMARY','columns'=>['attempt_event_id'],'unique'=>true,'type'=>'BTREE'],
                    ['name'=>'@prefixoutbox_attempt_uq','columns'=>['intent_id','job_id','attempt'],'unique'=>true,'type'=>'BTREE'],
                    ['name'=>'@prefixoutbox_attempt_ix_job','columns'=>['job_id'],'unique'=>false,'type'=>'BTREE'],
                ],
                'foreignKeys'=>[
                    ['name'=>'@prefixoutbox_attempt_fk_intent','columns'=>['intent_id'],'target'=>'fm2_outbox_intents','referenced'=>['intent_id'],'update'=>'RESTRICT','delete'=>'RESTRICT'],
                    ['name'=>'@prefixoutbox_attempt_fk_job','columns'=>['job_id'],'target'=>'fm2_jobs','referenced'=>['job_id'],'update'=>'RESTRICT','delete'=>'RESTRICT'],
                ],
                'checks'=>[
                    'attempt BETWEEN 1 AND 5',
                ],
                'engine'=>'InnoDB',
                'collation'=>'utf8mb4_unicode_ci',
            ],
            'fm2_scheduler_slots'=>[
                'columns'=>[
                    ['name'=>'schedule_key','type'=>'varchar(80)','nullable'=>false,'default'=>null,'extra'=>'','charset'=>'ascii','collation'=>'ascii_bin'],
                    ['name'=>'due_at_utc','type'=>'char(27)','nullable'=>false,'default'=>null,'extra'=>'','charset'=>'ascii','collation'=>'ascii_bin'],
                    ['name'=>'enqueued_at_utc','type'=>'char(27)','nullable'=>false,'default'=>null,'extra'=>'','charset'=>'ascii','collation'=>'ascii_bin'],
                    ['name'=>'skipped_slots','type'=>'int unsigned','nullable'=>false,'default'=>'0','extra'=>'','charset'=>null,'collation'=>null],
                    ['name'=>'job_id','type'=>'bigint unsigned','nullable'=>false,'default'=>null,'extra'=>'','charset'=>null,'collation'=>null],
                ],
                'indexes'=>[
                    ['name'=>'PRIMARY','columns'=>['schedule_key'],'unique'=>true,'type'=>'BTREE'],
                    ['name'=>'@prefixscheduler_uq_job','columns'=>['job_id'],'unique'=>true,'type'=>'BTREE'],
                ],
                'foreignKeys'=>[
                    ['name'=>'@prefixscheduler_fk_job','columns'=>['job_id'],'target'=>'fm2_jobs','referenced'=>['job_id'],'update'=>'RESTRICT','delete'=>'RESTRICT'],
                ],
                'checks'=>[
                    'skipped_slots >= 0',
                ],
                'engine'=>'InnoDB',
                'collation'=>'utf8mb4_unicode_ci',
            ],
            'fm2_worker_heartbeats'=>[
                'columns'=>[
                    ['name'=>'worker_id','type'=>'varchar(80)','nullable'=>false,'default'=>null,'extra'=>'','charset'=>'ascii','collation'=>'ascii_bin'],
                    ['name'=>'observed_at_utc','type'=>'char(27)','nullable'=>false,'default'=>null,'extra'=>'','charset'=>'ascii','collation'=>'ascii_bin'],
                ],
                'indexes'=>[
                    ['name'=>'PRIMARY','columns'=>['worker_id'],'unique'=>true,'type'=>'BTREE'],
                ],
                'foreignKeys'=>[
                ],
                'checks'=>[
                ],
                'engine'=>'InnoDB',
                'collation'=>'utf8mb4_unicode_ci',
            ],
        ];
    }
}

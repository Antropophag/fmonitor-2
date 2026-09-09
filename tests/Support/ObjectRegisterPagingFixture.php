<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;

use FMonitor2\InstallationProcess\{CanonicalMigrationApplication,ProductionPilotMigrationCatalogue,PilotOtizSchemaMigration,OtizPublicationSchemaMigration,OtizEvidenceSchemaMigration};

/** Disposable canonical data set for OTIZ-OBJECT-REGISTER-PAGING-001. */
final class ObjectRegisterPagingFixture
{
    public readonly SelectedOriginalFixture $original;
    public readonly \mysqli $db;

    public function __construct(public readonly string $prefix)
    {
        $this->original=new SelectedOriginalFixture($prefix);
        $this->db=$this->original->selection->db;
        try{self::seed($this->original,$prefix,1);}catch(\Throwable $error){$this->close();throw $error;}
    }

    /** Seed an existing SelectionHttpFixture database before its server starts. */
    public static function seed(SelectedOriginalFixture $original,string $prefix,int $actorRole=3):void
    {
        $db=$original->selection->db;$p=$prefix;
        $migration=CanonicalMigrationApplication::run($db,$p,ProductionPilotMigrationCatalogue::migrations());
        if(($migration['exitCode']??1)!==0)throw new \RuntimeException('SETUP_FAILURE canonical paging fixture migration');
        PilotOtizSchemaMigration::apply($db,$p);
        if(class_exists(OtizPublicationSchemaMigration::class))OtizPublicationSchemaMigration::apply($db,$p);
        if(class_exists(OtizEvidenceSchemaMigration::class))OtizEvidenceSchemaMigration::apply($db,$p);
        $db->query("INSERT IGNORE INTO `{$p}fm2_pilot_role_permissions`(role_id,permission) VALUES({$actorRole},'otiz.manage')");
        $db->query("DELETE FROM `{$p}fm_maintable`");
        $db->query("DELETE FROM `{$p}fm2_pilot_object_details`");

        $legacy=$db->prepare("INSERT INTO `{$p}fm_maintable`(id,ordadr_address,regnumber) VALUES(?,?,'TIE-REGISTER')");
        $detail=$db->prepare("INSERT INTO `{$p}fm2_pilot_object_details`(object_id,schema_version,content_sha256,payload_json,captured_at) VALUES(?,'technical-object-detail-v1',?,?,'2026-09-01T09:00:00Z')");
        for($id=1;$id<=125;$id++){
            $address=$id===125?'Уникальная цель за третьей страницей':'Вымышленный адрес '.str_pad((string)$id,3,'0',STR_PAD_LEFT);
            $legacy->bind_param('is',$id,$address);$legacy->execute();
            $fields=['floors'=>['raw'=>'5'],'weight'=>['raw'=>'320'],'pitmaterial'=>['display'=>'Железобетон'],'lift_type'=>['display'=>'Пассажирский']];
            if($id===5)$fields['weight']['raw']='501';
            $payload=json_encode(['schemaVersion'=>'technical-object-detail-v1','objectId'=>$id,'fields'=>$fields],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
            $hash=hash('sha256',$payload);$detail->bind_param('iss',$id,$hash,$payload);$detail->execute();
        }
        $legacy->close();$detail->close();

        $snapshot=$db->prepare("INSERT INTO `{$p}fm2_pilot_otiz_snapshots`(id,report_date,status,previous_snapshot_id,rules_version,calculated_at,calculated_by_user_id,accepted_at,accepted_by_user_id,total_pool_cents,total_closed_cents,total_available_cents,content_hash) VALUES(?,?,'draft',NULL,'fixture-v1','2026-09-03T10:00:00Z',18,NULL,NULL,0,0,0,?)");
        $object=$db->prepare("INSERT INTO `{$p}fm2_pilot_otiz_snapshot_objects`(snapshot_id,object_id,regnumber,address,previous_progress_bp,current_progress_bp,progress_fact_date,premium_cents,shaft_bp,kss_bp,accrued_cents,fund_cents,closed_before_cents,remaining_cents,pool_cents,distributed_cents,undistributed_cents,calculation_state,inputs_json) VALUES(?,?,'TIE-REGISTER','Fixture',0,1000,'2026-09-03',52000000,10000,10000,?,52000000,0,52000000,?,0,0,?,?)");
        $cases=[
            [101,'2026-09-03',1,100,100,'ready'],
            [102,'2026-09-03',2,100,100,'ready'],
            [103,'2026-09-03',3,300,0,'blocked'],
            [104,'2026-09-03',4,400,0,'no_new_amount'],
            [105,'2026-09-03',5,500,0,'ready'],
            [106,'2026-09-02',6,600,0,'ready'],
            [206,'2026-09-01',6,9999,0,'blocked'],
        ];
        foreach($cases as[$snapshotId,$date,$objectId,$accrued,$pool,$state]){
            $hash=str_pad(dechex($snapshotId),64,'a',STR_PAD_LEFT);$snapshot->bind_param('iss',$snapshotId,$date,$hash);$snapshot->execute();
            $progressAmount=$objectId===3?350:$accrued;
            $inputs=json_encode(['premiumCalculation'=>['formulaTrace'=>[['step'=>'progress','resultCents'=>$progressAmount]]]],JSON_THROW_ON_ERROR);
            $object->bind_param('iiiiss',$snapshotId,$objectId,$accrued,$pool,$state,$inputs);$object->execute();
        }
        $snapshot->close();$object->close();
        $closure=$db->prepare("INSERT INTO `{$p}fm2_pilot_otiz_payment_closures`(snapshot_id,object_id,closed_on,paid_cents,discipline_cents,deadline_cents,basis,artifact,created_by_user_id,created_at) VALUES(?,?,'2026-09-04',?,0,0,'Synthetic closure','',18,'2026-09-04T10:00:00Z')");
        foreach([[101,1,100],[102,2,99]]as[$sid,$oid,$paid]){$closure->bind_param('iii',$sid,$oid,$paid);$closure->execute();}$closure->close();
        $db->query("INSERT INTO `{$p}fm2_pilot_otiz_payment_closures`(snapshot_id,object_id,closed_on,paid_cents,discipline_cents,deadline_cents,basis,artifact,created_by_user_id,created_at) VALUES(103,3,'2026-09-04',0,10,20,'Trace versus stored deadline fixture','',18,'2026-09-04T10:00:00Z')");
    }

    /** Replace one card while preserving the canonical content hash. */
    public function card(int $id,mixed $floors,mixed $weight,string $material,string $type):void
    {
        self::replaceCard($this->db,$this->prefix,$id,$floors,$weight,$material,$type);
    }

    public static function replaceCard(\mysqli$db,string $prefix,int $id,mixed $floors,mixed $weight,string $material,string $type):void
    {$fields=['floors'=>['raw'=>$floors],'weight'=>['raw'=>$weight],'pitmaterial'=>['display'=>$material],'lift_type'=>['display'=>$type]];$payload=json_encode(['schemaVersion'=>'technical-object-detail-v1','objectId'=>$id,'fields'=>$fields],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$hash=hash('sha256',$payload);$s=$db->prepare("UPDATE `{$prefix}fm2_pilot_object_details` SET content_sha256=?,payload_json=? WHERE object_id=?");$s->bind_param('ssi',$hash,$payload,$id);$s->execute();$s->close();}

    public function close():void{$this->original->close();}
}

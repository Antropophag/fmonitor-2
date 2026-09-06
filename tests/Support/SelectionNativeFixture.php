<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;
use FMonitor2\AssignmentOrderComposition as C;
use FMonitor2\InstallationProcess as I;
use FMonitor2\AssignmentOrderOriginal as O;

/** Synthetic setup and read-only observation; never implements the command. */
final class SelectionNativeFixture
{
    public readonly SelectionSchemaTestDatabase $schema;
    public readonly \mysqli $db;
    public int $clockCalls = 0;
    public function __construct()
    {
        $this->schema = new SelectionSchemaTestDatabase();
        $this->db = $this->schema->db;
        try {
            \assertSameValue(['applied'=>true], I\AssignmentOrderSelectionSchemaMigration::apply($this->db), 'approved selection schema');
            foreach ([I\IdentityAccessSchemaMigration::class,I\WorkforceCatalogSchemaMigration::class,I\InstallationCompletionSchemaMigration::class,I\ProcessUserCapabilitiesSchemaMigration::class,I\ProcessCommandCapabilitiesSchemaMigration::class] as $migration) {
                \assertSameValue(true, $migration::apply($this->db)['applied'], 'approved native dependency schema');
            }
            \assertSameValue(O\AssignmentOrderOriginalSchemaMigrationStatus::APPLIED, O\AssignmentOrderOriginalSchemaMigration::apply($this->db)->status(), 'approved original schema');
            $this->db->query('CREATE TABLE fm_maintable (id BIGINT UNSIGNED PRIMARY KEY, ordadr_address VARCHAR(500), entrance VARCHAR(80), regnumber VARCHAR(120), workdatestart VARCHAR(40), workdateendadjusted VARCHAR(40), plan_finish_date VARCHAR(40), workdatefinish VARCHAR(40), ptoactdate VARCHAR(40), responsstroicontrol VARCHAR(80)) ENGINE=InnoDB');
            $this->db->query("INSERT INTO fm_maintable(id,ordadr_address,regnumber) VALUES(4512,'Вымышленный объект','TEST-4512')");
            foreach ([[18,'Сотрудник теста'],[73,'Инженер теста']] as [$id,$name]) {
                $this->schema->insert('fm2_pilot_users',['user_id'=>$id,'full_name'=>$name,'email'=>"test$id@example.invalid",'status'=>1,'activation_state'=>'active','source_updated_at'=>'2026-09-01T06:00:00Z']);
            }
            foreach ([[1,'fkr_operator'],[2,'construction_control_engineer']] as [$id,$code]) {
                $this->schema->insert('fm2_pilot_roles',['role_id'=>$id,'code'=>$code,'name'=>$code,'description'=>'synthetic','status'=>1,'source_updated_at'=>'2026-09-01T06:00:00Z']);
            }
            foreach ([[18,1],[73,2]] as [$user,$role]) {
                $this->schema->insert('fm2_pilot_user_roles',['user_id'=>$user,'role_id'=>$role,'origin'=>'bootstrap','assigned_at'=>'2026-09-01T06:00:00Z']);
            }
            $this->schema->insert('fm2_pilot_role_permissions',['role_id'=>1,'permission'=>'assignment_order.composition.select']);
            foreach ([7001,7002] as $id) {
                $this->schema->insert('fm2_workforce_catalog',['installer_tab_id'=>$id,'fio'=>"Монтажник $id",'position'=>'Монтажник','employment_status'=>'employed','employed_from'=>'2020-01-01','employed_to'=>null,'workforce_source'=>'synthetic-hr','workforce_source_updated_at'=>'2026-09-01T06:00:00Z']);
            }
            \assertSameValue(true,I\AssignmentOrderSelectionSchemaMigration::isReady($this->db),'ready before target action');
        } catch (\Throwable $e) { $this->schema->close(); throw $e; }
    }
    public function app(): C\AssignmentOrderCompositionApplication
    {
        \assertSameValue(true,is_callable([C\AssignmentOrderCompositionNativeVerificationFactory::class,'dependencies']), 'RED_ASSERTION: native selection binding missing after valid database setup');
        $p=C\AssignmentOrderCompositionNativeVerificationFactory::dependencies($this->db,fn()=>$this->schema->source->connect($this->schema->source->name));
        $clock=new class($this) implements C\SelectionClock {
            public function __construct(private SelectionNativeFixture $fixture) {}
            public function now(): C\SelectionInstantLookup {
                $this->fixture->clockCalls++;
                return C\SelectionInstantLookup::found(new C\SelectionInstant('2026-09-05T09:00:00Z'));
            }
        };
        return C\AssignmentOrderCompositionFactory::create(new C\SelectionDependencies($p->authorizer,$p->facts,$clock,$p->requests,$p->transactions,$p->freshReaders,$p->audits,$p->terminalAttempts));
    }
    public static function command(int $request=1,int $revision=0,int $installer=7001,int $object=4512): C\SelectAssignmentOrderCompositionCommand
    {
        return new C\SelectAssignmentOrderCompositionCommand(new C\SelectionRequestId(sprintf('11111111-1111-4111-8111-%012d',$request)),
            $revision===0?C\AssignmentOrderCompositionMode::NEW_ORDER:C\AssignmentOrderCompositionMode::REPLACE_PENDING,
            new C\InstallationObjectId($object),new C\UserId(18),new C\InstallerTabIdList([new C\InstallerTabId($installer)]),new C\UserId(73),new C\SelectionRevision($revision));
    }
    public function rows(): array
    {
        $rows=[];
        foreach ($this->schema->state() as $table=>$state) $rows[$table]=$state[1];
        return $rows;
    }
    public function close(): void { $this->schema->close(); }
}

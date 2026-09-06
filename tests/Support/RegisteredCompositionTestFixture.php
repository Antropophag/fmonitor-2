<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;
use FMonitor2\InstallationProcess\AssignmentOrderSelectionSchemaMigration;

final class RegisteredCompositionTestFixture
{
    public readonly SelectionSchemaTestDatabase $schema;
    public readonly \mysqli $db;
    public function __construct(string $kind = 'selection')
    {
        $this->schema = new SelectionSchemaTestDatabase(); $this->db = $this->schema->db;
        try {
            \assertSameValue(['applied'=>true], AssignmentOrderSelectionSchemaMigration::apply($this->db), 'approved native selection schema prerequisite');
            if ($kind === 'selection') { $this->schema->populate(); }
            if ($kind === 'legacy') { $this->legacy(); }
        } catch (\Throwable $error) {
            try { $this->schema->close(); } catch (\Throwable $cleanup) { throw new \TestFailure($error->getMessage().' | '.$cleanup->getMessage()); }
            throw $error;
        }
    }
    public function legacy(): void
    {
        $this->db->query("INSERT INTO fm2_assignment_orders(id,installation_case_id,version_no,kind,status,order_date,control_engineer_user_id,control_engineer_fio_snapshot,control_engineer_position_snapshot,organization_form,object_address_snapshot,entrance_snapshot,object_registration_number_snapshot,planned_start_date_snapshot,planned_finish_date_snapshot,prepared_at,prepared_by_user_id) VALUES(81,4512,1,'initial','prepared','2026-09-02',73,'Инженер теста','Инженер','individual','Адрес теста','2','77-000123','2026-10-05','2026-12-20','2026-09-02T07:00:00Z',18)");
        $this->db->query("INSERT INTO fm2_assignment_order_identities VALUES(81,4512,1,'legacy_order','2026-09-02 07:00:00.000000')");
        foreach ([[7001,'2026-09-01',null,'assign'],[7002,'2026-09-01','2026-09-02','release'],[7003,'2026-08-01','2026-09-01','retain']] as [$id,$from,$to,$action]) {
            $this->schema->insert('fm2_order_installers', ['assignment_order_id'=>'81','installer_tab_id'=>(string)$id,'fio_snapshot'=>'Монтажник теста','position_snapshot'=>'Монтажник',
                'employment_status_snapshot'=>'employed','employed_from_snapshot'=>'2020-01-01','employed_to_snapshot'=>null,'workforce_source_snapshot'=>'synthetic-hr',
                'workforce_source_updated_at_snapshot'=>'2026-09-01T06:00:00Z','valid_from'=>$from,'valid_to'=>$to,'change_action'=>$action]);
        }
    }
    public function state(): array { return $this->schema->state(); }
    public function close(): void { $this->schema->close(); }
}

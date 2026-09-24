# INSPECTION-PLANNING-001 Gate 2 RED evidence

Exact worktree base `cbd390f54ea34699523909c462f440f49833c27b`. Local Composer autoload was rebuilt for this worktree; no full suite was run.

- `php tests/InstallationProcess/inspection_planning_object_schema_001_test.php` — intended RED: unique identity всё ещё `(installation_case_id, control_engineer_user_id, inspection_date)`, а контракт требует object/date без engineer.
- `php tests/Yii2/yii2_inspection_planning_002_test.php` — intended RED: undefined public method `YiiInspectionPlanning::createInspectionPlan`.
- `php tests/Yii2/yii2_inspection_planning_object_concurrency_001_test.php` — intended RED: independent worker reaches the absent `createInspectionPlan` seam before the object race.
- `php tests/InstallationProcess/inspection_planning_schema_001_test.php` — GREEN legacy/canonical v9 baseline witness.
- `php tests/InstallationProcess/inspection_planning_runtime_ddl_001_test.php` — GREEN runtime paths perform no DDL/repair.

Yii presentation RED/evidence удалены из первого среза и принадлежат issue #255.

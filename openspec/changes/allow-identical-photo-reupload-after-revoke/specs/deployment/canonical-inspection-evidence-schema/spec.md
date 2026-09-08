## MODIFIED Requirements

### Requirement: Canonical migration owns the complete inspection-evidence family

Canonical production migration v8 SHALL создать точные prefixed tables revisions, operations, operation installers и photos непосредственно после exact landed catalogue v1–v7. Database-default collation validation SHALL наследовать approved v6/v7 UCA-alias normalization. Fresh operations SHALL сразу содержать immutable template identity, а installer evidence — `assignment_source`. Следующая выделенная canonical migration на фактическом свободном frontier SHALL заменить unique photo content index `(installation_case_id, section_id, sha256)` на non-unique lookup index с теми же columns/order. Final canonical schema MUST разрешать несколько retained historical photo rows одного case/section/SHA-256; active duplicate idempotency и same-case serialization принадлежат application seam, а не unique content constraint.

#### Scenario: Clean deployment creates exact final schema
- **WHEN** canonical runner применяется к clean compatible database
- **THEN** все четыре таблицы, columns, keys, indexes, engine и collation присутствуют в final runtime-compatible form
- **AND** photos содержит unique `upload_operation_id`, non-unique `(installation_case_id, section_id, sha256)` lookup и non-unique `(installation_case_id, section_id)` lookup
- **AND** runner сообщает последнюю фактически выделенную canonical schema version после последовательного применения v1–v8 и successor migrations

#### Scenario: Repeat migration is idempotent
- **WHEN** migration повторно применяется к exact final family
- **THEN** она сообщает no-op
- **AND** не изменяет rows, indexes или auto-increment state
- **AND** full canonical catalogue повторно проходит historical v8 entry и successor final compatibility без `SCHEMA_MIGRATION_CONFLICT`, возвращая empty applied versions
- **AND** literal-v8 compatibility остаётся exact predecessor oracle и не принимает final non-unique index как v8

#### Scenario: Populated unique index is reconciled without data loss
- **WHEN** compatible populated photos table имеет прежний unique `(installation_case_id, section_id, sha256)` index
- **THEN** migration preflight подтверждает exact predecessor shape и заменяет только этот index на canonical non-unique lookup
- **AND** все photo rows, revoked timestamps, operation rows, blobs и auto-increment state остаются byte/value-identical
- **AND** deployment rollback восстанавливает predecessor code only before data begins relying on repeated historical hashes; после принятия новых повторных facts destructive index rollback запрещён

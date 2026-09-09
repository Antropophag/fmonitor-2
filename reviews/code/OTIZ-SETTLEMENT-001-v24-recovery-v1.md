# Gate 5 code review: OTIZ-SETTLEMENT-001 canonical v24 recovery

- Reviewer: runtime_review, independently tasked; did not author the specification, tests, or implementation.
- Reviewed implementation commit: `bd7098383b5fb6ca58abbe1c195888447539aafd`.
- Reviewed production delta: `RuntimeRecovery.php` and new `RuntimeRecoverySchemaV24.php`.
- Verdict: **APPROVED**.

## Complete findings

No blocking or non-blocking findings.

The new v24 profile derives the current inventory from the immutable v23
constants, adds exactly `fm2_otiz_settlement_locks` and
`fm2_otiz_settlement_operations`, and sorts the resulting table list before
prefixing it. It inherits the unchanged 39-table AUTO_INCREMENT inventory and
empty deferred list. Independent static evaluation produced 69 v23 tables, 71
v24 tables, 39 AUTO_INCREMENT tables, a sorted v24 list, and exactly those two
set-difference entries.

`RuntimeRecovery` consistently advances every current-image contract point from
v23 to v24: backup manifest deferred metadata, restore manifest/version/table
validation, live backup table/AUTO validation, and restore AUTO validation. The
change leaves bundle identity, quiescence, publication, checksum, target
precondition, mutation ordering, and failure reasons untouched. A v23 manifest
therefore fails current validation before import, while current backup and
restore agree on one exact profile.

Historical behavior is preserved. `RuntimeRecoverySchemaV22.php` and
`RuntimeRecoverySchemaV23.php` are byte-for-byte unchanged by the implementation,
and the approved forward test explicitly selects the matching historical profile
for each exact source image. The implementation introduces no fallback that
would let current tooling accept an old inventory or let old tooling accept v24.

## Verification evidence

Executor focused GREEN evidence reviewed:

```text
php tests/Runtime/runtime_jobs_recovery_001_test.php
PASS: PRODUCTION-JOBS-RECOVERY-001 v24 exact backup/restore contract

php tests/Runtime/runtime_recovery_forward_update_001_test.php
PASS: PRODUCTION-JOBS-RECOVERY-001 exact historical forward update boundary
```

The default forward command covers both exact v22 and v23 source/image paths.
The tests exercise real public backup/restore, exact inventory/version rejection
before target mutation, preservation of rows/AUTO/private bytes, forward
migrations to v24, and rejection of v24 bundles by both historical images. These
container-heavy checks were not duplicated because the two-file implementation
adds no risk outside their reviewed coverage.

Independent static verification:

```text
v23 tables: 69
v24 tables: 71, sorted
v24 AUTO_INCREMENT tables: 39
v24 - v23: fm2_otiz_settlement_locks, fm2_otiz_settlement_operations
v22 profile: unchanged
v23 profile: unchanged
RuntimeRecovery current-profile references: RuntimeRecoverySchemaV24 only
```

Reviewed SHA-256:

- `app/RuntimeRestore/RuntimeRecoverySchemaV24.php`: `5c61012c6b976a982bb55504c3e9f22d593e7647aa7a49e0492e6007a8b5080e`
- `app/RuntimeRestore/RuntimeRecovery.php`: `b5c3ef5dcfddb91c16c4a5a7fb47cedecda4c69516c0f7d92e5d1b59893d9248`

Full exact-candidate CI remains required for final integration.

# Test review: PRODUCTION-MIGRATION-RUNNER-001

- Reviewer: `Codex agent /root/migration_runner_test_review` (independent; did not author specification, tests, fixtures, or production implementation)
- Test author: separately tasked Codex agent, working session `2026-08-28`
- Reviewed commit: working tree at HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/PRODUCTION-MIGRATION-RUNNER-001.md`](../../specs/PRODUCTION-MIGRATION-RUNNER-001.md), version `0.5`, `APPROVED 2026-08-28`
- Public seam: isolated `php bin/fmonitor2-migrate.php` processes against independently constructed completed-v4 MariaDB catalogs
- Red command: `php tests/InstallationProcess/production_migration_runner_001_test.php`
- Superseding Gate 3 verdict: `APPROVED`

This final v0.5 review supersedes the earlier `CHANGES_REQUESTED` verdict.

## Findings

- **Traceability and independence:** the test cites v0.5 and transcribes the section 5.2 literals/trees into test-owned fixture SQL. It creates each base catalog through the public CLI, then independently replaces only the two CHECK constraints. It does not invoke production classifiers or derive expectations from production normalization/output.
- **Accepted grammar sensitivity:** a completed-v4 engineer CHECK with whole-expression wrapping must no-op, and a deliberately permuted list containing each of the four exact capability literals once must no-op. This distinguishes semantic `IN` membership from one production serialization.
- **Quoted-literal rejection:** independent schemas change whitespace inside `'assignment_order. prepare'`, capability literal case in `'Installation.open'`, and engineer literal case in `'Construction_control_engineer'`. Every case requires exact conflict at v3.
- **Capability structure rejection:** separate extra-literal and duplicate-literal fixtures preserve all four required literals while adding invalid structure. They prevent a containment/subset classifier from passing and require exact v3 conflict.
- **Engineer tree rejection:** `(A OR B) AND C` uses the exact approved operands and quoted literals but a different parse tree. It prevents token-set/global-parentheses normalization and requires exact v3 conflict.
- **No mutation:** every accepted/rejected fixture inserts a valid engineer sentinel row and snapshots every table's exact `SHOW CREATE TABLE` plus rows. The post-run state must equal the pre-run state byte-for-byte at the observed seam.
- **Immediate stop before v4:** each rejected fixture instruments only the approved v4 deployment class inside its isolated child process. Exact v3 conflict plus marker absence proves the runner does not invoke v4; accepted fixtures and recovery use the real uninstrumented migration.
- **Cleanup and isolation:** every database and marker name is random and bounded. Databases and all registered markers are removed in outer `finally`; the shared artifact root is not mutated or deleted. The focused failure leaves `.test-artifacts` empty.
- **Valid RED:** reproduced exit `255` at `permuted exact capability IN literals`. Expected exact success with `appliedVersions:[]`; actual exact `SCHEMA_MIGRATION_CONFLICT`, `schemaVersion:3`, exit `2`. Configuration, charset fault, clean catalog, base repeat and whole-wrapper compatibility pass first, so the RED isolates missing order-insensitive completed-v4 recognition rather than fixture setup.
- **Prior coverage retained:** canonical catalog, exact output/redaction, environment isolation, present-empty versus absent, charset ordering, partial recovery, unexpected DDL failure and original v3 stop sentinel remain unchanged.

## Required changes

None. Gate 3 for `PRODUCTION-MIGRATION-RUNNER-001 v0.5` is approved for minimal Gate 4 implementation without changing the reviewed expectations.

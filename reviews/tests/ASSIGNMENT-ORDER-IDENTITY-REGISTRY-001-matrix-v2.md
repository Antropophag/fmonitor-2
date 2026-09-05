# Test review: ASSIGNMENT-ORDER-IDENTITY-REGISTRY-001 full matrix v2

- Reviewer: separately tasked agent `/root/admission_oracle_gate3`
- Test author: `/root`; reviewed commit authored by Timofey Grishin
- Reviewed commit: `7762c753591b6802e520508c2da659a03c77eff6`
- Specification: `specs/ASSIGNMENT-ORDER-IDENTITY-REGISTRY-001.md` v0.1, SHA256 `31ffe9a297af927f030947e00cbbf9629fe2ebf98a312d222cdbf7bff3f1896f`
- Public seam: production migration facade, typed verification apply/snapshot and phase observer, plus standard MariaDB catalog
- Red command and intended failure: each of the four standalone registry tests exits `1` at its explicit missing-public-engine assertion after successful real owned-database setup and exact cleanup
- Verdict: `APPROVED`

## Exact reviewed inputs

```text
31ffe9a297af927f030947e00cbbf9629fe2ebf98a312d222cdbf7bff3f1896f  specs/ASSIGNMENT-ORDER-IDENTITY-REGISTRY-001.md
e5cbce078a73019a1e04a2735c927f19abcbaefd444efeb79fd59a2caff2db2b  docs/operations/assignment-order-identity-registry-gate1-review-v01-2026-09-05.md
e4841961e89b7c7e39c11449579be71a38766c8b20be0023cb52db69b4c74073  tests/InstallationProcess/assignment_order_identity_registry_001_test.php
57f2f45aec8a364467cfe95bd447c83208c1aadeeb38165281cc887ab667e5ae  tests/InstallationProcess/assignment_order_identity_registry_schema_001_test.php
23e1453486ad26ac124edbdb921ae9a702ae5196ae895732d957cb8fc713392b  tests/InstallationProcess/assignment_order_identity_registry_recovery_001_test.php
42e885421aa0f73bce6080e25e9ae41470f4a9cc8c4b2bf644607c5e9c810ca1  tests/InstallationProcess/assignment_order_identity_registry_concurrency_001_test.php
b409eddac3fa05caf1283c7c2dbf96f36f7611f4bd1a86cb8fd4bbf06b89d02c  tests/Support/IdentityRegistryTestDatabase.php
d3c9a7b9584ade1c1d9a18f26f9f891fb08a85194e25aff5501dabce14aac655  tests/Support/assignment_order_identity_registry_worker.php
a381d6b4d3dc1d995b35756d6caa18d6f8a53f100656145760fd70d87d3c0cef  docs/operations/assignment-order-identity-registry-matrix-red-v2-2026-09-05.md
f0c52e558e44dceceb18e3f3f0db2cfa1921008f1bbca6f6c207b935f6bd21e1  reviews/tests/ASSIGNMENT-ORDER-IDENTITY-REGISTRY-001-matrix-v1.md
```

The populated/repeat tracer, recovery test, concurrency test, shared helper and worker remain byte-identical to v1. Only the schema test changed. This rereview supersedes v1's `CHANGES_REQUESTED` verdict for the combined test matrix; it does not approve an implementation or claim GREEN.

## Findings

All three v1 findings are closed.

Invalid-prefix sensitivity now proves the required ordering. A separate caller-owned `mysqli` is deliberately closed, then the public migration facade is invoked with the independently invalid 26-byte prefix. Only the fixed `InvalidArgumentException('Invalid registry migration configuration.')` is accepted. Any transaction, catalog, lock or other database access through that object fails differently, so an implementation cannot postpone validation until after harmless database reads and still pass. Existing live-connection cases continue to prove invalid characters/length cause zero mutation.

Historical source range coverage now treats order identity and case identity separately. For each field, independent fixtures set `0` and literal decimal `9223372036854775808`. The case fixture updates both the referenced installation case and the order reference while foreign-key enforcement is temporarily disabled, leaving a matching source relationship; rejection therefore cannot be satisfied merely by orphan detection. Each case snapshots the already-corrupt synthetic source and requires exact `SCHEMA_MIGRATION_CONFLICT`, no target/source/catalog/decoy mutation, and incomplete status. Literal SQL decimal values avoid PHP integer overflow or float comparison.

Cleanup target sensitivity now runs before the missing-engine assertion. An outer owner creates a similarly named database with a marker plus a similarly named account and captured grants. The inner public fixture cleanup must remove its exact task-owned schema and restricted account while the outer database, marker, account and grants remain observable. The outer harness then removes only its own exact decoys through attempt-all cleanup. This distinguishes exact ownership cleanup from prefix, age or wildcard deletion and also exercises the prior readonly-bind correction on the actual RED path.

Combined-matrix traceability and expected-value independence remain sound. Exact columns/types/nullability/defaults/encodings, indexes, FK/actions, named CHECK predicates, engine/collation, empty/populated receipts and literal hashes come from the approved specification rather than generated DDL. Prefix, transaction, incompatible-family, malformed source, receipt/history, frontier, exhaustion, denied-principal and privileged-recovery cases observe the public facade and catalog. Before/after snapshots prove preservation without defining expected target schema.

Recovery retains every specified phase interruption, exact phase prefixes and DDL truthfulness, pre-commit atomic rollback, post-commit acknowledgement-loss persistence, connection/transaction/lock release, compatible partial family recovery, a preserved current gap of `101`, changed-source recapture, non-1 release failure, retry and repeat behavior. Concurrency retains bounded pipe barriers and deadlines, the real same-prefix five-second timeout without DDL, independent-prefix progress, subsequent repeat, fresh-connection visibility before commit, actual TERM/reap, and one-time recovery.

The shared helper and worker remain task-owned, deterministic and isolated from production data or runtime hooks. Worker scope inputs are validated before connection, processes receive argv without shell interpolation, and cleanup aggregates worker, connection, database and user failures. No external system, real document, PII, protected E2E, safe-log or remote resource participates.

Independent reproduction at reviewed commit:

```text
$ php tests/InstallationProcess/assignment_order_identity_registry_001_test.php
RED_ASSERTION: public assignment-order identity registry migration is missing
Expected: true
Actual: false

$ php tests/InstallationProcess/assignment_order_identity_registry_schema_001_test.php
RED_ASSERTION: registry schema engine is missing
Expected: true
Actual: false

$ php tests/InstallationProcess/assignment_order_identity_registry_recovery_001_test.php
RED_ASSERTION: registry recovery engine is missing
Expected: true
Actual: false

$ php tests/InstallationProcess/assignment_order_identity_registry_concurrency_001_test.php
RED_ASSERTION: registry concurrency engine is missing
Expected: true
Actual: false
```

Each command exited `1` with no additional setup or cleanup failure. The changed schema test linted successfully, and `git diff --check` passed. These are intended missing-engine REDs; branches after the explicit assertion become mandatory GREEN obligations without changing approved expectations.

No blocking traceability, public-seam, expected-value independence, sensitivity, rejected-case, determinism, process-bound, setup-isolation or cleanup finding remains for the combined registry engine matrix. Gate 4 may implement only the approved engine behavior needed to satisfy these exact tests.

This approval does not establish engine GREEN or Gate 5, canonical migration registration, mixed-writer safety, selection writer/reader cutover, parent selection completion, protected E2E readiness, or product-policy change.

## Required changes

None.

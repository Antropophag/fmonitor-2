# Test review: PILOT-E2E-FLOW-001 v0.2

- Gate: 3 — fresh independent review of migration-setup correction
- Reviewer: separately tasked agent `/root/e2e_test_review_v3`
- Test author: separately tasked Gate 2 author; reviewer authored neither reviewed input
- Specification commit: `236a9eb9d51d1ebd88c852125bfd05051e49162e`
- Test commit / reviewed artifact: `815e4e9`
- Specification: `specs/PILOT-E2E-FLOW-001.md`, version `0.2`, `APPROVED`
- Test: `tests/InstallationProcess/pilot_e2e_flow_001_test.php`
- Public seam: configured production raw HTTP under `/pilot`, isolated MariaDB `fm2_*` state, and production artifact store
- Date: `2026-08-29`
- Verdict: `APPROVED`

## Findings

None.

## Review assessment

- **Correction scope:** relative to the previously approved test commit `6446cb9e430456c8cec5a34505384bcdfe3d7524`, commit `815e4e9` changes exactly two executable lines: it imports `ProcessCommandCapabilitiesSchemaMigration` and applies it in fixture setup. The approved v0.2 specification is byte-for-byte unchanged.
- **Production migration order:** setup now applies schema v1 (`ProductionProcessSchemaMigration`), v2 (`WorkforceCatalogSchemaMigration`), v3 (`ProcessUserCapabilitiesSchemaMigration`), then the forward v4 `ProcessCommandCapabilitiesSchemaMigration`, followed by the workforce-history extension. This is required because v4 explicitly accepts only the exact v3 capability table and expands its CHECK before the fixture inserts `assignment_order.confirm_registration` and `installation.open`. The same v1 → v2 → v3 → v4 order is exercised by the approved production-composition and artifact-store tests.
- **Setup validity:** the migration completes, the test verifies that the database CHECK contains all four exact capabilities, and the capability fixture inserts successfully. Thus the former failure at the v3-only CHECK is removed without bypassing production migration behavior or weakening its assertion.
- **Coverage unchanged:** all HTTP journey, routing/method, auth/capability, CSRF/body, PRG/error, concurrency, queue/card, artifact integrity, persistence/history, no-task, and no-legacy-write assertions are byte-for-byte unchanged from `6446cb9`. No expected value, oracle, mock, seam, fixture fact, or production file changed.
- **Traceability and independence:** the test still cites `PILOT-E2E-FLOW-001 v0.2` at specification commit `236a9eb`; its exact artifact oracle and all expected values retain the preceding independent approval. This review relies only on the approved specification, test diff, migration contracts, and observed run—not planned implementation.
- **Determinism and isolation:** the test continues to use random bounded database/user/artifact names, fixed clocks and identities, real MariaDB connections, production migrations, and `finally` cleanup. The clean detached review run left no database fixture, artifact directory, pilot server, or worktree modification other than this review record.

## RED verification

Command run in a clean detached worktree at exact commit `815e4e9`:

```text
$ php tests/InstallationProcess/pilot_e2e_flow_001_test.php
PHP Fatal error: Uncaught TestFailure: prepare uses POST seam
Expected: 1
Actual: 0
at tests/InstallationProcess/pilot_e2e_flow_001_test.php:55
exit code: 255
```

The test passes database creation, all five production migration calls, capability-CHECK inspection, capability inserts, workforce fixture setup, production server startup, queue GET, card GET, and prepare GET. It first fails when the configured production prepare page lacks the specified POST form. This is the intended missing production HTTP behavior, not migration/setup, dependency, dirty-state, or artifact-oracle failure.

## SHA-256 reviewed-input manifest

```text
980bc3a522738fab7083d352d662f92625427295e73a44cafc10de1d31c0b0bb  specs/PILOT-E2E-FLOW-001.md
ac0231ddcf7cc31aca74eabffced8914f575202e737740b52ff6e070868353e7  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

Git blob identities:

```text
1efd59fbf6904992c6859afe0e0845545a7d6e0d  specs/PILOT-E2E-FLOW-001.md
f5ca58e1d2139cf3a3240496499af649501e35c9  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

Any byte change to either reviewed input invalidates this approval. The review record is excluded from the self-referential manifest.

## Required changes

None. Gate 3 is approved for test commit `815e4e9`; Gate 4 may proceed only against these exact reviewed inputs.

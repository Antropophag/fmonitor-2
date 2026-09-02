# CLASSIFICATION-PROVENANCE-SCHEMA-001 v2 — independent Gate 5 code review

Date: 2026-09-02  
Reviewer: separately tasked independent agent `/root/classification_code_review`  
Gate: 5  
Verdict: **CHANGES_REQUIRED**

The reviewer did not author or edit the reviewed production code or tests. This
append-only review record is the reviewer's only slice edit.

## Exact reviewed inputs

```text
d6227243dad996c7f67e3b0e8e9fcac0c100567e101ca66220a00946034e4790  specs/CLASSIFICATION-PROVENANCE-SCHEMA-001.md
8de39b681a64ef8a74c497c700e15f1a461930214fe2aa8320940b18490061cc  tests/InstallationProcess/classification_provenance_schema_001_test.php
409a00d9d6c0cb929a6a91800d115cc81245e7349e768ef21f66fb798a6a6c56  tests/Support/classification_provenance_barrier_runner.php
57a0d5750275b47ec9a7d6fd112a947d911a574d0c914c1d2635c7824971c086  app/InstallationProcess/ClassificationProvenanceDefinitionSchemaMigration.php
249dba52cfa5769d8832633aba95cec20f0769d9d63a011567d05d7380787a98  app/InstallationProcess/MariaDbClassificationProvenanceSchemaFingerprint.php
e2828754f5e915f53e3ba78d29d8fad5e168e020c655fd925f1555525044a3dc  app/InstallationProcess/ClassificationProvenanceSchemaMigration.php
e9caa610a952ba9bcbef28dd6e17996e3c83cc5a51c82f527e7b44a99625acf9  bin/fmonitor2-migrate.php
2e7dbf6b3e7f01e0682170767dea9fff39e894364992f0c78cda3d7b8a038b8c  rapid-pilot/legacy-migration/LegacyMigrationRouter.php
d74a9fadba9d35dcc66e8a958af8ee55e50d23f490880176ae114407ebdc1e1a  rapid-pilot/batch-import-native-candidates.php
a230cf1b1da91cd71901b162c0117a93e71f69ea753efd7d4a394d37173b95d0  rapid-pilot/batch-import-legacy-history.php
13b6b879c30ac66af524ca1dcd8468e408e8427d0c4fa3220e4e458316e2cc56  rapid-pilot/batch-import-legacy-active.php
```

The approved amended Gate 1 spec hash and the Gate 3-approved verifier/helper
hashes are unchanged. The owner approval and fresh Gate 3 verdict in
`grill-009-classification-session-exact-hash-approval-2026-09-02.md` and
`reviews/tests/CLASSIFICATION-PROVENANCE-SCHEMA-001-v4.md` were verified.

## Standards axis

No blocking production-code standards finding was found. The implementation
keeps one canonical definition and one semantic fingerprint, validates the
prefix before database access at the public CLI, and limits runtime code to a
read-only schema precondition plus provenance DML. No new domain logic was
placed in `rapid-pilot`; the edited code there remains migration adapter code.

The mandatory `beforeCreate` callable is a narrow verifier seam. The production
catalogue always supplies an unconditional no-op closure. Repository inspection
found no argv, environment or supported configuration path that can substitute
the verifier coordinator, and no production `GET_LOCK`, `SLEEP`, advisory lock,
durable/ephemeral ledger or artificial delay. The verifier helper alone owns
arrival/release coordination.

## Spec axis

The reviewed production behavior matches the approved v2 contract: literal v11
ordering and manifest, exact/conflict/repeat behavior, prefix preservation,
plain-CREATE race behavior, and the three pre-source runtime readiness checks
are implemented. Runtime classification provenance DDL was removed. The focused
verifier passes, including populated preservation, decoys, DDL-denied operation,
three source sentinels and `PILOT_ONLY_OUTPUT_WITHOUT_PROVENANCE`.

One blocking integration finding remains:

1. **[blocking] The landed v11 catalogue is not reconciled through inherited
   canonical-runner fixtures, so the required full regression gate is RED.**
   `make verify` fails `db-test` and `characterization-test` on multiple literal
   terminal-v10 expectations and catalogue inventories. This includes
   `production_migration_runner_001_test.php`,
   `workforce_canonical_runner_001_test.php`, schema predecessor tests,
   `verify-calendar-projections.php`, and
   `harness_otiz_canonical_compat_001_test.php`. The approved spec requires
   focused/full/fresh verification and task 4.2 explicitly requires
   `make verify`; tasks 4.1 and 4.2 remain open. Reconcile the inherited fixtures
   to approved terminal v11 and rerun Gate 5 review.

`tests/InstallationProcess/pilot_case_import_001_test.php` is the same stale
harness class, not evidence of a production migration defect: with the canonical
admin credential it expects v10 while the runner correctly returns v11. Its
standalone fallback password is also non-canonical, but after supplying the
canonical credential the first semantic failure remains the stale v10 result.
It must be updated as integration work; it does not justify changing v11.

The other current `make verify` failures from independently approved intentional
RED slices (session storage, work-navigation removal and RBAC) are not attributed
to classification v11. Two runtime-DDL fixture failures that try to create a
classification table already created by v11 are likewise inherited fixture
reconciliation needs.

## Reproduced verification

```text
php tests/InstallationProcess/classification_provenance_schema_001_test.php
PILOT_ONLY_OUTPUT_WITHOUT_PROVENANCE
PASS CLASSIFICATION-PROVENANCE-SCHEMA-001 deterministic verifier

php rapid-pilot/verify-history-batch-import.php
PASS: historical batch import is bounded, resumable and legacy-read-only

php rapid-pilot/verify-active-batch-import.php
PASS: active baseline batch is bounded, resumable, template-bound and legacy-read-only

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

openspec validate canonicalize-classification-provenance-schema --strict
Change 'canonicalize-classification-provenance-schema' is valid

git diff --check
exit 0, empty output

make verify
FULL_VERIFICATION_FAILURE count=4 stages=unit-test,db-test,characterization-test,e2e-test
```

## Verdict

The v11 production implementation itself has no identified standards or
contract defect, but Gate 5 and Done cannot be approved while the slice's
catalogue-wide v11 integration fixtures remain stale and `make verify` has not
completed. Verdict: **CHANGES_REQUIRED**. A fresh review is required after that
test-only reconciliation and verification evidence.

# Gate 5 code review: CHECKLIST-TEMPLATE-SCHEMA-001

## Superseding Gate 5 rereview v2

- Reviewed at: `2026-09-01` (`Europe/Moscow`)
- Reviewer: separately tasked agent `/root/checklist_v7_code_rereview`
- Independence: reviewer authored none of the specification, OpenSpec artifacts,
  tests, implementation, RED/GREEN evidence, or prior reviews
- Fixed point: `c57663db0773e8dd6edf5fa109a4dbcb8977ebe8`
- Scope: named checklist-template v7 production/runtime changes, their catalogue
  regressions and approved evidence only; unrelated dirty-worktree changes were
  excluded
- Prior verdict superseded: `CHANGES_REQUESTED` at prior review hash
  `dd577c5e2c0ddf6a04f4ef0bfc4f654fd5165bc7dcbd28a013ac8b846044fd12`
- Verdict: `APPROVED`

This approval applies only to the exact manifest below. No blocking Standards
or Specification findings remain.

### Standards axis

The prior operational-failure blocker is closed. Both runtime adapters catch
only the migration-domain `DatabaseUnavailable` result used for unsafe or
inapplicable database collation metadata. Raw `mysqli`/driver, permission and
programming failures from inspection are not caught and therefore propagate;
the dedicated closed-connection cases exercise both public mutating consumers.
Inspected absent/incompatible families still receive only the stable redacted
`CHECKLIST_TEMPLATE_SCHEMA_REQUIRED` precondition.

No new security or maintainability blocker was found. Prefix validation occurs
before database access, table names are derived only from the validated ASCII
prefix and literals, and the database collation is validated and trial-applied
before identifier quoting. The prior non-blocking positional-manifest / repeated
inspection-query smell remains a reasonable later deepening opportunity, not a
reason to widen this minimal migration slice.

### Specification axis

The prior wrapper-ordering blocker is closed: `associateActiveBaseline()` now
calls the exact-family precondition before either baseline or snapshot query,
while direct `associate()` retains its own guard. Approved absent and
incompatible wrapper fixtures verify the redacted outcome and zero mutation.

The catalogue oracle is now selective: MariaDB physical `longtext` is treated
as logical `json` only for predecessor
`fm2_process_events.payload_json`, whose v1 contract retains the implicit
`json_valid` check. Checklist
`fm2_checklist_template_snapshots.payload_json` remains literal `longtext`, and
the shared and dedicated contracts require no checklist CHECK. Fresh independent
Gate 3 rereview v8 approved these exact test hashes.

The full implementation rereview also confirms literal v7 registration exactly
once after v1-v6; exact column/default/generated/index/engine/charset/collation
fingerprints; inherited safe UCA-alias handling; whole-family preflight before
DDL; normative conflict order; compatible partial completion; zero mutation on
conflict/repeat; runtime no-DDL ownership; runner redaction/failure routing; and
the intended snapshot/association immutable write and replay outcomes.

### Fresh verification evidence v2

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/checklist_template_schema_001_test.php
CHECKLIST-TEMPLATE-SCHEMA-001 migration runner test passed

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/production_migration_runner_001_test.php
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract

$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

$ openspec validate canonicalize-checklist-template-schema --strict
Change 'canonicalize-checklist-template-schema' is valid

$ make verify
VERIFY_STAGE migrate PASS
VERIFY_STAGE architecture-check PASS
VERIFY_STAGE lint PASS
VERIFY_STAGE unit-test PASS
REGRESSION_FAILURE: 8 verifier(s) failed
VERIFY_STAGE db-test FAIL
VERIFY_STAGE characterization-test PASS
REGRESSION_FAILURE: 1 verifier(s) failed
VERIFY_STAGE e2e-test FAIL
VERIFY_STAGE diff-check PASS
FULL_VERIFICATION_FAILURE count=2 stages=db-test,e2e-test
```

The fresh full run is exactly the established unrelated baseline: eight DB
failures and the existing E2E missing-assignment-artifact failure only;
characterization passes. The v7 dedicated test and shared v1-v7 catalogue both
pass within that run.

### Exact artifact manifest v2

```text
fe7e2fab97faefbea6ea890c5b44a2de136590ea54b12c03a5824a5907922e44  app/InstallationProcess/ChecklistTemplateSchemaMigration.php
9338de86cd797521f8ccab26174b8ce91edbfc2825b8ff7636ca65c6de8ccaf1  bin/fmonitor2-migrate.php
ee1d93212ce1b65f08f51e43b4064b9ef8acc45f1cf212e9da4d4815cad73805  rapid-pilot/legacy-migration/LegacyChecklistTemplateSnapshot.php
5b9394e62e0846f93a60a63e631895885dd62d0515578a6e51b2d7cf5ba0cb3b  rapid-pilot/legacy-migration/ChecklistTemplateAssociation.php
32de4c5c6ace6ebcb8d94735002be0bd4a542b46f2c1ff15781c1aae2201ee79  rapid-pilot/verify-active-baseline-case-connector.php
9094170947b65fdae21b9136a5438ad26ced37d82fef3cd48e6c959b106063e8  tests/InstallationProcess/checklist_template_schema_001_test.php
f3fd143860c903139e484e8d04ea151185260bbbe60ff0e629cb88d5cd397f0d  tests/InstallationProcess/production_migration_runner_001_test.php
cdcada85ce500439032650728e29eb42ee63bb8146b0bbf4381a538a9d36984b  tests/Support/ProductionMigrationRunnerCatalogContract.php
aa487835583045729e313571a0a6a5ed71945b889b254e7babcc5532d30a3edb  tests/InstallationProcess/identity_access_schema_001_test.php
9ab22c4b70ff37fae415eca3f1e2071dc8196ca28b27335992393a98fe44eb75  tests/InstallationProcess/pilot_case_import_001_test.php
f83de8f3b898c416760c545f15a86d43131431b12f58f522c512d458b0d9beff  tests/InstallationProcess/pilot_http_auth_001_test.php
7e0badf3b9a8a52208fd3f78fcefdf071d3e3c1cbce065e4eecb002a7ab68eae  tests/InstallationProcess/workforce_canonical_runner_001_test.php
7254b9ec4a3ba7f7b846e33eb97a853708c38c2290a00f7511d396b4fb562168  tests/Verification/harness_otiz_canonical_compat_001_test.php
2951eff3c1f1c743d0fb5ad797ce2f678568e919e2f8375941dd70c23bc63f23  specs/CHECKLIST-TEMPLATE-SCHEMA-001.md
c7b6d267e2d067a8a2aef51e651e0c62d5230b43e00edabf87a17a3a4e133e00  reviews/tests/CHECKLIST-TEMPLATE-SCHEMA-001.md
31efda3256b9a0e01538da431ba2082e55f11133d037938ee474b18e221d1963  docs/operations/checklist-template-schema-red-evidence.md
b029fe7ac990e62fe532360396786c4fb4e09e078b1c0adc9765931f61282bad  docs/operations/checklist-template-schema-green-verification.md
4ec0cb4c5f4aabd151558f2cad455a78c242acdeb79efe6a8db069077fdf2214  docs/operations/checklist-template-schema-gate1-review.md
5d94f9a6bb3f1d8654aeb1ba3604f6415a779032e6aaaa55b26624514da460e3  openspec/changes/canonicalize-checklist-template-schema/proposal.md
baf750cb17c8dc35ad1edef603d6457c8fbcec7824ce85ba0e466c25ec5cc711  openspec/changes/canonicalize-checklist-template-schema/design.md
09ee91894c45be1faf40a97946c874e5b38abd9d288d3e2a8d2d17bf4637e4b6  openspec/changes/canonicalize-checklist-template-schema/tasks.md
c217f7446f9c3c231bf78ba5e61611644f14327afe481e26999c7277e0c34f5c  openspec/changes/canonicalize-checklist-template-schema/.openspec.yaml
1a998b9ead7b76d1853a693eeadb82d2a9aeb20426e236d13c1491affe659b2d  openspec/changes/canonicalize-checklist-template-schema/specs/deployment/canonical-checklist-template-schema/spec.md
```

## Prior Gate 5 history preserved below

- Reviewed at: `2026-09-01` (`Europe/Moscow`)
- Reviewer: separately tasked agent `/root/checklist_v7_code_review`
- Independence: reviewer authored none of the specification, OpenSpec artifacts,
  test, implementation, RED/GREEN evidence, or prior reviews
- Fixed point: `c57663db0773e8dd6edf5fa109a4dbcb8977ebe8`
- Scope: only the named checklist-template v7 implementation, runtime wiring,
  catalogue regressions, approved test, and planning/evidence artifacts; all
  unrelated dirty-worktree changes were excluded
- Verdict: `CHANGES_REQUESTED`

This verdict applies only to the exact artifact manifest below. OpenSpec tasks
`4.1` and `4.2` must remain unchecked. The slice is not ready to land.

## Standards axis

### Blocking: runtime adapters mask unexpected inspection failures

`LegacyChecklistTemplateMySqlTarget::apply()` at
`rapid-pilot/legacy-migration/LegacyChecklistTemplateSnapshot.php:45-46` and
`ChecklistTemplateAssociationTarget::associate()` at
`rapid-pilot/legacy-migration/ChecklistTemplateAssociation.php:30` catch every
`Throwable` from the public schema inspection and silently convert connection,
permission, driver, and programming failures to
`CHECKLIST_TEMPLATE_SCHEMA_REQUIRED`.

That makes an operational failure indistinguishable from an inspected absent or
incompatible family and discards its cause. Preserve the stable redacted
precondition for a completed negative compatibility result, but do not silently
classify every unexpected failure as that result. The corrected boundary must
retain redaction without hiding unexpected failure semantics.

### Non-blocking maintainability note

`ChecklistTemplateSchemaMigration.php:70-129` introduces another positional,
pipe-delimited schema-manifest representation and repeats `information_schema`
query shapes already owned by schema-inspection code. This is a judgement-call
Duplicated Code / Primitive Obsession smell, not a Gate 5 blocker for this
minimal slice. A later deepening should prefer structured manifests and shared
inspector operations.

## Specification axis

### Blocking: `associateActiveBaseline()` bypasses the required precondition

`rapid-pilot/legacy-migration/ChecklistTemplateAssociation.php:42-46` reads the
baseline and checklist snapshot tables before calling `associate()`, whose
family precondition is at line 30. If the checklist family is absent, the
snapshot read can throw a raw `mysqli_sql_exception`, including prefixed table
and driver details, instead of the stable
`CHECKLIST_TEMPLATE_SCHEMA_REQUIRED` outcome.

This violates specification section 5, which explicitly includes
`associateActiveBaseline(...)` through the association seam and requires an
absent or incompatible family to be rejected with the redacted stable
precondition before runtime mutation/repair. Perform the exact-family preflight
before the wrapper's reads while retaining the direct `associate()` guard.

The approved dedicated test does not exercise this wrapper on absent and
incompatible families, so its passing result does not detect the defect.

### Blocking: the v1-v7 catalogue oracle erases `LONGTEXT` versus `JSON`

`tests/Support/ProductionMigrationRunnerCatalogContract.php:21-23` records
snapshot `payload_json` as `json`. The approved specification section 3.1 says
the opposite: `payload_json` remains exact `LONGTEXT`, not `JSON`, specifically
to avoid an implicit `json_valid` constraint.

`tests/InstallationProcess/production_migration_runner_001_test.php:136-140`
then normalizes every `LONGTEXT payload_json` observation to `json`, masking
this distinction in a contract described as the exact v1-v7 catalogue. The
production migration and dedicated approved test currently use the correct
`LONGTEXT`; the shared catalogue regression must also transcribe it literally.
Because correcting this changes test expectations after the approved Gate 3
hash, the corrected test artifact requires fresh independent test review under
the repository gate process before Gate 5 is repeated.

## Conforming areas

- Literal v7 is wired exactly once after landed v1-v6.
- Migration inspects the complete family before DDL, reports conflicts in
  normative order, and does not create a missing sibling when a conflict exists.
- Exact production column/index/engine/collation manifests match the dedicated
  approved schema contract, including `LONGTEXT payload_json`.
- Prefix validation and inherited MariaDB UCA-alias normalization are used.
- Both direct runtime mutation paths have had lazy DDL removed.
- Runner conflict redaction and unexpected migration-step failure routing remain
  wired through the canonical migration application.

## Fresh verification evidence

```text
$ php -l app/InstallationProcess/ChecklistTemplateSchemaMigration.php
No syntax errors detected in app/InstallationProcess/ChecklistTemplateSchemaMigration.php

$ php -l bin/fmonitor2-migrate.php
No syntax errors detected in bin/fmonitor2-migrate.php

$ php -l rapid-pilot/legacy-migration/LegacyChecklistTemplateSnapshot.php
No syntax errors detected in rapid-pilot/legacy-migration/LegacyChecklistTemplateSnapshot.php

$ php -l rapid-pilot/legacy-migration/ChecklistTemplateAssociation.php
No syntax errors detected in rapid-pilot/legacy-migration/ChecklistTemplateAssociation.php

$ php -l tests/InstallationProcess/checklist_template_schema_001_test.php
No syntax errors detected in tests/InstallationProcess/checklist_template_schema_001_test.php

$ git diff --check -- <named slice files and artifacts>
[no output; exit 0]

$ openspec validate canonicalize-checklist-template-schema --strict
Change 'canonicalize-checklist-template-schema' is valid

$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

$ docker compose -f compose.test.yaml up -d --wait test-db
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/checklist_template_schema_001_test.php
CHECKLIST-TEMPLATE-SCHEMA-001 migration runner test passed

$ docker compose -f compose.test.yaml down -v --remove-orphans
[test database container and network removed]

$ docker compose -f compose.test.yaml ps
[no services]
```

The recorded Gate 4 full verification reached the established baseline: migrate
v1-v7, architecture, lint, unit, characterization, and diff checks passed; DB
ended with the known eight failures and E2E with the known missing-artifact
failure. Those baseline failures are unrelated to this review's blockers.

## Exact artifact manifest

```text
fe7e2fab97faefbea6ea890c5b44a2de136590ea54b12c03a5824a5907922e44  app/InstallationProcess/ChecklistTemplateSchemaMigration.php
9338de86cd797521f8ccab26174b8ce91edbfc2825b8ff7636ca65c6de8ccaf1  bin/fmonitor2-migrate.php
2a0b6ee68cb7f710c3c735fdeb00e275265e18529d01f682ded1d0af668437dd  rapid-pilot/legacy-migration/LegacyChecklistTemplateSnapshot.php
b9d38b204973217801649f4c658c6acfaa77e624b707e6274fd441af827643ca  rapid-pilot/legacy-migration/ChecklistTemplateAssociation.php
32de4c5c6ace6ebcb8d94735002be0bd4a542b46f2c1ff15781c1aae2201ee79  rapid-pilot/verify-active-baseline-case-connector.php
1f0f0d9d23c70e92ad8ae3504d46a2ef5c7ba8f59a90ae2074a561dc8c794e6e  tests/InstallationProcess/checklist_template_schema_001_test.php
aa487835583045729e313571a0a6a5ed71945b889b254e7babcc5532d30a3edb  tests/InstallationProcess/identity_access_schema_001_test.php
9ab22c4b70ff37fae415eca3f1e2071dc8196ca28b27335992393a98fe44eb75  tests/InstallationProcess/pilot_case_import_001_test.php
f83de8f3b898c416760c545f15a86d43131431b12f58f522c512d458b0d9beff  tests/InstallationProcess/pilot_http_auth_001_test.php
8c912f9d7672f9fecb7aff29ebefaa0b11aa633ac65845a82ec3190b810dbf4e  tests/InstallationProcess/production_migration_runner_001_test.php
7e0badf3b9a8a52208fd3f78fcefdf071d3e3c1cbce065e4eecb002a7ab68eae  tests/InstallationProcess/workforce_canonical_runner_001_test.php
6751e09ccaac14050d477aebbde7474dc1db5ac3142d30922c4d9dc8d6777508  tests/Support/ProductionMigrationRunnerCatalogContract.php
7254b9ec4a3ba7f7b846e33eb97a853708c38c2290a00f7511d396b4fb562168  tests/Verification/harness_otiz_canonical_compat_001_test.php
2951eff3c1f1c743d0fb5ad797ce2f678568e919e2f8375941dd70c23bc63f23  specs/CHECKLIST-TEMPLATE-SCHEMA-001.md
24e465f285d72b66bec8b7c0bc17b51f48cbab587e96a35cb2fdd78818a0b728  reviews/tests/CHECKLIST-TEMPLATE-SCHEMA-001.md
cc6d1acebe3e35a3d9ac8dc192eabf97b74984995a56107cae7809a36a580a26  docs/operations/checklist-template-schema-red-evidence.md
b029fe7ac990e62fe532360396786c4fb4e09e078b1c0adc9765931f61282bad  docs/operations/checklist-template-schema-green-verification.md
4ec0cb4c5f4aabd151558f2cad455a78c242acdeb79efe6a8db069077fdf2214  docs/operations/checklist-template-schema-gate1-review.md
5d94f9a6bb3f1d8654aeb1ba3604f6415a779032e6aaaa55b26624514da460e3  openspec/changes/canonicalize-checklist-template-schema/proposal.md
baf750cb17c8dc35ad1edef603d6457c8fbcec7824ce85ba0e466c25ec5cc711  openspec/changes/canonicalize-checklist-template-schema/design.md
09ee91894c45be1faf40a97946c874e5b38abd9d288d3e2a8d2d17bf4637e4b6  openspec/changes/canonicalize-checklist-template-schema/tasks.md
c217f7446f9c3c231bf78ba5e61611644f14327afe481e26999c7277e0c34f5c  openspec/changes/canonicalize-checklist-template-schema/.openspec.yaml
1a998b9ead7b76d1853a693eeadb82d2a9aeb20426e236d13c1491affe659b2d  openspec/changes/canonicalize-checklist-template-schema/specs/deployment/canonical-checklist-template-schema/spec.md
```

## Required next steps

1. Correct unexpected inspection-failure handling without leaking sensitive DB
   details.
2. Preflight the exact family before `associateActiveBaseline()` reads it and
   add sensitive absent/incompatible wrapper coverage.
3. Correct the shared catalogue oracle to exact `LONGTEXT` without type
   normalization and obtain the required fresh independent test review.
4. Re-run focused GREEN, relevant regressions, architecture check and full
   verification, then request a fresh independent Gate 5 review.

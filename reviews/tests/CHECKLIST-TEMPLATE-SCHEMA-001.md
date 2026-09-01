# Test rereview v8: CHECKLIST-TEMPLATE-SCHEMA-001 v0.1

- Gate: 3 — fresh independent rereview after selective JSON/LONGTEXT oracle fix
- Reviewed at: `2026-09-01` (`Europe/Moscow`)
- Reviewer: separately tasked agent `/root/checklist_v7_oracle_rereview`
- Independence: reviewer authored none of the specification, OpenSpec artifacts,
  tests, production, RED/GREEN evidence, or prior reviews
- Test author: separately tasked agent `/root/checklist_v7_red`
- Reviewed commit: `c57663db0773e8dd6edf5fa109a4dbcb8977ebe8`
- Specification: `specs/CHECKLIST-TEMPLATE-SCHEMA-001.md`, version `0.1`,
  status `GATE_1_APPROVED`
- Verdict: `APPROVED`

This verdict applies only to the exact SHA-256 manifest below. It closes the
Gate 3 restart required by the prior Gate 5 catalogue-oracle finding; it does
not approve production code or Gate 5.

## Review result

The selective normalization is correct and sensitive to both contracts.
`production_migration_runner_001_test.php` maps MariaDB's physical
`longtext` representation to logical `json` only when the table is exact
`fm2_process_events` and the column is exact `payload_json`. The independent
catalogue contract retains that predecessor column as `json` and separately
requires its implicit `json_valid(payload_json)` CHECK.

`fm2_checklist_template_snapshots.payload_json` is not covered by the alias
branch. Its test-owned catalogue literal is exact `longtext`, and longtext is
explicitly treated as a utf8mb4 textual column. No checklist-template CHECK is
present in the shared catalogue contract. The unchanged dedicated v7 test
independently requires physical `longtext` and an empty CHECK set for the
snapshot table, so either accidentally broadening the alias or adding an
implicit/explicit JSON validity constraint remains observable.

The dedicated test hash is unchanged from v7. Its public runtime boundary
fixtures still cover absent and incompatible `associateActiveBaseline()`
preflight before raw lookup, plus closed-connection inspection failures for
both mutating consumers. The complete approved migration/runtime matrix is
therefore retained rather than weakened to repair the shared oracle.

No blocking or non-blocking findings remain in the reviewed test scope.

## Fresh verification evidence v8

```text
$ php -l tests/InstallationProcess/production_migration_runner_001_test.php
No syntax errors detected in tests/InstallationProcess/production_migration_runner_001_test.php

$ php -l tests/InstallationProcess/checklist_template_schema_001_test.php
No syntax errors detected in tests/InstallationProcess/checklist_template_schema_001_test.php

$ php -l tests/Support/ProductionMigrationRunnerCatalogContract.php
No syntax errors detected in tests/Support/ProductionMigrationRunnerCatalogContract.php

$ openspec validate canonicalize-checklist-template-schema --strict
Change 'canonicalize-checklist-template-schema' is valid

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/production_migration_runner_001_test.php
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract
[exit 0]

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/checklist_template_schema_001_test.php
CHECKLIST-TEMPLATE-SCHEMA-001 migration runner test passed
[exit 0]

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_CTS_RUNNER=/home/antropophag/code/fmonitor-2-checklist-v7-rereview-predecessor/bin/fmonitor2-migrate.php \
    php tests/InstallationProcess/checklist_template_schema_001_test.php
Expected: schemaVersion 7, appliedVersions [1,2,3,4,5,6,7]
Actual:   schemaVersion 6, appliedVersions [1,2,3,4,5,6]
[exit 255; qualifying missing-v7 RED after successful v1-v6]

$ git diff --check -- tests/InstallationProcess/production_migration_runner_001_test.php tests/Support/ProductionMigrationRunnerCatalogContract.php tests/InstallationProcess/checklist_template_schema_001_test.php docs/operations/checklist-template-schema-red-evidence.md
[no output; exit 0]

$ SELECT SCHEMA_NAME ... WHERE SCHEMA_NAME LIKE 't_cts_%' OR SCHEMA_NAME LIKE 't_pmr_%'
[no rows]
```

## SHA-256 manifest reviewed in v8

```text
2951eff3c1f1c743d0fb5ad797ce2f678568e919e2f8375941dd70c23bc63f23  specs/CHECKLIST-TEMPLATE-SCHEMA-001.md
9094170947b65fdae21b9136a5438ad26ced37d82fef3cd48e6c959b106063e8  tests/InstallationProcess/checklist_template_schema_001_test.php
f3fd143860c903139e484e8d04ea151185260bbbe60ff0e629cb88d5cd397f0d  tests/InstallationProcess/production_migration_runner_001_test.php
cdcada85ce500439032650728e29eb42ee63bb8146b0bbf4381a538a9d36984b  tests/Support/ProductionMigrationRunnerCatalogContract.php
31efda3256b9a0e01538da431ba2082e55f11133d037938ee474b18e221d1963  docs/operations/checklist-template-schema-red-evidence.md
b6a22272ae9359f464d95b647c2ba07631f07b16e149ac6643a474071e6a7356  reviews/tests/CHECKLIST-TEMPLATE-SCHEMA-001.md (prior record before this append)
dd577c5e2c0ddf6a04f4ef0bfc4f654fd5165bc7dcbd28a013ac8b846044fd12  reviews/code/CHECKLIST-TEMPLATE-SCHEMA-001.md (Gate 5 findings reviewed as scope evidence)
```

# Test rereview v7: CHECKLIST-TEMPLATE-SCHEMA-001 v0.1

- Gate: 3 — fresh independent rereview after Gate 5 findings
- Reviewed at: `2026-09-01` (`Europe/Moscow`)
- Reviewer: separately tasked agent `/root/checklist_v7_gate5_test_review`
- Independence: reviewer authored none of the specification, OpenSpec artifacts,
  tests, production, RED/GREEN evidence, or prior reviews
- Test author: separately tasked agent `/root/checklist_v7_red`
- Reviewed commit: `c57663db0773e8dd6edf5fa109a4dbcb8977ebe8`
- Specification: `specs/CHECKLIST-TEMPLATE-SCHEMA-001.md`, version `0.1`,
  status `GATE_1_APPROVED`
- Verdict: `CHANGES_REQUESTED`

This verdict applies only to the exact SHA-256 manifest below. OpenSpec task
`2.2` and Gate 5 completion must not be advanced on this artifact.

## Blocking finding: shared catalogue regression breaks the pre-v7 JSON contract

The Gate 5 correction successfully stops normalizing
`fm2_checklist_template_snapshots.payload_json`: the test-owned catalogue now
expects literal `longtext`, and the dedicated v7 test independently verifies
that column plus the absence of a `json_valid` CHECK.

However, `production_migration_runner_001_test.php` removed the MariaDB JSON
alias normalization for every catalogue column. MariaDB reports the existing
approved `fm2_process_events.payload_json JSON` declaration physically as
`longtext` plus its implicit `json_valid(payload_json)` CHECK. The test-owned
contract still correctly records that earlier column as `json`, so the current
shared regression fails before completing its prior v1-v7 matrix:

```text
fm2_process_events exact types, nullability, order and extras.
Expected payload_json type: json
Actual payload_json type:   longtext
[exit 255]
```

This is not a production failure and must not be solved by changing the v1
contract. Narrow correction: normalize the physical MariaDB alias to `json`
only for the catalogue column whose approved contract is JSON (or otherwise
derive that distinction from the test-owned expected entry and its required
`json_valid` CHECK). Keep checklist snapshot `payload_json` literal `longtext`
and continue to require no CHECK there. Then rerun the shared catalogue test
and request another fresh Gate 3 review.

## Conforming reviewed additions

- Both absent and incompatible family fixtures call public
  `associateActiveBaseline()` before either baseline or snapshot raw lookup,
  receive exact redacted `CHECKLIST_TEMPLATE_SCHEMA_REQUIRED`, and compare the
  complete schema/rows runtime state before and after.
- Closed-connection fixtures separately exercise public snapshot `apply()` and
  association `associate()`. The current GREEN run reaches both and proves
  their unexpected driver/programming failures are not converted to the stable
  compatibility outcome.
- The dedicated v7 matrix remains intact and passes against current production,
  including migration preservation, both partial directions, full fingerprint
  conflict dimensions, prefixes, collation aliases, DDL-denied runtime,
  immutable outcomes, fail-closed consumers, and the architecture ratchet.
- The exact same dedicated test hash produces the qualifying predecessor RED
  (successful canonical v1-v6, missing literal v7) and current GREEN.
- Strict OpenSpec validation passes.

## Fresh verification evidence v7

```text
$ php -l tests/InstallationProcess/checklist_template_schema_001_test.php
No syntax errors detected in tests/InstallationProcess/checklist_template_schema_001_test.php

$ php -l tests/InstallationProcess/production_migration_runner_001_test.php
No syntax errors detected in tests/InstallationProcess/production_migration_runner_001_test.php

$ openspec validate canonicalize-checklist-template-schema --strict
Change 'canonicalize-checklist-template-schema' is valid

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_CTS_RUNNER=/home/antropophag/code/fmonitor-2-checklist-v7-gate3-review/bin/fmonitor2-migrate.php \
    php tests/InstallationProcess/checklist_template_schema_001_test.php
Expected: schemaVersion 7, appliedVersions [1,2,3,4,5,6,7]
Actual:   schemaVersion 6, appliedVersions [1,2,3,4,5,6]
[exit 255]

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/checklist_template_schema_001_test.php
CHECKLIST-TEMPLATE-SCHEMA-001 migration runner test passed
[exit 0]

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/production_migration_runner_001_test.php
fm2_process_events exact types, nullability, order and extras:
expected json, actual longtext
[exit 255]
```

The detached predecessor worktree was clean at exact commit `c57663d`; its
runner SHA-256 was
`8e2883213d3f723d5bd643a56aeaada904aa9e8d7303010b8f8e986637b06267`.
Both diagnostics cleaned their isolated databases.

## SHA-256 manifest reviewed in rereview v7

```text
2951eff3c1f1c743d0fb5ad797ce2f678568e919e2f8375941dd70c23bc63f23  specs/CHECKLIST-TEMPLATE-SCHEMA-001.md
9094170947b65fdae21b9136a5438ad26ced37d82fef3cd48e6c959b106063e8  tests/InstallationProcess/checklist_template_schema_001_test.php
fc80f4f89d61c94f8a60fef5a96246bfae9bb5d3cf6d9c47d71d590f4855f01b  tests/InstallationProcess/production_migration_runner_001_test.php
cdcada85ce500439032650728e29eb42ee63bb8146b0bbf4381a538a9d36984b  tests/Support/ProductionMigrationRunnerCatalogContract.php
31efda3256b9a0e01538da431ba2082e55f11133d037938ee474b18e221d1963  docs/operations/checklist-template-schema-red-evidence.md
b029fe7ac990e62fe532360396786c4fb4e09e078b1c0adc9765931f61282bad  docs/operations/checklist-template-schema-green-verification.md
24e465f285d72b66bec8b7c0bc17b51f48cbab587e96a35cb2fdd78818a0b728  reviews/tests/CHECKLIST-TEMPLATE-SCHEMA-001.md (prior record before this append)
9338de86cd797521f8ccab26174b8ce91edbfc2825b8ff7636ca65c6de8ccaf1  bin/fmonitor2-migrate.php (current diagnostic production)
8e2883213d3f723d5bd643a56aeaada904aa9e8d7303010b8f8e986637b06267  bin/fmonitor2-migrate.php (reviewed predecessor c57663d)
5d94f9a6bb3f1d8654aeb1ba3604f6415a779032e6aaaa55b26624514da460e3  openspec/changes/canonicalize-checklist-template-schema/proposal.md
baf750cb17c8dc35ad1edef603d6457c8fbcec7824ce85ba0e466c25ec5cc711  openspec/changes/canonicalize-checklist-template-schema/design.md
09ee91894c45be1faf40a97946c874e5b38abd9d288d3e2a8d2d17bf4637e4b6  openspec/changes/canonicalize-checklist-template-schema/tasks.md
c217f7446f9c3c231bf78ba5e61611644f14327afe481e26999c7277e0c34f5c  openspec/changes/canonicalize-checklist-template-schema/.openspec.yaml
1a998b9ead7b76d1853a693eeadb82d2a9aeb20426e236d13c1491affe659b2d  openspec/changes/canonicalize-checklist-template-schema/specs/deployment/canonical-checklist-template-schema/spec.md
```

## Prior Gate 3 history preserved below

# Test rereview v6: CHECKLIST-TEMPLATE-SCHEMA-001 v0.1

- Gate: 3 — fresh independent rereview after narrowing the runtime allocator expectation
- Reviewed at: `2026-09-01` (`Europe/Moscow`)
- Reviewer: separately tasked agent `/root/checklist_v7_fixture_rereview`
- Independence: reviewer authored none of the specification, OpenSpec artifacts,
  test, production, RED evidence, or prior reviews
- Test author: separately tasked agent `/root/checklist_v7_red`
- Reviewed commit: `c57663db0773e8dd6edf5fa109a4dbcb8977ebe8`
- Specification: `specs/CHECKLIST-TEMPLATE-SCHEMA-001.md`, version `0.1`,
  status `GATE_1_APPROVED`
- Qualifying RED: the clean predecessor runner completes exact v1-v6 and omits
  approved literal v7
- Verdict: `APPROVED`

This approval applies only to the exact SHA-256 manifest below. The reviewed
test and superseding RED evidence satisfy Gate 3; OpenSpec task `2.2` may be
checked and Gate 4 may resume without changing the approved expectations.

## Rereview v6 conclusion

No blocking or non-blocking test findings remain.

The v6 narrowing matches the normative boundary. `ctsRuntimeState()` starts
from the full family state, removes only the explicit `autoIncrement` field,
and removes only MariaDB's numeric ` AUTO_INCREMENT=<n>` table option from
`SHOW CREATE TABLE`. It continues to compare normalized complete schema and
all persisted rows, so association replay still proves identical immutable
identity/facts and `created=false` without claiming allocator preservation that
sections 5 and 7 do not require.

The narrowing does not affect migration preservation evidence:

- populated migration repeat compares complete `ctsState`, including rows and
  `AUTO_INCREMENT`;
- both compatible partial directions compare the complete existing-table
  `ctsState`, including its allocator;
- every single-dimension conflict, extra FK, ordered dual conflict, and
  conflict-plus-missing case compares complete before/after family `ctsState`,
  including allocator state.

The generated-column fixture recreates normative `created_at` in its normative
ordinal position as a valid virtual expression of `captured_at`. The CHECK
fixture adds a valid `source_label <> ''` constraint. Both execute on the test
MariaDB and are rejected as exact fingerprint conflicts without mutation. The
full passing current-production run also exercised the remaining column,
SQL/string default, index name/order/kind, engine, collation, FK, prefix,
UCA-alias, runtime privilege, replay, rejection, and architecture cases.

The same current test hash was used for both bounded runner diagnostics. With
`FMONITOR_CTS_RUNNER` selecting the clean detached predecessor runner at exact
commit `c57663d`, the test reaches the public runner and fails exclusively on
expected v1-v7 versus actual successful v1-v6. Against current production, the
identical test artifact passes the complete matrix. The override is confined to
the test helper's argv executable selection and does not alter production.

## Fresh verification evidence v6

```text
$ php -l tests/InstallationProcess/checklist_template_schema_001_test.php
No syntax errors detected in tests/InstallationProcess/checklist_template_schema_001_test.php

$ openspec validate canonicalize-checklist-template-schema --strict
Change 'canonicalize-checklist-template-schema' is valid

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_CTS_RUNNER=/home/antropophag/code/fmonitor-2-checklist-gate3-rereview/bin/fmonitor2-migrate.php \
    php tests/InstallationProcess/checklist_template_schema_001_test.php
Expected: schemaVersion 7, appliedVersions [1,2,3,4,5,6,7]
Actual:   schemaVersion 6, appliedVersions [1,2,3,4,5,6]
[exit 255]

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/checklist_template_schema_001_test.php
CHECKLIST-TEMPLATE-SCHEMA-001 migration runner test passed
[exit 0]

$ SELECT SCHEMA_NAME ... WHERE SCHEMA_NAME LIKE 't_cts_001_%'
[no rows]
```

The predecessor worktree was clean and detached at exact
`c57663db0773e8dd6edf5fa109a4dbcb8977ebe8`; its runner SHA-256 was
`8e2883213d3f723d5bd643a56aeaada904aa9e8d7303010b8f8e986637b06267`.

## SHA-256 manifest approved in rereview v6

```text
2951eff3c1f1c743d0fb5ad797ce2f678568e919e2f8375941dd70c23bc63f23  specs/CHECKLIST-TEMPLATE-SCHEMA-001.md
1f0f0d9d23c70e92ad8ae3504d46a2ef5c7ba8f59a90ae2074a561dc8c794e6e  tests/InstallationProcess/checklist_template_schema_001_test.php
cc6d1acebe3e35a3d9ac8dc192eabf97b74984995a56107cae7809a36a580a26  docs/operations/checklist-template-schema-red-evidence.md
18454dc7ef9899c0a7362fe0d09bd6efefb8563b9ef4ae64551d4c9e2d8885a6  reviews/tests/CHECKLIST-TEMPLATE-SCHEMA-001.md (prior record before this append)
9338de86cd797521f8ccab26174b8ce91edbfc2825b8ff7636ca65c6de8ccaf1  bin/fmonitor2-migrate.php (current diagnostic production)
8e2883213d3f723d5bd643a56aeaada904aa9e8d7303010b8f8e986637b06267  bin/fmonitor2-migrate.php (reviewed predecessor c57663d)
5d94f9a6bb3f1d8654aeb1ba3604f6415a779032e6aaaa55b26624514da460e3  openspec/changes/canonicalize-checklist-template-schema/proposal.md
baf750cb17c8dc35ad1edef603d6457c8fbcec7824ce85ba0e466c25ec5cc711  openspec/changes/canonicalize-checklist-template-schema/design.md
be665be4ff7124e2038cfe04b094999888619c42ed1863463af39ba9dfb3a927  openspec/changes/canonicalize-checklist-template-schema/tasks.md
c217f7446f9c3c231bf78ba5e61611644f14327afe481e26999c7277e0c34f5c  openspec/changes/canonicalize-checklist-template-schema/.openspec.yaml
1a998b9ead7b76d1853a693eeadb82d2a9aeb20426e236d13c1491affe659b2d  openspec/changes/canonicalize-checklist-template-schema/specs/deployment/canonical-checklist-template-schema/spec.md
```

## Prior Gate 3 history preserved below

# Test rereview v5: CHECKLIST-TEMPLATE-SCHEMA-001 v0.1

- Gate: 3 — fresh independent test rereview after fixture correction
- Reviewed at: `2026-09-01` (`Europe/Moscow`)
- Reviewer: separately tasked agent `/root/checklist_v7_fixture_review`
- Independence: reviewer authored none of the specification, OpenSpec artifacts,
  test, production, RED evidence, or prior reviews
- Test author: separately tasked agent `/root/checklist_v7_red`
- Reviewed commit: `c57663db0773e8dd6edf5fa109a4dbcb8977ebe8`
- Specification: `specs/CHECKLIST-TEMPLATE-SCHEMA-001.md`, version `0.1`,
  status `GATE_1_APPROVED`
- Qualifying RED: clean predecessor runner completes exact v1-v6 and omits
  approved literal v7
- Verdict: `CHANGES_REQUESTED`

This verdict applies only to the exact SHA-256 manifest below. Gate 4 remains
paused and OpenSpec task `2.2` must remain unchecked.

## Rereview v5 finding

### Blocking: runtime association replay asserts an unapproved counter invariant

The corrected generated-column fixture is valid on the repository MariaDB: it
recreates the normative `created_at` name in its normative ordinal position as
a virtual expression of `captured_at`. The migration detects the resulting
`IS_GENERATED` / `GENERATION_EXPRESSION` mismatch and returns the exact conflict
without mutation. The corrected extra `CHECK (source_label <> '')` fixture is
also valid and independently detected. All previously accepted migration,
prefix, collation, runtime, rejection, and architecture cases remain present.

The current-production diagnostic was reproduced exactly: association replay
returns the original row/identity with `created=false`, leaves both table rows
and schema unchanged, but MariaDB advances the association table's next
`AUTO_INCREMENT` value from `2` to `3`. This is an observed production behavior,
but the approved contract does not make it a runtime replay failure:

- section 4.2 explicitly requires counter preservation for an **exact completed
  migration repeat**;
- section 5 outcome 3 requires runtime exact replay to return the same
  association identity with `created=false`;
- section 5 outcome 5 prohibits changing the previously persisted immutable
  snapshot or association; the persisted row is unchanged;
- the runtime no-DDL paragraph requires a before/after **schema fingerprint**,
  not preservation of table data-state counters.

The assertion at
`tests/InstallationProcess/checklist_template_schema_001_test.php:418` compares
the complete `ctsState`, including `AUTO_INCREMENT`, and therefore adds a new
runtime invariant not independently authorized by sections 4.2, 5.5, or matrix
item 10. The exact replay identity/facts assertion immediately above it already
covers the approved runtime outcome. Gate 3 cannot approve this expectation.

Narrow correction: retain schema and row preservation for association replay
but exclude `AUTO_INCREMENT` from that runtime replay comparison, and correct
the v5 evidence's characterization of the `2` to `3` advance. Do not weaken any
migration repeat, partial, or conflict counter-preservation assertion, where
the specification explicitly requires it. Then request a fresh independent
Gate 3 rereview.

## Verified evidence v5

```text
$ php -l tests/InstallationProcess/checklist_template_schema_001_test.php
No syntax errors detected in tests/InstallationProcess/checklist_template_schema_001_test.php

$ openspec validate canonicalize-checklist-template-schema --strict
Change 'canonicalize-checklist-template-schema' is valid

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_CTS_RUNNER=/home/antropophag/code/fmonitor-2-checklist-gate3-predecessor/bin/fmonitor2-migrate.php \
    php tests/InstallationProcess/checklist_template_schema_001_test.php
Expected: schemaVersion 7, appliedVersions [1,2,3,4,5,6,7]
Actual:   schemaVersion 6, appliedVersions [1,2,3,4,5,6]
[exit 255]

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/checklist_template_schema_001_test.php
TestFailure: Association replay performs zero schema/DML/counter mutation.
Expected association AUTO_INCREMENT: 2
Actual association AUTO_INCREMENT:   3
[exit 255]

$ git diff --check -- tests/InstallationProcess/checklist_template_schema_001_test.php docs/operations/checklist-template-schema-red-evidence.md reviews/tests/CHECKLIST-TEMPLATE-SCHEMA-001.md
[no output; exit 0 before this append]
```

The predecessor worktree was created detached at exact clean commit
`c57663db0773e8dd6edf5fa109a4dbcb8977ebe8`; its runner SHA-256 was independently
recomputed as `8e2883213d3f1d8654aeb1ba3604f6415a779032e6aaaa55b26624514da460e3`.
`FMONITOR_CTS_RUNNER` occurs only in the dedicated test helper, selects one argv
executable without shell interpolation, and is not a production configuration
surface.

## SHA-256 manifest reviewed in rereview v5

```text
2951eff3c1f1c743d0fb5ad797ce2f678568e919e2f8375941dd70c23bc63f23  specs/CHECKLIST-TEMPLATE-SCHEMA-001.md
ff47b912924e7015028cef85c6ecf618d26dbeedfc8f3e66990387316cf74a5d  tests/InstallationProcess/checklist_template_schema_001_test.php
dbb2ef1a6ffb7e922418488778a09c52d7c2f04e4f8a8875831c051e9369bbff  docs/operations/checklist-template-schema-red-evidence.md
16eb2a6a592c73d18454079d1b3097d73be570f4527acb2c01e65b437dd33c95  reviews/tests/CHECKLIST-TEMPLATE-SCHEMA-001.md (prior record before this append)
9338de86cd797521f8ccab26174b8ce91edbfc2825b8ff7636ca65c6de8ccaf1  bin/fmonitor2-migrate.php (current diagnostic production)
8e2883213d3f1d8654aeb1ba3604f6415a779032e6aaaa55b26624514da460e3  bin/fmonitor2-migrate.php (reviewed predecessor c57663d)
5d94f9a6bb3f1d8654aeb1ba3604f6415a779032e6aaaa55b26624514da460e3  openspec/changes/canonicalize-checklist-template-schema/proposal.md
baf750cb17c8dc35ad1edef603d6457c8fbcec7824ce85ba0e466c25ec5cc711  openspec/changes/canonicalize-checklist-template-schema/design.md
be665be4ff7124e2038cfe04b094999888619c42ed1863463af39ba9dfb3a927  openspec/changes/canonicalize-checklist-template-schema/tasks.md
c217f7446f9c3c231bf78ba5e61611644f14327afe481e26999c7277e0c34f5c  openspec/changes/canonicalize-checklist-template-schema/.openspec.yaml
1a998b9ead7b76d1853a693eeadb82d2a9aeb20426e236d13c1491affe659b2d  openspec/changes/canonicalize-checklist-template-schema/specs/deployment/canonical-checklist-template-schema/spec.md
```

## Prior Gate 3 history preserved below

# Test rereview v4: CHECKLIST-TEMPLATE-SCHEMA-001 v0.1

- Gate: 3 — fresh independent test rereview v4
- Reviewed at: `2026-09-01` (`Europe/Moscow`)
- Reviewer: separately tasked agent `/root/checklist_v7_test_rereview_v4`
- Independence: reviewer authored none of the specification, OpenSpec artifacts,
  test, production, RED evidence, or prior reviews
- Test author: separately tasked agent `/root/checklist_v7_red`
- Reviewed commit: `c57663db0773e8dd6edf5fa109a4dbcb8977ebe8`
- Specification: `specs/CHECKLIST-TEMPLATE-SCHEMA-001.md`, version `0.1`,
  status `GATE_1_APPROVED`
- RED command: `FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/checklist_template_schema_001_test.php`
- Qualifying failure: the clean canonical runner completes exact v1-v6 and omits
  the approved literal v7
- Verdict: `APPROVED`

This approval applies only to the exact SHA-256 manifest below. The reviewed
test and evidence are qualifying and sensitive to the approved contract;
OpenSpec task `2.2` may be checked and minimal Gate 4 implementation may begin
without changing the reviewed expectations.

## Rereview v4 conclusion

No blocking or non-blocking test findings remain. The v4 artifact closes both
v3 blockers without weakening the previously accepted matrix:

- The successful snapshot import is followed immediately by an exact persisted
  row assertion covering identity, version, both capture/validity timestamps,
  scope, source, content hash, exact payload JSON, and creation timestamp.
- The successful association is followed immediately by an exact persisted row
  assertion covering identity, association version, subject, effective time,
  snapshot identity/version/hash, and creation timestamp.
- Snapshot replay returns the original identity and `created=false`; association
  replay returns the same original identity and immutable returned facts with
  `created=false`. Complete schema/rows/`AUTO_INCREMENT` snapshots prove that
  neither replay mutates persisted state.
- A caller-provided wrong `template_snapshot_version` independently produces
  exact `CHECKLIST_TEMPLATE_SNAPSHOT_MISMATCH`; complete before/after family
  state proves zero schema, row, timestamp, or counter mutation.

The remaining approved matrix stays intact: exact clean fingerprints and
literal runner v7; populated no-op; both partial directions; independent
column/default/generated/index/engine/collation/FK/CHECK conflicts; ordered
dual conflicts; conflict-plus-missing preflight; complete preservation checks;
prefix isolation and 25/26 boundaries; accepted UCA alias plus rejected
non-UTF8/invalid/unknown defaults; DDL-denied runtime success/replay/conflict;
capture, rebind, missing/hash/version snapshot, and policy rejection outcomes;
absent/incompatible fail-closed behavior for both consumers; and the
family-targeted runtime-DDL architecture ratchet.

The test owns the schema/version/result literals and does not use planned v7
production DDL or constants as its oracle. Its use of the built snapshot hash
as both the caller input and expected persisted hash checks faithful persistence
of that independently exercised input; the surrounding expected row and exact
payload remain test literals. Plausible wrong persistence, replay mutation,
ignored expected-version validation, schema repair, or relaxed fingerprinting
would fail an assertion after v7 is implemented.

## Fresh verification evidence v4

```text
$ php -l tests/InstallationProcess/checklist_template_schema_001_test.php
No syntax errors detected in tests/InstallationProcess/checklist_template_schema_001_test.php

$ openspec validate canonicalize-checklist-template-schema --strict
Change 'canonicalize-checklist-template-schema' is valid

$ git diff --check -- tests/InstallationProcess/checklist_template_schema_001_test.php docs/operations/checklist-template-schema-red-evidence.md reviews/tests/CHECKLIST-TEMPLATE-SCHEMA-001.md
[no output; exit 0]

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/checklist_template_schema_001_test.php
PHP Fatal error: Uncaught TestFailure: Clean canonical runner must apply literal checklist-template migration v7.
Expected: schemaVersion 7, appliedVersions [1,2,3,4,5,6,7]
Actual:   schemaVersion 6, appliedVersions [1,2,3,4,5,6]
[exit 255]

$ SELECT SCHEMA_NAME ... WHERE SCHEMA_NAME LIKE 't_cts_%'
[no rows]
```

## SHA-256 manifest approved in rereview v4

```text
2951eff3c1f1c743d0fb5ad797ce2f678568e919e2f8375941dd70c23bc63f23  specs/CHECKLIST-TEMPLATE-SCHEMA-001.md
76a60812cf9a02ffd903f597227a04d2e2505c89d55c0288562602a382c94a21  tests/InstallationProcess/checklist_template_schema_001_test.php
651f089bfc32f40c16e6589fc1fe3fd3ee084c157f6c48ef5dafcbd7f3d0a60a  docs/operations/checklist-template-schema-red-evidence.md
8acada01e8ba85e4bc72a43405fc11dfd745b82e2559a232463e2822603f027b  reviews/tests/CHECKLIST-TEMPLATE-SCHEMA-001.md (prior record before this append)
8e2883213d3f1d8654aeb1ba3604f6415a779032e6aaaa55b26624514da460e3  bin/fmonitor2-migrate.php
5d94f9a6bb3f1d8654aeb1ba3604f6415a779032e6aaaa55b26624514da460e3  openspec/changes/canonicalize-checklist-template-schema/proposal.md
baf750cb17c8dc35ad1edef603d6457c8fbcec7824ce85ba0e466c25ec5cc711  openspec/changes/canonicalize-checklist-template-schema/design.md
be665be4ff7124e2038cfe04b094999888619c42ed1863463af39ba9dfb3a927  openspec/changes/canonicalize-checklist-template-schema/tasks.md
c217f7446f9c3c231bf78ba5e61611644f14327afe481e26999c7277e0c34f5c  openspec/changes/canonicalize-checklist-template-schema/.openspec.yaml
1a998b9ead7b76d1853a693eeadb82d2a9aeb20426e236d13c1491affe659b2d  openspec/changes/canonicalize-checklist-template-schema/specs/deployment/canonical-checklist-template-schema/spec.md
```

## Prior Gate 3 history preserved below

# Test rereview v3: CHECKLIST-TEMPLATE-SCHEMA-001 v0.1

- Gate: 3 — fresh independent test rereview v3
- Reviewed at: `2026-09-01` (`Europe/Moscow`)
- Reviewer: separately tasked agent `/root/checklist_v7_test_rereview_v3`
- Independence: reviewer authored none of the specification, test, production,
  RED evidence, or prior reviews
- Test author: separately tasked agent `/root/checklist_v7_red`
- Reviewed commit: `c57663db0773e8dd6edf5fa109a4dbcb8977ebe8`
- Specification: `specs/CHECKLIST-TEMPLATE-SCHEMA-001.md`, version `0.1`,
  status `GATE_1_APPROVED`
- RED command: `FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/checklist_template_schema_001_test.php`
- Intended failure: clean canonical runner completes exact v1-v6 but omits approved
  literal v7
- Verdict: `CHANGES_REQUESTED`

This verdict applies only to the SHA-256 manifest below. Gate 4 remains
prohibited and OpenSpec task `2.2` must remain unchecked.

## Rereview v3 findings

### Blocking: successful runtime writes are not asserted as exact persisted facts

The v3 test closes the previous preservation gaps: every migration conflict
now compares before/after family state; both partial-created siblings and the
UCA family receive exact fingerprints; runtime grants prove `SELECT`/`INSERT`
without DDL; successful and rejected runtime calls compare schema and/or full
row/counter state; replay identity and the previously absent capture,
association, missing/hash-mismatched snapshot, and policy conflicts are
covered.

However, the first successful snapshot and association writes only assert the
returned result and unchanged schema. They do not assert the exact rows written
to MariaDB. A plausible implementation can persist an incorrect
`captured_at`, `validity_scope`, `source_label`, `payload_json`, `created_at`,
`association_version`, or association `created_at`, return the expected result,
and still satisfy every later replay/conflict assertion because those paths
read only the identity/hash/effective/template subset. This violates approved
section 5 outcomes 2–3 and matrix item 10 while passing the test.

The snapshot-mismatch matrix also covers a missing ID and wrong hash but not a
wrong `template_snapshot_version`. An implementation that checks hash but
ignores the caller's expected version can pass, despite section 5 outcome 4
requiring version/hash match. Add a wrong-version rejection with full
before/after state equality.

Gate 3 therefore cannot approve this hash. Assert the complete persisted row
for the first snapshot import and association creation from independent test
literals, including all timestamps and payload JSON, and add the mismatched
snapshot-version rejection. Retain the qualifying missing-v7 RED and request a
fresh independent rereview.

### Verified closures and passing checks

- FK, every single-dimension conflict, simultaneous two-table conflicts, and
  conflict-plus-missing compare complete family schema/rows/`AUTO_INCREMENT`
  before and after.
- Snapshot replay, capture conflict, association replay/rebind conflict,
  missing/hash-mismatched snapshot, policy rejection, and absent/incompatible
  schema compare the relevant before/after state; association replay compares
  original identity and immutable returned facts.
- Both partial directions and accepted UCA alias creation use the independent
  exact column/index/engine/charset/collation fingerprint; UCA repeat is a full
  no-op.
- SQL `NULL`, string `'NULL'`, and a generated expression on the normative
  `payload_json` column are independent conflict fixtures. Non-UTF8 default is
  rejected before target DDL. Invalid and unknown collation defaults are
  demonstrated as impossible selected-database states because MariaDB rejects
  database creation itself, with no schema left to mutate.
- The architecture ratchet scans runtime PHP owners and is sensitive to the two
  currently present family-targeted lazy DDL statements; only the planned
  canonical owner and verifier fixtures are excluded.
- The fresh RED reached the public runner, successfully applied exact v1-v6,
  and failed solely on missing v7. Its `finally` cleanup removed the randomized
  database; a post-run query returned no `t_cts_*` residue, and Compose was
  stopped and removed.
- Strict OpenSpec validation, PHP syntax, and diff whitespace checks pass.

## Fresh verification evidence v3

```text
$ php -l tests/InstallationProcess/checklist_template_schema_001_test.php
No syntax errors detected in tests/InstallationProcess/checklist_template_schema_001_test.php

$ openspec validate canonicalize-checklist-template-schema --strict
Change 'canonicalize-checklist-template-schema' is valid

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/checklist_template_schema_001_test.php
PHP Fatal error: Uncaught TestFailure: Clean canonical runner must apply literal checklist-template migration v7.
Expected: schemaVersion 7, appliedVersions [1,2,3,4,5,6,7]
Actual:   schemaVersion 6, appliedVersions [1,2,3,4,5,6]
[exit 255]

$ SELECT SCHEMA_NAME ... WHERE SCHEMA_NAME LIKE 't_cts_%'
[no rows]

$ git diff --check -- tests/InstallationProcess/checklist_template_schema_001_test.php docs/operations/checklist-template-schema-red-evidence.md
[no output; exit 0]

$ docker compose -f compose.test.yaml down -v --remove-orphans
[test database container and network removed]
```

## SHA-256 manifest reviewed in rereview v3

```text
2951eff3c1f1c743d0fb5ad797ce2f678568e919e2f8375941dd70c23bc63f23  specs/CHECKLIST-TEMPLATE-SCHEMA-001.md
47b9eb63d122924d90ce70c3133d627bc7db2865d5fb5ae2c4f4e2ebc236d762  tests/InstallationProcess/checklist_template_schema_001_test.php
89e0adc7b11f901b52453bb987a6b871963a0001968e09a4321ff8fe3d9a61c7  docs/operations/checklist-template-schema-red-evidence.md
2bda1aeccbcfa0fea75bc58efe505be1211bef7a5537bf2055f432dfbbbeb88c  reviews/tests/CHECKLIST-TEMPLATE-SCHEMA-001.md (prior record before this append)
8e2883213d3f723d5bd643a56aeaada904aa9e8d7303010b8f8e986637b06267  bin/fmonitor2-migrate.php
5d94f9a6bb3f1d8654aeb1ba3604f6415a779032e6aaaa55b26624514da460e3  openspec/changes/canonicalize-checklist-template-schema/proposal.md
baf750cb17c8dc35ad1edef603d6457c8fbcec7824ce85ba0e466c25ec5cc711  openspec/changes/canonicalize-checklist-template-schema/design.md
be665be4ff7124e2038cfe04b094999888619c42ed1863463af39ba9dfb3a927  openspec/changes/canonicalize-checklist-template-schema/tasks.md
1a998b9ead7b76d1853a693eeadb82d2a9aeb20426e236d13c1491affe659b2d  openspec/changes/canonicalize-checklist-template-schema/specs/deployment/canonical-checklist-template-schema/spec.md
```

## Prior Gate 3 history preserved below

# Test rereview: CHECKLIST-TEMPLATE-SCHEMA-001 v0.1

- Gate: 3 — fresh independent test rereview
- Reviewed at: `2026-09-01` (`Europe/Moscow`)
- Reviewer: separately tasked agent `/root/checklist_v7_test_rereview`
- Independence: reviewer authored neither the specification, revised test, RED
  evidence, nor production implementation; reviewer differs from the first Gate
  3 reviewer and the test author
- Test author: separately tasked agent `/root/checklist_v7_red`
- Reviewed commit: `c57663db0773e8dd6edf5fa109a4dbcb8977ebe8`
- Specification: `specs/CHECKLIST-TEMPLATE-SCHEMA-001.md`, version `0.1`,
  status `GATE_1_APPROVED`
- Public seams: `ChecklistTemplateSchemaMigration.apply(connection, prefix)` and
  `php bin/fmonitor2-migrate.php`
- RED command: `FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/checklist_template_schema_001_test.php`
- Intended failure: the clean canonical runner returns the successful literal
  v1-v6 catalogue instead of approved v1-v7 because canonical v7 is absent
- Verdict: `CHANGES_REQUESTED`

The verdict applies only to the SHA-256 manifest below. Gate 4 remains
prohibited. OpenSpec task `2.2` must remain unchecked.

## Rereview findings

### Blocking: several preservation and runtime outcomes are still not sensitive

The revised 357-line artifact is a substantial and relevant expansion over the
first tracer. It now executes clean fingerprint, populated repeat, both partial
directions, independent incompatibility dimensions, ordered multi-conflict,
conflict-plus-missing, prefix isolation/bounds, UCA/non-UTF8 defaults, a
DDL-denied runtime principal, absent/incompatible runtime families, and a
family-targeted architecture scan. The retained first assertion remains a
qualifying public-runner RED.

It still does not make every approved section 7 claim executable and sensitive
to plausible wrong implementations once v7 exists:

1. **Zero mutation is not asserted for all conflicts.** The extra-FK case and
   simultaneous two-table conflict call `ctsExpectConflict()` without capturing
   and comparing family state. An implementation may mutate rows, indexes or
   `AUTO_INCREMENT`, then return the expected ordered conflict, and these cases
   pass. Section 4.4 and matrix items 5-7 require zero family mutation for every
   conflict.
2. **Runtime no-DDL/zero-DML is not fingerprinted.** The DDL-denied principal is
   good privilege evidence, but the test never compares before/after schema
   fingerprints around successful import/link/replay/conflict outcomes as
   section 5 explicitly requires. For absent/incompatible families it asserts
   only exception text; it does not compare before/after schema, rows and
   `AUTO_INCREMENT`. A consumer that performs DML or a permitted mutation and
   then throws `CHECKLIST_TEMPLATE_SCHEMA_REQUIRED` can pass.
3. **The runtime immutable-conflict matrix is incomplete.** The test covers an
   association rebinding conflict, but not snapshot hash conflict
   `CHECKLIST_TEMPLATE_CAPTURE_CONFLICT`, missing/mismatched snapshot
   `CHECKLIST_TEMPLATE_SNAPSHOT_MISMATCH`, or policy rejection
   `DEFINITION_VERSION_UNPROVEN`. It also checks only `created=false` for the
   association replay rather than equality of the original association identity
   and immutable returned facts. These are approved observable outcomes in
   section 5 and matrix item 10.
4. **Partial and alternate-collation creation are under-asserted.** Each partial
   scenario proves the existing sentinel is unchanged but does not verify the
   newly created sibling's exact fingerprint. The accepted UCA-alias database
   checks only `applied=true`; it does not verify that both created tables and
   every character column use the exact reported database default, nor that an
   exact repeat is a no-op. A separate partial/UCA DDL path with a reduced or
   wrong schema can therefore pass despite sections 2, 3, 4.3 and 7.3/7.9.
5. **Database-default and default/generation variants are not fully exercised.**
   Matrix item 9 names invalid, unknown and non-UTF8 defaults, but the test has
   only accepted UCA and non-UTF8 fixtures. The `def_` mutation covers one
   non-null date default, not the independently named SQL/string-default
   variants. The generated-expression case adds an extra generated column, so
   it can pass solely through an extra-column check without proving comparison
   of generated metadata on the normative column manifest.

These are not requests for broader coverage outside the slice. They are direct
gaps in the owner-approved matrix and permit implementations that violate
preservation or immutable runtime outcomes while satisfying the current
assertions. Gate 3 therefore cannot approve this hash.

### Checks that pass

- **Traceability and public seams:** the test cites the specification and uses
  the canonical CLI plus the approved migration and runtime consumer seams.
- **Independent literals:** literal version `7`, predecessor list v1-v6, table
  names, columns, keys, engine and base collation are test-owned rather than
  imported from planned production migration constants/DDL.
- **Fixture shape:** randomized database/user names, exact table fixtures,
  populated sentinels and both partial directions are deterministic. `finally`
  removes the isolated database; a post-RED query found no `t_cts_001_%`
  database. The temporary Compose database/network were removed.
- **Runtime principal:** the new principal receives only `SELECT`/`INSERT` on
  the two exact prefixed tables and no `CREATE`/`ALTER`/`DROP`, so the intended
  successful DML path cannot rely on runtime DDL.
- **Architecture rule:** the rule catches both current family-targeted lazy
  `CREATE TABLE` statements and permits only the planned canonical migration
  owner. It is a valid zero-baseline ratchet for the named family.
- **RED qualification:** the rerun connected successfully and applied exact
  prerequisites `[1,2,3,4,5,6]`; it failed only because runner output omitted
  literal v7. This is missing behavior, not fixture/setup failure.
- **OpenSpec and static checks:** strict validation, PHP syntax and diff
  whitespace checks pass.

## Required changes

Make every conflict and runtime rejection compare relevant before/after family
state; complete the approved runtime conflict/replay outcomes; assert exact
fingerprints for newly created partial and UCA tables; and add executable
fixtures for the explicitly named remaining default/collation/generated
variants (or reconcile the Gate 1 matrix before implementation if a database
state is genuinely impossible to construct). Retain a qualifying missing-v7
RED, then request another fresh independent Gate 3 review. Do not implement
GREEN or mark task `2.2` meanwhile.

## Fresh verification evidence

```text
$ php -l tests/InstallationProcess/checklist_template_schema_001_test.php
No syntax errors detected in tests/InstallationProcess/checklist_template_schema_001_test.php

$ git diff --check -- tests/InstallationProcess/checklist_template_schema_001_test.php
[no output; exit 0]

$ openspec validate canonicalize-checklist-template-schema --strict
Change 'canonicalize-checklist-template-schema' is valid

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/checklist_template_schema_001_test.php
PHP Fatal error: Uncaught TestFailure: Clean canonical runner must apply literal checklist-template migration v7.
Expected: schemaVersion 7, appliedVersions [1,2,3,4,5,6,7]
Actual:   schemaVersion 6, appliedVersions [1,2,3,4,5,6]
[exit 255]

$ SELECT SCHEMA_NAME ... WHERE SCHEMA_NAME LIKE 't_cts_001_%'
[no rows]

$ docker compose -f compose.test.yaml down -v --remove-orphans
[test database container and network removed]
```

## SHA-256 manifest reviewed in this rereview

```text
2951eff3c1f1c743d0fb5ad797ce2f678568e919e2f8375941dd70c23bc63f23  specs/CHECKLIST-TEMPLATE-SCHEMA-001.md
d9f1c4d21b4e9f0c7da404075fbb88aed1c1981827fe1591005108d91394fcec  tests/InstallationProcess/checklist_template_schema_001_test.php
e57270f4fb836e3bddbb33983e160d46e4d15152fbe97ea0223e04b5f682a8a7  docs/operations/checklist-template-schema-red-evidence.md
8e2883213d3f723d5bd643a56aeaada904aa9e8d7303010b8f8e986637b06267  bin/fmonitor2-migrate.php
5d94f9a6bb3f1d8654aeb1ba3604f6415a779032e6aaaa55b26624514da460e3  openspec/changes/canonicalize-checklist-template-schema/proposal.md
baf750cb17c8dc35ad1edef603d6457c8fbcec7824ce85ba0e466c25ec5cc711  openspec/changes/canonicalize-checklist-template-schema/design.md
be665be4ff7124e2038cfe04b094999888619c42ed1863463af39ba9dfb3a927  openspec/changes/canonicalize-checklist-template-schema/tasks.md
1a998b9ead7b76d1853a693eeadb82d2a9aeb20426e236d13c1491affe659b2d  openspec/changes/canonicalize-checklist-template-schema/specs/deployment/canonical-checklist-template-schema/spec.md
```

## Prior Gate 3 history preserved

The first independent review by `/root/checklist_v7_test_review` at the same
base commit returned `CHANGES_REQUESTED` against test hash
`d6b8d3b09af4b0ec77dd20a6bf4b1d1135d6741df273811e478d9d2b4c0e5ef2`.
It found that the original 92-line tracer covered only clean runner
registration/table presence and omitted almost all of section 7. The revised
hash reviewed above supersedes that narrow artifact and closes most of that
finding, but the preservation/sensitivity gaps listed in this rereview remain.

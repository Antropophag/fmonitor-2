# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 5 setup rereview v3

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_dbsetup_gate5_v3`
- Reviewed implementation commit: `6e19bd4191b659f8485d6543df303db60af98575`
- Included implementation commits: `32dd3151941f1198e0fdd6ce5ee8ee9e1b851abd`, `edbae87a46ff9d9abf0bda98dd411a6f4ba28aa5`
- Prior Gate 5: `c40c0101f46cbbee0187ea48f569999c3a0c49f1`, `f4bdb56aa446148a69b3044bf565228bbdef0f3d`
- Replacement RED: `5c97abcd270b34aa4ab9d08f0583264ece653b71`
- Fresh Gate 3: `a3df95a71d509fa8dc15e002d1d8ef54504eb8ea`
- Approved executable specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v14
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the specification, OpenSpec artifacts, tests,
support oracle, RED evidence or production implementation. This append-only
review record is the only authored artifact.

## Blocking finding

### G5-SETUP-V3-1 — fixture validation is collation equality, not byte equality

The v14 contract and prior Gate 5 correction require every occupied fixture row
to compare byte-for-byte before seed or cleanup DML. The implementation builds
`$good` with ordinary SQL predicates such as
`full_name='Тестовый Оператор ФКР'` on `utf8mb4_unicode_ci` columns. MariaDB
therefore applies collation semantics and ignores distinctions such as trailing
spaces (and other collation-equivalent text), rather than comparing stored
bytes. `rows === good` can consequently accept a foreign/drifted row, and
`cleanupExampleA` then deletes it.

This was reproduced on a fresh task-owned MariaDB database after canonical V4,
original migration and Example-A seed. The reviewer changed only actor 18's
stored `full_name` by appending one U+0020 byte. Its exact hex value ended in
`...D09AD0A020`, proving the extra byte was stored. Both public fixture calls
still accepted the row, and cleanup deleted it:

```text
SEED_ACCEPTED_TRAILING_SPACE D0A2D0B5D181D182D0BED0B2D18BD0B920D09ED0BFD0B5D180D0B0D182D0BED18020D0A4D09AD0A020
CLEANUP_ACCEPTED count=0
```

The task-owned database was dropped in `finally`. This violates the exact
fixture identity and bounded-cleanup security contract: a byte-different row
must produce fixed `AssignmentOrderOriginalVerificationFixtureConflict` before
any DML and must never be deleted.

The approved test's generated field matrix does not catch this. Every string
mutation uses `value . '-drift'`, which is unequal even under the configured
collation. Add independently derived collation-equivalent but byte-distinct
seed and cleanup cases (at minimum a persisted trailing-space case), then make
all textual fixture comparisons binary/octet-exact, including nullable fields
and complete rows. Since test sensitivity must change, this returns to Gate 2
and requires a fresh independent Gate 3 before another Gate 5.

## Prior findings and remaining review surface

- **G5-SETUP-2 remains closed.** The current classifier selects one safe-named
  top-level capability candidate, excludes the engineer-position constraint,
  accepts only the exact sorted V4 or V5 member set, rejects missing,
  upload-only, correct-only, subset, superset and multiple candidates, and
  returns the exact binary-sorted conflict union without DDL.
- **G5-SETUP-3 remains closed.** Every durable table creation is observable;
  the complete seven-table family is revalidated before publication; the V5
  ALTER is last; post-ALTER fresh classification resolves durable V5 and makes
  V4/conflict/unavailable recovery fail closed. Affected lists retain manifest
  order with capability last, while exact V5 repeat is empty `UNCHANGED`.
- **The Gate 5 v2 missing-prerequisite finding is closed.** An absent
  `fm2_process_user_capabilities` now returns `CONFLICT` with only that logical
  name and a byte-identical zero-DDL schema snapshot in both real-MariaDB
  verifiers.
- Complete-row fixture coverage, partial-family seed refusal, partially absent
  cleanup, contention/recovery and preservation of unrelated identities all
  pass for values that are SQL-distinct. They do not compensate for the binary
  identity defect above.
- Searches found no application, HTTP, cron, worker or `rapid-pilot/` runtime
  invocation of the migration or fixture. DDL remains in the explicit schema
  setup owner; verification-only fixture DML creates no original facts. Prefix
  validation, fixed exception messages, fictional identities without
  credentials, authorization regressions and production migration runner all
  remain satisfactory.
- The densely compressed implementation remains harder to audit and maintain
  than necessary, but that is not recorded as a separate blocker for this
  minimal setup slice.

## Reproduced verification

```text
$ php tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_001_OK

$ php tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_CAPABILITY_MIGRATION_001_OK

$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/process_command_authorization_001_test.php
PASS: PROCESS-COMMAND-AUTHORIZATION-001

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/production_migration_runner_001_test.php
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check
PASS (no output before this review record)
```

The focused suites are genuinely green, but the independent real-MariaDB probe
demonstrates that they are not sensitive to the required byte-exact fixture
boundary.

## Exact reviewed hashes

```text
f19bca46b2334e482e079c95fd856754b45f4151fba1636ee82041c0356b9d26  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
20307b87e81cf11c29d42b3ca69f0a6bed28828a9724adda667ecf74bc603f58  openspec/changes/replace-pilot-registration-with-original-upload/design.md
db0956e61f3fc67320dae2c7b993b98ae3d8531252a486de69dbd922c0749f46  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
97bba61e2ba7fdbbf53abb160f802d491cc284c9fce06b537e85880d40838864  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
8557ddec86169836d30b0d43236b9f9cbd8e0214d712147f54d11ac150ba9f01  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
6e8d35d624018a344ef923eba64a7cd0f1927e2205d924a5cac301dd7e3ff4ec  tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
2d03ad4d6d5d950e4a92592922193e683ee2b7f96451e3634c50efccd0a65a0d  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
78b911c27665a6d01ebf2498c48a1d3b0d0952967557b4190a7877e093aaaa4b  tests/Support/AssignmentOrderOriginalSchemaMigrationObserverSpy.php
8b34d11c5d204d68cafa3bf74322c046fa393f89d701863808d8a0d9ad60f72c  app/InstallationProcess/AssignmentOrderOriginalSchemaMigration.php
8f7364134463dffae94b481a3d84ff0b0515e0c1ef6d178d3d8101b1eb97a581  app/InstallationProcess/AssignmentOrderOriginalSchemaMigrationEngineSchemaMigration.php
d729e13a1fff56ba92acaedfc31b668c1c0f23db598584fd838da18748f73931  app/InstallationProcess/MariaDbAssignmentOrderOriginalVerificationFixture.php
625beca62d5218585f7a488a3c2e3f0aea7dcaf841ec458599c52b43392bd088  app/AssignmentOrderOriginal/AssignmentOrderOriginalSchemaMigrationVerificationFactory.php
2e6f2e7d4cbe955acfe278cf54efd3b3eb293bc26a63052555e14f1e953f5107  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v1.md
d364f820d32b21c57b832557f53b7d0334e77f641556060b4b7434e8a5669f50  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v2.md
a9bd02ec34701c776d9351bfbe458e5c5b750bb93de6a877ed2c08a1dcd7de30  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v22.md
e0edba0f1faf9e74219000fd946b3d63c9e6d0e493d75a57dfe73801f9b07600  docs/operations/assignment-order-original-database-setup-green-v3-2026-09-05.md
```

The review omits its own circular hash. Gate 5 remains
**CHANGES_REQUESTED**; OpenSpec task 3.2 must remain unchecked.

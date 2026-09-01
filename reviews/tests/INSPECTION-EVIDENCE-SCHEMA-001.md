# Test review: INSPECTION-EVIDENCE-SCHEMA-001

- Gate: 3 — fresh independent test review
- Reviewer: separately tasked agent `/root/inspection_v8_test_review`
- Independence: reviewer did not author the specification, test, RED evidence, or production code
- Reviewed commit: `ceb051e5b192ccee323fca26e4c773715a2d0b43`
- Date: `2026-09-01`
- Verdict: `CHANGES_REQUESTED`

## Verdict

The captured failure is a qualified RED for missing literal migration v8, but
the test is not sufficient to approve Gate 3. The approved specification makes
G2-01 through G2-16 the **minimum** Gate 2 matrix. The reviewed test explicitly
implements only G2-01, G2-02, G2-04, G2-10, and G2-16, and several of those
cases do not assert the exact normative fingerprints/results. Gate 4 must not
begin and OpenSpec task 2.2 remains unchecked.

## Reproduced RED

Command:

```text
make test-env-up && php tests/InstallationProcess/inspection_evidence_schema_001_test.php
```

The disposable MariaDB container became healthy. The real runner exited
successfully through exact prerequisites `[1,2,3,4,5,6,7]`, stdout decoded as
JSON, and the test then failed on the intended absent behavior:

```text
PHP Fatal error: Uncaught TestFailure: G2-01 canonical runner must own literal inspection-evidence v8 after proven v1-v7.
Expected: 8
Actual: 7
```

Test process exit: `255`. This is an assertion RED, not an environment, DB,
runner-start, predecessor, or parsing failure.

## Blocking findings

1. **G2-01 does not assert exact final fingerprints.** `iesState()` collects
   column metadata and index metadata, but `iesAssertFinal()` checks only
   ordered column names, engine, table collation, character charset/collation,
   and absence of FK/CHECK rows. It never compares exact `COLUMN_TYPE`,
   `IS_NULLABLE`, SQL-NULL-versus-string `COLUMN_DEFAULT`, `EXTRA`,
   `IS_GENERATED`, `GENERATION_EXPRESSION`, or the collected index names,
   uniqueness, order, `SUB_PART`, direction, type, and visibility. Plausible
   implementations with wrong numeric widths/defaults/generated columns or
   entirely wrong/missing indexes pass. The test also does not assert the
   exact ordered `tablesCreated` result required by Scenario A.

2. **The required sensitivity matrix G2-03 and G2-05 through G2-09 is absent.**
   There is no exhaustive 36-state compatible product; no independent/empty
   predecessor variants; no partial template-column subset rejection; and no
   one-at-a-time mutation coverage for columns/defaults/generated metadata,
   engine/charset/collation, indexes, FK, CHECK, or extra constraints. A
   permissive implementation that repairs arbitrary drift can pass the
   reviewed test.

3. **The predecessor assertions are incomplete.** The single populated
   simultaneous-upgrade case checks three new NULLs, one installer backfill,
   and the operations allocator, but does not compare all pre-existing row
   bytes, keys, photos allocator, or exact `tablesCreated`/`tablesUpgraded`
   lists. It does not prove the operations and installer predecessors work
   independently, empty and populated, as required by G2-04.

4. **G2-10's intended fixture is not fully proved.** The case does exercise two
   conflict classes and snapshots the family, but revisions is implicitly
   absent and operations has no sentinel row/allocator. Consequently it cannot
   prove the predecessor was not backfilled or that its rows and next value
   survived the family preflight. Exact final-fingerprint validation is also
   missing, so the fixture helpers do not self-prove all required predecessor
   metadata before invoking production.

5. **Prefix and database preflight cases G2-11 through G2-13 are absent.** The
   test does not cover valid prefix length 25, rejection of length 26 before DB
   access, alternate valid database-default collations/UCA aliases, missing or
   incompatible SCHEMATA/collation metadata with zero mutation, or isolated
   missing/out-of-order/renumbered runner catalogues. The clean assertion only
   fixes `utf8mb4_unicode_ci` and the empty prefix.

6. **Runtime no-DDL is only a weak source regex, not the required public-seam
   behavior.** The regex can miss dynamically assembled/query-indirect DDL and
   does not run checklist sync/item/photo paths under a DML-only DB principal
   as required by G2-14. More importantly, G2-15 is wholly absent: each missing
   and each incompatible owned table must be exercised through checklist JSON
   and file-producing photo public paths, with exact infrastructure/HTTP 503
   outcomes, zero evidence DML, zero schema repair, and filesystem unchanged.
   The reviewed test never constructs a storage root or checks file state.

7. **Repeat is tested through the migration class rather than the specified
   repeat runner observation.** It therefore does not prove that a repeat
   runner omits `8` from `appliedVersions`. The state comparison is useful, but
   the initial family is empty: it does not contain the required sentinel rows
   and non-default operations/photos next values, weakening its DDL/DML and
   allocator sensitivity.

8. **Prefix isolation is partial.** It snapshots one incompatible `decoy_`
   table only. G2-16 requires compatible target family plus incompatible
   unprefixed and `decoy_` families and byte-identical definitions, rows, and
   next values for both decoys. Those inputs/observations are not present.

## Required changes

1. Add literal, test-owned exact column/table/index/constraint fingerprints and
   exact migration result assertions; do not derive expectations from the
   implementation.
2. Implement the complete minimum G2-01–G2-16 matrix from approved section 8,
   including the exhaustive 36 compatible states and systematic malformed
   fingerprints.
3. Exercise repeat and ordering through the canonical runner where specified,
   with sentinel rows and both non-default allocators captured before/after.
4. Add DML-only runtime success coverage and per-table absent/incompatible
   fail-closed coverage through the approved checklist JSON/photo entry points;
   fingerprint DB and exact storage roots before/after to prove zero DML,
   repair, and file persistence.
5. Re-run the focused test and retain a qualified RED caused by missing v8
   behavior, then request a fresh independent Gate 3 review.

## SHA-256 reviewed-input manifest

```text
82b82114ab7db34c63a06ec34dd287d38a0f25e52e71b4dd314545f97f0f58d7  specs/INSPECTION-EVIDENCE-SCHEMA-001.md
9541efbc1c702d5040a5f16450cabdad9161956ecd0615ce05a508cd5c18de34  tests/InstallationProcess/inspection_evidence_schema_001_test.php
8f4fd193fdea732712857f0b60acc1b67e93bffbeb365690f64483c1ccfa1490  docs/operations/inspection-evidence-schema-red-evidence.md
6ad3cad4d132e08ad79422918a5c2f9c82ff7811b1f689058ad5fa74f97021c9  openspec/changes/canonicalize-inspection-evidence-schema/proposal.md
57336dc20461afca35cdf90514a419885efd28dd6ede5dd1cb6f528955d3345f  openspec/changes/canonicalize-inspection-evidence-schema/design.md
678bf8c4f1d7f8db12d37bb6024fb4c7bcef34c17aa7bda6c682322323781d87  openspec/changes/canonicalize-inspection-evidence-schema/tasks.md
123704eda50e1fc552335a14db07f418115ade304ccf6206583dc9d8bd0b1745  openspec/changes/canonicalize-inspection-evidence-schema/.openspec.yaml
69676a9d16dd2d380de13b76f2f50d35d4c085998b01a109a4a9a6cc49b0cdb7  openspec/changes/canonicalize-inspection-evidence-schema/specs/deployment/canonical-inspection-evidence-schema/spec.md
b92369d7a36efe107ba3c312d79c7eb71af81c16f2d594c5384cce5cc931728c  app/PilotHttp/ChecklistSync.php
9338de86cd797521f8ccab26174b8ce91edbfc2825b8ff7636ca65c6de8ccaf1  bin/fmonitor2-migrate.php
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
800d135a043633260ce59440579f35f4dcf16553c61f7e149d82825c9e6c3509  tests/bootstrap.php

METADATA  reviews/tests/INSPECTION-EVIDENCE-SCHEMA-001.md
```

Any byte change in the approved specification, reviewed test, or RED evidence
invalidates this review. OpenSpec task 2.2 intentionally remains unchecked.

---

## Fresh rereview after test expansion — 2026-09-01

- Reviewer: separately tasked agent `/root/inspection_v8_test_review`
- Independence: reviewer did not author the revised test or RED evidence
- Verdict: `CHANGES_REQUESTED`

The revised test materially improves schema sensitivity: it now owns literal
column/index fingerprints, enumerates the 36 compatible form combinations,
adds independent/combined predecessor cases, exercises multiple malformed
column/table/index/constraint forms, adds runner-repeat and catalogue probes,
tests prefix 25/26 and a real UCA alias/non-utf8 database, strengthens the
atomic conflict fixture, and fingerprints both unprefixed and alternate-prefix
decoys. The new RED remains qualified. These improvements resolve several
findings from the first review, but the minimum approved G2 matrix is still not
fully observable.

### Reproduced revised RED

```text
make test-env-up && php tests/InstallationProcess/inspection_evidence_schema_001_test.php

PHP Fatal error: Uncaught TestFailure: G2-01 canonical runner must own literal inspection-evidence v8 after proven v1-v7.
Expected: 8
Actual: 7
```

Exit `255`, after the disposable MariaDB became healthy and the real runner
successfully applied exact v1–v7. `php -l` also reports no syntax errors.

### Remaining blocking findings

1. **G2-14 is not the approved DML-only golden-runtime case.** At lines 249–256
   the DML-only principal calls `ChecklistSync::ensureSchema()` and then
   `projection(999999)`, whose expected outcome is only an `OutOfBoundsException`.
   No successful checklist sync, item completion, or photo action is executed,
   and no accepted facts/files are asserted. An implementation can remove DDL
   yet break every real state-changing runtime path and pass this probe. The
   static regex does not replace the specified public runtime actions.

2. **G2-15 does not invoke either required public behavior path.** For each
   absent/incompatible table, line 255 invokes only `ensureSchema()` directly.
   It does not invoke a checklist JSON entry point, does not observe exact HTTP
   `503` plus JSON `{"status":"retryable"}`, and does not invoke the
   file-producing photo path. Comparing `scandir($root)` around a call that
   never attempts photo persistence cannot detect a regression that writes a
   blob before discovering schema drift. The claimed authenticated-HTTP seam
   limitation is not acceptable against this explicit approved requirement:
   the repository already has real HTTP fixture/server conventions, and the
   approved spec names `PilotE2ECoordinator` and checklist HTTP/sync/photo entry
   points as public seams. At minimum, the existing callable ChecklistSync
   sync/photo public operations must actually be driven with valid fixtures;
   exact adapter mapping must also be exercised where section 8 explicitly
   requires it.

3. **G2-03 does not prove preservation across the 36-state product.** The
   enumerator creates empty existing tables and immediately applies the
   migration. It does not seed existing forms with sentinel rows or non-default
   operations/photos allocators, nor compare their pre-existing bytes/keys/next
   values before and after. Separate G2-04 cases cover some predecessor rows,
   but do not supply the section-8 observation for all compatible combinations.

4. **G2-04 still misses complete installer preservation in its variant matrix.**
   `iesAssertPredecessorVariants()` compares only the first 13 operation fields
   and the operations allocator. It never compares the seven pre-existing
   installer snapshot fields before/after or asserts the exact backfill in the
   independent populated installer case. The later single combined `up_` case
   checks the backfill literal but not all old installer bytes/keys. This leaves
   room for an implementation that rewrites a personnel snapshot while adding
   `assignment_source`.

5. **G2-06/G2-08 do not cover all explicitly named fingerprint dimensions.**
   The mutation set has no column-reordering case and no mutation of existing
   column `EXTRA` independent of adding an extra column. Its generated-column
   probe changes generated status and expression together, so it is not
   sensitive to accepting a wrong expression independently. The index set has
   no `INDEX_TYPE` mutation and no removed-index case. Section 8 explicitly
   requires one mutation at a time for these dimensions and added/removed
   indexes; representative neighboring mutations are insufficient.

6. **The 26-byte prefix probe does not prove rejection before DB access.**
   `iesRunRunner()` supplies a reachable, valid database. It proves the result
   code but would also pass if the runner connected/read metadata and only then
   rejected the prefix. Use an unreachable/invalid DB configuration with the
   otherwise-valid 26-byte prefix, following the existing preflight-test
   convention, so only pre-access validation can produce the expected
   configuration result.

### Assessment of the hostile metadata limitation

The author's narrower limitation is reasonable only for MariaDB states that
cannot exist through the approved real `mysqli` connection: a connected target
database necessarily has a SCHEMATA row, and MariaDB refuses malformed/unknown
or non-applicable database-default collations. The real non-utf8 database and
documented UCA alias probes provide useful constructible coverage. This does
not itself require introducing a test-only production metadata seam. It does
not, however, waive the independent prefix-before-access check or any runtime
public-seam scenarios above.

### Exact SHA-256 rereview manifest

```text
82b82114ab7db34c63a06ec34dd287d38a0f25e52e71b4dd314545f97f0f58d7  specs/INSPECTION-EVIDENCE-SCHEMA-001.md
8b7c28b44b66ef0304a8515ed2cf7064dcee3c06994819ce5189abf271ebfa41  tests/InstallationProcess/inspection_evidence_schema_001_test.php
807830173ecdb0870fc94a748dbae13bd273d59ed325a546d49a0301f24582ee  docs/operations/inspection-evidence-schema-red-evidence.md
678bf8c4f1d7f8db12d37bb6024fb4c7bcef34c17aa7bda6c682322323781d87  openspec/changes/canonicalize-inspection-evidence-schema/tasks.md
6ad3cad4d132e08ad79422918a5c2f9c82ff7811b1f689058ad5fa74f97021c9  openspec/changes/canonicalize-inspection-evidence-schema/proposal.md
57336dc20461afca35cdf90514a419885efd28dd6ede5dd1cb6f528955d3345f  openspec/changes/canonicalize-inspection-evidence-schema/design.md
69676a9d16dd2d380de13b76f2f50d35d4c085998b01a109a4a9a6cc49b0cdb7  openspec/changes/canonicalize-inspection-evidence-schema/specs/deployment/canonical-inspection-evidence-schema/spec.md
b92369d7a36efe107ba3c312d79c7eb71af81c16f2d594c5384cce5cc931728c  app/PilotHttp/ChecklistSync.php
9338de86cd797521f8ccab26174b8ce91edbfc2825b8ff7636ca65c6de8ccaf1  bin/fmonitor2-migrate.php
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
800d135a043633260ce59440579f35f4dcf16553c61f7e149d82825c9e6c3509  tests/bootstrap.php

METADATA  reviews/tests/INSPECTION-EVIDENCE-SCHEMA-001.md
```

OpenSpec task 2.2 remains unchecked. Gate 4 must not begin until the exact
revised test bytes receive a fresh independent `APPROVED` review.

---

## Third fresh rereview — 2026-09-01

- Reviewer: separately tasked agent `/root/inspection_v8_test_review`
- Independence: reviewer did not author the latest test or evidence
- Verdict: `APPROVED`

The exact test SHA-256
`ebca4c75101f2714e69843171a60f993f7443efee4b7a147eb4f1942d18995c0`
is approved for Gate 4. The earlier blocking findings are resolved.

### Independent assessment

- G2-01/G2-02 own literal exact column, default, generated/extra, charset,
  collation, index, engine and constraint expectations, runner v1–v8 ordering,
  repeat omission, sentinel rows and both allocators.
- G2-03 enumerates all 36 compatible combinations, seeds every existing form,
  and compares all old row fields plus operations/photos next values.
- G2-04 exercises empty/populated and independent/combined predecessors,
  verifies exact upgrade lists, operation bytes/allocator, installer snapshot
  bytes, SQL NULL additions and exact assignment-source backfill.
- G2-05 through G2-09 reject partial additions and independently observable
  mutations of order/type/nullability/default/extra/generated expression,
  engine/table and character collation, index name/order/uniqueness/direction/
  prefix/type/visibility/addition/removal, FK and CHECK with zero family repair.
- G2-10 fingerprints a populated predecessor, absent sibling, two simultaneous
  conflict classes, rows and allocators; conflict names are binary ordered.
- G2-11 proves prefix 25 succeeds and prefix 26 returns configuration failure
  even with deliberately unreachable DB credentials, establishing pre-access
  validation. G2-12 covers the constructible real-MariaDB inputs: exact UCA
  alias application and non-utf8 database rejection with zero mutation.
- G2-13 invokes the public migration application with missing, out-of-order and
  renumbered catalogues and proves the evidence callback is not reached.
- G2-14 executes accepted item and photo operations plus projection through the
  public `ChecklistSync` seam using a principal granted only DML, and observes
  both persisted revision and photo file.
- G2-15 builds a valid photo attempt for every owned table absent and
  incompatible, invokes the same production precondition-plus-operation
  composition, and compares the entire four-table metadata/rows/allocators and
  recursively hashed storage tree. Thus a repair, partial evidence write, or
  premature photo blob is observable.
- G2-16 fingerprints incompatible unprefixed and alternate-prefix families and
  proves only the configured target family changes.

The exact HTTP adapter is not safely isolated without constructing unrelated
final production dependencies (trusted identity, authorization/card reads,
CSS descriptors, session and request-body orchestration). The approved
`ChecklistSync` public seam is the strongest directly callable behavior seam
and now genuinely drives both DML and the file-producing photo path. The
production coordinator explicitly maps the typed infrastructure failure to
HTTP 503/retryable. For this ownership slice, duplicating that already-existing
adapter composition would add unrelated setup without increasing schema-failure
sensitivity, so this limitation is accepted.

Likewise, a connected real MariaDB target cannot have a missing SCHEMATA row or
an unknown/non-applicable configured database default: the server refuses such
states before the approved `mysqli` seam exists. The constructible charset and
UCA cases are sufficient; no test-only metadata seam is required.

### Reproduced qualified RED

```text
make test-env-up && php tests/InstallationProcess/inspection_evidence_schema_001_test.php

PHP Fatal error: Uncaught TestFailure: G2-01 canonical runner must own literal inspection-evidence v8 after proven v1-v7.
Expected: 8
Actual: 7
```

Exit `255`. MariaDB became healthy; the real runner successfully applied and
reported exact prerequisites `[1,2,3,4,5,6,7]` before the target assertion.
`php -l` passed. This remains the intended missing-v8 RED, not setup failure.

### Exact SHA-256 approval manifest

```text
82b82114ab7db34c63a06ec34dd287d38a0f25e52e71b4dd314545f97f0f58d7  specs/INSPECTION-EVIDENCE-SCHEMA-001.md
ebca4c75101f2714e69843171a60f993f7443efee4b7a147eb4f1942d18995c0  tests/InstallationProcess/inspection_evidence_schema_001_test.php
75c98e6aaa68ab0582bdc637497cac2ee836e32c23db8964e9cfe36b4b744721  docs/operations/inspection-evidence-schema-red-evidence.md
b0c1b178bd34b3ddfd1d11829c434002b033df8250af05ba56fe9c7d3a75a995  openspec/changes/canonicalize-inspection-evidence-schema/tasks.md (task 2.2 complete)
6ad3cad4d132e08ad79422918a5c2f9c82ff7811b1f689058ad5fa74f97021c9  openspec/changes/canonicalize-inspection-evidence-schema/proposal.md
57336dc20461afca35cdf90514a419885efd28dd6ede5dd1cb6f528955d3345f  openspec/changes/canonicalize-inspection-evidence-schema/design.md
123704eda50e1fc552335a14db07f418115ade304ccf6206583dc9d8bd0b1745  openspec/changes/canonicalize-inspection-evidence-schema/.openspec.yaml
69676a9d16dd2d380de13b76f2f50d35d4c085998b01a109a4a9a6cc49b0cdb7  openspec/changes/canonicalize-inspection-evidence-schema/specs/deployment/canonical-inspection-evidence-schema/spec.md
b92369d7a36efe107ba3c312d79c7eb71af81c16f2d594c5384cce5cc931728c  app/PilotHttp/ChecklistSync.php
9338de86cd797521f8ccab26174b8ce91edbfc2825b8ff7636ca65c6de8ccaf1  bin/fmonitor2-migrate.php
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
800d135a043633260ce59440579f35f4dcf16553c61f7e149d82825c9e6c3509  tests/bootstrap.php

METADATA  reviews/tests/INSPECTION-EVIDENCE-SCHEMA-001.md
```

Any byte change in the approved spec, exact test, or RED evidence invalidates
this approval. Gate 4 may change production only; a fresh independent Gate 5
review remains mandatory.

---

## Post-Gate-4 fixture-correction rereview — 2026-09-01

- Reviewer: separately tasked agent `/root/inspection_v8_test_review`
- Verdict: `APPROVED`
- Corrected test: `3e9ce3564f3b5645e2c703887cd7782a3d5804911437f83387a2283723e3ea38`

The correction changes exactly one test-owned value: G2-14's item-completion
fixture now pairs `itemId=1` with `sectionId=3` instead of `sectionId=1`.
Reconstructing the prior bytes by changing that value back produces the exact
previously approved SHA-256
`ebca4c75101f2714e69843171a60f993f7443efee4b7a147eb4f1942d18995c0`.
No expectation, seam, schema oracle, conflict case, or other fixture changed.

The correction matches the landed public `ChecklistSync::SECTION_ITEMS`
mapping, where section 3 owns items 1–6. It removes a test-fixture rejection;
it does not weaken G2-14. The test still requires, in sequence under the same
DML-only principal:

1. exact item result `accepted`, revision 1;
2. exact photo result `accepted`, revision 2, with base revision 1;
3. projection revision 2; and
4. the exact SHA-addressed photo blob on disk.

Thus either operation rejecting, skipping DML, using the wrong revision, losing
projection visibility, performing runtime DDL, or failing file persistence is
still observable. G2-15's per-table absent/incompatible DB and recursive file
invariance matrix is byte-identical to the prior approval.

The corrected focused test was independently run against the current Gate 4
worktree:

```text
make test-env-up
php -l tests/InstallationProcess/inspection_evidence_schema_001_test.php
php tests/InstallationProcess/inspection_evidence_schema_001_test.php

No syntax errors detected in tests/InstallationProcess/inspection_evidence_schema_001_test.php
INSPECTION-EVIDENCE-SCHEMA-001 tests passed.
```

This GREEN run is post-Gate-4 sensitivity proof only. The historical qualified
RED (real runner through exact v1–v7, expected v8/actual v7) remains preserved
in the reviewed evidence; changing a fixture after implementation correctly
required this fresh Gate 3 approval.

### Exact SHA-256 manifest

```text
82b82114ab7db34c63a06ec34dd287d38a0f25e52e71b4dd314545f97f0f58d7  specs/INSPECTION-EVIDENCE-SCHEMA-001.md
3e9ce3564f3b5645e2c703887cd7782a3d5804911437f83387a2283723e3ea38  tests/InstallationProcess/inspection_evidence_schema_001_test.php
475860373aef8f7b48e7cc334c792af9408165451fb82eddc8c2b248398bff62  docs/operations/inspection-evidence-schema-red-evidence.md
b0c1b178bd34b3ddfd1d11829c434002b033df8250af05ba56fe9c7d3a75a995  openspec/changes/canonicalize-inspection-evidence-schema/tasks.md (task 2.2 complete)
6ad3cad4d132e08ad79422918a5c2f9c82ff7811b1f689058ad5fa74f97021c9  openspec/changes/canonicalize-inspection-evidence-schema/proposal.md
57336dc20461afca35cdf90514a419885efd28dd6ede5dd1cb6f528955d3345f  openspec/changes/canonicalize-inspection-evidence-schema/design.md
123704eda50e1fc552335a14db07f418115ade304ccf6206583dc9d8bd0b1745  openspec/changes/canonicalize-inspection-evidence-schema/.openspec.yaml
69676a9d16dd2d380de13b76f2f50d35d4c085998b01a109a4a9a6cc49b0cdb7  openspec/changes/canonicalize-inspection-evidence-schema/specs/deployment/canonical-inspection-evidence-schema/spec.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
800d135a043633260ce59440579f35f4dcf16553c61f7e149d82825c9e6c3509  tests/bootstrap.php

POST-GATE-4 CONTEXT (not frozen by Gate 3):
2e514ad5b270962d8bcb5036c5933a55157d0b42e91f2aaf01c663eddec78d4b  app/InstallationProcess/InspectionEvidenceSchemaMigration.php
5ca58842cf4b0f1e7107cb277afa349251b6f971b1ace2d1527c841f34348007  app/InstallationProcess/CanonicalMigrationApplication.php
eba45bff34689c54088e89b9d4801c8c16bef2b420b3abdaed7f9f98e8c7bef6  app/PilotHttp/ChecklistSync.php
024e88f6879b7ac7d33b2deeb672d514e397b24530c1f989caeaa7cd018bbc23  bin/fmonitor2-migrate.php

METADATA  reviews/tests/INSPECTION-EVIDENCE-SCHEMA-001.md
```

OpenSpec task 2.2 is complete again. The corrected exact test/spec/evidence
bytes are frozen for Gate 4 completion and fresh independent Gate 5 review.

---

## Integration-driven shared-v8 contract rereview — 2026-09-01

- Reviewer: separately tasked agent `/root/inspection_v8_test_review`
- Independence: reviewer did not author the shared contract or eight changed tests
- Verdict: `APPROVED`

The integration edits correctly advance only composed real-runner expectations
to terminal schema v8 and `[1,2,3,4,5,6,7,8]`. They do not renumber or weaken
the direct predecessor seams: the checklist-template test still asserts literal
`ChecklistTemplateSchemaMigration` version 7 results, and the identity/access
test still asserts literal `IdentityAccessSchemaMigration` version 6 results.

`ProductionMigrationRunnerCatalogContract` is test-owned and does not load
production migration SQL. Its literal catalog now contains exactly 26 tables.
The four v8 tables contribute exact ordered columns/types/nullability/extras
and ten exact primary/unique/secondary index tuples. The composed runner test
compares the complete returned table set against those 26 names, compares every
column/index tuple, and compares the complete global FK and CHECK lists. Because
the v8 tables add no entries to the latter two lists, any v8 FK or CHECK (or any
other extra constraint represented there) fails the shared contract. The
dedicated approved test retains the stricter v8 defaults, generated metadata,
collation, index visibility/direction, and no-FK/no-CHECK fingerprints.

The other consumers change only their canonical setup/catalogue expectations:
case import, HTTP auth, workforce runner, checklist-template runner,
identity/access runner, production runner and OTIZ compatibility now require
the real runner's v8 terminal state. No product acceptance/rejection literal or
known GRILL-002 assertion was removed or relaxed.

### Independent executions

With `FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local` where required:

```text
PASS  tests/InstallationProcess/inspection_evidence_schema_001_test.php
PASS  tests/InstallationProcess/checklist_template_schema_001_test.php
PASS  tests/InstallationProcess/identity_access_schema_001_test.php
PASS  tests/InstallationProcess/pilot_case_import_001_test.php
PASS  tests/InstallationProcess/production_migration_runner_001_test.php
PASS  tests/InstallationProcess/workforce_canonical_runner_001_test.php
PASS  tests/Verification/harness_otiz_canonical_compat_001_test.php
```

The first direct case-import attempt without the test password was correctly
classified as setup failure (`Access denied`), then passed with the harness
credential. It is not a RED or regression.

`pilot_http_auth_001_test.php` passed the new real v8 migration fixture and then
reached the established unrelated CSP baseline:

```text
missing identity despite spoof content-security-policy
Expected: default-src 'none'; style-src 'self'; img-src 'self'; ...
Actual:   default-src 'none'; style-src 'self'; script-src 'self'; img-src 'self'; ...
```

This is the pre-existing `script-src 'self'` expectation mismatch, not a v8
fixture/schema failure. The test edit changes only its setup result from v7 to
v8, so the GRILL-002 outcome remains visible and unweakened.

### Exact SHA-256 reviewed manifest

```text
82b82114ab7db34c63a06ec34dd287d38a0f25e52e71b4dd314545f97f0f58d7  specs/INSPECTION-EVIDENCE-SCHEMA-001.md
e06b02081fcebd70407a6d18c8d294549cc8b16bc1dbd948bb92497d99dbb989  tests/Support/ProductionMigrationRunnerCatalogContract.php
d8d96368b9f45d66da15dff91bf6265cacf6eb0f5b7ea44ad71676197a2b0b9d  tests/InstallationProcess/checklist_template_schema_001_test.php
bb411e4bcf6d1d9dbb0fb7f513672923c903bb32c7fe893c6e8e30e9e2068ad4  tests/InstallationProcess/identity_access_schema_001_test.php
3e9ce3564f3b5645e2c703887cd7782a3d5804911437f83387a2283723e3ea38  tests/InstallationProcess/inspection_evidence_schema_001_test.php
a4f03f69b4061cc93b36488ca49319615c5cd6b367a03b761b1233d241d8b2f9  tests/InstallationProcess/pilot_case_import_001_test.php
50fefbdc32e93eee2874db1b0cd67ac782af7e28dd106654771fa6997a27ec73  tests/InstallationProcess/pilot_http_auth_001_test.php
81df9e82583c489380d917d615e00d89021c8e35e199a3639364545a5deb1d03  tests/InstallationProcess/production_migration_runner_001_test.php
7e02179ce41b308a187355b30d15cb527788db32f5827d9b3756828eae3fc7fb  tests/InstallationProcess/workforce_canonical_runner_001_test.php
b0fd777b8748a54611bf6061299b38cb48944f85b6512a7ff8e0b6bd868eb639  tests/Verification/harness_otiz_canonical_compat_001_test.php
475860373aef8f7b48e7cc334c792af9408165451fb82eddc8c2b248398bff62  docs/operations/inspection-evidence-schema-red-evidence.md
b6910d389763d75b0eb5c907014cb81cd3116a12d714ae7a985c1f6b1327572e  docs/operations/inspection-evidence-schema-green-verification.md
7890acc5ffeb7e163a013a6892072e8747d5a2be95a41a88461009c812db80ca  openspec/changes/canonicalize-inspection-evidence-schema/tasks.md

POST-GATE-4 CONTEXT:
2e514ad5b270962d8bcb5036c5933a55157d0b42e91f2aaf01c663eddec78d4b  app/InstallationProcess/InspectionEvidenceSchemaMigration.php
5ca58842cf4b0f1e7107cb277afa349251b6f971b1ace2d1527c841f34348007  app/InstallationProcess/CanonicalMigrationApplication.php
eba45bff34689c54088e89b9d4801c8c16bef2b420b3abdaed7f9f98e8c7bef6  app/PilotHttp/ChecklistSync.php
024e88f6879b7ac7d33b2deeb672d514e397b24530c1f989caeaa7cd018bbc23  bin/fmonitor2-migrate.php

METADATA  reviews/tests/INSPECTION-EVIDENCE-SCHEMA-001.md
```

Any byte change in the shared contract, eight reviewed tests, dedicated test,
specification, or evidence invalidates this integration approval. Gate 5 remains
independent and mandatory.

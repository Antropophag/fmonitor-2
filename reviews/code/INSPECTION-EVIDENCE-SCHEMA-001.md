# Code review: INSPECTION-EVIDENCE-SCHEMA-001

- Date: 2026-09-01
- Fixed point: `970d44012cfc3e0140ebac77bb0c5b0b95723884`
- Standards reviewer: separately tasked agent `/root/inspection_v8_standards_review`
- Specification reviewer: separately tasked agent `/root/inspection_v8_spec_review`
- Verdict: `APPROVED`

## Reviewed contract and tests

The normative specification and dedicated executable test remained frozen for
Gate 5:

```text
82b82114ab7db34c63a06ec34dd287d38a0f25e52e71b4dd314545f97f0f58d7  specs/INSPECTION-EVIDENCE-SCHEMA-001.md
3e9ce3564f3b5645e2c703887cd7782a3d5804911437f83387a2283723e3ea38  tests/InstallationProcess/inspection_evidence_schema_001_test.php
```

Gate 3 is independently `APPROVED` in
`reviews/tests/INSPECTION-EVIDENCE-SCHEMA-001.md`. Its post-Gate-4 fixture
correction review and shared-v8 integration review were performed by separately
tasked agent `/root/inspection_v8_test_review`. The dedicated test correction
only made the fixture's section/item pair valid; it did not change an
expectation, seam, schema oracle, conflict case, or runtime-no-DDL assertion.
The shared review approved the literal v8 catalogue changes while preserving
the known GRILL-002 expectations.

## Specification axis

The separately tasked specification reviewer `/root/inspection_v8_spec_review`
reviewed the exact public runner ordering, four-table fingerprints, both exact
additive predecessors, family-wide fail-closed preflight, prefix/collation
handling, repeat behavior, runtime DDL removal, and integration evidence.

Verdict: `APPROVED`, with no blocking or non-blocking findings.

The reviewer confirmed that canonical v8 is registered once after literal
v1-v7, preserves compatible rows and allocators, refuses incompatible family
members before mutation, and leaves checklist runtime as a schema consumer.

## Standards axis and closure

The first standards pass returned `CHANGES_REQUESTED`. The initial
`InspectionEvidenceSchemaMigration.php` was exactly 149 lines only because its
DDL, manifests and positional fingerprint logic were compressed into lines up
to 1,116 characters. That representation created Duplicated Code, Data Clumps,
Primitive Obsession and Divergent Change risk and defeated the intent of the
150-line hotspot ratchet.

The implementation was refactored and freshly rereviewed. The final
responsibilities are cohesive:

- the 83-line migration class owns family inspection and application;
- the 130-line definition class owns the shared catalogue and typed metadata
  builders;
- the 117-line operation-definition class owns operation/installers final and
  predecessor forms;
- the 70-line exact fingerprint class owns MariaDB metadata comparison.

Opaque pipe-delimited signatures were replaced by named arrays. No file is a
new hotspot, no suspicious line compression remains, and dependencies stay
inside `InstallationProcess`. The fresh standards verdict is `APPROVED`, with
no remaining blocking or non-blocking findings.

## Exact final production manifest

```text
e7b0cf2d1e79bc23b449b01ecacdb562b094e96859b6a3768ff1fe1321622bf3  app/InstallationProcess/InspectionEvidenceSchemaMigration.php
bfc2419df51a1ae8400b3d674043e0867d2e8de34cb1292525bf7aa415905b36  app/InstallationProcess/InspectionEvidenceDefinitionSchemaMigration.php
752bbe073cf39db4c353f5c448beeb0b7ac7d67930329dbe97ef98147abe0c83  app/InstallationProcess/InspectionEvidenceOperationDefinitionSchemaMigration.php
b4c858dcec502122f20f255e234d308ab937f60570c2a59faa33c843b5905f0e  app/InstallationProcess/MariaDbExactSchemaFingerprint.php
5ca58842cf4b0f1e7107cb277afa349251b6f971b1ace2d1527c841f34348007  app/InstallationProcess/CanonicalMigrationApplication.php
024e88f6879b7ac7d33b2deeb672d514e397b24530c1f989caeaa7cd018bbc23  bin/fmonitor2-migrate.php
```

The reviewed slice bytes of `app/PilotHttp/ChecklistSync.php` had SHA-256
`5a09072941f44a48a5f8b628e872663b2994927097dc8c3c0ac20f369474c19d`:
the v8 slice imports `InspectionEvidenceSchemaMigration`, replaces runtime
CREATE/ALTER statements with `isCompleteCompatible()`, and fails closed with
`INSPECTION_EVIDENCE_SCHEMA_REQUIRED`. At final record time the whole dirty
file SHA-256 is
`eba45bff34689c54088e89b9d4801c8c16bef2b420b3abdaed7f9f98e8c7bef6`;
the only difference from the reviewed whole-file bytes is unrelated global
qualification of existing PHP built-ins outside the v8 ownership hunk. The
v8 hunk itself is unchanged and was reviewed surgically.

The final 24-file standards rereview scope manifest, including the extracted
classes, had aggregate SHA-256
`da2b5116704aac8d5335a833869e92fcc6b9df76d80e8dc47c09bb1395b9b1cb`.
The test-owned literal integration catalogue is:

```text
e06b02081fcebd70407a6d18c8d294549cc8b16bc1dbd948bb92497d99dbb989  tests/Support/ProductionMigrationRunnerCatalogContract.php
```

Several rapid-pilot verifier files are dirty from concurrent work. Review was
surgically limited to the v8 checklist setup hunks: loading the migration
dependencies, applying the canonical family in isolated verifier prefixes,
using complete canonical fixture rows, and cleaning all four family tables.
In particular, unrelated identity/RBAC, OTIZ fixture expansion, CSP, and other
dirty hunks in `rapid-pilot/verify-otiz-workflow.php` were excluded from this
approval.

## Verification evidence

Focused lint, the dedicated v8 DB test, composed runner checks, scoped
`git diff --check`, and `make architecture-check` passed. Architecture reported
all 7 rules green. The final full verification returned the established
baseline without a new v8 regression:

```text
canonical migrate: PASS, schemaVersion=8, appliedVersions=[1,2,3,4,5,6,7,8]
architecture-check: PASS (7 rules)
lint: PASS
unit-test: PASS
db-test: 8 known GRILL-002 failures
characterization-test: PASS
e2e-test: 1 known missing assignment-order artifact failure
diff-check: PASS
```

The DB and E2E results are pre-existing classified baseline failures, remain
visible, and were not weakened or reclassified by this slice.

## Final finding

The exact reviewed slice conforms to the approved specification, reduces
runtime DDL debt, preserves architecture boundaries, and closes the initial
maintainability finding. Gate 5 is `APPROVED`. OpenSpec task 4.2 may be marked
complete; task 4.3 remains pending until the orchestrator performs the final
Done transition.

# INSPECTION-EVIDENCE-SCHEMA-001 — Gate 2 RED evidence

Date: 2026-09-01  
Role: separately tasked RED test author  
Approved specification commit: `ceb051e5b192ccee323fca26e4c773715a2d0b43`  
Approved specification SHA-256: `82b82114ab7db34c63a06ec34dd287d38a0f25e52e71b4dd314545f97f0f58d7`

## Public seams and scope

The executable artifact uses the approved canonical runner
`php bin/fmonitor2-migrate.php` and, after the clean tracer becomes GREEN, the
approved public `InspectionEvidenceSchemaMigration::apply(mysqli, string)`
seam. Expected schemas and predecessor fixtures are literal test-owned
transcriptions of specification sections 3 and 4, not values obtained from a
production migration implementation.

The retained post-tracer matrix now encodes G2-01–G2-16 sensitivity: literal
column/index/table/constraint fingerprints, all 36 compatible forms,
independent and combined predecessor variants, malformed additive subsets,
representative column/table/index/FK/CHECK conflicts, atomic multi-conflict,
prefix boundaries and isolation, UCA/non-utf8 database defaults, isolated
catalogue corruption, real item/photo/projection success under a DML-only
principal, and an actual photo-operation sequence for every owned table
absent/incompatible with DB and recursive storage fingerprints.

Two hostile metadata inputs cannot be constructed through the approved real
`mysqli` seam: MariaDB will not allow creation of a database with a missing
`SCHEMATA` row or malformed/unknown/non-applicable default collation. The test
therefore covers the externally constructible non-utf8 default and UCA alias;
full synthetic sensitivity for the impossible metadata rows would require a
new injectable metadata seam, which is production architecture and outside
Gate-2 test-author authority. The fail-closed cases exercise the approved
`ChecklistSync` public seam directly and attempt the valid photo action after
schema validation. Exact JSON/HTTP mapping is not directly callable on
`ChecklistSync`: `PilotE2ECoordinator::checklist()` is private and its public
`handle()` requires the final concrete `ProductionPilotHttpDependencies` plus
trusted identity, CSS descriptors, legacy user/card tables, session and stdin
body orchestration. No existing test support exposes a narrower callable HTTP
adapter seam. The test therefore uses the strongest reachable approved
sync/photo seam without inventing production architecture; the coordinator's
existing public catch maps `PilotHttpInfrastructureUnavailable` to status 503
and `status=retryable`.

## Environment and setup proof

Command:

```text
make test-env-up && php tests/InstallationProcess/inspection_evidence_schema_001_test.php
```

The disposable MariaDB container reached `Healthy`. The test then:

1. created a fresh random `utf8mb4`/`utf8mb4_unicode_ci` database;
2. started the real canonical runner successfully;
3. asserted runner exit `0` and empty stderr;
4. decoded runner stdout as JSON without parse error;
5. passed the independent prerequisite assertion that the first applied
   versions were exact `[1,2,3,4,5,6,7]`.

Thus DB connection, database creation, runner setup, JSON parsing, and all
landed prerequisites v1-v7 succeeded before the target assertion.

## Qualified RED transcript

Exit: `255`

```text
PHP Fatal error:  Uncaught TestFailure: G2-01 canonical runner must own literal inspection-evidence v8 after proven v1-v7.
Expected: 8
Actual: 7 in /home/antropophag/code/fmonitor-2/tests/bootstrap.php:36
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/inspection_evidence_schema_001_test.php(296): assertSameValue()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/bootstrap.php on line 36
```

The failure is the intended missing behavior: the real runner completed
through literal v7 but does not register or apply canonical inspection-evidence
v8. It is not an environment, connection, fixture, permission, predecessor,
or parse failure. No production code was changed.

Syntax check:

```text
$ php -l tests/InstallationProcess/inspection_evidence_schema_001_test.php
No syntax errors detected in tests/InstallationProcess/inspection_evidence_schema_001_test.php
```

## Artifact hashes

- `tests/InstallationProcess/inspection_evidence_schema_001_test.php`:
  `ebca4c75101f2714e69843171a60f993f7443efee4b7a147eb4f1942d18995c0`
- `openspec/changes/canonicalize-inspection-evidence-schema/tasks.md` after
  marking qualified RED task 2.1 complete:
  `678bf8c4f1d7f8db12d37bb6024fb4c7bcef34c17aa7bda6c682322323781d87`

Gate 2 result: **QUALIFIED RED**. Gate 3 independent test review is required
before implementation.

## Post-approval fixture correction — 2026-09-01

Gate 4 exposed a test-owned G2-14 fixture defect: item `1` was paired with
section `1`, while the landed public `ChecklistSync` contract maps item `1` to
section `3`. This was a valid runtime rejection, not an implementation defect.
The frozen test fixture alone was corrected to `sectionId=3,itemId=1`; the
photo already used section `3`, so its base revision and projection
expectations remain unchanged.

The original qualified RED transcript above is retained as historical Gate 2
evidence. The corrected artifact was checked against the current Gate 4
worktree:

```text
$ php -l tests/InstallationProcess/inspection_evidence_schema_001_test.php
No syntax errors detected in tests/InstallationProcess/inspection_evidence_schema_001_test.php

$ make test-env-up && php tests/InstallationProcess/inspection_evidence_schema_001_test.php
INSPECTION-EVIDENCE-SCHEMA-001 tests passed.
```

Corrected test SHA-256:
`3e9ce3564f3b5645e2c703887cd7782a3d5804911437f83387a2283723e3ea38`.

Because reviewed test bytes changed, OpenSpec task 2.2 is returned to unchecked
pending fresh independent test rereview. No production or specification bytes
were changed by this correction.

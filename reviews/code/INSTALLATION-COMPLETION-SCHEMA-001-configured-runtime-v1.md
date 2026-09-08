# Independent Gate 5 code review — INSTALLATION-COMPLETION-SCHEMA-001 configured runtime

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/completion_runtime_gate5`
- Production candidate: `cd72c4cdaa4619a7642453d6ce3319a502927aeb`
- Gate 3 review: `84267e1b1e8b7ec9fec620019d551b56a531f55c`
- Reviewed test commit: `918a5670a72ec6819db38d544e0cab191e65a1f9`
- Reviewed RED evidence head: `f24df6631c8be1c25a75e2b6976c7157569a127f`
- Verdict: **APPROVED**

This reviewer did not author or edit the specification, approved test, support
router, production implementation, or GREEN evidence. This append-only review
record is the reviewer's only change.

## Exact reviewed artifacts

```text
c6f3cf995a81d214559d4078696f82d6d2cfaa1123120cb91775fc5c6b5c5448  specs/INSTALLATION-COMPLETION-SCHEMA-001.md
e570542db85396474cf639cdd6b733a545f4379b79294411fc01acda8df98c00  reviews/tests/INSTALLATION-COMPLETION-SCHEMA-001-card-local-identity-integration-v3.md
93463d5c519d010e5b37d880ecd0fa975ace7db62bb9175acca077f26cd4cf6d  tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
1331bc60607cd8a6c7f7e87a0bcb1d837a727c90e00465185dfd789c2ebbb682  tests/Support/installation_completion_runtime_router.php
22ea2d7f0455f1778522f807ef9e0ead1303c5205762b2ba1eee1e124f87dfeb  app/PilotHttp/ChecklistSync.php
ff41533ddd438697cc3e2bb609be6e2ba97fb1784da9751cd074615ce46d28f4  app/PilotHttp/MariaDbChecklistProjectionStateReader.php
f6491662738821743976e06086bcb988269c78a4b3d87b9899df4f65575b30b0  app/PilotHttp/PilotE2ECoordinator.php
f25457f3a89fb09cf2dc7877695c662823aa03743382b2b52f8f7ce2c883ea8f  docs/operations/installation-completion-configured-runtime-green-2026-09-04.md
```

The production commit is a descendant of the approved Gate 3 commit. The two
approved executable artifacts retain the Gate 3 hashes; the production commit
does not edit either test or its support router.

## Standards axis

The implementation keeps completion-schema ownership in canonical migration
v10 and calls its read-only compatibility check before constructing or reading
the checklist projection. Missing and drifted completion families therefore
fail before checklist revision initialization, installer-attribution backfill,
completion DML, HTML enhancement, or ready-manifest publication.

`ChecklistSync::projection()` now obtains revision and historical operation
installer attribution through the named
`MariaDbChecklistProjectionStateReader`. Both methods contain SELECT-only SQL.
The former backfill method remains private but has no call site. Revision-row
initialization remains on accepted write paths through the existing private
`revision()` method, including the locked operation path and duplicate-photo
write response. This preserves write revision behavior without read-side DML.

The configured object-card route resolves the active local actor and requires
the exact `objects.read` capability. `REMOTE_USER` remains a decoy rather than
positive or display identity. Completion readiness failures on card and
checklist HTML GET/HEAD use the plaintext page contract with `Retry-After: 60`;
the checklist operation/photo/sync paths retain their JSON error contract and
remain outside the completion-family readiness requirement.

No new domain logic is placed in `rapid-pilot/`, no runtime DDL or repair is
introduced, and the change preserves the explicit application/read-adapter
boundaries checked by the architecture rules.

## Spec axis and independent verification

The exact configured runtime matrix was run against MariaDB at
`127.0.0.1:23306` under its DML-only runtime principal. It covers missing,
drifted and exact completion families across queue, card GET/HEAD, checklist
GET/HEAD, completion POST and bootstrap. Its full prefixed-family snapshots
prove zero mutation on rejected reads and only the expected PTO root append on
the healthy write. It passed:

```text
PASS: INSTALLATION-COMPLETION-SCHEMA-001 DML-only runtime matrix
```

The related public endpoint, current-crew/historical-attribution replay,
MariaDB persistence/concurrency and migration matrices also passed:

```text
PASS: INSPECTION-ITEM-COMPLETE-001 raw HTTP endpoint admission
PASS: INSPECTION-ITEM-COMPLETE-001 exact replay after mutable changes
PASS: INSPECTION-ITEM-COMPLETE-001 MariaDB concurrency and persistence
PASS: INSTALLATION-COMPLETION-SCHEMA-001 migration and chain matrix
```

Repository checks passed:

```text
ARCHITECTURE CHECK PASSED (7 rules)
make lint: PASS
git diff --check: PASS
```

## Findings and verdict

No blocking or non-blocking correctness finding was identified in the reviewed
scope. The candidate implements the approved RED with a minimal production
change, preserves the reviewed test surface, and satisfies both Standards and
Spec axes. Gate 5 is **APPROVED** for production commit
`cd72c4cdaa4619a7642453d6ce3319a502927aeb`.

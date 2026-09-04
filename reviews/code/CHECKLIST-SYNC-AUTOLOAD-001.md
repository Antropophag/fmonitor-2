# ChecklistSync projection reader loading — independent Gate 5 code review

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/assignment_evidence_gate1`
- Approved Gate 3: `dae7d2d0268c20a0de2481a6be6880cc542a2da1`
- Reviewed production commit: `56ffd66759b349cd9cefa748b81dd7481311da4c`
- Reviewed production file SHA-256: `b1a5256a0d61a70a3feba21f99f7e358893b9aaadbf4a6195a3fc7e288b79d44`
- Verdict: **APPROVED**

## Production diff and ownership

The candidate changes exactly one production dependency-load statement in
`app/PilotHttp/ChecklistSync.php`:

```php
require_once __DIR__.'/MariaDbChecklistProjectionStateReader.php';
```

No test, verifier, SQL, domain rule, DTO, route, storage path, migration or
result expectation changed between the approved Gate 3 and production
candidate. The added file was already production code; loading it declares only
the namespaced final readonly reader class and performs no database query,
filesystem access, registration, mutation or other top-level side effect.

`ChecklistSync.php` is the established standalone composition owner. It already
loads its direct MariaDB case resolver and the InspectionEvidence collaborators
needed by callers that require this public adapter without `app/autoload.php`.
The projection method directly constructs
`MariaDbChecklistProjectionStateReader`, so loading that collaborator beside the
existing direct require keeps dependency knowledge at the owner instead of
duplicating it in five callers. `require_once` also makes dependency loading
idempotent within the process.

The production line was kept within the existing serialized-file format. File
length remains exactly 149 lines before and after the change, so the hotspot
line ratchet is neither weakened nor bypassed by changing its baseline. The
architecture checker independently accepts all seven rules.

## Conformance and regression sensitivity

This is the exact minimal GREEN prescribed by Gate 3. Before the change, all
five unchanged public paths reached `ChecklistSync::projection()` and failed at
construction with `Class ... MariaDbChecklistProjectionStateReader not found`.
With the production require present, the same paths exercise their full,
independently approved matrices:

- current crew projection and historical attribution;
- photo upload acceptance/replay/storage-failure behavior;
- rejected photo zero-mutation behavior;
- immutable upload/revoke/reupload projection;
- winner-neutral limit/concurrency serialization.

A missing/wrong include, empty class, bypassed projection, hidden caller fake or
SQL/domain change cannot satisfy these unchanged results. The fix therefore
repairs composition only while retaining strong behavioral sensitivity.

## Independent verification

The reviewer reran all five paths sequentially against the disposable MariaDB
because the four meta-verifiers assert whole-schema isolation:

```text
Checklist current crew contract OK.
ok - CHARACTERIZE-INSPECTION-PHOTO-UPLOAD-001 public oracle is deterministic, isolated, and correctly classified
ok - CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001 public oracle is deterministic, isolated, and correctly classified
ok - CHARACTERIZE-INSPECTION-PHOTO-REVOKE-001 oracle is deterministic, audited, isolated, and correctly classified
ok - CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001 oracle is deterministic and isolated
```

Additional checks:

```text
$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

$ php -l app/PilotHttp/ChecklistSync.php
No syntax errors detected in app/PilotHttp/ChecklistSync.php

$ require_once ChecklistSync.php twice and inspect dependency class
DEPENDENCY_LOAD_OK

$ wc -l app/PilotHttp/ChecklistSync.php
149 before; 149 after

$ git diff --check
PASS (no output)
```

The aggregate `make characterization-test` reaches every checklist verifier and
all five checklist paths pass. Its only failure is the previously classified,
unrelated macOS host limitation:

```text
tests/Verification/characterize_inspection_schedule_duplicate_001_test.php
proc_open(): posix_spawn() failed: No such file or directory
REGRESSION_FAILURE: 1 verifier(s) failed
```

That verifier invokes the unavailable external `setsid`; the failure precedes
and does not exercise ChecklistSync or the reviewed change. It is not treated as
green suite evidence, but it does not contradict the five focused GREEN results.

## Verdict

**APPROVED.** The implementation is the independently prescribed one-line
composition fix, has no hidden side effects, preserves dependency ownership and
the hotspot ratchet, and leaves SQL/domain/tests unchanged. All relevant
unchanged public-seam verifiers are green. `CHECKLIST-SYNC-AUTOLOAD-001` has
completed Gate 5 at the exact production commit above.

The reviewer changed no production or test file. Only this immutable code-review
record was added.

# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — corrective factory/worker Gate 3 v6

- Reviewer: separately tasked agent `/root/original_upload_factory_worker_red/original_upload_factory_worker_gate3`
- Review timestamp: `2026-09-04T06:32:37+03:00`
- Reviewed commit: `830c630e4e7baccf5be8650e64a09230414a43e1`
- Predecessor review: `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-factory-worker-v5.md`
- Specification: `specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md` v4, owner-approved SHA-256 `97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479`
- Verdict: **CHANGES_REQUESTED**

## Independence and exact artifacts

The reviewer authored only this append-only rereview. The specification, test,
RED evidence and production implementation were not changed.

```text
42cc0e41ceae54488ffbbd5dd1d9d5635a4c38b12b47baca6d7dbf2feb101e91  tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php
39daf3ae7f6bd387e6d394fbdee64452b2064ce1c852c5b0b268bac2bf02cc1b  docs/operations/assignment-order-original-upload-gate5-factory-worker-red-correction-v2-2026-09-04.md
81de7900918ddd185fe6ae5c9456775720674a66f9c10c9030fdc3f4c1abc441  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-factory-worker-v5.md
```

## Blocking finding: replay proves compatibility, not provenance

The unreadable replacement stream is a good assertion of v4 request-replay
precedence. It proves that a worker-created terminal request row is readable by
the production repository and that the production application returns it before
stream access. It still does not prove that the worker created that row by
calling the production-composed application.

The current direct-SQL worker remains a concrete plausible bypass. After the
factory itself is made constructible, the worker can retain its duplicate SQL
authorization, composition lookup, fingerprint calculation, CAS and direct
revision/request/event inserts, while merely accepting `inspectorMode=real`.
Those direct inserts produce the terminal row which
`aoocCrossReplayWorkerResult()` then asks the production application to replay.
The replay succeeds precisely because request lookup observes storage state,
regardless of which code path created it. The unreadable stream stays unread in
both the correct implementation and this bypass.

The evidence-reader assertions likewise verify resulting facts, not their
state-changing origin. The factory preflight already creates the one canonical
private blob, and the direct-SQL worker can reference the same digest identity,
so the blob-count assertion does not distinguish the bypass either. Thus the
test can still pass while neither child reconstructs adapters and invokes the
same application owner required by v4 section 16 and the Gate 5 correction.

## Required correction

Add an oracle at the child composition boundary which is emitted or made
observable only when the worker actually builds and invokes the declared
application composition; do not infer provenance from rows that arbitrary SQL
can reproduce. A focused structural policy check that forbids SQL/database
domain ownership in the worker bootstrap, combined with behavioral worker
coverage, is also a valid route if it follows the repository's architecture
test conventions. Preserve the five-descriptor IPC/barrier contract, real PDF
mode, unread replay precedence and read-only evidence-reader observations.
Capture fresh RED against the current direct-SQL implementation and request a
new independent Gate 3 review.

## Verification

```text
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php

$ git diff --check 30aa791..830c630
PASS (no output)

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid
```

The committed credentialed RED still fails at the earlier factory
`LogicException`, consistently with current production. That intended failure
does not exercise the new replay assertion and cannot demonstrate its
sensitivity to direct-SQL worker ownership.

Gate 3 remains **CHANGES_REQUESTED**; Gate 4 must not proceed from commit
`830c630e4e7baccf5be8650e64a09230414a43e1`.

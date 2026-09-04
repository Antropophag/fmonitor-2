# PILOT-SESSION-STORAGE-001 — anonymous repeated commit Gate 3 review

Date: 2026-09-04

Reviewer: `/root/session_anonymous_repeat_gate3`

Reviewed commit: `a12326cae59e4cdbe30d4c4484d99f6cca95b8cd`

Base: `94c1846f7744a5b5fa7001ad99ab54ea9f6a4913`

Verdict: **REJECTED**

Independence: the reviewer did not author or edit the reviewed test or RED
evidence. This review adds documentation only.

## Traceability and scope

The two-file diff adds only the focused public-owner test and its evidence.
Production code is unchanged. Approved inputs match the evidence:

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
a2e376531a4db9364cc16636388d9bc8285bd54b06d16ddd8b68edd6f0818496  reviews/tests/PILOT-SESSION-STORAGE-001-local-auth-lifecycle-v1.md
1abbf879022d43d2e85bc4bfcd1ae8845fe46c09c8c7768fb9e8c4f0013c354e  reviews/tests/PILOT-SESSION-STORAGE-001-architecture-ratchet-v2.md
```

The deterministic entropy queue establishes ID1 and ID2 without relying on
ambient randomness. The primary scenario correctly exposes the ordinary-write
bug: the same owner starts ID1, commits payload one under ID1, then an ordinary
second `writeCommit(ID1, payload2)` incorrectly returns ID2. A diagnostic run
also observed two committed files with payloads `one,two`, confirming that the
current owner publishes a second identity rather than retaining one file under
ID1 with the latest bytes.

## Blocking spec finding

The external-collision oracle is attached to the wrong lifecycle operation.
The test expects `start(null)` itself to skip the preexisting committed target
and return ID2. Sections 3 and 4 specify an in-memory anonymous start followed
by collision detection and candidate retry during the no-clobber
`writeCommit` publication. On the reviewed implementation the isolated
collision scenario therefore returns ID1 from `start(null)` and would fail the
test even after the intended repeated-write defect is fixed.

The corrected public-owner sequence was independently probed without changing
repository files: `start(null)` returned the colliding ID1;
`writeCommit(ID1, bytes)` returned deterministic ID2; the preexisting mode-0600
foreign file retained its exact SHA-256; and the newly owned committed file was
separate. The preservation requirement is valid, but the checked operation and
the RED evidence statement must be corrected.

Because the primary assertion aborts first, the committed-file/latest-bytes
and external-collision assertions in the checked-in test are not reached by
the recorded RED execution. The diagnostic observations support the primary
defect, but they do not cure the second, unrelated false oracle.

## Reproduction

```text
$ php -l tests/InstallationProcess/pilot_session_storage_anonymous_repeat_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_session_storage_anonymous_repeat_001_test.php

$ php tests/InstallationProcess/pilot_session_storage_anonymous_repeat_001_test.php
Repeated anonymous commit retains the caller current ID1.
Expected: [OK, 1111111111111111111111111111111111111111111111111111111111111111]
Actual:   [OK, 2222222222222222222222222222222222222222222222222222222222222222]
exit 255

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
Admitted malformed item maps to HTTP 422.
Expected: 422
Actual: 403
exit 255
```

The checklist verifier has no diff from the fixed base, so the `403` symptom
is unchanged rather than introduced or weakened by this slice.

## Standards

No production-standard or smell finding in the two-file focused diff. The test
uses the approved public seam, deterministic dependencies, task-owned artifact
namespace, and `finally` cleanup. The blocking issue is specification accuracy,
not style.

## Cleanup

Both the checked-in failing test and independent diagnostic probes removed
their exact owned artifact roots. No matching `.test-artifacts` child remained;
the checklist verifier reported no cleanup aggregate, and the worktree was
clean before this review document was added.

## Required correction

Change the external-collision scenario to assert ID1 from `start(null)`, then
call `writeCommit(ID1, payload)` and assert returned ID2, foreign-byte
preservation, and separate owned publication. Update the RED evidence to name
the collision at commit publication. Re-run fresh Gate 3 afterward.

# Independent Gate 3 review — PRODUCTION-RUNTIME-SESSION-CONTENTION-001

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/runtime_review`
- Test author: separately tasked agent `/root/runtime_tests`
- Verdict: **APPROVED (BOUNDED)**

## Exact reviewed artifacts

```text
d858b529f8ce6edd0c361c0a9b697f4bd436e0eddfd8b35bbf89d52aa10d6b24  specs/PILOT-SESSION-STORAGE-001.md
86c01bf40652775f004d52a9162204379ec04c40b77640426984bb0e38d74609  specs/PRODUCTION-HTTP-RUNTIME-001.md
83c3f9d66e0ba224333637642f712083d3694db715c5be3b6baebc4e62c0c074  openspec/changes/production-http-runtime/specs/operations/production-http-runtime/spec.md
799e824a6fee1d3cc1a448892a971a817ac4a17f18f478e8ba11bb18cc882edd  tests/Runtime/session_contention_001_test.php
f079416f7b7e234b908a08efc5d5c3f25274741f543df2421f857609ee938aa7  tests/Runtime/session_contention_worker.php
```

## Review

The test uses the public `PilotSessionStorageFactory` and `start(existingId)` seam
with native filesystem primitives. The parent owns the exact session lock. A real
lifecycle observer in the child signals only after the first native FLOCK returns
non-OK, proving actual contention rather than process-start timing. The parent keeps
the lock for another 250ms, releases it within the normative two-second deadline,
and bounds child completion to three seconds.

While holding the lock after proven contention, the parent replaces the committed
payload. The expected OK result must contain those fresh bytes read after lock
acquisition, and the committed file must remain unchanged afterward. The test neither invokes a private lock
method nor replaces flock behavior. Existing fault suites retain permanent
native-false/warning/exception coverage.

The specification now distinguishes native WOULD_BLOCK from permanent failure and
allows only read/start to succeed after waiting and reading fresh bytes. Contended
write/regenerate/destroy remains fail-closed, avoiding stale payload publication.

## Demonstrated RED

```text
$ php tests/Runtime/session_contention_001_test.php

INTENTIONAL_RED: transient native contention retries and returns the current committed payload
Expected: {status: OK, category: null, payload: authenticated-runtime-session-after-owner-update}
Actual:   {status: UNAVAILABLE, category: READ_FAILED, payload: null}
exit 255
```

Setup, the native lock, observed failed FLOCK, bounded release, child completion,
and exact session material all succeed before the assertion.

## Verdict

**APPROVED (BOUNDED).** Gate 4 may add the typed WOULD_BLOCK primitive result and
retry only `start(existingId)` reads within the existing deadline. Permanent faults
and contended mutation operations must retain their current fail-closed outcomes.

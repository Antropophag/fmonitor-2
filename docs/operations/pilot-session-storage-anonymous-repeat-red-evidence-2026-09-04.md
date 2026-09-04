# PILOT-SESSION-STORAGE-001 — anonymous repeated commit RED

Date: 2026-09-04

RED author: `/root/original_upload_red`

Base: `94c1846`

Verdict: **INTENDED RED; fresh Gate 3 required**

## Approved inputs

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
a2e376531a4db9364cc16636388d9bc8285bd54b06d16ddd8b68edd6f0818496  reviews/tests/PILOT-SESSION-STORAGE-001-local-auth-lifecycle-v1.md
1abbf879022d43d2e85bc4bfcd1ae8845fe46c09c8c7768fb9e8c4f0013c354e  reviews/tests/PILOT-SESSION-STORAGE-001-architecture-ratchet-v2.md
```

## Public-factory oracle

The smallest real-owner test calls only the approved public factory and storage
seam. Deterministic anonymous `start(null)` returns ID1. The first
`writeCommit(ID1,payload1)` must return ID1, and a second
`writeCommit(ID1,payload2)` must return the same ID1. Parent-side filesystem
observation independently requires exactly one committed file under ID1 and
the latest payload2 bytes.

A separate instance precreates a mode-0600 committed file for an entropy
candidate with externally-owned bytes. Anonymous start must skip that collision
for the next deterministic ID and preserve the foreign digest byte-identically.
The collision sentinel is not mixed into the primary one-file cardinality.
Both instances live below one validated task-owned repository test root, which
is removed and proved absent in `finally`.

## Intended RED

```text
$ php -l tests/InstallationProcess/pilot_session_storage_anonymous_repeat_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_session_storage_anonymous_repeat_001_test.php

$ php tests/InstallationProcess/pilot_session_storage_anonymous_repeat_001_test.php
Repeated anonymous commit retains the caller current ID1.
Expected: [OK, 1111111111111111111111111111111111111111111111111111111111111111]
Actual:   [OK, 2222222222222222222222222222222222222222222222222222222222222222]
exit 255
```

The current owner treats the second ordinary commit as regeneration, returning
ID2 and publishing a second identity. This is the intended behavioral RED, not
setup failure.

## HTTP integration evidence

The existing checklist durable-session verifier was not edited or weakened:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
Admitted malformed item maps to HTTP 422.
Expected: 422
Actual: 403
exit 255
```

The integration symptom remains consistent with CSRF/session material being
committed under ID2 while the client continues presenting ID1. The unit RED is
the causal public-owner seam; the HTTP test remains independent integration
evidence. Production code was not changed.

```text
$ git diff --check
PASS (no output)
```

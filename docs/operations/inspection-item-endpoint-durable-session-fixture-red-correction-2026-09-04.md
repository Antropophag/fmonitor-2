# Inspection item endpoint — durable session fixture RED correction

Date: 2026-09-04

RED author: `/root/original_upload_red`

Base: `181f8e7`

Verdict: **SETUP CORRECTED; next admission RED reached; fresh Gate 3 required**

## Approved session evidence

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
a2e376531a4db9364cc16636388d9bc8285bd54b06d16ddd8b68edd6f0818496  reviews/tests/PILOT-SESSION-STORAGE-001-local-auth-lifecycle-v1.md
1abbf879022d43d2e85bc4bfcd1ae8845fe46c09c8c7768fb9e8c4f0013c354e  reviews/tests/PILOT-SESSION-STORAGE-001-architecture-ratchet-v2.md
```

## Correction

The raw endpoint verifier configured the artifact root but omitted the approved
durable session owner. Its first checklist GET therefore returned 503 before
the CSRF/admission contract.

The fixture now creates a validated `sessions` child inside its existing
task-owned root, requires its real absolute path to remain beneath that root,
and passes exact `FMONITOR_SESSION_STATE_ROOT` plus unique
`FMONITOR_SESSION_INSTANCE=iea-<test-token>` to the real HTTP server.

Snapshot observation now separates allowed session state from immutable
evidence. Checklist tables and every non-session artifact must remain exactly
unchanged. The session subtree must start empty, become non-empty through the
expected CSRF/session flow, and contain only regular non-symlink files. The
existing recursive owned-root cleanup and post-cleanup absence assertion cover
the session subtree as well. No foreign cleanup target is introduced.

All endpoint admission, authorization, response, schema and cleanup assertions
remain otherwise unchanged.

## Demonstration

Before correction:

```text
Unassigned engineer obtains checklist CSRF page.
Expected: 200
Actual: 503
exit 255
```

After correction on exact `181f8e7`:

```text
$ php -l tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
No syntax errors detected in tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
Admitted malformed item maps to HTTP 422.
Expected: 422
Actual: 403
exit 255

$ openspec validate define-pilot-session-storage-contract --strict
Change 'define-pilot-session-storage-contract' is valid
$ git diff --check
PASS (no output)
```

The corrected fixture passes GET/CSRF/durable-session ownership and reaches the
next existing admission behavior mismatch. That 403/422 RED is not changed or
reclassified here. Production code was not modified; fresh Gate 3 is required.

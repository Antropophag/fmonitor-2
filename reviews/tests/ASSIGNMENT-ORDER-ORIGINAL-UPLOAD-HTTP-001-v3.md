# Test review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-HTTP-001 — v3 fixture correction

- Reviewer: `/root/original_gate3`, independently tasked agent; not author of the fixture correction or production implementation.
- Test author: root implementation agent.
- Reviewed commit: `051eaf0c33d257b29f3e5b99343af2e811b3c857` plus the two-line `OriginalHttpFixture.php` correction.
- Specification: unchanged HTTP v0.3 and canonical positive PDF literal in `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md`, section 5.
- Public seam: real router/session HTTP flow, unchanged.
- Verdict: `APPROVED`.

## Findings and verification

This bounded review covers only replacing the fixture's default PDF bytes and adding an upfront fixed-hash setup assertion. Earlier v1/v2 records remain preserved. During GREEN the original fixture sent `SelectedOriginalFixture::pdf()` (the passive PDF 1.7 corpus), whereas the HTTP flow had always expected the normative PDF 1.4 literal's hash. The correction fixes the input; it does not weaken or change the approved observable expectations.

Independently compared `AssignmentOrderOriginalRemainingMatrix::POSITIVE_PDF_BASE64` to the literal in the normative specification: byte-for-byte base64 equality, decoded size 327, SHA-256 `4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784`. This is data-only fixture provenance, not a value derived from implementation output. Explicit byte overrides used for rejection/boundary cases remain unchanged. The added setup assertion prevents another silent default-corpus mismatch.

Inspected the isolated RED worktree `/Users/antropophag/code/fmonitor-original-red-20260907`: HEAD is the reviewed preimplementation commit and its sole modified file is `tests/Support/OriginalHttpFixture.php`. SHA-256 of this file matches the active checkout exactly: `b30de22dd3cbd119f17193cb5d0aaa71bfe6677eac2ab4b6b969933dcc111408`.

RED command: `php tests/AssignmentOrderComposition/original_upload_http_flow_001_test.php` in that worktree. Inspected `/Users/antropophag/.local/state/fmonitor2-verification/original-http-20260907/canonical-fixture-red-v3-final.log`: setup succeeds, then `INTENDED_RED original submission form missing after native selection`, expected 200, actual 404. Execution belongs to root; review verified the log and exact fixture identity. The earlier `canonical-fixture-red-v3.log` class-alias/setup failure is retained as failed attempt evidence and is explicitly not accepted as Gate 2 RED.

No blocking findings. Gate 3 approval is renewed for the corrected fixture with all v2 test expectations intact. GREEN and independent code review may resume; this record does not approve production changes. Only this review record was written by the reviewer.

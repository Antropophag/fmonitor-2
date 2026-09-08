# Test review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-HTTP-001 — v4 stale intent fixture

- Reviewer: `/root/original_gate3`, independent agent; not author of this correction or production implementation.
- Test author: root implementation agent.
- Reviewed base: `051eaf0c33d257b29f3e5b99343af2e811b3c857`; bounded flow-test input correction plus previously approved v3 fixture.
- Specification: unchanged HTTP v0.3; inherited original command fingerprint/replay contract.
- Public seam: real HTTP router/session and original command, unchanged.
- Verdict: `APPROVED`.

## Findings

Re-read the normative original command fingerprint tuple and replay precedence. Document date participates in the fingerprint; request ID does not. An accepted fingerprint replays before lineage/CAS even after its correction target is no longer current. Therefore changing only request ID after the accepted September 2 correction does not construct the different intent required to observe the HTTP specification's stale-target conflict.

The single-line input correction additionally changes documentDate to `2026-09-03`, keeping the old target/expected revision. This is an independently specified, past date with a different fingerprint, so the unchanged 409/`stale_revision` expectation is now sensitive to the intended stale-lineage behavior. The later historical correction uses the current leaf and remains a distinct valid request. No domain expectation or implementation is changed; inherited semantic replay remains authoritative.

## RED evidence

Inspected isolated worktree `/Users/antropophag/code/fmonitor-original-red-20260907` at the reviewed preimplementation HEAD. Its only changes are this flow test and the v3 fixture. Flow SHA-256 matches the active checkout: `9a6c90bc03e67aa6018a060f71deba7240d519b330d9513607ebf6166f9f8e50`.

Command: `php tests/AssignmentOrderComposition/original_upload_http_flow_001_test.php`. Root execution output in `/Users/antropophag/.local/state/fmonitor2-verification/original-http-20260907/stale-intent-red-v4.log` shows successful setup followed by expected form 200 versus absent-route 404. This is intended slice RED; it does not claim the later stale assertion executes before the missing form exists. The reviewer inspected evidence and hashes rather than claiming another run.

No blocking findings in this bounded correction. Prior records are preserved. Gate 4 may resume with the corrected test; production GREEN and independent Gate 5 remain separate obligations. Only this review record was written by the reviewer.

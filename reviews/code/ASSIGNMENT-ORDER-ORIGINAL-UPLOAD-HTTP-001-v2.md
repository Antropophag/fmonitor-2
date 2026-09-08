# Code review supplement: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-HTTP-001 — browser acceptance

- Reviewer: `/root/original_gate5`, independently tasked agent; not implementation or test author.
- Reviewed production: `6daea29c2533e4b38591ae8ab0f8d90584dc1969`.
- Specification: HTTP v0.3, unchanged.
- Verdict: `APPROVED` for the scoped operator upload/correction UI, supplementing the preserved original Gate 5 record.

## Findings and closing evidence

No new blocking findings. Production after the earlier `7cb79d0` review differs only by the independently approved 113 global-call qualifications recorded in `PILOT-HTTP-AUTH-001-original-http-global-calls.md`. Eight real HTTP regression suites pass at the qualified source, and this reviewer independently reran the unchanged client test successfully.

Root reported completing actual Chrome CUA acceptance on the isolated fictional fixture at port 60575 on this source: native login, submission form prefilled from the September 6 template event, actual File/date September 1/confirmation submission, and accepted revision 1. The correction then selected the File, September 2 date and Russian reason. Temporarily moving the owned synthetic private storage root produced a real 503 and Russian retry feedback; restoring it and retrying the same unchanged form/File produced revision 2. Root inspected the final layout and visible neutral keyboard focus. These are the browser operator's observations; this reviewer does not claim to have executed those gestures or independently viewed the transient screenshots.

Independently read external `qa-original-v2-cleanup.json` and inspected the final snapshot's structure. Cleanup evidence confirms exactly one root and two revisions: September 1 and September 2, each 327 bytes with normative SHA-256 `4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784`; revision 1 has no correction reason, revision 2 retains the entered Russian reason. It also records one template event, zero process tasks, removed fixture control directory and closed port. The browser operator reports closing the task tab and fixture process with exit 0. Evidence resides under `/Users/antropophag/.local/state/fmonitor2-verification/original-http-20260907`.

The native file-picker control limitation in the first review is resolved: the operator addressed the macOS helper panel service rather than sending keys to Chrome. The earlier direct no-template acceptance remains valid; the new browser pass adds previous-template prefill, correction and actual failed-request retry. Together with the independently reviewed code and focused tests, these observations close the scoped browser correction/retry/final-layout limitation of the first review.

## Completion boundary

No required changes remain from this bounded code/browser review. The original review's full-verification uncertainty is now a known failure: the `7cb79d0` full run ended with four failing stages. This supplement does not assert `VERIFY_OK`, approve deployment, archive any parent change or close the persistent launch goal. General history/download, assigned-engineer read authority, application/opening and the remaining integration work retain their separate gates.

Earlier review and evidence records remain preserved. No production edits or commit were made by this reviewer.

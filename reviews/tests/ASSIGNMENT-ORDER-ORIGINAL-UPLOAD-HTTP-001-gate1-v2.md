# Independent Gate 1 amendment — ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-HTTP-001

- Verdict: **APPROVED**
- Reviewer: `/root/original_http_review`, separately tasked agent; not an author of the specification, tests or production code.
- Date: 2026-09-07
- Reviewed HEAD: `adcef69dd413ad1809dba4cf0545b5036e2f26ed`
- Reviewed specification: version 0.2, SHA-256 `b63881623f72f7d83e67c019ec72151e521cbf2ed7f3ac966a8a40fb633d55b1`.
- Prior record: `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-HTTP-001-gate1-v1.md`.

## Amendment and disposition

Sections 2, 3 and 6 now explicitly distinguish native framed request input from all TCP connection bytes. Exact adapter errors apply to requests reaching the adapter; a native-stream length mismatch still rejects before command invocation. Pre-PHP framing rejection is a separate observable outcome, requiring connection closure rather than timeout, a healthy server afterwards and unchanged original facts. It cannot count as a successful upload or a skipped test. This resolves the sole material finding in v1 without changing the approved domain owner or expanding the slice.

Inspected external `framing.py` and `framing-results.json` under `/Users/antropophag/.local/state/fmonitor2-verification/original-http-20260907/`. These establish the preliminary ordinary/max/over body and 16384-byte header observations and show no response for short/extra frames. The preliminary script catches timeouts together with connection resets, so it does not itself prove the newly required closed-not-timeout assertion; that assertion remains an explicit requirement for Gate 2/3 tests, not a claimed test approval here.

All unchanged scope assessments in v1 remain applicable. Gate 1 is **APPROVED** for the narrow upload/correction/form slice. This does not approve tests, implementation, parent history/download scope or integration.

## Unchanged OpenSpec artifact hashes

Paths relative to `openspec/changes/expose-assignment-order-original-upload-ui/`:

| Artifact | SHA-256 |
| --- | --- |
| proposal.md | `0c34ff35136feee2e76b62fed7184a4bea4652c12eca357b8ac8ae88297f9006` |
| design.md | `c5181beb9ff9d704a6654718d29edbace2ee6f8c5b5e3da297d3b4694c4ff33e` |
| tasks.md | `01c4828d7623190c7c36b7668f960cb9c5a8fe13d70dff3680a66663dddba890` |
| specs/pilot/assignment-order-original-upload-ui/spec.md | `32902b3228addd1b1729ce90a6f70d50bd200b1556a1415d1c33ac91b76ea845` |

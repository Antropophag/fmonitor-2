# Test review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-HTTP-001

- Reviewer: `/root/original_gate3`, separately tasked agent; did not author specification, tests or implementation.
- Test author: predecessor implementation agent, checkpoint committed by Timofey Grishin.
- Reviewed commit: `5d407ff57614e34040fccbc6e38ac7bc4545e2e8`.
- Specification: v0.3, SHA-256 `19c42e9f68e3d0bc2204d6a2482c5cce595b9ce2106c66e4de05cd03968cb8a4`; independent Gate 1 v3 APPROVED in adjacent record.
- Public seam: real loopback HTTP through `rapid-pilot/router.php`, native session, selected order and production original infrastructure; client DOM submit/input events with isolated fetch.
- Verdict: `CHANGES_REQUESTED`.

## Reviewed artifacts and RED

Read `original_upload_http_{flow,admission,bounds,prefill}_001_test.php`, `original_upload_client_001_test.mjs`, `OriginalHttpFixture.php`, and checkpoint changes to `SelectionHttpFixture.php`. Product/context, pilot contracts and delivery process were consulted. No tests or production code were changed by this reviewer.

Inspected root's rerun manifest `/Users/antropophag/.local/state/fmonitor2-verification/original-http-20260907/checkpoint-red-20260906T235955Z.json` and all five named checkpoint logs. Commands are `php tests/AssignmentOrderComposition/original_upload_http_<name>_001_test.php` for the four names above, and `node tests/Verification/original_upload_client_001_test.mjs`. PHP exits 255: flow/prefill expect form 200 but receive missing-route 404; admission reaches its enumerated missing-route failures then expects 411 but receives 404; bounds passes framing prerequisites and expects 413 but receives 404. Client exits 1 for the deliberately absent asset after valid File/DOM setup. These are intended RED failures, not broken setup. No independent rerun was needed to establish the captured failures; evidence inspection is distinguished from execution.

The external `framing-results-v2.json` characterizes ordinary/max/oversize requests reaching PHP and short/extra frames closing at EOF without response. The bounds test additionally requires healthy server and unchanged facts after each malformed frame. `fixture-regression.log` records existing selection/replay/template/native HTTP PASS after fixture changes.

## Findings

The central happy path has useful independent expectations: fixed 327-byte hash/size/date, actual selected identity without a template, exact domain envelope fields, replay equality, revision preservation, historical correction after a later pending order, and untouched non-original rows. Yesterday-template prefill is generated through the approved application with a controlled clock. Fixture grants and rows are setup/observation, not a substitute mutation seam. Private resources and synthetic database are isolated and cleaned up. Nullable header removal and write-half-close make framing assertions meaningful rather than timeouts. Native parser/selection/audit behavior can reuse its earlier approvals.

However, plausible defects in the new HTTP/UI boundary would pass the proposed suite. The following are blocking coverage gaps, not requests to retest all inherited domain internals.

## Required changes

1. **Exact authorization and context (spec sections 1, 2, 5, 6).** All positive fixtures grant upload, correct and read together. Add HTTP cases that distinguish initial upload from correction permission and require explicit read plus the current action permission for GET/HEAD form. At least one correction denial must preserve all original facts/private files before command. Exercise a revoked native session as inherited 303, and wrong object/order paths as exact context rejection with no facts. This catches an adapter that uses upload permission for every mode or resolves context by order alone. Include canonical invalid route identity coverage where it reaches the recognized routing contract.
2. **Transport rejection and precedence (sections 2, 6).** Add a noncanonical numeric Content-Length and Transfer-Encoding case, with server characterization where necessary. A frame rejected before PHP may use the approved closed-without-timeout/healthy-server/unchanged-facts evidence; do not demand adapter JSON from an unreachable adapter. At least one combined malformed metadata plus bad CSRF case must demonstrate shape-before-CSRF admission, and an unpadded/alternate canonical metadata encoding case must reject. Existing single-error cases alone do not establish ordering or full canonical encoding.
3. **Response/CSP contract (sections 3, 4).** Assert exact successful form GET/HEAD CSP and header parity/zero HEAD bytes, BASE CSP on POST results and failed forms, and `Retry-After: 60` on a real 503. Current flow checks only the presence of connect-src and absence of worker-src on GET, while the JSON helper does not inspect CSP or Retry-After. An implementation broadening POST/error CSP or dropping the required retry header would pass.
4. **Correction and client state (sections 4, 6).** Exercise missing correction reason over real HTTP and assert the native rejection envelope/unchanged revisions. Add client event cases for correction lineage/reason metadata, 200 replay redirect, and 4xx showing a safe Russian reason then issuing a new intent key on the next submission. Retain exact File/key/metadata across an unchanged 503 retry. The current client only proves initial 201 navigation and network retry; its 503 is followed by an input change, and no 4xx or correction occurs.

Capture new RED evidence before production edits and resubmit the amended tests to this independent reviewer. Real browser/visual/focus evidence remains a later integration obligation already named by the specification; the isolated DOM harness is not reported as that evidence. This review does not reopen approved domain semantics or Gate 1 v0.3.

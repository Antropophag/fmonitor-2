# Independent Gate 3 rereview — ASSIGNMENT-ORDER-COMPOSITION-HTTP-001

- Verdict: **APPROVED**
- Reviewer: `/root/composition_http_review`, separately tasked agent; did not author specification, tests, fixtures, or production implementation.
- Test author: `/root`.
- Date: 2026-09-07
- Reviewed HEAD: `2948146225fbbb9a028396d4ff2edbe1f0d1daa3`; exact worktree artifacts pinned below.
- Specification SHA-256: `d20728bc3e27c02e5797f75b4b3afe1c06f41ad6b6fd1befc1a5be28a9796597`, unchanged from approved Gate 1 v2.
- Previous review: `reviews/tests/ASSIGNMENT-ORDER-COMPOSITION-HTTP-001-http-v1.md`.
- Public seam: actual raw HTTP/router, native sessions/LocalAuth and fictional native MariaDB, existing production selection/template factories.

## Reviewed hashes

| Artifact | SHA-256 |
|---|---|
| `tests/Support/SelectionHttpFixture.php` | `3bcd9f63cd9eaf097448aea9957b478a8d5c7e4edc716f56374058dd9fc21633` |
| `tests/Support/SelectionHttpAssertions.php` | `dd24a8132d70aa255047d2fc22f09db51801bf22fa85013c600f9ad380d1624d` |
| `tests/AssignmentOrderComposition/selection_http_flow_001_test.php` | `43d9e3456345b486b40ccb16490f1ceb3cb9c2da549b8d07f353a5f261d4f037` |
| `tests/AssignmentOrderComposition/selection_http_admission_001_test.php` | `3964bd743c6f507b85ecb6c6e8f9075e685e561367eefaa80356db23cf612655` |
| `tests/AssignmentOrderComposition/selection_http_failures_001_test.php` | `c6af79308b72f8f36d0bc615815ce32060609824739f7adde78c19a3725cbf37` |

## Findings resolved

Both v1 findings are resolved. The successful selection now submits actual DOM-derived form controls after selecting the intended installer/engineer/confirmation; replay resubmits those exact bytes. The retry body is extracted from the rendered retry form, compared with every original intent field including CSRF, crew, engineer and confirmation, and that exact body is submitted after dependency restoration. Missing or changed controls can no longer be masked by reconstructing a successful request from fixture constants.

After order 83 is created, the public GET must pass the HTML 200/private-response assertions and omit the prior generation date. A failed GET cannot satisfy the absence check. Given the fixture's distinct fixed object/planning/provenance dates, this catches the reviewed case-wide predecessor-date leakage. Existing date-owner tests remain inherited.

The remaining v1 conclusions stand: traceability, independent worked identities/reasons, real native seam, isolated fixture state, negative admission cases, persistence observations and setup prerequisites are adequate for this bounded HTTP/UI slice. No material blocker remains. No previously approved domain behavior is reopened.

## Final RED verification

The reviewer reran the exact final changed entrypoints, with production still unchanged:

```text
/opt/homebrew/bin/php tests/AssignmentOrderComposition/selection_http_flow_001_test.php
/opt/homebrew/bin/php tests/AssignmentOrderComposition/selection_http_failures_001_test.php
```

Both exited 255 for the intended absent-route behavior after native fixture/session setup: flow expected 200 and received 404; `installer_required` mapping expected 422 and received 404. This is RED evidence, not a claim that downstream assertions have run GREEN. The unchanged admission and approved native/renderer prerequisites reuse the exact v1 evidence.

Evidence directory: `/Users/antropophag/.local/state/fmonitor2-verification/composition-http-20260906-2200/`.

- `selection_http_flow_001_test-final-red-v2.log`: SHA-256 `a79fa89de3fd29a7a3fff4002ce2784f49b6b36d6790fd31b4b00e4c8318e1b1`.
- `selection_http_failures_001_test-final-red-v2.log`: SHA-256 `09e409642c8b43eb4e345df48b7cffe684be0e4832418a0c9bec371581ed1e38`.

## Decision

Gate 3 is **APPROVED** for these exact artifacts. Proceed to minimal Gate 4 implementation. Relevant regression, architecture, visual/keyboard/public-shlz QA and independent Gate 5 remain required; this record makes no integration or launch claim.

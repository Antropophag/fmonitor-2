# Independent Gate 5 — ASSIGNMENT-ORDER-COMPOSITION-HTTP-001 v1

- Verdict: **CHANGES_REQUESTED**
- Reviewer: `/root/composition_http_review`, separately tasked agent; did not author specification, tests, fixtures, or production implementation.
- Review date: 2026-09-07
- Reviewed source commit: `333e6633f825b469d932a69b4fb1d9c51a043081`.
- Compared parent: `2948146225fbbb9a028396d4ff2edbe1f0d1daa3`.
- Specification SHA-256: `d20728bc3e27c02e5797f75b4b3afe1c06f41ad6b6fd1befc1a5be28a9796597`.
- Approved tests: `reviews/tests/ASSIGNMENT-ORDER-COMPOSITION-HTTP-001-http-v2.md`; checked support/flow/admission/failure test hashes remain equal to that approval.

## Material finding

**HTTP request IDs accept non-v4 UUIDs despite the explicit transport contract.**

`app/PilotHttp/FreshOrderFormInput.php:31` validates the received `requestId` through `SelectionScalar::uuid`. That approved domain helper intentionally accepts RFC4122 versions 1–5 (`[1-5]` in the version position), while section 3 of this HTTP specification requires canonical lowercase **v4**. Therefore a well-formed v1 UUID such as `11111111-1111-1111-8111-000000000001`, with otherwise valid selection input, reaches the native command and can create a selection instead of yielding transport 400. The browser-generated UUID is correctly v4, but raw HTTP is the confirmed public seam and must enforce the same admission boundary.

Required correction: add the narrow raw-HTTP non-v4 rejection regression, demonstrate RED, obtain independent Gate 3 approval, and enforce v4 in this HTTP adapter. Do not change the inherited domain UUID grammar or reopen its approval. Rerun the affected suite and obtain Gate 5 rereview of the exact final source.

## Other reviewed conclusions

No additional material blocker was found in this bounded review. The authorized read query owns domain SQL and uses one object/selection/candidate snapshot, then the existing date reader bound to that snapshot's immutable order identity. HTTP admission authenticates and checks exact authority before input decoding; transport actor fields cannot establish identity. The native selection/template owners retain mutation, recovery, history and audit responsibilities. Retry forms preserve the existing intent rather than silently allocating a new request. PDF responses are memory-only, carry safe attachment headers, and expose bytes only after the approved owner succeeds. The explicit fresh flag and enumerated denials close the addressed predecessor writers without migrating history.

The rendering uses existing PilotView/public shlz controls, escapes dynamic strings and includes native labels/fieldset/legend, explicit engineer confirmation, separate PDF action and pending replacement. Code inspection confirms POST form methods and appropriate selection/retry fields. Existing native selection/original-binding/template Gate 5 approvals are reused; those owners were not re-audited here.

## Evidence examined

External evidence directory: `/Users/antropophag/.local/state/fmonitor2-verification/composition-http-20260906-2200/`.

| Evidence | Result / SHA-256 |
|---|---|
| `selection_http_flow_001_test-green-final.log` | PASS; `f20021d7d255c200e157d4831763076b51c0d8e9071b011268b7c04a5c5db979` |
| `selection_http_admission_001_test-green.log` | PASS; `aa488e1591ae4ce11a2b2be2e6716788dc977954305cd8e77bbf9248e867a6d1` |
| `selection_http_failures_001_test-green.log` | PASS; `2c55e4bee23794d7e4e26c4ebce1483156ce5066c9e426c8246a20dbd5ef555e` |
| `architecture-v2.log` | 7 rules PASS; `e136384c4f780880dae297b69446fff60e3d8e6848597a6d20df1daf65bb9450` |
| `impeccable-final.json` | Empty findings; `37517e5f3dc66819f61f5a7bb8ace1921282415f10551d2defa5c3eb0985b570` |

`docs/operations/composition-http-green-2026-09-07.md` reports the real isolated Chrome login/selection/replacement/download/date and keyboard-focus QA, records Safari capture failure and the Chrome fallback, and preserves verification limits. Browser interaction was performed by the author; this reviewer examined the source and evidence record, not a new browser run.

## Gate decision and scope

Gate 5 is **CHANGES_REQUESTED** for the source commit above because of the single transport admission finding. Existing GREEN evidence does not cover that plausible raw-HTTP regression.

Full `make verify`/`VERIFY_OK`, native-family runner integration, clean runtime deployment, original HTTP, composition application and opening remain explicitly separate. This review introduces no additional historical migration or launch requirements and makes no completion, deployment or launch claim.

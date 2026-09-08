# Independent Gate 3 — ASSIGNMENT-ORDER-COMPOSITION-HTTP-001

- Verdict: **CHANGES_REQUESTED**
- Reviewer: `/root/composition_http_review`, separately tasked agent; did not author the reviewed tests, fixtures, specification, or production code.
- Test author: `/root`.
- Date: 2026-09-07
- Reviewed HEAD: `2948146225fbbb9a028396d4ff2edbe1f0d1daa3`; uncommitted reviewed artifacts are pinned below.
- Specification SHA-256: `d20728bc3e27c02e5797f75b4b3afe1c06f41ad6b6fd1befc1a5be28a9796597`, approved by `ASSIGNMENT-ORDER-COMPOSITION-HTTP-001-gate1-v2.md`.
- Public seam: raw HTTP to the actual rapid router, native session/LocalAuth, native fictional MariaDB, production composition/template owners.

## Exact artifacts

| Path | SHA-256 |
|---|---|
| `tests/Support/SelectionHttpFixture.php` | `3bcd9f63cd9eaf097448aea9957b478a8d5c7e4edc716f56374058dd9fc21633` |
| `tests/Support/SelectionHttpAssertions.php` | `aa64f28e8ee873d5a44aa274708c64b0d297e454c3173e2a287806ef25ee4799` |
| `tests/AssignmentOrderComposition/selection_http_flow_001_test.php` | `c54bd85d09603f500a13e627cfc7e1ce6cc10c02eeefadbd5a1078a78fbe93e5` |
| `tests/AssignmentOrderComposition/selection_http_admission_001_test.php` | `3964bd743c6f507b85ecb6c6e8f9075e685e561367eefaa80356db23cf612655` |
| `tests/AssignmentOrderComposition/selection_http_failures_001_test.php` | `0db3891448edf1e6c118b94ab548574942012b3cc156d65ce69c874707aabb5a` |

## RED and setup evidence

Evidence directory: `/Users/antropophag/.local/state/fmonitor2-verification/composition-http-20260906-2200/`.

The reviewed logs show flow failure `Expected: 200 / Actual: 404` at the new form, admission failures at the absent routes, and failure mapping `Expected: 422 / Actual: 404` for `installer_required`. The fixture first requires a successful native session write and a 303 authenticated `/pilot/login` response. `native-prerequisite.log` passes selection/replay/replacement/no-case prerequisites; `template-prerequisite.log` passes native generation and the production renderer. These are intended missing HTTP behavior failures, not broken database/session/renderer setup.

The author confirmed exact invocations use `export PATH=/opt/homebrew/bin:$PATH; php tests/AssignmentOrderComposition/<file>.php`, with prerequisite files `selection_native_tracer_001_test.php` and `template_generation_001_test.php`, followed by RED files `selection_http_flow_001_test.php`, `selection_http_admission_001_test.php`, and `selection_http_failures_001_test.php`. This reviewer inspected supplied output rather than rerunning the suites.

Log SHA-256 values:

- `native-prerequisite.log`: `3d9385c8451ee215e8d733bf94856ae4c3566c51ce3f15ee99ddac692e2caa9c`
- `template-prerequisite.log`: `8251753b9be7e38daf52b1c48e4d0088b3d5956e5b5bf722fb7898959f74bb40`
- `selection_http_flow_001_test-red.log`: `a79fa89de3fd29a7a3fff4002ce2784f49b6b36d6790fd31b4b00e4c8318e1b1`
- `selection_http_admission_001_test-red.log`: `8aa47275efd786545415d7306de6b43a8cdb788a2d535e04f4d5c9e77c1e8fd8`
- `selection_http_failures_001_test-red.log`: `09e409642c8b43eb4e345df48b7cffe684be0e4832418a0c9bec371581ed1e38`

## Findings

Traceability, native seam selection, fictional isolation, and independent identity/reason expectations are sound. Database reads observe persisted facts; only fixture preparation and fault injection use direct writes. The public original command supplies the accepted-original precondition without expanding HTTP upload scope. Prior domain approvals are reused and not re-audited. The latest admission additions cover supplied non-yes confirmation and safe CSRF logging.

Two material sensitivity gaps remain in the HTTP/UI contract:

1. **Rendered form and retry can be unusable while tests pass.** Every selection POST is rebuilt from `A::input`; the form's actual CSRF, installer, engineer, and confirmation controls are never used to submit. The retry test checks only request ID, revision, and mode, then resubmits the original pre-failure array. Missing or wrong person/confirmation fields in the generated form or retry therefore escape detection. Submit one selected form using actual rendered successful controls and submit the extracted retry form after recovery; assert the complete original intent is retained. This is the specified form-to-command behavior, not a request for a browser automation expansion.
2. **The newly approved date/identity binding lacks a regression assertion.** The test generates templates for 82, then creates 83, but never checks that the new identity has no displayed template date. A case-wide predecessor date lookup would pass. Add a public GET assertion after creating 83 proving the generation date for 82 is not attributed to 83. Keep the existing date reader and its domain tests unchanged.

## Required changes and decision

Address the two findings, rerun the affected RED entrypoints while production is unchanged, and obtain independent rereview of the exact amended tests. No additional domain re-audit, historical migration coverage, upload/application/opening behavior, or fixture redesign is requested.

Gate 3 remains **CHANGES_REQUESTED** for these exact tests. This record does not authorize Gate 4.

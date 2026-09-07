# Code review: PILOT-HTTP-AUTH-001 — unknown-route lazy admission

- Reviewer: `/root/original_gate5`, independently tasked agent; did not author implementation or reviewed assertions.
- Implementation author: root implementation agent.
- Reviewed source: `29397bd27e3af856e626908da722f474a272fe39`, production diff against `88925774f7ca805758a94930b0ea62926c0fba6b`.
- Specification: inherited PILOT-HTTP-AUTH-001 v0.12, sections 9 and 11.2.
- Approved test review: `reviews/tests/PILOT-HTTP-AUTH-001-unknown-route-admission.md`.
- Verdict: `APPROVED`.

## Findings

No blocking findings. Independently inspected the two added production lines. The coordinator now recognizes the exact union of its legacy prepare, registration/artifact, control-engineer and opening routes before reading `FMONITOR_FRESH_ORDER_FLOW`. Requests outside that union and the already-recognized fresh selection/template routes delegate immediately. Earlier original submission/form and script branches remain unchanged. The new regex does not replace the selection/template capture array used below it.

The classification matches the legacy branches already owned by this coordinator. Their flag-dependent redirect/rejection/delegation behavior remains intact, while unknown routes and unrelated downstream routes no longer incur the fresh-flow configuration read. This is the minimal correction for the approved lazy admission invariant. It introduces no writer, authorization rule, domain fact, audit change or route fallback. Built-in calls remain globally qualified.

The unchanged auth assertion calls the real production entrypoint with the declared EnvironmentSource object and expects no reads for an unknown-route 404. Its independently reviewed RED reaches that assertion after the separately approved canonical-frontier setup correction. No allowance for the fresh flag or runtime interception was added to satisfy GREEN. The separate frontier test working changes are outside this production review.

## Verification

Inspected `pilot_http_auth_001_test-green-v2.log`: HTTP boundary PASS. The prior direct invocation without the configured synthetic DB password is setup-failure evidence and is not treated as GREEN. Also inspected `auth-global-regression.log`, `selection-admission-after-lazy-fix.log`, `original-admission-after-lazy-fix.log` and `architecture-after-lazy-fix.log`: all report PASS, architecture covers seven rules. Selection admission explicitly covers legacy redirects/rejections and disabled fresh flow; original admission retains transport/local/native grant and audit separation checks. These execution logs belong to root.

Independently ran PHP lint for `FreshOrderHttpCoordinator.php`, `php tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php`, and `git diff --check 8892577 29397bd`: PASS. Evidence root is `/Users/antropophag/.local/state/fmonitor2-verification/canonical-frontier-20260907`.

## Required changes and scope

None for this bounded fix. Canonical-frontier test reconciliation, workforce CHECK amendment, full exact-source verification and launch integration remain separate work; this record does not assert full `VERIFY_OK` or deployment readiness. Only this review record was written; no production/test edits or commit were made by the reviewer.

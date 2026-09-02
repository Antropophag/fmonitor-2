# Independent code review — PILOT-ROUTE-CSP-001

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `csp_gate5_code_review`  
Verdict: **APPROVED**

The reviewer did not author or modify the executable specification, tests,
production implementation, evidence, or OpenSpec tasks. This review record is
the only edit.

## Reviewed baseline and hashes

- Worktree base commit: `9abe0c42913d0f2598e866d38b9b357327e48b13`
- Owner-approved executable spec:
  `47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef`
- `app/PilotHttp/PilotRouteCsp.php`:
  `6170ee90ca599a404650943a29ae0f16f9507911f86f15af787ab0387fcb5465`
- `app/PilotHttp/PilotHttp.php`:
  `c31ae9b11b8b2cda6d4c141ddb41b99e9421509906702017535d0318b5543635`
- `app/PilotHttp/PilotE2ECoordinator.php`:
  `5a948f15d72198b05af1be828b1b4840c9cce37f036f8fce283b334fc1be2033`
- `app/PilotHttp/checklist.js`:
  `d5cbcc912eaabed5b9ebeaa517ca5f722947d1fd4334f99bcbf86a7484872a01`
- `rapid-pilot/CompletionFlow.php`:
  `2649a3b152b6f8883fff9e5f8d9b159ee75db8ec76d48fafc7ab31239e3a48aa`
- `rapid-pilot/router.php`:
  `b8234f4d52cc0ada1c788f9997207bf6ff7ea60049c0e17b402c408393380523`
- Approved tests, respectively coordinator/inventory/login/CompletionFlow/final
  HTML: `f3e336e7c87b261b981704b9077381abbc036827fd39efdc1ef9b1296628bded`,
  `0a134eff5fc21457dca8804ab8988ac266ca239a8d97cfaae71c60ad9f1182f7`,
  `eec985d8181a590d9ff2c96aef50be56347b69737e5692b1bd217928bbf2dd75`,
  `1d35bfb8e1fa6219c17090e6f221a18409598aa679a5b7acca14ae83eafbe913`,
  `3d1d6cc64a4a3a59ab7c1e62574df453f7259407fb3f06a4cc51815782955f6a`.

The specification hash matches the owner approval. Gate 3 v3 and the bounded
post-GREEN fixture correction rereview v4 are both `APPROVED`.

## Standards axis

**APPROVED — no blocking findings.** `PilotRouteCsp` is one pure, fail-closed
route/result policy seam. The two coordinators delegate CSP selection to it and
retain their established response construction. The direct adapter installs the
same policy only at the final header boundary. Global PHP calls in the new
classifier are qualified, `git diff --check` is clean, PHP lint is green, and
`make architecture-check` passes all seven rules.

The rapid-pilot hotspot edit removes presentation injection and adds only final
header wiring; it introduces no new domain fact, state transition, persistence
owner, or runtime DDL. The existing external checklist asset owns the bounded
presentation helper. No new baseline exemption or architecture dependency was
added. No material Fowler smell was found in this narrowly scoped diff.

## Specification axis

**APPROVED — no blocking findings.** Classification is byte-exact for base,
scripted HTML, checklist HTML, and the exact Service Worker asset. It is bounded
by method, full path, positive canonical id, final `2xx` status, and media type;
errors, redirects, commands, assets, script-free/future routes, and near-misses
fail closed. Both coordinator seams additionally require the final body to
contain a same-origin external pilot script before widening the policy. The
direct router's successful allowlisted representations are exercised through
the real HTTP/login matrix; its final callback preserves existing status, body,
cache, security, asset, and operational headers.

CompletionFlow's inline block is removed. `checklist.js` exposes and invokes the
external cap helper, which preserves incomplete `85` and completed `100`
behavior. Final-HTML snapshots show no schema, ordered-row, completion-fact, or
audit mutation. Existing command responses and the completion characterization
remain green. No authorization, history, cache lifecycle, Service Worker
behavior, or completion-domain semantics moved into this slice.

## Verification evidence

Reproduced green:

```text
php tests/InstallationProcess/pilot_route_csp_inventory_001_test.php
php tests/InstallationProcess/pilot_route_csp_001_test.php
php tests/InstallationProcess/pilot_route_csp_completion_flow_001_test.php
node tests/InstallationProcess/support/pilot_route_csp_completion_browser.js app/PilotHttp/checklist.js
docker ... tests/InstallationProcess/pilot_route_csp_login_001_test.php
docker ... tests/InstallationProcess/pilot_route_csp_completion_final_html_001_test.php
make architecture-check
make lint
openspec validate define-pilot-route-csp --strict
```

All focused commands passed. The independently approved tests are sensitive to
policy widening/order, status/media/method/route mistakes, missing representation
qualification, inline execution, cap regressions, response/header drift, asset
byte drift, and persistence mutation.

`make verify` was rerun. Architecture, lint, unit, CSP focused tests,
characterization, and diff-check passed. Overall result remained
`FULL_VERIFICATION_FAILURE count=2 stages=db-test,e2e-test`: the DB stage has
the already classified canonical-v9 fixture expectations, local-RBAC fixture
admission failures, and ObjectQueue completion runtime-DDL prerequisite; E2E
has the existing missing combined assignment-order artifact. During the shared
DB sequence the real CSP login test also encountered a missing-cookie setup
after earlier mutable fixtures, while the same test passed against the canonical
fresh test DB immediately before the full run. This is suite isolation/integration
debt, not a CSP behavior failure, and no approved expectation was changed.

## Verdict

Gate 5 is **APPROVED** for the reviewed CSP production diff. This approval does
not claim full integration Done: OpenSpec task 3.4 and the slice Done tasks must
remain open until the separately owned full-verification prerequisites are
resolved or integrated with honest evidence.

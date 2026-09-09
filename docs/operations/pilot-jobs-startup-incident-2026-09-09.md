# Root pilot startup regression — 2026-09-09

Corporate stand source: `41e39498`. After `make down`, fast-forward main and
`make up`, pilot/MariaDB became healthy but workforce-sync restarted with
`{"ok":false,"error":"CONFIGURATION_INVALID"}`.

## Cause and verification gap

PR64 changed `rapid-pilot/workforce-worker.sh` into an explicit-prefix `--once`
adapter (implementation commit `116f65c4`). Root `compose.yaml` and its generator
template still invoked it without arguments, omitted the prefix, and waited for
the removed `/tmp/workforce-ready` marker. This is a deterministic caller/callee
contract mismatch, before any corporate Bitrix request.

`legacy_workforce_once_delegation_001_test.php` positively asserts that no-argument
invocation exits64. The existing worker CLI test was changed to supply `--once`
and an explicit prefix. The runtime-wiring review approved that new contract and
`deploy/runtime/compose.yaml`, without including the remaining root caller.
The retained integration report records successful isolated new-runtime Compose
smoke and full CI `34315902017` on `088b51be`. Those checks did not establish
that root `make up` remained functional. This is missing deployment-path coverage
and incomplete migration/review scope, not evidence of a corporate configuration
mistake or a falsely reported test result.

## Correction contract

See `specs/PILOT-JOBS-STARTUP-001.md`. Root deployment must use the native Jobs
worker/scheduler and health operations, with pilot manifest/private configuration
translation confined to its adapter. A root-caller regression and isolated real
Compose exercise are required. Do not treat health as proof of an external sync.

## Evidence status

Initial local reproduction: `git show origin/main:rapid-pilot/workforce-worker.sh
| sh` returned64 and `CONFIGURATION_INVALID`, matching the corporate log.
The correction adds pilot-only configuration translation and starts the native
worker and scheduler together. The new resolved-root-caller test is GREEN after
the recorded RED. Existing private Bitrix startup, historical once delegation,
workforce CLI and native Jobs runtime contract checks pass. `make architecture-check`
passed the HTTP qualification and all7 architecture rules. Independent bounded
source review is APPROVED in `reviews/code/PILOT-JOBS-STARTUP-001.md`.

Initial real root-image/root-Compose smoke passed local HTTPS delivery of51
synthetic workforce entries, native job completion, health and restart preservation.
Review then strengthened the smoke to wait for DB commit rather than transport
completion; the final exact test rerun also passed via
`python3 tests/Deployment/pilot_jobs_compose_001_test.py`. The smoke is registered in the E2E
inventory so CI exercises the root deployment as well as the separate runtime.
Full CI and installation on the corporate stand remain pending. No retained stand
or its volumes were changed; all smoke resources belong to isolated test projects.

## First authoritative CI and inventory correction

PR67 run34329817588 on832e57f9 completed with unit, both integration shards and
E2E successful. The new real root Compose smoke passed on Linux in42.331s.
Complete failure inventory: fast failed `verification_ci_001_test.py`'s ordered
E2E expectation; governance failed that same test and
`verification_inventory_001_test.py`'s historical suite membership check. Verify
correctly rejected the failed fast/governance evidence. No other job failed.

The correction adds exactly the two newly registered test names to the existing
explicit membership checks. Historical fingerprints, uniqueness and complete-list
assertions remain. Both affected governance files pass15/15 locally; `/root`
independently approved the three literal-row changes authored by the reviewer.
The complete first-run failures were inspected before pushing this correction.
Production code, new deployment regressions and their reviewed hashes are unchanged.

## Same-source E2E failures retained

Run34330765302 onbb6874e5 passed fast, governance, unit and both integration
shards. Attempt1 failed only `production_runtime_browser_001_test.php` after its
flow reached100%, because its collector recorded one unattributed `net::ERR_ABORTED`.
The collector omits URL/resource/page context, so a navigation/close cancellation
is a hypothesis rather than an established cause. The unchanged failed-job rerun
passed that browser test. No browser error assertion was suppressed.

Attempt2 instead failed only `pilot_e2e_flow_001_test.php` through
`local_rbac_objects_route_admission_001_test.php`: the numeric identity-redaction
oracle returned1. Its raw authorization log was not included in the failure output.
Inspection demonstrated a deterministic oracle defect: the legitimate12-hex opaque
correlation `abc701defabc` matches its forbidden role701 expression. Production
generates correlations with `bin2hex(random_bytes(6))`; the contract explicitly
allows12-hex opaque correlations. The correction must distinguish the exact
validated correlation field while retaining detection of actual IDs elsewhere.

The root Compose/HTTPS/restart regression passed all three CI executions. Both
failed-attempt inventories and their aggregate refusals were inspected; they are
not reported as clean full CI. Primary failed logs remain outside the repository,
with immutable attempts available in PR67's Actions history.

The numeric-oracle correction is test-only and independently approved in
`reviews/tests/LOCAL-RBAC-AUTH-CONTRACT-001.md`. Its new deterministic assertions
accept the strictly validated opaque field and retain detection of all three
fixture IDs, including the same correlation text on an untrusted additional line.
The real GET/DB admission test passes independently. Browser assertions remain
unchanged; the earlier unattributed aborted-request failure remains recorded.

# Independent Gate 5 review — checklist worker navigation

- Date: `2026-09-25`
- Reviewer: separately tasked agent `/root/final_review_checklist_sw`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T104446Z-df5f4d3640/package.json`
- Base commit: `7aa2951debde7c2c7f4b07e69fbed8d6713b038b`
- Exact candidate source: `5fb950d2d8cc36305219e3dd699c19bef72dddc7fbff6f16af311669de07171c`
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T104446Z-df5f4d3640/snapshot/source.patch`, SHA-256 `508c43e0938ef5adc9b473184dbb7ddf8bdf188eb406d241f98beba7876f1a46`
- Gate 3 record: `reviews/tests/YII2-INSPECTION-JOURNEY-001-worker-navigation.md` (`APPROVED` for the corrected RED candidate)
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the reviewed specification, OpenSpec artifacts,
tests, production worker, or Gate 3 record. This review record is the only
authored artifact.

## Finding

### 1. HIGH — the regression suite replaces the installed worker with a clean install and does not prove delivery to existing clients

Location: `tests/Yii2/inspection_browser.mjs:51-53` and
`tests/Yii2/checklist_service_worker_navigation_001_test.mjs:35-43`.

The browser flow explicitly unregisters every Service Worker and deletes every
`fmonitor2-checklist-*` cache before opening the queue. It therefore proves a
fresh registration, exact `/pilot/` scope and prefetch, but not the production
upgrade path from the currently deployed broad-navigation `v7` worker. The VM
test evaluates only the new script and never dispatches its `install` or
`activate` lifecycle events. Consequently the complete suite would remain green
if `skipWaiting()` or `clients.claim()` were removed or broken: a fresh install
would still become active, while an already-open client could remain controlled
by the old broad worker until it is closed/reloaded. Deleting the document
caches also means the browser flow cannot demonstrate that an existing user's
cached checklist remains available through the upgrade.

This is a release-blocking gap for this fix because the defect lives in an
already-active Service Worker, and the requested behavior must reach existing
clients without sacrificing their user-bound offline checklist. Add a bounded
upgrade regression that starts with the old worker controlling a client and a
cached checklist, serves the candidate worker bytes at the same script URL,
triggers the update, observes candidate activation/control for that existing
client, and verifies both ordinary navigation is no longer intercepted and the
pre-existing checklist remains available offline. A deterministic lifecycle
oracle may be used if the real-browser fixture cannot swap served worker bytes,
but it must exercise `install`/`activate`, `skipWaiting`, `clients.claim`, and
cache preservation rather than merely inspect source text. Recompute the plan
because this requires a test change, obtain the planner-required Gate 3 approval,
then return the complete candidate for Gate 5.

## Conformance assessment

Apart from the blocking upgrade-oracle gap, the implementation is minimal and
conforms to the accepted route behavior. The anchored `checklistPath` predicate
admits only the two positive-ID canonical aliases; ordinary pages, suffixes,
zero and leading-zero IDs remain browser-owned. Checklist network-first and
user-bound offline fallback behavior is unchanged. Message prefetch remains
same-origin and exact-route constrained. Logout/account-switch purge behavior,
asset handling, authorization, persistence and append-only domain history are
untouched. Keeping cache generation `v7` preserves the existing shell, active
user marker and user-bound document cache when the browser performs an in-place
script update; the worker script is served with `max-age=0`, and its changed
bytes make it updateable. Those production properties make the intended upgrade
credible, but they do not replace executable evidence that existing controlled
clients receive it.

The corrected OpenSpec delta validates strictly. No scope creep or maintainability
problem was found in the production diff. The focused route test would catch a
return to broad `/pilot/` interception and loose route matching, while the real
browser flow covers fresh registration/prefetch and the adjacent inspection
journey. It does not catch the plausible lifecycle regression described above.

## Independent verification

All bounded selected checks passed against exact source
`5fb950d2d8cc36305219e3dd699c19bef72dddc7fbff6f16af311669de07171c`:

```text
$ openspec validate limit-checklist-worker-navigation --strict
Change 'limit-checklist-worker-navigation' is valid

$ node tests/Yii2/checklist_service_worker_navigation_001_test.mjs
PASS: YII2-INSPECTION-JOURNEY-001 checklist-only worker navigation

$ php tests/Yii2/yii2_inspection_browser_001_test.php
PASS: YII2-INSPECTION-JOURNEY-001 browser ...

$ python3 tests/Deployment/pilot_jobs_compose_001_test.py
PASS: PILOT-JOBS-STARTUP-001 isolated root Compose delivery and restart

$ python3 tests/Verification/change_verification_001_test.py
Ran 59 tests ... OK

$ python3 tests/Verification/architecture_guard_001_test.py
Ran 18 tests ... OK

$ git diff --check
exit=0
```

No full local suite was run. Mandatory exact-source CI remains `UNKNOWN` and is
not treated as GREEN. Gate 5 is **CHANGES_REQUESTED** with one open HIGH finding.

---

## Gate 5 correction rereview — existing-client upgrade coverage — 2026-09-25

- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T105535Z-8f5901689a/package.json`
- Exact candidate source: `24b937120b0e3ef6a37309df27ce313dfde950f18b6cb0e77bfc4fadb188251b`
- Executable source: `887fc3eddbfebd0289304d4aaddc42dd0383e2b0d49c4e45e74ad06db7a8b407`
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T105535Z-8f5901689a/snapshot/source.patch`, SHA-256 `010fdb169118a01cc745b69d8b026f11b351cf14b7397bcbb8bf14d893c83dde`
- Restarted Gate 3: `APPROVED` for exact source `a8a39c5b7ef5ca46829a25e72444ee6d790ac24f5d5764e5b56e519f87c9510c`
- Reviewer independence: unchanged; this reviewer authored no reviewed production code, specification, test, OpenSpec artifact, or Gate 3 approval.
- Verdict: **APPROVED**

### Prior-finding disposition

1. **Existing-client upgrade oracle — fixed.** The normative contract and
   OpenSpec now state the upgrade behavior explicitly. The worker test dispatches
   the real candidate's `install` and `activate` listeners and requires one
   `skipWaiting()` call, one `clients.claim()` call, preservation of a seeded
   current-generation `v7-17` user checklist, and deletion of the seeded stale
   `v6-17` cache before checking the route matrix. Removing or breaking either
   lifecycle call or current-generation cache preservation now fails the test.
   The real-browser flow no longer unregisters the worker; it clears only
   document caches, retains the `/pilot/` registration, and observes queue-driven
   repopulation of the eligible `v7-73` checklist document. The earlier HIGH
   finding is closed.

### Complete conformance decision

The production correction remains the same minimal anchored-route change. Only
the two canonical positive-ID checklist aliases call `respondWith`; ordinary
pilot pages and near-boundary suffix/ID cases remain browser-owned. Both aliases
retain network-first behavior and user-bound offline fallback. Queue registration,
same-origin exact-route prefetch, logout/account-switch isolation, asset handling,
authorization, persistence and append-only domain history remain intact.

The unchanged `v7` generation deliberately retains current user documents during
the byte-detected worker update, while activation deletes prior generations.
`skipWaiting()` and `clients.claim()` provide the specified transition for open
clients. The revised regression would catch plausible route, lifecycle and cache
preservation failures. No new scope creep, security issue, standards violation,
or maintainability blocker was found. All prior Gate 3 findings and the prior
Gate 5 finding are fixed; no open finding remains.

### Independent verification

```text
$ openspec validate limit-checklist-worker-navigation --strict
Change 'limit-checklist-worker-navigation' is valid

$ node tests/Yii2/checklist_service_worker_navigation_001_test.mjs
PASS: YII2-INSPECTION-JOURNEY-001 checklist-only worker navigation

$ php tests/Yii2/yii2_inspection_browser_001_test.php
PASS: YII2-INSPECTION-JOURNEY-001 browser ...

$ python3 tests/Deployment/pilot_jobs_compose_001_test.py
PASS: PILOT-JOBS-STARTUP-001 isolated root Compose delivery and restart

$ python3 tests/Verification/change_verification_001_test.py
Ran 59 tests ... OK

$ python3 tests/Verification/architecture_guard_001_test.py
Ran 18 tests ... OK

$ git diff --check
exit=0
```

No full local suite was run. Gate 5 is **APPROVED** for exact source
`24b937120b0e3ef6a37309df27ce313dfde950f18b6cb0e77bfc4fadb188251b`.
Mandatory exact-source CI remains `UNKNOWN`; this approval does not represent CI
GREEN, publication readiness, deployment, or merge authorization.

---

## Supplemental Gate 5 — CI runtime digest correction — 2026-09-25

- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T112059Z-6847b3def5/package.json`
- Base commit: `34224304705b1b051d799bf5c8ac10b27c53d12e`
- Exact candidate source: `bc71c63a80ef2e2bd8fa3c76b390444c4aacee33077a08e5847c907a4078ba19`
- Executable source: `7fd68a2d014c3499e46dcb15f0b8a68730129c5285ba6f17d68968332448d357`
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T112059Z-6847b3def5/snapshot/source.patch`, SHA-256 `ad12e02e8ba1d263a2ba2d2c8909ff8b67a35c9de907256b153e80651bc08822`
- Supplemental Gate 3: `APPROVED` for exact source `9706e7eb23451b25f179b3d647e5506f6c1f078c4064afb90a312cc3f40be8e6`
- Reviewer independence: unchanged; this reviewer authored neither the worker,
  digest correction, verification mapping, runtime oracle, Gate 3 approval nor
  CI result.
- Verdict: **APPROVED**

### Complete failed-CI inventory

GitHub Quality Graph run `36126802220` on exact commit
`34224304705b1b051d799bf5c8ac10b27c53d12e` completed `failure`.
The complete job inventory is:

- `plan`, `fast`, `unit`, `e2e`, `governance`, `Integration (2/2)` and
  `quality-results`: `success`;
- `harness`: expected `skipped`;
- `Integration (1/2)`: `failure`, with exactly one `REGRESSION_FAILURE` in
  `tests/Runtime/yii2_production_web_cutover_001_test.php`;
- `verify`: dependent aggregate failure because integration was not successful;
- terminal `Quality Graph`: dependent failure status.

The sole regression expected worker SHA-256 `e0555252…` and observed
`be191f76…`; status, cache policy, media type, `nosniff`, same-origin policy and
the remaining runtime matrix matched. The integration category reported 168
tests and exactly one failure. Inspection of the complete failed-job logs found
no second `REGRESSION_FAILURE` or concealed product failure.

### Correction review

Independent hashing of `app/YiiRuntime/Assets/checklist-sw.js` yields exactly
`be191f7623fe90b10572a09be39bc9e52f0058ba14780119ec1fba3401bc784f`,
the corrected literal in
`tests/Support/yii2_production_web_cutover_contract.php`. The production worker,
runtime test and serving code are unchanged from the previously approved
candidate. The correction changes only stale expected data and maps its helper
plus the existing public runtime consumer in the verification input.

The oracle remains independent and substantive: it fetches the asset through
the Yii runtime, hashes the served bytes, and continues to verify status, media
type, `public, max-age=0`, `nosniff`, same-origin resource policy,
`Service-Worker-Allowed: /pilot/`, runtime ownership, absence of rapid-pilot
loading, and unchanged protected facts. No assertion was removed or relaxed.
The new mapping ensures this deployment-boundary consumer is selected alongside
the lifecycle/route and browser regressions. No behavior, permission, history,
offline, route or cache semantic changed in this CI correction.

### Independent focused verification

```text
$ sha256sum app/YiiRuntime/Assets/checklist-sw.js
be191f7623fe90b10572a09be39bc9e52f0058ba14780119ec1fba3401bc784f

$ php tests/Runtime/yii2_production_web_cutover_001_test.php
PASS: YII2-PRODUCTION-WEB-CUTOVER-001 single runtime

$ node tests/Yii2/checklist_service_worker_navigation_001_test.mjs
PASS: YII2-INSPECTION-JOURNEY-001 checklist-only worker navigation

$ php tests/Yii2/yii2_inspection_browser_001_test.php
PASS: YII2-INSPECTION-JOURNEY-001 browser ...

$ python3 tests/Deployment/pilot_jobs_compose_001_test.py
PASS: PILOT-JOBS-STARTUP-001 isolated root Compose delivery and restart

$ python3 tests/Verification/change_verification_001_test.py
Ran 59 tests ... OK

$ php tests/Runtime/runtime_storage_001_test.php
PASS: PRODUCTION-HTTP-RUNTIME-001 storage prepare/replay/readiness contract

$ python3 tests/Verification/architecture_guard_001_test.py
Ran 18 tests ... OK

$ git diff --check
exit=0
```

No full local suite was run. Supplemental Gate 5 is **APPROVED** for exact source
`bc71c63a80ef2e2bd8fa3c76b390444c4aacee33077a08e5847c907a4078ba19`.
The historical failed run remains failed evidence. A fresh exact-source CI run
is required; this verdict is not CI GREEN, merge authorization or deployment.

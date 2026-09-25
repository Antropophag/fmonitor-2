# Independent Gate 3 review — checklist worker navigation

- Date: `2026-09-25`
- Reviewer: separately tasked agent `/root/gate3_checklist_sw`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T103440Z-4932c759ef/package.json`
- Base commit: `7aa2951debde7c2c7f4b07e69fbed8d6713b038b`
- Exact candidate source: `21fb37e8fc5ec87cc95e3cdac0adef58aeb05e50257ef04a720ef4d625fcc651`
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T103440Z-4932c759ef/snapshot/source.patch`, SHA-256 `d04157dba69f18ea0aec21773892f9a9471533bd19ed2a33a05986bfaef05907`
- Public seam: active Yii checklist Service Worker `fetch` event
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the reviewed specification, OpenSpec artifacts,
test, production worker, suite registration, or captured RED evidence. This
review record is the only authored artifact.

## Findings

### 1. HIGH — the OpenSpec delta is structurally invalid

`openspec validate limit-checklist-worker-navigation --strict` rejects
`specs/ui/checklist-offline-navigation/spec.md` because the added requirement
`Queue prefetch remains available` has no `#### Scenario:` block. The invalid
lifecycle artifact cannot describe a reviewable accepted change. Add an
observable scenario for queue registration and eligible checklist prefetch,
then rerun strict validation.

### 2. HIGH — the executable matrix does not prove the exact route boundary

The normative amendment says the worker controls navigation *exclusively* for
the two canonical checklist route shapes. The test proves several ordinary
pilot pages remain browser-owned, but it has no near-boundary negative such as
`/pilot/objects/4512/checklist/operations`, a trailing segment, or a
non-canonical object ID. An implementation based on a loose `checklist`
substring/prefix can therefore pass while continuing to intercept a navigation
outside both canonical documents. Add representative near-boundary navigation
rejections for both aliases so the exact route shape, rather than only the
general distinction between ordinary pages and checklists, is executable.

### 3. HIGH — the restated queue registration/prefetch promise is not mapped to an executable oracle

The normative amendment and OpenSpec both promise that the construction-control
queue continues to register the `/pilot/`-scoped worker and sends
`CACHE_CHECKLIST` for eligible links. The new test loads only
`checklist-sw.js`; it never executes `control-queue.js`, observes registration
scope, or observes the prefetch messages. The package's acceptance mapping
contains only this test, and the searched existing checks do not provide an
exact active-Yii queue registration/prefetch oracle for this change. Extend the
mapped test (or map an existing/new focused public-seam test) to prove the queue
promise without relying on production source text.

## RED reproduction

The prepared evidence record exists at
`/private/tmp/fm2-checklist-worker-red-evidence.json` and is bound to candidate
source `21fb37e8fc5ec87cc95e3cdac0adef58aeb05e50257ef04a720ef4d625fcc651`.
Independent reproduction reached the intended production defect, not setup
failure:

```text
$ node tests/Yii2/checklist_service_worker_navigation_001_test.mjs
AssertionError [ERR_ASSERTION]: /pilot/ must remain browser-owned
true !== false
exit=1
```

The test directly evaluates the active production worker asset in an isolated
VM, its expected values follow the normative route contract, and the failure is
deterministic. These strengths do not close the missing acceptance branches.

Strict lifecycle validation independently failed as follows:

```text
$ openspec validate limit-checklist-worker-navigation --strict
Change 'limit-checklist-worker-navigation' has issues
ERROR: ADDED "Queue prefetch remains available" must include at least one scenario
exit=1
```

`git diff --check` passed. No local full suite was run. CI, implementation,
deployment, and enforcement remain `UNKNOWN` and are not treated as GREEN.

## Exact reviewed hashes

```text
089b31368ffae1c8ef45df685e650d509516b28df4d21a88eb68e1ea2c8f06ed  specs/YII2-INSPECTION-JOURNEY-001.md
ebde345058875d26097a96b239a1f807553ee34beb7f8a354718e4cebd688ecf  openspec/changes/limit-checklist-worker-navigation/proposal.md
243e1b58aa3f32142a7a0f693eb778f8f4ebd3373f54a15e18262a77aff7b572  openspec/changes/limit-checklist-worker-navigation/design.md
bb963ecc0d5038412f08f41188910e622cdccf1334005fff9a8a457f7040c6d6  openspec/changes/limit-checklist-worker-navigation/specs/ui/checklist-offline-navigation/spec.md
5761b4298ee1615c73d7e19aced413e6117dd0f73460ca8bd42732ca093b15e0  openspec/changes/limit-checklist-worker-navigation/tasks.md
5e4d0b37790361695f92ce11d27d8c3a0d75a3ec750a33bd31559517e57cfd44  openspec/changes/limit-checklist-worker-navigation/verification-input.json
d8c4e0913a98276735956ed1362b3b49f37ac201661631b5b8df1f4918d59821  tests/Yii2/checklist_service_worker_navigation_001_test.mjs
86a94774a36eaeceb7312d3000952ce8c3722e2a1b5e036b4f2cb033e392de01  tools/verification/suites.tsv
```

Gate 3 is **CHANGES_REQUESTED**. Gate 4 remains blocked until the lifecycle
artifact validates, the exact-route negatives and queue prefetch behavior are
executable, fresh source-bound RED evidence is captured, and the complete
candidate receives a new independent Gate 3 review.

---

## Gate 3 correction rereview — 2026-09-25

- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T103822Z-7e9f2728c9/package.json`
- Exact candidate source: `78ff3f047f0f9704172f0ee3e6c318cc85d8444dc29642004b14536aff1f05a6`
- Executable source: `22718a444a73f11349727a4a32588ecd14396e147533d62cbe5f307858a0dc0f`
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T103822Z-7e9f2728c9/snapshot/source.patch`, SHA-256 `52d9c25ec0dc77cfbe3d9007eb215b65a236bd17ae4234f1c7f147bafc9eedb5`
- Reviewer independence: unchanged; this reviewer authored none of the corrected specification, OpenSpec, tests, production worker, or evidence.
- Verdict: **APPROVED**

### Prior-finding disposition

1. **OpenSpec validity — fixed.** `Queue prefetch remains available` now has
   an observable queue scenario. Independent strict validation passes.
2. **Exact route boundary — fixed.** The worker test now rejects operations and
   history suffixes plus zero and leading-zero IDs for both aliases. These cases
   make a loose checklist substring/prefix implementation fail while retaining
   the two exact positive aliases.
3. **Queue registration/prefetch oracle — fixed.** The mapped real-browser flow
   clears prior worker/cache state, opens the active construction-control queue,
   observes an exact-origin `/pilot/` registration, and requires the eligible
   checklist document in the user-bound `v7-73` document cache. This executes
   the active Yii queue and worker rather than inspecting source text.

### Independent verification

```text
$ openspec validate limit-checklist-worker-navigation --strict
Change 'limit-checklist-worker-navigation' is valid
exit=0

$ node tests/Yii2/checklist_service_worker_navigation_001_test.mjs
AssertionError [ERR_ASSERTION]: /pilot/ must remain browser-owned
true !== false
exit=1 (INTENDED_RED)

$ php tests/Yii2/yii2_inspection_browser_001_test.php
PASS: YII2-INSPECTION-JOURNEY-001 browser /var/folders/yc/548th18156s39y3kx0xc05tc0000gn/T/yii-preopening-732ef96866fd
exit=0

$ git diff --check
exit=0
```

The refreshed external evidence records agree with those results and are bound
to candidate source `78ff3f047f0f9704172f0ee3e6c318cc85d8444dc29642004b14536aff1f05a6`:

- `/private/tmp/fm2-checklist-worker-red-evidence.json`: `INTENDED_RED`, exit 1.
- `/private/tmp/fm2-checklist-worker-browser-green-evidence.json`: `GREEN`, exit 0.

The RED fails at the intended missing routing correction after successful worker
evaluation, not at setup. The browser check independently preserves the adjacent
registration, cache identity, offline/replay, authorization, logout/account
isolation, and queue behavior. Expected route and scope values come from the
normative contract. No full local suite was run; CI, implementation and final
review remain outside this Gate 3 verdict.

### Corrected reviewed hashes

```text
089b31368ffae1c8ef45df685e650d509516b28df4d21a88eb68e1ea2c8f06ed  specs/YII2-INSPECTION-JOURNEY-001.md
ebde345058875d26097a96b239a1f807553ee34beb7f8a354718e4cebd688ecf  openspec/changes/limit-checklist-worker-navigation/proposal.md
243e1b58aa3f32142a7a0f693eb778f8f4ebd3373f54a15e18262a77aff7b572  openspec/changes/limit-checklist-worker-navigation/design.md
685a9aa35c6c34f75622cffc32147eb14e0727151aedf4068c3d8fe19193bc0f  openspec/changes/limit-checklist-worker-navigation/specs/ui/checklist-offline-navigation/spec.md
5761b4298ee1615c73d7e19aced413e6117dd0f73460ca8bd42732ca093b15e0  openspec/changes/limit-checklist-worker-navigation/tasks.md
691e343f537724e06167d9e541c6c80807b5b030845fea65f66c964f1ffe21d7  openspec/changes/limit-checklist-worker-navigation/verification-input.json
a775dd5939d763e500ea8ba7fd8c0b87d0d23797d47085af28dcd1d11ea2adf5  tests/Yii2/checklist_service_worker_navigation_001_test.mjs
c79b9622af95a038f9276e90c925fb06e5adbb6d361bf4b9fa1314b699a67629  tests/Yii2/inspection_browser.mjs
86a94774a36eaeceb7312d3000952ce8c3722e2a1b5e036b4f2cb033e392de01  tools/verification/suites.tsv
```

No blocking finding remains. Gate 3 is **APPROVED** for exact source
`78ff3f047f0f9704172f0ee3e6c318cc85d8444dc29642004b14536aff1f05a6`.
Gate 4 may implement the minimal routing correction without changing the
approved expectations; any later spec/test change requires recomputation and
the planner-selected review cycle.

---

## Restarted Gate 3 review — existing-client upgrade coverage — 2026-09-25

- Trigger: Gate 5 found the previously approved test matrix did not prove that
  already installed clients receive the corrected worker without losing the
  current user-bound checklist cache.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T105006Z-4dbfb260e7/package.json`
- Exact candidate source: `a8a39c5b7ef5ca46829a25e72444ee6d790ac24f5d5764e5b56e519f87c9510c`
- Executable source: `1cc51463975a0f5698b0e8cfb173be4d1a6154cb86e13c88ac17dcb51ce55d8e`
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T105006Z-4dbfb260e7/snapshot/source.patch`, SHA-256 `c1b954d339d30a13d67d9ec9be3543e97f3ea0aea4362cd51fd0c737fefc74cd`
- Reviewer independence: unchanged; this reviewer authored no reviewed contract,
  OpenSpec artifact, test, production worker, Gate 5 finding, or evidence.
- Verdict: **APPROVED**

### Review

The normative contract and OpenSpec now explicitly require in-place activation
for existing tabs, client claiming, preservation of current-generation
user-bound checklist documents, and removal of stale-generation caches. The
worker-seam test independently executes both lifecycle listeners before its
route matrix. It requires exactly one `skipWaiting` call during install, exactly
one `clients.claim` call during activation, byte-present `v7-17` cached content
after activation, and deletion of the seeded `v6-17` cache. The expectations
are observable at the Service Worker lifecycle seam and do not depend on the
planned route implementation.

The real-browser correction no longer unregisters the installed worker. It
clears only checklist document caches, navigates the existing client to the
construction-control queue, and requires the retained `/pilot/` registration
to repopulate the exact eligible document in the current user's `v7-73` cache.
Thus the GREEN no longer obtains a false fresh-install path by removing the
worker under review.

All previously approved exact-route positives/negatives, ordinary browser-owned
navigations, offline fallback, queue prefetch, user/cache isolation, and
deterministic expected values remain intact. No blocking finding remains.

### Independent verification

```text
$ openspec validate limit-checklist-worker-navigation --strict
Change 'limit-checklist-worker-navigation' is valid
exit=0

$ node tests/Yii2/checklist_service_worker_navigation_001_test.mjs
AssertionError [ERR_ASSERTION]: /pilot/ must remain browser-owned
true !== false
exit=1 (INTENDED_RED; install/activate upgrade assertions passed first)

$ php tests/Yii2/yii2_inspection_browser_001_test.php
PASS: YII2-INSPECTION-JOURNEY-001 browser /var/folders/yc/548th18156s39y3kx0xc05tc0000gn/T/yii-preopening-8212e3f43556
exit=0

$ git diff --check
exit=0
```

The refreshed external evidence records are bound to candidate source
`a8a39c5b7ef5ca46829a25e72444ee6d790ac24f5d5764e5b56e519f87c9510c`.
The RED record explicitly reports that lifecycle assertions passed before the
intended route failure; the browser record reports GREEN. No local full suite
was run. CI and final implementation review remain `UNKNOWN` and outside this
Gate 3 verdict.

### Restarted reviewed hashes

```text
c2cec7098208ab34c71b1f4256a46d38f7cdee22872a7873c850f363a64af0ad  specs/YII2-INSPECTION-JOURNEY-001.md
ebde345058875d26097a96b239a1f807553ee34beb7f8a354718e4cebd688ecf  openspec/changes/limit-checklist-worker-navigation/proposal.md
243e1b58aa3f32142a7a0f693eb778f8f4ebd3373f54a15e18262a77aff7b572  openspec/changes/limit-checklist-worker-navigation/design.md
7556ac0f81ff92669e5a6ebf80b5cc3555e295fe64d1340facbee4c981f0484e  openspec/changes/limit-checklist-worker-navigation/specs/ui/checklist-offline-navigation/spec.md
5761b4298ee1615c73d7e19aced413e6117dd0f73460ca8bd42732ca093b15e0  openspec/changes/limit-checklist-worker-navigation/tasks.md
691e343f537724e06167d9e541c6c80807b5b030845fea65f66c964f1ffe21d7  openspec/changes/limit-checklist-worker-navigation/verification-input.json
6637ca4200b3f4d4838b123cd64096871d1c92add72365ad8b4ba6ebaf2891af  tests/Yii2/checklist_service_worker_navigation_001_test.mjs
5575a7a890b0434af297a7f44fea11620feb74547c279d7a329c29a9ab362210  tests/Yii2/inspection_browser.mjs
86a94774a36eaeceb7312d3000952ce8c3722e2a1b5e036b4f2cb033e392de01  tools/verification/suites.tsv
```

Restarted Gate 3 is **APPROVED** for exact source
`a8a39c5b7ef5ca46829a25e72444ee6d790ac24f5d5764e5b56e519f87c9510c`.
Gate 4 may proceed against these expectations. Any later specification or test
change requires plan recomputation and the planner-selected review cycle.

# Code review: CHANGE-VERIFICATION-PLACEMENT-181

- Reviewer: independent Gate 5 agent `/root/issue181_gate5`
- Authors: root authored scope, specification, lifecycle artifacts and tests; separate executor authored the implementation
- Reviewed source: base `e245ba1c173cc09f9183380228a7532c8dc942d2` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T202129Z-ee08f922d6/snapshot`; snapshot manifest SHA-256 `99c1ec012ae2ec459f41a6716dc9072b69ced2b01537c80f5c518e28f65c0933`; patch SHA-256 `d60dac17f31447774d7a96490b9c87f449114c75984a6f66cb0aa02d3b55113c`; candidate source `7173b67e3d57b5ece845c7aa8f03f39e59be6b08a69c7b369c4dca823f50bff8`
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T202129Z-ee08f922d6/package.json`; plan SHA-256 `50923e31421d0c7006965c83ecf6d4b9032bc1e6592684df62d3c789d5192f31`
- Specification: `specs/CHANGE-VERIFICATION-PLACEMENT-181.md`
- Gate 3: `reviews/tests/CHANGE-VERIFICATION-PLACEMENT-181.md`, final rereview verdict `APPROVED`
- Verdict: `APPROVED`

## Findings

None.

## Review assessment

The implementation confines the placement change to the existing planner, focused runner and reviewer-package seams. Semantic-integration-closure-only commands are retained in the canonical command list and exposed as CI obligations, while the focused runner skips them. Any acceptance, regression/changed-test, direct boundary or known-consumer reason promotes the same argv to local execution; reasons are canonicalized and the command remains deduplicated. The shipped-#187 reconstruction proves the requested reduction from 279 to 4 local commands while preserving the exact ordered previous CI argv inventory.

The package route remains fail closed for missing local acceptance evidence and invalid ownership, policy or verifier inventory. Its current #181 plan has no semantic escalation and therefore retains the pre-existing command schema, while still exposing `make test` under `ci_obligations`; the supplied local evidence covers both focused obligations and does not fabricate CI evidence. The existing CI selection and aggregate are unchanged, and tests retain non-success for missing, skipped, failed and cancelled mandatory integration work, including the local-GREEN/CI-failure boundary.

The diff does not alter FAST classification, review selection, registries, admission state, product code or CI aggregate behavior. The inventory addition only registers the new governance regression. The earlier Gate 3 findings are covered by the final 21-case executable matrix.

## Verification evidence

- Prepared exact-source evidence: `python3 tests/Verification/change_verification_placement_181_test.py` — `GREEN`, retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789676430566674000-6b24003988764a5bb2dc45afeff45ba8.json`.
- Prepared exact-source evidence: `python3 tests/Verification/change_verification_001_test.py` — `GREEN`, retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789676456767173000-13467ecfa5e7485aafd30057158d47cf.json`.
- Independent reviewer rerun: placement regression — 21 tests, all passed.
- Independent reviewer rerun: inherited planner regression — 18 tests, all passed.
- `git diff --check e245ba1c173cc09f9183380228a7532c8dc942d2` — passed.
- Python compilation of the changed planner, package consumer and acceptance test — passed.

The required full exact-source CI obligation is `make test`. It is intentionally pending and has no fabricated local result. This Gate 5 approval covers the reviewed candidate source; publication readiness still requires the planned exact-source GitHub CI to finish GREEN.

---

## Gate 5 correction review — CI run 35270615451

- Reviewer: independent Gate 5 agent `/root/issue181_gate5`
- Correction author: separate executor
- Corrected source: base `e245ba1c173cc09f9183380228a7532c8dc942d2` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T204611Z-c8f4a079ab/snapshot`; snapshot manifest SHA-256 `4bfbfb85f4dc4b15d51f13633772272edcd3d3ae16ec52609d88ed514565bcf2`; patch SHA-256 `9451ab6c45363d2cdc698c42057f90fa8ac34bfd143ecb5caffbc5fe2c4052a9`; candidate source `b335a56b650909ed18ae8849cae663388b87ac3058719b4def9df808dcad64c4`
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T204611Z-c8f4a079ab/package.json`; plan SHA-256 `64f24bd62d4767f7aae13192aba355d2e92a50c9877bc91a067a57190719e441`
- Verdict: `APPROVED`

### CI failure inventory and diagnosis

Exact-source run `35270615451` failed in the primary `governance` job because `delivery_harness_ci_completeness_001_test.py` observed that deduplication retained an earlier rationale for `verification_ci_001_test.py` instead of upgrading it to `generated consumer obligation`. The harness-specific command filter consequently dropped that required generated consumer. The `verify` and `Quality Graph` failures correctly aggregated the primary governance failure; the supplied complete inventory reports every other job passed. This is a planner deduplication regression, not a failure in CI selection or aggregation.

### Correction assessment

The correction changes only `add()`'s duplicate-command handling. A generated source/consumer rationale now upgrades the legacy single `rationale` field even when the plan is untyped, which restores the existing harness consumer filter. Typed plans still update `purpose`, and semantic-placement plans retain their complete sorted `rationales` list rather than replacing it. Local precedence, once-only argv deduplication, CI placement, #187 command composition, package visibility and the full-CI aggregate remain unchanged.

No additional finding was introduced by the correction. The delta is limited to the diagnosed failure and remains within the approved #181 scope.

### Correction verification

- Prepared exact-source focused evidence: `python3 tests/Verification/change_verification_placement_181_test.py` — `GREEN`, record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789677912528475000-577344cb0c2f4bf4ae64efab59454f1c.json`.
- Prepared exact-source focused evidence: `python3 tests/Verification/change_verification_001_test.py` — `GREEN`, record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789677940171130000-c4fd04953b574ba3befc05e4941d778c.json`.
- Independent reviewer rerun: `python3 tests/Verification/delivery_harness_ci_completeness_001_test.py` — 17 tests, all passed.
- Independent reviewer rerun: `python3 tests/Verification/change_verification_placement_181_test.py` — 21 tests, all passed.
- `git diff --check HEAD` and Python compilation of the corrected planner — passed.

This correction approval supersedes the earlier Gate 5 source identity while preserving its findings disposition. A new exact-source CI run is still required; the failed run is retained as failure evidence and is not GREEN by implication.

---

## Gate 5 delta review — acceptance/generated-consumer preservation

- Reviewer: independent Gate 5 agent `/root/issue181_gate5`
- Test author: root; implementation correction author: separate executor
- Reviewed source: base `e245ba1c173cc09f9183380228a7532c8dc942d2` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T213247Z-b7f6351e6f/snapshot`; snapshot manifest SHA-256 `f8f090f10191093165f5eeb350d050bed2c14929f73f48d1e8e89cec9c7fba65`; patch SHA-256 `f06628ae567590ea6abc007b76a3364f6622481ae281dfbb13f913a6ca26a4b5`; candidate source `d65f1d8d0a8b9f4468fe432c02b62cabd4a5436f3e7ea6960e670143bee09925`
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T213247Z-b7f6351e6f/package.json`; plan SHA-256 `4ce267f39e18c49a69607fcdb3359b43986a388f4403c9ba49a13828caa4a241`
- Gate 3 delta reviews: v5 and fixture correction v6 in `reviews/tests/CHANGE-VERIFICATION-PLACEMENT-181.md`, both `APPROVED`
- Verdict: `APPROVED`

### Findings

None.

### Delta assessment

The root-authored regression isolates a registered acceptance test selected independently as a generated consumer by using a separate committed generated artifact. It covers both the ordinary non-semantic/untyped plan and a real semantic-escalation variant, then exercises the reviewer package with current evidence and proves that omission of that evidence remains rejected. Gate 3 v5/v6 correctly identify the earlier changed-test overlap and approve the corrected independent fixture.

The planner correction accumulates selection reasons for every duplicate argv without replacing the primary legacy `rationale`. In the non-semantic untyped case the one command therefore preserves `rationale: acceptance mapping` and adds sorted `rationales: [acceptance mapping, generated consumer obligation]`. `_mapped_commands`, `_gate_expectations` and acceptance lookup continue to recognize the mapping, so reviewer evidence remains mandatory. In typed plans a later generated reason cannot replace `purpose: acceptance`; in semantic plans local execution still wins and both reasons remain visible.

This also restores the generated-consumer reason used by harness completeness filtering without dropping acceptance identity. Changed-registered-test selection is not suppressed: the existing changed-test/consumer regression passes, and the current package itself shows the acceptance command retaining both acceptance and changed-test reasons. No FAST, registry, CI aggregate, product or admission scope changed.

The pinned shipped-#187 comparison remains 279 focused commands before versus 4 local commands after, with the exact ordered prior CI argv equal to the new `ci_obligations` inventory.

### Verification evidence

- Prepared exact-source placement evidence — `GREEN`: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789680645844591000-94161d48442a401699b35a44e99eccca.json`.
- Prepared generated-completeness suite — 17/17 `GREEN`: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789680677378431000-bb4183f02d594a8390a1bcb33c31ba83.json`.
- Prepared exact-source planner evidence — `GREEN`: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789680734192034000-82ef938d76c946629419bbe8f10823ef.json`.
- Independent reviewer run of the new ordinary/semantic case, changed-test preservation case and #187 comparison — 3/3 passed.
- `git diff --check b8e070f3f8b39aaf1cd94dbf9ba1bb55b009b50a` and Python compilation of the changed planner/test — passed.

This verdict supersedes the prior correction source identity. The currently recorded successful CI run is associated with Git head `b8e070f3f8b39aaf1cd94dbf9ba1bb55b009b50a`; it does not by itself validate the uncommitted candidate snapshot `d65f1d8d...`. Exact-source CI for the final committed candidate remains required before PR readiness.

---

## Gate 5 delta review — production runtime Compose port collision

- Reviewer: independent Gate 5 agent `/root/issue181_gate5`
- Correction author: root/executor delivery session; changed artifact is test infrastructure only
- Reviewed source: base `e245ba1c173cc09f9183380228a7532c8dc942d2` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T215751Z-525a5ee0dd/snapshot`; snapshot manifest SHA-256 `76c89ba9906e784d44f61deeaecce8b1e4ff510e7fcc01e0ee8db305d83b63f5`; patch SHA-256 `505c70a0f5f37de5d7adad5c9b5287043a64649195ee370b64ebdb7683b53409`; candidate source `6ed644d69eab649e9c4e7b32a7c3aedb5a5489c39febebd741a7c0ddc866816a`
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T215751Z-525a5ee0dd/package.json`; plan SHA-256 `df35a3d6f8f0b994f263015c74ae68510f1e7abf04fb293e5464d605e51fcdad`
- Verdict: `APPROVED`

### Findings

None.

### CI failure inventory and delta assessment

Exact-source run `35277418237` had one primary failure: the e2e `production_runtime_compose_001_test.php` selected random host port `23306`, which was already occupied when Docker attempted to publish the web service. `verify` only aggregated that mandatory e2e failure; the supplied complete inventory reports every other job GREEN. The failure is isolated to test host-port allocation, not product/runtime behavior.

The delta changes only `tests/Runtime/production_runtime_compose_001_test.php`. It replaces blind `random_int(20000, 40000)` selection with an OS-assigned loopback ephemeral port obtained by binding `127.0.0.1:0`. One allocation supplies valid initial Compose configuration; a fresh allocation immediately before `web` publication narrows the release-to-Docker-bind interval after the long image/database/migration setup.

Closing the probe socket before Docker binds necessarily leaves a small time-of-check/time-of-use race, but the correction materially removes the observed deterministic collision class and minimizes the remaining window without changing production Compose configuration. Parallel test isolation remains provided by the cryptographically unique Compose project, image and credentials. If publication still loses the residual race, the existing `finally` path runs project-scoped `down --volumes --remove-orphans`, removes the uniquely named image and deletes the temporary nginx fixture; the probe socket itself is closed on both successful allocations and the helper fails explicitly if allocation or address parsing fails.

The refreshed port and trusted-host value are updated together before startup, so subsequent health, Host-header, restart and outage assertions target the actually published port. No product code, runtime manifest, planner behavior, #181 placement semantics or acceptance expectation changed.

### Verification evidence

The prepared exact-source reviewer package contains all four planner-selected focused obligations as GREEN for source `6ed644d69eab...`:

- `python3 tests/Verification/change_verification_placement_181_test.py` — record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789682090137956000-82d97fceed8c4712ae8bad9dfede694f.json`.
- `php tests/Runtime/production_runtime_compose_001_test.php` — full real Compose lifecycle GREEN, record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789682124578466000-bfbdaf0e6ade4aa0a5e6f6b799f30757.json`.
- `python3 tests/Deployment/pilot_jobs_compose_001_test.py` — e2e boundary GREEN, record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789682183561045000-c22c5146aba44395a6c7caff4f28322e.json`.
- `python3 tests/Verification/change_verification_001_test.py` — record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789682234974243000-3f5b81331e924333ad837e0bef8e74a0.json`.
- Independent reviewer checks: PHP syntax and `git diff --check HEAD` passed.

This approval supersedes the preceding Gate 5 source identity. Run one new exact-source full CI on the final committed candidate; the failed run remains failure evidence and is not converted to GREEN by this focused result.

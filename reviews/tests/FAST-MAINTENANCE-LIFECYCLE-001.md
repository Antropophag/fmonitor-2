# Gate 3 review — FAST-MAINTENANCE-LIFECYCLE-001

- Reviewer: independent agent `/root/gate3_review`; not an author of the specification, OpenSpec artifacts, test, RED evidence, or planned implementation.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T003700Z-f047098f0c/package.json`.
- Reviewed exact candidate source: `d8c724f14da8dbf0e8439a6e172d46d50dc4fea9918e51d5b9650802bdda3327`; executable source `f232f796710006dd5983f934013b453ab5365df1c1aca02a98264f287f1aa843`; base `fa1dfa8dba9b20eeaa1c072c5c351acada29b615`.
- Snapshot patch SHA-256: `ecd8d69f523a84cb0bcd1ef3c94ef21ba4736c2125594ffc2a1e462b9a553068`.
- Verification plan SHA-256: `d465ab5bd49d4922704f4ae39a689a7f23f03599e72fca7b923407bee6cd6862`; lane `CRITICAL`, required reviews `gate3` and `final`.
- Normative contract SHA-256: `23af9f2e5c3e78784600e8f9a8507ddf0158e77b569ad950df95ec297a25ab3e`.
- RED record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789519006109238000-465cdf3a77c241cba80dd696da49663c.json`; exit `1`, source drift `false`, package outcome `INTENDED_RED`.
- OpenSpec strict validation: GREEN.
- Verdict: `CHANGES_REQUESTED`.

## Findings

1. **CRITICAL — retained RED is a setup/parser failure, not the intended routing failure.** Locations: `tests/Delivery/fast_maintenance_lifecycle_162_test.py:102-119`; retained RED stdout/stderr for record `1789519006109238000-465cdf3a77c241cba80dd696da49663c`. Every lifecycle-declaration case stops at `SETUP_FAILURE: malformed change input`; the legacy case stops at `SETUP_FAILURE: missing verification directory: tests/AssignmentOrderComposition`. No public prepare invocation reaches a lifecycle assertion. The retained output therefore cannot establish that A–L/N are sensitive to absent routing, freshness, or admission behavior, and the record's `INTENDED_RED` label does not change the observed cause. Correct the fixture/input boundary so setup and legacy prepare succeed, retain a fresh exact-source run whose sole failures identify missing T06 behavior, and rebuild the package.

2. **HIGH — B, C, exact-source CI admission, and stale apply are not executable cases.** Locations: `specs/FAST-MAINTENANCE-LIFECYCLE-001.md:47-59`; `tests/Delivery/fast_maintenance_lifecycle_162_test.py:117-145`. The A/B/C method checks only initial `PENDING`/`UNKNOWN` fields and `required_reviews=["final"]`; it never attempts implementation admission without intended RED, never attempts PR-ready without independent final approval, and never tests mismatched/UNKNOWN CI. Case D calls `state` only, while the contract explicitly requires `prepare/state/apply` to reject a stale package. An implementation that merely serializes the expected initial dictionary and allows every prohibited continuation would pass. Exercise the existing public admission/apply/closeout seams and require each prohibited transition to fail for the specified reason.

3. **HIGH — the claimed historical FAST replay is not shown to be planner-eligible FAST under authoritative T05.1 policy.** Locations: `openspec/changes/minimal-fast-maintenance-lifecycle/design.md:8-15`; `tests/Delivery/fast_maintenance_lifecycle_162_test.py:184-213`; `docs/operations/issue-150-main-navigation-delivery.md:1-17`. The measurement labels #151/#160 as a presentation candidate but counts artifacts from issue #150 / PR #151, whose delivered change introduced a shared permission-aware navigation renderer and five integrations. The replay never invokes the current planner for those historical paths, and the current T05.1 class is deliberately bounded. Consequently it does not prove the measured candidate is one to which T06 could apply. Select an actual historical candidate that the authoritative current policy classifies FAST, or create a faithful replay of its paths and prove planner FAST before reporting before/after lifecycle counts.

4. **HIGH — case M's quality comparison is tautological rather than evidence-backed.** Locations: `tests/Delivery/fast_maintenance_lifecycle_162_test.py:197-212`; `specs/FAST-MAINTENANCE-LIFECYCLE-001.md:76-82`. The four preservation claims are hard-coded `True`; they are not derived from the before/after artifact sets or a prepared FAST package. The after set also reuses historical OpenSpec-local verification input and old review/delivery files, so it does not demonstrate the proposed compact record or canonical digest traceability. A routing implementation could drop regression, final-review, CI, or requirement bindings and this measurement would still pass. Derive each quality result from inspected artifacts/package fields and validate that the before/after sets represent the same behavior and guarantees; keep token telemetry `UNKNOWN`.

5. **MEDIUM — case N does not test explicit OpenSpec tooling.** Locations: `specs/FAST-MAINTENANCE-LIFECYCLE-001.md:61-66`; `tests/Delivery/fast_maintenance_lifecycle_162_test.py:215-219`. It only omits the lifecycle declaration and asks `harness.py prepare` for a normal route. It never invokes `openspec status`, `openspec validate`, or the explicit proposal workflow, so an implementation that breaks the required existing OpenSpec route can pass. Add a bounded executable witness using the real OpenSpec tooling for a change requiring normal lifecycle.

6. **MEDIUM — negative escalation assertions collapse distinct obligations into one generic reason and omit declaration-level fail-closed sensitivity.** Locations: `tests/Delivery/fast_maintenance_lifecycle_162_test.py:147-169`; `specs/FAST-MAINTENANCE-LIFECYCLE-001.md:31-45`. E–J all expect only `planner_not_fast`; the suite does not separately prove that `semantic_change=true`, owner-decision markers, missing/invalid intent fields, or a regression not mapped to acceptance prevent the shortcut when the planner remains FAST. This permits a route implementation that keys solely on lane plus file existence and ignores the typed maintenance assertions. Add FAST-planner fixtures for the closed declaration failures and require stable distinct reasons; retain sensitive/non-FAST planner cases as authority/precedence witnesses.

## Assessment

The specification and OpenSpec artifacts correctly preserve planner authority, prohibit classifier expansion, identify the public harness seam, keep STANDARD/CRITICAL review policy, and describe deterministic digest freshness without introducing a second registry. The plan correctly escalates T06 itself to CRITICAL because it changes delivery/admission policy. Those strengths do not compensate for a RED run that never leaves setup or for missing behavioral admission and measurement witnesses.

CI, PR, deployment, and enforcement remain `UNKNOWN`/not configured and are not treated as GREEN or approval. Gate 4 implementation is blocked pending one corrected complete A–N suite, fresh exact-source intended-RED evidence, a rebuilt prepared package, and independent Gate 3 rereview.

## Verdict

`CHANGES_REQUESTED`

---

## Correction rereview — package `20260916T004300Z-57e6b7539e`

- Corrected candidate source: `7e7cf854c9de39fcb2f79a4e3519092531a5bcb19207f723a217bbf8ad2057a3`; executable source `69984188ca05de5e520c967235adf4f7ec8eb2ce7b8978a8814c448dcee9263e`.
- Verification plan SHA-256: `d6cb5828eea70290fbb33214ddad4a480946959f2be08565741b8278a5d01c05`; lane remains `CRITICAL`, required reviews remain `gate3` and `final`.
- Corrected RED record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789519362463153000-e277613f3e48497d83b38ac22fffafd9.json`; exit `1`, source drift `false`, outcome `INTENDED_RED`.
- Scope: disposition of the six findings above and regressions introduced by their correction. Reviewer independence is unchanged.

### Prior-finding disposition

1. **Resolved.** The fixture now contains the verification directories required by public prepare and deliberately adds only the not-yet-shipped opaque `lifecycle` input allowance to its copied planner. Public `harness.py prepare` succeeds and the behavioral cases fail on the absent `package["lifecycle"]`, not setup. A separate shipped-source assertion fails specifically because the real planner allowlist does not yet accept `lifecycle`. The corrected retained run therefore identifies absent T06 output/schema rather than malformed fixture or missing directory setup.

2. **Partially resolved; remaining HIGH finding below.** Executor admission without intended RED and executor continuation of a stale active binding are now executable public-harness assertions with specific diagnostics. Missing-final-review and mismatched/UNKNOWN-CI admission remain untested.

3. **Not resolved; remaining HIGH finding below.** The correction proves the shipped #160 owner/oracle registration and proves the synthetic replay package selects FAST. It does not prove that the historical artifact set being measured represents a historical maintenance change that the current authoritative planner would classify FAST.

4. **Resolved as to tautological booleans.** Quality fields are now derived from prepared lifecycle/package fields and concrete artifact bytes rather than hard-coded `True`. The validity of the chosen historical before/after population remains coupled to finding 3.

5. **Resolved.** Case N now invokes real `openspec status --json` and strict validation and checks successful spec-driven operation in addition to legacy harness routing.

6. **Resolved.** FAST-planner declaration cases separately require stable fail-closed reasons for semantic change, owner decision, missing intent, empty issue, unmapped regression, and invalid canonical path. Sensitive/non-FAST planner cases remain separate authority witnesses.

### Remaining findings

1. **HIGH — final-review and exact-source-CI admission are still represented only by initial metadata.** Locations: `tests/Delivery/fast_maintenance_lifecycle_162_test.py:134-154`; `specs/FAST-MAINTENANCE-LIFECYCLE-001.md:52-59`. The corrected suite exercises no-RED executor rejection, but it still never attempts PR-ready disposition with final review missing/non-approved, CI `UNKNOWN`, CI non-GREEN, or CI bound to a different source. Assertions that initial fields are `PENDING`/`UNKNOWN` do not prove the required admission behavior. An implementation could serialize those initial values and later admit PR-ready without either guarantee. Add public closeout/admission attempts for missing final approval and each invalid CI condition, require specific rejection, and include one fully matching evidence fixture that may advance while remaining non-publication test data.

2. **HIGH — case M measures the lifecycle artifacts of classifier issue #160, not a demonstrated historical FAST-maintenance candidate.** Locations: `tests/Delivery/fast_maintenance_lifecycle_162_test.py:224-271`; `docs/operations/issue-160-fast-server-rendered-presentation-delivery.md`; `openspec/changes/fast-bounded-server-rendered-presentation/`. The `before` list is the OpenSpec/spec/test/reviews/delivery set used to introduce T05.1's verification-policy classifier itself. That change is a CRITICAL admission-policy change and cannot be routed through T06. Checking that current shipped policy owns `feedback-confirmation.php`, then preparing a different synthetic `card.php` change, does not make #160's artifact set the before-state of the same maintenance change. Thus the reported 10-to-3 reduction compares unlike scopes and cannot satisfy the requested historical replay. Use a real historical presentation-maintenance change (or a faithful fixture of one), run its actual changed paths through current authoritative planning to prove FAST, and compare that same change's old mandatory artifacts with its T06 set. Derive the final-review bytes/reference from that same replay rather than reusing #160's classifier review.

### Correction verdict

`CHANGES_REQUESTED`

The corrected RED and four of the six correction areas are accepted. Gate 4 remains blocked on the two bounded HIGH findings above. Submit one fresh exact-source package and RED after correcting both; any normative or executable-test change requires another independent Gate 3 rereview. CI, PR, deployment, and enforcement remain `UNKNOWN`/not configured and are not treated as approval or GREEN.

---

## Third Gate 3 rereview — package `20260916T004622Z-8d5cbf73c8`

- Exact candidate source: `1f00257a7c28ab8f75cd5e92685b15a2f653315fbebe6db28fd7c0a94fcf26f0`; executable source `719f7204a9c58a83cf11037c5a0b184abf2c8fd2a7b3828e4a29f8c100524e42`.
- Verification plan SHA-256: `d2687b9735eec373d151e4460ab885d884173f6ae5cf5dcb14f87300714fe26c`; lane `CRITICAL`, required reviews `gate3` and `final`.
- RED record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789519565791049000-98806efb443a41c8a237899bbb079267.json`; exit `1`, source drift `false`, outcome `INTENDED_RED`.
- Scope: the two remaining findings from the correction rereview and any regression introduced by their changes.

### Prior-finding disposition

1. **Substantively corrected, but test sensitivity remains incomplete.** The suite now calls public `harness.py prepare-merge` and supplies missing-final, pending-CI, and wrong-current-head observations. Missing final and wrong head require their semantic rejection reasons. The remaining sensitivity defect is below.

2. **Resolved.** Case M no longer counts #160's classifier artifacts as the old lifecycle. It first pins the shipped T05.1 owner/oracle, obtains an authoritative FAST plan, then constructs before/after bytes for one identical issue/requirement/regression identity. The existing regression is reused rather than counted as a newly created artifact; the before proxy contains the former duplicated lifecycle documents, while after contains the compact record and final review. Quality claims are derived from the prepared package, and token telemetry remains `UNKNOWN`. This is clearly a disposable proxy rather than a claim about measured token savings.

### Remaining finding

1. **HIGH — the new prepare-merge matrix lacks a valid positive control and the pending-CI assertion is non-specific.** Locations: `tests/Delivery/fast_maintenance_lifecycle_162_test.py:212-255`. The test constructs `healthy` but never calls `admitted(healthy)` to require exit zero and `merge_ready=true`. Therefore every negative observation may carry an unrelated baseline admission defect. This is particularly visible for `unknown_ci`: it requires only nonzero, `merge_ready=false`, and a status other than `SUCCESS`; any unrelated rejection or malformed otherwise-healthy fixture satisfies it. Even the two cases with expected reason membership do not prove those are the sole material differences from an admissible observation. First assert that the unmodified observation passes public `prepare-merge` and binds the expected exact source; then mutate one dimension per case and require the specific final-review, pending/non-GREEN CI, and head/source mismatch diagnostics. Include a completed non-GREEN CI mutation as required by the normative “not GREEN” condition, unless the pending diagnostic is intentionally defined to cover it and that shared behavior is asserted explicitly.

### Third rereview verdict

`CHANGES_REQUESTED`

The historical measurement correction is accepted. Gate 4 remains blocked only on the bounded prepare-merge sensitivity correction above. Capture a fresh exact-source RED/package after adding the positive control and specific CI rejection assertions, then return for independent rereview. CI and publication remain `UNKNOWN`; this decision authorizes neither merge nor deployment.

---

## Final correction rereview — package `20260916T004801Z-6d6443e7bb`

- Exact candidate source: `f672a6bae933c7674342a949d492193e2b8d00900083ddee908cd877e2b79446`; executable source `5ff13daae520f8af4c30bdb83624a022ea748a7e8859c20a7afb59376c9d13d7`.
- Verification plan SHA-256: `b57c29565b5ef0b429377afd9feef206b73864f61e046503248813179a2cdb56`; lane `CRITICAL`, required reviews `gate3` and `final` for T06 itself.
- RED record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789519665375938000-429349e1afa6445da751a3c94d3cb1e5.json`; exit `1`, source drift `false`, outcome `INTENDED_RED`.

### Prior-finding disposition

The prior sensitivity finding is resolved. The unmodified observation must now pass public `prepare-merge`, report `merge_ready=true`, and report CI `SUCCESS`. Each subsequent observation changes one dimension and requires the corresponding semantic result: missing final review, pending plan job, completed failed plan job, or changed exact head. The pending and failure cases assert exact CI status and exact reason, so unrelated rejection cannot satisfy them.

### New complete-candidate finding

1. **HIGH — the positive FAST admission fixture includes Gate 3 and therefore does not prove the “one final review only” guarantee.** Locations: `tests/Delivery/fast_maintenance_lifecycle_162_test.py:225-241`; `specs/FAST-MAINTENANCE-LIFECYCLE-001.md:49-55`. The prepared FAST plan correctly asserts `required_reviews=["final"]`, but the supposedly healthy merge observation supplies approved reviews for both gates 3 and 5. It can therefore pass even if public admission still incorrectly requires Gate 3 for a FAST maintenance change. This is precisely the lifecycle reduction T06 must preserve: one independent final review, not Gate 3 plus final. Build the healthy FAST observation with only the repository's final-review gate representation and require it to be merge-ready; then remove that sole review for the missing-final case. If `prepare-merge` cannot currently express planner-selected review requirements, the implementation contract/test must connect the prepared plan/lifecycle binding to admission rather than silently satisfying an extra gate.

### Final correction verdict

`CHANGES_REQUESTED`

The requested positive-control and CI diagnostics correction is accepted, and no other new findings were identified. Gate 4 remains blocked on the single review-cardinality witness above. CI, PR, deployment, and enforcement remain `UNKNOWN`/not configured and are not treated as GREEN or approval.

---

## Scope correction and controlling Gate 3 verdict

The last finding is withdrawn after reconciling it with the explicit T06 scope boundary and repository constitution.

`prepare-merge` belongs to the existing #107 admission policy. Its current Gate 3 + Gate 5 contract is intentionally unchanged, live enforcement remains `UNKNOWN`, and T06 explicitly prohibits changing verification/admission policy or implementing #107. Requiring that generic command to admit a final-only observation would therefore force an out-of-scope policy change. The test's two-review positive observation and one-field rejection cases are valid unchanged-admission regressions; they are not the T06 proof of FAST review cardinality.

The in-scope evidence is sufficient at the existing package/state seam:

- the authoritative planner plan selects `FAST` and exactly `required_reviews=["final"]`;
- the compact FAST lifecycle requires `final_review` and initializes it to `PENDING`;
- exact-source CI is required and initialized to `UNKNOWN`;
- disposition remains `IN_PROGRESS`, never `PR_READY`, while that evidence is absent;
- the executable no-RED and stale-binding cases block continuation through prepared executor routing;
- existing generic admission regressions remain unchanged and continue to fail closed without being redefined by T06.

This proves that T06 neither removes the final-review/CI guarantees nor falsely turns missing evidence into approval. Actual final approval and exact-source CI are later delivery evidence, not behavior that T06 may synthesize or enforce by changing #107. The generic `prepare-merge` block may remain as a no-regression witness, but it is not interpreted as requiring Gate 3 for a planner-selected FAST maintenance package.

### Findings

None.

### Final controlling verdict

`APPROVED`

Gate 3 approves implementation against exact candidate source `f672a6bae933c7674342a949d492193e2b8d00900083ddee908cd877e2b79446` and executable source `5ff13daae520f8af4c30bdb83624a022ea748a7e8859c20a7afb59376c9d13d7`. This approval is limited to T06 lifecycle routing and compact evidence projection. It does not authorize a #107/admission-policy change, merge, deployment, settings change, or treating `UNKNOWN` as GREEN. Any later normative expectation or executable-test change requires renewed Gate 3 review.

---

## Gate 5 correction test-delta review — package `20260916T010314Z-db67146c47`

- Exact candidate source: `03b576de0292fa0441a19580f9a090d9644ad411961b1598bacb717d216c46c0`; executable source `91a0f5a229c4c9695126aa75461fdbd368fe73f880cda454930696614016fae6`.
- Verification plan SHA-256: `717a8ba2632f6e4b4571fda16baebd1ecead8732a0e9b0411efb9748f66450d5`; lane remains `CRITICAL`, required reviews `gate3` and `final` for T06.
- RED record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789520570098602000-18b2c71c008c4752822e6014a63302fb.json`; exit `1`, source drift `false`, outcome `INTENDED_RED`.
- Scope: root-authored test corrections requested by Gate 5: exact regression-evidence identity, parent-symlink containment at prepare/state, and complete historical before/after evidence accounting. No production implementation was authored or changed by this reviewer.

### Delta assessment

No findings.

The intended RED is exact and sensitive. Nine established groups pass. The only two failures are the newly required behaviors: executor preparation incorrectly accepts intended-RED evidence produced by the other mapped test instead of the lifecycle-declared executable regression, and prepare incorrectly admits a canonical requirement reached through a parent-directory symlink outside the checkout. There are no setup failures, unrelated failures, or source drift.

The RED-identity fixture is independent enough to detect the defect. Both tests are valid mapped acceptance tests, both produce retained `INTENDED_RED`, and only the declaration selects the allowed executable regression. The positive evidence from the declared oracle is admitted first; changing only the evidence command to the other mapped oracle must then be rejected with the declared-regression diagnostic. A generic “any mapped RED” implementation cannot pass.

The containment cases cover both entry and freshness boundaries. Prepare receives a lexically repository-relative path whose parent symlink resolves outside and must route `OPENSPEC_REQUIRED` with `canonical_requirement_invalid`. Separately, a valid in-repository requirement is prepared, its parent directory is replaced by an outside symlink, and public `state` must mark the active binding `STALE`. The temporary repository and outside directory are fixture-owned, deterministic, and cleaned up; no host repository path is mutated.

Historical measurement now reports both total participating evidence and mandatory newly created lifecycle artifacts. Before is 10 artifacts / 9 created; after is 4 artifacts / 2 created because the existing executable regression is reused on both sides. Byte and character totals are calculated from the complete fixture bytes, including duplicated canonical prose in the old lifecycle and the concrete reused feedback regression, compact input, final-review proxy, and prepared lifecycle record. Review dispatches remain `2 -> 1`, phase stops `1 -> 0`, quality guarantees derive from the package, and token telemetry remains `UNKNOWN`. This resolves the Gate 5 completeness concern without claiming actual token savings.

The delta does not expand the FAST classifier, alter STANDARD/CRITICAL lifecycle, redefine #107 admission, create a registry, or touch product code. Existing OpenSpec, semantic/sensitive escalation, no-RED, stale-digest, independent-final-review, CI, and legacy-route witnesses remain intact.

### Delta verdict

`APPROVED`

Gate 3 approves the root test delta for exact candidate source `03b576de0292fa0441a19580f9a090d9644ad411961b1598bacb717d216c46c0` and executable source `91a0f5a229c4c9695126aa75461fdbd368fe73f880cda454930696614016fae6`. The executor may implement only the two exposed behaviors against this reviewed matrix. Any later normative expectation or executable-test change requires renewed independent Gate 3 review. CI/publication remain `UNKNOWN`; no merge, deployment, or settings action is authorized.

---

## Exact-source CI portability correction — Gate 3 delta review

- Trigger: exact-source GitHub CI run `35043123501` reported the sole primary `REGRESSION_FAILURE` in `tests/Delivery/fast_maintenance_lifecycle_162_test.py`; its 14 assertions observed runtime-created `tools/delivery/__pycache__` as a changed `delivery-policy` path in the disposable repository.
- Reviewed delta: only `tests/Delivery/fast_maintenance_lifecycle_162_test.py:98-101`; test SHA-256 `0d429aec4f0ab797449b1661a9df19edf82a3454c1cce0feea3b25c2c6c82e62` over checkout HEAD `69b9e55d4a27d9816dd320ef9d565bb96785df64` plus this uncommitted test-only edit.
- Reviewer rerun: `python3 tests/Delivery/fast_maintenance_lifecycle_162_test.py` — 11/11 GREEN; historical measurement remains byte-for-byte `10/9 created -> 4/2 created`, reviews `2 -> 1`, stops `1 -> 0`, token telemetry `UNKNOWN`.
- Verdict: `APPROVED`.

### Assessment

No findings.

The correction is fixture-local and targets only Python interpreter byproducts. `__pycache__/` and `*.py[cod]` are conventional generated bytecode patterns; ignoring them prevents environment-dependent runtime imports from contaminating the disposable repository's authoritative changed-path set. The root repository already ignores `__pycache__/`, and other verification fixtures use the same boundary, so the correction aligns the isolated fixture with repository behavior rather than weakening production planning.

The patterns do not mask source `.py` changes or any non-bytecode file under `tools/delivery`. A real harness, policy, test, spec, OpenSpec, or other delivery-policy source mutation remains visible to Git and the planner. The ignore file is committed into the fixture before its base commit, so it does not itself become an unplanned candidate path. No planner/classifier, harness implementation, normative contract, lifecycle route, #107 admission behavior, product code, or historical measurement logic changes.

The local rerun exercises all A–N groups, including exact declared-RED identity and parent-symlink containment, and is GREEN. This is focused correction evidence, not replacement exact-source CI. Current harness state still reports CI `FAILURE`, publication/merge false, and enforcement unconfigured; none is treated as approval or GREEN. A corrected exact-source CI run remains required.

### Delta verdict

`APPROVED`

Gate 3 approves this exact test-only portability delta. Any further normative or executable-test change requires renewed independent review. This verdict authorizes neither merge, deployment, settings change, nor publication before corrected exact-source CI.

---

## Second exact-source CI portability correction — Gate 3 review

- Trigger: exact-source run `35044404987`; plan, fast, unit, e2e, both integration shards, and quality-results GREEN; governance's sole primary `REGRESSION_FAILURE` was case N raising `FileNotFoundError` because the runner lacks the external `openspec` executable; verify failed only as aggregate consequence.
- Reviewed delta: conditional external CLI execution plus an unconditional artifact-set assertion in `tests/Delivery/fast_maintenance_lifecycle_162_test.py:394-412`.
- Local evidence: `openspec validate minimal-fast-maintenance-lifecycle --strict` GREEN; focused suite rerun reaches 10 passing groups and fails case N on its new artifact assertion.
- Verdict: `CHANGES_REQUIRED`.

### Finding

1. **HIGH — the unconditional delta asserts the wrong delta-spec path and fails in every environment.** Location: `tests/Delivery/fast_maintenance_lifecycle_162_test.py:402`. The test expects `openspec/changes/minimal-fast-maintenance-lifecycle/specs/fast-maintenance-lifecycle/spec.md`, but the actual complete artifact is `openspec/changes/minimal-fast-maintenance-lifecycle/specs/delivery/fast-maintenance-lifecycle/spec.md`. The reviewer reran the suite with `openspec` available; case N fails before the availability branch with that exact false assertion. Correct the repository-relative path and rerun the complete focused suite before preparing the CI correction.

### Remaining assessment

The portability strategy itself is sound. Case N continues to require the public legacy input to route `OPENSPEC_REQUIRED` with its exact reason. Unconditional checks for proposal, design, tasks, verification input, and the correct delta spec preserve the presence of the existing OpenSpec workflow on runners without the optional CLI. When the executable is available, real status/schema and strict validation remain mandatory. Returning only after the complete artifact set is established does not weaken STANDARD/CRITICAL routing, generate OpenSpec automatically, or claim strict-validation evidence where the dependency is absent.

No other finding was identified in this bounded delta. CI and publication remain non-GREEN; no merge, deployment, or settings action is authorized.

### Delta verdict

`CHANGES_REQUIRED`

Fix only the incorrect delta-spec path, capture a GREEN focused rerun, and return the test-only correction for independent rereview.

### Immediate corrected-path rereview — controlling verdict

The draft finding is resolved before completion of this review. The current diff asserts the actual delta-spec path `openspec/changes/minimal-fast-maintenance-lifecycle/specs/delivery/fast-maintenance-lifecycle/spec.md`.

Reviewer evidence against the corrected test SHA-256 `ec33210189d506dda5dbfd794412bce5c4bcf33d5530be83d55f761272aadd81`:

- `python3 tests/Delivery/fast_maintenance_lifecycle_162_test.py` — 11/11 GREEN;
- `openspec validate minimal-fast-maintenance-lifecycle --strict` — GREEN;
- historical measurement unchanged and complete.

No findings remain. The availability branch skips only invocation of an absent external executable after public normal routing and all five required OpenSpec artifacts are proven present. Environments with the CLI continue to require real `status` schema and strict validation.

`APPROVED`

This is the controlling verdict for the second CI portability correction. Corrected exact-source CI remains required; merge, deployment, settings changes, and publication are not authorized by this review.

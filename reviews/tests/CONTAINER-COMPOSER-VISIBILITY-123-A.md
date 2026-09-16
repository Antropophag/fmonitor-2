# Test review: CONTAINER-COMPOSER-VISIBILITY-123-A

- Reviewer: independent `gpt-5.6-sol / low` Gate 3 agent (`/root/issue123_gate3`); authored neither specification nor tests.
- Test author: primary/root Codex session, as recorded in `docs/operations/issue-123-slice-a-delivery.md`.
- Reviewed source: commits `65c53a18` + `1a8d7c32` (`HEAD` `1a8d7c32cf332bb808dc7534164d33734f23e53d`); exact candidate source `c4acf14743d0b1fbcf45990a99650e0322078f1ebac25d9d678c27866c59c8f4`.
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T022859Z-fe96dc3f80/package.json`; package plan SHA-256 `4239958a0643c2ec110b0ea3f32362a16662ad52f0b273ebcaadda2c70e29522`; context manifest SHA-256 `d9e477d3f5b619614288c3d3c6731d04335a4e5952b3a0649d284975ecb62a69`.
- Reconstructible source: package snapshot base `1a8d7c32cf332bb808dc7534164d33734f23e53d`, empty patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`.
- Agreed review scope: initial Gate 3 review of the complete slice A candidate; normative specification, public seam, A–J traceability, test sensitivity/independence/determinism/isolation, and captured RED validity. No prior findings.
- Specification: `specs/CONTAINER-COMPOSER-VISIBILITY-123-A.md` (package-bound SHA-256 `7e3e942516631e4bbd076af7839daed669577ee27770ac804e0ce53883f32daa`).
- Public seam: `tools/delivery/run-in-profile <profile> <command> [args...]`.
- Planner: `CRITICAL`; required reviews `gate3`, `final`; required categories `governance`, `integration`.
- Verdict: `CHANGES_REQUESTED`.

## Evidence reviewed

- `python3 tests/Verification/container_composer_visibility_123_a_test.py` — exit `1`, recorded `INTENDED_RED` for exact candidate source in `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789525655563874000-192e74c947514cbfac8cb31a0ba0fa3f.json`. A/C/G-H fail through the public seam for the expected pre-implementation visibility/host-masking gap, but F fails in fixture setup before invoking the seam.
- `php tests/Verification/quality_graph_ci_setup_001_test.php` — exit `0`, `QUALITY-GRAPH-CI-SETUP-001 PASSED`, recorded for the same exact candidate source in `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789525675970521000-96b5cecc4a114db59093a3ac28a13563.json`.
- No full local `make test` or `make verify` was run. The reviewer performed source/evidence inspection only.

## Acceptance and quality assessment

- A, B, D, I, J: mapped by `test_a_b_d_i_j_two_clean_worktrees_candidate_origin_and_warm_repeat`; two disposable detached worktrees, candidate marker origin, dependency/autoloader origin, read-only dependency view, tracked digest, absence of host `vendor/`, warm repeat and compact timing evidence are asserted.
- C: mapped by `test_c_stale_host_vendor_is_masked`; the hostile host fixture makes accidental host use observable.
- E: mapped by `test_e_changed_lock_never_silently_reuses_old_dependency_identity`; a disposable changed lock must not reach Yii behavior using the old identity. Either a matching rebuild or a pre-behavior failure remains permitted by the specification.
- F: intended mapping exists, with hostile host fallback material, but is not executable as submitted; see blocking finding F1.
- G and H: mapped across the existing governance, integration and browser profiles through the same public Yii bootstrap seam.
- Expected values are independently derived from the normative paths/origins, marker values, exit behavior and compact evidence contract rather than planned implementation internals. Tests use disposable worktrees and Docker images, check Docker availability explicitly, avoid production systems, and preserve cleanup/isolation. The only determinism/setup defect found is F1.
- The normative specification is observable at the confirmed public seam and adequately states actor, preconditions, successful outcome, prohibited host state, lock identity rejection, fail-closed behavior, profile compatibility and tracked-input invariants for this tooling-only slice.

## Findings

### F1 — Blocking — acceptance F is a setup failure, not a public-seam RED

- Location: `tests/Verification/container_composer_visibility_123_a_test.py:183`; actual recipe at `tools/delivery/Dockerfile.focused-checks:44`.
- The fixture searches for `RUN composer install --working-dir=/workspace --no-interaction --no-scripts`, while the bound Dockerfile installs with `--working-dir=/opt/fmonitor`. Therefore `re.sub` makes no change and line 189 raises `SETUP_FAILURE: dependency install seam not found` before `bootstrap(root)` is called.
- The captured full RED confirms this exact failure. Consequently acceptance F (missing/corrupt container dependency must fail closed through canonical `run-in-profile`, without host fallback) has no valid executable RED, and the aggregate `INTENDED_RED` outcome cannot advance Gate 2/3 for the complete A–J matrix.
- Correction: make the corrupt-layer fixture target the actual canonical `/opt/fmonitor` dependency-install seam (or another deterministic, implementation-independent fixture that produces a missing/corrupt container dependency), assert that the mutation occurred, then invoke the public launcher and prove nonzero pre-behavior failure with no host fallback. Capture a fresh exact-source RED record and resubmit the corrected delta for independent Gate 3 rereview.

## Required changes

1. Correct F1 and retain a fresh complete RED record demonstrating that every test reaches its intended assertion path; the suite may remain RED for the missing production behavior, but must contain no setup failure.
2. Refresh the prepared reviewer package/plan if the test/source binding changes, and request independent rereview of the correction.

Gate 3 does not advance to implementation on this source.

---

## Gate 3 rereview — corrected source

- Rereviewer: same independent `gpt-5.6-sol / low` Gate 3 agent (`/root/issue123_gate3`); authored neither specification nor tests and did not make the correction.
- Corrected source: correction commit `1e3d6054ed50cf5cc05ffee48c2c7c4d2613500c`; exact candidate source `615aeb9ad2b77b52cda63c1daef22a09375935130558fbda1f23e327b8001d61`.
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T023249Z-4cf39ef9e8/package.json`; package plan SHA-256 `9ad055e1d75bd9c0189991baf2244b04779f88034f746a16c48b7a56b0514138`; context manifest SHA-256 `e77271902fa293cecbc3ddebda93287dcf182eaf77e848bca81269cb29cd6344`.
- Reconstructible source: package snapshot base `1e3d6054ed50cf5cc05ffee48c2c7c4d2613500c`, empty patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`.
- Scope: disposition of prior F1 against the corrected delta, followed by complete rereview of the unchanged normative specification, complete A–J test, public seam, sensitivity, expected-value independence, rejected cases, determinism/isolation and exact-source evidence.
- Specification: unchanged `specs/CONTAINER-COMPOSER-VISIBILITY-123-A.md`, SHA-256 `7e3e942516631e4bbd076af7839daed669577ee27770ac804e0ce53883f32daa`.
- Planner: unchanged `CRITICAL`; required reviews `gate3`, `final`; required categories `governance`, `integration`.
- Current verdict: `APPROVED`. This rereview verdict supersedes the initial `CHANGES_REQUESTED` verdict for the corrected source only.

### Rereview evidence

- `python3 tests/Verification/container_composer_visibility_123_a_test.py` — exit `1`, `INTENDED_RED`, exact source `615aeb9ad2b77b52cda63c1daef22a09375935130558fbda1f23e327b8001d61`; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789525887291964000-03bbfc36e3dc47e98eb96caa2532e7c4.json`.
- The captured RED contains no fixture setup failure. A/C/G-H continue to fail for the missing dependency visibility/host masking behavior. E passes by rejecting the changed lock before Yii behavior. F now reaches `bootstrap(root)`, hence the public `run-in-profile` seam, and fails at the intended sensitivity assertion: the current launcher returns `0` because it consumes the hostile host vendor instead of failing closed on the deliberately corrupt container dependency.
- `php tests/Verification/quality_graph_ci_setup_001_test.php` — exit `0`, `QUALITY-GRAPH-CI-SETUP-001 PASSED`; same exact source; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789525914660850000-4f5e1bf083ae422da978317104d528a3.json`.
- No full local `make test` or `make verify` was run.

### Prior finding disposition

- F1 — `RESOLVED`. At `tests/Verification/container_composer_visibility_123_a_test.py:183`, the fixture now recognizes the canonical `/opt/fmonitor` install location (while remaining compatible with the alternate repository-relative layout), asserts that the recipe changed, builds the corrupt dependency fixture, and invokes the public launcher. The resulting success-via-host-fallback is precisely the missing behavior acceptance F is designed to catch; a conforming implementation must turn it into a nonzero pre-behavior failure.

### Complete rereview findings

- None.

### Complete acceptance and quality conclusion

- A/B/D/I/J remain covered by two disposable clean worktrees, distinct candidate markers, candidate/dependency origins, read-only dependency view, tracked-input digest, host-vendor absence, warm repeat and compact timing evidence.
- C retains a hostile stale host vendor that makes any fallback observable. E independently changes locked input and rejects silent old-layer reuse. F now deterministically exercises corrupt container dependencies and catches host fallback through the public seam. G/H exercise governance, integration and browser profiles through that same seam.
- Assertions remain derived from the normative contract rather than planned production structure: exact observable origins, marker value, host isolation, lock rejection, public exit behavior and compact evidence. Docker availability is distinguished as setup, worktrees are disposable, cleanup is bounded, and no production system is used.
- The complete corrected RED is attributable to missing slice behavior, not broken setup. Gate 3 advances this exact corrected source to implementation; later test/spec changes require renewed review under the recomputed plan.

### Required changes

None.

## Gate 3 delta review — owner-authorized frozen source layout

- Reviewer: independent `gpt-5.6-sol / low` Gate 3 agent (`/root/issue123_gate3`); authored neither the A–O specification nor rewritten tests.
- Review type: fresh delta review; no reliance on the earlier A–J approval.
- Reviewed source: `2b4a493608f0f946272e997771ee0e17e72faa0e`; exact candidate source `86e0af5929022bf72f9d10222d5c964929b7656b8b30d6d1be6f22debc26593b`.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T070817Z-dcca2e0244/package.json`; plan SHA-256 `a7d076ab1ce652b5c8439c68c08d097b86ae42e4101a92422650698c4b680106`; context manifest SHA-256 `d22a803e4d3431bd03938e265c4f3bf4ba3a74981a22d9d4fd27d31933d03202`.
- Reconstructible source: package snapshot base `2b4a493608f0f946272e997771ee0e17e72faa0e`, empty patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`.
- Normative contract: `specs/CONTAINER-COMPOSER-VISIBILITY-123-A.md`, package-bound SHA-256 `70c7228d4e2cfb992c7c1611526e8cafdc7ed0be2c8952ad3b85a07968fa563c`.
- Public seam: `tools/delivery/run-in-profile <profile> <command> [args...]`, with the existing frozen snapshot as optional explicit source input.
- Planner: `CRITICAL`; required reviews `gate3`, `final`; required categories `governance`, `integration`.
- Verdict: `CHANGES_REQUESTED`.

### Evidence reviewed

- `python3 tests/Verification/container_composer_visibility_123_a_test.py` — exit `1`, `INTENDED_RED`; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789542428533512000-5330887dae9b4b379a70d5553f0234a1.json`. The five failures are behavioral: absent exact-source evidence, post-freeze host mutation leaking into execution, host source remaining writable, and profile identity gaps. E/G/H already pass through their intended public routes. There is no setup failure.
- `php tests/Verification/quality_graph_ci_setup_001_test.php` — exit `255`, `INTENDED_RED`; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789542479298946000-e4c277395d8542baa661354a2831a8bb.json`. It reaches the governance profile and fails specifically because compact evidence lacks the newly required `source_digest` field.
- Both records bind exact source `86e0af5929022bf72f9d10222d5c964929b7656b8b30d6d1be6f22debc26593b` and executable source `7a26c83b20ab417dc5d4772881ee288d63d61c158aeff4eb60acc71c49fe4ec6`.
- No full local `make test` or `make verify` was run.

### Complete A–O assessment

- A–C/I/O: two disposable worktrees, distinct untracked candidate markers, cold/warm launches, origins, source immutability and before/after dependency inventories make source contamination and empty host dependency mountpoints observable.
- D: an explicit existing snapshot is frozen before a later host marker mutation; the expected frozen marker and independently captured executable digest are asserted against payload, evidence and image label.
- E: a tracked deletion plus an untracked executable-mode addition are exercised through the public route. Together with D, this is sensitive to additions, deletions, modes and post-freeze mutation.
- F/G/H: hostile stale host vendor preservation, deliberately corrupt container dependencies and changed lock identity cover host fallback, fail-closed behavior and lock invalidation. Execution-time network fallback is excluded by the intended immutable/read-only runtime design and public-route failure behavior.
- J: source writing must fail while `/tmp` and the explicit `.local` artifact area remain writable; the host tracked digest and absent host vendor are checked afterward.
- K–M: governance, integration and browser all use the common Yii bootstrap assertion. The PHP exact-source adaptation is minimal and limited to explicit executed Git identity plus the additional compact evidence field.
- N: explicit-snapshot identity is independently checked in D, but automatic/default snapshot identity is only self-consistency checked; see blocking finding D1.
- The PHP bootstrap output now uses real newline characters (`"\nYII_BOOTSTRAP_OK\n"`), so the earlier literal-backslash newline defect is fixed.
- The OpenSpec proposal/design/delta and normative contract coherently bound the owner-authorized change to verification source layout, existing capture/restore identity, read-only source, separate locked dependencies and unchanged production/CI topology.

### Findings

#### D1 — Blocking — automatic frozen-source identity has a circular oracle

- Location: `tests/Verification/container_composer_visibility_123_a_test.py:201-203`.
- When `snapshot is None`—the ordinary automatic-freeze path used by A–C/F/K–M—the test sets `expected_source = payload["source_digest"]`, then only checks that payload, compact evidence and image label repeat that same value. A plausible regression that labels and reports an arbitrary or stale 64-hex digest while executing current bytes would pass these identity assertions. Marker checks establish some source bytes, but do not prove the complete executable digest required by CCV123A-01 and matrix N.
- The explicit-snapshot D case is independently anchored to `snapshot.executable_digest`, but it cannot establish that the default launcher captured the current candidate identity in every automatic invocation.
- Correction: independently compute the expected executable digest from the candidate before invoking the ordinary public route (using the already selected `source_details()` seam), then compare payload, compact evidence and image label to that value. Preserve the explicit snapshot oracle for post-freeze mutation. Capture fresh exact-source RED evidence and resubmit the corrected delta.

### Complete findings list

- D1 only. No additional traceability, seam, expected-value, determinism, setup-isolation, OpenSpec coherence, newline, A–M or O findings.

### Required changes

1. Correct D1 so matrix N independently proves automatic/default frozen executable identity rather than internal digest agreement.
2. Refresh exact-source RED evidence/package after the test delta and request independent Gate 3 rereview.

This delta does not advance to implementation on the reviewed source.

---

## Gate 3 delta rereview — independent automatic-source identity

- Rereviewer: independent `gpt-5.6-sol / low` Gate 3 agent (`/root/issue123_gate3`); authored neither the correction nor reviewed artifacts.
- Corrected source: commit `1d423bf924c7ecd49b6b171ac4b644c72ea707d1`; exact candidate source `81865c8282cc706fb61207e46a3a212b7acdc4d18ded57859d39dd1629ed41f6`.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T071235Z-d9c6a065e5/package.json`; plan SHA-256 `ed1bde79cd85d2bc45114b4b26b6ecb1bf0e72e17cebf658686c98dbe7ee722a`; context manifest SHA-256 `f88d7a739af90dfa75b18454975bac43bd43c9482157a025bb465a6f9edc725b`.
- Reconstructible source: package snapshot base `1d423bf924c7ecd49b6b171ac4b644c72ea707d1`, empty patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`.
- Scope: D1 correction plus full rewritten-test coherence against the unchanged A–O contract.
- Verdict: `APPROVED`. This verdict supersedes the preceding `CHANGES_REQUESTED` verdict for this corrected exact source only.

### Evidence

- `python3 tests/Verification/container_composer_visibility_123_a_test.py` — exit `1`, `INTENDED_RED`; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789542636575608000-98d1f019756c4f38a0c00cdad9895b3d.json`.
- `php tests/Verification/quality_graph_ci_setup_001_test.php` — exit `255`, `INTENDED_RED`; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789542722353392000-301d097fe2dd48e5a17c278e09a995d9.json`.
- Both records bind exact source `81865c8282cc706fb61207e46a3a212b7acdc4d18ded57859d39dd1629ed41f6` and executable source `189a4092467a9a44a4033d53c1b8acbdb8e64587272c33ac3a261e5278c8a7bd`. Failures remain behavioral—missing source evidence/layout, post-freeze isolation and read-only source—not setup failures.
- No full local `make test` or `make verify` was run.

### Prior finding disposition

- D1 — `RESOLVED`. Before every ordinary launcher call, `assert_bootstrap` now independently obtains the candidate `source_details()["executable_digest"]` from the existing host-side harness seam. It then compares that fixed expectation with the container environment payload, compact result and image label. An arbitrary or stale internally self-consistent digest can no longer pass. Explicit-snapshot D continues to use the digest captured before the later host mutation, preserving the distinct freeze oracle.

### Complete rereview findings

- None.

### Coherence conclusion

- The one-line correction strengthens matrix N without changing the public route or expected implementation. It composes correctly with A–C/F/K–M automatic capture and D explicit post-freeze mutation.
- The remaining A–O assertions retain the previously reviewed sensitivity for additions/deletions/modes, host dependency cleanliness, candidate isolation, read-only source and writable artifacts, corruption/lock rejection, profiles, newline output and exact frozen identity.
- The fresh complete RED is valid for the owner-authorized source-layout gap. Gate 3 advances this exact corrected source to implementation; later spec/test changes require renewed review under the recomputed plan.

### Required changes

None.

---

## Gate 3 narrow delta rereview — frozen profile Compose source

- Reviewer: independent `gpt-5.6-sol / low` Gate 3 agent (`/root/issue123_gate3`); authored neither the test delta nor implementation.
- Prior approved test source: review commit `8871ebb43874eeb4095caea641990076001ff027`.
- Gate 5 originating finding: F1 in review commit `41a731da`, requiring integration/browser Compose configuration to come from the frozen candidate rather than later host state.
- Test delta: commit `32c05957eff0bb7efe09440445b455d70e6fc5ef`.
- Exact candidate source: `100ef5891714e6e58a1ff5715a925deb625a2d90b73ebb37410419ad20807f43`; executable source `6597161536102e519e2502baeadb5cac95408cc4605e0753025cc6f77651e588`.
- Prepared root package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T075900Z-641957d8c7/package.json`; plan SHA-256 `b2faf8b2fb8405f39c544ffea842d9d1528aeabc1c367ae2a23429463dd430ba`; context manifest SHA-256 `6e520033630df7d3316449326698fa3b678617234ef8b98d49faa40f833d1c3f`.
- Reconstructible source: snapshot base `32c05957eff0bb7efe09440445b455d70e6fc5ef`, empty patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`.
- Scope: only the added integration/browser post-freeze Compose-source assertion and its interaction with the previously approved matrix D.
- Verdict: `APPROVED`.

### RED evidence

- Focused command: `python3 tests/Verification/container_composer_visibility_123_a_test.py ContainerComposerVisibilityTest.test_d_frozen_candidate_ignores_later_host_mutation`.
- Record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789545399360630000-c5d196244f944f11963f7b43a4529e96.json`; exit `1`; harness outcome `REGRESSION_FAILURE` because this is a post-implementation correction cycle. For Gate 3 sensitivity it is the required intended RED: both integration and browser report `x-fm123-source: later-host-compose` where the independently frozen expectation is `x-fm123-source: frozen-compose`.
- Runtime: 78.742s. No setup failure, no full local suite, and no `rapid-pilot` access.

### Review assessment

- Public seam: both probes call the real `tools/delivery/run-in-profile <profile> true` route with the explicit existing `FMONITOR_EXECUTION_SNAPSHOT`; the test does not invoke a private implementation helper.
- Independent oracle: the fixture adds `frozen-compose` before capture, mutates only the live host checkout to `later-host-compose` after capture, and expects the immutable pre-mutation value. The expected value is not derived from launcher output or planned correction.
- Sensitivity: a temporary `docker` shim records the concrete `-f` Compose file consumed by the launcher, then delegates unchanged argv to the real Docker CLI. The retained RED proves the current implementation reads live host Compose input for both affected profiles; changing those entry points to the restored frozen candidate is necessary for GREEN.
- Determinism and isolation: the worktree, snapshot, shim directory and log are disposable; the real Docker executable is resolved explicitly; the shim preserves all non-observation behavior; integration and browser are asserted separately and in a fixed order. Existing marker/source-digest assertions still prove the container application bytes use the same frozen snapshot.
- Coherence: the 42-line addition is confined to matrix D and directly closes Gate 5 F1. It does not weaken or change prior A–O expectations, the exact-source oracle, dependency/lock assertions, profile command semantics or production scope.

### Findings

- None.

### Required changes

None. This test delta is approved for the exact source above; implementation correction and renewed Gate 5 review remain required.

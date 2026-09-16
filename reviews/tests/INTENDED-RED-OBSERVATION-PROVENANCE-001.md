# Test review: INTENDED-RED-OBSERVATION-PROVENANCE-001

- Reviewer: Codex independent reviewer, `gpt-5.6-sol` / low
- Test author: root agent (owner-authorized autonomous assignment)
- Reviewed source: base `11f0fcb446d8fbd0cbfa2c88caf2e0888b9819cf` plus retained binary patch `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T023319Z-16d2bdd601/snapshot/source.patch`, SHA-256 `cd6d37d1908ff14b1d9a86b3c1d288b87ce7c6d54585fbb29919b5e6b20f889b`; snapshot manifest `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T023319Z-16d2bdd601/snapshot/manifest.json`, SHA-256 `9c394aadf38b8a5d3de8c9af95e6fe45f1d7b3477a6b39ac71bc38dcf2197836`; prepared candidate source `2093b50037e8c5a3d3c5d53b7029ea8e55d1a32ed6bb1af4dc496b4c77bbc061`; package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T023319Z-16d2bdd601/package.json`
- Agreed review scope / prior findings disposition (for rereview): first Gate 3 review of the complete slice B specification, CRITICAL verification plan, root-authored tests, and retained RED evidence; no prior findings
- Specification: `specs/INTENDED-RED-OBSERVATION-PROVENANCE-001.md`; OpenSpec change `openspec/changes/reject-wrapper-only-intended-red/`
- Public seam: `python3 tools/delivery/harness.py run [--intended-red MARKER] -- ARGV...`, including the supported structured `tools/delivery/run-in-profile` route
- Red command and intended failure: `python3 tests/Verification/delivery_harness_001_test.py`; retained harness record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789525933669904000-1a2f99bcfe604fd9bba6d19cd5f7f884.json`; source `2093b50037e8c5a3d3c5d53b7029ea8e55d1a32ed6bb1af4dc496b4c77bbc061`; outcome `INTENDED_RED`; executable source `f1a925689f04b0603970c058112f41c40b743c4746015ff1fb0043d155ca9248`. The focused suite ran 26 tests in 50.766 s with one deterministic failure at `tests/Verification/delivery_harness_001_test.py:616`: expected `REGRESSION_FAILURE`, observed the old false `INTENDED_RED` when the marker appeared in emitted serialized metadata.
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **Blocking — the sensitivity test does not cross the specified structured-wrapper seam.** `tests/Verification/delivery_harness_001_test.py:611-638` invokes a direct `python -c` child that merely prints strings named `RUN_IN_PROFILE_COMMAND` and `RUN_IN_PROFILE_RESULT`. It never invokes `tools/delivery/run-in-profile` or another bounded wrapper implementing the structured observation protocol promised by the spec, design decision 3, verification-plan seam, and task 2.1. A production change that special-cases these printed strings could pass while the real wrapper route remains vulnerable. Add a public `harness.py run -- ... tools/delivery/run-in-profile ...` (or its documented supported invocation) fixture that separates actual child observation from wrapper metadata, with both metadata-only negative and legitimate-child-observation positive cases.

2. **Blocking — the positive oracle cases contradict the normative provenance rule.** The contract excludes a marker originating only from an `expected-value echo`, but `tests/Verification/delivery_harness_001_test.py:623` obtains the marker from `sys.argv[1]` and immediately prints it, and lines 629-632 do the same before adding metadata. These tests require `INTENDED_RED` for behavior the contract says must not admit it, so expected values are not independently derived and the test can force an implementation to preserve the false-positive class. Make the legitimate oracle independently emit a canonical failure observation determined by executed test behavior, not copy the expected CLI argument/environment/metadata into output; retain a separate negative expected-value-echo case.

3. **Blocking — A–M traceability and coverage are incomplete/ambiguous.** The single test has no case labels or mapping and does not distinctly demonstrate C (command echo), J (existing healthy fixture lifecycle), K (existing setup fixture lifecycle), L (public prepare/run machine-readable outcome), or M through the real T08-shaped wrapper route. A and B are partially represented, D–I are mostly represented, but several are conflated and the actual wrapper requirement is absent. Add an explicit A–M mapping (separate subtests or a table with case IDs) and assertions for each promised observable, reusing existing tests only where the mapping names the exact test and its assertions. In particular, verify retained wrapper diagnostic plus raw command verdict/child exit without treating metadata as oracle.

4. **Major — setup/control provenance is not tested against the structured boundary.** Lines 619-622 print a `SETUP_FAILURE` line from the same direct child and therefore only exercise existing control-marker priority; they do not prove that launcher/setup reachability is conveyed and validated by the wrapper protocol, nor that missing/corrupt required provenance fails closed. Add valid, absent, and malformed structured-provenance cases and show that pre-behavior setup failure remains its exact non-RED outcome while malformed/missing provenance cannot become `INTENDED_RED`.

The retained failure itself is suitable RED evidence for the narrow serialized-metadata defect: the runner starts successfully, evidence is external, all other focused harness tests pass, and the failure is the old admission outcome rather than broken setup. The CRITICAL plan is current, binds the reviewed spec/test bytes, requires Gate 3 and final review, and selects focused governance/unit checks plus full CI only. Scope boundaries remain tooling-only and exclude product/domain, schema, deployment, FAST/T06/T03, and worktree-identity work as required.

## Required changes

- Replace or supplement the simulated diagnostic cases with a real supported structured-wrapper public-route fixture, negative and positive.
- Remove positive expectations based on copying the expected marker from argv; add explicit expected-value-echo rejection and an independently produced oracle observation.
- Provide explicit executable A–M traceability, including C, J, K, L, and a real wrapper-shaped M.
- Cover absent/malformed structured provenance and setup-before-behavior fail-closed behavior.
- Capture a fresh deterministic RED record for the corrected complete candidate and prepare a fresh reviewer package because test/spec-bound source bytes will change.

---

## Gate 3 rereview — corrected test delta

- Reviewer: Codex independent reviewer, `gpt-5.6-sol` / low
- Test author: root agent (owner-authorized autonomous assignment)
- Reviewed source: base `11f0fcb446d8fbd0cbfa2c88caf2e0888b9819cf` plus retained binary patch `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T023959Z-bb67a8bb1e/snapshot/source.patch`, SHA-256 `0a298a5b8dc5dfd01cf804b457f95396fe4e7d89dff0b6b52a130fe61235932f`; snapshot manifest `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T023959Z-bb67a8bb1e/snapshot/manifest.json`, SHA-256 `17b8052cf231f218ee3589784b6f78c59bcfdcd520c1252a91e550e00629b1a0`; prepared candidate source `492ede65c95dbb2f91cca5d3bf9c6054f4b8467f56f8e1ec6e541c295eeb9789`; package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T023959Z-bb67a8bb1e/package.json`; correction delta `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T023959Z-bb67a8bb1e/delta.patch`
- Agreed rereview scope: disposition of the four findings above in the corrected root-authored tests; specification and scope unchanged
- Fresh RED: `python3 tests/Verification/delivery_harness_001_test.py`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789526340479843000-7726d481d85e409ab4b9a7ea228b0441.json`; source `492ede65c95dbb2f91cca5d3bf9c6054f4b8467f56f8e1ec6e541c295eeb9789`; executable source `b3b256a43684d8d39d98a17e36e9772784618a74d6de221a6b945f4e3a67514e`; 26 tests ran in 44.292 s with one failure at line 648: the real `run-in-profile` route returned the old false `INTENDED_RED` instead of `REGRESSION_FAILURE`
- Verdict: `CHANGES_REQUESTED`

### Prior findings disposition

1. **Real wrapper sensitivity — resolved.** `profile_result()` invokes the public harness with the actual `tools/delivery/run-in-profile` route and a bounded fake Docker adapter. Both metadata-only failure and legitimate child-oracle paths use that seam. The fresh RED fails for the intended old behavior, not setup.

2. **Independent positive oracle — resolved.** The positive direct and wrapped cases execute `oracle-red.py`, whose expected assertion is fixed independently of the harness marker argument. The earlier positive argv echo has been removed.

3. **A–M traceability — partially resolved, still blocking.** The test now labels A–M, but several labels are grouped without distinct promised observations. In particular, C requires marker-only command-echo rejection, yet the actual `run-in-profile` script emits `RUN_IN_PROFILE_RESULT` serialized metadata and no command echo; assertions at lines 651-652 prove only that result metadata contains the marker. L requires the public prepare/run route's machine-readable exact outcome, while lines 674-677 only inspect a parsed run record and paths: no `prepare` invocation or assertion of the delivered machine-readable outcome is made. J is described as preservation of existing healthy fixtures but is represented only by the newly created oracle fixture; if an existing fixture is intended, name and invoke it, otherwise adjust the normative example before Gate 2 rather than silently redefining it. Add distinct assertions/mapping for these cases so one metadata scenario is not counted as A, B, C, and M simultaneously without observing each promised source.

4. **Missing/malformed structured provenance and setup boundary — partially resolved, still blocking.** The fake-Docker build failure is a real pre-child wrapper setup path, but the test asserts only a non-`INTENDED_RED` outcome and does not prove the child/oracle was not executed (for example with a sentinel). More importantly, lines 679-685 run direct `python -c` commands that print protocol-looking text; they never pass through `run-in-profile` and therefore do not exercise missing or malformed provenance at the structured wrapper boundary. A direct command's stdout/stderr is otherwise a legitimate observation channel under the contract, so these expectations invite implementation-specific string parsing rather than validating the designed protocol. Exercise absent and malformed envelopes through a bounded wrapper/protocol fixture recognized by the runner, and assert the exact fail-closed outcome/evidence. Add a child-not-reached witness for setup failure.

### Required changes after rereview

- Add a distinct command-echo-only negative case for C, or remove/clarify C in the normative contract before resubmitting Gate 3.
- Exercise and assert L's complete public `prepare`/`run` machine-readable outcome contract; explicitly identify the existing healthy fixture used for J or correct the contract.
- Route missing/malformed provenance cases through the structured wrapper protocol rather than direct commands printing protocol-like strings, and assert their exact non-RED outcome.
- Prove the setup-failure fixture does not execute the child oracle.
- Capture fresh RED evidence and prepare another source-bound reviewer package after correction.

---

## Gate 3 rereview 2 — third submitted candidate

- Reviewer: Codex independent reviewer, `gpt-5.6-sol` / low
- Test author: root agent (owner-authorized autonomous assignment)
- Reviewed source: base `11f0fcb446d8fbd0cbfa2c88caf2e0888b9819cf` plus retained binary patch `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T024354Z-c96200e484/snapshot/source.patch`, SHA-256 `3ccf5cf1328e4095cd3eb68ca14844dce7b3143273ce00fc3401c546212023d5`; snapshot manifest `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T024354Z-c96200e484/snapshot/manifest.json`, SHA-256 `91f3c47d7cf358e4977a013d27e406dcf04503246258996a33e49fa62bb04d3f`; prepared candidate source `7969e564bb1ab080063a1327c35b471d87492084c63d50923b3c4cb705a6be2a`; package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T024354Z-c96200e484/package.json`; correction delta `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T024354Z-c96200e484/delta.patch`
- Fresh RED: `python3 tests/Verification/delivery_harness_001_test.py`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789526585922814000-bfe064f104bb4ec9b513c245f3b87378.json`; source `7969e564bb1ab080063a1327c35b471d87492084c63d50923b3c4cb705a6be2a`; executable source `a0f30c726d870fcd5044f854c36aeed711582e4e02d32ff8951e7a57f85dc77c`; outcome `INTENDED_RED`
- Verdict: `CHANGES_REQUESTED`

### Disposition and findings

1. **Command-echo C — resolved.** The bounded structured-wrapper fixture now emits a distinct `RUN_IN_PROFILE_COMMAND` containing the marker while its result envelope reports zero observation bytes, and asserts `REGRESSION_FAILURE`.

2. **Setup-before-child D/K — resolved.** The real `run-in-profile`/fake-Docker build failure asserts exact `REGRESSION_FAILURE`, preserves the raw command verdict, and a filesystem sentinel proves the child did not execute.

3. **Existing healthy fixture J — resolved.** The mapping explicitly identifies pre-existing sibling `Harness.test_outcome_precedence_and_actual_exit`, which runs in the same focused suite, in addition to the independent positive oracle.

4. **Missing/malformed structured provenance — seam resolved, assertion remains weak.** Both cases now use the bounded structured-wrapper fixture, but lines 713-717 assert only `outcome != INTENDED_RED`. The normative ordinary-nonzero result here is `REGRESSION_FAILURE`; asserting only inequality would allow an erroneous `GREEN`, `UNKNOWN`, `SETUP_FAILURE`, or other classification to pass. Assert exact `REGRESSION_FAILURE` and the retained raw exit/command verdict for both cases.

5. **Blocking deterministic contradiction in L.** Lines 695-697 assign `summary` from the legitimate wrapped oracle and assert its outcome is `INTENDED_RED`. No later run reassigns `summary`; after `prepare`, line 709 asserts that same value is `REGRESSION_FAILURE`. Therefore the test must fail after the intended implementation reaches this section. The retained RED stops at the earlier metadata-only assertion and cannot validate this later path. Keep the H outcome assertion as `INTENDED_RED`; for L, capture a named run result (or assert the already captured intended outcome consistently) and separately assert the parsed `prepare` package. This is a test defect, not intended RED sensitivity.

The fresh evidence remains deterministic for the original missing production behavior, and the package/plan/source bindings are current. Approval is blocked solely by the contradictory L expectation and the overly permissive malformed/missing outcome assertions.

### Required changes after rereview 2

- Correct L so it does not assert two different outcomes for the same `summary`; explicitly bind the machine-readable run result being checked.
- Require exact `REGRESSION_FAILURE` plus preserved raw exit/command verdict for missing and malformed provenance.
- Capture fresh source-bound RED evidence and prepare the corrected reviewer package.

---

## Gate 3 rereview 3 — final test candidate

- Reviewer: Codex independent reviewer, `gpt-5.6-sol` / low
- Test author: root agent (owner-authorized autonomous assignment)
- Reviewed source: base `11f0fcb446d8fbd0cbfa2c88caf2e0888b9819cf` plus retained binary patch `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T024552Z-bf12fc27b8/snapshot/source.patch`, SHA-256 `843d04e983a66c53454653f8269ff0e49905e0f4b3128cd4ea2a908329e6a461`; snapshot manifest `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T024552Z-bf12fc27b8/snapshot/manifest.json`, SHA-256 `dd1b47e6cd27a7dfcac8ee9ceb6a5ee23b787d6e5e8a01c37bccf3d454cd9ec0`; prepared candidate source `07161e838cd94abd38d0c217b2ab258650ec0bc92a60128e0d5f9515ee5d6852`; package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T024552Z-bf12fc27b8/package.json`; correction delta `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T024552Z-bf12fc27b8/delta.patch`
- Fresh RED: `python3 tests/Verification/delivery_harness_001_test.py`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789526700095962000-aa795f235be04829a0a6f9eec198ee67.json`; source `07161e838cd94abd38d0c217b2ab258650ec0bc92a60128e0d5f9515ee5d6852`; executable source `df345de83820b4caba7cb33e6bd13d91e524a7f83b55b0fb6fc5ee689f2abc9b`; command verdict `REGRESSION_FAILURE`; harness outcome `INTENDED_RED`; 26 tests ran in 37.929 s with the sole failure at line 658, where the current implementation falsely classified real-wrapper metadata-only provenance as `INTENDED_RED`
- Verdict: `APPROVED`

### Final findings and prior-disposition check

None.

The final delta saves the A/B/M metadata-only run under dedicated variables and L now consistently asserts its public run result, raw exit `255`, command verdict, retained paths, and the separately parsed machine-readable `prepare` package. Missing and malformed structured-wrapper provenance now each require CLI/raw exit `8`, exact `REGRESSION_FAILURE`, and preserved raw command verdict. The earlier real-wrapper, independent-oracle, command-echo, setup sentinel, existing healthy sibling fixture, and explicit A–M corrections remain present.

Traceability is complete for the bounded A–M contract; expected values are independently determined; rejected/control cases fail closed with exact outcomes; the real public seam and structured-wrapper boundary are exercised; evidence remains external and reconstructible; fixtures use only bounded local fake Docker/files; and the retained RED is deterministic and attributable to the missing provenance implementation rather than setup. The CRITICAL plan remains current and requires the later independent final review and exact-source CI. Gate 4 may proceed against this reviewed test source.

### Required changes

None.

---

## Gate 3 test-correction rereview — fixture boundary

- Reviewer: Codex independent reviewer, `gpt-5.6-sol` / low
- Test author: root agent (owner-authorized autonomous assignment)
- Reviewed source: base `11f0fcb446d8fbd0cbfa2c88caf2e0888b9819cf` plus retained binary patch `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T025122Z-f43a270ec3/snapshot/source.patch`, SHA-256 `5109e750265205e93731424aa6dfb94207fb3b45ebbab943fe2171ffcbefd51f`; snapshot manifest `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T025122Z-f43a270ec3/snapshot/manifest.json`, SHA-256 `176766510a9af6d474600583816f60b8bfca0599f21435931c176b952ada325e`; prepared candidate source `33a608f8516bd3243f3ebab930337deb8bce766cadb1a7c03d1fe0e62bd9079f`; package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T025122Z-f43a270ec3/package.json`; correction delta `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T025122Z-f43a270ec3/delta.patch`
- Rereview scope: test-only fixture-boundary correction after executor exposed source mutation during execution; prior A–M expectations and approved specification are unchanged
- Fresh RED: `python3 tests/Verification/delivery_harness_001_test.py`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789527032914582000-ade5dcfe70424d95999da3e8ffcc28f3.json`; source `33a608f8516bd3243f3ebab930337deb8bce766cadb1a7c03d1fe0e62bd9079f`; executable source `8486ac4d0b5ad7ad9d4c64fa39e39281ef619945b439cd6970252f9be6a1abe9`; command verdict `REGRESSION_FAILURE`; harness outcome `INTENDED_RED`; 26 tests ran in 39.165 s with the sole failure at the intended metadata-only assertion on line 658
- Verdict: `APPROVED`

### Findings

None.

The delta changes only the locations of `bounded-structured-wrapper`, the red/unrelated/green oracle files, and the child-reached sentinel from the temporary fixture repository (`self.repo`) to its owning external temporary directory (`self.outer`). This removes incidental source-digest mutation while retaining per-test isolation and cleanup. Commands continue to receive explicit absolute paths, the fake-Docker and real `run-in-profile` boundary are unchanged, and no acceptance expectation or production behavior is weakened.

The reconstructed source and fresh evidence show deterministic RED for the same missing production provenance behavior, with all 25 sibling harness tests green. The correction therefore preserves the previously approved A–M sensitivity, setup isolation, independent expected values, and retained-evidence checks.

### Required changes

None.

# Gate 3 test review — VERIFICATION-DELIVERY-DEDUPLICATION-001

- Reviewer: separately tasked agent `/root/issue198_gate3`; authored none of the reviewed specification or tests.
- Review date: 2026-09-19.
- Test author: root agent.
- Base: `62d027d54af7a01a1da300eda2901ba68b2bab20`.
- Reviewed candidate source: `852e755c47417a820819c1e75147cd3a7d460ec7b8e78141a1eebff53bef26e2`.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T105122Z-9d771a08ca/package.json`.
- Reconstructible snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T105122Z-9d771a08ca/snapshot/source.patch`, SHA-256 `9653183c592de0df0952995e9e7aa5109fe1b3ea24135247e3b2ec5bd3d3d992`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T105122Z-9d771a08ca/verification-plan.json`, package-declared SHA-256 `cf14c1b7d8a0df623e54c95b86f8120e68411ae81f1995720dee6a4df814e0a4`; lane `CRITICAL`; required reviews `gate3`, `final`.
- Stable contract: `specs/VERIFICATION-DELIVERY-DEDUPLICATION-001.md`, package-bound SHA-256 `bcacd4adc25c0447d4a7121d6016a484d4ac891792edfcc8b978266601437338`.
- Reviewed test: `tests/Verification/verification_delivery_deduplication_001_test.py`, package-bound SHA-256 `711d5736c8fc118e128c71455a93de8d33cee6c89d5e9be276e63c14eb597cd6`.
- Retained RED record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789815076188576000-a79f84d5d80c490984391c01539e7596.json`; command `python3 tests/Verification/verification_delivery_deduplication_001_test.py`; exit `1`; outcome `INTENDED_RED`; command verdict `REGRESSION_FAILURE`; executable source `8f79b2f534b103efe9808b4e27588066bf5223eea35c5d8e222dea224873716d`.
- Scope checked against issue `#198`, its acceptance list, the OpenSpec change, required context, verification plan and retained stdout/stderr.
- Verdict: `CHANGES_REQUESTED`.

## Findings

1. **CRITICAL — A3 has no executable witness and the completed-success case can bypass the required observer/admission result checks.** The contract requires a completed run with unconfirmed mandatory jobs/results to remain non-GREEN and assigns final truth to the existing observer/admission seam (`specs/VERIFICATION-DELIVERY-DEDUPLICATION-001.md:49-51`). The test instead accepts any identity-matching `status="completed", conclusion="success"` as `REUSE` (`tests/Verification/verification_delivery_deduplication_001_test.py:82-84`) and never supplies or asserts mandatory jobs/results, observer invocation, one-run evidence integrity, or non-GREEN handling for incomplete results. An implementation that returns the run directly and never connects it to the existing observer would pass. Add an injected observer/admission seam and cases for complete required results, missing/incomplete results, and a guard against combining jobs from different run identities.

2. **CRITICAL — bounded discovery and post-dispatch tracking are not exercised.** Every launcher test forces `polls=1` (`tests/Verification/verification_delivery_deduplication_001_test.py:62-66`), while `FakeGithub.list_runs()` returns the same flat observation on every call (`:25-35`). Thus the suite cannot distinguish immediate absence from a PR run appearing during the bounded window, cannot assert the poll bound, and cannot prove that polls after fallback dispatch track captured run `900` without dispatching again, which are central requirements of issue #198 and A2 (`spec:36-47`). Add sequenced observations and call counts: absent then applicable run must reuse with zero dispatch; confirmed absence only after the configured bound must dispatch once; post-dispatch queued/completed/error polling must retain the created identity and keep dispatch count at one.

3. **HIGH — UNKNOWN/incomplete GitHub responses and transport failures are only partially covered.** The sole UNKNOWN case raises from `list_runs()` (`tests/Verification/verification_delivery_deduplication_001_test.py:106-108`). There are no cases for malformed/incomplete list data, unknown applicability fields, failure during dispatch, an incomplete dispatch response without identity/link, or an error while observing the captured fallback run. A permissive implementation can treat missing fields as mismatch/absence and dispatch blindly, or report `DISPATCHED` without a trackable identity. Add deterministic rejection cases for each of these fail-closed paths and assert zero further dispatch and no success/GREEN claim.

4. **CRITICAL — A4 and A5 are checked as prose substrings rather than observable prepared-package behavior.** `test_a4_a5_handoff_retains_delta_findings_and_narrow_cosmetic_rule` only searches three phrases in a Markdown template and four words in process documentation (`tests/Verification/verification_delivery_deduplication_001_test.py:110-118`). It does not prepare or inspect a correction package, so it cannot prove full candidate/last-reviewed identities, an actual delta reference, complete prior findings, valid dispositions, open-finding blocking, two-return handling, suggestion separation, or material-versus-cosmetic classification. An inert documentation edit would make all of A4/A5 GREEN while the real package remains unchanged. Exercise the actual package/harness public seam with small fixtures for #194/#195: verify retained findings and dispositions, reject `open` presented as fixed, and prove only checkbox/PR-typo deltas are exempt while normative, executable, binding, authority and GREEN-status changes require review.

5. **HIGH — A1 proves one known mapping and TSV line uniqueness, but not the issue's complete consumer/selection requirement or preserved browser semantics.** The test inspects only `refresh-yii2-shlz-ui/verification-input.json` (`tests/Verification/verification_delivery_deduplication_001_test.py:68-76`) and does not ask the verification planner/runner for the focused and full selected command lists. It therefore cannot catch another active acceptance mapping/consumer selecting the removed wrapper, a newly introduced equivalent wrapper, or duplicate command expansion outside this one TSV entry. It also has no characterization binding that the canonical browser assertions/fixtures and intended-RED/regression/environment classification remain unchanged. Add an inventory/consumer search or planner-level selection witness for all current mappings, assert canonical command multiplicity once in focused and once in full CI, and bind the canonical test/fixture content or existing classification tests so deletion/weakening is detected.

6. **MEDIUM — the RED is exact-source and deterministic, but most A2 branches share only a missing-module failure and therefore lack branch reachability evidence.** The retained record is correctly bound to candidate source and command blob, and A1/A4/A5 fail for intended missing behavior. However all A2 cases stop in `load_launcher()` because `tools/delivery/ci_launch.py` is absent; no decision-table branch or fake transport interaction is reached. That is a valid initial RED for the absent seam, but not the complete reachable A1–A5 matrix required by Gate 3. After adding the missing cases above, provide either a minimal test-only seam that reaches each branch without production implementation or staged exact-source RED evidence showing each independent expectation fails for its intended reason rather than all being masked by one import guard.

## Required changes

Resolve findings 1–6, especially the missing A3 observer/admission assertions, real bounded polling/single-dispatch lifecycle, and behavioral A4/A5 package cases. Regenerate the verification plan and reviewer package when the test/source binding changes, retain fresh exact-source RED output with independently reachable failures, and resubmit to an independent Gate 3 reviewer before Gate 4 implementation.

---

## Correction rereview — 2026-09-19

- Corrected candidate source: `4eb305696c77c48b52b9ac0ac502c474b9d4731f4f43d29cc43626d8fc40b4ee` over the same base `62d027d54af7a01a1da300eda2901ba68b2bab20`.
- Corrected package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T105720Z-ef9b2df75a/package.json`.
- Delta from the previously reviewed snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T105720Z-ef9b2df75a/delta.patch`, SHA-256 `7d0354c51937a3fad41aaff388f8e28c4ac97dc0ba9a73e570c48676a98b804f`.
- Corrected reconstructible snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T105720Z-ef9b2df75a/snapshot/source.patch`, SHA-256 `d39eca6df7ce6bc45caf3735287c20d29b6067232bda0a71a1d45ad303b91cfa`.
- Corrected verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T105720Z-ef9b2df75a/verification-plan.json`, SHA-256 `0acc44eea38dcefe955641b9348664601619727d1bf880526e51fead30e288c0`; lane remains `CRITICAL`, required reviews remain `gate3`, `final`.
- Corrected test SHA-256: `acb5887d28095601d6d894e3de9e2f3ed6c5e948262926b630bfa63846c93ffc`.
- Corrected RED record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789815432487486000-61ee824f7c024669b89e74da2b389d03.json`; exit `1`; outcome `INTENDED_RED`; command verdict `REGRESSION_FAILURE`; executable source `c45dba866bdda1b4f51aa65b9cbf7f938cfb131cf98b5a88004909fbe6d9c3cf`.
- Independence is unchanged; this reviewer authored none of the correction.
- Correction verdict: `CHANGES_REQUESTED`.

### Prior findings disposition

1. **Partially resolved; still blocking.** The corrected suite injects an `Admission` seam, verifies that incomplete required results produce `UNKNOWN`, records run identity `77`, and rejects a job belonging to run `88`. It still has no positive completed-success case proving that a complete admission result is invoked and the same run is returned to the observer/admission path. Both completed cases expect `UNKNOWN`; an implementation that returns `UNKNOWN` for every completed run and never supports valid completed reuse would pass. Add a completed run with complete same-run jobs and admission `SUCCESS`, assert one admission call for run `77`, `REUSE` of run `77`, zero dispatch, and no jobs from another run.

2. **Partially resolved; still blocking.** The sequence `[[], [run()]]` now proves a PR run appearing on poll two is reused without dispatch, and `[[], [], []]` proves the configured discovery bound is exhausted before one dispatch. However the post-dispatch case supplies queued and completed observations but explicitly expects `observe_calls == 1` (`tests/Verification/verification_delivery_deduplication_001_test.py:45-46`). The completed observation is never consumed, so the test does not prove subsequent polls track captured identity `900`, terminate on completion, invoke result admission, or avoid a second dispatch throughout that lifecycle. Require both observations to be consumed, record every observed ID as `900`, and assert dispatch remains exactly one through completion.

3. **Resolved for the bounded contract.** The corrected matrix covers malformed discovery data, discovery API error, dispatch exception, dispatch response missing identity, and observer exception. Each expects `UNKNOWN`, forbids success, and bounds dispatch to at most one. Combined with the mismatch/failure/cancelled table, this is sensitive to the specified fail-closed transport outcomes. Preserve these cases.

4. **Partially resolved; still blocking.** A4/A5 now call proposed public functions in `tools/delivery/harness_context.py`, which is materially better than Markdown substring checks. The A4 case only proves one `fixed` finding round-trips and requires `ValueError` for one `open` finding at `return_count=2`. It does not prove that an open finding on an ordinary correction is retained as an explicit blocker, that the complete prior finding list survives, that `not-applicable` requires a reason, or that reviewer suggestions/new delta risks remain distinct. Moreover forcing an exception after two returns is not the contract's only allowed result: root may return a concrete blocker/reconsideration state. Add fixtures with multiple prior findings and assert complete ordered dispositions, open-blocker preservation, reasoned `not-applicable`, suggestion separation, and an explicit reconsider/block result after the second unresolved return.

5. **Largely resolved, with one remaining consumer gap.** The test now scans all current change verification inputs, rejects wrapper use in each acceptance `tests` list, requires a canonical reference, checks inventory uniqueness/removal, and binds the canonical browser file byte-for-byte to base. This protects browser assertions/fixtures from weakening. It does not inspect `gate3_expected` keys or other direct mapping fields, even though the current `refresh-yii2-shlz-ui` input carries the wrapper in both `tests` and `gate3_expected`; an implementation could update only `tests` and leave a stale direct consumer. Extend the all-mappings assertion to recursively reject the wrapper wherever it is used as an executable/mapping value (at minimum `tests` and `gate3_expected`) while allowing historical prose/review records.

6. **Unresolved.** Fresh RED is exact-source, deterministic and now separates A1, A2, A4 and A5 at the unittest-case level. Nevertheless every A2 decision case still stops at the same absent `tools/delivery/ci_launch.py` import, and both harness cases stop at absent attributes. None of the new sequenced polling, admission, one-run jobs, malformed transport or correction/cosmetic assertions is reached in retained evidence. Since the previous review explicitly required independent branch reachability, the correction has expanded assertions but not supplied that evidence. Provide staged test-only reachability or an equivalent retained pre-implementation probe that exercises the fake transports/context inputs and fails each branch at its intended behavioral expectation without implementing production behavior.

### New finding

1. **HIGH — A5 trusts caller-supplied `kind` labels and therefore does not classify the actual diff.** `test_a5_cosmetic_classifier_is_narrow` passes dictionaries already labelled `checkbox`, `pr typo`, `normative`, `executable`, `source binding`, `authority`, and `green status`. A trivial implementation `return any(change["kind"] not in {"checkbox", "pr typo"} ...)` passes without examining paths or changed bytes, so a normative edit mislabelled `checkbox` could incorrectly skip review. Exercise the actual classification seam with concrete before/after diff content: a tasks checkbox and external PR-number typo should be exempt, while normative prose, executable code, source/evidence binding, authority, and GREEN assertions must be detected from their path/content rather than trusted labels.

### Required changes for the next rereview

Add the positive completed/admission case, consume the full post-dispatch observation sequence with one captured identity and one dispatch, complete behavioral correction-package fixtures, reject stale wrapper keys beyond `tests`, classify cosmetic/material changes from actual diff bytes, and retain reachable RED evidence for the independent branches. Regenerate the exact-source package after correction and resubmit before Gate 4.

---

## Test-only reachability rereview — 2026-09-19

- Candidate source: `5a09170edad6b1a77f07bcc06055d493c447ecfc8d540d2305b6b8574f220c37` over base `62d027d54af7a01a1da300eda2901ba68b2bab20`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T110137Z-a2acdc15a4/package.json`, SHA-256 `bf68fec09b324e2b2a79650e6c7b5e11e762eb6d132eef2c619afe3e4096cb9b`.
- Delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T110137Z-a2acdc15a4/delta.patch`, SHA-256 `e6642502f9543a2cddacf48d021f378f32bd9bb2ff0a0c5883fc9b837e522ea2`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T110137Z-a2acdc15a4/snapshot/source.patch`, SHA-256 `0fa8bb9ed620df9c9351a65f8cc4e49f80e72d45f20749b5ce21550f38599a8c`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T110137Z-a2acdc15a4/verification-plan.json`, SHA-256 `34694ab7ee0f14db993c756e46754656f9168273d3f57a9c606ad50caeb8fb3d`; lane `CRITICAL`; reviews `gate3`, `final`.
- Test SHA-256: `3bbfb7dae3b48534f8f9be8f7c3fab32000969b603bccbd70139646b9cceee8c`.
- RED record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789815672412733000-c6afb9f4c2994647a015a72dff89b0c5.json`; exit `1`; `INTENDED_RED`; executable source `5bfac8458797be4025de060f9d7110c708642bb515b7e14f8bebafd9d21b8ea0`.
- Test-only seam: `tests/Verification/fixtures/verification_delivery_deduplication_red.py`; reviewed as deliberately incomplete RED scaffolding, not production implementation.
- Independence is unchanged.
- Verdict: `CHANGES_REQUESTED`.

### Dispositions

1. **A1 consumer/base-byte coverage — resolved.** The mapping scan now includes both acceptance `tests` and `gate3_expected` keys across current change inputs, rejects the wrapper, retains one canonical inventory entry, requires a canonical mapping, removes the wrapper file, and binds canonical browser bytes to the base. This is sensitive to the identified direct consumers and browser-test weakening.

2. **Positive completed admission — assertion added, but RED reachability remains incomplete.** The test now contains the correct positive expectation: complete same-run jobs plus admission `SUCCESS` must produce `REUSE` for run `77`, record admission run `77`, and dispatch zero times. In retained RED, the method stops at its first incomplete-admission assertion (`NOT_IMPLEMENTED` versus `UNKNOWN`); neither the mixed-run rejection nor positive-success assertion executes. The expected matrix is sound, but the submitted evidence does not demonstrate independent reachability.

3. **Post-dispatch completion identity — assertion corrected, but RED reachability remains incomplete.** The expected tuple now requires three discovery polls, one dispatch, two observer calls, admission of run `900`, and returned identity `900`. This closes the earlier semantic gap. The deliberately incomplete seam stops after one list call and the test fails immediately, so the queued/completed observations and admission path are not reached in retained evidence.

4. **Correction package — matrix substantially corrected, but only its first missing field is reached.** The test now specifies complete ordered `fixed`/`open`/reasoned `not-applicable` dispositions, suggestions, new risks, `BLOCKED`, second-return `RECONSIDER_OR_BLOCK`, and rejection of unreasoned `not-applicable`. These expectations match A4. Retained RED raises `KeyError: last_reviewed_source` on the first tuple construction; the second-return and invalid-disposition cases do not execute independently.

5. **Actual-byte cosmetic classification — resolved in expectation, partially reached in evidence.** The suite now passes concrete before/after content instead of caller-supplied kind labels. The checkbox plus PR-number-only example reaches and passes against the test seam; the first material normative example reaches and fails. Executable, source-binding, authority and GREEN-status examples are not reached because the loop aborts on the first failed assertion.

6. **Fail-closed transport matrix — expectations preserved, reachability incomplete.** The malformed discovery case reaches the test-only seam and errors with `KeyError: 0`, demonstrating the seam is deliberately incomplete rather than accidentally implementing behavior. The subsequent discovery API, dispatch error, missing dispatch identity, and observer error cases do not run because the loop stops on that first error.

7. **Public seam placement — new blocking integration mismatch.** A4/A5 now load `tools/delivery/ci_launch.py` (`tests/Verification/verification_delivery_deduplication_001_test.py:55-58`) and require that CI launcher module to expose `correction_review_context` and `requires_repeat_code_review`. The stable contract declares separate CI and review seams (`specs/VERIFICATION-DELIVERY-DEDUPLICATION-001.md:15-16`), and the design assigns package/role-prompt changes to the harness ownership path, not the CI launcher. This test would force unrelated review-package and cosmetic-diff policy into, or re-exported through, the launcher merely because the test-only fixture combines them. Exercise the actual prepared-package/harness context seam for A4/A5 and reserve `ci_launch.py` for A2/A3.

### Required correction

Keep the deliberately incomplete fixture, but make RED collection non-short-circuiting: split each subcase into separate unittest methods or aggregate failures while continuing all cases. The retained output must show the positive admission, mixed-run rejection, full post-dispatch sequence, every transport failure, second-return/not-applicable dispositions, and all material diff categories were invoked and failed for their intended expectation. Route A4/A5 through the real prepared-package/harness context seam rather than `ci_launch.py`. Regenerate the package and fresh exact-source RED evidence, then resubmit before Gate 4.

---

## Full branch-reachability rereview — 2026-09-19

- Candidate source: `e3fe2fc5206564ea4063ae7395c32c196af72ea12dfbc373c8b7dcbc535b497b` over base `62d027d54af7a01a1da300eda2901ba68b2bab20`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T110639Z-1839dab839/package.json`, SHA-256 `bf093f98acf0c5f580fe18f95a5b6b0cff073a1d762468030db8c1adfacf3eb0`.
- Delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T110639Z-1839dab839/delta.patch`, SHA-256 `8b895405de054cded971fb7c1517b745588c1385d95be2039ac717001d0db82f`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T110639Z-1839dab839/snapshot/source.patch`, SHA-256 `549f59e121b0b7894fa2ccf969b95642f04f52737633715f2304508a9b43e5f3`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T110639Z-1839dab839/verification-plan.json`, SHA-256 `cc4d700c5f1f01a790431a1cfe0e185270f8717ee553a246d938adad0213f76f`; lane `CRITICAL`; reviews `gate3`, `final`.
- Test SHA-256: `57ca94c3b0cbcc1f9ab09addd670c4005dcb9b329173d63770df3b070daa8bcc`.
- RED record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789815984778510000-92266c8daef64db799b5d5ca30e6992d.json`; exit `1`; `INTENDED_RED`; executable source `3d630af03910c82593ea71006b1b44dd3de69952422c12b20a5741686344128b`.
- Independence remains unchanged.
- Verdict: `CHANGES_REQUESTED`.

### Accepted corrections

- A2 completed cases are now separate and independently reached: incomplete admission, mixed-run jobs and positive same-run admission each fail on the expected decision while recording admission identity.
- Bounded discovery and fallback lifecycle are fully reached: retained RED shows two-poll discovery, and the fallback fixture performs three list calls, one dispatch, two observes and admission of run `900`; only the deliberately unimplemented decision differs.
- Every mismatch, failure/cancelled and incomplete-transport case is reached via `subTest`; the output contains all six identity mismatches, both conclusions and all five UNKNOWN transport cases.
- A5 uses the actual `harness_context` ownership seam with fallback and all five concrete material before/after byte categories are reached independently. The cosmetic checkbox/PR-number example remains GREEN characterization.
- The A4/A5 ownership mismatch with `ci_launch.py` is corrected: both now target `tools/delivery/harness_context.py`.

### Remaining blocking finding

1. **CRITICAL — the A4 fallback makes the missing production seam permanently GREEN.** `contract_module()` falls back whenever the requested attribute is absent, not only for a separately invoked RED probe (`tests/Verification/verification_delivery_deduplication_001_test.py:10-12`). The fallback fixture's `correction_review_context()` already implements every asserted A4 outcome: full identities/delta/dispositions, suggestions, risks, `BLOCKED`, `RECONSIDER_OR_BLOCK`, and invalid `not-applicable` rejection. Consequently `test_a4_actual_correction_package_retains_delta_dispositions` is `ok` in retained output even though the real `tools/delivery/harness_context.py` does not expose the API. After A1–A3 and A5 are implemented, the entire suite can become GREEN while A4 is still absent from the actual prepared-package/harness seam. This is a false GREEN and fails Gate 2 sensitivity. The normal regression test must require the real attribute. If test-only reachability is retained, invoke it in a separate explicitly RED-only probe or make its A4 function deliberately return `NOT_IMPLEMENTED`; split A4 cases so every expectation remains reachable without allowing fallback success to satisfy the production contract.

### Non-blocking evidence note

The A1 test still stops at the existing inventory duplicate before executing its mapping/base-byte assertions. Its expectations were reviewed directly and are deterministic; because A1 is a repository-state transformation rather than a multi-branch command, this is acceptable once the false-GREEN A4 issue is corrected. Fresh RED should nevertheless make the production-seam absence explicit rather than report A4 as `ok`.

### Required correction

Remove the production-test fallback success for `correction_review_context`: require the real harness-context API in the ordinary suite, while preserving independent RED reachability through a separate deliberately failing fixture/probe if needed. Capture fresh exact-source RED in which A4 fails for the missing production seam and resubmit before Gate 4.

---

## Real A4 seam targeted rereview — 2026-09-19

- Candidate source: `006e7c9972b4fe491b6f92b746f0ae6c70b916a83a61f691be254c6cb3f64132` over base `62d027d54af7a01a1da300eda2901ba68b2bab20`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T110904Z-a8f8cdbb25/package.json`, SHA-256 `5ba28a1e5bcc1da34ed092896ebc216d3a7d05617306c824da1ec1f8ebed02d5`.
- Delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T110904Z-a8f8cdbb25/delta.patch`, SHA-256 `3e4c1790fc0f66c4669a2b6ff1acb3df49c933163bf8cd170dfae8fb8250bbec`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T110904Z-a8f8cdbb25/snapshot/source.patch`, SHA-256 `582ab9b0bdffc1fd6f399beb0561a533fc2c870686315611410ea27d0a924a47`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T110904Z-a8f8cdbb25/verification-plan.json`, SHA-256 `c2ae044b3f3b4c36c8a0962cc27e5c029e6b401794178471e97ba25dd602ace5`; lane `CRITICAL`; reviews `gate3`, `final`.
- Test SHA-256: `3d7bbabf4ca52450515205438aa0233377e8bebf59473d0c6777c77a942b5629`.
- RED record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789816135802111000-3f309edf27a743a497205cfe3669c1d7.json`; exit `1`; `INTENDED_RED`; executable source `bc3c2916bf9b3916be887a821b00597dd1886b19a494af47ec86b4f1286b2e4b`.
- Independence remains unchanged.
- Findings: none.
- Verdict: `APPROVED`.

### Final disposition

The sole remaining blocker is resolved. The ordinary A4 regression now loads the real `tools/delivery/harness_context.py` module and explicitly requires its `correction_review_context` attribute. The test-only fixture cannot satisfy this assertion. Fresh retained RED contains `INTENDED_RED real correction package seam absent`, proving the missing real seam is observable and preventing the prior false GREEN.

All earlier accepted coverage remains byte-identical apart from this targeted test correction: A1 checks inventory, all active `tests`/`gate3_expected` mappings and unchanged canonical browser bytes; A2/A3 reach bounded discovery, positive and incomplete completed admission, cross-run job rejection, one dispatch followed by two observations of the captured identity, every mismatch/failure/cancelled case and all incomplete transport outcomes; A4 specifies complete dispositions, blockers, suggestions, risks and second-return behavior at the real harness seam; A5 classifies concrete before/after bytes and reaches every material category independently. Expected values are derived from the stable contract and issue #198, the fakes are isolated from GitHub, and RED is deterministic and exact-source bound.

Gate 3 is approved for candidate source `006e7c9972b4fe491b6f92b746f0ae6c70b916a83a61f691be254c6cb3f64132`. Gate 4 may implement A1–A5 without changing the approved expectations. Any test/spec/source-binding change requires a refreshed plan and applicable independent rereview.

---

## Supplemental post-implementation test-delta review — 2026-09-19

- Exact source: `7a767014d2294416c7b9491662f208199eeac85b93177db0fec0dc47e3dffb05` over base `62d027d54af7a01a1da300eda2901ba68b2bab20`.
- Prepared final package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T113210Z-83bd3af49c/package.json`, SHA-256 `7a701ca3e20af64a34a40c19969d3c54bef4bf001a6c426bdc05d14ad5c64861`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T113210Z-83bd3af49c/snapshot/source.patch`, SHA-256 `3e48e7b4c4865fba569e06b62dd8d5313707307f345782124d3f3a9cef8cb947`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T113210Z-83bd3af49c/verification-plan.json`, SHA-256 `510c6371a19475e039cf83ab624ce0ef6a60091d6d7c65fac0d63d0ffdc01d2b`; lane `CRITICAL`; required reviews `gate3`, `final`.
- Root contract GREEN: record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789817471740393000-18ff2679233941a69f0ab56da1a4d377.json`; command blob `41ac2a54190c8a3789b36cea0b510ae66fd5769538b9e3be4d98ecf077ab1a19`.
- Registered transport GREEN: record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789817520222370000-4ef7ebdf75a6442fa3c4786fd5de7c9f.json`; command blob `4a0db050df8c691c0d6318ef9bbd6b2de00a5498640a04d7283a9b5e14738b95`.
- Harness limitation: v1 cannot prepare a new Gate 3 package once the current acceptance expectation is GREEN. This supplemental independent review uses the exact-source final package and does not infer delivery readiness.
- Findings: none.
- Verdict: `APPROVED`.

### Disposition

The root test delta changes only the successful dispatched fixture from an incomplete completed envelope to `conclusion="success"`. This restores fidelity to the stable fail-closed contract; missing conclusion remains a rejection and is now independently exercised by the registered transport test.

`tools/delivery/ci_launch_transport_198_test.py` covers the real adapter/core boundary for completed-run hydration without dispatch, authoritative required-job inventory, policy-derived mode mismatch, empty 204 dispatch, old/new run-ID correlation, queued `PENDING`, every non-success terminal conclusion, and the formerly exceptional dispatched completed/missing-conclusion path. Its expected values are independent of implementation structure and derived from A2/A3. `tools/verification/suites.tsv` registers it as governance, `tools/verification/inventory.py` permits that exact repository-owned delivery test only, and the refreshed planner selects the command as a focused obligation. Exact-source GREEN evidence binds both root and transport tests.

The verification input's acceptance expectation changing from `INTENDED_RED` to `GREEN` correctly records the implemented state; it does not waive browser or CI obligations. Supplemental Gate 3 approves this bounded test/mapping delta for source `7a767014d2294416c7b9491662f208199eeac85b93177db0fec0dc47e3dffb05`.

# Test review: DELIVERY-EXECUTION-107 / I1

- Reviewer: independent `review_i1` agent (`gpt-5.6-sol`, low)
- Test author: root
- Reviewed source: base `b6fe81c3bec74a9dcfad54bc12f4b1063359ed6e`; retained package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T190425Z-3160bd0004/package.json`; candidate source `da4dfeaa73b791972287e1c6093fbc298dcf9392e719ba3cc55ddcc5ac0bd19d`; executable source `7cff76139876841622a91546efdb5059fd19139e9539775f4823f13caa19c1fe`; plan SHA-256 `1e8489f6d65d1107a97a68ecfaadf9c162ac3055098bee482dfa368186f0c404`
- Agreed review scope / prior findings disposition (for rereview): Gate 3 for I1 public contract and AC01-I1 only. I2-I4 and AC02-AC12 remain unfinished and are not approved by this review. The later current-goal pointer and the known I3 typed-plan compatibility gap are outside the retained I1 snapshot.
- Specification: `specs/DELIVERY-EXECUTION-107.md`, section `I1 public contract`
- Public seam: `harness.py admission/state/wait/prepare-merge`, `run`, and `report`
- Red command and intended failure: `python3 tests/Verification/delivery_execution_107_i1_test.py`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789239842388719000-b363810372e0471283bd8a30032c40a3.json`, exit 1, 8 methods / 103 failures. The missing `admission` command, missing scoped runner arguments, and absent `command_verdict` are genuine intended REDs. Most admission assertions were not reached because `evaluate()` first rejects the absent CLI; this evidence proves the seam is missing, not the correctness or sensitivity of the later assertions.
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **High — the existing state regression oracle contradicts the new fail-closed policy and is absent from the I1 verification mapping.** `tests/Verification/delivery_harness_001_test.py:268-291` still requires `ci.status == SUCCESS` when GitHub reports only a successful `verify` job without the required full/harness job set or any binding. The I1 contract requires missing jobs and foreign/unbound evidence to block. An implementation can satisfy the new focused test while breaking the existing suite, or preserve this weak success and violate I1. `openspec/changes/reliable-delivery-execution/verification-input.json:8-24` and the prepared plan map only the new test plus the planner governance test; they do not identify the conflicting regression expectation. Root must amend the old oracle to expect `UNKNOWN`/blocked missing obligations (and bind it to I1 verification) before implementation.

2. **High — authorization, owner-exception, and enforcement behavior is not tested as a complete positive/negative contract.** `tests/Verification/delivery_execution_107_i1_test.py:60-69` covers only absent authorization, while lines 141-151 cover only a structurally empty exception. There is no valid exact-scoped authorization/exception example and no rejection matrix for wrong head, policy, action, uncovered reason, or an exception that attempts to erase retained failures. There is also no assertion that `ENFORCEMENT_NOT_CONFIGURED` prevents an otherwise authorized autonomous merge. A blanket exception or authorization inferred from readiness could pass these tests. Add independently specified exact values for the valid case and mutate every scope dimension and covered reason; assert retained failures remain visible and autonomous action remains blocked without enforcement.

3. **High — runner truth and scoped reporting omit required observable axes.** `tests/Verification/delivery_execution_107_i1_test.py:162-192` checks only `STALE` applicability and filters report by task/run. It never checks `APPLICABLE`, `UNKNOWN`, or the separately retained applicability reason, and it does not test candidate filtering although the normative I1 contract explicitly requires `task/run/candidate`. Thus an implementation that collapses UNKNOWN into STALE, drops the reason, or combines records from different candidates passes. Add records for all applicability states with exact reason expectations, and same-task/same-run records for two candidates proving candidate isolation. Preserve raw exit/verdict/log assertions for both success and failure.

4. **High — the shared evaluator is tested only through replay input, not through a live-adapter fixture.** `tests/Verification/delivery_execution_107_i1_test.py:123-139` passes the same synthetic `--observation` file to `state`, `wait`, and `prepare-merge`; this can prove common replay semantics but cannot catch an adapter that drops GitHub job binding/status, chooses a weak required-job list, or bypasses admission when collecting live state. The existing fake-`gh` fixture at `tests/Verification/delivery_harness_001_test.py:247-291` is the natural integration witness, but currently encodes the conflicting weak-success expectation from finding 1. Add a bounded fake live publisher/API fixture with exact job/run/attempt/head/base/policy evidence and prove the real `state`/wait/merge-preparation adapters feed the evaluator and fail closed on missing or stale fields.

5. **Medium — rejection-output assertions are under-sensitive.** `tests/Verification/delivery_execution_107_i1_test.py:123-139` compares only `merge_ready` and `reasons`, and only for six defect classes; it never exercises a positive observation through `state`, `wait`, and `prepare-merge`. Duplicate-name rejection at lines 105-107 and UNKNOWN/exception rejection at lines 141-151 do not assert the required nonzero exit, CI status, or reasons. Separate divergent implementations could agree on one boolean/list while disagreeing on publication readiness, action authorization, enforcement, or exit semantics. Exercise one positive and the complete material rejection set through all four commands and compare the full normative result fields; for every rejection assert nonzero exit and a specific observable reason/status.

6. **Medium — the retained RED cannot validate the mutation matrices beyond command absence.** `evaluate()` at lines 52-58 asserts JSON before any behavioral expectation, so 101 admission-related failures stop at the same parser error. This is a valid first RED for an absent public seam, but it does not establish that missing jobs, binding fields, review independence, races, skips, or expected values are independently reached. After adding the public seam, root must obtain a fresh Gate 2 RED (for example against a deliberately incomplete/stub evaluator or through incremental focused runs) showing the corrected matrix reaches its behavioral assertions; the executor must not edit the test to manufacture that evidence.

Traceability to the stated I1 seam is otherwise clear, fixtures are isolated from production and use disposable evidence/git directories, expected required-job names are independently fixed by the normative contract, and no full local suite was run.

## Required changes

1. Resolve and map the conflicting `delivery_harness_001_test.py` CI-success oracle.
2. Complete authorization/exception/enforcement sensitivity, including exact valid and mismatched cases.
3. Cover APPLICABLE/STALE/UNKNOWN with separate reasons and task/run/candidate report isolation.
4. Add a real live-adapter fixture for state/wait/merge preparation, strengthen all public-command result/exit assertions, and demonstrate the behavioral matrix is reached in fresh RED evidence.

## Correction rereview — 2026-09-12

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T191127Z-4dbf331191/package.json`
- Corrected candidate source: `62765a05067ca0eec30ec1425cb3457993342f7a98a103e77baa4e537426643f`
- Executable source: `fea3ee13aa6c78a77a0469cb591616ae809476d4186b6a460811587deb37de24`
- Verification plan SHA-256: `b8d796bb4c7da51ed4ed0e42405fc8b85a536754f6ce08ba24b74d6784a5e9c3`
- Verdict: `APPROVED` for I1 / AC01-I1 only. I2-I4 and AC02-AC12 remain unfinished and are not approved.

### Findings disposition

1. Resolved: `tests/Verification/delivery_harness_001_test.py` now rejects verify-only, unbound CI as `UNKNOWN`, is included in verification input/plan, and retained evidence shows exactly that changed assertion RED while the other 24 methods pass.
2. Resolved: the corrected suite supplies exact owner-controlled authorization and scoped exception positives, mutates head/policy/action/actor/reasons, preserves `original_failures`, and proves autonomous authorization is blocked when enforcement is not configured.
3. Resolved: APPLICABLE, STALE and explicit UNKNOWN have reason assertions; report filtering now distinguishes two candidates sharing task/run scope.
4. Resolved: a disposable git repository plus native fake-`gh` PR/run/jobs payload exercises live `state`, `wait --once`, and `prepare-merge` for healthy, missing-job, wrong-attempt, wrong-base and foreign-head observations. Replay remains explicitly distinct from live evidence.
5. Resolved: replay evaluation compares every normative result field and exit across all aliases; every non-merge-ready result must be nonzero with reasons.
6. Resolved with corrected Gate interpretation: Gate 3 does not require production seam implementation or a shipped stub. The absent public seam remains genuine retained intended RED; test-only fixture witnesses independently validate the observation shapes and mutations. Behavioral assertions become reachable against production at Gate 4 without permitting the executor to edit approved tests.

### Evidence

- `python3 tests/Verification/delivery_execution_107_i1_test.py`: retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789240241122560000-a24bf6a99fa9497f9e58fd52f470d799.json`, `INTENDED_RED`, exact corrected source. The fixture-witness method passes; failures are the missing admission/live/scoped-runner behavior and absent command/applicability fields.
- `python3 tests/Verification/delivery_harness_001_test.py`: retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789240241135887000-b9166c27ef8848259f693e9241ba9b17.json`, `INTENDED_RED`, 25 methods with exactly one failure: current code incorrectly returns `SUCCESS` for verify-only CI instead of required `UNKNOWN`.

No remaining findings in the corrected I1 scope. Gate 4 may proceed without changing these approved expectations. No full local suite was run.

## Post-implementation test delta Gate 3 — 2026-09-12

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T192357Z-a7cd9669b2/package.json`
- Candidate source: `815a21486728eb2d2915a54a218be3487a1699a3695c3c804ebac4ac16ac6a69`
- Executable source: `7f57b935a1bc08f834e591d3c925257c0c8e8f2f65ef3474a7ffe563ddb6b2f7`
- Verification plan SHA-256: `e1d7abf77dce25610f5e74a77ba207c8bfac755d605f77bff21ff66fab1c3dbe`
- Scope: test-only review of three new I1 witnesses derived from the already approved publisher-job, fail-closed enforcement, and action-scoped exception contract. Production and all I2 artifacts are outside this verdict.
- Verdict: `APPROVED`; no findings.

The tests are sensitive to three distinct plausible regressions: a benign publisher job must neither replace nor block required jobs; unrecognized enforcement representations must not authorize autonomous action; and a merge-scoped exception may not make failed publication preflight ready. Expected values are explicit at the public admission seam, each negative preserves the surrounding otherwise-healthy observation, and the publisher test also proves the extra job cannot substitute for missing `verify`.

Retained evidence `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789240975104117000-b6a93ee3f4f0476db4fd2dd9bb77d3d3.json` is exact-source `INTENDED_RED`: 15 methods ran, exactly the three new witnesses failed, and the other 12 passed. Existing regression evidence `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789240975103059000-319cd60b749840b1b2a02568ddcc1a6c.json` is honestly mapped `GREEN`: 25/25 methods. The failures reach the intended assertions rather than setup or an absent seam. Gate 4 correction may resume without changing these tests. No full local suite was run.

## Gate 5 correction matrix Gate 3 — 2026-09-12

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T194645Z-7ac90fde94/package.json`
- Candidate source: `0c4dbf8ab0287190a4abcc19bcea850641385f835f6d157d61e67c1e7f1fe62f`
- Executable source: `3fba03c155dd1e82303c6f462807dc925b44d138c9bbcc0537bc4ec7eefd853a`
- Verification plan SHA-256: `7d922426bbce2a49343be92aec7dbe64b016fa33b0085afbcfd033f02c11ed66`
- Scope: I1 spec/test correction only. I2 spec/tests/registry/workflow expectations in the snapshot are excluded and require their own Gate 3.
- Verdict: `APPROVED`; no findings.

The clarified exit contract is coherent: live `state` is a diagnostic read returning 0 for valid status JSON without implying readiness or GREEN; admission, replay state, wait and merge preparation retain nonzero denial semantics. New fixtures cover typed exact identities, PR number/head/base association, pull-request event, selected run ID/attempt/workflow, shared SessionStart state, fully task/run/candidate-scoped event aggregates, and normalized enforcement. They use disposable repositories and a native fake-`gh` boundary without external mutation.

Retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789242290165610000-d171e71d2def4a8da388f7035ce2203e.json` is exact-source `INTENDED_RED`: 17 methods ran and the new assertions reach the current implementation defects (23 assertion/subtest failures), rather than failing setup. Existing harness regression record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789242290165616000-1e5469e50aa24c2e9a9427d6dcc90d2a.json` remains honestly `GREEN`, 25/25. Executor may correct I1 production without changing these expectations. No full local suite was run.

## Native GitHub job-shape delta Gate 3 — 2026-09-12

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T200200Z-142d36cc23/package.json`
- Candidate source: `9c3d2c45566f4cac09088883a3ef7623748ca9c1345870e00d7d02bab92c8e2a`
- Executable source: `78f8e404e9e095a1d79430f1c5f957aed43daa69554ff42d9b869a9824a3b9e4`
- Verification plan SHA-256: `f0c7b14322118e66eaed8cd9d4a63927d5fb3f953fd8232a4a240187bd3a9953`
- Scope: I1 native job-shape spec/test delta only. I2 changes are excluded.
- Verdict: `CHANGES_REQUESTED`

### Finding

1. **High — required skipped-job obligations remain untested.** `test_every_missing_skipped_failed_cancelled_or_pending_required_job_blocks()` skips every job whose healthy conclusion is `SKIPPED`. Consequently harness/docs tests never remove or alter `unit`, `governance`, `fast`, `e2e`, the literal skipped `Integration (${{ matrix.shard }}/2)`, or other mode-required skips. `test_real_github_job_payloads_preserve_selected_matrix_shape()` proves the real payload is accepted and that missing `verify` blocks, but it does not prove any skipped entry is required: an evaluator requiring only the three successful jobs and treating all skipped jobs as benign extras would pass. This violates the contract that each skip must match the mode and missing jobs block. For every required skipped job, add at least missing and unexpected non-SKIPPED mutations (including the literal integration job) in harness and docs modes, with nonzero denial and non-success CI assertions.

The captured full/harness payload shapes and the explicit limitation that historical empty PR arrays are replay-only are otherwise sound. Retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789243149546087000-4fd515b63d66463c847dea2fef09c99a.json` is genuine reached `INTENDED_RED`; existing harness record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789243149560525000-f20f2c9e37784c479436a0d4ca8e601d.json` is `GREEN`. No full local suite was run.

### Required change

Cover removal and wrong conclusions for every mode-required skipped job, then retain fresh reached RED before executor correction.

## Native GitHub job-shape correction Gate 3 — 2026-09-12

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T200718Z-8d0275bb54/package.json`
- Candidate source: `9fc559a7c6ef919dd82f9ef50e1fcaeea4aad7312f144c51b62c87600abf10d7`
- Executable source: `08a72ef6aa7dc4e61db554114efde3cb7a19517edfa8e1848e9f06ad9205308d`
- Verification plan SHA-256: `6cdefae70edabf693e447dc5422eb52f0307940ced07223c46e83be5524fff08`
- Scope: corrected I1 native job-shape mutation matrix only; I2 remains excluded.
- Verdict: `APPROVED`; no findings.

The corrected matrix now visits every expected job in full, harness and docs modes. Jobs expected to succeed are mutated to missing/SKIPPED/FAILURE/CANCELLED/PENDING; jobs expected to skip are mutated to missing/SUCCESS/FAILURE/CANCELLED/PENDING. This includes the literal unexpanded `Integration (${{ matrix.shard }}/2)` obligation, so ignored skipped jobs or publisher extras cannot substitute for the canonical mode policy.

Exact-source record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789243440521708000-136e6996625b408d8898f78cbb47d7c9.json` is reached `INTENDED_RED`: 18 methods ran and current production fails the corrected harness/docs policy assertions, including expected-skipped mutations. Existing harness regression record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789243440521272000-6a40b43c51de48be8d0d6b16a2ec5dd9.json` remains `GREEN`, 25/25. Executor may correct the canonical CI mode helper without changing these approved tests. No full local suite was run.

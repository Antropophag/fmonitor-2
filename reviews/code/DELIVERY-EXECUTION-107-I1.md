# Code review: DELIVERY-EXECUTION-107 / I1

- Reviewer: independent `review_i1` agent (`gpt-5.6-sol`, low)
- Implementation author: `/root/executor_i1` (production only)
- Reviewed source: base `b6fe81c3bec74a9dcfad54bc12f4b1063359ed6e`; retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T193743Z-ac6f7ef1a2/snapshot`, patch SHA-256 `ba84d3099fcb0dcb5cedb4c622a3eff0f59f83f42d155e418f9feaf04a821014`; candidate source `1294b110dea3dd532dc4b0734054402e8122fda0e92bea36e732527599ca91ac`; executable source `d1ec609ce0c3ad8101f81cc1dd7f396876781378e80acbdbae3e5614d1e328ff`
- Scope: I1 / AC01-I1 production in `tools/delivery/admission.py`, `harness.py`, `harness_context.py`, `verify.py`, `tools/verification/ci.py`, and I1 inventory entries. I2 scaffolding, I2-I4 implementation, full CI, PR and deployment are excluded.
- Specification: `specs/DELIVERY-EXECUTION-107.md`, I1 public contract
- Approved tests: `tests/Verification/delivery_execution_107_i1_test.py`, `tests/Verification/delivery_harness_001_test.py`; Gate 3 record `reviews/tests/DELIVERY-EXECUTION-107-I1.md`
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **High — live `state` turns a blocked admission into exit 0, contrary to the shared public contract.** In `tools/delivery/harness_context.py`, `command_live_admission()` returns 0 unconditionally when `args.command == "state"`, even when `merge_ready` is false. Replay `state` returns 1 through `command_admission()`, while live `wait`/`prepare-merge` also return 1. The I1 contract says rejection has a nonzero exit and the same evaluator backs live state/wait/merge preparation; callers can therefore mistake a blocked live state for accepted execution. Remove the live-state exception (or change the normative contract and independently reviewed expectations, which would restart Gate 2); add a live adapter assertion for the blocked exit.

2. **High — the native GitHub adapter can admit a workflow run that is not bound to the PR.** `_native_github_observation()` selects the newest workflow run solely by matching `headSha`, then validates `pull_requests` only when that array is nonempty. A push/manual run on the same SHA with an empty `pull_requests` array is accepted and its binding is synthesized with the current PR number/base. It also does not verify the PR number when `pull_requests` is populated. The contract requires exact PR/head/base/workflow/run/attempt provenance. Require exactly the intended PR association (number, head and base) from the run and reject absent/ambiguous/foreign PR bindings; add native fixture cases for empty and wrong-number `pull_requests`.

3. **Medium — report scoping filters check records but leaks all event aggregates.** `tools/delivery/harness.py:report()` applies task/run/candidate filters only to `_records()`. `tool_calls`, `agent_tasks`, `review_returns`, and `observed_tool_output_bytes` are still computed from every event in the evidence home. The I1 contract says report filters task/run/candidate, so a scoped report can mix other tasks/candidates into its metrics and misstate efficiency. Apply equivalent scope metadata/filtering to events, or explicitly return scoped fields as UNKNOWN when historical events lack the required identity; add isolation witnesses for event aggregates, not only `checks`.

4. **Medium — unknown enforcement is not normalized to the required public status.** `admission.evaluate()` uses `observation.get("enforcement") or "ENFORCEMENT_NOT_CONFIGURED"`, so truthy unrecognized values such as `"UNKNOWN"`, `"unverified"`, `True`, or an object are echoed in the response. They correctly fail to authorize autonomous action, but the normative output requires unconfigured/unverified enforcement to display `ENFORCEMENT_NOT_CONFIGURED`. Accept only the explicit configured sentinel and normalize everything else; assert the returned field as well as `action_authorized`.

## Required changes

1. Make blocked live state return nonzero consistently with replay/wait/prepare-merge.
2. Require exact PR association for the selected native GitHub run.
3. Scope or honestly mark UNKNOWN all report aggregates, not just records.
4. Normalize every unrecognized enforcement representation to `ENFORCEMENT_NOT_CONFIGURED`.

## Verification evidence reviewed

- Exact candidate GREEN: I1 suite record `1789241725112822000-ab3a000b755047ddb2d1c7ca37ecd073`; harness regression record `1789241725112832000-69a96c8a32f14d7a892d643d83366c29`; planner governance record `1789241749670158000-4ecca667133d42e5854ca2dac3a33eef`.
- Inventory record `1789241411910160000-3221167be9a6477fae2d81c610f4a3f7` is GREEN with the same executable digest.
- CI policy record `1789241437942245000-ca9adf8992634c6b9d7dbb42e31b8fe4` retains raw child 0 / command verdict GREEN but is honestly `STALE` and overall `UNKNOWN` after review-only metadata drift; it is not counted as current GREEN approval.
- No full local suite was run. GREEN focused evidence does not resolve the source-level findings above.

## Final correction Gate 5 — 2026-09-12

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T201346Z-a75156e3b7/package.json`
- Retained candidate source: `d2a6ca3b41dd45cb9cd266347270766954f3423a4b2fda893a25fbd6d9256cf1`
- Executable source: `fd8d23ffd366d30908d273a4c49728486386dc795a3541c37f0c33de9d2d3d4b`
- Verification plan SHA-256: `b314ab4254905e7faeda1d730a97292d0fdd8b08990721ff2c4d4ab23caac99b`
- Scope: complete I1 / AC01-I1 correction production and approved tests. I2 contracts/tests/workflow expectations, I2-I4 implementation, full CI, PR, merge and deployment remain excluded.
- Verdict: `APPROVED`; no remaining I1 findings.

### Prior findings disposition

1. Resolved by normative clarification: live `state` is a diagnostic read and returns 0 only after emitting valid status JSON; it does not imply readiness or GREEN. Admission, replay state, wait and merge preparation retain nonzero denial. Tests preserve this separation.
2. Resolved: native discovery uses exact `gh run list --commit`, validates selected/API run ID and attempt, workflow path, pull-request event, and exactly one associated PR number/head/base before CI can be SUCCESS. Missing, foreign, push/manual and stale identities fail closed.
3. Resolved: task/run/candidate scope now filters records and every event aggregate; unscoped or foreign events are not attributed to the selected report.
4. Resolved: only the two recognized enforcement sentinels survive; every other representation normalizes to `ENFORCEMENT_NOT_CONFIGURED` and cannot authorize autonomous action.

The SessionStart hook obtains CI through the same strict native observation/evaluator instead of the legacy rollup; fallback is UNKNOWN. Canonical expected-job ownership covers full, harness and docs shapes, including both expanded full Integration shards and the literal unexpanded skipped Integration matrix job in harness/docs. Required successes and required skips are both enforced, while publisher extras neither replace obligations nor block a complete matrix. Owner exceptions remain exact merge-scoped preflight exceptions, preserve original failures, and cannot waive publication, CI, review, binding or race failures.

Exact-source focused evidence is GREEN: I1 suite record `1789243830935048000-527e85422e3d4415afebd63f70f2dec6`, harness regression record `1789243920294319000-6af107ac3c3548868eb62aa52937dc9c`, and planner governance record `1789243963216230000-d07d95c46aea45c68d072b4909110386`. No full local suite was run. This approves I1 only and is not approval of complete #107.

## Reconstruction Gate 5 — 2026-09-13

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T072648Z-a439234a3a/package.json`
- Reviewed candidate source: `12632f47297456478912c8978381954db0a802c781f70d14a4f0093e5ca90040`
- Executable source: `eb442decbb54616d8f7a34472d4e6137ed68ce54ba3b8802366f52af9f244458`
- Scope: standalone reconstruction of I1 / AC01-I1 on fresh `origin/main`; I2-I4 are excluded and findings outside I1 are follow-up work.
- Verdict: `CHANGES_REQUESTED`.

### Findings

1. **High — the I1-only verification inventory still contains an absent I2 executable.** `tools/verification/categories.json:363` and `tools/verification/suites.tsv:376` register `tests/Verification/delivery_execution_107_i2_test.py`, but that path is absent from the reconstructed candidate. This both couples the standalone slice to excluded I2 and leaves canonical inventory execution pointing at a nonexistent test. Remove only those two I2 registry rows and retain the adjacent I1 rows; freeze a new source and repeat the mapped focused evidence, including any inventory check required by the refreshed plan.
2. **Medium — the reconstructed repository review record omitted the append-only final historical approval.** Before this reviewer-owned record correction, `reviews/code/DELIVERY-EXECUTION-107-I1.md` ended after the superseded `CHANGES_REQUESTED` review and did not retain the final correction approval and findings disposition. The historical section above restores that record. This documentation correction does not approve the candidate; the inventory correction and a new exact-source Gate 5 package remain required.

The reviewed production hunks otherwise preserve the approved standalone I1 behavior on fresh main and introduce no Docker or `execution_environment` path. All three supplied records are exact-source `GREEN`, `APPLICABLE`, and `source_drift=false`: I1 18/18, harness 25/25, and change-verification 16/16. They do not waive the two findings above. No full local suite was run.

## Reconstruction final correction rereview — 2026-09-13

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T073318Z-54fb52dc30/package.json`
- Base: `1ce54b04f37d234a5b951cb53eb40a0b30e9b4a8`
- Candidate source: `98909c6b60bbd8e93fc07e0c3690314c2ed294d8129d2eba00c634752478cea3`
- Executable source: `468c0d6d48404b4b0c2d5856f081cd6e9c34c637dbfefad8cb96591cf2577aca`
- Scope: standalone I1 / AC01-I1 reconstruction on fresh main; I2-I4 remain excluded.
- Verdict: `APPROVED`; no remaining findings in I1 scope.

### Prior findings disposition

1. Resolved: `tools/verification/categories.json` and `tools/verification/suites.tsv` now register only `tests/Verification/delivery_execution_107_i1_test.py`; the absent I2 executable and its registry coupling are removed. The candidate contains no Docker profile, container-envelope or `execution_environment` implementation.
2. Resolved: the repository record retains the original `CHANGES_REQUESTED`, the complete historical `Final correction Gate 5 — 2026-09-12` approval and findings disposition, and the reconstruction review history above. This rereview is appended separately and does not rewrite prior outcomes.

Exact-source focused evidence is `GREEN`, `APPLICABLE`, and `source_drift=false` for all four reviewed records: I1 18/18 (`1789284647032359000-de2b6c3d491f418b8cdf26563a6304bc`), harness 25/25 (`1789284715482637000-65270ca5c80b4e2a8d12c7468e71c9fb`), change-verification 16/16 (`1789284751427293000-30deec53d569444782aafeb015f318d7`), and architecture guard 59/59 (`1789284769993686000-0d472e43a8f64543aef189b87cad63ad`). No local full suite was run.

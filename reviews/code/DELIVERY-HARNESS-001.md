# DELIVERY-HARNESS-001 — Gate 5 code review

- Gate: 5 — independent bounded code review
- Reviewer: separately tasked `/root/consumer_evidence`; reviewer authored neither implementation nor tests
- Review package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T152512Z-01fa597982/package.json`
- Restored source: `/private/tmp/fmonitor-harness-gate5-review`
- Base: `4bab010f157d352f50d6b94050d80cb49d40a8a9`
- Candidate source identity: `3d13d01b3273ad04310321f55dd807684602a648476bd7f2a386c0886a16c84f`
- Package approval field: `NOT_REVIEWED` (correct; preparation did not self-approve)
- Verdict: **CHANGES_REQUESTED**

## Finding

### HIGH — confirmed InspectionEvidence consumers are attached to a nonexistent exact owner

`.quality-graph/verification-policy.json` declares the consumer owners as:

```text
app/InspectionEvidence/InspectionEvidenceService.php
app/PilotHttp/ChecklistSync.php
```

The first path does not exist in the candidate or current repository. More importantly, `tools/delivery/change-verification.py` selects a consumer only with `owner in effective`; it does not interpret owner entries as patterns. The contract test uses the same invented `InspectionEvidenceService.php` string, so it passes while none of the real shared files such as `app/InspectionEvidence/MariaDbYiiChecklist.php`, `MariaDbYiiChecklistAdmission.php`, `MariaDbYiiChecklistMutation.php`, `MariaDbYiiChecklistPhoto.php`, `InspectionEvidence.php`, or `YiiChecklist.php` selects the schema/upload/rejection/revoke/concurrency family.

This defeats R4 and the owner's explicit next-task acceptance criterion. PR88's recorded failures were caused by changes in those real shared owner paths, and the new mechanism would still require an agent to remember the five consumer suites manually.

Required correction:

1. Represent the confirmed InspectionEvidence owner relationship with actual supported patterns, for example `app/InspectionEvidence/**`, while keeping `app/PilotHttp/ChecklistSync.php` exact. Extend policy validation and consumer matching with the same unambiguous `fnmatch` semantics already used for boundaries, or enumerate the actual shared owner files if the intended scope is narrower.
2. Replace the fictional positive test path with at least two existing representative owners from the PR88 correction, including a split trait such as `app/InspectionEvidence/MariaDbYiiChecklistAdmission.php`; retain the exact ChecklistSync positive and local PilotHttp presentation negative.
3. Preserve fail-closed handling for malformed/ambiguous owner patterns and retain the five already registered consumer tests. Do not broaden this to all PilotHttp or all E2E.

## Reviewed evidence and unaffected conclusions

The reviewer package is reconstructible and contains all five mapped acceptance GREEN records with one exact source and environment; the sixth focused architecture guard is also GREEN in `/tmp/fmonitor-harness-final-focused.json`. Full logs remain outside the repository. The package correctly remains `NOT_REVIEWED`, rejects stale source/environment and irrelevant argv, distinguishes Gate 3 intended RED from Gate 5 GREEN, and checks all mapped obligations.

The compact runner preserves child exits, full stdout/stderr, source/fixture/environment identities, interruption/setup/UNKNOWN precedence and sequential diagnostic inventory. CI/native wrappers reject a setup outcome even when the child exits zero and continue selected inventory. No GREEN cache or parallel shared-fixture execution was introduced.

State separates dirty/exact source, PR, CI, merge and deployment; unavailable GitHub is UNKNOWN with last-check metadata, and merged state does not request publication. Current goal now contains owner intent/queue and historical mutable state is retained in a dated snapshot.

The additive native installer preserves same-event foreign hooks, is idempotent, rejects invalid JSON unchanged, scopes dispatch by registered Git common directory and leaves unrelated repositories silent. Actual Codex 0.154.0 startup, ordinary task and resume receipts share fingerprint `5f597ee4afb1e27e44287b54c1d8e2b7de31b6f94b3f53cd6891f9327ac5eff9`; `/tmp/fmonitor-harness-baseline/native-hook-smoke-summary.json` asserts all three. Five hooks were active through native trust, with no alternate execution backend or required upgrade.

The measurement record accurately reports 240,028 retained raw bytes versus 3,637 emitted summary bytes (98.48% smaller for the same logs) and explicitly does not infer token, money, weekly-limit or total-task savings. Current root/subagent token telemetry remains UNKNOWN because the installed supported hook interface exposes no usage fields; injected usage is ignored.

No product code, product test, stand, merge or deployment is changed by the candidate. No full architecture audit or duplicate full test was run during this review. After the HIGH finding is corrected, recapture an exact package, rerun the affected planner/harness focused checks, and request an independent Gate 5 delta before PR/full CI.

## Gate 5 delta — consumer owners

- Review package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T153410Z-56c191dae3/package.json`
- Restored source: `/private/tmp/fmonitor-harness-gate5-delta-review`
- Candidate source identity: `20b41c9be853d0c0b7a77a4f093f3e9a02190e27ea22be9f2ba23eb6dd46b4e4`
- Previous snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T152512Z-01fa597982/snapshot`
- Package SHA-256: `ea93d5962126dbe4f0a02b775e0d65a14e859acb4b388b75005585a30672d2fa`
- Delta SHA-256: `ea9391cea4f9ac17c36d4111f155ea9f5b3b35e98f4e26b1a9a245181336fbee`
- Package approval field: `NOT_REVIEWED`
- Verdict: **APPROVED**

The HIGH finding is closed. Policy now uses `app/InspectionEvidence/**` and the exact `app/PilotHttp/ChecklistSync.php`. Planner consumer selection applies case-sensitive `fnmatch` to each effective path, rejects more than one matching consumer relationship as ambiguous, validates nonempty owner patterns and retains the existing registered five-test family. It does not attach all PilotHttp or E2E checks.

The approved test now drives two real owners, `MariaDbYiiChecklist.php` and split `MariaDbYiiChecklistAdmission.php`, and asserts that those source files exist before requiring all five schema/photo consumers. Exact ChecklistSync remains positive and `PilotHttp/LocalView.php` remains negative. This would fail both the former invented exact path and an overbroad PilotHttp mapping.

The new package correctly links the previous reviewed snapshot and original findings and contains a generated reproducible delta. All five mapped acceptance records are GREEN on exact source and environment; `/tmp/fmonitor-harness-final-focused-v2.json` also records the required architecture guard GREEN. The package remains `NOT_REVIEWED`, so this verdict comes only from the independent review.

No other implementation was changed in the delta, and the prior native integration fingerprint remains applicable. The complete bounded candidate is **APPROVED** for commit, PR and one full exact-source CI. This does not authorize merge or deployment; any source change after this snapshot requires new applicable evidence and review.


# DELIVERY-HARNESS-001 / PR89 bounded fixes — Gate 5

- Gate: 5 — independent bounded code review
- Reviewer: `/root/consumer_evidence`; reviewer authored neither code nor tests
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T165727Z-523fc57004/package.json`
- Base: `98c6eceeac8d606e0215b1d59354d2d2c9f5d8b4`
- Candidate source identity: `ee0a059d04c4a78816ba664922c77bb84e7aabc94f2fecfbc68ec20aea1d613b`
- Restored source: `/private/tmp/fmonitor-pr89-gate5-review`
- Package approval: `NOT_REVIEWED`
- Verdict: **CHANGES_REQUESTED**

## Finding

### HIGH — namespaced active plan is outside the resolver's allowed filename set

PR89 correctly namespaces mutable binding/state by canonical worktree key. On resume, `harness_context._refresh_active_binding()` writes:

```text
<evidence>/state/active-verification-plan-<worktree-key>.json
```

and stores that path in the active binding returned by `state` and injected into resume context. However, `change-verification.plan_path()` permits an absolute trusted state artifact only when `resolved.name` is exactly `active-verification-plan.json` (or `verification-plan.json`). It therefore rejects every newly generated namespaced active plan before `checked_plan`, `refresh`, or `run` can use it.

The interleaved-worktree test checks that the two JSON files differ and contain the correct change, but only opens them directly with `Path(...).read_text()`. It never passes either returned `state.active_binding.plan` through the public change-verification CLI. Thus the test is GREEN while the normal resume route points agents to a plan that its own runner rejects.

Required correction:

1. Admit only the exact generated namespaced grammar in the trusted state directory, such as `active-verification-plan-[0-9a-f]{20}.json`, while retaining canonical containment, lexical traversal and symlink escape rejection. Do not admit arbitrary state JSON names.
2. In the linked-worktree test, run `change-verification check --plan <state.active_binding.plan>` from each corresponding worktree after interleaved prepare/resume and require success. Retain distinct change/package assertions. A stale mutation may still require refresh before check as appropriate, but the exact plan emitted by the completed resume refresh must be usable.

## Closed portions and evidence

The rest of the bounded implementation conforms to the approved PR89 matrix. `gate3_expected` is part of the existing acceptance mapping, validates exact test keys/outcomes, supports mixed RED/GREEN Gate 3 with at least one RED, and leaves Gate 5 all-GREEN. Arbitrary GREEN cannot replace a declared RED obligation.

Generated package plans under the trusted packages directory work through check/refresh/run. External copies, lexical traversal, symlink escape and source-input traversal remain rejected. The above finding concerns only the newly generated trusted state filename and does not weaken those guards.

GREEN runner delivery contains only id/outcome/record navigation; full metadata and streams remain in the retained record. The actual six-stream replay verifies identical input hashes and reduces delivered bytes from 8,547 before to 1,504 after for 6,860 raw bytes. This is measured output transport reduction, not token or quota savings. Failure diagnostics remain bounded and CI readers retain full streams. The strict line marker correction separately fixes the two fully inventoried first-CI false UNKNOWN classifications.

The package contains five exact-source/environment mapped GREEN records: harness 24 tests, planner 16, CI 16, native 10 and inventory 16. Architecture guard is GREEN in `/tmp/pr89-fixes-architecture-summary.json`. The package correctly links the previous approved snapshot and owner findings and remains `NOT_REVIEWED`. No new full suite was run by this reviewer while the owner-authorized isolated local full run was in progress.

After the active-plan resolver/test correction, recapture the exact package and provide affected focused GREEN for a narrow Gate 5 delta. Final completion still requires the already running full local regression to finish successfully and a new full exact-source GitHub CI. No merge or deployment is authorized.

## PR89 final Gate 5 delta — active plan

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T172504Z-d7d428e534/package.json`
- Previous snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T165727Z-523fc57004/snapshot`
- Restored source: `/private/tmp/fmonitor-pr89-final-gate5-review`
- Candidate source identity: `62d35eb0c17a7a1ab3a4fd73b04bc7dc25a6675241f6e7b0c76a43b48f020584`
- Plan SHA-256: `69493e5a19eea7e81c40ab495adadc916173952702bfa4ecb3744e6c7113461e`
- Package approval: `NOT_REVIEWED`
- Verdict: **APPROVED**

The HIGH finding is closed. `plan_path()` now admits a state artifact only when its basename exactly matches `active-verification-plan-[0-9a-f]{20}.json`, in addition to the existing generated package filename. Canonical containment under the trusted evidence packages/state directories, lexical `..` rejection, direct symlink rejection, file existence and strict source/input bindings remain unchanged. Arbitrary state filenames are not admitted.

The linked-worktree test now sends each worktree's returned `state.active_binding.plan` through that worktree's actual `change-verification check --plan` public seam and requires success, while retaining distinct package/change/context assertions. The real worktree evidence `/tmp/pr89-real-active-plan-check.json` additionally shows refresh exit 0 with `obligations_changed:false` and subsequent `CHANGE_VERIFICATION_OK` for `active-verification-plan-87e07261e8ed6734c1d1.json`.

All six focused commands are GREEN on exact source `62d35eb…`: the five mapped suites and architecture guard in `/tmp/pr89-final-fixes-focused.json`. The package correctly links the prior reviewed snapshot and findings and supplies a reproducible delta. The final spec/test bytes retain the independent Gate 3 approvals; the R1 sentence merely consolidates the already approved three-field GREEN transport contract. No product behavior or broader architecture changed.

Native startup/task/resume/worktree-binding proof remains current in `/tmp/pr89-final-native-smoke.json`; the planner-only resolver correction does not alter its integration files. The first local full run's 359 checks plus `VERIFY_OK` apply to prior source `ee0a059d…` and are preserved honestly. This exact candidate is **APPROVED** for final commit. Completion still requires a new full local regression and full GitHub CI on that final commit SHA; neither future result is predicted here. Merge and deployment remain unauthorized.

## Public runner fail-closed exit — Gate 5 delta

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T181614Z-5f5add9d23/package.json`
- Previous snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T172504Z-d7d428e534/snapshot`
- Restored source: `/private/tmp/fmonitor-pr89-exit-gate5-review`
- Candidate source identity: `956023e48ce0689a7b9b79a36c5ab8793d2578f4a91e7dd93f711ed2d4b9b14a`
- Plan SHA-256: `2f6dda5814d7b18b8b477446da643d538c58a083b22dc678aff45dabfe3c60de`
- Package approval: `NOT_REVIEWED`
- Verdict: **APPROVED**

The implementation changes only public exit selection and provenance recording. `cli_exit` is zero exactly for GREEN; every other outcome returns the existing nonzero child code, or 1 when the child actually exited zero. Retained `exit_code` and `raw_child_returncode` remain child facts, while `cli_exit_code` records the public harness status. The failure summary exposes both child and CLI exits; GREEN keeps its approved three-field delivery.

`/tmp/pr89-public-exit-scenarios.json` confirms the full affected matrix: explicit SETUP_FAILURE/UNKNOWN with child/raw 0 return CLI 1; domain UNKNOWN text remains GREEN/0; ordinary failure, intended RED and pipefail preserve 7; SIGTERM preserves child/CLI 143 with raw -15; timeout preserves 124 with raw -15. Marker grammar and outcome precedence are unchanged.

Existing downstream guards remain necessary and correct because they interpret retained child provenance as well as outcome. CI converts a non-GREEN child 0 to effective category failure, while the native shell independently rejects non-GREEN even if a future boundary regression returned zero. No guard was removed and no product expectation was weakened.

All five mapped suites are GREEN on exact source `956023e4…` (harness 25, planner 16, CI 16, native 10, inventory 16). The actual architecture check and architecture guard are GREEN in `/tmp/pr89-exit-architecture*.json`. The package links the prior reviewed snapshot and bounded findings and remains `NOT_REVIEWED`; this independent verdict supplies approval.

This public-exit delta is **APPROVED** for commit. The historical local run with two unrelated failures remains FAILED, the isolated v23 pass does not invent a cause, and old CI 34508656112 is not evidence for this source. Completion requires the latest requested focused/governance/architecture matrix and a new full exact-source GitHub CI after commit. Merge and deployment remain unauthorized.

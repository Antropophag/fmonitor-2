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

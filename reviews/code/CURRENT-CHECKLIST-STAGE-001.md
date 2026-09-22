# Gate 5 — CURRENT-CHECKLIST-STAGE-001

## Verdict

`APPROVED`

## Exact source and independence

- Base: `0504d2589835f2583dc9afdbc47e4694e2573365`.
- Reviewed candidate HEAD: `957c3ba3ea5c80d38d456aeac59d97e0424a296c` (`Record current checklist stage review`) plus the bounded correction captured by the prepared package.
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T162813Z-774aa05fdd/package.json`.
- Package candidate source: `20157be3798ed765d93caaef4f560ed26eab337fa18ee140a2ba15a3bd695159`; executable source: `9db46837e4cbce3a711f6dbc396bd54998b7918672cf95459dd6d0a5d9a5f592`.
- Reconstructible package snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T162813Z-774aa05fdd/snapshot/source.patch`, SHA-256 `130e676a6f81cd7e152693ea4f53eacdf57e2c05ac15cc1a136515817f510a9b`.
- Specification and executable acceptance test author: root Codex session. Production implementation author: independent executor `/root/implement_current_stage` (gpt-5.6-sol/low). Gate 3 reviewer: `/root/gate3_current_stage` (gpt-5.6-sol/low).
- Gate 5 reviewer: independent Codex sub-agent `/root/gate5_current_stage` (gpt-5.6-sol/low). This reviewer authored neither the specification, test, nor production implementation and changed only this review record.

## Scope reviewed

The full candidate diff from the pinned base, `CURRENT-CHECKLIST-STAGE-001`, its OpenSpec proposal/design/delta/tasks, the Gate 3 record, mandatory prepared-package context, both changed production files, both affected queue tests, suite registration, verification input, delivery record, first-CI failure inventory, and retained verification evidence were reviewed. The refreshed review specifically covers the correction in `tests/Yii2/yii2_object_queue_lineage_001_test.php`; production is unchanged from the previously approved implementation.

## Findings

No findings. There are no Critical, High, Medium, or Low corrections required.

The implementation at `app/InstallationProcess/MariaDbYiiObjectQueue.php:81,150-164` replaces historical distinct completion counting with one shared current-state expression. It selects the latest completion-changing operation for each `(installation_case_id,item_id)` by `accepted_revision`, then `id`; only a latest `item_completed` contributes, item 42 remains excluded, and attribution-only operations remain irrelevant. Both ordinary queue stage filters and `stagePredicates()` consume that expression before the shared SQL `COUNT`, `LIMIT`, and `OFFSET`. `app/InstallationProcess/MariaDbYiiOperationalDashboard.php:89-100` continues to consume those same stage predicates, so dashboard values and queue drill-down totals share the same meaning. Existing documentary-completion and `needs_assignment_change` precedence is preserved. No writer, history, schema, authorization, API, or unrelated product surface changed.

The correction at `tests/Yii2/yii2_object_queue_lineage_001_test.php:18` replaces only the stale assertion that deliberately characterized the former defect. It now requires the retracted case to leave `document_closeout` and additionally requires that same case to enter `installation`. The independent pre-retraction matrix at lines 13-16, displayed-card assertion, read-only fact check, documentary-completed neighbor, and `needs_assignment_change` coverage remain intact. Coverage is therefore aligned and strengthened, not weakened. `openspec/changes/fix-current-checklist-stage/verification-input.json:9` includes this changed registered test in the bounded planned paths, and the refreshed planner package selects it as a local semantic-integration obligation.

## Verification and evidence assessment

- Package-bound refreshed-source acceptance evidence is GREEN for `php tests/Yii2/yii2_current_checklist_stage_001_test.php`: record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790094460378509000-d9cd2aa6144c4b4a9632dc8797e00f74.json`.
- Independent review rerun of that focused acceptance test: GREEN, `PASS: CURRENT-CHECKLIST-STAGE-001 A-J current-state stage consistency`.
- Independent PHP lint of both changed production files and `git diff --check`: GREEN.
- Gate 3 is `APPROVED`; its intended RED isolated the historical-count defect and its corrected test covers equal-revision `id` tie-breaking, retraction/recompletion, duplicate completion, attribution-only operations, adjacent stages, pagination, and dashboard/filter parity.
- The delivery record reports the planner-selected governance and architecture checks GREEN. The canonical full local suite was correctly not run under owner policy.
- The complete first-CI failed-job inventory for run `35752011089` at published HEAD `957c3ba3ea5c80d38d456aeac59d97e0424a296c` is: primary `Integration (1/2)` failure, downstream aggregate `verify` failure, and downstream `Quality Graph` failure. The complete `REGRESSION_FAILURE` inventory contains exactly one item: `tests/Yii2/yii2_object_queue_lineage_001_test.php`. Its expected `[451205,451206]` versus actual `[451206]` proves the production behavior matched `CURRENT-CHECKLIST-STAGE-001` while the old characterization expectation was stale. The integration shard continued after the failure and reported `159` tests with `1` failure. No second primary regression is hidden by the aggregate failures.
- The corrected lineage test cannot be claimed locally GREEN in this environment because it still stops earlier at the already recorded baseline-only HTTP 503 (`all status buckets render`), which reproduces on the clean pinned base. The separate dashboard HTTP 404 baseline likewise remains unresolved and is not claimed GREEN. Neither masks a candidate-specific local assertion failure, but the corrected lineage expectation still requires exact-source CI execution.
- The first CI run is correctly classified `FAILURE`, not GREEN. Because the correction changes executable source, a new exact-source GitHub CI run is required; it is pending/UNKNOWN. PR #236 is open. Merge and deployment remain unauthorized and UNKNOWN. This review approval does not promote CI, merge, or deployment to GREEN.

## Required corrections

None.

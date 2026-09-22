# CURRENT-CHECKLIST-STAGE-001 delivery record

## Scope and authorship

- Base/audit main: `0504d2589835f2583dc9afdbc47e4694e2573365`.
- Root authored scope, OpenSpec artifacts, normative specification and test.
- Independent Gate 3 reviewer: `/root/gate3_current_stage`, gpt-5.6-sol/low; final verdict `APPROVED` after one return.
- Production executor: `/root/implement_current_stage`, gpt-5.6-sol/low; changed only the two read-side production files.
- Merge/deploy: not authorized and not performed.

## Reproduction

`php tests/Yii2/yii2_current_checklist_stage_001_test.php` on unchanged production code exited 255 at `B closeout filter excludes retracted case before pagination`: returned row status was `Монтажные работы`, progress 83%, but the SQL `document_closeout` filter still returned it with total 1.

Retained RED: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790092166304681000-20453b3c1d684588a01d0bc88137b5a7.json`.

## Implementation

`MariaDbYiiObjectQueue::currentChecklistCompletionCount()` is the shared SQL read meaning for ordinary status filters and `stagePredicates()`. It keeps only the latest `item_completed`/`completion_retracted` per item by `accepted_revision,id`, treats legacy NULL revision as preceding nonnegative revisions, excludes item 42, and ignores attribution-only operations. Dashboard stage aggregates consume the same predicates. No schema, writer, offline/history or materialized status changed.

## Focused verification

- Acceptance GREEN: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790092759918297000-ab1f1c52a6a54a98b80d4c77460a73e7.json`.
- `python3 tests/Verification/change_verification_001_test.py`: GREEN, 18 tests.
- `python3 tests/Verification/architecture_guard_001_test.py`: GREEN, 59 tests.
- PHP lint for both changed production files and `git diff --check`: GREEN.

Complete local regression-failure inventory: `yii2_object_queue_lineage_001_test.php` returned HTTP 503 and `yii2_operational_dashboard_bar_charts_001_test.php` returned HTTP 404. Both exact commands reproduce unchanged on clean base `0504d258`; they remain unresolved pre-existing/environment failures and are not claimed GREEN. Their candidate evidence records are `1790092790123277000-63153195841f4f76a8b9258fb147771e` and `1790092798923440000-7a7731c1c01846abb90490f5bfcc8a91`.

## Remaining gates

Independent Gate 5, PR publication and one exact-source GitHub CI run are pending. Full local `make test` / `make verify` was not run.

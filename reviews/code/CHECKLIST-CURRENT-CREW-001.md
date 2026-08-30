# Code review: CHECKLIST-CURRENT-CREW-001

- Reviewer: separately tasked Codex agent `/root/code_review` (independent; did not author the specification, verifier, or implementation)
- Implementation author: Codex agent `/root`, working session `2026-08-30`
- Reviewed commit: working-tree `app/PilotHttp/ChecklistSync.php` diff at HEAD `513363b02570ee25a5d5c699630803a544f7cced`; reviewed file SHA-256 `9834bd40302c1d22eec8d646904768f19058fdff2c8c6ffc4ceb4bc2b5244391`
- Specification: [`specs/CHECKLIST-CURRENT-CREW-001.md`](../../specs/CHECKLIST-CURRENT-CREW-001.md), SHA-256 `7e8347b693e3271a0dc8a59d774c0beb87deaa63c49a4c57a50227496fbd7646`
- Approved test review: [`reviews/tests/CHECKLIST-CURRENT-CREW-001.md`](../tests/CHECKLIST-CURRENT-CREW-001.md), verdict `APPROVED`, SHA-256 `14b66ab851b1e7d42bf827a921852a96ae883e249724b11f472538c8d521b30d`
- Verification commands: configured isolated-container run of `php rapid-pilot/verify-checklist-current-crew.php` — `Checklist current crew contract OK.`; `php -l app/PilotHttp/ChecklistSync.php` — PASS; `php -l rapid-pilot/verify-checklist-current-crew.php` — PASS; focused `git diff --check` — PASS
- Verdict: `APPROVED`

## Findings

None.

### Standards

- The change is confined to the existing private `crew()` projection seam and does not introduce a second source of process truth, direct historical edits, authorization changes, or unrelated behavior.
- The correlated `MAX(version_no)` subquery is scoped to the same installation case and independently restricted to `status='registered'`. A higher prepared, superseded, or cancelled version therefore cannot displace the greatest registered basis.
- Production schema uniqueness on `(installation_case_id, version_no)` prevents an ambiguous pair of orders at the selected maximum version. Ordering the resulting installer rows by tab ID keeps projection output deterministic.
- Removing the former PHP de-duplication loop is appropriate: after selecting one exact order version, the order-installer primary key already guarantees one row per installer, so retaining the union/de-duplication behavior would obscure the intended invariant.
- No new duplication, speculative abstraction, feature envy, mutable-history path, or integration-boundary concern is introduced by the focused query correction.

### Spec

- With registered versions 1 and 2, the current checklist crew is sourced exclusively from the registered order whose `version_no` is the greatest; installers found only in earlier registered versions are not offered for new work.
- Both `projection()` and checklist item acceptance continue to call the same corrected `crew()` seam, so displayed selection and server-side validation use the same latest registered composition.
- Historical completed-item attribution remains sourced from `fm2_checklist_operation_installers`, keyed by the operation ID. The implementation does not update, delete, or filter those recorded rows by current order membership.
- For an installer removed from the latest crew, the historical row is projected from its stored name, position, employment-status, dismissal, and assignment-source snapshots. The accepted verifier observes current crew `['202']` and the prior completed item's installer `['101']` in the same projection.
- The focused implementation neither rebuilds checklist operations nor backfills over an operation that already has installer snapshots; the existing backfill query only targets legacy completed operations with zero stored installer rows.

## Required changes

None.

Gate 5 is approved for the identified `ChecklistSync.php` bytes at HEAD `513363b02570ee25a5d5c699630803a544f7cced`. Approval does not extend to unrelated working-tree changes.

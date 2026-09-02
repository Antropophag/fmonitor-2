# INSPECTION-ITEM-COMPLETE-001 — Gate 5 UI code review

Verdict: `APPROVED`

Reviewer: separately tasked independent agent `/root/item_ui_code_review`

Date: 2026-09-01

## Scope

Reviewed the approved executable specification and OpenSpec design, Gate 2 UI
RED evidence, independent Gate 3 rereview, the executable browser harness, and
the production diff limited to:

- `app/PilotHttp/ChecklistView.php`
- `app/PilotHttp/checklist.js`

The reviewed spec/test/evidence hashes still match the hashes recorded by Gate
3. Production and tests were not changed by this review.

## Findings

No blocking or non-blocking findings.

The renderer derives two explicit permissions. `itemCompletionEnabled` admits
an opened object's legacy-assigned/role users as before and additionally admits
an active user carrying exact `inspection.item.complete`. The separate
`legacyOperationsEnabled` remains exactly the prior opened-object plus assigned
engineer/role gate. Consequently the capability-only engineer receives usable
single-item toggles and no inert ancestor, while photo inputs, installer-edit
buttons and section/bulk buttons remain disabled. Existing assigned-engineer
and role access continues to enable both flags, preserving the legacy UI
behavior.

The client consumes the two server-derived flags without broadening the new
capability. Item-toggle handlers enqueue only `item_completed` under
`itemCompletionEnabled`; bulk/section completion, photo upload/revoke and
installer correction continue to require `legacyOperationsEnabled`. Automatic
section completion is also legacy-gated. The operation admission predicate used
by synchronization and retry allows `item_completed` for the new capability but
filters queued legacy photo, installer and section operations for a
capability-only session, so an old device queue cannot bypass the narrowed UI.
For legacy-assigned/role users that predicate still admits every existing
operation type.

The change is confined to presentation/client admission and does not add a
writer, runtime DDL, direct domain mutation, or fallback around the public
application seam. The reviewed executable interaction observes the real HTML
and served client: all 42 item toggles are usable, 16 photo inputs, 42 installer
controls and 8 bulk controls remain disabled, and clicking an item persists and
sends only `item_completed`.

## Independent verification

Passed:

```text
php -l app/PilotHttp/ChecklistView.php
node --check app/PilotHttp/checklist.js
php -l tests/InstallationProcess/inspection_item_complete_001_ui_client_test.php
node --check tests/InstallationProcess/support/inspection_item_complete_ui_browser.js
php tests/InstallationProcess/inspection_item_complete_001_ui_client_test.php
php tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
make architecture-check
```

Observed focused results:

```text
PASS: INSPECTION-ITEM-COMPLETE-001 raw HTTP endpoint admission
ARCHITECTURE CHECK PASSED (7 rules)
```

The UI runner intentionally reuses the endpoint runner and passed with its
item-only browser contract enabled. The separate endpoint run also preserved
the capability-only 403 for a non-item operation.

## Verdict

`APPROVED`. The minimal GREEN closes the final capability-only UI gap without
opening photos, photo revocation, installer correction, bulk/section completion
or queued legacy synchronization, and without regressing legacy assigned/role
behavior.

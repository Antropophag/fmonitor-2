# PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001 — Gate 2 RED evidence

Date: 2026-09-02  
Author role: RED author `/root/grill009_fresh_rereviews`  
Gate status: **INTENDED RED / ROUTE-FAMILY EXPANSION OPEN**

## Approved contract

Gate 1 exact package is approved in
`docs/operations/pilot-work-navigation-removal-exact-hash-approval-2026-09-02.md`.
The approved executable hash is:

```text
17d383f8dc12d2f08789f9f2e196cffd50b5dad1166cdd5ca5722b41dc318626  specs/PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001.md
```

## Intended RED obtained

The new test invokes the real configured shared renderer for every exact route
family/current-screen composition named by the executable contract. It checks
the public navigation DOM for visible/hidden/accessibility labels, exact root
destination/current substitutes, repeat bytes, content preservation and exact
minimal/broad sibling sequences.

```text
php tests/InstallationProcess/pilot_work_navigation_item_removal_001_test.php

PHP Fatal error: Uncaught TestFailure:
/pilot/ no visible or hidden work label
Expected: 0
Actual: 2
```

Classification: **INTENDED RED**. Current `PilotView` still emits the disabled
«Моя работа» item; the failure is exact removal behavior, not setup or an
unrelated assertion. Production was not changed.

## Canonical HTTP intended RED

The existing canonical raw-HTTP object-list verifier was updated only at its
superseded navigation predecessor assertion: it now expects no «Моя работа»
item and no `/pilot/` navigation destination while leaving its RBAC matrix and
facts untouched.

The test-owned canonical actor mismatch was corrected in
`PilotObjectReadRbacFixture` without changing any role, permission or near-match
fixture. Focused execution now reaches the navigation predecessor assertion:

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/pilot_object_list_001_test.php

approved removal predecessor: no work item or root navigation destination
Expected: 0
Actual: 2
```

Classification: **INTENDED RED** at the canonical raw-HTTP seam. Route setup,
identity and object-list authorization pass before the exact removed-item
assertion; current production emits both the disabled label and its representing
navigation node. The existing RBAC matrix/facts after this predecessor assertion
were not weakened.

Task 2.3 is complete. Tasks 2.1–2.2 remain open because exact canonical raw-HTTP
coverage still needs successful representations for every other enumerated
route family plus the complete inherited redirect/error/sibling/zero-write
matrix. The renderer test covers their common composition sensitivity but does
not substitute for the required raw-HTTP calls. A fresh test review must not
approve this intermediate state as Gate 3.

## Existing canonical HTTP fixture inventory

The RED author inventoried existing public-entrypoint tests before considering
any new schema graph. Existing fixtures are reusable evidence/setup owners as
follows; none is silently treated as part of the removal verifier until it has
an approved absence assertion:

| Governed family | Existing canonical HTTP fixture | Current removal status |
|---|---|---|
| `/pilot/` | `pilot_http_auth_001_test.php`, `pilot_ui_shell_001_test.php` | Healthy GET/HEAD fixture, but both retain predecessor positive `Моя работа` assertions. Updating those assertions belongs to coordinated regression work; the focused removal test currently provides only renderer-level RED. |
| `/pilot/objects` | `pilot_object_list_001_test.php` | Canonical GET/HEAD removal assertion installed; intended RED `Expected: 0 / Actual: 2` occurs after identity/admission and before the unchanged RBAC matrix. |
| `/pilot/objects/{positive-id}` | `pilot_object_card_001_test.php`, `pilot_ui_shell_001_test.php`, `pilot_prepare_form_001_test.php` | Healthy canonical card fixtures exist; no isolated removal assertion yet. `pilot_object_card_001_test.php` is a large shared verifier and must be edited only in a serialized pass. |
| `/pilot/objects/{positive-id}/assignment-order/prepare` | `pilot_prepare_form_001_test.php`, `pilot_ui_shell_001_test.php` | Canonical GET/HEAD fixtures exist, but `pilot_prepare_form_001_test.php` is an active RBAC hotspot. Do not mix navigation edits into its amended Gate 2 cycle. |
| `/pilot/objects/{positive-id}/checklist` | `inspection_item_complete_001_endpoint_admission_test.php`, `installation_completion_runtime_ddl_001_test.php` | Healthy HTTP 200 fixture exists. Its schema/persistence setup is owned by completion/admission slices; navigation assertion still needs a serialized, read-only insertion. |
| `/pilot/construction-control` | `inspection_planning_runtime_ddl_001_test.php` | Healthy HTTP 200 fixture exists, but it is a migration/runtime-DDL verifier rather than a navigation fixture. Reuse requires a bounded DOM assertion without changing its healthy/missing/incompatible matrix. |
| `/pilot/construction-control/objects/{positive-id}/checklist` | `pilot_e2e_flow_001_test.php` and checklist admission fixtures contain the route family | No standalone healthy removal fixture was found. `pilot_e2e_flow_001_test.php` is an explicitly serialized active RBAC/PDF hotspot, so it is a predecessor coordination blocker for this family. |
| `/pilot/installers` | `pilot_ui_shell_001_test.php` supplies workforce/schema data and the shared renderer test names the screen | No existing canonical HTTP success call for this exact route was found. `LocalRbacFixture` now accepts an explicit test-owned `fullName`, and UI-shell requests `Сидоров Сергей Сергеевич`; execution nevertheless fails first at the older structural `shell identity` header assertion (expected 1 / actual 0) because the current shared shell presents identity in the sidebar. This predecessor contract mismatch must be reconciled before the setup can safely host removal RED. |
| `/pilot/admin/users` | CSP inventory/classifier tests name the route | No healthy production HTTP/DB fixture was found; CSP classifier coverage is not route rendering. Needs a canonical local-RBAC admin fixture, not a reconstructed application graph. |
| `/pilot/admin/roles` | CSP inventory/classifier tests name the route | Same blocker as `/pilot/admin/users`: route classification exists, successful production HTTP representation fixture does not. |

This inventory prevents duplication of checklist/workforce/process schemas and
keeps tasks 2.1–2.2 open. The next safe RED-author step is a serialized pass over
the healthy card, checklist and construction-control fixtures; the prepare and
E2E hotspots remain excluded until their current Gate 2 edits settle.

The failed UI-shell reuse attempt is classified **PREDECESSOR BLOCKER**, not
navigation RED: execution never reached `/pilot/installers` or either admin
route. No opt-in code from that attempt remains in the shared verifier. Focused
checks after the fixture correction classify the surrounding shared state as:

- object-list reaches the exact intended navigation RED (`Expected: 0 / Actual:
  2`), proving its approved actor name is preserved;
- object-card stops at an unrelated `200` versus `503` predecessor failure;
- prepare stops at an unrelated missing safe-correlation assertion on `503`;
- UI-shell stops at the structural header-versus-sidebar identity predecessor.

These failures are not reclassified as removal evidence and no corresponding
production or hotspot assertion was weakened.

## Hygiene

```text
php -l tests/InstallationProcess/pilot_work_navigation_item_removal_001_test.php
No syntax errors detected

php -l tests/InstallationProcess/pilot_object_list_001_test.php
No syntax errors detected

php -l tests/Support/PilotObjectReadRbacFixture.php
No syntax errors detected

git diff --check -- tests/InstallationProcess/pilot_work_navigation_item_removal_001_test.php \
  tests/InstallationProcess/pilot_object_list_001_test.php \
  docs/operations/pilot-work-navigation-removal-red-evidence.md
exit 0, empty output
```

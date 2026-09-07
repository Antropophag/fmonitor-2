# Independent focused review — selection picker manual pilot

- Verdict: **APPROVED**
- Reviewer: `/root/workforce_schedule_audit`; reviewer did not author the reviewed implementation or tests.
- Date: 2026-09-07.
- Scope: bounded installer search, restored modal selection/original surfaces, route/CSP/static-asset wiring and focused tests in the shared working tree.
- This is focused manual-pilot review evidence, not a full Gate 5, browser-stand, deployment or production-readiness claim.

## Findings

No blocking findings remain.

An initial review found that complete unknown-employment proof validation occurred
after `LIMIT 21`, which could let ineligible rows consume a page. The backend author
corrected the query before final review: employment interval, delivered state,
authority/delivery identity, positive delivery person, completed current run,
metadata linkage, positive page/delivery counts, UUID and checksum predicates now
apply before the stable `ORDER BY ... LIMIT 21 OFFSET ...`. The existing command
owner still revalidates submitted installer eligibility; search remains read-only.

The endpoint requires the exact selection capability, GET/HEAD, a two-to-120-character
query and bounded positive page. It returns at most 20 rows plus `hasMore`; the SQL
binds user text, uses `LOCATE` for literal name/personnel matching, and normalizes
leading-zero personnel queries without interpolating them. The initial selection
page contains only the already saved selection and never embeds the full workforce
catalogue.

The UI restores the existing order layout and native `<dialog>` picker. A client-side
map preserves choices while paging and closing/reopening the modal; saved choices
are rendered back into hidden command inputs on reload. New response values are
inserted with `textContent`; server-rendered values use the shared HTML escaper.
Native dialog cancellation and close behavior retain keyboard Escape handling and
restore focus to the opener. Stale fetches are aborted and generation-checked.
No reviewed selection/view/route file introduces a legacy or direct domain writer.

## Source evidence

```text
95b4c65a11e57a12c5e4d9c19c094cadad2edafcb58c4aad549eabfe96db049f  app/AssignmentOrderComposition/AssignmentOrderSelectionPortalQuery.php
3168a4cc6c3a3f6e3e6322496ccf97fe24de735d58b35e0362c6066347b3933c  app/AssignmentOrderComposition/MariaDbSelectionPortalQuery.php
738d63e732ba5f69af75bc397b0c0506c0532811d0efcce5e7375f107e7c9346  app/PilotHttp/FreshOrderHttpCoordinator.php
757c5bf3ab7c1c8e2fefff6844d52cb8cd578e0108841c7b45a56997a12c3b14  app/PilotHttp/FreshOrderHttpHandler.php
ae8a1befec83ac81e93fc13019293c22a7bdbde18181fa43fcb74455e66a84ad  app/PilotHttp/FreshOrderSelectionView.php
c6462ae17361b7e8c072f812666c8794c4ae3eab0e36390b0b1898c918131940  app/PilotHttp/OriginalUploadView.php
74a5ce3fd10cf91ddcb2a640049fa735a6fe655ca32b36d40c28b14be1575927  app/PilotHttp/PilotRouteAdmission.php
d2599dc0e2a5489e899216e42e4d2031bb5bb7a3b3402579e321a905f5b44aaf  app/PilotHttp/PilotRouteCsp.php
eebcb3975209b119e04704bfaa7b786950ef53f046f9a233a6330984c805c703  app/PilotHttp/selection-picker.js
e29eab9f849f0f7f7f3a1a3ec18e7b71cd0370b66840924ef4c495e4e4af248b  app/PilotHttp/original-upload.js
dadfb237a9f2f8ec8989573b4ed1537c1905d4a469a2cb34f911fe77245a1b5d  rapid-pilot/router.php
7a6d88e10f38f0430d50d5ee3ad40b26214fba458a4150f13d858e6a627f85fb  rapid-pilot/pilot.css
7888f1f9772b3db415bbf1a98922539648471258b74e96c709c8b0ac707c4f83  tests/AssignmentOrderComposition/installer_search_http_manual_pilot_test.php
7c5de67906066dda38f26e8b2fc3dbed9c74561bcd16169e4630840b004c402f  tests/AssignmentOrderComposition/selection_picker_view_manual_test.php
```

## Verification

- `php tests/AssignmentOrderComposition/installer_search_http_manual_pilot_test.php` — PASS.
- `php tests/AssignmentOrderComposition/selection_picker_view_manual_test.php` — PASS.
- `php tests/InstallationProcess/selected_original_lifecycle_001_test.php` — 6/6 PASS with setup and cleanup.
- PHP lint for both changed views — PASS.
- Node syntax checks for both changed scripts — PASS.
- `git diff --check` — PASS at reviewed source.

Real stand browser interaction remains owned and reported separately by the root
flow; this review does not infer that evidence from source-level checks.

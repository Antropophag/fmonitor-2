# PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001 v2 — integration RED v5

- Captured: `2026-09-04T16:26:23+03:00` through
  `2026-09-04T16:29:00+03:00`
- Pre-test integration head: `8b99f6696d6dbe00f9d76c96146948262159c4d6`
- Scope: Gate 2 tests/evidence only; no production bytes changed.
- Result: **INTENDED RED**. Gate 3 remains open.

## Approved contract and current integration lineage

The exact Gate 1 v2 planning hashes remain unchanged:

```text
ffb72c0602a26e24aa86f7df339bcc209f6b0ce894f8a41988527c62e9db8c65  specs/PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001.md
44724732faad0fa0aae318ee64df41a53b496b1231b1997aa1f3a793903c4230  openspec/changes/remove-pilot-work-navigation-item/proposal.md
6dd91e84e023b21f82ff5884ca181e228c7e6b43f006ceec4b9490926e7d11b1  openspec/changes/remove-pilot-work-navigation-item/design.md
888bfabec7f079c9a5bc21ebf1093cded10c08dde131e6169fd9f37b24225504  openspec/changes/remove-pilot-work-navigation-item/specs/ui/pilot-work-navigation-item-removal/spec.md
```

The integration predecessor is the freshly approved UI-shell evidence head
`8b99f669...`. Its independent Gate 5 record
`reviews/code/PILOT-UI-SHELL-001-upload-first-integration-v4.md` has SHA-256
`47fbb292797b24e1772d3a8deb7a26a27b78818a7137c1f7cecaff9fdfd7a109`
and explicitly excludes navigation removal. The bounded Prepare and Card
completion records have SHA-256 `fa51c2ad...` and `e4544d54...` respectively.
They are predecessor evidence only and are not reused as approval of this
changed integration composition.

## Current Gate 2 inputs

```text
3e0a910f293e4601f46b3e8e5c6a2dc3586e58f8154e79a224b13d7505cceff5  tests/InstallationProcess/pilot_work_navigation_item_removal_001_test.php
a34d50cfcb58ba3e16e2215defab2aaa39c0aacfffdeaaa5feb89113eeb2bbc9  tests/InstallationProcess/pilot_http_auth_001_test.php
62c3ad3cf0ed8ebe18fc07009b41e799e61ecc3c17921e27adaaf746898aa2cf  tests/InstallationProcess/pilot_object_list_001_test.php
3d0d01da364cb9575793bf43e15389371ac73ffb633b34b79963c1191e2d065d  tests/InstallationProcess/pilot_object_card_001_test.php
59552423291008f1fa9b42a33a5523a988522c8c8b1841c05d2496a410be7611  tests/InstallationProcess/pilot_prepare_form_001_test.php
3a882c110496772d741340b2c1f43b8725cbbbb15e0319ee5446c0d76b7bed6f  tests/InstallationProcess/pilot_ui_shell_001_test.php
c7446e5345d82d3fd548c6e4da6fdc11763aae91c734ea345dac53bc04cdac6e  openspec/changes/remove-pilot-work-navigation-item/tasks.md
```

The UI-shell HTTP verifier now applies the approved absence to each configured
root, object-list, card and prepare representation. Exact root behavior remains
separate: document title and main `h1` are still `Моя работа`, while the shared
navigation has no such label, accessible substitute, `/pilot/` destination or
root current marker. Object-list/card/prepare retain `Объекты монтажа` as their
one exact current item. Scripts, CSP, CSS ownership, breadcrumbs, content and
compatibility composition assertions are preserved. The unconfigured
compatibility composition is intentionally unchanged because sections 1 and 3
of the controlling executable spec exclude it from DOM-removal scope.

## Reproduced results

With `FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local`:

```text
pilot_work_navigation_item_removal_001_test.php
  RED /pilot/ no visible or hidden work label: Expected 0, Actual 2

pilot_http_auth_001_test.php
  reached authenticated 200 scripted shared shell, then
  RED work navigation removed: Expected 0, Actual 1

pilot_object_list_001_test.php
  reached configured object-list/RBAC representation, then
  RED approved removal predecessor: Expected 0, Actual 1

pilot_object_card_001_test.php
  reached Example A configured shared-shell DOM, then
  RED exact anchor multiset: unexpected /pilot/ anchor

pilot_prepare_form_001_test.php
  PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form

pilot_ui_shell_001_test.php
  reached configured root 200/CSP/script/identity composition, then
  RED shell approved work navigation removal: Expected 0, Actual 1

pilot_route_csp_inventory_001_test.php
  PASS
```

This is one causal failure: current production still renders the removed item.
No production removal has been made. The root sentinel's formerly stale
predecessor CSP/script expectations were aligned to the exact independently
approved UI-shell composition before observing the navigation RED; error and
non-script response security expectations remain unchanged.

The direct production-renderer verifier enumerates all ten approved current
states and pins literal remaining-child hashes for minimal and broad actors.
The two canonical HTTP sentinels are root and object-list. Card and Prepare
current route tests now reach their approved post-Prepare/UI-shell behavior;
Card already fails solely on the extra root navigation anchor and Prepare stays
GREEN on its own admission/content contract.

## Noncanonical consumers and E2E exclusion

Sections 3 and 7 of `PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001 v2` and decision
`pilot-work-navigation-deep-seam-owner-decision-2026-09-03.md` require the ten-
state production renderer oracle, two canonical HTTP sentinels and existing
route-specific wiring evidence; they explicitly reject duplicate per-route
fixture stacks. The UI-shell v11 evidence accepted by independent Gate 5 on
this exact predecessor contains eight configured-consumer cases and is used as
the consolidated wiring evidence for the noncanonical checklist,
construction-control, installer and administration consumers.

The blocked legacy business E2E is not used as navigation-removal evidence. It
owns different session/checklist/business predecessors, is not the approved
shared-renderer seam, and cannot replace or invalidate the exact ten-state
oracle. Fresh Gate 3 must review this composition and the current test hashes;
no old Gate 3 or Gate 5 approval is reused.

## Hygiene

```text
php -l tests/InstallationProcess/pilot_http_auth_001_test.php   PASS
php -l tests/InstallationProcess/pilot_ui_shell_001_test.php    PASS
openspec validate remove-pilot-work-navigation-item --strict   valid
git diff --check                                                PASS
```

OpenSpec tasks 2.1 and 2.2 are checked because their executable Gate 2 evidence
is now present. Task 2.4 remains open for a separately tasked independent test
reviewer. Gate 4 production work is not authorized before that verdict.

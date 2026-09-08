# PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001 v2 — configured sentinels RED v6

- Captured: `2026-09-04T16:42:02+03:00` through
  `2026-09-04T16:44:26+03:00`
- Clean predecessor/review head:
  `1e3ab503eb5119aee660a91a13ff105b7be89353`
- Scope: corrected Gate 2 tests and append-only evidence only.
- Verdict: **INTENDED RED; fresh Gate 3 required**.

## Correction

The root and object-list sentinels now explicitly provide a validated absolute
`FMONITOR_PILOT_CSS_PATH` ending in `pilot.css`, assert the public
`/pilot/assets/pilot.css` stylesheet link and assert the `fm2-shell` configured
composition before testing navigation removal. Existing unconfigured/
compatibility expectations are unchanged and do not assert removal, as required
by sections 1 and 3 of the executable specification.

The object-card anchor oracle no longer contains the stale third
`/pilot/objects` expectation. Its literal post-removal set is independently
named by DOM role, destination and normalized label:

```text
skip                #main-content   Перейти к содержанию
primary-navigation  /pilot/objects  Объекты монтажа
breadcrumb          /pilot/objects  Объекты монтажа
```

Only the capable-card case adds the approved process-action tuple for
`/pilot/objects/{id}/assignment-order/prepare` / `Загрузить распоряжение`.
Missing, extra, relocated or relabelled anchors remain observable.

## Exact test inputs

```text
3e0a910f293e4601f46b3e8e5c6a2dc3586e58f8154e79a224b13d7505cceff5  tests/InstallationProcess/pilot_work_navigation_item_removal_001_test.php
a16229cb573cf48abe743c993afdc968fc7925a92b3a0469d8ec908fcec0cf3a  tests/InstallationProcess/pilot_http_auth_001_test.php
cbd5ba188d00acff2d17485fcafdce451367a6e0354b7ac9ea167a0887f5dd7d  tests/InstallationProcess/pilot_object_list_001_test.php
82fbac131ae7200037b9a8287dca488f3fcbb0a9d83d8313643ff09f14ffdf13  tests/InstallationProcess/pilot_object_card_001_test.php
59552423291008f1fa9b42a33a5523a988522c8c8b1841c05d2496a410be7611  tests/InstallationProcess/pilot_prepare_form_001_test.php
3a882c110496772d741340b2c1f43b8725cbbbb15e0319ee5446c0d76b7bed6f  tests/InstallationProcess/pilot_ui_shell_001_test.php
c7446e5345d82d3fd548c6e4da6fdc11763aae91c734ea345dac53bc04cdac6e  openspec/changes/remove-pilot-work-navigation-item/tasks.md
```

Task 2.4 is reopened because the v5 independent approval predates these changed
test bytes. It cannot be reused for the changed integration composition.

## Unchanged-production RED

Against exact restored production `PilotView.php` SHA-256
`dc84358ebf4e1fbe879dc05140aecb6d8c72e18ef4fa0151bf1e8b8baeaba883`:

```text
pilot_work_navigation_item_removal_001_test.php
  /pilot/ no visible or hidden work label: Expected 0, Actual 2

pilot_http_auth_001_test.php
  configured shared shell reached; work navigation removed:
  Expected 0, Actual 1

pilot_object_list_001_test.php
  configured shared shell reached; approved removal predecessor:
  Expected 0, Actual 1

pilot_object_card_001_test.php
  exact configured role/href/label set contains the sole extra tuple
  primary-navigation /pilot/ Моя работа

pilot_prepare_form_001_test.php
  PASS

pilot_ui_shell_001_test.php
  configured root reached; approved work navigation removal:
  Expected 0, Actual 1
```

Every owned RED is the still-present configured navigation item.

## Test-only post-removal sensitivity probe

A temporary, uncommitted mutation removed the work anchor from both configured
navigation compositions in `PilotView::document()`. Removing it only from the
first branch did not affect root/list/card sentinels; this proves those public
routes select the second canonical-journey composition and disproves the prior
hypothesis that their RED came from missing CSS configuration. Removing it from
both branches produced:

```text
pilot_object_card_001_test.php
  PASS: PILOT-OBJECT-CARD-001 public HTTP card

pilot_http_auth_001_test.php
  navigation removal assertion passed; execution advanced to unrelated
  byte-exact uppercase legacy identity predecessor (Expected 403, Actual 200)

pilot_object_list_001_test.php
  navigation removal assertion passed; execution advanced to unrelated
  origin-filter predecessor (Expected 1, Actual 0)
```

Thus the corrected sentinels become sensitive to the intended minimal removal
and do not demand removal in the separately excluded compatibility composition.
The temporary production mutation was fully reverted with `apply_patch`; the
production hash above matches the clean predecessor and no production diff
remains.

## Hygiene

```text
php -l tests/InstallationProcess/pilot_http_auth_001_test.php    PASS
php -l tests/InstallationProcess/pilot_object_list_001_test.php PASS
php -l tests/InstallationProcess/pilot_object_card_001_test.php PASS
openspec validate remove-pilot-work-navigation-item --strict    valid
git diff --check                                                 PASS
```

No GREEN or Gate 4 claim is made. A separately tasked reviewer must approve
these exact v6 inputs before any production edit.

# Test review: PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001 configured sentinels RED v6

- Gate: 3 — fresh independent test review
- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/navigation_integration_gate3`
- Reviewed exact HEAD: `ffcd6964f2a8047c03e050d83b09a28689e27fd6`
- Verdict: **APPROVED**

The reviewer did not author the specification, OpenSpec artifacts, tests,
production, v6 RED evidence or mutation. This append-only record and rechecking
task 2.4 are the reviewer's only changes; no test or production byte was edited.
The earlier v4 approval remains historical and is not reused for changed test
bytes.

## Authority and reviewed correction

The normative v2 executable specification and proposal/design/delta retain the
exact hashes approved by the independent Gate 1 review and owner deep-seam
decision. v6 changes only three sentinel tests, the object-card oracle, the
append-only RED record and Gate status.

The root and object-list HTTP sentinels now resolve the repository
`app/PilotHttp/pilot.css` to an absolute regular path with exact basename,
configure `FMONITOR_PILOT_CSS_PATH`, protect those bytes from writes, and prove
both `/pilot/assets/pilot.css` and one `.fm2-shell` before evaluating absence.
Their RED therefore comes from the configured shared composition rather than
the excluded compatibility composition. The UI-shell test continues to assert
the compatibility variants separately: one predecessor stylesheet, no
`.fm2-shell`, and their existing scripts/DOM. No removal expectation was added
to compatibility output.

The card oracle now compares a literal multiset of exact
`role / href / normalized-label` tuples. The non-capable card has exactly:

```text
skip                #main-content   Перейти к содержанию
primary-navigation  /pilot/objects  Объекты монтажа
breadcrumb          /pilot/objects  Объекты монтажа
```

Only a capable card adds the exact process-action tuple ending in
`/assignment-order/prepare` with label `Загрузить распоряжение`. All tested
permissionless, legacy-authorized, capable, negative-action and state cases
reuse this oracle. Missing, extra, relocated, relabelled or incorrectly
permissioned anchors are observable; the stale duplicated href-only
expectation is gone without weakening card admission or content checks.

## Sensitivity and branch proof

The unchanged exhaustive renderer test still covers all ten approved current
states, minimal and broad actor compositions, exact siblings/order/current and
disabled semantics, accessibility names, hidden/renamed/icon-only substitutes,
root destination and literal element/icon hashes. Root/list/card/UI-shell HTTP
tests independently retain GET/HEAD, root page heading, route/error,
authorization and zero-write contracts. Prepare remains a route-specific GREEN
predecessor.

The v6 test-only mutation is methodologically valid. `PilotView::document()`
contains two configured branches: the full permission-aware branch at lines
50–62 and the canonical-journey branch at lines 64–66. Removing the item only
from the first branch leaves root/list/card sentinels RED; removing it from both
makes their navigation assertions pass. That is the expected proof that the
direct ten-state oracle and canonical HTTP sentinels require both production
branches. Compatibility renderers are separate callers and were not mutated.

Reversion is exact: SHA-256 of `app/PilotHttp/PilotView.php` is
`dc84358ebf4e1fbe879dc05140aecb6d8c72e18ef4fa0151bf1e8b8baeaba883`
in both parent `ffcd696^` and reviewed `ffcd696`; `git diff
1e3ab503..ffcd696 -- app rapid-pilot` is empty. The worktree was clean before
this review. There is no production residue or unreviewed GREEN.

After the two-item mutation, the root and list suites advance respectively to
the later uppercase-legacy-identity and origin-filter assertions. Those later
failures are externally owned integration predecessors. Their ordering proves
the configured navigation assertions have already passed; they do not turn
the navigation GREEN into a false RED or authorize changes to identity/origin
behavior.

## Independent reproduction

On exact reviewed HEAD, with the canonical test database available:

```text
2026-09-04T16:46:08+03:00
pilot_work_navigation_item_removal_001_test.php
  intended RED: /pilot/ label absence, Expected 0, Actual 2

2026-09-04T16:46:09+03:00
pilot_http_auth_001_test.php
  configured shell assertions passed, then intended navigation RED,
  Expected 0, Actual 1

2026-09-04T16:46:10+03:00
pilot_object_list_001_test.php
  configured shell assertions passed, then intended navigation RED,
  Expected 0, Actual 1

2026-09-04T16:46:10+03:00
pilot_object_card_001_test.php
  intended exact tuple RED: sole extra tuple is
  primary-navigation /pilot/ Моя работа

2026-09-04T16:46:11+03:00
pilot_prepare_form_001_test.php
  PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form

2026-09-04T16:46:21+03:00
pilot_ui_shell_001_test.php
  configured root reached, then intended navigation RED,
  Expected 0, Actual 1
```

`openspec validate remove-pilot-work-navigation-item --strict` passes and
`git diff --check` is empty.

## Exact reviewed hashes

```text
ffb72c0602a26e24aa86f7df339bcc209f6b0ce894f8a41988527c62e9db8c65  specs/PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001.md
44724732faad0fa0aae318ee64df41a53b496b1231b1997aa1f3a793903c4230  openspec/changes/remove-pilot-work-navigation-item/proposal.md
6dd91e84e023b21f82ff5884ca181e228c7e6b43f006ceec4b9490926e7d11b1  openspec/changes/remove-pilot-work-navigation-item/design.md
888bfabec7f079c9a5bc21ebf1093cded10c08dde131e6169fd9f37b24225504  openspec/changes/remove-pilot-work-navigation-item/specs/ui/pilot-work-navigation-item-removal/spec.md
3e0a910f293e4601f46b3e8e5c6a2dc3586e58f8154e79a224b13d7505cceff5  tests/InstallationProcess/pilot_work_navigation_item_removal_001_test.php
a16229cb573cf48abe743c993afdc968fc7925a92b3a0469d8ec908fcec0cf3a  tests/InstallationProcess/pilot_http_auth_001_test.php
cbd5ba188d00acff2d17485fcafdce451367a6e0354b7ac9ea167a0887f5dd7d  tests/InstallationProcess/pilot_object_list_001_test.php
82fbac131ae7200037b9a8287dca488f3fcbb0a9d83d8313643ff09f14ffdf13  tests/InstallationProcess/pilot_object_card_001_test.php
59552423291008f1fa9b42a33a5523a988522c8c8b1841c05d2496a410be7611  tests/InstallationProcess/pilot_prepare_form_001_test.php
3a882c110496772d741340b2c1f43b8725cbbbb15e0319ee5446c0d76b7bed6f  tests/InstallationProcess/pilot_ui_shell_001_test.php
a596432a604fd89ecad34626111ac9b3f2baca8a3c76d2d96b2a1139d84f4e0a  docs/operations/pilot-work-navigation-removal-integration-red-v6-2026-09-04.md
dc84358ebf4e1fbe879dc05140aecb6d8c72e18ef4fa0151bf1e8b8baeaba883  app/PilotHttp/PilotView.php
```

## Verdict

**APPROVED.** The corrected tests exercise the intended configured public seams,
retain the exhaustive absence and preservation sensitivity, and fail unchanged
production only because both configured compositions still contain the removed
item. Gate 4 may perform the minimal removal from those two shared composition
branches. This verdict does not approve production, GREEN, Gate 5,
repository-wide verification, CI or release readiness.

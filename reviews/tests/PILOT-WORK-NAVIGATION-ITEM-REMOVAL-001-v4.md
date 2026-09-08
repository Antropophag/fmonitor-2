# Test review: PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001 v2 integration RED v5

- Gate: 3 — independent test review
- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/navigation_integration_gate3`
- Independence: this reviewer authored none of the specification, OpenSpec
  artifacts, tests, production, predecessor evidence or RED evidence
- Reviewed exact HEAD: `ea48ec8f8ddd17c65677b2d66c2adfca92faf43e`
- Verdict: **APPROVED**

This append-only review record and the Gate 3 task checkbox are the reviewer's
only changes. No specification, test or production byte was edited.

## Gate 1 authority and scope

The owner-approved v2 contract is unchanged at its reviewed exact hashes. The
fresh independent Gate 1 approval is
`docs/operations/pilot-work-navigation-gate1-rereview-v2.md`; the owner then
approved the exhaustive production shared-renderer seam, two canonical real
HTTP sentinels and existing route-focused wiring evidence in
`docs/operations/pilot-work-navigation-deep-seam-owner-decision-2026-09-03.md`.
That decision deliberately rejects eight duplicate database/server fixture
stacks. The historical `restore-pilot-work-navigation` approval does not apply
and was not reused.

The test change therefore does not need the blocked legacy
`PILOT-E2E-FLOW` as a navigation oracle. That suite owns session/checklist and
business-flow predecessors outside this presentation-only behavior. The
controlling topology is satisfied by the exact ten-state production renderer,
real root and object-list GET/HEAD sentinels, focused route/admission tests, and
the production-identical configured-consumer evidence accepted by fresh
UI-shell Gate 5. This is use of the approved seam, not a new requirement or a
waiver of an approved one.

## Traceability, seam and sensitivity

`pilot_work_navigation_item_removal_001_test.php` invokes the production
`PilotView::document()` public representation seam for all ten governed current
states: root, object list, card, prepare, object checklist, construction-control
queue/checklist, installers, admin users and admin roles. Minimal and broad
actors traverse both conditional sibling compositions. The common oracle runs
after each render and rejects:

- normalized visible or hidden exact `Моя работа` text;
- direct and referenced accessible names, including `aria-label`, `title`,
  image `alt` and `aria-labelledby`;
- exact `/pilot` or `/pilot/` anchors and root current/disabled substitutes;
- hidden, renamed or icon-only replacement through the fixed direct-child
  cardinality and literal full-element SHA-256 vectors.

The independently fixed vectors preserve order, exact labels/destinations,
conditional visibility, `aria-current`, disabled/accessibility attributes and
inline icon bytes for every remaining sibling. Separate full literal snapshots
for minimal object-list and broad administration compositions make accidental
group/item loss or reorder observable. Root has no replacement current item;
object/card/prepare states keep exactly `Объекты монтажа` current, and each
privileged state keeps its own exact current item.

Each of the ten renders is repeated byte-for-byte and preserves supplied
content bytes. The canonical root and object-list HTTP tests additionally pin
successful GET, empty-body HEAD with GET status/header/Content-Length parity,
read-only database/filesystem observations, exact inherited redirect/method
and `401/403/404/405/503` response behavior. The root tests separately retain
the document title and main `h1` `Моя работа`; only the navigation item is
removed. Thus a broad removal of the route or page content cannot satisfy the
test.

The object-list predecessor update is bounded to the superseded navigation
expectation. Its canonical-grant revoke probe, complete negative-actor matrix,
identity/admission checks, list facts, pagination, failures and zero-write
snapshots remain intact. The test reaches authenticated status `200` before
failing on the one current production anchor; it is not made green by weaker
RBAC setup.

## Intended RED and predecessor reproduction

After `TEST_DB_RESET_OK` and migration through schema version `11`, the exact
reviewed HEAD produced the intended causal failures:

```text
2026-09-04T16:32:27+03:00
pilot_work_navigation_item_removal_001_test.php
  /pilot/ no visible or hidden work label: Expected 0, Actual 2

2026-09-04T16:32:28+03:00
pilot_http_auth_001_test.php
  work navigation removed: Expected 0, Actual 1

2026-09-04T16:32:29+03:00
pilot_object_list_001_test.php
  approved removal predecessor: no work item or root navigation destination
  Expected 0, Actual 1

2026-09-04T16:32:29+03:00
pilot_object_card_001_test.php
  configured anchor multiset contains the sole unexpected `/pilot/` anchor

2026-09-04T16:32:39+03:00
pilot_ui_shell_001_test.php
  shell approved work navigation removal: Expected 0, Actual 1
```

All failures occur after setup and a successful production representation. The
prepare route-specific contract remains GREEN because it does not duplicate
the shared navigation assertion:

```text
2026-09-04T16:32:30+03:00
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form

2026-09-04T16:32:47+03:00
pilot_route_csp_inventory_001_test: PASS

2026-09-04T16:34:22+03:00
pilot_route_csp_001_test: PASS

2026-09-04T16:34:42+03:00
PASS: PILOT-UI-SHELL-001 actual CSS ownership
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 public seam contract
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 real GET /pilot/objects admission
```

The separately approved UI-shell v11 consolidated evidence contains twelve
canonical layout cases, three picker cases and eight configured-consumer cases
for construction-control, checklist, installers and administration, all GREEN.
Its report pins candidate `d17803a9a259ea85e8b551ec70db2f85cf437768`.
`git diff --quiet d17803a..ea48ec8 -- app/PilotHttp` exits `0`, so the current
pre-removal production bytes are identical to that reviewed candidate.

Additional inspection-planning and session/UserAccess suites were also
sampled and remain RED at their already-owned `503`/payload predecessors. They
do not fail on navigation and are not substituted for the approved navigation
oracle. The inspection endpoint probe reaches its public checklist page before
its later JSON operation failure. These known repository-wide predecessors do
not invalidate the successful configured-renderer and focused route-wiring
evidence, and this approval makes no claim that they or `make verify` are
GREEN.

## Exact reviewed hashes

```text
ffb72c0602a26e24aa86f7df339bcc209f6b0ce894f8a41988527c62e9db8c65  specs/PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001.md
44724732faad0fa0aae318ee64df41a53b496b1231b1997aa1f3a793903c4230  openspec/changes/remove-pilot-work-navigation-item/proposal.md
6dd91e84e023b21f82ff5884ca181e228c7e6b43f006ceec4b9490926e7d11b1  openspec/changes/remove-pilot-work-navigation-item/design.md
888bfabec7f079c9a5bc21ebf1093cded10c08dde131e6169fd9f37b24225504  openspec/changes/remove-pilot-work-navigation-item/specs/ui/pilot-work-navigation-item-removal/spec.md
3e0a910f293e4601f46b3e8e5c6a2dc3586e58f8154e79a224b13d7505cceff5  tests/InstallationProcess/pilot_work_navigation_item_removal_001_test.php
a34d50cfcb58ba3e16e2215defab2aaa39c0aacfffdeaaa5feb89113eeb2bbc9  tests/InstallationProcess/pilot_http_auth_001_test.php
62c3ad3cf0ed8ebe18fc07009b41e799e61ecc3c17921e27adaaf746898aa2cf  tests/InstallationProcess/pilot_object_list_001_test.php
3d0d01da364cb9575793bf43e15389371ac73ffb633b34b79963c1191e2d065d  tests/InstallationProcess/pilot_object_card_001_test.php
59552423291008f1fa9b42a33a5523a988522c8c8b1841c05d2496a410be7611  tests/InstallationProcess/pilot_prepare_form_001_test.php
3a882c110496772d741340b2c1f43b8725cbbbb15e0319ee5446c0d76b7bed6f  tests/InstallationProcess/pilot_ui_shell_001_test.php
b3f20f9ae13d56b6b485ad049e2d8df1f6dd6fa333c2e67a793b415096277982  docs/operations/pilot-work-navigation-removal-integration-red-v5-2026-09-04.md
47fbb292797b24e1772d3a8deb7a26a27b78818a7137c1f7cecaff9fdfd7a109  reviews/code/PILOT-UI-SHELL-001-upload-first-integration-v4.md
c43a3d9993b77bf290cd23e39f948a7312bd373ea61bddb804b89bdd8c816cc0  ui-shell-consolidated-v11-d17803a/report.json
1cc1cf993d6d93149e4e43177fa8e39701a0b379131529d893428fe58ff98023  ui-shell-consolidated-v11-d17803a/runtime.json
fa51c2ad7aae5eb13b32a013c67b100d8676b9b113b28888d4ac3eec2a9c6333  docs/operations/pilot-prepare-rbac-gate5-completion-2026-09-04.md
e4544d546bf4c957e9c0d177ea53671d74bbe3a9da1c20666177de1877c6fe8b  docs/operations/pilot-object-card-gate5-v1-correction-green-2026-09-04.md
```

The two UI-shell report files are external primary evidence at
`/home/antropophag/code/fmonitor-2-visual-tools/evidence/ui-shell-consolidated-v11-d17803a/`;
their hashes were recomputed during this review.

## Verdict

**APPROVED.** The tests are traceable to the exact owner-approved v2 contract,
exercise the approved public seams, are deterministic and sensitive to the
specified visible/accessibility/hidden/renamed/icon/root-destination variants,
preserve root content, siblings, transport, authorization and zero-write
behavior, and fail current production for the intended missing removal only.
Gate 4 may now make the minimal shared-composition production edit. This review
does not approve production, GREEN, Gate 5, repository integration, CI or
release readiness.
